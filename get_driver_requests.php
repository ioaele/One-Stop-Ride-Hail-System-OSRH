<?php
session_start();
error_reporting(0); // Suppress PHP errors from corrupting JSON output
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'connect.php';

// Get driver ID from session or other source
$users_id = $_SESSION['users_id'] ?? null;

if (!$users_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Driver not logged in'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Prepare and execute stored procedure
    $sql = "{CALL [eioann09].[getDriverRequestsWithInARadius](?)}";
    $stmt = $conn->prepare($sql);
   
    $stmt->bindValue(1, $users_id, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch all results
    $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $rides,
        'count' => count($rides)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
