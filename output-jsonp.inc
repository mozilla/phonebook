<?php

function output_jsonp($entries) {
    if($_GET['callback']){
        header('Content-Type: text/javascript; charset=utf8');
        print $_GET['callback'] . '('. json_encode($entries) . ');';
        die;
    } else {
        print 'callback parameter required';
        die;

    }
}

