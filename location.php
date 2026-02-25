<?php
session_start(); // Make sure session is started
require_once 'connect.php';

header("Content-Type: application/json");

$json = trim(file_get_contents('php://input'));
$data = json_decode($json);

if($data == null){
    http_response_code(400);
    echo json_encode(['status' => 'error','message' => 'Invalid JSON']);
    exit;
}

// Read from session instead of writing to it
if(!isset($_SESSION['users_id'])){
    http_response_code(401);
    echo json_encode(['status' => 'error','message' => 'User not logged in']);
    exit;
}

$users_id = $_SESSION['users_id'];
$latitude = $data->latitude ?? null;
$longitude = $data->longitude ?? null;

if($latitude === null || $longitude === null){
    http_response_code(400);
    echo json_encode(['status' => 'error','message' => 'Missing latitude or longitude']);
    exit;
}

$sql = "{CALL [eioann09].[insertLocation](?,?,?)}";

try{
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $users_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $latitude, PDO::PARAM_STR);
    $stmt->bindParam(3, $longitude, PDO::PARAM_STR);

    $stmt->execute();

    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success','message' => 'Location updated successfully']);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error','message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>