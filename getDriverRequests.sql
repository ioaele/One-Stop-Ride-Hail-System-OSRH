CREATE PROCEDURE getDriverRequests
    @DriverID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        rr.ride_id,
        -- pickup coordinates instead of pickup_point_id
        p_pick.GeoPoint AS pickup_lat,
     --   p_pick.longitude AS pickup_lng,
        -- dropoff coordinates instead of dropoff_point_id
        p_drop.GeoPoint AS dropoff_lat,
    --    p_drop.longitude AS dropoff_lng,
        v.vehicle_type_id,
        rr.request_time,
        rr.response_time,
        rr.status
    FROM 
        eioann09.RIDEREQUEST rr
    INNER JOIN 
        RIDE r ON rr.ride_id = r.ride_id
    INNER JOIN
        VEHICLE v ON rr.vehicle_id_requested = v.vehicle_id
    LEFT JOIN POINT p_pick ON r.pickup_point_id = p_pick.point_id
    LEFT JOIN POINT p_drop ON r.dropoff_point_id = p_drop.point_id
    WHERE 
        rr.driver_id = @DriverID
        AND rr.status IN ('Pending', 'Accepted')  -- assuming these are "current" statuses
    ORDER BY 
        rr.request_time DESC;
END;
GO
