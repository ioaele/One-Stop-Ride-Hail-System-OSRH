<?php
session_start();
require_once 'connect.php';

header("Content-Type: application/json");

// Check if user is logged in
if(!isset($_SESSION['users_id'])){
    http_response_code(401);
    echo json_encode(['status' => 'error','message' => 'User not logged in']);
    exit;
}

$users_id = $_SESSION['users_id'];

$sql = "{CALL [eioann09].[deleteLocation](?)}";

try{
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $users_id, PDO::PARAM_INT);
  
    $stmt->execute();

    http_response_code(200);
    echo json_encode(['status' => 'success','message' => 'You are now offline']);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error','message' => 'Database error: ' . $e->getMessage()]);
}
?>