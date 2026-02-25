<?php
// get_distance_between_points.php
header('Content-Type: application/json');
require_once 'connect.php';

$lat1 = isset($_GET['lat1']) ? floatval($_GET['lat1']) : null;
$lng1 = isset($_GET['lng1']) ? floatval($_GET['lng1']) : null;
$lat2 = isset($_GET['lat2']) ? floatval($_GET['lat2']) : null;
$lng2 = isset($_GET['lng2']) ? floatval($_GET['lng2']) : null;

if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
    echo json_encode(['success' => false, 'message' => 'Missing coordinates']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('EXEC [eioann09].[GetDistanceBetweenPoints] ?, ?, ?, ?');
    $stmt->bindParam(1, $lat1);
    $stmt->bindParam(2, $lng1);
    $stmt->bindParam(3, $lat2);
    $stmt->bindParam(4, $lng2);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $distance = $row ? floatval($row['distance_meters']) : null;
    echo json_encode(['success' => true, 'distance_meters' => $distance]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
