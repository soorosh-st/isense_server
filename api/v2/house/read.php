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



$user = new user($conn, NULL, NULL, true, NULL, $token, NULL);


if ($result = $user->getHouses()) {
    http_response_code($result['code']);
    echo json_encode(
        $result['result']
    );
}




?>