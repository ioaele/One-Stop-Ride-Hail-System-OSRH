 CREATE PROCEDURE [eioann09].[UpdateUserPhoneNumber]
@phone_numberNEW NVARCHAR(100),
@users_id INT
AS 

 IF NOT EXISTS ( -- elegxos an iparxi xristis me afto to tilefono
        SELECT 1
        FROM USERS
        WHERE phone_number = @phone_numberNEW
    )
    BEGIN
     
       UPDATE USERS 
       SET phone_number = @phone_numberNEW
       WHERE users_id=@users_id
END

       ELSE

       BEGIN
           RAISERROR('This phone_number already exists', 16, 1);
    END
