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
  $auth = new MozillaAuthAdapter();

  if (!isset($_SERVER["PHP_AUTH_USER"])) {
    ask();
    wail_and_bail();
  } else {
    // Check for validity of login
    if ($auth->check_valid_user($_SERVER["PHP_AUTH_USER"])) {
      $user_dn = $auth->user_to_dn($_SERVER["PHP_AUTH_USER"]);
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

/*
function email_to_dn($ldapconn, $email) {
  $user_s = ldap_search($ldapconn, "dc=mozilla", "mail=" . $email);
  $user_s_r = ldap_get_entries($ldapconn, $user_s);
  if ($user_s_r['count'] != 1) {
    die("Multiple DNs match email.");
  }
  return $user_s_r[0]['dn'];
}
*/

/*
function query_users($ldapconn, $filter, $base='', $attributes, $sort=null) {
  $adapter = new MozillaSearchAdapter();
  $conf = $adapter->conf();
  $search = ldap_search($ldapconn, $base, $filter, $attributes);
  ldap_sort($ldapconn, $search, $sort || $conf["ldap_sort_order"] || "sn");
  return ldap_get_entries($ldapconn, $search);
}
*/

/*
// The logic here is that failure to find out who has permissions to edit
// someone else's entry implies that you aren't one of them.
function is_phonebook_admin($ldapconn, $dn) {
  $search = ldap_list(
    $ldapconn,
    "ou=groups, dc=mozilla", "(&(member=$dn)(cn=phonebook_admin))",
    array("cn")
  );
  $results = ldap_get_entries($ldapconn, $search);
  return $results["count"];
}
*/

/*
// Used to create LDAP data structures
function empty_array($element) {
  if (empty($element[0])) {
    return array();
  }
  return $element;
}
*/

/*
// Facilitates in creating user
function get_status($current_org, $current_emp_type) {
  if ($current_emp_type == 'D' ||
      $current_org == 'D') {
    return "DISABLED";
  } else {
    return $current_org . $current_emp_type;
  }
}
*/

/*
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
*/

/*
function everyone_list($ldapconn) {
  $search = ldap_search($ldapconn, 'o=com,dc=mozilla', 'objectClass=mozComPerson');
  ldap_sort($ldapconn, $search, 'cn');
  return ldap_get_entries($ldapconn, $search);
}
*/

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

// LDAP escape functions borrowed from PEAR's Net_LDAP_Utils

/**
* Converts all ASCII chars < 32 to "\HEX"
*
* @param string $string String to convert
*
* @static
* @return string
*/
function asc2hex32($string)
{
    for ($i = 0; $i < strlen($string); $i++) {
        $char = substr($string, $i, 1);
        if (ord($char) < 32) {
            $hex = dechex(ord($char));
            if (strlen($hex) == 1) {
                $hex = '0'.$hex;
            }
            $string = str_replace($char, '\\'.$hex, $string);
        }
    }
    return $string;
}

/**
* Escapes a DN value according to RFC 2253
*
* Escapes the given VALUES according to RFC 2253 so that they can be safely used in LDAP DNs.
* The characters ",", "+", """, "\", "<", ">", ";", "#", "=" with a special meaning in RFC 2252
* are preceeded by ba backslash. Control characters with an ASCII code < 32 are represented as \hexpair.
* Finally all leading and trailing spaces are converted to sequences of \20.
*
* @param array $values An array containing the DN values that should be escaped
*
* @static
* @return array The array $values, but escaped
*/
function escape_ldap_dn_value($values = array())
{
    // Parameter validation
    $unwrap = !is_array($values);
    if ($unwrap) {
        $values = array($values);
    }

    foreach ($values as $key => $val) {
        // Escaping of filter meta characters
        $val = str_replace('\\', '\\\\', $val);
        $val = str_replace(',', '\,', $val);
        $val = str_replace('+', '\+', $val);
        $val = str_replace('"', '\"', $val);
        $val = str_replace('<', '\<', $val);
        $val = str_replace('>', '\>', $val);
        $val = str_replace(';', '\;', $val);
        $val = str_replace('#', '\#', $val);
        $val = str_replace('=', '\=', $val);

        // ASCII < 32 escaping
        $val = asc2hex32($val);

        // Convert all leading and trailing spaces to sequences of \20.
        if (preg_match('/^(\s*)(.+?)(\s*)$/', $val, $matches)) {
            $val = $matches[2];
            for ($i = 0; $i < strlen($matches[1]); $i++) {
                $val = '\20'.$val;
            }
            for ($i = 0; $i < strlen($matches[3]); $i++) {
                $val = $val.'\20';
            }
        }

        if (null === $val) {
            $val = '\0';  // apply escaped "null" if string is empty
        }

        $values[$key] = $val;
    }

    if ($unwrap) return $values[0]; else return $values;
}

/**
* Escapes the given VALUES according to RFC 2254 so that they can be safely used in LDAP filters.
*
* Any control characters with an ACII code < 32 as well as the characters with special meaning in
* LDAP filters "*", "(", ")", and "\" (the backslash) are converted into the representation of a
* backslash followed by two hex digits representing the hexadecimal value of the character.
*
* @param array $values Array of values to escape
*
* @static
* @return array Array $values, but escaped
*/
function escape_ldap_filter_value($values = array())
{
    // Parameter validation
    $unwrap = !is_array($values);
    if ($unwrap) {
        $values = array($values);
    }

    foreach ($values as $key => $val) {
        // Escaping of filter meta characters
        $val = str_replace('\\', '\5c', $val);
        $val = str_replace('*', '\2a', $val);
        $val = str_replace('(', '\28', $val);
        $val = str_replace(')', '\29', $val);

        // ASCII < 32 escaping
        $val = asc2hex32($val);

        if (null === $val) {
            $val = '\0';  // apply escaped "null" if string is empty
        }

        $values[$key] = $val;
    }

    if ($unwrap) return $values[0]; else return $values;
}
