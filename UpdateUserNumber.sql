CREATE PROCEDURE [eioann09].[UpdateUserNumber]
@numberNEW NVARCHAR(10),
@users_id INT
AS 

BEGIN 
UPDATE USERS
SET number=@numberNEW
WHERE users_id=@users_id
END