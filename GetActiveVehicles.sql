CREATE  PROCEDURE [eioann09].[GetActiveVehicles]
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        V.vehicle_id,
        V.license_plate,
        V.service_type_id,
        V.vehicle_type_id,
        V.is_active,
        V.location.STLat()  AS lat,
        V.location.STLong() AS lng
    FROM [eioann09].[VEHICLE] AS V
    WHERE v.is_active = 1
      AND v.location IS NOT NULL;
END;
GO
