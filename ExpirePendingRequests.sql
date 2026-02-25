CREATE PROCEDURE ExpirePendingRequests
(
    @timeout_seconds INT = 120
)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @now DATETIME = GETDATE();

    ------------------------------------------------------------
    -- Step 1: Expire all pending requests older than timeout
    ------------------------------------------------------------
    UPDATE RideRequest
    SET status = 'Expired',
        response_time = @now
    WHERE status = 'Pending'
      AND DATEADD(SECOND, @timeout_seconds, request_time) < @now;


    ------------------------------------------------------------
    -- Step 2: Build temporary table for rides without pending
    ------------------------------------------------------------
    IF OBJECT_ID('tempdb..#ridesWithoutPending') IS NOT NULL
        DROP TABLE #ridesWithoutPending;

    CREATE TABLE #ridesWithoutPending (
        ride_id INT PRIMARY KEY
    );

    INSERT INTO #ridesWithoutPending (ride_id)
    SELECT ride_id
    FROM RideRequest
    GROUP BY ride_id
    HAVING 
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) = 0
        AND SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) = 0;
        -- ignore accepted rides


    ------------------------------------------------------------
    -- Step 3: Loop through each ride in this temp table
    ------------------------------------------------------------
    DECLARE @ride_id INT;

    DECLARE rideCursor CURSOR LOCAL FAST_FORWARD FOR 
        SELECT ride_id FROM #ridesWithoutPending;

    OPEN rideCursor;
    FETCH NEXT FROM rideCursor INTO @ride_id;

    WHILE @@FETCH_STATUS = 0
    BEGIN
        DECLARE @radius INT, @attempts INT;

        SELECT 
            @radius = search_radius,
            @attempts = dispatch_attempts
        FROM Ride
        WHERE ride_id = @ride_id;


        --------------------------------------------------------
        -- Step 3.1: Max attempts?
        --------------------------------------------------------
        IF @attempts >= 3
        BEGIN
            UPDATE Ride
            SET status = 'NoDriversAvailable'
            WHERE ride_id = @ride_id;
        END
        ELSE
        BEGIN
            --------------------------------------------------------
            -- 3.2 Expand radius & redispatch
            --------------------------------------------------------
            SET @radius = @radius * 2;
            SET @attempts = @attempts + 1;

            UPDATE Ride
            SET search_radius = @radius,
                dispatch_attempts = @attempts
            WHERE ride_id = @ride_id;

            DECLARE @vehicle_type_requested_id INT;

            -- find what type the user requested
            SELECT TOP 1 
                @vehicle_type_requested_id = vehicle_type_requested_id
            FROM RideRequest
            WHERE ride_id = @ride_id
            ORDER BY request_time ASC;

            EXEC sp_DispatchRideRequests
                @ride_id = @ride_id,
                @vehicle_type_requested_id = @vehicle_type_requested_id,
                @radius_meters = @radius;
        END

        FETCH NEXT FROM rideCursor INTO @ride_id;
    END

    CLOSE rideCursor;
    DEALLOCATE rideCursor;

END;
GO
