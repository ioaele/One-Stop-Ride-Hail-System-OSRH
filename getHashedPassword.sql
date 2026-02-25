SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [eioann09].[getHashedPassword]
@email NVARCHAR(100)

AS BEGIN

SELECT  users_id,username,password_hashed
FROM USERS
WHERE email=@email
END
GO
