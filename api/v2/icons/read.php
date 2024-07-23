<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/image.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


$image = new image($conn);

echo json_encode($image->readAll());
http_response_code(200);