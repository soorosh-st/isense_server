<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/group.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
$data = json_decode(file_get_contents("php://input"));
$devices = $data->devices;
$group_id = filter_var($data->group_id, FILTER_SANITIZE_STRING);
$group_name = filter_var($data->group_name, FILTER_SANITIZE_STRING);




$group = new group($group_id, $group_name, NULL, NULL, $conn);
if ($group->import($devices)) {
    http_response_code(200);
    echo json_encode(["message" => "Devices added successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Failed to add some or all devices to room"]);
}


















?>