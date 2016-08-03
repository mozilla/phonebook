<?php
require_once('init.php');
require_once('config.php');
require_once('country_codes.php');
error_reporting(E_ERROR);

$edit = new MozillaEditingAdapter();
$auth = new MozillaAuthAdapter();
$search = new MozillaSearchAdapter($ldapconn);

$ldap_char_blacklist = array("*", "&", "|", "(", ")", "=");
$user = str_replace($ldap_char_blacklist, "", $_SERVER["REMOTE_USER"]);

$is_admin = $auth->is_phonebook_admin($ldapconn, $auth->user_to_dn($user));
if (isset($_REQUEST["edit_mail"]) && $is_admin) {
  $edit_user = str_replace($ldap_char_blacklist, "", $_REQUEST["edit_mail"]);
} else {
  $edit_user = $auth->user_to_email($user);
}

$user_search = $search->query_users("mail=$edit_user", "dc=mozilla");
$user_data = $user_search[0];
if (!empty($_POST)) {
  $new_user_data = array();
  foreach ($editable_fields as $editable_field) {
    if (isset($_POST[$editable_field])) {
        if(in_array($editable_field, $MONKEY_FREE_ARRAY)){
            $_POST[$editable_field][0] = trim(ldap_escape($_POST[$editable_field][0], null, LDAP_ESCAPE_FILTER));
        }
        $new_user_data[$editable_field] = $_POST[$editable_field];
    }
    if(isset($new_user_data["other"][0])){
        $new_user_data["other"][0] = strip_tags($new_user_data["other"][0]);
    }
    if(isset($new_user_data["description"][0])){
        $new_user_data["description"][0] = strip_tags($new_user_data["description"][0]);
    }
  }

  $edit->cook_incoming($new_user_data, $is_admin);

  // Save the attributes
  foreach ($new_user_data as $key => $value) {
    // strip the 'count' elements from user_data
    if (is_array($user_data[strtolower($key)]) and
        array_key_exists('count', $user_data[strtolower($key)])) {
      unset($user_data[strtolower($key)]['count']);
    }
    // normalize user_data to the type of new_user_data
    if (is_string($value)) {
      if (is_array($user_data[strtolower($key)]) and
          count($user_data[strtolower($key)]) == 1) {
        $user_data[strtolower($key)] = $user_data[strtolower($key)][0];
      }
    } else {
      if (is_string($user_data[strtolower($key)])) {
        $user_data[strtolower($key)] = array($user_data[strtolower($key)]);
      } elseif (is_null($user_data[strtolower($key)])) {
        $user_data[strtolower($key)] = array();
      }
    }

    if (!isset($user_data[strtolower($key)])) {
      if (!ldap_add($ldapconn,
                    $auth->email_to_dn($ldapconn, $edit_user),
                    array($key => $value))) {
        fb("Failure on $key => ". print_r($value, TRUE) ." for $edit_user");
      }
      fb("Success on $key => ". print_r($value, TRUE) ." for $edit_user");
    } elseif (is_array($new_user_data[strtolower($key)])) {
      if (count(array_diff($user_data[strtolower($key)], $new_user_data[strtolower($key)])) == 0) {
        unset($new_user_data[$key]);
      }
    } elseif ($user_data[strtolower($key)] == $value) {
      unset($new_user_data[$key]);
    }
  }

  if (!empty($_FILES['jpegPhoto']['tmp_name'])) {
    if ($_FILES['jpegPhoto']['type'] != 'image/jpeg') {
      die("Photo must be a JPEG!");
    }
    $pic_file = fopen($_FILES['jpegPhoto']['tmp_name'], 'r');
    $new_user_data['jpegPhoto'] = fread($pic_file, filesize($_FILES['jpegPhoto']['tmp_name']));
  }
  if ($memcache_on) {
    $memcache->delete(MEMCACHE_PREFIX . $edit_user . 'standard');
    $memcache->delete(MEMCACHE_PREFIX . $edit_user . 'thumb');
  }
  if (ldap_modify($ldapconn,
                  $auth->email_to_dn($ldapconn, $edit_user),
                  $new_user_data)) {
    $uri = explode("/",$_SERVER['REQUEST_URI']);
    if ($uri[1] != 'edit.php'){
        $location_redirect = '/' . $uri[1] . "/#search/" . $edit_user;
    } else {
        $location_redirect = "/#search/" . $edit_user;
    }
    header("Location: $location_redirect");
  } else {
    fb($new_user_data, "ldap_modify fail for $edit_user");
  }
}

$user_data = $edit->clean_userdata($user_data);
$managerlist = $search->list_everyone($ldapconn);

require_once 'templates/edit.php';
