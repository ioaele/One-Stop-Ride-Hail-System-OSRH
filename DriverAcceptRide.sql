USE [eioann09]
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE OR ALTER PROCEDURE [eioann09].[DriverAcceptRide]
    @users_id INT,  -- driver's users_id
    @ride_request_id INT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @driver_id INT;

    BEGIN TRY
        BEGIN TRANSACTION;

        ----------------------------------------------------
        -- 1. Get driver_id from users_id
        ----------------------------------------------------
        SELECT @driver_id = driver_id 
        FROM DRIVER 
        WHERE users_id = @users_id;

        IF @driver_id IS NULL
        BEGIN
            RAISERROR('Driver not found for this user.', 16, 1);
            ROLLBACK;
            RETURN;
        END

        ----------------------------------------------------
        -- 2. Ensure ride request exists and is Pending
        ----------------------------------------------------
        IF NOT EXISTS (
            SELECT 1 FROM RIDErequest
            WHERE ride_request_id = @ride_request_id AND status = 'Pending'
        )
        BEGIN
            RAISERROR('Ride request is not available or already accepted.', 16, 1);
            ROLLBACK;
            RETURN;
        END


        ----------------------------------------------------
        -- 3. Update ride request status to Accepted
        ----------------------------------------------------
        UPDATE RIDErequest
        SET status = 'Accepted',
            response_time = GETDATE(),
            driver_id = @driver_id
        WHERE ride_request_id = @ride_request_id;

        ----------------------------------------------------
        -- 4. Set vehicle is_active = 1 for assigned vehicle
        ----------------------------------------------------
        DECLARE @vehicle_id INT;
        SELECT @vehicle_id = V.vehicle_id
        FROM VEHICLE V
        INNER JOIN DRIVER D ON V.driver_id = D.driver_id
        INNER JOIN RIDEREQUEST RR ON RR.ride_request_id = @ride_request_id
        WHERE D.driver_id = @driver_id AND V.vehicle_type_id = RR.vehicle_type_requested;
        IF @vehicle_id IS NOT NULL
        BEGIN
            UPDATE VEHICLE SET is_active = 1 WHERE vehicle_id = @vehicle_id;
        END

        ----------------------------------------------------
        -- 5. Update driver status to unavailable
        ----------------------------------------------------
        UPDATE DRIVER
        SET status_d = 'N'  -- Not available
        WHERE driver_id = @driver_id;

        COMMIT;
        
        -- Return success
        SELECT 
            'Success' AS result,
            @ride_request_id AS ride_request_id,
            @driver_id AS driver_id;
            
    END TRY

    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK;
        THROW;
    END CATCH
END;
GO
