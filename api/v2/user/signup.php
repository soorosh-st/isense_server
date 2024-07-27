<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Credentials: true");
require_once $_SERVER['DOCUMENT_ROOT'] . '/data/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


$data = json_decode(file_get_contents("php://input"));

$username = filter_var($data->username, FILTER_SANITIZE_STRING);
$password = filter_var($data->password, FILTER_SANITIZE_STRING);
$isManager = filter_var($data->isManager, FILTER_VALIDATE_BOOLEAN);
if (isset($data->timeout))
    $timeout = filter_var($data->timeout, FILTER_SANITIZE_STRING);




if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(array("Message" => "Not enough information"));
    die();
}

if ($isManager)
    $user = new user($conn, $username, $password, true, NULL, NULL, NULL);
else
    $user = new user($conn, $username, $password, false, $timeout, NULL, NULL);

if ($reslut = $user->signup()) {
    http_response_code(200);
    echo json_encode($reslut);
} else {
    http_response_code(409);
    echo json_encode(array("Message" => "Cant create the user"));
}

?>