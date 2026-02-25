CREATE PROCEDURE [eioann09].[UpdateServiceFee]
@service_feeNEW DECIMAL (6,2),
@ride_type NVARCHAR(100)



AS 
BEGIN 
UPDATE SERVICE_TYPE 
SET service_fee =@service_feeNEW
WHERE ride_type=@ride_type

END