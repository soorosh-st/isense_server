<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
try {
    $database = new database();
    $conn = $database->get_Connection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Unable connect to database"));
    die();
}

?>