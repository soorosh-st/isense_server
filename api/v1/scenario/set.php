<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/scenario.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
$username = filter_var($data->username, FILTER_SANITIZE_STRING);
$token = filter_var($data->token, FILTER_SANITIZE_STRING);
$house_name = filter_var($data->house_name, FILTER_SANITIZE_STRING);
$key = filter_var($data->scenario, FILTER_SANITIZE_STRING);

if (!$username || !$token || !$house_name || !$key) {
    http_response_code(400);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}


$scenario = new scenario($key, NULL);
$user = new user($conn, $username, NULL, NULL, NULL, $token);
if (!$user->checkAccess()) {
    echo json_encode(array("Error" => "Access Denied"));
    http_response_code(404);
    die();
}
if ($response = $scenario->setKey($house_name, $key, $conn)) {
    echo json_encode(array("Message" => "Command sent successfully"));
    http_response_code(200);
} else {
    echo json_encode(array("Error" => "Data could not be retrived"));
    http_response_code(400);
}






?>