<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['users_id'])) {
    $_SESSION['users_id'] = 10056;
}

$users_id = $_SESSION['users_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Not POST']);
    exit;
}

$driver_docs = $_POST['driver_docs'] ?? [];
if (empty($driver_docs)) {
    echo json_encode(['status' => 'error', 'message' => 'No driver_docs']);
    exit;
}

try {
    require_once 'connect.php';
    $db = new Database();
    $conn = $db->getConnection();

    // Step 5: Check first document data
    $firstDoc = $driver_docs[0];
    $d_doc_type_id = $firstDoc['doc_type_id'] ?? null;
    $doc_code = $firstDoc['doc_code'] ?? null;
    $pub_date = $firstDoc['publish_date'] ?? null;
    $exp_date = $firstDoc['exp_date'] ?? null;

    if (!$d_doc_type_id || !$doc_code || !$pub_date) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Missing required fields',
            'step' => 5,
            'data' => $firstDoc
        ]);
        exit;
    }

    // Step 6: Check file
    $fileInputName = "driver_docs_0_file";
    if (!isset($_FILES[$fileInputName])) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'File not found',
            'step' => 6,
            'expected' => $fileInputName,
            'available' => array_keys($_FILES)
        ]);
        exit;
    }

    if ($_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'File upload error',
            'step' => 6,
            'error_code' => $_FILES[$fileInputName]['error']
        ]);
        exit;
    }

    // Step 7: Try to create upload directory
    $uploadDir = __DIR__ . "/uploads/driver_docs/";
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Cannot create upload directory',
                'step' => 7
            ]);
            exit;
        }
    }

    // Step 8: Try to move file
    $file = $_FILES[$fileInputName];
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid('driver_docs_' . $users_id . '_' . $d_doc_type_id . '_', true) . '.' . $extension;
    $targetPath = $uploadDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Cannot move uploaded file',
            'step' => 8
        ]);
        exit;
    }

    $filePath = "uploads/driver_docs/" . $uniqueName;

    // Step 9: Try stored procedure
    $sql = "{CALL [eioann09].[UpdateDrDoc](?,?,?,?,?,?)}";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Cannot prepare statement',
            'step' => 9
        ]);
        exit;
    }

    // Step 10: Bind and execute
    $stmt->bindValue(1, $users_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $doc_code, PDO::PARAM_STR);
    $stmt->bindValue(3, $d_doc_type_id, PDO::PARAM_INT);
    $stmt->bindValue(4, $pub_date, PDO::PARAM_STR);
    
    if ($exp_date === null || $exp_date === '') {
        $stmt->bindValue(5, null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(5, $exp_date, PDO::PARAM_STR);
    }
    
    $stmt->bindValue(6, $filePath, PDO::PARAM_STR);

    $stmt->execute();

    // Step 11: Success!
    echo json_encode([
        'status' => 'success',
        'message' => 'Document resubmitted successfully!',
        'step' => 11
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'step' => 'PDO Exception'
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage(),
        'step' => 'Exception'
    ]);
    exit;
}