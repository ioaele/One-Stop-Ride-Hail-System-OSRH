<?php
session_start();

header('Content-Type: application/json; charset=utf-8');


// Accept JSON input if content-type is application/json, else use form data
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!is_array($data)) $data = [];
} else {
    $data = $_POST;
}

// Store all relevant vehicle info in session if present
if (isset($data['selected_vehicle_id'])) {
    $_SESSION['selected_vehicle_id'] = $data['selected_vehicle_id'];
}
if (isset($data['vehicle_type_name'])) {
    $_SESSION['vehicle_type_name'] = $data['vehicle_type_name'];
}
if (isset($data['license_plate'])) {
    $_SESSION['license_plate'] = $data['license_plate'];
}
if (isset($data['rental_start'])) {
    $dt = strtotime($data['rental_start']);
    $_SESSION['rental_start'] = $dt ? date('Y-m-d H:i:s', $dt) : $data['rental_start'];
}
if (isset($data['rental_end'])) {
    $dt = strtotime($data['rental_end']);
    $_SESSION['rental_end'] = $dt ? date('Y-m-d H:i:s', $dt) : $data['rental_end'];
}

// (Keep the old logic for other fields if needed)
$allowed = ['service','service_type','pickup_lat','pickup_lng','dropoff_lat','dropoff_lng','selected_vehicle_type_id'];
foreach ($allowed as $key) {
    if (isset($data[$key])) {
        $_SESSION[$key === 'service_type' ? 'selected_service_type' : ($key === 'service' ? 'selected_service' : $key)] = $data[$key];
    }
}

// If the form included a redirect flag (non-AJAX submit), redirect to the PHP page
if (isset($data['redirect']) && $data['redirect']) {
    // redirect to ride_map_options.php
    header('Location: ride_map_options.php');
    exit;
}

echo json_encode([
    'success' => true,
    'session' => $_SESSION
]);

?>
