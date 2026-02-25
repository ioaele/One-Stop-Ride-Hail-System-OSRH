CREATE PROCEDURE CreateRide

(
    @rider_users_id INT,
    @service_id INT,
    @service_type_id INT,
    @pickup_point_id INT,
    @dropoff_point_id INT,
    @estimated_price DECIMAL(10,2) = NULL,
    @vehicle_type_id INT,
    @vehicle_id INT,
    @new_ride_id INT OUTPUT
)
AS
BEGIN
    SET NOCOUNT ON;

   

    IF NOT EXISTS (SELECT 1 FROM Users WHERE users_id = @rider_users_id)
    BEGIN
        RAISERROR('Invalid rider_users_id.', 16, 1);
        RETURN;
    END
      IF EXISTS (SELECT 1 FROM RideRequest WHERE ride_id = @new_ride_id)
    BEGIN
        RAISERROR('Request Already Exists. Cancel to make new request.', 16, 1);
        RETURN;
    END

    IF NOT EXISTS (SELECT 1 FROM Service WHERE service_id = @service_id)
    BEGIN
        RAISERROR('Invalid service_id.', 16, 1);
        RETURN;
    END

    IF NOT EXISTS (SELECT 1 FROM Service_Type WHERE service_type_id = @service_type_id)
    BEGIN
        RAISERROR('Invalid service_type_id.', 16, 1);
        RETURN;
    END

    IF NOT EXISTS (SELECT 1 FROM Point WHERE point_id = @pickup_point_id)
    BEGIN
        RAISERROR('Invalid pickup_point_id.', 16, 1);
        RETURN;
    END

    IF NOT EXISTS (SELECT 1 FROM Point WHERE point_id = @dropoff_point_id)
    BEGIN
        RAISERROR('Invalid dropoff_point_id.', 16, 1);
        RETURN;
    END

    -- Insert into RIDE table first
    INSERT INTO RIDE
    (
        users_id,
        service_id,
        service_type_id,
        pickup_point_id,
        dropoff_point_id,
        price,
        driver_id,
        vehicle_id,
        status,
        ride_datetime_start,
        ride_datetime_end
    )
    VALUES
    (
        @rider_users_id,
        @service_id,
        @service_type_id,
        @pickup_point_id,
        @dropoff_point_id,
        @estimated_price,
        NULL,                -- driver_id (assigned later)
        @vehicle_id,         -- vehicle_id stores vehicle_type_id initially
        'Requested',         -- initial status
        NULL,                -- ride_datetime_start
        NULL                 -- ride_datetime_end
    );

    SET @new_ride_id = SCOPE_IDENTITY();

END;
GO
