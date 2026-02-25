CREATE PROCEDURE [eioann09].[UpdateUserFistName]
@first_nameNEW NVARCHAR(30),
@users_id INT
AS 
BEGIN 
UPDATE USERS
SET first_name=@first_nameNEW
WHERE users_id=@users_id
END