<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/group.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
$data = json_decode(file_get_contents("php://input"));
$devices = $data->devices;
$group_id = filter_var($data->group_id, FILTER_SANITIZE_STRING);


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


$group = new group($group_id, NULL, NULL, $NULL, $conn);
if ($group->import($devices)) {
    http_response_code(200);
    echo json_encode(["message" => "Devices added successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Failed to add some or all devices to room"]);
}


















?>