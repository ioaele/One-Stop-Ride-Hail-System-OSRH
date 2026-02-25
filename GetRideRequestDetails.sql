USE [eioann09]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE OR ALTER PROCEDURE [eioann09].[GetRideRequestLocations]
    @ride_request_id INT
AS
BEGIN
    SET NOCOUNT ON;
    
    SELECT 
        R.ride_request_id,
        -- Pickup point details
        P1.GeoPoint.Lat AS pickup_latitude,
        P1.GeoPoint.Long AS pickup_longitude,
        -- Dropoff point details
        P2.GeoPoint.Lat AS dropoff_latitude,
        P2.GeoPoint.Long AS dropoff_longitude
    FROM RIDErequest R
    INNER JOIN POINT P1 ON R.pickup_point_id = P1.point_id
    INNER JOIN POINT P2 ON R.dropoff_point_id = P2.point_id
    WHERE R.ride_request_id = @ride_request_id;
    
END;
GO
