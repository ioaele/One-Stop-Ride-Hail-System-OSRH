CREATE PROCEDURE [eioann09].[UpdateUserGender]
@genderNEW CHAR(1),
@users_id INT
AS 
BEGIN 
UPDATE USERS
SET gender=@genderNEW
WHERE users_id=@users_id
END