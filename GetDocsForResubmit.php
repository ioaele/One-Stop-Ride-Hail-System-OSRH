<?php
require 'connect.php';

header('Content-Type: application/json');
session_start();

$users_id = $_SESSION['users_id'] ?? null;
$company_id = $users_id;

if ($company_id === null) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'User not authenticated. Please log in.'
    ]);
    exit;
}

error_log("Company ID from session: " . $company_id);

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Use your stored procedure to get vehicles for this company
    $sqlVehicles = "{CALL [eioann09].[getVehicleCompany](?)}";
    $stmtVehicles = $conn->prepare($sqlVehicles);
    $stmtVehicles->bindParam(1, $company_id, PDO::PARAM_INT);
    $stmtVehicles->execute();
    $vehicles = $stmtVehicles->fetchAll(PDO::FETCH_ASSOC);
    $stmtVehicles->closeCursor();
    
    error_log("Number of vehicles found: " . count($vehicles));
    
    if (empty($vehicles)) {
        echo json_encode([
            'status'  => 'no_issues',
            'code'   => 'NO_ISSUES',
            'message' => 'No vehicles found for this company.',
            'documents' => []
        ]);
        exit;
    }

    $allDocuments = [];
    $documentTypes = [10, 11, 12];

    // Check each vehicle
    foreach ($vehicles as $vehicle) {
        $vehicle_id = $vehicle['vehicle_id'];
        error_log("Checking vehicle_id: " . $vehicle_id);
        
        // Check each document type
        foreach ($documentTypes as $docType) {
            error_log("=== Checking doc type: $docType for vehicle: $vehicle_id ===");
            
            try {
                $sql = "{CALL [eioann09].[GetDocsForResubmitCompany](?, ?, ?)}";
                $stmt = $conn->prepare($sql);

                $stmt->bindParam(1, $company_id, PDO::PARAM_INT);
                $stmt->bindParam(2, $vehicle_id, PDO::PARAM_INT);
                $stmt->bindParam(3, $docType, PDO::PARAM_INT);

                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                error_log("Rows returned: " . count($rows));
                error_log("Raw rows data: " . json_encode($rows));

                if ($rows && count($rows) > 0) {
                    foreach ($rows as $index => $row) {
                        $statusCode = $row['StatusCode'] ?? null;
                        $v_doc_status = $row['v_doc_status'] ?? null;
                        
                        error_log("Row $index - StatusCode: " . ($statusCode ?? 'NULL') . ", v_doc_status: " . ($v_doc_status ?? 'NULL'));
                        
                        // Status codes that need resubmission:
                        // 'C' = Needs Correction/Rejected
                        // Add other status codes as needed
                        $needsResubmission = [
                            'C',           // Needs Correction
                            'c',           // lowercase version
                        ];
                        
                        if ($statusCode === 'SUCCESS') {
                            if (in_array($v_doc_status, $needsResubmission)) {
                                error_log("✓ Adding document to resubmission list: " . json_encode($row));
                                $allDocuments[] = $row;
                            } else {
                                error_log("✗ Skipping document with status: $v_doc_status (StatusCode: $statusCode)");
                            }
                        } elseif ($statusCode === 'NO_ISSUES') {
                            error_log("✗ No issues for this document type - skipping");
                        } else {
                            error_log("? Unknown StatusCode: " . ($statusCode ?? 'NULL'));
                            // If StatusCode is null but v_doc_status is 'C', still add it
                            if (in_array($v_doc_status, $needsResubmission)) {
                                error_log("✓ Adding document based on v_doc_status only: " . json_encode($row));
                                $allDocuments[] = $row;
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Error calling stored procedure for doc type $docType: " . $e->getMessage());
                continue;
            }
        }
    }

    error_log("Total documents needing resubmission: " . count($allDocuments));

    if (empty($allDocuments)) {
        echo json_encode([
            'status'     => 'no_issues',
            'code'       => 'NO_ISSUES',
            'message'    => 'No documents need correction.',
            'documents'  => []
        ]);
    } else {
        echo json_encode([
            'status'     => 'success',
            'code'       => 'SUCCESS',
            'message'    => 'Documents that need correction.',
            'documents'  => $allDocuments
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected error while fetching documents.',
        'debug'   => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected error occurred.',
        'debug'   => $e->getMessage()
    ]);
}