<?php
session_start();
header('Content-Type: application/json');

require_once 'connect.php';

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

    $sql = "{CALL [eioann09].[GetDriverLocationByRideRequest](?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $ride_request_id, PDO::PARAM_INT);
    $stmt->execute();

    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$location) {
        echo json_encode([
            'success' => false,
            'error' => 'Driver location not available'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $location
    ]);

} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    
    if (strpos($errorMessage, 'not assigned') !== false) {
        echo json_encode([
            'success' => false,
            'error' => 'Driver not assigned yet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $errorMessage
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
