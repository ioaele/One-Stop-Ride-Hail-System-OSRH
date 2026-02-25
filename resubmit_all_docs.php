<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$users_id = $_SESSION['users_id'] ?? null;
error_log("=== START resubmit_all_docs.php ===");
error_log("Session users_id: " . ($users_id ?? 'NULL'));
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r(array_keys($_FILES), true));

if ($users_id === null) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'User not authenticated.'
    ]);
    exit;
}

function ensureUploadDir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("Failed to create directory: $dir");
            throw new Exception("Failed to create upload directory");
        }
    }
}

function processFileUpload(string $fileInputName, string $subfolder, int $id, int $typeId): string {
    error_log("Looking for file: $fileInputName");
    
    if (!isset($_FILES[$fileInputName])) {
        error_log("File '$fileInputName' not found in \$_FILES");
        error_log("Available files: " . print_r(array_keys($_FILES), true));
        throw new Exception("File upload required for $fileInputName (not found in upload)");
    }
    
    if ($_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES[$fileInputName]['error'];
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        $errorMsg = $errorMessages[$errorCode] ?? "Unknown error code: $errorCode";
        error_log("File upload error for $fileInputName: $errorMsg");
        throw new Exception("File upload error: $errorMsg");
    }

    $file = $_FILES[$fileInputName];
    error_log("File details: name={$file['name']}, type={$file['type']}, size={$file['size']}");
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['size'] > $maxSize) {
        throw new Exception("File {$file['name']} exceeds 5MB limit");
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        error_log("Invalid file type: {$file['type']}");
        throw new Exception("Invalid file type for {$file['name']}. Allowed: PDF, JPG, PNG, GIF");
    }
    
    $uploadDir = __DIR__ . "/uploads/$subfolder/";
    ensureUploadDir($uploadDir);
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid($subfolder . '_' . $id . '_' . $typeId . '_', true) . '.' . $extension;
    $targetPath = $uploadDir . $uniqueName;
    
    error_log("Moving file to: $targetPath");
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        error_log("Failed to move file from {$file['tmp_name']} to $targetPath");
        throw new Exception("Failed to upload file {$file['name']}");
    }
    
    error_log("File uploaded successfully: $targetPath");
    return "uploads/$subfolder/" . $uniqueName;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    error_log("Database connection established");

    $conn->beginTransaction();
    error_log("Transaction started");

    $driver_docs = $_POST['driver_docs'] ?? [];
    $vehicle_docs = $_POST['vehicle_docs'] ?? [];
    $vehicle_id = $_POST['vehicle_id'] ?? null;

    error_log("Driver docs count: " . count($driver_docs));
    error_log("Vehicle docs count: " . count($vehicle_docs));
    error_log("Vehicle ID: " . ($vehicle_id ?? 'NULL'));

    if (empty($driver_docs) && empty($vehicle_docs)) {
        throw new Exception('No documents to process');
    }

    $processedCount = 0;

    // ===================================================================
    // PROCESS DRIVER DOCUMENTS
    // ===================================================================
    if (!empty($driver_docs)) {
        error_log("=== PROCESSING DRIVER DOCUMENTS ===");
        $sqlDriverDoc = "{CALL [eioann09].[UpdateDrDoc](?,?,?,?,?,?)}";
        $stmtDriverDoc = $conn->prepare($sqlDriverDoc);

        foreach ($driver_docs as $idx => $doc) {
            error_log("--- Processing driver doc index: $idx ---");
            error_log("Driver doc data: " . print_r($doc, true));
            
            $d_doc_type_id = $doc['doc_type_id'] ?? null;
            
            if ($d_doc_type_id === null || $d_doc_type_id === '') {
                error_log("Skipping driver doc $idx: missing doc_type_id");
                continue;
            }

            $doc_code = $doc['doc_code'] ?? null;
            $pub_date = $doc['publish_date'] ?? null;
            $exp_date = $doc['exp_date'] ?? null;

            error_log("Fields: doc_type=$d_doc_type_id, code=$doc_code, pub=$pub_date, exp=$exp_date");

            if ($doc_code === null || $doc_code === '' || $pub_date === null || $pub_date === '') {
                error_log("Skipping driver doc $idx: missing required fields");
                continue;
            }

            // Validate dates
            try {
                $publishDate = new DateTime($pub_date);
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                
                if ($publishDate > $today) {
                    throw new Exception("Issue date cannot be in the future for driver document type $d_doc_type_id");
                }

                if (!empty($exp_date) && $exp_date !== '') {
                    $expDate = new DateTime($exp_date);
                    if ($expDate <= $publishDate) {
                        throw new Exception("Expiration date must be after issue date for driver document type $d_doc_type_id");
                    }
                } else {
                    $exp_date = null;
                }
            } catch (Exception $e) {
                error_log("Date validation error: " . $e->getMessage());
                throw $e;
            }

            // Process file upload
            $fileInputName = "driver_docs_{$idx}_file";
            error_log("Processing file upload: $fileInputName");
            
            try {
                $filePath = processFileUpload($fileInputName, 'driver_docs', $users_id, $d_doc_type_id);
                error_log("File uploaded: $filePath");
            } catch (Exception $e) {
                error_log("File upload error: " . $e->getMessage());
                throw $e;
            }

            // Call stored procedure
            error_log("Calling UpdateDrDoc with: users_id=$users_id, code=$doc_code, type=$d_doc_type_id, pub=$pub_date, exp=" . ($exp_date ?? 'NULL') . ", file=$filePath");
            
            try {
                $stmtDriverDoc->bindValue(1, $users_id, PDO::PARAM_INT);
                $stmtDriverDoc->bindValue(2, $doc_code, PDO::PARAM_STR);
                $stmtDriverDoc->bindValue(3, $d_doc_type_id, PDO::PARAM_INT);
                $stmtDriverDoc->bindValue(4, $pub_date, PDO::PARAM_STR);
                
                if ($exp_date === null) {
                    $stmtDriverDoc->bindValue(5, null, PDO::PARAM_NULL);
                } else {
                    $stmtDriverDoc->bindValue(5, $exp_date, PDO::PARAM_STR);
                }
                
                $stmtDriverDoc->bindValue(6, $filePath, PDO::PARAM_STR);

                $stmtDriverDoc->execute();
                $processedCount++;
                
                error_log("Successfully processed driver doc type $d_doc_type_id");
            } catch (PDOException $e) {
                error_log("Stored procedure error: " . $e->getMessage());
                throw new Exception("Database error updating driver document type $d_doc_type_id: " . $e->getMessage());
            }
        }
    }

    // ===================================================================
    // PROCESS VEHICLE DOCUMENTS
    // ===================================================================
    if (!empty($vehicle_docs)) {
        error_log("=== PROCESSING VEHICLE DOCUMENTS ===");
        
        if ($vehicle_id === null || $vehicle_id === '') {
            throw new Exception('Vehicle ID is required for vehicle documents');
        }

        $sqlVehicleDoc = "{CALL [eioann09].[UpdateVehDoc](?,?,?,?,?,?)}";
        $stmtVehicleDoc = $conn->prepare($sqlVehicleDoc);

        foreach ($vehicle_docs as $idx => $doc) {
            error_log("--- Processing vehicle doc index: $idx ---");
            error_log("Vehicle doc data: " . print_r($doc, true));
            
            $v_doc_type_id = $doc['doc_type_id'] ?? null;
            
            if ($v_doc_type_id === null || $v_doc_type_id === '') {
                error_log("Skipping vehicle doc $idx: missing doc_type_id");
                continue;
            }

            $doc_code = $doc['doc_code'] ?? null;
            $pub_date = $doc['publish_date'] ?? null;
            $exp_date = $doc['exp_date'] ?? null;

            error_log("Fields: doc_type=$v_doc_type_id, code=$doc_code, pub=$pub_date, exp=$exp_date");

            if ($doc_code === null || $doc_code === '' || $pub_date === null || $pub_date === '') {
                error_log("Skipping vehicle doc $idx: missing required fields");
                continue;
            }

            // Validate dates
            try {
                $publishDate = new DateTime($pub_date);
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                
                if ($publishDate > $today) {
                    throw new Exception("Issue date cannot be in the future for vehicle document type $v_doc_type_id");
                }

                if (!empty($exp_date) && $exp_date !== '') {
                    $expDate = new DateTime($exp_date);
                    if ($expDate <= $publishDate) {
                        throw new Exception("Expiration date must be after issue date for vehicle document type $v_doc_type_id");
                    }
                } else {
                    $exp_date = null;
                }
            } catch (Exception $e) {
                error_log("Date validation error: " . $e->getMessage());
                throw $e;
            }

            // Process file upload
            $fileInputName = "vehicle_docs_{$idx}_file";
            error_log("Processing file upload: $fileInputName");
            
            try {
                $filePath = processFileUpload($fileInputName, 'vehicle_docs', $vehicle_id, $v_doc_type_id);
                error_log("File uploaded: $filePath");
            } catch (Exception $e) {
                error_log("File upload error: " . $e->getMessage());
                throw $e;
            }

            // Call stored procedure
            error_log("Calling UpdateVehDoc with: vehicle_id=$vehicle_id, code=$doc_code, type=$v_doc_type_id, pub=$pub_date, exp=" . ($exp_date ?? 'NULL') . ", file=$filePath");
            
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
                $processedCount++;
                
                error_log("Successfully processed vehicle doc type $v_doc_type_id");
            } catch (PDOException $e) {
                error_log("Stored procedure error: " . $e->getMessage());
                throw new Exception("Database error updating vehicle document type $v_doc_type_id: " . $e->getMessage());
            }
        }
    }

    if ($processedCount === 0) {
        throw new Exception('No documents were processed. Please check your form data.');
    }

    $conn->commit();
    error_log("Transaction committed. Processed $processedCount documents.");

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

    error_log("PDOException: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error while processing documents.',
        'debug'   => $e->getMessage()
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
        'message' => $e->getMessage()
    ]);
    exit;
}