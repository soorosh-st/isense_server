<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/scenario.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));


$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);
$scenario_id = filter_var($data->scenario_id, FILTER_SANITIZE_STRING);
$token;
$headers = getallheaders();
if ($headers['Authorization']) {

    $authHeader = $headers['Authorization'];
    list($type, $token) = explode(' ', $authHeader);
    if ($type != 'Bearer') {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        die();
    }
} else {
    // Missing token
    http_response_code(401);
    echo json_encode(['error' => 'Missing token']);
    die();
}

if (!$house_id || !$scenario_id) {
    http_response_code(400);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}


$scenario = new scenario($scenario_id, NULL, NULL, NULL, NULL);

if ($response = $scenario->setKey($house_id, $scenario_id, $conn)) {
    echo json_encode(array("Message" => "Command sent successfully"));
    http_response_code(200);
} else {
    echo json_encode(array("Error" => "Could not set the scneario"));
    http_response_code(501);
}






?>