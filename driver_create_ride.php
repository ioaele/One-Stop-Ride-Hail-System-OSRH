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

    // Call stored procedure to start the ride and insert into RIDE table
    $sql = "{CALL [eioann09].[DriverStartRide](?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $users_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $ride_request_id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? 'Ride started successfully',
        'ride_id' => $result['ride_id'] ?? null,
        'ride_request_id' => $ride_request_id
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
