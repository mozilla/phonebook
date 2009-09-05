<?php
require_once('init.php');
require_once('country_codes.php');

$user = $_SERVER["PHP_AUTH_USER"];

$is_admin = is_phonebook_admin($ldapconn, user_to_dn($user));
if (isset($_REQUEST["edit_mail"]) && $is_admin) {
  $edit_user = $_REQUEST["edit_mail"];
} else {
  $edit_user = user_to_email($user);
}

$user_search = ldap_search($ldapconn, "dc=mozilla", "mail=". $edit_user, $editable_fields);
$user_data = ldap_get_entries($ldapconn, $user_search);
$user_data = $user_data[0];

if (!empty($_POST)) {
  $new_user_data = array();
  foreach ($editable_fields as $editable_field) {
    if (isset($_POST[$editable_field])) {
      $new_user_data[$editable_field] = $_POST[$editable_field];
    }
  }

  preprocess_incoming_user_data($new_user_data, $is_admin);

  // Save the attributes
  foreach ($new_user_data as $key => $value) {
    if (!isset($user_data[strtolower($key)])) {
      if (!ldap_add($ldapconn,
                    email_to_dn($ldapconn, $edit_user),
                    array($key => $value))) {
        fb("Fail on $key => $value for $edit_user");
      }
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
    $memcache->delete($edit_user . 'standard');
    $memcache->delete($edit_user . 'thumb');
  }
  if (ldap_modify($ldapconn, email_to_dn($ldapconn, $edit_user), $new_user_data)) {
    header("Location: .");
  } else {
    fb($new_user_data, "ldap_modify fail for $edit_user");
  }
}

$user_data = clean_userdata($user_data);
$managerlist = everyone_list($ldapconn);

require_once 'templates/edit.php';
