CREATE OR ALTER PROCEDURE CompleteRentalEarly
    @ride_id INT,
    @user_lat FLOAT,
    @user_lng FLOAT
AS
BEGIN
    SET NOCOUNT ON;
    -- Set the ride end time to now
    UPDATE RIDE
    SET ride_datetime_end = GETDATE(),
        status = 'Completed'
    WHERE ride_id = @ride_id;

    -- Get the vehicle_id for this ride
    DECLARE @vehicle_id INT;
    SELECT @vehicle_id = vehicle_id FROM RIDE WHERE ride_id = @ride_id;

    -- Update vehicle location and status
    UPDATE Vehicle
    SET location =Geography::Point(@user_lat,@user_lng,4326),
        is_active = 0
    WHERE vehicle_id = @vehicle_id;

    -- Optionally, update any other related tables/statuses as needed
END
