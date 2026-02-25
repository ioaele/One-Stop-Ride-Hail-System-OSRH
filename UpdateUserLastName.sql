CREATE PROCEDURE [eioann09].[UpdateUserLastName]
@last_nameNEW NVARCHAR(20),
@users_id INT 

AS 
BEGIN 
UPDATE USERS
SET last_name=@last_nameNEW
WHERE users_id=@users_id
END

