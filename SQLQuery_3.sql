SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE   PROCEDURE [eioann09].[SignUpUser]
    @last_name       NVARCHAR(100),
    @first_name      NVARCHAR(100),
    @gender          NVARCHAR(10),
    @password        NVARCHAR(255),     
    @datebirth       DATE,
    @post_code       NVARCHAR(20),
    @city            NVARCHAR(100),
    @number          NVARCHAR(20),
    @street          NVARCHAR(100),
    @country         NVARCHAR(100),
    @username        NVARCHAR(100),
    @phone_number    NVARCHAR(30),
    @email           NVARCHAR(255),  
    @users_id        INT OUTPUT       
AS
BEGIN
    SET NOCOUNT ON; -- den emfanizei to x rows affected
    IF NOT EXISTS ( -- elegxos an iparxi o xristis idi 
        SELECT 1
        FROM USERS
        WHERE email = @email
           OR username = @username
           OR phone_number = @phone_number
    )
    BEGIN
     
        INSERT INTO USERS (
            last_name,
            first_name,
            gender,
            password_hashed,
            datebirth,
            post_code,
            city,
            number,
            street,
            country,
            username,
            phone_number,
            email
        )
        VALUES (
            @last_name,
            @first_name,
            @gender,
            HASHBYTES('SHA2_256', @password), 
            @datebirth,
            @post_code,
            @city,
            @number,
            @street,
            @country,
            @username,
            @phone_number,
            @email
        );


        SET @users_id = SCOPE_IDENTITY();

        INSERT INTO ROLE (type_r,users_id)
        SELECT
            'passenger',
            U.users_id
        FROM USERS U
        WHERE U.email        = @email
          AND U.username     = @username
          AND U.phone_number = @phone_number;


          PRINT('Succesful Signup');
    END
    ELSE
    BEGIN
    
        SET @users_id = NULL; -- einai idi eggegrammenos
    END
END;
GO
