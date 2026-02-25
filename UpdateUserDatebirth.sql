CREATE PROCEDURE [eioann09].[UpdateUserDatebirth]
@datebirthNEW DATE,
@users_id INT
AS 

BEGIN 
UPDATE USERS
SET datebirth=@datebirthNEW
WHERE users_id=@users_id
END