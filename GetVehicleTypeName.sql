-- Stored Procedure to get vehicle_type name by vehicle_type_id
-- Replaces: SELECT vehicle_type FROM VEHICLE_TYPE WHERE vehicle_type_id = ?

ALTER PROCEDURE GetVehicleTypeName
    @vehicle_type_id INT
   
AS
BEGIN
    SET NOCOUNT ON;
    
    SELECT vehicle_type
    FROM VEHICLE_TYPE
    WHERE vehicle_type_id = @vehicle_type_id;
END;
GO
