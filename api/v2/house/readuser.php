<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
$token = filter_var($data->token, FILTER_SANITIZE_STRING);
$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);

if (!$token || !$house_id) {
    http_response_code(400);
    echo json_encode(array("message" => "Not enough information"));
    die();
}


$house = new house(NULL, $conn, $house_id);

if ($response = $house->readAlluser($token)) {
    echo json_encode($response);
    http_response_code(200);
} else {
    echo json_encode(array("message" => "Data could not be retrived"));
    http_response_code(404);
}






?>