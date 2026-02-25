<?php
header('Content-Type: application/json');
require_once 'connect.php';

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $driver_id       = null;
    $users_id        = 123;      // βάλε υπαρκτό
    $service_type_id = 12;
    $seats           = 2;
    $vehicle_type    = 'TestType';
    $license_plate   = 'TEST-1234';
    $luggage_volume  = 1.00;
    $luggage_weight  = 12;
    $photo_interior  = '';       // ΚΕΝΟ, ΟΧΙ NULL
    $photo_exterior  = '';       // ΚΕΝΟ, ΟΧΙ NULL

    $sql = "
    DECLARE 
        @success BIT,
        @message NVARCHAR(500),
        @new_vehicle_id INT;

    EXEC [eioann09].[registerDriverWithVehicle]
         @driver_id       = :driver_id,
         @users_id        = :users_id,
         @service_type_id = :service_type_id,
         @seats           = :seats,
         @vehicle_type    = :vehicle_type,
         @license_plate   = :license_plate,
         @luggage_volume  = :luggage_volume,
         @luggage_weight  = :luggage_weight,
         @photo_interior  = :photo_interior,
         @photo_exterior  = :photo_exterior,
         @success         = @success OUTPUT,
         @message         = @message OUTPUT,
         @new_vehicle_id  = @new_vehicle_id OUTPUT;

    SELECT 
        @success AS success,
        @message AS message,
        @new_vehicle_id AS vehicle_id;
    ";

    $stmt = $conn->prepare($sql);

    if ($driver_id === null) {
        $stmt->bindValue(':driver_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':driver_id', (int)$driver_id, PDO::PARAM_INT);
    }

    $stmt->bindValue(':users_id',        (int)$users_id,        PDO::PARAM_INT);
    $stmt->bindValue(':service_type_id', (int)$service_type_id, PDO::PARAM_INT);
    $stmt->bindValue(':seats',           (int)$seats,           PDO::PARAM_INT);
    $stmt->bindValue(':vehicle_type',    $vehicle_type,         PDO::PARAM_STR);
    $stmt->bindValue(':license_plate',   $license_plate,        PDO::PARAM_STR);
    $stmt->bindValue(':luggage_volume',  (float)$luggage_volume);
    $stmt->bindValue(':luggage_weight',  (int)$luggage_weight,  PDO::PARAM_INT);
    $stmt->bindValue(':photo_interior',  $photo_interior,       PDO::PARAM_STR);
    $stmt->bindValue(':photo_exterior',  $photo_exterior,       PDO::PARAM_STR);

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'ok',
        'result' => $result
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error',
        'debug'   => $e->getMessage(),
        'info'    => $e->errorInfo ?? null
    ]);
}
