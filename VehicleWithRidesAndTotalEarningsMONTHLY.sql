CREATE PROCEDURE [eioann09].[VehicleWithRidesAndTotalEarningsMONTHLY ]


AS BEGIN
   DECLARE @current_year INT = YEAR(GETDATE());

SELECT  MONTH(R.[ride_datetime_end])  AS [ride month],V.vehicle_type, COUNT(R.ride_id) AS [total rides], SUM(R.price) AS [total earning]
FROM RIDE R,VEHICLE V
WHERE R.vehicle_id=V.vehicle_id AND YEAR(R.[ride_datetime_end])=@current_year AND R.status ='C'
    GROUP BY MONTH(R.[ride_datetime_end]),V.vehicle_type
    ORDER BY MONTH(R.[ride_datetime_end]),V.vehicle_type

END 