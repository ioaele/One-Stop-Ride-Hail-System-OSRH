<?php
require 'connect.php';

header('Content-Type: application/json');
session_start();

$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    $data = new stdClass();
}

$users_id = $data->users_id ?? ($_SESSION['users_id'] ?? null);

if ($users_id === null || $users_id === '') {
    echo json_encode([
        'status'  => 'no_docs',
        'message' => 'We could not find any driver information for your account.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $sql = "{CALL [eioann09].[CheckRentDriverDocsStatus](?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $users_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (empty($rows)) {
        echo json_encode([
            'status'  => 'no_docs',
            'message' => 'No driver documents found.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $statusCode = $rows[0]['StatusCode'] ?? null;

    switch ($statusCode) {
        case 'SUCCESS':
   
            echo json_encode([
                'status'     => 'has_issues',
                'message'    => count($rows) . ' driver document(s) need correction.',
                'driverDocs' => $rows,
                'users_id'   => $users_id
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'APPROVED':
            echo json_encode([
                'status'  => 'approved',
                'message' => $rows[0]['MessageText'] ?? 'All your documents have been accepted.'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'PENDING':
            echo json_encode([
                'status'  => 'pending',
                'message' => $rows[0]['MessageText'] ?? 'All documents are pending approval.'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'NO_DOCS':
        default:
            echo json_encode([
                'status'  => 'no_docs',
                'message' => $rows[0]['MessageText'] ?? 'No driver documents found.'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (PDOException $e) {
    error_log("Database error in checkRentDocs.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected error while checking documents.',
        'debug'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}