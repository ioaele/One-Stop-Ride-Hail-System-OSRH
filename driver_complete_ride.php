<?php
session_start();
header('Content-Type: application/json');

require_once 'connect.php';

if (!isset($_SESSION['users_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

$users_id = $_SESSION['users_id'];
$ride_request_id = $_POST['ride_request_id'] ?? null;
$current_lat = $_POST['current_lat'] ?? null;
$current_long = $_POST['current_long'] ?? null;

if (!$ride_request_id || !$current_lat || !$current_long) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters (ride_request_id, current_lat, current_long)'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $sql = "{CALL [eioann09].[DriverCompleteRide](?, ?, ?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $users_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $ride_request_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $current_lat, PDO::PARAM_STR);
    $stmt->bindValue(4, $current_long, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? 'Ride completed successfully',
        'distance_from_dropoff' => $result['distance_from_dropoff'] ?? null
    ]);

} catch (PDOException $e) {
    // Check if it's a SQL Server error message
    $errorMessage = $e->getMessage();
    
    // Extract custom error message if it exists
    if (strpos($errorMessage, 'meters away from dropoff') !== false) {
        preg_match('/You are (\d+) meters away/', $errorMessage, $matches);
        echo json_encode([
            'success' => false,
            'error' => $matches[0] ?? 'You must be within 50 meters of dropoff to complete the ride',
            'too_far' => true
        ]);
    } else if (strpos($errorMessage, 'not in progress') !== false) {
        echo json_encode([
            'success' => false,
            'error' => 'This ride is not in progress or does not belong to you'
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
