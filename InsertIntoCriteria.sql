CREATE PROCEDURE [eioann09].[InsertIntoCriteria]
AS
BEGIN   
INSERT INTO CRITERIA (seats_c, luggage_weight_c, luggage_volume_c, service_type_id)
SELECT 
    4      AS seats_c,
    200    AS luggage_weight_c,   
    400    AS luggage_volume_c,  
    ST.service_type_id
FROM SERVICE_TYPE ST
WHERE ST.ride_type = N'Απλή διαδρομή επιβάτη';

-- 2) 
INSERT INTO CRITERIA (seats_c, luggage_weight_c, luggage_volume_c, service_type_id)
SELECT 
    4,
    250,
    450,
    ST.service_type_id
FROM SERVICE_TYPE ST
WHERE ST.ride_type = N'Πολυτελής διαδρομή επιβάτη';

-- 3)
INSERT INTO CRITERIA (seats_c, luggage_weight_c, luggage_volume_c, service_type_id)
SELECT 
    2,
    600,
    3000,
    ST.service_type_id
FROM SERVICE_TYPE ST
WHERE ST.ride_type = N'Μεταφορά ελαφριού οικιακού φορτίου';

-- 4) 
INSERT INTO CRITERIA (seats_c, luggage_weight_c, luggage_volume_c, service_type_id)
SELECT 
    2,
    1200,
    6000,
    ST.service_type_id
FROM SERVICE_TYPE ST
WHERE ST.ride_type = N'Μεταφορά μεγάλου οικιακού φορτίου';

-- 5) 
INSERT INTO CRITERIA (seats_c, luggage_weight_c, luggage_volume_c, service_type_id)
SELECT 
    4,
    150,
    350,
    ST.service_type_id
FROM SERVICE_TYPE ST
WHERE ST.ride_type = N'Μεταφορά με ενδιάμεσα σημεία (geofencing)';
 END