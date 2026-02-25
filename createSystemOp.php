<?php

require 'connect.php';  

header('Content-Type: application/json');

if (strcasecmp($_SERVER["REQUEST_METHOD"], 'POST') !== 0) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method must be POST']);
    exit;
}

$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$so_username = $data->so_username;
$sa_users = $data->sa_users_id ;



try {
    $db   = new Database();
    $conn = $db->getConnection();

    $sql = "{CALL [eioann09].[CreateSystemOperator](?,?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $so_username, PDO::PARAM_STR);
    $stmt->bindParam(2, $sa_users, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode([
        'status'  => 'success',
        'message' => 'User is now a System Operator.'
    ]);

} catch (PDOException $e) {

    $msg = $e->getMessage(); // pairnoume ta raise errors tou db
    error_log("CreateSystemOperator error: " . $msg);

    if (str_contains($msg, 'The requested S.O is not a sign up user')) {
        $userMessage = 'The requested System Operator is not a registered user.';
    } elseif (str_contains($msg, 'User is already a System Operator')) {
        $userMessage = 'User is already a System Operator.';
    } else {
        $userMessage = 'Unexpected error while creating System Operator.';
    }

    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $userMessage,
        'debug'   => $msg
    ]);
}
