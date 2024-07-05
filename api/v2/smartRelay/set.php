<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartRelay.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
$username = filter_var($data->username, FILTER_SANITIZE_STRING);
$token = filter_var($data->token, FILTER_SANITIZE_STRING);
$house_name = filter_var($data->house_name, FILTER_SANITIZE_STRING);
$smart_relay = filter_var($data->smart_relay, FILTER_SANITIZE_STRING);
$smart_relay_status = filter_var($data->smart_relay_status, FILTER_SANITIZE_STRING);

if (!$username || !$token || !$house_name || !$smart_relay_status || !$smart_relay) {
    http_response_code(400);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}


$smartRelay = new smartRelay($smart_relay, NULL, NULL, NULL);
$user = new user($conn, $username, NULL, NULL, NULL, $token);
if (!$user->checkAccess()) {
    echo json_encode(array("Error" => "Access Denied"));
    http_response_code(404);
    die();
}
if ($response = $smartRelay->setRelay($house_name, $smart_relay_status, $conn)) {
    echo json_encode(array("Message" => "Command sent successfully"));
    http_response_code(200);
} else {
    echo json_encode(array("Error" => "Data could not be retrived"));
    http_response_code(400);
}






?>