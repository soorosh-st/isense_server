<?php


header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/group.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
$data = json_decode(file_get_contents("php://input"));

$house_id = filter_var($data->house_id, FILTER_SANITIZE_STRING);
$group_name = filter_var($data->group, FILTER_SANITIZE_STRING);
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

$group = new group($group_name, NULL, NULL, $house_id, $conn);
if ($group_name == 'All') {
    $result = $group->readAll();
    echo json_encode($result);
    http_response_code(200);
} else if (ctype_digit($group_name)) {
    $result = $group->readSingle();
    echo json_encode(array("Smartkeys" => $result));
    http_response_code(200);
} else if ($group_name == 'Top') {
    $result = $group->readTop();
    echo json_encode($result);
    http_response_code(200);
} else {
    echo json_encode(array("Message" => "Not enough information"));
    http_response_code(400);
}














?>