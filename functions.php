<?php

function ask() {
  header('WWW-Authenticate: Basic realm="Mozilla Corporation - LDAP Login"');
}

function wail_and_bail() {
  header('HTTP/1.0 401 Unauthorized');
  ask();
  print "<h1>401 Unauthorized</h1>";
  die; 
}

function get_ldap_connection() {
  $ldapconn = ldap_connect(LDAP_HOST);
  
  if (!isset($_SERVER["PHP_AUTH_USER"])) {
    ask();
    wail_and_bail();
  } else {
    // Check for validity of login
    if (preg_match("/[a-z]+@mozilla\\.com/", $_SERVER["PHP_AUTH_USER"])) {
      $user_dn = "mail=". $_SERVER["PHP_AUTH_USER"] .",o=com,dc=mozilla";
      $password = $_SERVER["PHP_AUTH_PW"];
    } else {
      wail_and_bail();
    }
  }

  if (!ldap_bind($ldapconn, $user_dn, $_SERVER['PHP_AUTH_PW'])) {
    wail_and_bail();
    die(ldap_error($ldapconn));
  }

  return $ldapconn; 
}

function get_dn_from_email($ldapconn, $email) {
  $user_s = ldap_search($ldapconn, "dc=mozilla", "mail=" . $email);
  $user_s_r = ldap_get_entries($ldapconn, $user_s);
  if ($user_s_r['count'] != 1)
    die("Search for user returned more than 1 entry");
  return $user_s_r[0]['dn'];
}

function search_users($ldapconn, $search) {
  $filter = ($search == '*') ? 'objectClass=mozComPerson' : "(&(|(cn=*$search*)(mail=*$search*)(im=*$search*))(objectClass=mozComPerson))";
  $search = ldap_search(
    $ldapconn, 'dc=mozilla', $filter,
    array(
      "cn", "title", "manager", "employeeType", "email", "emailAlias", 
      "physicalDeliveryOfficeName", "telephoneNumber", "mobile", "im", 
      "bugzillaEmail", "description", "status", "other"
    )
  );
  ldap_sort($ldapconn, $search, 'sn');
  return ldap_get_entries($ldapconn, $search);
}

function employee_status($status) {
  global $orgs, $emp_type;
  $current_org = $current_emp_type = "";
  if ($status != "") {
    $current_org = $status[0];
    $current_emp_type = $status[1];
  }
  if ($status == "DISABLED") {
    return array('DISABLED');
  } else {
    if (array_key_exists($current_org, $orgs) &&
        array_key_exists($current_emp_type, $emp_type)) {
      return array($orgs[$current_org], $emp_type[$current_emp_type]);
    } else {
      return array('Unknown');
    }
  }
}

/*function get_manager($ldapconn, $manager_dn) {
  global $memcache_on, $memcache;
  if ($memcache_on && ($manager = $memcache->get($manager_dn))) {
      return $manager;
  }
  $manager_search = ldap_search($ldapconn, $manager_dn, '(mail=*)', array('cn','mail'));
  if ($manager_search) { 
    $entry = ldap_first_entry($ldapconn, $manager_search);
    if ($entry) { 
      $attrs = ldap_get_attributes($ldapconn, $entry);
      $manager_string = '<a href="search.php?search='. $attrs['mail'][0] .'">' .$attrs['cn'][0] . '</a>'; 
    } else {
      $manager_string =  "Invalid Manager";
    }
    if ($memcache_on) {
      $memcache->set($manager_dn, $manager_string);
    }
    return $manager_string;
  }
}*/

function get_manager($manager_dn) {
  global $ldapconn, $memcache_on, $memcache;
  if ($memcache_on && ($manager = $memcache->get($manager_dn))) {
    return $manager;
  }
  $manager_search = @ldap_search($ldapconn, $manager_dn, '(mail=*)', array('cn','mail'));
  if (ldap_errno($ldapconn) == 32) { // No manager found
    return null;
  }
  if ($manager_search) { 
    $entry = ldap_first_entry($ldapconn, $manager_search);
    if ($entry) { 
      $attrs = ldap_get_attributes($ldapconn, $entry);
      $manager = array(
        "cn" => $attrs['cn'][0],
        "dn" => $manager_dn
      );
    } else {
      $manager = null;
    }
    if ($memcache_on) {
      $memcache->set($manager_dn, $manager);
    }
    return $manager;
  }
}

function wikilinks($string) { 
  $matches = array();
  $string = nl2br(htmlspecialchars($string));
  if (preg_match_all('/\[(.+?)(?:\s(.+?))?\]/', $string, $matches)) {
    foreach ($matches[1] as $key => $value) {
      if (!empty($matches[2][$key])) {
        $title = $matches[2][$key];
      } else {
        $title = $value;
      }
      $string = str_replace(
        $matches[0][$key],
        '<a href="'. $value .'">'. $title .'</a>',
        $string
      );
    }
  }
  return $string;
}

function location_formatter($location) {
  return str_replace(":::", "/", $location);
}

function mobile_normalizer($m) {
  if (!is_array($m)) {
    $m = array($m);
  }
  return array_map("wikilinks", $m);
}

/*function emaillinks($string) {
  $matches = array();
  if (preg_match('/[A-z0-9\._%+-]+@[A-z0-9\.-]+\.[A-z]{2,4}/', $string, $matches)) {
    $string = str_replace(
      $matches[0],
      '<a href="mailto:'. $matches[0] .'">'. $matches[0] .'</a>',
      $string
    );  
  }
  return $string;
}*/

// The logic here is that failure to find out who has permissions to edit
// someone else's entry implies that you aren't one of them.
function phonebookadmin($ldapconn, $mail) {
  $dn = get_dn_from_email($ldapconn, $mail);
  $search = ldap_list(
    $ldapconn, 
    "ou=groups, dc=mozilla", "(&(member=$dn)(cn=phonebook_admin))", 
    array("cn")
  );
  $results = ldap_get_entries($ldapconn, $search);
  return $results["count"];
}

// Used to create LDAP data structures
function empty_array($element) {
  if (empty($element[0])) {
    return array();
  }
  return $element;
}

// Facilitates in creating user
function get_status($current_org, $current_emp_type) {
  if ($current_emp_type == 'D' || 
      $current_org == 'D') {
    return "DISABLED";
  } else {
    return $current_org . $current_emp_type;
  }
}

function clean_userdata($user_data) {
  global $editable_fields;
  foreach ($editable_fields as $field) {
    $field = strtolower($field);
    if (!isset($user_data[$field])) {
      $user_data[$field] = array('count' => 0, '');
    }
  }
  return $user_data;
}

function manager_list($ldapconn) {
  $search = ldap_search($ldapconn, 'o=com,dc=mozilla', 'objectClass=mozComPerson');
  ldap_sort($ldapconn, $search, 'cn');
  return ldap_get_entries($ldapconn, $search);
}

// Normalizes an LDAP entry data structure to a JSON-friendly structure
function normalize($o) {
  if (!is_array($o)) {
    return $o;
  }
  unset($o["count"]);
  $keys = array_keys($o);
  if (count(array_unique(array_map("is_int", $keys))) != 1) {
    $i = 0;
    while (isset($o[$i])){
      unset($o[$i]);
      $i++;
    }
  }
  foreach ($o as &$e) {
    $e = normalize($e);
    if (is_array($e) && count($e) == 1) {
      $e = $e[0];
    }
  }
  return $o;
}
