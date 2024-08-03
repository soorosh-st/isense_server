<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartKey.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));

$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);
$key_id = filter_var($data->key_id, FILTER_SANITIZE_STRING);
$pole_id = filter_var($data->pole_id, FILTER_SANITIZE_STRING);
$status = filter_var($data->status, FILTER_SANITIZE_STRING);
$polenumber = filter_var($data->polenumber, FILTER_SANITIZE_STRING);
$token;
$headers = getallheaders();
if ($headers['Authorization']) {

    $authHeader = $headers['Authorization'];
    list($type, $token) = explode(' ', $authHeader);
    if ($type != 'Bearer') {
        http_response_code(401);
        echo json_encode(['Message' => 'Invalid token']);
        die();
    }
} else {
    // Missing token
    http_response_code(401);
    echo json_encode(['Message' => 'Missing token']);
    die();
}

if (!$house_id) {
    http_response_code(400);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}


$key = new smartkey($key_id, NULL, $status, NULL, NULL, NULL, NULL, 1);
$user = new user($conn, NULL, NULL, NULL, NULL, $token, NULL);

if ($response = $key->setKey($house_id, $key, $conn, $pole_id)) {
    echo json_encode(array("Message" => "Command sent successfully"));
    http_response_code(200);
} else {
    echo json_encode(array("Message" => "Could set changes to key"));
    http_response_code(501);
}






?>