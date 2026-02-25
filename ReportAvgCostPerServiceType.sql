CREATE PROCEDURE [eioann09].[ReportAvgCostPerServiceTypeMETAKSIdates]
 @start_date DATE,
 @end_date DATE,
@ride_type NVARCHAR(100),
@parish NVARCHAR(60),
@providence NVARCHAR(170),
@postcode NVARCHAR(10),
@country NVARCHAR(40),
@city NVARCHAR(40),
@radius DECIMAL(4,2),
@latitude1 DECIMAL(4,2),
@longitude1 DECIMAL(4,2),
@latitude2 DECIMAL(4,2),
@longitude2 DECIMAL(4,2),
@start BIT,
@end BIT 
 
 AS
BEGIN

    -- START LOCATION FILTERS
    IF (@start = 1)
    BEGIN

        IF (@ride_type IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            WHERE ST.ride_type       = @ride_type
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@parish IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.parish            = @parish
              AND RP.start_end        = 0            -- start
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@providence IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.providence        = @providence
              AND RP.start_end        = 0
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@postcode IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.postcode         = @postcode
              AND RP.start_end       = 0
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@country IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.country          = @country
              AND RP.start_end       = 0
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@city IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.city             = @city
              AND RP.start_end       = 0
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@radius IS NOT NULL AND @latitude1 IS NOT NULL AND @longitude1 IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.radius           = @radius
              AND P.latitude         = @latitude1
              AND P.longitude        = @longitude1
              AND RP.start_end       = 0
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@latitude1 IS NOT NULL AND @longitude1 IS NOT NULL
            AND @latitude2 IS NOT NULL AND @longitude2 IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP1  ON R.ride_id        = RP1.ride_id
            JOIN POINT P1        ON RP1.point_id     = P1.point_id
            JOIN RIDE_POINT RP2  ON R.ride_id        = RP2.ride_id
            JOIN POINT P2        ON RP2.point_id     = P2.point_id
            WHERE P1.latitude        = @latitude1
              AND P1.longitude       = @longitude1
              AND P2.latitude        = @latitude2
              AND P2.longitude       = @longitude2
              AND RP1.start_end      = 0
              AND RP2.start_end      = 0
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

    END  -- IF(@start = 1)



    -- END LOCATION FILTERS
    IF (@end = 1)
    BEGIN

        IF (@ride_type IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            WHERE ST.ride_type       = @ride_type
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@parish IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.parish            = @parish
              AND RP.start_end        = 1            -- end
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@providence IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.providence        = @providence
              AND RP.start_end        = 1
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@postcode IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.postcode         = @postcode
              AND RP.start_end       = 1
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@country IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.country          = @country
              AND RP.start_end       = 1
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@city IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.city             = @city
              AND RP.start_end       = 1
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@radius IS NOT NULL AND @latitude1 IS NOT NULL AND @longitude1 IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP   ON R.ride_id        = RP.ride_id
            JOIN POINT P         ON RP.point_id      = P.point_id
            WHERE P.radius           = @radius
              AND P.latitude         = @latitude1
              AND P.longitude        = @longitude1
              AND RP.start_end       = 1
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

        IF (@latitude1 IS NOT NULL AND @longitude1 IS NOT NULL
            AND @latitude2 IS NOT NULL AND @longitude2 IS NOT NULL)
        BEGIN
            SELECT  AVG(R.price)           AS [average cost],
                    COUNT(R.ride_id)       AS [total rides],
                    ST.ride_type
            FROM RIDE R
            JOIN SERVICE_TYPE ST ON R.service_type_id = ST.service_type_id
            JOIN RIDE_POINT RP1  ON R.ride_id        = RP1.ride_id
            JOIN POINT P1        ON RP1.point_id     = P1.point_id
            JOIN RIDE_POINT RP2  ON R.ride_id        = RP2.ride_id
            JOIN POINT P2        ON RP2.point_id     = P2.point_id
            WHERE P1.latitude        = @latitude1
              AND P1.longitude       = @longitude1
              AND P2.latitude        = @latitude2
              AND P2.longitude       = @longitude2
              AND RP1.start_end      = 1
              AND RP2.start_end      = 1
              AND R.ride_datetime_end >= @start_date
              AND R.ride_datetime_end <= @end_date
            GROUP BY ST.service_type_id, ST.ride_type
            ORDER BY ST.ride_type;
        END

    END  -- IF(@end = 1)

END;
GO
