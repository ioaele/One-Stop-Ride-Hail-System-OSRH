CREATE PROCEDURE GetRideStatus
    @ride_id INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        status
    FROM RIDE
    WHERE ride_id = @ride_id;
END;
GO
