<?php
require_once("init.php");
require_once("filters.inc");

$keyword = isset($_GET["query"]) ? $_GET["query"] : '*';
$entries = normalize(search_users($ldapconn, $keyword));
$filters = get_filters();

foreach ($entries as &$entry) {
  foreach ($entry as $name => $attribute) {
    if (isset($filters[$name]) && function_exists($filter = $filters[$name])) {
      $entry[$name] = call_user_func($filter, $attribute);
    }
  }
  if (preg_match("/mail=(\w+@mozilla.*),o=/", $entry["dn"], $m)) {
    $entry["picture"] = BASEPATH ."pic.php?mail=". $m[1];
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
