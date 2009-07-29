<?php

function output_autocomplete($entries) {
  global $keyword;
  $struct = array(
    "query" => $keyword,
    "suggestions" => array(),
    "data" => array()
  );
  foreach ($entries as $entry) {
    $struct["suggestions"][] = $entry["cn"];
    $struct["data"][] = $entry["dn"];
  }
  header("Content-Type: application/json");
  print json_encode($struct);
  die;
}

