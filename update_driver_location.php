<?php
session_start();
header('Content-Type: application/json');

require_once 'connect.php';

// Check if user is authenticated and is a driver
if (!isset($_SESSION['users_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not authenticated'
    ]);
    exit;
}

try {
    // Get location data from POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['latitude']) || !isset($data['longitude'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing latitude or longitude'
        ]);
        exit;
    }
    
    $users_id = $_SESSION['users_id'];
    $latitude = floatval($data['latitude']);
    $longitude = floatval($data['longitude']);
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Call the stored procedure to update driver location
    $sql = "{CALL [eioann09].[updateLocation](?, ?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $users_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $latitude, PDO::PARAM_STR);
    $stmt->bindParam(3, $longitude, PDO::PARAM_STR);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Location updated',
        'latitude' => $latitude,
        'longitude' => $longitude
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
