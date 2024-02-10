<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


$data = json_decode(file_get_contents("php://input"));
$name = filter_var($data->name, FILTER_SANITIZE_STRING);
$key_firmware_version = filter_var($data->key_firmware_version, FILTER_SANITIZE_STRING);

if (!$name || !$key_firmware_version) {
    http_response_code(400);
    echo json_encode(array("message" => "Not enough information"));
    die();
}

$house = new house($name, $conn, NULL);

if ($house->create($key_firmware_version)) {
    http_response_code(200);
    echo json_encode(array("message" => "House created"));
} else {
    http_response_code(409);
    echo json_encode(array("message" => "Cant create the House"));
}

?>