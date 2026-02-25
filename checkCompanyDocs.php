<?php
require 'connect.php';

header('Content-Type: application/json');
session_start();

// Get JSON from request
$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    $data = new stdClass();
}

// company_id from JSON or from session
$users_id = $data->users_id ?? ($_SESSION['users_id'] ?? null);
$company_id = $users_id;

// 1) If we don't have company_id → assume no company request yet
if ($company_id === null || $company_id === '') {
    echo json_encode([
        'status'     => 'no_docs',
        'company_id' => null,
        'message'    => 'You have not submitted any company vehicle documents yet.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    
    $sql = "{CALL [eioann09].[CheckVehicleCOMPANYDocsStatus](?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $vehicleRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();


    if (empty($vehicleRows)) {
        echo json_encode([
            'status'     => 'no_docs',
            'company_id' => (int)$company_id,
            'message'    => 'You have not submitted any company vehicle documents yet.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

  
    $statusCode = $vehicleRows[0]['StatusCode'] ?? 'OTHER';

    switch ($statusCode) {
        case 'NO_DOCS':
          
            echo json_encode([
                'status'     => 'no_docs',
                'company_id' => (int)$company_id,
                'message'    => $vehicleRows[0]['MessageText'] ?? 'You have not submitted any company vehicle documents yet.'
            ], JSON_UNESCAPED_UNICODE);
            break;

            case 'CAN_SUMBIT_AGAIN':
          
                echo json_encode([
                    'status'     => 'no_docs',
                    'company_id' => (int)$company_id,
                    'message'    => $vehicleRows[0]['MessageText'] ?? 'You have not submitted any company vehicle documents yet.'
                ], JSON_UNESCAPED_UNICODE);
                break;

        case 'ALL_P':

            echo json_encode([
                'status'      => 'all_pending',  
                'company_id'  => (int)$company_id,
                'message'     => $vehicleRows[0]['MessageText'] ?? 'You have made your request. Wait for approval...',
                'vehicleDocs' => $vehicleRows
            ], JSON_UNESCAPED_UNICODE);
            break;
        

        case 'HAS_ISSUES':
            
            $messages = [];
            foreach ($vehicleRows as $row) {
                if (!empty($row['MessageText'])) {
                    $messages[] = $row['MessageText'];
                }
            }
            
            $combinedMessage = empty($messages) 
                ? 'Some documents need to be corrected.' 
                : implode("\n", $messages);

            echo json_encode([
                'status'      => 'has_issues',
                'company_id'  => (int)$company_id,
                'message'     => $combinedMessage,
                'vehicleDocs' => $vehicleRows
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            // OTHER or unknown status
            $messages = [];
            foreach ($vehicleRows as $row) {
                if (!empty($row['MessageText'])) {
                    $messages[] = $row['MessageText'];
                }
            }
            
            $combinedMessage = empty($messages) 
                ? 'There is some status information for your company vehicle documents.' 
                : implode("\n", $messages);

            echo json_encode([
                'status'      => 'other',
                'company_id'  => (int)$company_id,
                'message'     => $combinedMessage,
                'vehicleDocs' => $vehicleRows
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected error while checking company vehicle documents. Please try again later.',
        'debug'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}