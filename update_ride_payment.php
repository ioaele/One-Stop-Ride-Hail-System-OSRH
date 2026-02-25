<?php
// update_ride_payment.php
// Call RecordRidePayment after ride completion to update price based on actual time/distance
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

$ride_id = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : null;
if (!$ride_id) {
    echo json_encode(['success' => false, 'message' => 'Missing ride_id']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('EXEC [eioann09].[RecordRidePayment] ?');
    $stmt->bindParam(1, $ride_id, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Ride payment updated.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
