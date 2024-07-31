<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartKey.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));

$key_id = filter_var($data->key_id, FILTER_SANITIZE_STRING);
$active_color = filter_var($data->active_color, FILTER_SANITIZE_STRING);
$deactive_color = filter_var($data->deactive_color, FILTER_SANITIZE_STRING);
$poles = $data->poles;
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



$key = new smartkey($key_id, NULL, NULL, $active_color, $deactive_color, NULL, NULL, 1);
//$user = new user($conn, NULL, NULL, NULL, NULL, $token, NULL);

if ($response = $key->updateKey($conn, $poles)) {
    echo json_encode(array("Message" => "Command sent successfully"));
    http_response_code(200);
} else {
    echo json_encode(array("Message" => "Could set changes to key"));
    http_response_code(501);
}
