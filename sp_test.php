<?php
require 'connect.php';

header('Content-Type: text/plain; charset=utf-8');

$service_type_id = isset($_GET['service_type_id']) ? intval($_GET['service_type_id']) : 11;
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 35.12797421116001;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : 33.42529947522864;
$radius = isset($_GET['radius']) ? intval($_GET['radius']) : 3000;

echo "Testing stored procedure call with params:\n";
echo " service_type_id={$service_type_id}\n lat={$lat}\n lng={$lng}\n radius={$radius}\n\n";

$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    echo "ERROR: Could not get DB connection. Check connect.php and drivers.\n";
    exit;
}

$procedures = [
    '[eioann09].[debug_GetAvailableVehicleTypes]',
    '[eioann09].[GetAvailableVehicleTypes]'
];

foreach ($procedures as $proc) {
    echo "Trying procedure: {$proc}\n";
    try {
        $sql = "{CALL {$proc}(?, ?, ?, ?)}";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $service_type_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $lat, PDO::PARAM_STR);
        $stmt->bindParam(3, $lng, PDO::PARAM_STR);
        $stmt->bindParam(4, $radius, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Success. Rows: " . count($rows) . "\n";
        if (count($rows) > 0) {
            foreach ($rows as $r) {
                echo json_encode($r) . "\n";
            }
        }
        $stmt->closeCursor();
    } catch (PDOException $e) {
        echo "PDOException: " . $e->getMessage() . "\n";
    }
    echo "----\n";
}

echo "Done.\n";

?>
