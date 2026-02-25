CREATE PROCEDURE [eioann09].[UpdateUserCity]
@cityNEW NVARCHAR(30),
@users_id INT
AS 

BEGIN 
UPDATE USERS
SET city=@cityNEW
WHERE users_id=@users_id
END

