 CREATE PROCEDURE [eioann09].[UpdateUserEmail]
@emailNEW NVARCHAR(100),
@users_id INT
AS 

 IF NOT EXISTS ( -- elegxos an iparxi xristis me afto to email
        SELECT 1
        FROM USERS
        WHERE email = @emailNEW
    )
    BEGIN
     
       UPDATE USERS 
       SET email = @emailNEW
       WHERE users_id=@users_id
END

       ELSE

       BEGIN
           RAISERROR('This email already exists', 16, 1);
    END
