CREATE PROCEDURE [eioann09].[UpdatePricePerMin]
@per_minNEW DECIMAL (6,2),
@ride_type NVARCHAR(100)



AS 
BEGIN 
UPDATE SERVICE_TYPE 
SET per_min =@per_minNEW
WHERE ride_type=@ride_type

END