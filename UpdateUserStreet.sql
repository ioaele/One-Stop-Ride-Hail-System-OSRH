CREATE PROCEDURE [eioann09].[UpdateUserStreet]
@streetNEW NVARCHAR(30),
@users_id INT
AS 

BEGIN 
UPDATE USERS
SET street=@streetNEW
WHERE users_id=@users_id
END