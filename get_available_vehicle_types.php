<?php
require 'connect.php';

header('Content-Type: application/json');

$raw = trim(file_get_contents("php://input"));
$data = json_decode($raw);

// If JSON decode failed, try reading URL-encoded form data fallback
if ($data === null) {
    parse_str($raw, $parsed);
    if (!empty($parsed)) {
        $data = (object) $parsed;
    } elseif (!empty($_POST)) {
        // also accept normal $_POST as ultimate fallback
        $data = (object) $_POST;
    }
}

$service_type_id = isset($data->service_type_id) ? intval($data->service_type_id) : null;
$latitude        = isset($data->lat) ? floatval($data->lat) : null;
$longitude       = isset($data->lng) ? floatval($data->lng) : null;
$search_radius   = isset($data->search_radius) ? intval($data->search_radius) : 3000;

$debug = (isset($_GET['debug']) && $_GET['debug']) || (isset($data->debug) && $data->debug);

// Validate inputs
if ($service_type_id === null || $latitude === null || $longitude === null) {
    $resp = [
        'status'  => 'error',
        'message' => 'service_type_id, lat, and lng are required.'
    ];
    if ($debug) {
        $resp['debug'] = [
            'raw_input' => $raw,
            'parsed'    => $data
        ];
    }
    echo json_encode($resp);
    exit;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        http_response_code(500);
        $resp = [
            'status' => 'error',
            'message' => 'Database connection failed'
        ];
        if ($debug) {
            $resp['debug'] = [ 'raw_input' => $raw, 'parsed' => $data ];
            // also try to include PHP error log tail if available
            $logPath = 'C:/xampp/php/logs/php_error_log';
            if (file_exists($logPath)) {
                $resp['debug']['php_error_tail'] = array_slice(explode("\n", file_get_contents($logPath)), -30);
            }
        }
        // write debug log for local inspection
        try {
            $dbg = '['.date('c').'] DB connection failed: ' . json_encode($resp) . "\n";
            file_put_contents(__DIR__ . '/get_available_vehicle_types_debug.log', $dbg, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // ignore logging errors
        }
        echo json_encode($resp);
        exit;
    }

    // Call the stored procedure (use debug version which exists and returns results)
    $sql = "{CALL [eioann09].[GetAvailableVehicleTypes](?, ?, ?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $service_type_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $latitude, PDO::PARAM_STR);
    $stmt->bindParam(3, $longitude, PDO::PARAM_STR);
    $stmt->bindParam(4, $search_radius, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch all vehicle types
    $vehicle_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    // Enrich results with vehicle type names if SP didn't return them
    $needNames = false;
    foreach ($vehicle_types as $row) {
        if (!isset($row['name']) || $row['name'] === null || $row['name'] === '') {
            $needNames = true;
            break;
        }
    }

    if ($needNames && count($vehicle_types) > 0) {
        $ids = array_unique(array_map(function($r){ return isset($r['vehicle_type_id']) ? intval($r['vehicle_type_id']) : null; }, $vehicle_types));
        $ids = array_filter($ids, function($v){ return $v !== null; });
        if (count($ids) > 0) {
            $in = implode(',', array_map('intval', $ids));
            try {
                $sql2 = "SELECT vehicle_type_id, name FROM VehicleType WHERE vehicle_type_id IN ($in)";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->execute();
                $names = [];
                while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                    $names[intval($r['vehicle_type_id'])] = $r['name'];
                }
                $stmt2->closeCursor();

                // merge names back into results
                foreach ($vehicle_types as &$vt) {
                    $id = isset($vt['vehicle_type_id']) ? intval($vt['vehicle_type_id']) : null;
                    if ($id !== null && (!isset($vt['name']) || $vt['name'] === null || $vt['name'] === '') && isset($names[$id])) {
                        $vt['name'] = $names[$id];
                    }
                }
                unset($vt);
            } catch (PDOException $e) {
                // ignore enrichment failure
            }
        }
    }

    // Return success response (include debug info if requested)
    $response = [
        'status'        => 'success',
        'vehicle_types' => $vehicle_types,
        'count'         => count($vehicle_types),
        'search_params' => [
            'service_type_id'      => $service_type_id,
            'latitude'             => $latitude,
            'longitude'            => $longitude,
            'search_radius_meters' => $search_radius
        ]
    ];

    if ($debug) {
        $response['debug'] = [
            'raw_input' => $raw,
            'parsed'    => $data,
            'rows'      => $vehicle_types
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    $resp = [
        'status'  => 'error',
        'message' => 'Unexpected error while fetching vehicle types. Please try again later.'
    ];
    if ($debug) {
        $resp['debug'] = [ 'exception' => $e->getMessage(), 'raw_input' => $raw, 'parsed' => $data ];
    }
    // write exception to local debug log as well
    try {
        $dbg = '['.date('c').'] Exception: ' . $e->getMessage() . "\nRaw input: " . $raw . "\nParsed: " . json_encode($data) . "\n\n";
        file_put_contents(__DIR__ . '/get_available_vehicle_types_debug.log', $dbg, FILE_APPEND | LOCK_EX);
    } catch (Exception $ex) {
        // ignore logging errors
    }
    echo json_encode($resp);
}

?>
