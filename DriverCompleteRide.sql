USE [eioann09]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE OR ALTER PROCEDURE [eioann09].[DriverCompleteRide]
    @users_id INT,           -- driver's users_id
    @ride_request_id INT,    -- ride to complete
    @current_lat FLOAT,      -- driver's current latitude
    @current_long FLOAT      -- driver's current longitude
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @driver_id INT;
    DECLARE @dropoff_lat FLOAT;
    DECLARE @dropoff_long FLOAT;
    DECLARE @distance_meters FLOAT;
    DECLARE @dropoff_point_id INT;
    
    -- Get driver ID
    SELECT @driver_id = driver_id FROM DRIVER WHERE users_id = @users_id;
    
    IF @driver_id IS NULL
    BEGIN
        RAISERROR('Driver not found.', 16, 1);
        RETURN;
    END
    
    -- Get dropoff point location for this ride
    SELECT 
        @dropoff_point_id = RR.dropoff_point_id,
        @dropoff_lat = P.GeoPoint.Lat,
        @dropoff_long = P.GeoPoint.Long
    FROM RIDEREQUEST RR
    INNER JOIN POINT P ON RR.dropoff_point_id = P.point_id
    WHERE RR.ride_request_id = @ride_request_id
    AND RR.driver_id = @driver_id
    AND RR.status = 'InProgress';
    
    IF @dropoff_point_id IS NULL
    BEGIN
        RAISERROR('Ride not found or not in progress for this driver.', 16, 1);
        RETURN;
    END
    
    -- Create geography point for driver's current location
    DECLARE @driver_location GEOGRAPHY;
    SET @driver_location = GEOGRAPHY::Point(@current_lat, @current_long, 4326);
    
    -- Get dropoff point geography and calculate distance
    DECLARE @dropoff_location GEOGRAPHY;
    SELECT @dropoff_location = GeoPoint
    FROM POINT
    WHERE point_id = @dropoff_point_id;
    
    SET @distance_meters = @driver_location.STDistance(@dropoff_location);
    
    -- Check if driver is within 50 meters of dropoff
    IF @distance_meters > 50
    BEGIN
        DECLARE @error_msg NVARCHAR(200) = 
            'You are ' + CAST(CAST(@distance_meters AS INT) AS NVARCHAR(10)) + 
            ' meters away from dropoff. You must be within 50 meters to complete the ride.';
        RAISERROR(@error_msg, 16, 1);
        RETURN;
    END
    
    -- Complete the ride
    UPDATE RIDEREQUEST 
    SET status = 'Completed'
    WHERE ride_request_id = @ride_request_id;
    
    -- Update RIDE table - set end time and status to Completed
    UPDATE RIDE
    SET status = 'Completed',
        ride_datetime_end = GETDATE()
    WHERE driver_id = @driver_id
    AND status = 'InProgress'
    AND pickup_point_id = (SELECT pickup_point_id FROM RIDEREQUEST WHERE ride_request_id = @ride_request_id);

    -- Get the ride_id and service_id for this ride
    DECLARE @ride_id INT, @service_id INT;
    SELECT TOP 1 @ride_id = ride_id, @service_id = service_id
    FROM RIDE
    WHERE driver_id = @driver_id AND status = 'Completed' AND pickup_point_id = (SELECT pickup_point_id FROM RIDEREQUEST WHERE ride_request_id = @ride_request_id);

    -- Only record service fee if not a rental (service_id != 7)
    IF @ride_id IS NOT NULL AND @service_id IS NOT NULL AND @service_id != 7
    BEGIN
        EXEC [eioann09].[RecordServiceFee] @ride_id;
    END

    -- Set vehicle is_active = 0 for the assigned vehicle
    DECLARE @vehicle_id INT;
    SELECT @vehicle_id = vehicle_id FROM RIDE WHERE ride_id = @ride_id;
    IF @vehicle_id IS NOT NULL
    BEGIN
        UPDATE VEHICLE SET is_active = 0 WHERE vehicle_id = @vehicle_id;
    END

    -- Update driver status back to available
    UPDATE DRIVER
    SET status_d = 'Y'
    WHERE driver_id = @driver_id;

    SELECT 'Ride completed successfully' AS message, @distance_meters AS distance_from_dropoff;

END;
GO
