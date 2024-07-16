<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartKey.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


$data = json_decode(file_get_contents("php://input"));

$id = $data->house_id;

$house = new house(NULL, $conn, $id);

$result = $house->getupdates();
echo json_encode($result);

http_response_code(200);

?>