<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

require_once 'connect.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Call stored procedure to get all pending documents
    $sql = "{CALL [eioann09].[GetAllPendingDocuments]()}";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    error_log("Found " . count($documents) . " pending documents");

    echo json_encode([
        'status' => 'success',
        'documents' => $documents,
        'count' => count($documents)
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error loading pending documents.',
        'debug' => $e->getMessage()
    ]);
}