<?php
require_once("init.php");
require_once("filters.inc");

$keyword = isset($_GET["query"]) ? $_GET["query"] : '*';
$entries = normalize(search_users($ldapconn, $keyword));
$filters = get_filters();

$preprocess = function_exists("preprocess_entry");
$filter_functions = array();
foreach ($entries as &$entry) {
  foreach ($entry as $name => $attribute) {
    $filter = isset($filters[$name]) ? $filters[$name] : NULL;
    if (!isset($filter_functions[$filter])) {
      $filter_functions[$filter] = function_exists($filter);
    }
    if ($filter_functions[$filter]) {
      $entry[$name] = call_user_func($filter, $attribute);
    }
  }
  if ($preprocess) {
    preprocess_entry($entry);
  }
}

$format = isset($_GET["format"]) ? $_GET["format"] : "json";
if (!file_exists("output-$format.inc")) {
  $format = "json";
}
require_once("output-$format.inc");
$function = "output_$format";
$user = $_SERVER["PHP_AUTH_USER"];
call_user_func($function, $entries, is_phonebook_admin($ldapconn, $user));
