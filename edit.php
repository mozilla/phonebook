<?php
require_once('init.php');
require_once('config.php');
require_once('country_codes.php');
error_reporting(E_ERROR);

$edit = new MozillaEditingAdapter();
$auth = new MozillaAuthAdapter();
$search = new MozillaSearchAdapter($ldapconn);
$user = $_SERVER["PHP_AUTH_USER"];

$is_admin = $auth->is_phonebook_admin($ldapconn, $auth->user_to_dn($user));
if (isset($_REQUEST["edit_mail"]) && $is_admin) {
  $edit_user = $_REQUEST["edit_mail"];
} else {
  $edit_user = $auth->user_to_email($user);
}

$user_search = $search->query_users("mail=$edit_user", "dc=mozilla");
$user_data = $user_search[0];

if (!empty($_POST)) {
  $new_user_data = array();
  foreach ($editable_fields as $editable_field) {
    if (isset($_POST[$editable_field])) {
      $new_user_data[$editable_field] = $_POST[$editable_field];
    }
  }

  $edit->cook_incoming($new_user_data, $is_admin);

  // Save the attributes
  foreach ($new_user_data as $key => $value) {
    if (!isset($user_data[strtolower($key)])) {
      if (!ldap_add($ldapconn,
                    $auth->email_to_dn($ldapconn, $edit_user),
                    array($key => $value))) {
        fb("Failure on $key => ". print_r($value, TRUE) ." for $edit_user");
      }
      fb("Success on $key => ". print_r($value, TRUE) ." for $edit_user");
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
    header("Location: .");
  } else {
    fb($new_user_data, "ldap_modify fail for $edit_user");
  }
}

$user_data = $edit->clean_userdata($user_data);
$managerlist = $search->list_everyone($ldapconn);

require_once 'templates/edit.php';
