<?php
error_reporting(E_ERROR | E_PARSE);
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/log.php';
header('Access-Control-Allow-Methods: PUT, GET, POST');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");
try {
    $database = new database();
    $conn = $database->get_Connection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Unable connect to database", "Error" => $e->getMessage()));
    die();
}

?>