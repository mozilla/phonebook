<?php

function get_filters() {
  return array(
    "employeetype" => "employee_status",
    "manager" => "get_manager",
    "physicaldeliveryofficename" => "location_formatter",
    "mobile" => "mobile_normalizer",
    "im" => "mobile_normalizer",
    "description" => "wikilinks",
    "other" => "wikilinks"
  );
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

function location_formatter($location) {
  return str_replace(":::", "/", $location);
}

function mobile_normalizer($m) {
  if (!is_array($m)) {
    $m = array($m);
  }
  return array_map("wikilinks", $m);
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


