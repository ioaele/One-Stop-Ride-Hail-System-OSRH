<?php
// Set headers for JSON response and allow cross-origin requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// This line includes the file with your Database connection class
require 'connect.php'; 

// Initialize variables for the connection and the database handler
$conn = null;
$db = null;

try {
    // 1. Create a database object and get the connection
    $db = new Database();
    $conn = $db->getConnection();

    if ($conn === null) {
        // If getConnection fails, it means there's a problem with drivers, credentials, or network.
        throw new Exception("Database connection could not be established.");
    }
    
    $results = array();
    
    // 2. Prepare and execute the stored procedure to get all safety characteristics
    // This assumes the stored procedure now returns a result set (not just printing)
    $dataStmt = $conn->prepare("EXEC getAllSafetyChar");
    $dataStmt->execute();
    
    // 3. Loop through the results and build a clean array
    while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
        // Expected columns from the stored procedure: 'check_date', 'comments', and 'official'
        $results[] = array(
            'date' => $row['check_date'],
            'comments' => $row['comments'], 
            'official' => $row['official']
        );
    }
    
    // 4. Return the data in a successful JSON response
    echo json_encode(array(
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ));
    
} catch(Exception $e) {
    // Log any errors that occur (e.g., connection failure, query error)
    error_log("API Error - Database operation failed: " . $e->getMessage());

    // 5. If an error occurs, send back a 500 status and a failure message
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => "Internal Server Error: Data loading failed."
    ));
} finally {
    // 6. Always close the connection when finished
    if ($db !== null) {
        $db->closeConnection();
    }
}
?>