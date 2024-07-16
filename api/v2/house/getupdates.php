<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartKey.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


$data = json_decode(file_get_contents("php://input"));

$id = $data->house_id;
$from = $data->from;
$house = new house(NULL, $conn, $id);

if ($from == "gw") {
    $result = $house->getupdates();
    echo json_encode($result);
} else {

}

http_response_code(200);

?>