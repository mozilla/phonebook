<?php

function output_json($entries) {
  header("Content-Type: application/json");
  print json_encode($entries);
  die;
}

