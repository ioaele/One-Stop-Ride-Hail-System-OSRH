<?php
require 'connect.php';

header('Content-Type: application/json');
session_start();

$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    $data = new stdClass();
}


$users_id   = $data->users_id   ?? ($_SESSION['users_id']   ?? null);
$vehicle_id = $data->vehicle_id ?? ($_SESSION['vehicle_id'] ?? null);


if ($users_id === null || $users_id === '') {
    echo json_encode([
        'status'   => 'no_docs',
        'message'  => 'We could not find any driver or vehicle information for your account.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

   
    $driverDocsToCorrect = [];
    $driverStatus = 'no_issues';
    
    $sqlDriver = "{CALL [eioann09].[getDriverDocsResubmit](?)}";
    $stmtDriver = $conn->prepare($sqlDriver);
    $stmtDriver->bindParam(1, $users_id, PDO::PARAM_INT);
    $stmtDriver->execute();
    $driverRows = $stmtDriver->fetchAll(PDO::FETCH_ASSOC);
    $stmtDriver->closeCursor();

    if (!empty($driverRows)) {
        $statusCode = $driverRows[0]['StatusCode'] ?? null;
        
        if ($statusCode === 'SUCCESS') {
            $driverDocsToCorrect = $driverRows;
            $driverStatus = 'has_issues';
        }
    }

    error_log("Driver docs to correct: " . count($driverDocsToCorrect));


    $vehicleDocsToCorrect = [];
    $vehicleStatus = 'no_issues';
    
    if ($vehicle_id !== null && $vehicle_id !== '') {
        $sqlVehicle = "{CALL [eioann09].[getVehicleDocsResubmit](?)}";
        $stmtVehicle = $conn->prepare($sqlVehicle);
        $stmtVehicle->bindParam(1, $vehicle_id, PDO::PARAM_INT);
        $stmtVehicle->execute();
        $vehicleRows = $stmtVehicle->fetchAll(PDO::FETCH_ASSOC);
        $stmtVehicle->closeCursor();

        if (!empty($vehicleRows)) {
            $statusCode = $vehicleRows[0]['StatusCode'] ?? null;
            
            if ($statusCode === 'SUCCESS') {
                $vehicleDocsToCorrect = $vehicleRows;
                $vehicleStatus = 'has_issues';
            }
        }
    }

    error_log("Vehicle docs to correct: " . count($vehicleDocsToCorrect));


    $hasDriverIssues = !empty($driverDocsToCorrect);
    $hasVehicleIssues = !empty($vehicleDocsToCorrect);

    if ($hasDriverIssues || $hasVehicleIssues) {
 
        $messages = [];
        if ($hasDriverIssues) {
            $messages[] = count($driverDocsToCorrect) . " driver document(s) need correction.";
        }
        if ($hasVehicleIssues) {
            $messages[] = count($vehicleDocsToCorrect) . " vehicle document(s) need correction.";
        }

        echo json_encode([
            'status'        => 'has_issues',
            'message'       => implode(' ', $messages),
            'driverDocs'    => $driverDocsToCorrect,
            'vehicleDocs'   => $vehicleDocsToCorrect,
            'users_id'      => $users_id,
            'vehicle_id'    => $vehicle_id
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Check if they have any documents at all
        $sqlCheckDriver = "SELECT COUNT(*) as cnt FROM [eioann09].[DRIVER_DOC] WHERE users_id = ?";
        $stmtCheck = $conn->prepare($sqlCheckDriver);
        $stmtCheck->execute([$users_id]);
        $driverCount = $stmtCheck->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        $vehicleCount = 0;
        if ($vehicle_id !== null && $vehicle_id !== '') {
            $sqlCheckVehicle = "SELECT COUNT(*) as cnt FROM [eioann09].[VEHICLE_DOC] WHERE vehicle_id = ?";
            $stmtCheckV = $conn->prepare($sqlCheckVehicle);
            $stmtCheckV->execute([$vehicle_id]);
            $vehicleCount = $stmtCheckV->fetch(PDO::FETCH_ASSOC)['cnt'];
        }

        if ($driverCount == 0 && $vehicleCount == 0) {
            echo json_encode([
                'status'  => 'no_docs',
                'message' => 'You have not submitted any documents yet.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status'  => 'pending',
                'message' => 'All your documents are pending approval or already approved.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

} catch (PDOException $e) {
    error_log("Database error in checkUsersDocs.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected error while checking documents.',
        'debug'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}