<?php
$vehicles = isset($vehicles) ? $vehicles : [];
file_put_contents(__DIR__ . '/debug2.txt', print_r($vehicles, true), FILE_APPEND);
foreach ($vehicles as &$vehicle) {
    if (isset($vehicle['location'])) {
        unset($vehicle['location']);
    }
}
require 'connect.php';
header('Content-Type: application/json');


$raw = trim(file_get_contents("php://input"));
$data = json_decode($raw);
session_start();

function parse_datetime($input) {
    $formats = [
        'Y-m-d\TH:i',
        'Y-m-d\TH:i:s',
        'Y-m-d H:i',
        'Y-m-d H:i:s',
    ];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $input);
        if ($dt !== false) {
            return $dt->format('Y-m-d H:i:s');
        }
    }
    try {
        $dt = new DateTime($input);
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return false;
    }
}

try {
    if ($data === null) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
        exit;
    }

    $service_type_id = isset($data->service_type_id) ? intval($data->service_type_id) : null;
    $rental_start_raw = isset($data->rental_start) ? $data->rental_start : null;
    $rental_end_raw = isset($data->rental_end) ? $data->rental_end : null;

    if (!$service_type_id || !$rental_start_raw || !$rental_end_raw) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required parameters: service_type_id, rental_start, rental_end'
        ]);
        exit;
    }

    $rental_start = parse_datetime($rental_start_raw);
    $rental_end = parse_datetime($rental_end_raw);

    if (!$rental_start || !$rental_end) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid rental_start or rental_end datetime format',
            'debug' => [
                'rental_start_raw' => $rental_start_raw,
                'rental_end_raw' => $rental_end_raw
            ]
        ]);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed'
        ]);
        exit;
    }

    $sql = "{CALL [eioann09].[GetAvailableRentalVehicles](?, ?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $service_type_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $rental_start, PDO::PARAM_STR);
    $stmt->bindParam(3, $rental_end, PDO::PARAM_STR);

    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    // Remove 'location' field from each vehicle (if present)
    foreach ($vehicles as &$vehicle) {
        if (isset($vehicle['location'])) {
            unset($vehicle['location']);
        }
    }
    unset($vehicle); // break reference
    echo json_encode([
        'status' => 'success',
        'vehicles' => $vehicles,
        'count' => count($vehicles)
    ]);
    if (function_exists('ob_flush')) { ob_flush(); }
    flush();

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

if (!headers_sent()) {
    if (!isset($vehicles)) {
        $vehicles = [];
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Unknown server error (final fallback)',
        'vehicles' => $vehicles,
        'count' => count($vehicles)
    ]);
}


