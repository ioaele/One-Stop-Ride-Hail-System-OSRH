CREATE OR ALTER PROCEDURE [eioann09].[GetDistanceToDropoff]
    @driver_id INT,
    @driver_point NVARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    
    SELECT TOP 1 RR.dropoff_point_id,
           P.GeoPoint.STDistance(geography::STGeomFromText(@driver_point, 4326)) AS distance_meters
    FROM RIDEREQUEST RR
    INNER JOIN POINT P ON RR.dropoff_point_id = P.point_id
    WHERE RR.driver_id = @driver_id AND RR.status IN ('Accepted', 'InProgress')
    ORDER BY CASE WHEN RR.status = 'InProgress' THEN 1 WHEN RR.status = 'Accepted' THEN 2 END;
END
GO
