CREATE PROCEDURE [eioann09].[GetAvailableRentalVehicles]
    @service_type_id INT,
    @rental_start DATETIME,
    @rental_end DATETIME
AS
BEGIN
    SET NOCOUNT ON;
    
    -- Get available rental vehicles based on service type
    -- Vehicle must have company_id (not null) meaning it's for rent without a driver
    -- Vehicle must not have conflicting bookings in the requested time period
    
    SELECT 
        v.vehicle_id,
        v.license_plate,
        v.vehicle_type_id,
        vt.vehicle_type AS vehicle_type_name,
        v.company_id,
        v.location,
        v.location.Lat AS current_lat,
        v.location.Long AS current_lng
   
    FROM VEHICLE v
    INNER JOIN Vehicle_Type vt ON v.vehicle_type_id = vt.vehicle_type_id
    INNER JOIN SERVICE_TYPE st ON v.service_type_id = st.service_type_id
    WHERE 
        -- Must be rental vehicle (has company, no driver)
        v.company_id IS NOT NULL
        -- Must support the requested service type
        AND st.service_type_id = @service_type_id
        -- Must have location data
        AND v.location IS NOT NULL
        -- Must not have conflicting bookings
        AND NOT EXISTS (
            SELECT 1 
            FROM RIDE r
            WHERE r.vehicle_id = v.vehicle_id
                AND r.status IN ('inprogress')
                AND (
                    -- Overlapping time periods
                    (r.ride_datetime_start <= @rental_end AND r.ride_datetime_end >= @rental_start)
                    OR
                    -- Ongoing rides without end time
                    (r.ride_datetime_start <= @rental_end AND r.ride_datetime_end IS NULL)
          )      
        )
    ORDER BY v.vehicle_id;
END
