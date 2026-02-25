SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [eioann09].[LogInUser]
    @users_id  INT
AS
BEGIN
    SET NOCOUNT ON;


    IF @users_id IS NOT NULL -- an einai signed up
    BEGIN
        UPDATE USERS 
        SET last_login=SYSDATETIME()
        WHERE @users_id=users_id
    END
   
END;
GO
