<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

require_once 'connect.php';

// Set JSON header at the very beginning
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$users_id = $_SESSION['users_id'] ?? null;
error_log("=== START company_resubmit_doc.php ===");
error_log("Session users_id: " . ($users_id ?? 'NULL'));

if ($users_id === null) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'User/driver not authenticated.'
    ]);
    exit;
}

error_log("POST data: " . json_encode($_POST));
error_log("FILES data: " . json_encode(array_keys($_FILES)));

function ensureUploadDir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("Failed to create directory: $dir");
            throw new Exception("Failed to create upload directory");
        }
    }
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    error_log("Database connection established");

    $conn->beginTransaction();
    error_log("Transaction started");

    $vehicle_docs = $_POST['vehicle_docs'] ?? [];
    $vehicle_id = $_POST['vehicle_id'] ?? null;

    error_log("Vehicle ID: " . ($vehicle_id ?? 'NULL'));
    error_log("Number of documents: " . count($vehicle_docs));

    if ($vehicle_id === null || $vehicle_id === '') {
        throw new Exception('Vehicle ID is required');
    }

    if (empty($vehicle_docs)) {
        throw new Exception('No documents to process');
    }

    $uploadDir = __DIR__ . '/uploads/vehicle_docs/';
    error_log("Upload directory: $uploadDir");
    ensureUploadDir($uploadDir);

    $sqlVehicleDoc  = "{CALL [eioann09].[UpdateVehDoc](?,?,?,?,?,?)}";
    $stmtVehicleDoc = $conn->prepare($sqlVehicleDoc);

    $processedCount = 0;

    foreach ($vehicle_docs as $idx => $doc) {
        error_log("--- Processing document index: $idx ---");
        error_log("Document data: " . json_encode($doc));
        
        $v_doc_type_id = $doc['v_doc_type_id'] ?? null;
        
        if ($v_doc_type_id === null || $v_doc_type_id === '') {
            error_log("Skipping document $idx: missing v_doc_type_id");
            continue;
        }

        $doc_code = $doc['doc_code'] ?? null;
        $pub_date = $doc['v_doc_publish_date'] ?? null;
        $exp_date = $doc['v_doc_exp_date'] ?? null;

        error_log("Doc type: $v_doc_type_id, Code: $doc_code, Pub: $pub_date, Exp: $exp_date");

        if ($doc_code === null || $doc_code === '' || $pub_date === null || $pub_date === '') {
            error_log("Skipping document $idx: missing required fields");
            continue;
        }

        // Validate publish date is not in the future
        try {
            $publishDate = new DateTime($pub_date);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            
            if ($publishDate > $today) {
                throw new Exception("Issue date cannot be in the future for document type $v_doc_type_id");
            }
        } catch (Exception $e) {
            error_log("Date validation error: " . $e->getMessage());
            throw $e;
        }

        // Validate expiration date if provided
        if (!empty($exp_date) && $exp_date !== '') {
            try {
                $expDate = new DateTime($exp_date);
                
                if ($expDate <= $publishDate) {
                    throw new Exception("Expiration date must be after issue date for document type $v_doc_type_id");
                }
            } catch (Exception $e) {
                error_log("Expiration date validation error: " . $e->getMessage());
                throw $e;
            }
        } else {
            $exp_date = null;
        }
       
        // Handle file upload
        $filePath = null;
        $fileInputName = "vehicle_docs_{$idx}_file";
        
        error_log("Looking for file: $fileInputName");
        
        if (!isset($_FILES[$fileInputName])) {
            error_log("File not found in \$_FILES");
            error_log("Available files: " . implode(', ', array_keys($_FILES)));
            throw new Exception("File upload required for document type $v_doc_type_id (file input not found)");
        }
        
        if ($_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $_FILES[$fileInputName]['error'];
            error_log("File upload error code: $errorCode");
            throw new Exception("File upload error for document type $v_doc_type_id (Error code: $errorCode)");
        }

        $file = $_FILES[$fileInputName];
        error_log("File details: " . json_encode([
            'name' => $file['name'],
            'type' => $file['type'],
            'size' => $file['size'],
            'tmp_name' => $file['tmp_name']
        ]));
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File {$file['name']} exceeds 5MB limit");
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            error_log("Invalid file type: " . $file['type']);
            throw new Exception("Invalid file type for {$file['name']}. Allowed: PDF, JPG, PNG, GIF");
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid('vdoc_' . $vehicle_id . '_' . $v_doc_type_id . '_', true) . '.' . $extension;
        $targetPath = $uploadDir . $uniqueName;
        
        error_log("Moving file to: $targetPath");
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            error_log("Failed to move file from " . $file['tmp_name'] . " to $targetPath");
            throw new Exception("Failed to upload file {$file['name']}");
        }
        
        // Store relative path for database
        $filePath = 'uploads/vehicle_docs/' . $uniqueName;
        error_log("File saved successfully: $filePath");

        // Call stored procedure
        error_log("Calling stored procedure with params: vehicle_id=$vehicle_id, doc_code=$doc_code, doc_type=$v_doc_type_id, pub_date=$pub_date, exp_date=" . ($exp_date ?? 'NULL') . ", file=$filePath");
        
        try {
            $stmtVehicleDoc->bindValue(1, $vehicle_id, PDO::PARAM_INT);
            $stmtVehicleDoc->bindValue(2, $doc_code, PDO::PARAM_STR);
            $stmtVehicleDoc->bindValue(3, $v_doc_type_id, PDO::PARAM_INT);
            $stmtVehicleDoc->bindValue(4, $pub_date, PDO::PARAM_STR);
            
            if ($exp_date === null) {
                $stmtVehicleDoc->bindValue(5, null, PDO::PARAM_NULL);
            } else {
                $stmtVehicleDoc->bindValue(5, $exp_date, PDO::PARAM_STR);
            }
            
            $stmtVehicleDoc->bindValue(6, $filePath, PDO::PARAM_STR);

            $stmtVehicleDoc->execute();
            error_log("Stored procedure executed successfully");
            
            $processedCount++;
        } catch (PDOException $e) {
            error_log("Stored procedure error: " . $e->getMessage());
            throw new Exception("Database error while updating document type $v_doc_type_id: " . $e->getMessage());
        }
    }

    $conn->commit();
    error_log("Transaction committed successfully. Processed $processedCount documents.");

    echo json_encode([
        'status'  => 'success',
        'message' => "Successfully resubmitted $processedCount document(s). Status changed to Pending for review."
    ]);
    exit;

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }

    error_log("PDO Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error while processing vehicle documents.',
        'debug'   => $e->getMessage(),
        'trace'   => $e->getTraceAsString()
    ]);
    exit;
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }

    error_log("Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'trace'   => $e->getTraceAsString()
    ]);
    exit;
}