CREATE OR ALTER PROCEDURE [eioann09].[insertVehicleWithCriteria]
    @driver_id        INT,
    @service_type     NVARCHAR(100),
    @seats            INT,
    @vehicle_type     NVARCHAR(50),
    @license_plate    NVARCHAR(30),
    @luggage_volume   DECIMAL(4,2),      -- ταιριάζει με το table
    @photo_interior   NVARCHAR(MAX),
    @luggage_weight   INT,
    @photo_exterior   NVARCHAR(MAX),
    @vehicle_id       INT OUTPUT,
    @errorMessage     NVARCHAR(500) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @vehicle_type_id  INT,
            @service_type_id  INT,
            @criteria_id      INT,
            @meetsRequirements BIT,
            @criteriaError    NVARCHAR(500);

    SET @vehicle_id   = NULL;
    SET @errorMessage = NULL;

    -- 1. Βρες vehicle_type_id
    SELECT @vehicle_type_id = vehicle_type_id
    FROM [eioann09].[VEHICLE_TYPE]
    WHERE vehicle_type = @vehicle_type;

    IF @vehicle_type_id IS NULL
    BEGIN
        SET @errorMessage = N'Invalid vehicle type.';
        RETURN;
    END;

    -- 2. Έλεγξε κριτήρια & πάρε service_type_id / criteria_id (χωρίς _id input απ’ έξω)
    EXEC [eioann09].[checkVehicleMeetsServiceCriteria]
         @service_type      = @service_type,
         @seats             = @seats,
         @luggage_weight    = @luggage_weight,
         @luggage_volume    = @luggage_volume,
         @meetsRequirements = @meetsRequirements OUTPUT,
         @errorMessage      = @criteriaError OUTPUT,
         @service_type_id   = @service_type_id OUTPUT,
         @criteria_id       = @criteria_id OUTPUT;

    IF @meetsRequirements = 0
    BEGIN
        SET @errorMessage = @criteriaError;
        RETURN;
    END;

    -- 3. Κάνε insert στο VEHICLE
    INSERT INTO [eioann09].[VEHICLE]
    (
        seats,
        vehicle_type_id,
        luggage_volume,
        photo_interior,
        license_plate,
        luggage_weight,
        photo_exterior,
        service_type_id,
        criteria_id,
        driver_id,
        is_active
    )
    VALUES
    (
        @seats,
        @vehicle_type_id,
        @luggage_volume,
        @photo_interior,
        @license_plate,
        @luggage_weight,
        @photo_exterior,
        @service_type_id,
        @criteria_id,
        @driver_id,
        0
    );

    IF @@ROWCOUNT > 0
    BEGIN
        SET @vehicle_id   = SCOPE_IDENTITY();
        SET @errorMessage = NULL;
    END
    ELSE
    BEGIN
        SET @vehicle_id   = NULL;
        SET @errorMessage = N'Failed to insert vehicle.';
    END;
END;
GO
