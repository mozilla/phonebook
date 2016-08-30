<?php

function output_json($search_result) {
  header("Content-Type: application/json");
  print json_encode($search_result);
  die;
}

