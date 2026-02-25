<?php
// get_ride_payment_details.php
// Returns the final price after RecordRidePayment has been called
// Improved: error reporting, output buffering, debug info
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

$users_id = $_SESSION['users_id'] ?? null;
if (!$users_id) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}


$result = null;
$debug = ['users_id' => $users_id];
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('EXEC [eioann09].[GetMostRecentCompletedRide] ?');
    $stmt->bindParam(1, $users_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug['row'] = $row;
    if ($row) {
        $result = [
            'success' => true,
            'ride_id' => $row['ride_id'] ?? null,
            'total' => isset($row['price']) ? floatval($row['price']) : null,
            'vehicle_id' => $row['vehicle_id'] ?? null
        ];
    } else {
        $result = ['success' => false, 'message' => 'No completed ride found.'];
    }
} catch (Exception $e) {
    $result = ['success' => false, 'message' => $e->getMessage()];
    $debug['exception'] = $e->getMessage();
}

$output = ob_get_clean();
if (!empty($output)) {
    $result['debug_output'] = $output;
}
// Always include debug info
$result['debug'] = $debug;
echo json_encode($result);
