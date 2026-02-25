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

// Get JSON input
$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$doc_category = $data->doc_category ?? null;
$doc_id = $data->doc_id ?? null;
$rejection_reason = $data->rejection_reason ?? null;

if (!$doc_category || !$doc_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

// Validate category
if (!in_array($doc_category, ['DRIVER', 'VEHICLE'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid document category']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    
    $users_id = $_SESSION['users_id'] ?? null;

    // Call stored procedure to disapprove document
    $sql = "{CALL [eioann09].[DisapproveDocument](?, ?, ?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $doc_category, PDO::PARAM_STR);
    $stmt->bindParam(2, $doc_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $rejection_reason, PDO::PARAM_STR);
    $stmt->bindParam(4, $users_id, PDO::PARAM_INT);
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    error_log("Disapprove result: " . json_encode($result));

    if ($result && $result['StatusCode'] === 'SUCCESS') {
        echo json_encode([
            'status' => 'success',
            'message' => $result['MessageText']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $result['MessageText'] ?? 'Failed to disapprove document'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error disapproving document.',
        'debug' => $e->getMessage()
    ]);
}