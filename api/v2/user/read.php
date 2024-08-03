<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);
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
    echo json_encode(array("message" => "Not enough information"));
    die();
}


$user = new user($conn, NULL, NULL, NULL, NULL, $token, NULL);
$response = $user->readAlluser($house_id);
if ($response['status']) {
    echo json_encode($response['List']);
    http_response_code(200);
} else {
    echo json_encode(array("message" => "Data could not be retrived"));
    http_response_code(404);
}






?>