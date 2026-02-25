<?php
require 'connect.php';
header('Content-Type: application/json');

$users_id = isset($_GET['users_id']) ? intval($_GET['users_id']) : null;

if (!$users_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing users_id']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Use stored procedure to get ride request status
    $sql = "{CALL [eioann09].[GetRideRequestStatus](?, ?, ?, ?)}";
    $stmt = $conn->prepare($sql);
    $status = null;
    $response_time = null;
    $ride_id = null;
    
    $stmt->bindParam(1, $users_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $status, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 50);
    $stmt->bindParam(3, $response_time, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 50);
    $stmt->bindParam(4, $ride_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 4);
    
    $stmt->execute();
    $stmt->closeCursor();

    // Return response based on status
    if ($status === 'none') {
        echo json_encode(['status' => 'none', 'message' => 'No request found']);
    } elseif ($status === 'accepted') {
        echo json_encode([
            'status' => 'accepted',
            'message' => 'Driver has accepted your request',
            'ride_id' => $ride_id,
            'response_time' => $response_time
        ]);
    } elseif ($status === 'cancelled') {
        echo json_encode([
            'status' => 'cancelled',
            'message' => 'Request was cancelled or declined'
        ]);
    } else {
        // Pending or other status
        echo json_encode([
            'status' => $status,
            'message' => 'Waiting for driver acceptance'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
