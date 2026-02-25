SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [eioann09].[CreateSystemOperator]
    @SO_username NVARCHAR(100),  
    @SA_users_id INT             
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @SO_users_id INT;

    SELECT @SO_users_id = U.users_id
    FROM [eioann09].[USERS] AS U
    WHERE U.username = @SO_username;

   
    IF (@SO_users_id IS NULL)
    BEGIN
        RAISERROR('The requested S.O is not a sign up user.', 16, 1);
        RETURN;
    END;

  
    IF EXISTS (
        SELECT 1
        FROM [eioann09].[ROLE] AS R
        WHERE R.users_id = @SO_users_id
          AND R.type_r   = N'System Operator'
    )
    BEGIN
        RAISERROR('User is already a System Operator.', 16, 1);
        RETURN;
    END;

    IF NOT EXISTS (SELECT 1 FROM ROLE WHERE @SO_users_id=users_id)
    BEGIN
    INSERT INTO [eioann09].[ROLE] (type_r, users_id)
    VALUES (N'System Operator', @SO_users_id);
    END
    ELSE 
    BEGIN
    UPDATE ROLE
    SET type_r=N'System Operator'
    WHERE @SO_users_id=users_id
    END
    PRINT('Successful operation.');
END;
GO
