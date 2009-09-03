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
    if (check_valid_user($_SERVER["PHP_AUTH_USER"])) {
      $user_dn = user_to_dn($_SERVER["PHP_AUTH_USER"]);
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

// The logic here is that failure to find out who has permissions to edit
// someone else's entry implies that you aren't one of them.
function is_phonebook_admin($ldapconn, $mail) {
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

function everyone_list($ldapconn) {
  $search = ldap_search($ldapconn, 'o=com,dc=mozilla', 'objectClass=mozComPerson');
  ldap_sort($ldapconn, $search, 'cn');
  return ldap_get_entries($ldapconn, $search);
}

function escape($s) {
  return htmlspecialchars($s, ENT_QUOTES);
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
