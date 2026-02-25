SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [eioann09].[ForgotPassword]
    @username NVARCHAR(50),
    @email    NVARCHAR(100),
    @password NVARCHAR(255)  
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE USERS
    SET password_hashed = @password
    WHERE email = @email
      AND username = @username;
END;
GO
