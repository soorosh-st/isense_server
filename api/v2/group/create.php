<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/group.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
$data = json_decode(file_get_contents("php://input"));
$title = filter_var($data->title, FILTER_SANITIZE_STRING);
//$src = filter_var($data->img_src, FILTER_SANITIZE_STRING);
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

$group = new group(NULL, $title, NULL, $house_id, $conn);
if ($group->create()) {
    http_response_code(200);
    echo json_encode(["message" => "Room created successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Failed to create room"]);
}















?>