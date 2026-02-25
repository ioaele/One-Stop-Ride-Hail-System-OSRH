USE [eioann09]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE OR ALTER PROCEDURE [eioann09].[GetDriverAcceptedRide]
    @users_id INT  -- driver's users_id
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @driver_id INT;
    
    SELECT @driver_id = driver_id FROM DRIVER WHERE users_id = @users_id;
    
    IF @driver_id IS NULL
    BEGIN
        RAISERROR('Driver not found.', 16, 1);
        RETURN;
    END
    
    SELECT 
        RR.ride_request_id,
        RR.users_id AS rider_users_id,
        U.username AS rider_username,
        U.phone_number AS rider_phone,
        RR.status,
        RR.request_time,
        RR.response_time,
        RR.vehicle_type_requested,
        RR.service_id,
        -- Pickup point
        RR.pickup_point_id,
        P1.GeoPoint.Lat AS pickup_latitude,
        P1.GeoPoint.Long AS pickup_longitude,
        -- Dropoff point
        RR.dropoff_point_id,
        P2.GeoPoint.Lat AS dropoff_latitude,
        P2.GeoPoint.Long AS dropoff_longitude
    FROM RIDEREQUEST RR
    INNER JOIN USERS U ON RR.users_id = U.users_id
    INNER JOIN POINT P1 ON RR.pickup_point_id = P1.point_id
    INNER JOIN POINT P2 ON RR.dropoff_point_id = P2.point_id
    WHERE RR.driver_id = @driver_id
    AND RR.status = 'Accepted';
    
END;
GO
