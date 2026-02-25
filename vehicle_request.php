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

<<<<<<< HEAD
// REMOVE THIS DEBUG LINE - it was breaking your JSON response
// echo json_encode($_SESSION);

// Use the role from session to determine company vs driver
$role = $_SESSION['role'] ?? null;

if ($role === 'company') {
    // Company registration - ignore driver_id even if it exists in session
    $company_id = $users_id;
    $driver_id = null;
} else {
    // Driver registration
    $driver_id = $_SESSION['driver_id'] ?? null;
    $company_id = null;
    
    if ($driver_id === null) {
        echo json_encode(['success' => false, 'message' => 'Driver ID not found in session']);
        exit;
    }
}

=======
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
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

<<<<<<< HEAD
try {
    $db   = new Database();
    $conn = $db->getConnection();

    $sql = "{CALL [eioann09].[addVehicle](?,?,?,?,?,?,?,?,?,?)}";
    $stmt = $conn->prepare($sql);

    // Handle driver_id - bind as NULL if null
    if ($driver_id === null) {
        $stmt->bindValue(1, null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(1, (int)$driver_id, PDO::PARAM_INT);
    }

    $stmt->bindValue(2, (int)$service_type,   PDO::PARAM_INT);
    $stmt->bindValue(3, (int)$seats,          PDO::PARAM_INT);
    $stmt->bindValue(4, (int)$vehicle_type,   PDO::PARAM_INT);
    $stmt->bindValue(5, $license_plate,       PDO::PARAM_STR);
    $stmt->bindValue(6, (int)$luggage_volume, PDO::PARAM_INT);
    $stmt->bindValue(7, (int)$luggage_weight, PDO::PARAM_INT);
    $stmt->bindValue(8, $interiorName,        PDO::PARAM_STR);
    $stmt->bindValue(9, $exteriorName,        PDO::PARAM_STR);

   
    if ($company_id === null) {
        $stmt->bindValue(10, null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(10, (int)$company_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
=======
$sql = "{CALL [eioann09].[registerDriverWithVehicle](?,?,?,?,?,?,?,?,?,?)}";

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
    
    $stmt->execute();
    
 
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
   
    if ($row === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'No result returned from stored procedure'
        ]);
        exit;
    }
    
<<<<<<< HEAD
    $vehicle_id    = $row['new_vehicle_id'] ?? null;
    $error_message = $row['error_message'] ?? null;
    
    if ($error_message !== null && $error_message !== '') {
        $error_type = 'general';
        
        if (strpos($error_message, 'already have a vehicle') !== false) {
            $error_type = 'duplicate_service';
        } elseif (strpos($error_message, 'criteria') !== false || strpos($error_message, 'requirements') !== false) {
            $error_type = 'criteria_not_met';
        }
        
        echo json_encode([
            'success' => false, 
            'message' => $error_message,
            'error_type' => $error_type
=======
    $vehicle_id    = $row['new_vehicle_id'] ?? $row['vehicle_id'] ?? null;
    $error_message = $row['error_message']   ?? null;
    // Get the newly created vehicle_id
    $vehicle_id = $conn->lastInsertId();

    // ✅ CRITICAL: Save vehicle_id to PHP session
    $_SESSION['vehicle_id'] = $vehicle_id;
    if ($error_message) {
        echo json_encode([
            'success' => false, 
            'message' => $error_message
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
        ]);
        exit;
    }

<<<<<<< HEAD
    if ($vehicle_id !== null && $vehicle_id > 0) {
        $_SESSION['vehicle_id'] = $vehicle_id;
        
=======
    if ($vehicle_id && $vehicle_id > 0) {
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
        echo json_encode([
            'success' => true, 
            'message' => 'Vehicle registered successfully!',
            'vehicle_id' => $vehicle_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
<<<<<<< HEAD
            'message' => 'Registration failed - no vehicle ID returned'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());

    $sqlState = $e->getCode();
    $driverErrorCode = $e->errorInfo[1] ?? null;

    if ($sqlState === '23000' && in_array($driverErrorCode, [2627, 2601])) {
        $message = 'A vehicle with this license plate is already registered.';
        $error_type = 'duplicate_plate';
    } else {
        $message = 'A database error occurred. Please try again later.';
        $error_type = 'database';
=======
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
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
    }

    echo json_encode([
        'success' => false,
<<<<<<< HEAD
        'message' => $message,
        'error_type' => $error_type
    ]);
}
=======
        'message' => $message
    ]);
}

>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
?>