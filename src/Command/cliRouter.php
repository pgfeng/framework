<?php
define('DEV_MODE', true);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
header('Access-Control-Allow-Headers: *');
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
    exit;
}
if (is_file($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])) {
    return false;
} else {
    $_SERVER["SCRIPT_FILENAME"] = '/index.php';
    $_SERVER["SCRIPT_NAME"] = '/index.php';
    $_SERVER["PHP_SELF"] = '/index.php';
    $_SERVER['PATH_INFO'] = $_SERVER['REQUEST_URI'];
    $_GET['_router'] = $_SERVER['REQUEST_URI'];
    include $_SERVER['DOCUMENT_ROOT'] . "/index.php";
}