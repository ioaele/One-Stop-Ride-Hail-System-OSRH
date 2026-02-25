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

$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$doc_category = $data->doc_category ?? null;
$doc_id = $data->doc_id ?? null;

if (!$doc_category || !$doc_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

if (!in_array($doc_category, ['DRIVER', 'VEHICLE'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid document category']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // ✅ Fixed: Added missing closing parenthesis
    $sql = "{CALL [eioann09].[ApproveDocument](?, ?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $doc_category, PDO::PARAM_STR);
    $stmt->bindParam(2, $doc_id, PDO::PARAM_INT);

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    error_log("Approve result: " . json_encode($result));

    // ✅ Fixed: Check for all success status codes
    $successCodes = ['SUCCESS', 'APPROVED', 'APPROVED_RENT'];
    
    if ($result && in_array($result['StatusCode'], $successCodes)) {
        $response = [
            'status' => 'success',
            'statusCode' => $result['StatusCode'],  // Include the specific status
            'message' => $result['MessageText']
        ];

        if (isset($result['driver_id']) && $result['driver_id'] !== null) {
            $response['driver_created'] = true;
            $response['driver_id'] = $result['driver_id'];
            $response['users_id'] = $result['users_id'];
        }

        if (isset($result['all_documents_approved']) && $result['all_documents_approved'] == 1) {
            $response['all_documents_approved'] = true;
        }

        echo json_encode($response);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $result['MessageText'] ?? 'Failed to approve document'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error approving document.',
        'debug' => $e->getMessage()
    ]);
}   