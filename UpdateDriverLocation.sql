CREATE OR ALTER PROCEDURE [eioann09].[UpdateDriverLocation]
(
    @driver_id INT,
    @latitude FLOAT,
    @longitude FLOAT
)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @newPoint GEOGRAPHY = geography::Point(@latitude, @longitude, 4326);
    DECLARE @now DATETIME = GETDATE();

    -- Throttle: Only update if last update was >= 4 seconds ago
    IF EXISTS (
        SELECT 1 FROM DriverLocation WHERE driver_id = @driver_id AND DATEDIFF(SECOND, last_update, @now) < 4
    )
    BEGIN
        RETURN;
    END

    MERGE DriverLocation AS target
    USING (SELECT @driver_id AS driver_id) AS src
    ON target.driver_id = src.driver_id
    WHEN MATCHED THEN
        UPDATE SET location = @newPoint, last_update = @now
    WHEN NOT MATCHED THEN
        INSERT (driver_id, location, last_update)
        VALUES (@driver_id, @newPoint, @now);
END;
GO

-- Create wrapper that accepts users_id (called by PHP files)
CREATE OR ALTER PROCEDURE [eioann09].[insertLocation]
(
    @users_id INT,
    @latitude FLOAT,
    @longitude FLOAT
)
AS
BEGIN
    DECLARE @driver_id INT;
    
    -- Get driver_id from users_id
    SELECT @driver_id = driver_id FROM DRIVER WHERE users_id = @users_id;
    
    IF @driver_id IS NULL
    BEGIN
        RAISERROR('Driver not found for this user.', 16, 1);
        RETURN;
    END
    
    -- Call the main procedure
    EXEC [eioann09].[UpdateDriverLocation] @driver_id, @latitude, @longitude;
END;
GO

-- Create alias for update_driver_location.php
CREATE OR ALTER PROCEDURE [eioann09].[updateLocation]
(
    @users_id INT,
    @latitude FLOAT,
    @longitude FLOAT
)
AS
BEGIN
    EXEC [eioann09].[insertLocation] @users_id, @latitude, @longitude;
END;
GO
