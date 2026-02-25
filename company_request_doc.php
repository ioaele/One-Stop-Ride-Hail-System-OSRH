<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

header('Content-Type: application/json');

require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$users_id = $_SESSION['users_id'] ?? null;
error_log("Session users_id: " . ($users_id ?? 'NULL'));

if ($users_id === null) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'User/driver not authenticated.'
    ]);
    exit;
}

error_log("POST keys: " . implode(', ', array_keys($_POST)));
error_log("FILES keys: " . implode(', ', array_keys($_FILES)));

// Debug: Log what files were received
foreach ($_FILES as $key => $file) {
    error_log("File '$key': name=" . ($file['name'] ?? 'N/A') . ", error=" . ($file['error'] ?? 'N/A'));
}

function ensureUploadDir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("Failed to create directory: $dir");
            throw new Exception("Failed to create upload directory");
        }
    }
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    $vehicle_docs = $_POST['vehicle_docs'] ?? [];
    $vehicle_id = $_POST['vehicle_id'] ?? null;

    // Validate vehicle_id
    if (!$vehicle_id) {
        throw new Exception('Vehicle ID is required');
    }

    error_log("Vehicle ID: $vehicle_id");
    error_log("Vehicle docs count: " . count($vehicle_docs));

    // Check if we received files
    if (empty($_FILES)) {
        throw new Exception('No files were uploaded. Make sure form has enctype="multipart/form-data"');
    }

    $uploadDir = __DIR__ . '/uploads/vehicle_docs/';
    ensureUploadDir($uploadDir);

    // Document type mappings
    $vehicleDocTypes = [
        0 => 10, // Vehicle Registration
        1 => 11, // MOT
        2 => 12  // Vehicle Classification
    ];

    $sqlVehicleDoc = "{CALL [eioann09].[insertVehicleDoc](?,?,?,?,?,?)}";
    $stmtVehicleDoc = $conn->prepare($sqlVehicleDoc);

    $processedCount = 0;

    foreach ($vehicle_docs as $idx => $doc) {
        error_log("=== Processing vehicle doc index: $idx ===");
        
        $v_doc_type_id = $vehicleDocTypes[$idx] ?? null;
        if ($v_doc_type_id === null) {
            error_log("Skipping index $idx: no doc type mapping");
            continue;
        }

        $doc_code = $doc['doc_code'] ?? null;
        $pub_date = $doc['v_doc_publish_date'] ?? null;
        $exp_date = $doc['v_doc_exp_date'] ?? null;

        error_log("Doc data: code=$doc_code, pub=$pub_date, exp=$exp_date, type=$v_doc_type_id");

        // Validate required fields
        if (!$doc_code || !$pub_date) {
            throw new Exception("Document code and issue date are required for document " . ($idx + 1));
        }

        // Handle empty expiration date
        if ($exp_date === '' || $exp_date === null) {
            $exp_date = null;
        }

        // Handle file upload
        $filePath = null;
        $fileInputName = "vehicle_docs_{$idx}_file";
        
        error_log("Looking for file: $fileInputName");
        error_log("Available files: " . implode(', ', array_keys($_FILES)));

        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileInputName];
            
            error_log("File found: name={$file['name']}, type={$file['type']}, size={$file['size']}");
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if ($file['size'] > $maxSize) {
                throw new Exception("File {$file['name']} exceeds 5MB limit");
            }
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception("Invalid file type for {$file['name']}. Allowed: JPG, PNG, GIF, PDF");
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uniqueName = uniqid('vdoc_' . $vehicle_id . '_' . $v_doc_type_id . '_', true) . '.' . $extension;
            $targetPath = $uploadDir . $uniqueName;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                error_log("Failed to move file from {$file['tmp_name']} to $targetPath");
                throw new Exception("Failed to upload file {$file['name']}");
            }
            
            error_log("File uploaded successfully: $targetPath");
            
            // Store relative path for database
            $filePath = 'uploads/vehicle_docs/' . $uniqueName;
        } else {
            // File is required but missing
            $errorCode = $_FILES[$fileInputName]['error'] ?? 'NOT SET';
            
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];
            
            $errorMsg = $errorMessages[$errorCode] ?? "Unknown error: $errorCode";
            error_log("File error for $fileInputName: $errorMsg");
            
            throw new Exception("File upload required for document " . ($idx + 1) . " ($errorMsg)");
        }

        // Insert document record
        error_log("Inserting document: vehicle_id=$vehicle_id, code=$doc_code, type=$v_doc_type_id, pub=$pub_date, exp=" . ($exp_date ?? 'NULL') . ", file=$filePath");
        
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
        $processedCount++;
        
        error_log("Document $idx inserted successfully");
    }

    if ($processedCount === 0) {
        throw new Exception('No documents were processed');
    }

    $conn->commit();
    error_log("Transaction committed. Processed $processedCount documents.");

    echo json_encode([
        'status'  => 'success',
        'message' => "Vehicle documents uploaded successfully. ($processedCount documents)"
    ]);
    exit;

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }

    error_log("PDOException: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error while processing vehicle documents.',
        'debug'   => $e->getMessage()
    ]);
    exit;
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }

    error_log("Exception: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}