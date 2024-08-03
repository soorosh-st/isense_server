<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartKey.php';
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


$smartkey = new smartkey(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
if ($response = $smartkey->readAll($house_id, $token, $conn)) {
    echo json_encode($response);
    http_response_code(200);
} else {
    echo json_encode(array("Error" => "Data could not be retrived"));
    http_response_code(404);
}






?>