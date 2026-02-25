<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// Store rental-specific session data
if (isset($data['service_type_id'])) {
    $_SESSION['service_type_id'] = $data['service_type_id'];
}
if (isset($data['rental_start'])) {
    $_SESSION['rental_start'] = $data['rental_start'];
}
if (isset($data['rental_end'])) {
    $_SESSION['rental_end'] = $data['rental_end'];
}

echo json_encode(['status' => 'success']);
?>
