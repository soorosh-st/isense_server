<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';

$data = json_decode(file_get_contents("php://input"));

$user_id = filter_var($data->user_id, FILTER_SANITIZE_STRING);
$password = filter_var($data->password, FILTER_SANITIZE_STRING);
$timeout = isset($data->timeout) ? filter_var($data->timeout, FILTER_SANITIZE_STRING) : NULL;
$iv = filter_var($data->iv, FILTER_SANITIZE_STRING);
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
if (!$user_id) {
    http_response_code(400);
    echo json_encode(array("message" => "Not enough information"));
    die();
}

$user = new user($conn, NULL, $password, false, $timeout, NULL, $user_id);

$result = $user->updateuser($iv, $token);
if ($result['success']) {
    http_response_code(200);
    echo json_encode(array("message" => $result['message']));
} else {
    http_response_code(409);
    echo json_encode(array("message" => $result['message']));
}
?>