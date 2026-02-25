ALTER PROCEDURE GetAvailableVehicleTypes
(
    @service_type_id INT,
    @latitude FLOAT,
    @longitude FLOAT,
    @search_radius INT = 100000
)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @pickup GEOGRAPHY = geography::Point(@latitude, @longitude, 4326);

    ------------------------------------------------------------
    -- Step 1: Vehicles available near pickup
    ------------------------------------------------------------
    ;WITH NearbyVehicles AS (
        SELECT 
            v.vehicle_type_id,
            COUNT(*) AS available_count
        FROM Vehicle v
        JOIN DRIVER d ON d.driver_id = v.driver_id
        WHERE 
            v.is_active = 0
            AND v.service_type_id = @service_type_id
          --  AND d.status = ''
            AND v.location.STDistance(@pickup) <= @search_radius
        GROUP BY v.vehicle_type_id
    )

    ------------------------------------------------------------
    -- Step 2: Join with vehicle types
    ------------------------------------------------------------
    SELECT 
        vt.vehicle_type_id,
        vt.vehicle_type,
        ISNULL(nv.available_count, 0) AS available_nearby
    FROM Vehicle_Type vt
    RIGHT JOIN NearbyVehicles nv
        ON vt.vehicle_type_id = nv.vehicle_type_id
    WHERE vt.vehicle_type_id IN (
        SELECT DISTINCT vehicle_type_id
        FROM Vehicle
        WHERE service_type_id = @service_type_id
          AND is_active = 0
    );
END;
GO
