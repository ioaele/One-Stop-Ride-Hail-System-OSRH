<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
  'session_data' => $_SESSION,
  'selected_vehicle_type_id' => $_SESSION['selected_vehicle_type_id'] ?? 'NOT SET'
]);
?>
