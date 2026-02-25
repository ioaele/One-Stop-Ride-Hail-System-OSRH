 CREATE PROCEDURE [eioann09].[RightToBeForgottenRequest]
    @users_id INT -- userid 

AS
BEGIN 

DELETE  USERS
WHERE @users_id=users_id
END
