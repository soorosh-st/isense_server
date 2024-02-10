<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/scenario.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


$data = json_decode(file_get_contents("php://input"));

$id = $data->id;



$arrayOfscenario = [];
foreach ($data->scenarios as $item) {
    $isActive = isset($item->isActive) ? $item->isActive : false;
    $scenarioObject = new scenario(
        $item->key,
        $item->name,

    );
    $arrayOfscenario[] = $scenarioObject;
}
$house = new house(NULL, $conn, $id);
$house->addscenario($arrayOfscenario);
http_response_code(200);

?>