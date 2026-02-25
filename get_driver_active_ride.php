<?php
session_start();
header('Content-Type: application/json');

require_once 'connect.php';

if (!isset($_SESSION['users_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

$users_id = $_SESSION['users_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $sql = "{CALL [eioann09].[GetDriverActiveRide](?)}";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $users_id, PDO::PARAM_INT);
    $stmt->execute();

    $ride = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        echo json_encode([
            'success' => false,
            'error' => 'No active ride found'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $ride
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
