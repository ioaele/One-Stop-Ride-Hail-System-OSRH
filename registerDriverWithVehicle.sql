CREATE OR ALTER PROCEDURE [eioann09].[registerDriverWithVehicle]
    @driver_id       INT NULL,
    @users_id        INT,   -- προς το παρόν δεν το χρησιμοποιούμε εδώ, αλλά το κρατάω για μελλοντικά docs κλπ

    -- Vehicle info (χωρίς _id)
    @service_type    NVARCHAR(100),
    @seats           INT,
    @vehicle_type    NVARCHAR(50),
    @license_plate   NVARCHAR(30),
    @luggage_volume  DECIMAL(4,2),
    @luggage_weight  INT,
    @photo_interior  NVARCHAR(MAX),
    @photo_exterior  NVARCHAR(MAX),

    -- Outputs
    @success         BIT OUTPUT,
    @message         NVARCHAR(500) OUTPUT,
    @new_vehicle_id  INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRANSACTION;

    BEGIN TRY
        DECLARE @errorMessage NVARCHAR(500);

        -- Step 1: Insert vehicle + criteria check μέσα στο insertVehicleWithCriteria
        EXEC [eioann09].[insertVehicleWithCriteria]
             @driver_id       = @driver_id,
             @service_type    = @service_type,
             @seats           = @seats,
             @vehicle_type    = @vehicle_type,
             @license_plate   = @license_plate,
             @luggage_volume  = @luggage_volume,
             @photo_interior  = @photo_interior,
             @luggage_weight  = @luggage_weight,
             @photo_exterior  = @photo_exterior,
             @vehicle_id      = @new_vehicle_id OUTPUT,
             @errorMessage    = @errorMessage   OUTPUT;

        IF @new_vehicle_id IS NULL
        BEGIN
            SET @success = 0;
            SET @message = @errorMessage;
            ROLLBACK TRANSACTION;
            RETURN;
        END;

        -- Εδώ στο μέλλον μπορείς να καλέσεις insertDriverDoc / insertVehicleDoc
        -- χρησιμοποιώντας @driver_id, @users_id, @new_vehicle_id κ.λπ.

        COMMIT TRANSACTION;

        SET @success = 1;
        SET @message = N'Vehicle registered successfully! Vehicle ID: ' 
                       + CAST(@new_vehicle_id AS NVARCHAR(50));
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        SET @success        = 0;
        SET @new_vehicle_id = NULL;
        SET @message = N'Error: ' + ERROR_MESSAGE() 
                     + N' (Line: ' + CAST(ERROR_LINE() AS NVARCHAR(10)) + N')';
    END CATCH;
END;
GO
