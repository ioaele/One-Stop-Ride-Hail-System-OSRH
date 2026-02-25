CREATE PROCEDURE [eioann09].[UpdateUserPassword]
@passwordNEW NVARCHAR(255),
@users_id INT
AS 

DECLARE @password_hashedNEW NVARCHAR(255);
SET @password_hashedNEW=HASHBYTES('SHA2_256', @passwordNEW);

BEGIN 
UPDATE USERS
SET password_hashed=@password_hashedNEW
WHERE users_id=@users_id
END