CREATE PROCEDURE [eioann09].[DriversWithRidesAndTotalEarningsMONTHLY ]


AS BEGIN
   DECLARE @current_year INT = YEAR(GETDATE());

SELECT  MONTH(R.[ride_datetime_end])  AS [ride month],U.first_name,U.last_name, COUNT(R.ride_id) AS [total rides], SUM(R.price) AS [total earning]
FROM DRIVER D, USERS U,RIDE R,VEHICLE V
WHERE R.vehicle_id=V.vehicle_id AND V.driver_id=D.driver_id AND D.users_id= U.users_id AND YEAR(R.[ride_datetime_end])=@current_year AND R.status ='C'
    GROUP BY MONTH(R.[ride_datetime_end]),U.first_name,U.last_name
    ORDER BY MONTH(R.[ride_datetime_end]),U.last_name,U.first_name

END 

