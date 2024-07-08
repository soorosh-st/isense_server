<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
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


if (!$username) {
    http_response_code(400);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}

$user = new user($conn, NULL, NULL, true, NULL, $token);


if ($user->checkAccess()) {
    http_response_code(200);
    echo json_encode(array("Message" => "Access Granted"));
} else {
    http_response_code(403);
    echo json_encode(array("Message" => "Access rejected"));
}



?>