<?php
header('Content-Type: application/json');

// DEBUG
error_log('========== REQUEST ==========');
error_log('POST: ' . print_r($_POST, true));
error_log('FILES: ' . print_r($_FILES, true));

// Extract FLAT data
$service_type_raw = $_POST['service_type'] ?? null;
$vehicle_type_raw = $_POST['vehicle_type'] ?? null;
$license_plate    = $_POST['license_plate'] ?? null;
$seats_raw        = $_POST['seats'] ?? null;
$luggage_vol_raw  = $_POST['luggage_volume'] ?? null;
$luggage_wt_raw   = $_POST['luggage_weight'] ?? null;

// Check what we got
$received = [
    'service_type' => $service_type_raw,
    'vehicle_type' => $vehicle_type_raw,
    'license_plate' => $license_plate,
    'seats' => $seats_raw,
    'luggage_volume' => $luggage_vol_raw,
    'luggage_weight' => $luggage_wt_raw,
    'photo_interior' => isset($_FILES['photo_interior']) ? 'YES' : 'NO',
    'photo_exterior' => isset($_FILES['photo_exterior']) ? 'YES' : 'NO',
];

error_log('Received: ' . print_r($received, true));

// Validate
if (empty($service_type_raw)) {
    echo json_encode(['status' => 'error', 'message' => 'service_type is empty', 'received' => $received]);
    exit;
}

if (empty($vehicle_type_raw)) {
    echo json_encode(['status' => 'error', 'message' => 'vehicle_type is empty', 'received' => $received]);
    exit;
}

if (empty($license_plate)) {
    echo json_encode(['status' => 'error', 'message' => 'license_plate is empty', 'received' => $received]);
    exit;
}

if (empty($seats_raw)) {
    echo json_encode(['status' => 'error', 'message' => 'seats is empty', 'received' => $received]);
    exit;
}

if (empty($luggage_vol_raw)) {
    echo json_encode(['status' => 'error', 'message' => 'luggage_volume is empty', 'received' => $received]);
    exit;
}

if (empty($luggage_wt_raw)) {
    echo json_encode(['status' => 'error', 'message' => 'luggage_weight is empty', 'received' => $received]);
    exit;
}

// Convert to integers
$service_type_id = (int)$service_type_raw;
$vehicle_type_id = (int)$vehicle_type_raw;
$seats_int       = (int)$seats_raw;
$luggage_vol_int = (int)$luggage_vol_raw;
$luggage_wt_int  = (int)$luggage_wt_raw;

// SUCCESS
echo json_encode([
    'status' => 'success',
    'message' => 'All data received correctly!',
    'data' => [
        'service_type_id' => $service_type_id,
        'vehicle_type_id' => $vehicle_type_id,
        'license_plate' => $license_plate,
        'seats' => $seats_int,
        'luggage_volume' => $luggage_vol_int,
        'luggage_weight' => $luggage_wt_int
    ]
]);