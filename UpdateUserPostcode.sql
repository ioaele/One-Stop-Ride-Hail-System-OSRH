CREATE PROCEDURE [eioann09].[UpdateUserPostcode]
@post_codeNEW NVARCHAR(15),
@users_id INT
AS 

BEGIN 
UPDATE USERS
SET post_code=@post_codeNEW
WHERE users_id=@users_id
END