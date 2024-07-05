<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';

$data = json_decode(file_get_contents("php://input"));

$username = filter_var($data->username, FILTER_SANITIZE_STRING);
$password = filter_var($data->password, FILTER_SANITIZE_STRING);
$isManager = filter_var($data->isManager, FILTER_VALIDATE_BOOLEAN);
$timeout = isset($data->timeout) ? filter_var($data->timeout, FILTER_SANITIZE_STRING) : NULL;
$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);

if (!$username || !$house_id) {
    http_response_code(400);
    echo json_encode(array("message" => "Not enough information"));
    die();
}

$house = new house(NULL, $conn, $house_id);
if ($isManager) {
    $user = new user($conn, $username, $password, true, NULL, NULL);
} else {
    $user = new user($conn, $username, $password, false, $timeout, NULL);
}

$result = $house->adduser($user);
if ($result['success']) {
    http_response_code(200);
    echo json_encode(array("message" => $result['message']));
} else {
    http_response_code(409);
    echo json_encode(array("message" => $result['message']));
}
?>