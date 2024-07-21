<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/house.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
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
$userToremove = filter_var($data->userToremove, FILTER_SANITIZE_STRING);
$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);

if (!$userToremove || !$house_id) {
    http_response_code(400);
    echo json_encode(array("message" => "Not enough information"));
    die();
}

$user = new user($conn, NULL, NULL, NULL, NULL, $token);
$result = $uesr->removeUserFromHouse($userToremove, $house_id);
if ($result['success']) {
    http_response_code($result["code"]);
    echo json_encode(array("message" => $result['message']));
} else {
    http_response_code($result["code"]);
    echo json_encode(array("message" => $result['message']));
}

?>