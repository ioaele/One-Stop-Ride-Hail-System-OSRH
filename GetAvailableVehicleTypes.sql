
DROP PROCEDURE IF EXISTS [eioann09].[GetAvailableVehicleTypes];
GO

CREATE PROCEDURE [eioann09].[GetAvailableVehicleTypes]
(
    @service_type_id INT,
    @latitude FLOAT,
    @longitude FLOAT,
    @search_radius INT = 3000
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
        JOIN DriverLocation dl ON dl.driver_id = d.driver_id
        WHERE 
            v.is_active = 0
            AND v.service_type_id = @service_type_id
            AND d.status IN ('N' , 'A')
            AND dl.location.STDistance(@pickup) <= @search_radius
        GROUP BY v.vehicle_type_id
    )

    ------------------------------------------------------------
    -- Step 2: Join with vehicle types - ONLY show types with nearby vehicles
    ------------------------------------------------------------
    SELECT 
        vt.vehicle_type_id,
        vt.vehicle_type,
        nv.available_count AS available_nearby
    FROM Vehicle_Type vt
    INNER JOIN NearbyVehicles nv
        ON vt.vehicle_type_id = nv.vehicle_type_id
    WHERE nv.available_count > 0;
END;
GO
