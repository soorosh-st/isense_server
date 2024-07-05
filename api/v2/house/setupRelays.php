<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/smartRelay.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));

$id = $data->id;
$arrayOfSmartKeys = [];
foreach ($data->smartRelays as $item) {
    $smartKeyObject = new smartRelay(
        $item->smart_relay_id,
        $item->smartRelayStatus,
        $item->smartRelayCount,
        $item->firmwareVersion
    );
    $arrayOfSmartKeys[] = $smartKeyObject;
}
$house = new house(NULL, $conn, $id);
$house->addRelay($arrayOfSmartKeys);
http_response_code(200);

?>