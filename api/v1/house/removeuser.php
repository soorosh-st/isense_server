<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


$data = json_decode(file_get_contents("php://input"));


$userToremove = filter_var($data->userToremove, FILTER_SANITIZE_STRING);
$id = filter_var($data->house_id, FILTER_SANITIZE_STRING);

if (!$userToremove || !$id) {
    http_response_code(400);
    echo json_encode(array("message" => "Not enough information"));
    die();
}

$house = new house(NULL, $conn, $id);
$result = $house->removeUserFromHouse($userToremove);
if ($result['success']) {
    http_response_code(200);
    echo json_encode(array("message" => $result['message']));
} else {
    http_response_code(409);
    echo json_encode(array("message" => $result['message']));
}

?>