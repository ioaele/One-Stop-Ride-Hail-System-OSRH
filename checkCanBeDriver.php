<?php
session_start();
header('Content-Type: application/json');

require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$users_id = $_SESSION['users_id'] ?? null;

if ($users_id === null) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();
    
    $sql = "EXEC [eioann09].[CanBeDriver] ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $users_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // If we reach here without exception, user can be a driver
    echo json_encode([
        'success' => true,
        'message' => 'You are eligible to be a driver'
    ]);

} catch (PDOException $e) {
    error_log("CanBeDriver error for user $users_id: " . $e->getMessage());
    
    $errorMessage = $e->getMessage();
    $errorCode = $e->errorInfo[1] ?? null;
    
    // Check if this is our custom RAISERROR (error code 50000)
    if ($errorCode == 50000) {
        // Extract the custom error message from the exception
        // The message format is: "SQLSTATE[42000]: [Microsoft][ODBC Driver...][SQL Server]YOUR MESSAGE"
        if (preg_match('/\[SQL Server\](.+)$/', $errorMessage, $matches)) {
            $customMessage = trim($matches[1]);
            
            echo json_encode([
                'success' => false,
                'message' => $customMessage
            ]);
        } else {
            // Fallback if we can't parse the message
            echo json_encode([
                'success' => false,
                'message' => 'You must be at least 18 years old to register as a driver.'
            ]);
        }
    } else {
        // Some other database error
        echo json_encode([
            'success' => false,
            'message' => 'A database error occurred. Please try again later.'
        ]);
    }
}
?>