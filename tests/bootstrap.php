<?php

$loader = require_once __DIR__ . "/../vendor/autoload.php";

if (! function_exists('resolve')) {
    function resolve($class, $params) {
        return new \Pushkin\Reader($params['input'], $params['startLine'], $params['endLine']);
    }
}