<?php

function vcard_cn($cn) {
  $p = preg_split("/ /", $cn, 2);
  return "FN:$cn\nN:{$p[1]};{$p[0]};;;\n";
}

function vcard_emails($o) {
  if (is_string($o)) {
    $o = array($o);
  }
  foreach ($o as &$email) {
    $email = "EMAIL;INTERNET:". $email;
  }
  return implode("\n", $o) ."\n";
}

function vcard_phone_numbers($o) {
  if (is_string($o)) {
    $o = array($o);
  }
  foreach ($o as &$e) {
    $e = "TEL;MSG;CELL:$e";
  }
  return implode("\n", $o) ."\n";
}

function vcard_main_mail($mail) {
  return "EMAIL;INTERNET:$mail\n";
}

function vcard_picture($url) {
  return "PHOTO;VALUE=URL:". str_replace(':', "\:", $url) ."\n";
}

function vcard_employeetype($a) {
  return "ORG:;{$a[0]}\n";
}

function vcard_extension($n) {
  return "TEL;WORK:650-903-0800 x$n\n";
}

function output_vcard($entries) {
  $mappings = array(
    "cn" => "vcard_cn",
    "mail" => "vard_main_mail",
    "emailalias" => "vcard_emails",
    "mobile" => "vcard_phone_numbers",
    "title" => "TITLE",
    "picture" => "vcard_picture",
    "employeetype" => "vcard_employeetype",
    "telephonenumber" => "vcard_extension"
  );
  header("Content-Disposition: attachment; filename=\"export.vcf\"");
  header('Content-Type: text/x-vcard');
  foreach ($entries as $entry) {
    fb($entry);
    print "BEGIN:VCARD\nVERSION:2.1\n";
    foreach (array_keys($mappings) as $attr) {
      if (!isset($entry[$attr])) {
        continue;
      }
      if (function_exists($mappings[$attr])) {
        print call_user_func($mappings[$attr], $entry[$attr]);
      } else {
        print "{$mappings[$attr]}:{$entry[$attr]}\n";
      }
    }
    print "END:VCARD\n";
  }
  die;
}

