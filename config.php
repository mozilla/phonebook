<?php

define('LDAP_HOST', 'pm-ns.mozilla.org');


function check_valid_user($user) {
  return preg_match('/^[a-z]+@(.+?)\.(.+)$|^[a-z]+$/', $user);
}

function user_to_dn($user) {
  if (preg_match('/^[a-z]+$/', $user)) {
    return "mail=$user@mozilla.com,o=com,dc=mozilla";
  }
  preg_match('/^[a-z]+@(.+?)\.(.+)$/', $user, $m);
  if ($m[1] == "mozilla-japan" && $m[2] == "org") {
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

function dn_to_email($dn) {
  if (preg_match("/mail=(\w+@.+),o=/", $dn, $m)) {
    return $m[1];
  }
  return NULL;
}

function user_to_email($user) {
  return dn_to_email(user_to_dn($user));
}


function preprocess_entry(&$entry) {
  if (preg_match("/mail=(\w+@.+),o=/", $entry["dn"], $m)) {
    $entry["picture"] = BASEPATH ."pic.php?mail=". $m[1];
  }
}


$tree_conf = array(
  "ldap_search_base" => "o=com, dc=mozilla",
  "ldap_search_filter" => "mail=*",
  "ldap_search_attributes" => array(
    "cn", "manager", "title", "mail", "employeeType"
  )
);

function tree_view_process_entry($person) {
  return array(
    "title" => !empty($person["title"][0]) ? $person["title"][0] : null,
    "name" => !empty($person["cn"][0]) ? $person["cn"][0] : null,
    "disabled" => isset($person["employeetype"]) ?
                    strpos($person["employeetype"][0], 'D') !== FALSE:
                    FALSE
  );
}

function tree_view_roots() {
  return array("mitchell@mozilla.com", "lilly@mozilla.com");
}

function tree_view_item($email, $leaf=FALSE) {
  global $everyone;
  $email = htmlspecialchars($email);
  $id = str_replace('@', "-at-", $email);
  $name = htmlspecialchars($everyone[$email]["name"]);
  $title = htmlspecialchars($everyone[$email]["title"]);
  $leaf = $leaf ? " leaf" : '';
  $disabled = $everyone[$email]["disabled"] ? " disabled" : '';
  return "<li id=\"$id\" class=\"hr-node expanded$leaf$disabled\">".
           "<a href=\"#search/$email\" class=\"hr-link\">$name</a> ".
           "<span class=\"title\">$title</span>".
         "</li>";
}


function preprocess_incoming_user_data(&$new_user_data, $is_admin) {
  foreach (array("title", "telephoneNumber", "description", "manager", "other",
                "mobile", "im", "emailAlias", "bugzillaEmail") as $attribute) {
    $new_user_data[$attribute] = empty_array($new_user_data[$attribute]);
  }

  if ($_POST["office_city"] == "Other") {
    $_POST["office_city"] = $_POST["office_city_name"];
  }
  $new_user_data['physicalDeliveryOfficeName'] = empty_array(array(implode(':::', array($_POST['office_city'], $_POST['office_country']))));

  if ($is_admin) {
    $new_user_data['employeeType'] = empty_array(get_status($_POST['org_code'], $_POST['employee_type_code']));
    if (isset($_POST['is_manager'])) {
      fb("is_manager: ". $_POST['is_manager']);
      $new_user_data['isManager'] = empty_array((bool)$_POST['is_manager']);
    }
  }
}

