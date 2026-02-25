<?php
session_start();
require_once 'connect.php';

// Set precision for float values to maintain decimal accuracy
ini_set('precision', 17);
ini_set('serialize_precision', -1);

header('Content-Type: application/json');

// Get JSON input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['latitude']) || !isset($data['longitude'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing latitude or longitude'
    ]);
    exit;
}

$latitude = floatval($data['latitude']);
$longitude = floatval($data['longitude']);

// Optional attributes (can be populated via reverse geocoding API)
$parish = $data['parish'] ?? null;
$providence = $data['providence'] ?? null;
$postcode = $data['postcode'] ?? null;
$country = $data['country'] ?? null;
$city = $data['city'] ?? null;
$radius = isset($data['radius']) ? floatval($data['radius']) : null;
$start = isset($data['start']) ? intval($data['start']) : 0; // 0=pickup, 1=dropoff

// TODO: If attributes are not provided, use reverse geocoding to get them
// Example: Call reverse_geocode.php internally or use Nominatim API
// if (!$city || !$country) {
//     $geocode = reverseGeocode($latitude, $longitude);
//     $city = $city ?? $geocode['city'];
//     $country = $country ?? $geocode['country'];
//     // etc.
// }

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
    
    // Call CreatePoint stored procedure with all attributes
    $pointId = null;
    $sql = "{CALL CreatePoint(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $latitude, PDO::PARAM_STR);
    $stmt->bindParam(2, $longitude, PDO::PARAM_STR);
    $stmt->bindParam(3, $parish, PDO::PARAM_STR);
    $stmt->bindParam(4, $providence, PDO::PARAM_STR);
    $stmt->bindParam(5, $postcode, PDO::PARAM_STR);
    $stmt->bindParam(6, $country, PDO::PARAM_STR);
    $stmt->bindParam(7, $city, PDO::PARAM_STR);
    $stmt->bindParam(8, $radius, PDO::PARAM_STR);
    $stmt->bindParam(9, $start, PDO::PARAM_INT);
    $stmt->bindParam(10, $pointId, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 4);
    $stmt->execute();
    $stmt->nextRowset();
    $stmt->closeCursor();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Point created successfully',
        'point_id' => $pointId,
        'latitude' => $latitude,
        'longitude' => $longitude
    ], JSON_PRESERVE_ZERO_FRACTION | JSON_NUMERIC_CHECK);
    
} catch (PDOException $e) {
    error_log('create_point.php error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
