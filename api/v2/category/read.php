<?php


header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/category.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
$data = json_decode(file_get_contents("php://input"));

$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);
$token;
$headers = getallheaders();
if ($headers['Authorization']) {

    $authHeader = $headers['Authorization'];
    list($type, $token) = explode(' ', $authHeader);
    if ($type != 'Bearer') {
        http_response_code(401);
        echo json_encode(['Message' => 'Invalid token']);
        die();
    }
} else {
    // Missing token
    http_response_code(401);
    echo json_encode(['Message' => 'Missing token']);
    die();
}

$category = new category($conn, $house_id);
if ($result = $category->readAll()) {
    echo json_encode($result);
    http_response_code(200);
} else {
    echo json_encode(array("Message" => "Not enough information"));
    http_response_code(400);
}














?>