<?php

function output_csv($data) {
  header("Content-Type: text/csv");
  $f = fopen("php://output", 'w');
  global $editable_fields;
  fputcsv($f, $editable_fields);
  foreach ($data as $pto) {
    $row = array();
    foreach ($editable_fields as $field) {
      $field = strtolower($field);
      $value = isset($pto[$field]) ? $pto[$field] : '';
      if (is_array($value)) {
        $value = implode(", ", $value);
      }
      $row[] = $value;
    }
    fputcsv($f, $row);
  }
  fclose($f);
  die; 
}

