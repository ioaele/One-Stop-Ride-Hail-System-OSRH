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

    $driver_docs = $_POST['driver_docs'] ?? [];
    $driver_id = $_POST['driver_id'] ?? null;

    error_log("Driver docs count: " . count($driver_docs));

    if (empty($driver_docs)) {
        error_log("ERROR: No driver_docs array");
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'message' => 'No driver documents data received.',
            'debug' => [
                'post_keys' => array_keys($_POST),
                'files_keys' => array_keys($_FILES)
            ]
        ]);
        exit;
    }

   
    error_log("Processing driver picture...");
    $driver_picture_path = null;
    if (isset($_FILES['driver_picture'])) {
        $driver_picture_path = saveSingleFile('driver_picture', 'driver_pictures');
        if (!$driver_picture_path) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to upload driver picture.'
            ]);
            exit;
        }
        error_log("Driver picture saved: $driver_picture_path");
    } else {
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'message' => 'Driver picture is required.'
        ]);
        exit;
    }

    $driverDocTypes = [
        0 => 7,  // ID / Passport
        1 => 8,  // Residence Permit
        2 => 9,  // Driving License
        3 => 10, // Criminal Record
        4 => 11, // Medical
        5 => 12  // Psychological
    ];

    $requiredIndexes = [0, 2, 3, 4, 5];

    $sqlDriverDoc  = "{CALL [eioann09].[insertDriverDoc](?,?,?,?,?,?,?,?)}";
    $stmtDriverDoc = $conn->prepare($sqlDriverDoc);

    $insertedCount = 0;
    $skippedDocs = [];

    foreach ($driver_docs as $idx => $doc) {
        // Use the doc type from the form data directly
        $doc_type_id = isset($doc['d_doc_type']) ? (int)$doc['d_doc_type'] : null;
    
        if ($doc_type_id === null) {
            $skippedDocs[] = "Index $idx: no doc_type_id";
            error_log("Skipped doc at index $idx: no doc_type_id");
            continue;
        }
        
        error_log("Processing doc index $idx with doc_type_id $doc_type_id");
        $doc_code = $doc['doc_code'] ;
        $pub_date = $doc['d_doc_publish_date'] ;
        $exp_date = $doc['d_doc_ex_date'] ?? null;

        $hasFile = (
            isset($_FILES['driver_docs']['name'][$idx]['image_pdf']) &&
            $_FILES['driver_docs']['error'][$idx]['image_pdf'] !== UPLOAD_ERR_NO_FILE
        );

        $hasAnyData = $doc_code || $pub_date || $exp_date || $hasFile;

        if (!$hasAnyData) {
            $skippedDocs[] = "Index $idx: no data";
            continue;
        }

        $image_path = null;
        if ($hasFile) {
            $image_path = saveNestedFile('driver_docs', $idx, 'image_pdf', 'driver_docs');
            if (!$image_path) {
                error_log("Failed to upload file for index $idx");
            }
        }

        if (in_array($idx, $requiredIndexes, true)) {
            if (!$doc_code || !$pub_date || !$image_path) {
                $conn->rollBack();
                header('Content-Type: application/json');
                echo json_encode([
                    'status'  => 'error',
                    'message' => "Missing required fields for document index $idx."
                ]);
                exit;
            }
        }

        
        if ($driver_id === null || $driver_id === '') {
            $stmtDriverDoc->bindValue(1, null, PDO::PARAM_NULL);
        } else {
            $stmtDriverDoc->bindValue(1, (int)$driver_id, PDO::PARAM_INT);
        }

        $stmtDriverDoc->bindValue(2, (int)$users_id, PDO::PARAM_INT);
        $stmtDriverDoc->bindValue(3, $driver_picture_path, PDO::PARAM_STR);
        $stmtDriverDoc->bindValue(4, (int)$doc_type_id, PDO::PARAM_INT);
        $stmtDriverDoc->bindValue(5, $doc_code, PDO::PARAM_STR);
        $stmtDriverDoc->bindValue(6, $pub_date, PDO::PARAM_STR);
        $exp_raw = $doc['d_doc_ex_date'] ?? null;
        $exp_date = (!empty($exp_raw)) ? $exp_raw : null;
        
        // later:
        if ($exp_date === null) {
            $stmtDriverDoc->bindValue(7, null, PDO::PARAM_NULL);
        } else {
            $stmtDriverDoc->bindValue(7, $exp_date, PDO::PARAM_STR);
        }
        
        if ($image_path === null) {
            $stmtDriverDoc->bindValue(8, null, PDO::PARAM_NULL);
        } else {
            $stmtDriverDoc->bindValue(8, $image_path, PDO::PARAM_STR);
        }

        error_log("Executing SP for index $idx");
        
        if ($stmtDriverDoc->execute()) {
            $insertedCount++;
            error_log("✓ Inserted doc $idx");
        } else {
            error_log("✗ Failed to insert doc $idx");
            $errorInfo = $stmtDriverDoc->errorInfo();
            error_log("SQL Error: " . print_r($errorInfo, true));
        }
    }

    if ($insertedCount === 0) {
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'message' => 'No valid documents processed.',
            'debug' => ['skipped' => $skippedDocs]
        ]);
        exit;
    }

    $conn->commit();
    error_log("Transaction committed");

    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'success',
        'message' => 'Driver documents submitted successfully.',
        'count'   => $insertedCount
    ]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("PDO Exception: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'code'    => $e->getCode()
    ]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Exception: " . $e->getMessage());

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>