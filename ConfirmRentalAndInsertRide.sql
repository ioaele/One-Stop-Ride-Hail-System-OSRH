CREATE OR ALTER PROCEDURE [eioann09].[ConfirmRentalAndInsertRide]
    @user_id INT,
    @vehicle_id INT,
    @rental_start DATETIME,
    @rental_end DATETIME,
    @service_type_id INT,
    @ride_id INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        BEGIN TRANSACTION;



        -- Get vehicle's current point_id for pickup and dropoff
        DECLARE @pickup_point_id INT, @dropoff_point_id INT;
        SELECT TOP 1 @pickup_point_id = p.point_id
        FROM VEHICLE v
        INNER JOIN POINT p ON v.location.STEquals(p.GeoPoint) = 1
        WHERE v.vehicle_id = @vehicle_id;
        SET @dropoff_point_id = @pickup_point_id; -- Placeholder, will be updated later when rental ends

        -- Insert new ride (dropoff_point_id is placeholder, will be updated later)
        INSERT INTO RIDE (
            users_id, vehicle_id, ride_datetime_start, ride_datetime_end, service_type_id, status, service_id, pickup_point_id, dropoff_point_id
        )
        VALUES (
            @user_id, @vehicle_id, @rental_start, @rental_end, @service_type_id, 'InProgress', 7, @pickup_point_id, @dropoff_point_id
        );
        -- dropoff_point_id is a placeholder and will be updated at the end of the rental

        -- Get the new ride_id
        SET @ride_id = SCOPE_IDENTITY();

        -- Set vehicle as unavailable (is_active = 1 means unavailable for rent)
        UPDATE VEHICLE
        SET is_active = 1
        WHERE vehicle_id = @vehicle_id;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END
