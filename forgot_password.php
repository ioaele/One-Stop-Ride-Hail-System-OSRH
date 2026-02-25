<?php

require 'connect.php';  

header('Content-Type: application/json');

$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$username         = $data->username ;
$email            = $data->email;
$password_plain   = $data->password ;
$confirm_password = $data->confirm_password ;

if ($password_plain !== $confirm_password) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Passwords do not match.',
    ]);
    exit;
}

$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

$sql = "{CALL [eioann09].[ForgotPassword](?,?,?)}";

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(1, $username,        PDO::PARAM_STR);
    $stmt->bindParam(2, $email,           PDO::PARAM_STR);
    $stmt->bindParam(3, $password_hashed, PDO::PARAM_STR);

    $stmt->execute();


    $affected = $stmt->rowCount();

    if ($affected > 0) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Password changed. Please log in!'
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Wrong username/email.'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected error while changing password. Please try again later.',
    ]);
}
