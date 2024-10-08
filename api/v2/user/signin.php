<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$data = json_decode(file_get_contents("php://input"));
$username = filter_var($data->username, FILTER_SANITIZE_STRING);
$password = filter_var($data->password, FILTER_SANITIZE_STRING);
$iv = filter_var($data->iv, FILTER_SANITIZE_STRING);

if (!$username || !$password) {
    http_response_code(401);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}

$user = new user($conn, $username, $password, NULL, NULL, NULL, NULL);
if ($result = $user->signin($iv)) {
    http_response_code($result['code']);
    echo json_encode(
        $result['result']
    );
}






?>