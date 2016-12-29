<?php
require_once('init.php');
define("NULL_PIC", dirname(__FILE__) . "/img/null.jpg");

// Ensure we have mail in $_GET
if (empty($_GET['mail'])) {
    header('HTTP/1.0 400 Bad request');
    echo 'Invalid email.';
    die();
}

// Validate requested email. Throw error if invalid.
if (false === filter_var($_GET['mail'], FILTER_VALIDATE_EMAIL)) {
    header('HTTP/1.0 400 Bad request');
    echo 'Invalid email.';
    die();
}


/** Thumb picture dimensions */
$width = "140";
$height = "175";


$pic = NULL;

header("Content-Type: image/jpeg");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + 300) . " GMT");

if (empty($_GET['type'])) {
  $_GET['type'] = 'standard';
} elseif ($_GET['type'] != 'standard' && $_GET['type'] != 'thumb') {
  exit;
}

if ($memcache_on && ($cached_pic = $memcache->get(MEMCACHE_PREFIX . $_GET['mail']. $_GET['type']))) {
  print $cached_pic;
  exit;
}

$search = ldap_search(
  $ldapconn, 'dc=mozilla', "(mail=". $_GET['mail'] .")", array('jpegPhoto')
);

if ($search) {
  $entry = ldap_first_entry($ldapconn, $search);
  if ($entry) {
    $attributes = ldap_get_attributes($ldapconn, $entry);
    if (!empty($attributes['jpegPhoto'])) {
      $jpeg = ldap_get_values_len($ldapconn, $entry, 'jpegPhoto');
      if ($jpeg) {
        $pic = $jpeg[0];
      }
    }
  }
}

if ($pic == NULL) {
  $pic = fread(fopen(NULL_PIC, 'r'), filesize(NULL_PIC));
}

$gd_pic = imagecreatefromstring($pic);

// Output
ob_start();
if ($_GET['type'] == 'thumb') {
  $width_orig = imagesx($gd_pic);
  $height_orig = imagesy($gd_pic);

  $ratio_orig = $width_orig / $height_orig;

  if (($width / $height) > $ratio_orig) {
    $width = $height * $ratio_orig;
  } else {
    $height = $width / $ratio_orig;
  }

  // Resample
  $image_p = imagecreatetruecolor($width, $height);
  imagecopyresampled($image_p, $gd_pic, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
  imagejpeg($image_p, NULL, 100);
} else {
  imagejpeg($gd_pic, NULL, 100);
}
$image_string = ob_get_clean();
print $image_string;
if ($memcache_on) {
  $memcache->set(MEMCACHE_PREFIX . $_GET['mail']. $_GET['type'], $image_string, 0, 300);
}
?>
