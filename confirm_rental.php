<?php
// confirm_rental.php

session_start();
require_once 'connect.php';
header('Content-Type: application/json');

// Get user and vehicle info from session or POST
$user_id = $_SESSION['user_id'] ?? $_SESSION['users_id'] ?? null;
$vehicle_id = $_SESSION['selected_vehicle_id'] ?? ($_POST['vehicle_id'] ?? null);
$rental_start = $_SESSION['rental_start'] ?? ($_POST['rental_start'] ?? null);
$rental_end = $_SESSION['rental_end'] ?? ($_POST['rental_end'] ?? null);
$service_type_id = $_SESSION['service_type_id'] ?? ($_POST['service_type_id'] ?? null);

if (!$user_id || !$vehicle_id || !$rental_start || !$rental_end || !$service_type_id) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $sql = "DECLARE @ride_id INT; EXEC [eioann09].[ConfirmRentalAndInsertRide] ?, ?, ?, ?, ?, @ride_id OUTPUT; SELECT @ride_id as ride_id;";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $vehicle_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $rental_start, PDO::PARAM_STR);
    $stmt->bindValue(4, $rental_end, PDO::PARAM_STR);
    $stmt->bindValue(5, $service_type_id, PDO::PARAM_INT);
    $stmt->execute();

    $ride_id = null;
    do {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['ride_id'])) {
            $ride_id = $row['ride_id'];
        }
    } while ($stmt->nextRowset());

    if ($ride_id) {
        echo json_encode(['success' => true, 'ride_id' => $ride_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ride not created.']);
    }
    $stmt->closeCursor();
    $db->closeConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
