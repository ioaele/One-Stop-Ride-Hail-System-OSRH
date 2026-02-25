<?php
session_start();
header('Content-Type: application/json');

require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$users_id = $_SESSION['users_id'] ?? null;

if ($users_id === null) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$service_type   = $_POST['service_type'] ?? null;
$vehicle_type   = $_POST['vehicle_type'] ?? null;
$license_plate  = $_POST['license_plate'] ?? null;
$seats          = $_POST['seats'] ?? null;
$luggage_volume = $_POST['luggage_volume'] ?? null;
$luggage_weight = $_POST['luggage_weight'] ?? null;

if (!$service_type || !$vehicle_type || !$license_plate) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$photoInterior = $_FILES['photo_interior'] ?? null;
$photoExterior = $_FILES['photo_exterior'] ?? null;

if (!$photoInterior || $photoInterior['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Interior photo required']);
    exit;
}

if (!$photoExterior || $photoExterior['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Exterior photo required']);
    exit;
}

$interiorName = $photoInterior['name'];
$exteriorName = $photoExterior['name'];

$sql = "{CALL [eioann09].[registerCompanyWithVehicle](?,?,?,?,?,?,?,?,?,?,?)}";

try {
    $db   = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare($sql);

    $stmt->bindValue(1,  null,                    PDO::PARAM_NULL);
    $stmt->bindValue(2,  (int)$users_id,          PDO::PARAM_INT);
    $stmt->bindValue(3,  (int)$service_type,      PDO::PARAM_INT);
    $stmt->bindValue(4,  (int)$seats,             PDO::PARAM_INT);
    $stmt->bindValue(5,  (int)$vehicle_type,      PDO::PARAM_INT);
    $stmt->bindValue(6,  $license_plate,          PDO::PARAM_STR);
    $stmt->bindValue(7,  (int)$luggage_volume,    PDO::PARAM_INT);
    $stmt->bindValue(8,  (int)$luggage_weight,    PDO::PARAM_INT);
    $stmt->bindValue(9,  $interiorName,           PDO::PARAM_STR);
    $stmt->bindValue(10, $exteriorName,           PDO::PARAM_STR);
    $stmt->bindValue(11, $users_id,           PDO::PARAM_INT);
    $stmt->execute();
    
 
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
   
    if ($row === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'No result returned from stored procedure'
        ]);
        exit;
    }
    
    $vehicle_id    = $row['new_vehicle_id'] ?? $row['vehicle_id'] ?? null;
    $error_message = $row['error_message']   ?? null;

    if ($error_message) {
        echo json_encode([
            'success' => false, 
            'message' => $error_message
        ]);
        exit;
    }

    if ($vehicle_id && $vehicle_id > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Vehicle registered successfully!',
            'vehicle_id' => $vehicle_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Registration failed - vehicle_id is ' . ($vehicle_id ?? 'NULL')
        ]);
    }

 } catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage()); // keep logging full error on server

    // SQLSTATE code (e.g. 23000 for integrity constraint violation)
    $sqlState = $e->getCode();
    // Driver-specific error number (e.g. 2627 or 2601 for unique key violation)
    $driverErrorCode = $e->errorInfo[1] ?? null;

    if ($sqlState === '23000' && in_array($driverErrorCode, [2627, 2601])) {
        // Friendly message to user
        $message = 'A vehicle with this license plate is already registered.';
    } else {
        // Generic error for other DB issues
        $message = 'A database error occurred. Please try again later.';
    }

    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
}

?>