CREATE PROCEDURE [eioann09].[ReportMaxAvgCostPerServiceType]

    AS 
    BEGIN 

    SELECT AVG(R.price) AS [average cost] , COUNT (R.ride_id) AS [total rides], ST.ride_type
    FROM RIDE R,SERVICE_TYPE ST 
    WHERE R.service_type_id=ST.service_type_id 
    GROUP BY ST.service_type_id,ST.ride_type
    ORDER BY AVG(R.price) DESC

    END