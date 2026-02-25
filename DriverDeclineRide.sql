CREATE PROCEDURE DriverRejectRide
(
    @driver_id INT,
    @ride_id INT
)
AS
BEGIN
    SET NOCOUNT ON;

    ------------------------------------------------------------
    -- 1. Reject this driver's request
    ------------------------------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM RideRequest
        WHERE ride_id = @ride_id
          AND driver_id = @driver_id
          AND status = 'Pending'
    )
    BEGIN
        RAISERROR('No pending ride request for this driver.', 16, 1);
        RETURN;
    END

    UPDATE RideRequest
    SET status = 'Rejected', response_time = GETDATE()
    WHERE ride_id = @ride_id AND driver_id = @driver_id;


    ------------------------------------------------------------
    -- 2. Check if any pending requests remain
    ------------------------------------------------------------
    IF EXISTS (
        SELECT 1 FROM RideRequest
        WHERE ride_id = @ride_id
          AND status = 'Pending'
    )
    BEGIN
        RETURN;  -- still waiting for other drivers
    END


    ------------------------------------------------------------
    -- 3. No pending requests left s expand radius
    ------------------------------------------------------------
    DECLARE @radius INT, @attempts INT;

    SELECT 
        @radius = search_radius,
        @attempts = dispatch_attempts
    FROM Ride
    WHERE ride_id = @ride_id;

    -- max attempts before giving up
    IF @attempts >= 3
    BEGIN
        UPDATE Ride
        SET status = 'NoDriversAvailable'
        WHERE ride_id = @ride_id;

        RETURN;
    END


    ------------------------------------------------------------
    -- 4. Expand radius (double it)
    ------------------------------------------------------------
    SET @radius = @radius * 2;
    SET @attempts = @attempts + 1;

    UPDATE Ride
    SET search_radius = @radius,
        dispatch_attempts = @attempts
    WHERE ride_id = @ride_id;


    ------------------------------------------------------------
    -- 5. Re-dispatch with expanded radius
    ------------------------------------------------------------
    EXEC sp_DispatchRideRequests
        @ride_id = @ride_id,
        @vehicle_type_requested_id = NULL,   -- reuse previous one inside SP
        @radius_meters = @radius;

END;
GO
