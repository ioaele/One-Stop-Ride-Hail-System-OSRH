<?php
session_start();
header('Content-Type: application/json');

require_once 'connect.php';

// Check if user is authenticated
if (!isset($_SESSION['users_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not authenticated'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $users_id = $_SESSION['users_id'];
    
    // Get user information - adjust table and column names to match your database
    $sql = "SELECT username, email, name FROM [eioann09].[USERS] WHERE users_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$users_id]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode([
            'status' => 'success',
            'username' => $user['username'],
            'email' => $user['email'] ?? null,
            'name' => $user['name'] ?? null
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error fetching user info: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error'
    ]);
}
?>