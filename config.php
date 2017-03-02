<?php

@include_once('config-local.php');
@include_once('config-srihashes.php');
require_once('constants.php');


if (!defined('LDAP_HOST')) {
    header('HTTP/1.0 500 LDAP Error');
    print('LDAP_HOST is not defined!');
    die;
}

/*************************************************************************/

class MozillaAuthAdapter extends AuthAdapter {
  public function check_valid_user($user) {
    return preg_match('/^[a-z.]+@(.+?)\.(.+)$|^[a-z]+$/', $user);
  }

  public function user_to_dn($user) {
    if (preg_match('/^[a-z]+$/', $user)) {
      return "mail=$user@mozilla.com,o=com,dc=mozilla";
    }
    preg_match('/^[a-z.]+@(.+?)\.(.+)$/', $user, $m);
    if ($m[1] == "mozilla" && $m[2] == "com") {
      // pass
    } elseif ($m[1] == "mozilla-japan" && $m[2] == "org") {
      $m[1] = "mozilla";
      $m[2] = "com";
    } elseif (strpos($m[1], "mozilla") === 0 && $m[2] == "org") {
      $m[1] = "mozilla";
      $m[2] = "org";
    } else {
      $m[1] = "mozilla";
      $m[2] = "net";
    }
    return "mail=$user,o={$m[2]},dc={$m[1]}";
  }

  public function dn_to_email($dn) {
    if (preg_match("/mail=(.*),o=/", $dn, $m)) {
      return $m[1];
    }
    return NULL;
  }

  public function email_to_dn($ldapconn, $email) {
    $user_s = ldap_search($ldapconn, "dc=mozilla", "mail=" . $email);
    $user_s_r = ldap_get_entries($ldapconn, $user_s);
    if ($user_s_r['count'] != 1) {
      die("Multiple DNs match email.");
    }
    return $user_s_r[0]['dn'];
  }

  // The logic here is that failure to find out who has permissions to edit
  // someone else's entry implies that you aren't one of them.
  public function is_phonebook_admin($ldapconn, $dn) {
    $search = ldap_list(
      $ldapconn,
      "ou=groups, dc=mozilla", "(&(member=$dn)(cn=phonebook_admin))",
      array("cn")
    );
    $results = ldap_get_entries($ldapconn, $search);
    return $results["count"];
  }
}

/*************************************************************************/

class MozillaEditingAdapter extends EditingAdapter {
  public function cook_incoming(&$new_user_data, $is_admin) {
  global $office_cities;
    foreach (array("title", "telephoneNumber", "description",
                  "other", "mobile", "im", "emailAlias", "bugzillaEmail", "shirtSize", "b2gNumber", "roomNumber",
                  "pgpFingerprint", "githubProfile", "WPRDeskNumber", "WPRDeskNumberNotes"
                  )
            as $attribute) {
      if (isset($new_user_data[$attribute])) {
        $new_user_data[$attribute] = $this->box($new_user_data[$attribute]);
      }
    }

    foreach ($_POST['office_city'] as $office_city){
        if (!empty($office_city) && $office_city == 'Other') {
            if($_POST['office_country']){
                $office_country = $_POST['office_country'];
            } else {
                $office_country = 'US';
            }
            $office_city = $_POST["office_city_name"];
            $new_user_data['physicalDeliveryOfficeName'][] = implode(':::', array($office_city, $office_country));
        }
    }
    foreach ($_POST['office_city'] as $office_city){
        if (!empty($office_city) && $office_city != 'Other') {
            if (in_array($office_city, array_keys($office_cities))) {
                $office_country = $office_cities[$office_city];
            } else {
                $office_country = $_POST['office_country'];
            }

            $new_user_data['physicalDeliveryOfficeName'][] = implode(':::', array($office_city, $office_country));
        }
    }
    //This removes the duplicate office locations from the array and only stores it once to avoid an ldap constraint error
    if ($new_user_data['physicalDeliveryOfficeName']){
        $new_user_data['physicalDeliveryOfficeName'] = array_unique($new_user_data['physicalDeliveryOfficeName']);
    } else {
        $new_user_data['physicalDeliveryOfficeName'] = ':::';
    }
    if ($is_admin) {
      $new_user_data['employeeType'] = $this->box(
        $this->get_status($_POST['org_code'], $_POST['employee_type_code'])
      );
    }
  }

  public function ldap_bool($boolean) {
      return false;
  }

  // Used to create LDAP data structures
  public function box($element) {
    if (empty($element[0])) {
      return array();
    }
    return $element;
  }

  // Facilitates in creating user
  public function get_status($current_org, $current_emp_type) {
    if ($current_emp_type == 'D' ||
        $current_org == 'D') {
      return "DISABLED";
    } else {
      return $current_org . $current_emp_type;
    }
  }

  public function clean_userdata($user_data) {
    global $editable_fields;
    foreach ($editable_fields as $field) {
      $field = strtolower($field);
      if (!isset($user_data[$field])) {
        $user_data[$field] = array('count' => 0, '');
      }
    }
    return $user_data;
  }

  public function clean_boolean($value) {
    return $value ? 'True' : 'False';
  }
}

/*************************************************************************/

class MozillaSearchAdapter extends SearchAdapter {

  function __construct($auth){
    $ldapconn = get_ldap_connection();
    parent::__construct($ldapconn);
    $this->auth = new MozillaAuthAdapter();
    $this->dn = $this->auth->user_to_dn($_SERVER["REMOTE_USER"]);
    $this->phonebook_admin = $this->auth->is_phonebook_admin($ldapconn, $this->dn);
  }

  public $fields = array(
    'cn', 'title', 'telephoneNumber', 'mobile', 'description', 'manager',
    'other', 'im', 'mail', 'emailAlias', 'physicalDeliveryOfficeName',
    'workdaylocation', 'workdaycostcenter', 'deptname', 'employeeType', 'description', 'isManager', 'bugzillaEmail', 'shirtSize', 'isManager', 'b2gNumber', "roomNumber",
    'pgpFingerprint', 'githubProfile', "WPRDeskNumber", "WPRDeskNumberNotes", "workdayTimezone"
  );
  public $search_fields = array(
    'cn', 'bugzillaEmail', 'mail', 'emailAlias', 'im', 'physicalDeliveryOfficeName',
    'description', 'telephoneNumber', 'mobile', 'b2gNumber', 'githubProfile', 'other'
  );
  public $conf = array(
    "ldap_sort_order" => "sn"
  );

  public function search_users($search, $exact=false) {
    if($exact == false){
      $terms = array_map("escape_ldap_filter_value", preg_split('/\s+/', trim($search)));
      $filter = "(objectClass=mozComPerson)";
      foreach ($terms as $escaped_term) {
          $subfilter = "";
          foreach ($this->search_fields as $field) {
              $subfilter .= "($field=*$escaped_term*)";
          }
          $filter .= "(|$subfilter)";
      }
      if (LDAP_EXCLUDE != '') {
          $filter = $filter . LDAP_EXCLUDE;
      }
      $filter = "(&$filter)";
    } else {
      $escaped = escape_ldap_filter_value($search);
      $filter = "(mail=$escaped)";
    }
    if (!$this->phonebook_admin) {
      $filter = '(&(!(employeeType=DISABLED))' . $filter . ')';
    }

    return $this->query_users($filter, 'dc=mozilla', $this->fields);
  }

  public function preprocess_entry(&$entry) {
    if (preg_match("/mail=(.*@.+),o=/", $entry["dn"], $m)) {
      $entry["picture"] = "pic.php?mail=". $m[1];
    }
  }
}

/*************************************************************************/

class MozillaTreeAdapter extends TreeAdapter {
  public $conf = array(
    array( // Corporation
      "ldap_search_base" => "o=com,dc=mozilla",
      "ldap_search_filter" => "mail=*",
      "ldap_search_attributes" => array(
          "sn", "cn", "manager", "title", "mail", "employeeType"
      )
    ),
    array( // Foundation
      "ldap_search_base" => "o=org,dc=mozilla",
      "ldap_search_filter" => "mail=*",
      "ldap_search_attributes" => array(
        "sn", "cn", "manager", "title", "mail", "employeeType"
      )
    )
  );
  public $roots = array(
    "mitchell@mozilla.com", "chris@mozilla.com", "mark@mozillafoundation.org"
  );

  public function process_entry($person) {
    return array(
      "title" => !empty($person["title"][0]) ? $person["title"][0] : NULL,
      "name" => !empty($person["cn"][0]) ? $person["cn"][0] : NULL,
      "sn" => !empty($person["sn"][0]) ? $person["sn"][0] : NULL,
      "disabled" => isset($person["employeetype"]) ?
                      strpos($person["employeetype"][0], 'D') !== FALSE:
                      FALSE
    );
  }

  public function format_item(&$everyone, $email, $leaf=FALSE) {
    $email = htmlspecialchars($email);
    $id = str_replace('@', "-at-", $email);
    $name = htmlspecialchars($everyone[$email]["name"]);
    $title = htmlspecialchars($everyone[$email]["title"]);
    $leaf = $leaf ? " leaf" : '';
    $disabled = $everyone[$email]["disabled"] ? " disabled" : '';
    return "<li id=\"$id\" class=\"hr-node expanded$leaf$disabled\">".
             "<a href=\"?search/$email\" class=\"hr-link\">$name</a> ".
             "<span class=\"title\">$title</span>".
           "</li>";
  }

  public function sort_items($a, $b) {
    global $everyone;
    global $people;
    list($x, $y) = array(empty($people[$a]), empty($people[$b]));
    if (($x && $y) || (!$x && !$y)) {
      return strcmp($everyone[$a]["sn"], $everyone[$b]["sn"]);
    }
    if (!$x && $y) {
      return 1;
    }
    if ($y && !$x) {
      return -1;
    }
  }
}
