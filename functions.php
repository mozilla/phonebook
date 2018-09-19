<?php

function wail_and_bail() {
  header('HTTP/1.0 401 Unauthorized');
  print "<h1>401 Unauthorized</h1>";
  die;
}

function server_error_and_bail() {
  header('HTTP/1.0 500 Server Error');
  print "<h1>500 Server Error</h1>";
  die;
}

/** Import ldap_escape() for PHP 5.3 from the author's implementation thereof.
* http://stackoverflow.com/questions/8560874/php-ldap-add-function-to-escape-ldap-special-characters-in-dn-syntax/8561604#8561604
*/
if (!function_exists('ldap_escape')) {
    define('LDAP_ESCAPE_FILTER', 0x01);
    define('LDAP_ESCAPE_DN',     0x02);

    /**
     * @param string $subject The subject string
     * @param string $ignore Set of characters to leave untouched
     * @param int $flags Any combination of LDAP_ESCAPE_* flags to indicate the
     *                   set(s) of characters to escape.
     * @return string
     */
    function ldap_escape($subject, $ignore = '', $flags = 0)
    {
        static $charMaps = array(
            LDAP_ESCAPE_FILTER => array('\\', '*', '(', ')', "\x00"),
            LDAP_ESCAPE_DN     => array('\\', ',', '=', '+', '<', '>', ';', '"', '#'),
        );

        // Pre-process the char maps on first call
        if (!isset($charMaps[0])) {
            $charMaps[0] = array();
            for ($i = 0; $i < 256; $i++) {
                $charMaps[0][chr($i)] = sprintf('\\%02x', $i);;
            }

            for ($i = 0, $l = count($charMaps[LDAP_ESCAPE_FILTER]); $i < $l; $i++) {
                $chr = $charMaps[LDAP_ESCAPE_FILTER][$i];
                unset($charMaps[LDAP_ESCAPE_FILTER][$i]);
                $charMaps[LDAP_ESCAPE_FILTER][$chr] = $charMaps[0][$chr];
            }

            for ($i = 0, $l = count($charMaps[LDAP_ESCAPE_DN]); $i < $l; $i++) {
                $chr = $charMaps[LDAP_ESCAPE_DN][$i];
                unset($charMaps[LDAP_ESCAPE_DN][$i]);
                $charMaps[LDAP_ESCAPE_DN][$chr] = $charMaps[0][$chr];
            }
        }

        // Create the base char map to escape
        $flags = (int)$flags;
        $charMap = array();
        if ($flags & LDAP_ESCAPE_FILTER) {
            $charMap += $charMaps[LDAP_ESCAPE_FILTER];
        }
        if ($flags & LDAP_ESCAPE_DN) {
            $charMap += $charMaps[LDAP_ESCAPE_DN];
        }
        if (!$charMap) {
            $charMap = $charMaps[0];
        }

        // Remove any chars to ignore from the list
        $ignore = (string)$ignore;
        for ($i = 0, $l = strlen($ignore); $i < $l; $i++) {
            unset($charMap[$ignore[$i]]);
        }

        // Do the main replacement
        $result = strtr($subject, $charMap);

        // Encode leading/trailing spaces if LDAP_ESCAPE_DN is passed
        if ($flags & LDAP_ESCAPE_DN) {
            if ($result[0] === ' ') {
                $result = '\\20' . substr($result, 1);
            }
            if ($result[strlen($result) - 1] === ' ') {
                $result = substr($result, 0, -1) . '\\20';
            }
        }

        return $result;
    }
}

function get_ldap_connection() {
  $ldapconn = ldap_connect(LDAP_HOST);
  $auth = new MozillaAuthAdapter();
  ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);

  if (!@ldap_start_tls($ldapconn)) {
    wail_and_bail();
  }
  if (!isset($_SERVER["REMOTE_USER"])) {
    wail_and_bail();
  }

  if (!ldap_bind($ldapconn, LDAP_BIND_DN, LDAP_BIND_PW)) {
    server_error_and_bail();
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
* This is a functional wrapper around the above-imported ldap_escape function.
* It was previously a custom implementation, which has been replaced. Note
* the use of the constant LDAP_ESCAPE_DN below, as this is for DNs.
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
        $values[$key] = ldap_escape($val, null, LDAP_ESCAPE_DN);
    }

    if ($unwrap) return $values[0]; else return $values;
}

/**
* This is a functional wrapper around the above-imported ldap_escape function.
* It was previously a custom implementation, which has been replaced. Note
* the use of the constant LDAP_ESCAPE_FILTER below, as this is for filters.
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
        $values[$key] = ldap_escape($val, null, LDAP_ESCAPE_FILTER);
    }

    if ($unwrap) return $values[0]; else return $values;
}

/*
 * These two functions let us emit JS/CSS links either with, or without,
 * SRI hashes, based on the config-local value ENABLE_SRIHASH and the
 * contents of the file 'config-srihashes.php'.
 */
function link_javascript($filename)
{
    $link = '<script type="text/javascript" src="';
    $link .= $filename;
    $link .= '"';
    if (ENABLE_SRIHASH === true && isset($GLOBALS["SRIHASHES"][$filename])) {
        $link .= ' integrity="';
        $link .= $GLOBALS["SRIHASHES"][$filename];
        $link .= '"';
    }
    $link .= '></script>';

    return $link;
}
function link_stylesheet($filename)
{
    $link = '<link rel="stylesheet" type="text/css" href="';
    $link .= $filename;
    $link .= '"';
    if (ENABLE_SRIHASH === true && isset($GLOBALS["SRIHASHES"][$filename])) {
        $link .= ' integrity="';
        $link .= $GLOBALS["SRIHASHES"][$filename];
        $link .= '"';
    }
    $link .= ' />';

    return $link;
}
