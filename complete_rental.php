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

$data = json_decode(file_get_contents('php://input'), true);
$ride_id = isset($data['ride_id']) ? intval($data['ride_id']) : null;
$user_lat = isset($data['user_lat']) ? floatval($data['user_lat']) : null;
$user_lng = isset($data['user_lng']) ? floatval($data['user_lng']) : null;

if (!$ride_id || $user_lat === null || $user_lng === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing parameters'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    $sql = "{CALL CompleteRentalEarly(?, ?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $ride_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $user_lat, PDO::PARAM_STR);
    $stmt->bindValue(3, $user_lng, PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode([
        'success' => true
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
