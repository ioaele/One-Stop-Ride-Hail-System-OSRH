-- Stored Procedure to create a new point with all attributes
-- Checks for existing points using geography proximity
-- Returns existing point_id if duplicate found, creates new otherwise

CREATE PROCEDURE CreatePoint
    @latitude FLOAT,
    @longitude FLOAT,
    @parish NVARCHAR(60) = NULL,
    @providence NVARCHAR(170) = NULL,
    @postcode NVARCHAR(10) = NULL,
    @country NVARCHAR(40) = NULL,
    @city NVARCHAR(40) = NULL,
    @radius DECIMAL(4,2) = NULL,
    @start BIT = 0,  -- 0 = pickup, 1 = dropoff
    @point_id INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @geopoint GEOGRAPHY;
    DECLARE @duplicate_threshold FLOAT = 1; -- meters tolerance for duplicate detection
    
    BEGIN TRY
        BEGIN TRANSACTION;
        
        -- Create geography point
        SET @geopoint = geography::Point(@latitude, @longitude, 4326);
        
        -- Check if a point already exists at this location (within threshold)
        SELECT TOP 1 @point_id = point_id
        FROM POINT
        WHERE GeoPoint.STDistance(@geopoint) <= @duplicate_threshold
        ORDER BY GeoPoint.STDistance(@geopoint);
        
        -- If duplicate found, return existing point_id
        IF @point_id IS NOT NULL
        BEGIN
            COMMIT TRANSACTION;
            RETURN;
        END
        
        -- No duplicate found, create new point
        -- Get next available point_id
        SELECT @point_id = ISNULL(MAX(point_id), 0) + 1
        FROM POINT;
        
        -- Insert the point with all attributes
        INSERT INTO POINT (
            point_id,
            parish,
            providence,
            postcode,
            country,
            city,
            radius,
            GeoPoint,
            start
        )
        VALUES (
            @point_id,
            @parish,
            @providence,
            @postcode,
            @country,
            @city,
            @radius,
            @geopoint,
            @start
        );
        
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO
