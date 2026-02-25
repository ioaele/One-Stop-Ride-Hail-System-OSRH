<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['users_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}


$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['license_plate'], $input['latitude'], $input['longitude'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Missing license_plate, latitude or longitude'
    ]);
    exit;
}

$license_plate = (string)$input['license_plate'];
$latitude      = (float)$input['latitude'];
$longitude     = (float)$input['longitude'];


if (trim($license_plate) === '') {
    echo json_encode(['status' => 'error', 'message' => 'License plate cannot be empty']);
    exit;
}

if ($latitude < -90 || $latitude > 90) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid latitude. Must be between -90 and 90']);
    exit;
}

if ($longitude < -180 || $longitude > 180) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid longitude. Must be between -180 and 180']);
    exit;
}


require_once 'connect.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Database connection failed'
        ]);
        exit;
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  
    $sql = "{CALL [eioann09].[insertVehicleLocation](?, ?, ?)}";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $license_plate,
        $latitude,
        $longitude
    ]);

    echo json_encode([
        'status'        => 'success',
        'message'       => 'Vehicle location updated successfully',
        'license_plate' => $license_plate,
        'latitude'      => $latitude,
        'longitude'     => $longitude
    ]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to update location',
    ]);
    exit;
}
