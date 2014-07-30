<?php
require_once("init.php");
require_once("config.php");
require_once("preprocessors-attr.inc");

$auth = new MozillaAuthAdapter();
$search = new MozillaSearchAdapter($ldapconn);
$keyword = isset($_GET["query"]) ? $_GET["query"] : '*';
$exact = isset($_GET["exact_search"]) ? true : false;
$entries = normalize($search->search_users($keyword, $exact=$exact));
$attr_preps = get_attr_preprocessors();

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
  $search->preprocess_entry($entry);
}

$format = isset($_GET["format"]) ? $_GET["format"] : "json";
if (!in_array($format, $output_formats) || !file_exists("output-$format.inc")) {
  $format = "json";
}
require_once("output-$format.inc");
$function = "output_$format";
$dn = $auth->user_to_dn($_SERVER["PHP_AUTH_USER"]);
call_user_func($function, $entries, $auth->is_phonebook_admin($ldapconn, $dn));
