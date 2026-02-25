<?php
require 'connect.php';  

header('Content-Type: application/json');

// Start session
session_start();

$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$email          = $data->email    ?? '';
$password_login = $data->password ?? '';

if ($email === '' || $password_login === '') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Email and password are required.'
    ]);
    exit;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $sql = "{CALL [eioann09].[getHashedPassword](?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $email, PDO::PARAM_STR);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Wrong credentials'
        ]);
        exit;
    }

    $users_id    = $row['users_id'];
    $username    = $row['username'];
    $stored_hash = $row['password_hashed'];

    if (!password_verify($password_login, $stored_hash)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Wrong credentials'
        ]);
        exit;
    }

    $stmt->closeCursor(); 

    // LogInUser
    $logSql = "{CALL [eioann09].[LogInUser](?)}";
    $logStmt = $conn->prepare($logSql);
    $logStmt->bindParam(1, $users_id, PDO::PARAM_INT);
    $logStmt->execute();
    $logStmt->closeCursor();

    // Get role
    $roleSql = "{CALL [eioann09].[getRoleType](?)}";
    $roleStmt = $conn->prepare($roleSql);
    $roleStmt->bindParam(1, $users_id, PDO::PARAM_INT);
    $roleStmt->execute();

    $row = $roleStmt->fetch(PDO::FETCH_ASSOC);
    $role_type = $row['type_r'] ?? null;

    $roleStmt->closeCursor();

    // Initialize driver_id as null
    $driver_id = null;

    // If user is a driver, get the driver_id
    if (strtolower(trim($role_type)) === 'driver') {
        $driverSql = "{CALL [eioann09].[getDriverID](?)}";
        $driverStmt = $conn->prepare($driverSql);
        $driverStmt->bindParam(1, $users_id, PDO::PARAM_INT);
        $driverStmt->execute();

        $driverRow = $driverStmt->fetch(PDO::FETCH_ASSOC);
        if ($driverRow && isset($driverRow['driver_id'])) {
            $driver_id = $driverRow['driver_id'];
        }

        $driverStmt->closeCursor();
    }

    // Set session variables
    $_SESSION['users_id'] = $users_id;
    $_SESSION['username'] = $username;
    $_SESSION['role']     = $role_type;
    
    if ($driver_id !== null) {
        $_SESSION['driver_id'] = $driver_id;
    }

    // Build response
    $response = [
        'status'   => 'success',
        'message'  => 'Login successful!',
        'users_id' => $users_id,
        'username' => $username,
        'role'     => $role_type
    ];

    // Include driver_id in response if user is a driver
    if ($driver_id !== null) {
        $response['driver_id'] = $driver_id;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected error while logging in. Please try again later.'
    ]);
}