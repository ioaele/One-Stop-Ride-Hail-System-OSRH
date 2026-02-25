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
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;

if ($lat === null || $lng === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing coordinates'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get driver_id
    $stmt = $conn->prepare('SELECT driver_id FROM DRIVER WHERE users_id = ?');
    $stmt->execute([$users_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Driver not found']);
        exit;
    }
    $driver_id = $row['driver_id'];

        // Use stored procedure to get distance to dropoff
        $pointWKT = sprintf('POINT(%f %f)', $lng, $lat);
        $sql = "{CALL [eioann09].[GetDistanceToDropoff](?, ?)}";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $driver_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $pointWKT, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['success' => false, 'error' => 'No active ride found']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'distance_meters' => $row['distance_meters']
        ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
