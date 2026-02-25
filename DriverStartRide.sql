USE [eioann09]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE OR ALTER PROCEDURE [eioann09].[DriverStartRide]
    @users_id INT,           -- driver's users_id
    @ride_request_id INT     -- ride request to start
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @driver_id INT;
    DECLARE @vehicle_id INT;
    DECLARE @rider_users_id INT;
    DECLARE @pickup_point_id INT;
    DECLARE @dropoff_point_id INT;
    DECLARE @vehicle_type_requested INT;
    DECLARE @service_id INT;
    DECLARE @service_type_id INT;
    DECLARE @new_ride_id INT;
    
    BEGIN TRY
        BEGIN TRANSACTION;
        
        -- Get driver info
        SELECT @driver_id = driver_id
        FROM DRIVER 
        WHERE users_id = @users_id;
        
        IF @driver_id IS NULL
        BEGIN
            RAISERROR('Driver not found.', 16, 1);
            ROLLBACK TRANSACTION;
            RETURN;
        END
        
        -- Get ride request details and verify it's accepted by this driver
        SELECT 
            @rider_users_id = users_id,
            @pickup_point_id = pickup_point_id,
            @dropoff_point_id = dropoff_point_id,
            @vehicle_type_requested = vehicle_type_requested,
            @service_id = service_id
        FROM RIDEREQUEST
        WHERE ride_request_id = @ride_request_id
        AND driver_id = @driver_id
        AND status = 'Accepted';
        
        IF @rider_users_id IS NULL
        BEGIN
            RAISERROR('Ride request not found or not accepted by this driver.', 16, 1);
            ROLLBACK TRANSACTION;
            RETURN;
        END
        
        -- Get the driver's vehicle for the requested vehicle type
        SELECT @vehicle_id = V.vehicle_id, @service_type_id = V.service_type_id
        FROM VEHICLE V
        WHERE V.driver_id = @driver_id
        AND V.vehicle_type_id = @vehicle_type_requested;
        
        IF @vehicle_id IS NULL
        BEGIN
            RAISERROR('Driver does not have a vehicle of the requested type.', 16, 1);
            ROLLBACK TRANSACTION;
            RETURN;
        END
        
        -- Insert into RIDE table
        INSERT INTO RIDE
        (
            users_id,
            service_id,
            service_type_id,
            pickup_point_id,
            dropoff_point_id,
            vehicle_id,
            driver_id,
            status,
            ride_datetime_start
        )
        VALUES
        (
            @rider_users_id,
            @service_id,
            @service_type_id,
            @pickup_point_id,
            @dropoff_point_id,
            @vehicle_id,
            @driver_id,
            'InProgress',
            GETDATE()
        );
        
        SET @new_ride_id = SCOPE_IDENTITY();
        
        -- Update RIDEREQUEST status to InProgress
        UPDATE RIDEREQUEST 
        SET status = 'InProgress', 
            is_ride_now = 1
        WHERE ride_request_id = @ride_request_id;
        
        COMMIT TRANSACTION;
        
        SELECT @new_ride_id AS ride_id, 'Ride started successfully' AS message;
        
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        THROW;
    END CATCH
    
END;
GO
