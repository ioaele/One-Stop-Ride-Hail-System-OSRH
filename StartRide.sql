CREATE PROCEDURE sp_StartRide
(
    @ride_id INT,
    @driver_id INT
)
AS
BEGIN
    SET NOCOUNT ON;

    --------------------------------------------------------
    -- Validate ride exists and is accepted by this driver
    --------------------------------------------------------
    IF NOT EXISTS (
        SELECT 1
        FROM Ride
        WHERE ride_id = @ride_id
          AND status = 'Accepted'
          AND driver_id = @driver_id
    )
    BEGIN
        RAISERROR('Ride cannot be started. Not accepted by this driver.', 16, 1);
        RETURN;
    END


    --------------------------------------------------------
    -- Ensure ride not already started
    --------------------------------------------------------
    IF EXISTS (
        SELECT 1 FROM Ride
        WHERE ride_id = @ride_id
          AND status = 'In progress' -- IN PROGRESS
    )
    BEGIN
        RAISERROR('Ride already in progress.', 16, 1);
        RETURN;
    END


    --------------------------------------------------------
    -- Update ride to InProgress
    --------------------------------------------------------
    UPDATE Ride
    SET 
        status = 'In progress',
        start_time = GETDATE()
    WHERE ride_id = @ride_id;


    --------------------------------------------------------
    -- Mark driver as busy
    --------------------------------------------------------
    UPDATE Driver
    SET status = 'B'
    WHERE driver_id = @driver_id;

     UPDATE Vehicle
    SET isactivev = 1
    WHERE vehicle_id = (
        SELECT vehicle_id
        FROM Ride 
        WHERE ride_id = @ride_id
    );

END;
GO
