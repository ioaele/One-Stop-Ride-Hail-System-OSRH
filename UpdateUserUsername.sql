 CREATE PROCEDURE [eioann09].[UpdateUserUsername]
@usernameNEW NVARCHAR(50),
@users_id INT
AS 

 IF NOT EXISTS ( -- elegxos an iparxi xristis me afto to username
        SELECT 1
        FROM USERS
        WHERE username = @usernameNEW
    )
    BEGIN
     
       UPDATE USERS 
       SET username = @usernameNEW
       WHERE users_id=@users_id
END

       ELSE

       BEGIN
           RAISERROR('This username already exists', 16, 1);
    END
