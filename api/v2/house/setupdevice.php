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
    $newCommand = isset($item->newCommand) ? $item->newCommand : false;
    $smartKeyObject = new smartKey(
        $item->key_id,
        $item->key_name,
        $item->key_status,
        $item->active_color,
        $item->deactive_color,
        $item->firmware_version,
        $item->key_model,
        $newCommand
    );
    $arrayOfSmartKeys[] = $smartKeyObject;
}
$house = new house(NULL, $conn, $id);
$house->addKey($arrayOfSmartKeys);
http_response_code(200);

?>