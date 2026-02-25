<?php
require 'connect.php';
header('Content-Type: application/json');

$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$users_id = isset($data->users_id) ? intval($data->users_id) : null;

if (!$users_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing users_id']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Call stored procedure to delete the pending RIDEREQUEST
    $sql = "{CALL [eioann09].[CancelRideByUser](?)}";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$users_id]);
    $stmt->closeCursor();

    echo json_encode([
        'status' => 'success',
        'message' => 'Ride request cancelled and deleted successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(200); // Return 200 so JavaScript can read error
    
    $errorInfo = $e->errorInfo;
    $errorMessage = 'Database error while cancelling request.';
    
    if (isset($errorInfo[2])) {
        $errorMessage = $errorInfo[2];
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => $errorMessage
    ]);
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
