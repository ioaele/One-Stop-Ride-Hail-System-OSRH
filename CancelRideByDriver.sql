CREATE PROCEDURE CancelRideByDriver
(
    @ride_id INT,
    @driver_id INT
)
AS
BEGIN
    SET NOCOUNT ON;

    --------------------------------------------------------
    -- Validate
    --------------------------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM Ride
        WHERE ride_id = @ride_id
          AND status = 'Accepted'
          AND driver_id = @driver_id
    )
    BEGIN
        RAISERROR('Driver cannot cancel this ride.', 16, 1);
        RETURN;
    END


    --------------------------------------------------------
    -- Cancel ride
    --------------------------------------------------------
    UPDATE Ride
    SET status = 'Cancelled'
    WHERE ride_id = @ride_id;

        --------------------------------------------------------
    -- Cancel all pending requests
    --------------------------------------------------------
    DELETE FROM RideRequest
    WHERE ride_id = @ride_id;


    --------------------------------------------------------
    -- Free driver
    --------------------------------------------------------
    UPDATE Driver
    SET status = 'N'
    WHERE driver_id = @driver_id;


    --------------------------------------------------------
    -- Free vehicle
    --------------------------------------------------------
    UPDATE Vehicle
    SET isactivev = 0
    WHERE vehicle_id = (
        SELECT vehicle_id 
        FROM Ride WHERE ride_id = @ride_id
    );
END;
GO
