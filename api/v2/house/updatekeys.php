<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartKey.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


$data = json_decode(file_get_contents("php://input"));

$id = $data->id;



$arrayOfSmartKeys = [];
foreach ($data->smartKeys as $item) {
    $smartKeyObject = new smartKey(
        $item->key_id,
        NULL,
        $item->key_status,
        NULL,
        NULL,
        NULL,
        NULL,
        true
    );
    $arrayOfSmartKeys[] = $smartKeyObject;
}
$house = new house(NULL, $conn, $id);
$house->updateKey($arrayOfSmartKeys);
http_response_code(200);

?>