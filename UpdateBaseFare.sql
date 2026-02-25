CREATE PROCEDURE [eioann09].[UpdateBaseFare]
@base_fareNEW DECIMAL (6,2),
@ride_type NVARCHAR(100)



AS 
BEGIN 
UPDATE SERVICE_TYPE 
SET base_fare =@base_fareNEW
WHERE ride_type=@ride_type

END