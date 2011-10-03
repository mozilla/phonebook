<?php
require_once('init.php');
require_once('config.php');

    $output = array();
    foreach ($office_cities as $key => $value){
        $output[] = array("office" => $key, "country" => $value);
    }
    echo json_encode($output);
?>
