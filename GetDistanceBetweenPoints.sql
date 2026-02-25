CREATE OR ALTER PROCEDURE [eioann09].[GetDistanceBetweenPoints]
    @lat1 FLOAT,
    @lng1 FLOAT,
    @lat2 FLOAT,
    @lng2 FLOAT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @point1 GEOGRAPHY = geography::Point(@lat1, @lng1, 4326);
    DECLARE @point2 GEOGRAPHY = geography::Point(@lat2, @lng2, 4326);
    SELECT @point1.STDistance(@point2) AS distance_meters;
END
GO
