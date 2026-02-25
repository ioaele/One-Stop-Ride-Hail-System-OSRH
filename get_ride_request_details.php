<?php
session_start();
header('Content-Type: application/json');

require_once 'connect.php';

// Check if user is authenticated
if (!isset($_SESSION['users_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

$ride_request_id = $_GET['ride_request_id'] ?? null;

if (!$ride_request_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing ride_request_id'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Call stored procedure to get ride request locations only
    $sql = "{CALL [eioann09].[GetRideRequestLocations](?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $ride_request_id, PDO::PARAM_INT);
    $stmt->execute();

    $locations = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$locations) {
        echo json_encode([
            'success' => false,
            'error' => 'Ride request not found'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $locations
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
