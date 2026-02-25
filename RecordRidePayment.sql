CREATE PROCEDURE [eioann09].[RecordRidePayment]
    @ride_id INT
AS
BEGIN

    DECLARE
        @service_type_id INT,
        @vehicle_id      INT,
        @ride_date       DATE,
        @start_time      DATETIME,
        @end_time        DATETIME,
        @duration_min    INT,
        @base_fare       DECIMAL(6,2),
        @per_min         DECIMAL(6,2),
        @min_price       DECIMAL(6,2),
        @peak_multiplier DECIMAL(4,2) = 1.0,
        @raw_price       DECIMAL(10,2),
        @final_price     DECIMAL(10,2);

    -- 1. Παίρνω στοιχεία της διαδρομής
    SELECT 
        @service_type_id = R.service_type_id,
        @vehicle_id      = R.vehicle_id,
        @ride_date       = R.[ride_datetime_end],
        @start_time      = R.ride_datetime_start,
        @end_time        = R.ride_datetime_end
    FROM [eioann09].[RIDE] R
    WHERE R.ride_id = @ride_id;

    -- Αν δεν έχει τελικό χρόνο, δεν μπορούμε να χρεώσουμε
    IF @end_time IS NULL
    BEGIN
        RETURN;
    END;

    -- 2. Υπολογίζω διάρκεια σε λεπτά
    SET @duration_min = DATEDIFF(MINUTE, @start_time, @end_time);

    IF @duration_min < 0
        SET @duration_min = 0;

    -- 3. Παίρνω βασικούς κανόνες τιμολόγησης από SERVICE_TYPE
    SELECT 
        @base_fare = ST.base_fare,
        @per_min   = ST.per_min,
        @min_price = ST.min_price
    FROM [eioann09].[SERVICE_TYPE] ST
    WHERE ST.service_type_id = @service_type_id;

    -- 4. Απλός κανόνας "ζήτησης" ανά ώρα (peak hours)
    -- π.χ. 07:00-09:00 και 16:00-19:00 → 1.5x
    IF (DATEPART(HOUR, @start_time) BETWEEN 7 AND 9)
        OR (DATEPART(HOUR, @start_time) BETWEEN 16 AND 19)
    BEGIN
        SET @peak_multiplier = 1.5;
    END
    ELSE
    BEGIN
        SET @peak_multiplier = 1.0;
    END

    -- 5. Υπολογίζω βασική τιμή
    SET @raw_price = @base_fare + (@per_min * @duration_min);

    -- 6. Εφαρμόζω multiplier ζήτησης
    SET @final_price = @raw_price * @peak_multiplier;

    -- 7. Εφαρμόζω ελάχιστη τιμή
    IF @final_price < @min_price
        SET @final_price = @min_price;

    -- 8. Γράφω την τιμή στο RIDE
    UPDATE [eioann09].[RIDE]
    SET price = @final_price
    WHERE ride_id = @ride_id;
END;


