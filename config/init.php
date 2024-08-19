<?php
error_reporting(E_ERROR | E_PARSE);
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/log.php';
header("Access-Control-Allow-Origin: *");
try {
    $database = new database();
    $conn = $database->get_Connection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Unable connect to database", "Error" => $e->getMessage()));
    die();
}

?>