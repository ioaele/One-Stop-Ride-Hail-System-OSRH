<?php
// Include database connection
require_once 'connect.php';

// Set CORS headers
setCorsHeaders();

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = array();

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. Only POST is allowed.');
    }

    // Get JSON input
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);

    // Validate input data
    if (!$data) {
        throw new Exception('Invalid JSON data received.');
    }

    // Validate required fields
    if (empty($data['license_plate']) || empty($data['comments']) || empty($data['official'])) {
        throw new Exception('All fields are required: license_plate, comments, and official.');
    }

    // Sanitize inputs
    $license_plate = trim($data['license_plate']);
    $comments = trim($data['comments']);
    $official = trim($data['official']);

    // Additional validation
    if (strlen($license_plate) > 30) {
        throw new Exception('License plate cannot exceed 30 characters.');
    }

    if (strlen($official) > 50) {
        throw new Exception('Official name cannot exceed 50 characters.');
    }

    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Database connection failed.');
    }

    // First, check if the vehicle exists
    $check_query = "SELECT vehicle_id FROM VEHICLE WHERE license_plate = :license_plate";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':license_plate', $license_plate);
    $check_stmt->execute();

    if ($check_stmt->rowCount() == 0) {
        throw new Exception('Vehicle with license plate "' . htmlspecialchars($license_plate) . '" not found in the database.');
    }

    // Prepare the stored procedure call
    $sql = "EXEC insertSafetyCharacteristics @license_plate = :license_plate, @comments = :comments, @official = :official";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':license_plate', $license_plate, PDO::PARAM_STR);
    $stmt->bindParam(':comments', $comments, PDO::PARAM_STR);
    $stmt->bindParam(':official', $official, PDO::PARAM_STR);
    
    // Execute the stored procedure
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Safety characteristics recorded successfully for vehicle: ' . htmlspecialchars($license_plate);
        $response['data'] = array(
            'license_plate' => $license_plate,
            'official' => $official,
            'timestamp' => date('Y-m-d H:i:s')
        );
    } else {
        throw new Exception('Failed to execute stored procedure.');
    }

    // Close database connection
    $database->closeConnection();

} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('PDO Error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Error: ' . $e->getMessage());
}

// Output JSON response
echo json_encode($response);
?>