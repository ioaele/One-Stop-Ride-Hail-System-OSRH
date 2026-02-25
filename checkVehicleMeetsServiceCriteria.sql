CREATE OR ALTER PROCEDURE [eioann09].[checkVehicleMeetsServiceCriteria]
    @service_type     NVARCHAR(100),
    @seats            INT,
    @luggage_weight   INT,
    @luggage_volume   DECIMAL(6,2),
    @meetsRequirements BIT OUTPUT,
    @errorMessage     NVARCHAR(500) OUTPUT,
    @service_type_id  INT OUTPUT,
    @criteria_id      INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    -- Initialize outputs
    SET @meetsRequirements = 0;
    SET @errorMessage      = NULL;
    SET @service_type_id   = NULL;
    SET @criteria_id       = NULL;

    -- Get service_type_id
    SELECT @service_type_id = service_type_id
    FROM [eioann09].[SERVICE_TYPE]
    WHERE ride_type = @service_type;

    IF @service_type_id IS NULL
    BEGIN
        SET @errorMessage = N'Invalid service type selected.';
        RETURN;
    END;

    -- Check if vehicle meets criteria
    SELECT TOP 1
        @criteria_id       = C.criteria_id,
        @meetsRequirements = 1
    FROM [eioann09].[CRITERIA] AS C
    WHERE C.service_type_id     = @service_type_id
      AND C.seats_c             <= @seats            -- at least seats
      AND C.luggage_weight_c    <= @luggage_weight   -- at least weight
      AND C.luggage_volume_c    <= @luggage_volume;  -- at least volume

    IF @meetsRequirements = 0
    BEGIN
        DECLARE @required_seats  INT,
                @required_weight INT,
                @required_volume DECIMAL(4,2);

        SELECT TOP 1
            @required_seats  = seats_c,
            @required_weight = luggage_weight_c,
            @required_volume = luggage_volume_c
        FROM [eioann09].[CRITERIA]
        WHERE service_type_id = @service_type_id;

        SET @errorMessage = 
               N'Vehicle does not meet criteria for ' + @service_type + N'. '
             + N'Required: ' + CAST(@required_seats AS NVARCHAR(10)) + N' seats, '
             + CAST(@required_weight AS NVARCHAR(10)) + N'kg luggage weight, '
             + CAST(@required_volume AS NVARCHAR(10)) + N'm³ luggage volume. '
             + N'Your vehicle: ' + CAST(@seats AS NVARCHAR(10)) + N' seats, '
             + CAST(@luggage_weight AS NVARCHAR(10)) + N'kg, '
             + CAST(@luggage_volume AS NVARCHAR(10)) + N'm³.';
    END;
END;
GO
