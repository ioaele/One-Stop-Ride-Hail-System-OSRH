CREATE PROCEDURE [eioann09].[GetRidePrice]
    @ride_id INT
    AS 
    BEGIN

    IF ((SELECT price FROM RIDE WHERE ride_id=@ride_id) IS NOT NULL)

    SELECT price 
    FROM RIDE
    WHERE ride_id=@ride_id


    ELSE 
    RAISERROR ('The ride is not finished',16,1);

    END