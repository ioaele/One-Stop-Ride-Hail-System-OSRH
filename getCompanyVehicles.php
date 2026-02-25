<?php
<<<<<<< HEAD
=======

>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['users_id'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'User not logged in'
    ]);
    exit;
}

require_once 'connect.php';

try {
<<<<<<< HEAD
    $database = new Database();
    $conn = $database->getConnection();

    $users_id = (int)$_SESSION['users_id'];

=======

    $database = new Database();
$conn = $database->getConnection();
   
    $users_id = (int)$_SESSION['users_id'];
    if (!isset($_SESSION['users_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit;
    }
  
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
    $sql = "{CALL getCompanyVehicle(?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $users_id, PDO::PARAM_INT);

    $stmt->execute();

<<<<<<< HEAD
    // Expected columns: license_plate, vehicle_type, latitude, longitude
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    echo json_encode([
        'status'   => 'success',
        'vehicles' => $rows ?: []
    ]);
} catch (PDOException $e) {
=======
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    
    echo json_encode([
        'status'   => 'success',
        'vehicles' => $rows ?: []   
    ]);
} catch (PDOException $e) {
   
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
<<<<<<< HEAD
        'message' => 'Database error'
=======
        'message' => $e->getMessage()  
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
    ]);
}

exit;
