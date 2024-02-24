<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartKey.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
$user_id = filter_var($data->user_id, FILTER_SANITIZE_STRING);
$house_name = filter_var($data->house_name, FILTER_SANITIZE_STRING);

if (!$user_id || !$house_name) {
    http_response_code(400);
    echo json_encode(array("message" => "Not enough information"));
    die();
}


$smartkey = new smartkey(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
if ($response = $smartkey->readAll($house_name, $user_id, $conn)) {
    echo json_encode($response);
    http_response_code(200);
} else {
    echo json_encode(array("Error" => "Data could not be retrived"));
    http_response_code(400);
}






?>