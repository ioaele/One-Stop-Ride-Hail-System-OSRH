CREATE  PROCEDURE [eioann09].[GetDriverLocations]
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        DL.driver_id,
        DL.location.STLat()  AS lat,
        DL.location.STLong() AS lng,
        DL.last_update
    FROM [eioann09].[DriverLocation] AS DL;
END;
GO
