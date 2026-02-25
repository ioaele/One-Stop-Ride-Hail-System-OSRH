USE [eioann09]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE OR ALTER PROCEDURE [eioann09].[GetUserActiveRide]
    @users_id INT  -- passenger's users_id
AS
BEGIN
    SET NOCOUNT ON;
    
    -- Get user's active ride (Accepted or InProgress)
    SELECT 
        RR.ride_request_id,
        RR.status,
        RR.request_time,
        RR.response_time,
        RR.vehicle_type_requested,
        VT.vehicle_type,
        -- Pickup point
        RR.pickup_point_id,
        P1.GeoPoint.Lat AS pickup_latitude,
        P1.GeoPoint.Long AS pickup_longitude,
        -- Dropoff point
        RR.dropoff_point_id,
        P2.GeoPoint.Lat AS dropoff_latitude,
        P2.GeoPoint.Long AS dropoff_longitude,
        -- Driver info (if assigned)
        RR.driver_id,
        D.users_id AS driver_users_id,
        U.username AS driver_username,
        U.phone_number AS driver_phone
    FROM RIDEREQUEST RR
    LEFT JOIN DRIVER D ON RR.driver_id = D.driver_id
    LEFT JOIN USERS U ON D.users_id = U.users_id
    LEFT JOIN VEHICLE_TYPE VT ON RR.vehicle_type_requested = VT.vehicle_type_id
    INNER JOIN POINT P1 ON RR.pickup_point_id = P1.point_id
    INNER JOIN POINT P2 ON RR.dropoff_point_id = P2.point_id
    WHERE RR.users_id = @users_id
    AND RR.status IN ('Pending', 'Accepted', 'InProgress')
    ORDER BY 
        CASE 
            WHEN RR.status = 'InProgress' THEN 1
            WHEN RR.status = 'Accepted' THEN 2
            WHEN RR.status = 'Pending' THEN 3
        END;
    
END;
GO
