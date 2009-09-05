<?php
require_once("init.php");
require_once("preprocessors-attr.inc");

$keyword = isset($_GET["query"]) ? $_GET["query"] : '*';
$entries = normalize(search_users($ldapconn, $keyword));
$attr_preps = get_attr_preprocessors();

$preprocess_entries = function_exists("preprocess_entry");
$preprocess_attr_functions = array();
foreach ($entries as &$entry) {
  foreach ($entry as $name => $attribute) {
    $prep = isset($attr_preps[$name]) ? $attr_preps[$name] : NULL;
    if (!isset($preprocess_attr_functions[$prep])) {
      $preprocess_attr_functions[$prep] = function_exists($prep);
    }
    if ($preprocess_attr_functions[$prep]) {
      $entry[$name] = call_user_func($prep, $attribute);
    }
  }
  if ($preprocess_entries) {
    preprocess_entry($entry);
  }
}

$format = isset($_GET["format"]) ? $_GET["format"] : "json";
if (!file_exists("output-$format.inc")) {
  $format = "json";
}
require_once("output-$format.inc");
$function = "output_$format";
$dn = user_to_dn($_SERVER["PHP_AUTH_USER"]);
call_user_func($function, $entries, is_phonebook_admin($ldapconn, $dn));
