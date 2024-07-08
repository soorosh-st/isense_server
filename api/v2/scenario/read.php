<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/scenario.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);
$theme = filter_var($data->theme, FILTER_SANITIZE_STRING);
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
if (!$house_id || !$theme) {
    http_response_code(400);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}


$scenario = new scenario(NULL, NULL, NULL, NULL, NULL);
if ($response = $scenario->readAll($house_id, $token, $conn, $theme)) {
    echo json_encode($response);
    http_response_code(200);
} else {
    echo json_encode(array("Message" => "Data could not be retrived"));
    http_response_code(400);
}






?>