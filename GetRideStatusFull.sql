CREATE PROCEDURE GetRideStatusFull
    @ride_id INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        R.ride_id,
        R.status,
        R.date,
        R.users_id AS passenger_id,
        R.vehicle_id,
        D.driver_id,
        U.username AS passenger_username
    FROM RIDE R
    LEFT JOIN DRIVER_RIDE DR ON DR.ride_id = R.ride_id
    LEFT JOIN DRIVER D ON D.driver_id = DR.driver_id
    LEFT JOIN USERS U ON U.users_id = R.users_id
    WHERE R.ride_id = @ride_id;
END;
GO
