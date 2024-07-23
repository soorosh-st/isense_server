<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/scenario.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);
$scenarios = $data->scenarios;
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
if (!$house_id || !$scenarios) {
    http_response_code(400);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}
foreach ($scenarios as $item) {
    $count += 1;
}
if ($count > 4) {
    http_response_code(400);
    echo json_encode(array("Message" => "You can only set four scenarios as favorite"));
    die();
}

$scenario = new scenario(NULL, NULL, NULL, NULL, NULL);
if ($scenario->favorite($house_id, $token, $conn, $scenarios)) {
    echo json_encode(array("Message" => "Scenarios set as favorite"));
    http_response_code(200);
} else {
    echo json_encode(array("Message" => "Scenarios cound not be set"));
    http_response_code(400);
}






?>