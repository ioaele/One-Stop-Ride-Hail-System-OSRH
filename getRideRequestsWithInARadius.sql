USE [eioann09]
GO

/****** Object:  StoredProcedure [eioann09].[getDriverRequests]    Script Date: 11/26/2025 4:58:06 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO


ALTER PROCEDURE [eioann09].[getDriverRequestsWithInARadius]
    @users_id INT, --the driver
    @search_radius INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @driver_id INT
    SELECT @driver_id = driver_id
    FROM  DRIVER
    WHERE @users_id=users_id

    SELECT U.username,
           U.phone_number,
           R.ride_request_id,
           R.status,
           P.GeoPoint.Lat AS pickup_latitude,
           P.GeoPoint.Long AS pickup_longitude,
           CAST(P.GeoPoint.STDistance(Dl.location) AS FLOAT) AS distance_meters
    FROM RIDErequest R
    INNER JOIN USERS U ON U.users_id = R.users_id
    INNER JOIN POINT P ON R.pickup_point_id = P.point_id
    INNER JOIN DriverLocation Dl ON @driver_id = Dl.driver_id
    WHERE R.status = 'Pending'
    AND P.GeoPoint.STDistance(Dl.location) < @search_radius 
    ORDER BY distance_meters ASC

END;
GO

