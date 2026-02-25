USE [eioann09]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE OR ALTER PROCEDURE [eioann09].[GetDriverLocationByRideRequest]
    @ride_request_id INT
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @driver_id INT;
    
    -- Get the driver_id from the ride request
    SELECT @driver_id = driver_id 
    FROM RIDEREQUEST 
    WHERE ride_request_id = @ride_request_id;
    
    IF @driver_id IS NULL
    BEGIN
        RAISERROR('Driver not assigned to this ride request.', 16, 1);
        RETURN;
    END
    
    -- Get the latest driver location
    SELECT TOP 1 
        location.Lat AS latitude,
        location.Long AS longitude,
        last_update
    FROM DriverLocation
    WHERE driver_id = @driver_id
    ORDER BY last_update DESC;
    
END;
GO
