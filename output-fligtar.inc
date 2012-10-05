<?php

function output_fligtar($entries) {
  $object = array();
  foreach ($entries as $entry) {
    $object[$entry["mail"]] = array(
      "name" => $entry["cn"],
      "title" => isset($entry["title"]) ? $entry["title"] : NULL,
      "ext" => isset($entry["telephonenumber"]) ? $entry["telephonenumber"] : NULL,
      "physicaldeliveryofficename" => isset($entry["physicaldeliveryofficename"]) ? $entry["physicaldeliveryofficename"] : NULL,
      "b2gnumber" => isset($entry["b2gnumber"]) ? $entry["b2gnumber"] : NULL,
      "phones" => isset($entry["mobile"]) ? unbox($entry["mobile"]) : NULL,
      "manager" => isset($entry["manager"]) ? unbox($entry["manager"]) : NULL,
      "bugzillaEmail" => isset($entry["bugzillaemail"]) ? $entry["bugzillaemail"] : NULL,
      "ims" => isset($entry["im"]) ? unbox($entry["im"]) : NULL
    );
  }
  header("Content-Type: application/json");
  print json_encode($object);
  die;
}

function unbox($o) {
  if (!is_array($o)) {
    return $o;
  } elseif (empty($o)) {
    return NULL;
  } else {
    return count($o) > 1 ? $o : $o[0];
  }
}

