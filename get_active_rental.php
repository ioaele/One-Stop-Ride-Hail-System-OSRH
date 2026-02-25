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

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed',
            'pdo_error' => $conn ? $conn->errorInfo() : 'no connection'
        ]);
        exit;
    }
    $sql = "{CALL getActiverental(?)}";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Statement prepare failed',
            'pdo_error' => $conn->errorInfo()
        ]);
        exit;
    }
    $stmt->bindValue(1, $users_id, PDO::PARAM_INT);
    $success = $stmt->execute();
    $success = $stmt->execute();
    // Workaround for SQL Server stored procedure: skip empty result sets
    do {
        $rental = $stmt->fetch(PDO::FETCH_ASSOC);
    } while ($stmt->nextRowset() && $rental === false);
    if (!$rental) {
        echo json_encode([
            'success' => true,
            'rental' => null
        ]);
        exit;
    }
    // Store active rental info in session (for compatibility)
    $_SESSION['active_rental_id'] = $rental['ride_id'] ?? null;
    $_SESSION['active_vehicle_id'] = $rental['vehicle_id'] ?? null;
    $_SESSION['active_license_plate'] = $rental['license_plate'] ?? null;
    $_SESSION['active_rental_start'] = $rental['ride_datetime_start'] ?? null;
    $_SESSION['active_rental_end'] = $rental['ride_datetime_end'] ?? null;
    $_SESSION['active_rental_status'] = $rental['status'] ?? null;
    $_SESSION['active_rental_price'] = $rental['price'] ?? null;
    echo json_encode([
        'success' => true,
        'rental' => $rental
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
