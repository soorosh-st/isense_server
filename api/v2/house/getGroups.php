<?php


header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/group.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
$data = json_decode(file_get_contents("php://input"));

$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);


$group = new group($group_name, NULL, NULL, $house_id, $conn);
if ($result = $group->readAllPanel()) {

    echo json_encode($result);
    http_response_code(200);
} else {
    echo json_encode(array("Message" => "Not enough information"));
    http_response_code(400);
}














?>