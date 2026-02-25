-- Stored Procedure to get ride_type name by service_type_id
-- Replaces: SELECT ride_type FROM SERVICE_TYPE WHERE service_type_id = ?

CREATE PROCEDURE GetServiceTypeName
    @service_type_id INT,
    @ride_type NVARCHAR(100) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    SELECT @ride_type = ride_type
    FROM SERVICE_TYPE
    WHERE service_type_id = @service_type_id;
END;
GO
