<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

require_once 'connect.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$users_id = $_SESSION['users_id'] ?? null;
error_log("Session users_id: " . ($users_id ?? 'NULL'));

if ($users_id === null) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'User/driver not authenticated.'
    ]);
    exit;
}

error_log("POST keys: " . implode(', ', array_keys($_POST)));
error_log("FILES keys: " . implode(', ', array_keys($_FILES)));


function ensureUploadDir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("Failed to create directory: $dir");
            throw new Exception("Failed to create upload directory");
        }
    }
}

function saveNestedFile(string $field, int $index, string $key, string $subFolder): ?string {
    error_log("saveNestedFile: field=$field, index=$index, key=$key, subfolder=$subFolder");
    
    if (
        !isset($_FILES[$field]['name'][$index][$key]) ||
        $_FILES[$field]['error'][$index][$key] !== UPLOAD_ERR_OK
    ) {
        $errorCode = $_FILES[$field]['error'][$index][$key] ?? 'not set';
        error_log("File error for $field[$index][$key]: $errorCode");
        return null;
    }

    $uploadBase = __DIR__ . '/uploads';
    ensureUploadDir($uploadBase);

    $targetDir = $uploadBase . '/' . $subFolder;
    ensureUploadDir($targetDir);

    $originalName = basename($_FILES[$field]['name'][$index][$key]);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $newName = uniqid($field . '_' . $index . '_', true) . ($ext ? '.' . $ext : '');
    $targetPath = $targetDir . '/' . $newName;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'][$index][$key], $targetPath)) {
        error_log("Failed to move file to: $targetPath");
        return null;
    }

    error_log("File saved: $targetPath");
    return 'uploads/' . $subFolder . '/' . $newName;
}

function saveSingleFile(string $fieldName, string $subFolder): ?string {
    error_log("saveSingleFile: fieldName=$fieldName, subfolder=$subFolder");
    
    if (
        !isset($_FILES[$fieldName]) ||
        $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK
    ) {
        $errorCode = $_FILES[$fieldName]['error'] ?? 'not set';
        error_log("File error for $fieldName: $errorCode");
        return null;
    }

    $uploadBase = __DIR__ . '/uploads';
    ensureUploadDir($uploadBase);

    $targetDir = $uploadBase . '/' . $subFolder;
    ensureUploadDir($targetDir);

    $originalName = basename($_FILES[$fieldName]['name']);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $newName = uniqid($fieldName . '_', true) . ($ext ? '.' . $ext : '');
    $targetPath = $targetDir . '/' . $newName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        error_log("Failed to move file to: $targetPath");
        return null;
    }

    error_log("File saved: $targetPath");
    return 'uploads/' . $subFolder . '/' . $newName;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    $vehicle_docs = $_POST['vehicle_docs'] ?? [];

    error_log('FULL POST: ' . print_r($_POST, true));

    $vehicle_id = $_POST['vehicle_id'] ?? null;
    error_log('vehicle_id from POST (raw): ' . var_export($vehicle_id, true));
    
    $uploadDir = __DIR__ . '/uploads/vehicle_docs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

 
    $vehicleDocTypes = [
        0 => 10, // Vehicle Registration
        1 => 11, // MOT
        2 => 12  // Vehicle Classification
    ];

    $sqlVehicleDoc  = "{CALL [eioann09].[insertVehicleDoc](?,?,?,?,?,?)}";
    $stmtVehicleDoc = $conn->prepare($sqlVehicleDoc);

    foreach ($vehicle_docs as $idx => $doc) {
        $v_doc_type_id = $vehicleDocTypes[$idx] ?? null;
        if ($v_doc_type_id === null) {
            continue;
        }
        $doc_code = $doc['doc_code'] ;
        $pub_date = $doc['v_doc_publish_date'] ?? null;
        $exp_date = $doc['v_doc_exp_date'] ?? null;
       
if ($exp_date === '' || $exp_date === null) {
    $exp_date = null;
}

 // Handle file upload
        $filePath = null;
        $fileInputName = "vehicle_docs_{$idx}_file"; // Match the frontend field name
        
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileInputName];
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if ($file['size'] > $maxSize) {
                throw new Exception("File {$file['name']} exceeds 5MB limit");
            }
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception("Invalid file type for {$file['name']}");
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uniqueName = uniqid('vdoc_' . $vehicle_id . '_' . $v_doc_type_id . '_', true) . '.' . $extension;
            $targetPath = $uploadDir . $uniqueName;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Failed to upload file {$file['name']}");
            }
            
            // Store relative path for database
            $filePath = 'uploads/vehicle_docs/' . $uniqueName;
        }

        // Insert document record with file path
        $stmtVehicleDoc->bindValue(1, $vehicle_id, PDO::PARAM_INT);
        $stmtVehicleDoc->bindValue(2, $doc_code, PDO::PARAM_STR);
 
       
        $stmtVehicleDoc->bindValue(3, $v_doc_type_id,  PDO::PARAM_INT);
        $stmtVehicleDoc->bindValue(4, $pub_date,       PDO::PARAM_STR);
               
if ($exp_date === null) {
    $stmtVehicleDoc->bindValue(5, null, PDO::PARAM_NULL);
} else {
    $stmtVehicleDoc->bindValue(5, $exp_date, PDO::PARAM_STR); // or PARAM_STR for 'YYYY-MM-DD'
}
     
        $stmtVehicleDoc->bindValue(6, $filePath,       PDO::PARAM_STR); // Store path, not base64

        $stmtVehicleDoc->execute();
    }

    $conn->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Vehicle documents uploaded successfully.'
    ]);
    exit;

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

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
    }

    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}