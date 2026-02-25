CREATE PROCEDURE [eioann09].[UpdateUserCountry]
@countryNEW NVARCHAR(30),
@users_id INT
AS 

BEGIN 
UPDATE USERS
SET country=@countryNEW
WHERE users_id=@users_id
END