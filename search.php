<?php
require_once("init.php");
require_once("output.inc");

$keyword = isset($_GET["query"]) ? $_GET["query"] : '*';
$entries = normalize(search_users($ldapconn, $keyword));
$filters = array(
  "description" => "wikilinks",
  "other" => "wikilinks",
  "employeetype" => "employee_status",
  "physicaldeliveryofficename" => "location_formatter",
  "mobile" => "mobile_normalizer",
  "im" => "mobile_normalizer",
  "manager" => "get_manager"
);
foreach ($entries as &$entry) {
  foreach ($entry as $name => $attribute) {
    if (isset($filters[$name]) && function_exists($filter = $filters[$name])) {
      $entry[$name] = call_user_func($filter, $attribute);
    } else {
      # $attribute = htmlspecialchars($entry);
    }
  }
  if (preg_match("/mail=(\w+@mozilla.*),o=/", $entry["dn"], $m)) {
    $entry["picture"] = BASEPATH ."pic.php?type=thumb&mail=". $m[1];
  }
}

$format = isset($_GET["format"]) ? $_GET["format"] : "json";
$function = "output_$format";
call_user_func($function, $entries);
