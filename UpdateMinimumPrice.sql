CREATE PROCEDURE [eioann09].[UpdateMinimumPrice]
@min_priceNEW DECIMAL (6,2),
@ride_type NVARCHAR(100)



AS 
BEGIN 
UPDATE SERVICE_TYPE 
SET min_price =@min_priceNEW
WHERE ride_type=@ride_type

END