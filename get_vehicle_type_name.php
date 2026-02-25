<?php
session_start();

header('Content-Type: application/json');

// Try to get vehicle type ID from POST body first, then session
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$vehicleTypeId = null;
if (isset($data['vehicle_type_id'])) {
    $vehicleTypeId = intval($data['vehicle_type_id']);
} else {
    $vehicleTypeId = $_SESSION['selected_vehicle_type_id'] ?? null;
}

if (!$vehicleTypeId) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No vehicle type selected'
    ]);
    exit;
}

require 'connect.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed'
        ]);
        exit;
    }
    
    // Get vehicle type name directly
    $sql = "SELECT vehicle_type FROM [eioann09].[Vehicle_Type] WHERE vehicle_type_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $vehicleTypeId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'name' => $result['vehicle_type']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Vehicle type not found'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
