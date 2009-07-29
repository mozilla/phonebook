<?php
require_once('init.php');
require_once('country_codes.php');

function print_status_edit($status, $is_manager, $admin) {
  global $orgs, $emp_type;
  $current_org = $current_emp_type = '';
  if ($status != '') {
    $current_org = $status[0];
    $current_emp_type = $status[1];
  }
  if ($status == "DISABLED") {
    $current_org = 'D';
    $current_emp_type = 'D';
  }
  if ($admin) {
    require "templates/_status.php";
  } else {
    if (array_key_exists($current_org, $orgs) &&
        array_key_exists($current_emp_type, $emp_type)) {
      print $orgs[$current_org] .", ". $emp_type[$current_emp_type];
    } else {
      print "DISABLED";
    }
  }
}

$prototype = true;
$is_admin = phonebookadmin($ldapconn, $_SERVER["PHP_AUTH_USER"]) == 1;
if (!empty($_REQUEST) && !empty($_REQUEST["edit_mail"]) && $is_admin) {
  $edit_user = $_REQUEST["edit_mail"];
}
else {
  $edit_user = $_SERVER["PHP_AUTH_USER"];
}
$user_search = ldap_search($ldapconn, "dc=mozilla", "mail=". $edit_user, $editable_fields);
$user_data = ldap_get_entries($ldapconn, $user_search);
$user_data = $user_data[0];

list($city, $country) = split(":::", $user_data["physicaldeliveryofficename"][0]);
$city_name = $city;
 
if (!empty($city) && !in_array($city, $office_cities)) {
  $city = "Other";
}

if (!empty($_POST)) { 
  if ($_POST["office_city"] == "Other") {
    $_POST["office_city"] = $_POST["office_city_name"];
  }
  $new_user_data = array();
  $new_user_data['cn'] = $_POST['cn']; 
  $new_user_data['title'] = empty_array($_POST['title']);
  $new_user_data['telephoneNumber'] = empty_array($_POST['telephoneNumber']);
  $new_user_data['description'] = empty_array($_POST['description']);
  $new_user_data['manager'] = empty_array($_POST['manager']);
  $new_user_data['other'] = empty_array($_POST['other']);
  $new_user_data['mobile'] = empty_array($_POST['mobile']);
  $new_user_data['im'] = empty_array($_POST['im']);
  $new_user_data['emailAlias'] = empty_array($_POST['emailAlias']);
  $new_user_data['bugzillaEmail'] = empty_array($_POST['bugzillaEmail']);
  $new_user_data['physicalDeliveryOfficeName'] = empty_array(array( implode(':::', array($_POST['office_city'], $_POST['office_country']))));

  if ($is_admin) {
    #$new_user_data['employeeType'] = empty_array($_POST['employeeType']);
    $new_user_data['employeeType'] = empty_array(get_status($_POST['org_code'], $_POST['employee_type_code']));
    if (isset($_POST['is_manager'])) {
      error_log("blah " . $_POST['is_manager']);
      $new_user_data['isManager'] = empty_array((bool)$_POST['is_manager']);
    }
  }

  foreach ($new_user_data as $key => $value) {
    if (!isset($user_data[strtolower($key)])) {
      if (!ldap_add($ldapconn, get_dn_from_email($ldapconn, $edit_user), array($key => $value))) {
        error_log("Fail on $key => $value for $edit_user");
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
  if (ldap_modify($ldapconn, get_dn_from_email($ldapconn, $edit_user), $new_user_data)) {
    header("Location: .");
  } else {
    error_log("ldap_modify fail for $edit_user: " . print_r($new_user_data, true));
  }
}

$user_data = clean_userdata($user_data);
$managerlist = manager_list($ldapconn);

require_once 'templates/edit.php';
