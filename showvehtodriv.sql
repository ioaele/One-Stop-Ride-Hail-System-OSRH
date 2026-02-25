CREATE PROCEDURE sp_ShowDriverAvailableVehicles
(
    @driver_id INT,
    @ride_id INT = NULL          -- optional
)
AS
BEGIN
    SET NOCOUNT ON;

    ------------------------------------------------------------
    -- Step 1: If ride_id given, load ride constraints
    ------------------------------------------------------------
    DECLARE 
        @vehicle_type_requested_id INT = NULL,
        @service_type_id INT = NULL;

    IF @ride_id IS NOT NULL
    BEGIN
        SELECT 
            @vehicle_type_requested_id = RR.vehicle_id_requested,
            @service_type_id = R.service_type_id
        FROM Ride R
        LEFT JOIN RideRequest RR ON RR.ride_id = R.ride_id
        WHERE R.ride_id = @ride_id;
    END


    ------------------------------------------------------------
    -- Step 2: Vehicles that are currently in use by other rides
    ------------------------------------------------------------
    ;WITH BusyVehicles AS (
        SELECT vehicle_id
        FROM Ride
        WHERE status IN ('Accepted', 'InProgress')
          AND vehicle_id IS NOT NULL
    )

    ------------------------------------------------------------
    -- Step 3: Select available vehicles for this driver
    ------------------------------------------------------------
    SELECT 
        V.vehicle_id,
        V.driver_id,
        V.vehicle_type_id,
        V.service_type_id,
        VT.name AS vehicle_type_name,
        ST.ride_type AS service_type_name,
        CASE WHEN BV.vehicle_id IS NOT NULL THEN 0 ELSE 1 END AS is_available
    FROM Vehicle V
    JOIN VehicleType VT ON VT.vehicle_type_id = V.vehicle_type_id
    JOIN Service_Type ST ON ST.service_type_id = V.service_type_id
    LEFT JOIN BusyVehicles BV ON BV.vehicle_id = V.vehicle_id
    WHERE 
        V.driver_id = @driver_id
        AND (BV.vehicle_id IS NULL)   -- only free vehicles

        -- filters depending on ride
        AND (@service_type_id IS NULL OR V.service_type_id = @service_type_id)
        AND (@vehicle_type_requested_id IS NULL OR V.vehicle_type_id = @vehicle_type_requested_id)

    ORDER BY V.vehicle_id;
END;
GO
