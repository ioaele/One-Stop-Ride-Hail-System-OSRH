CREATE OR ALTER PROCEDURE getActiverental
    @users_id INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1
        r.ride_id,
        v.vehicle_id,
        v.license_plate,
        r.ride_datetime_start,
        r.ride_datetime_end,
        r.status,
        r.price
    FROM RIDE r
    INNER JOIN Vehicle v ON r.vehicle_id = v.vehicle_id
    WHERE r.users_id = @users_id
      AND r.status IN ('Active', 'Ongoing', 'InProgress')
    
END
