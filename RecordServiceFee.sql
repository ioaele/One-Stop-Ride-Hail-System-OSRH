CREATE PROCEDURE [eioann09].[RecordServiceFee]
    @ride_id INT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE 
        @price           DECIMAL(5,2),
        @service_fee_pct DECIMAL(5,2) = 15.00,  -- 15% προμήθεια OSRH
        @service_fee     DECIMAL(6,2),
        @driver_earnings DECIMAL(6,2);

    -- 1. Παίρνω την τιμή της διαδρομής
    SELECT @price = price
    FROM [eioann09].[RIDE]
    WHERE ride_id = @ride_id;

    -- Αν δεν υπάρχει price, δεν μπορώ να υπολογίσω fees
    IF @price IS NULL
    BEGIN
        RETURN;
    END

    -- 2. Υπολογίζω προμήθεια και καθαρό έσοδο
    SET @service_fee     = ROUND(@price * @service_fee_pct / 100.0, 2);
    SET @driver_earnings = @price - @service_fee;

    -- 3. Ενημερώνω τη διαδρομή
    UPDATE [eioann09].[RIDE]
    SET 
        service_fee     = @service_fee,
        driver_earnings = @driver_earnings
    WHERE ride_id = @ride_id;
END;
GO
