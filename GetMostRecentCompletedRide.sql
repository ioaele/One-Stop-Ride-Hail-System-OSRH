CREATE OR ALTER PROCEDURE [eioann09].[GetMostRecentCompletedRide]
    @users_id INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1 r.ride_id, r.price , r.vehicle_id
    FROM RIDE r
    INNER JOIN DRIVER d ON r.driver_id = d.driver_id
    WHERE r.users_id = @users_id AND r.status = 'Completed'
    ORDER BY r.ride_datetime_end DESC;
END
GO
