SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [eioann09].[SignUpUser]
    @last_name       NVARCHAR(100),
    @first_name      NVARCHAR(100),
    @gender          NVARCHAR(10),
    @password_hashed NVARCHAR(255),     
    @datebirth       DATE,
    @post_code       NVARCHAR(20),
    @city            NVARCHAR(100),
    @number          NVARCHAR(20),
    @street          NVARCHAR(100),
    @country         NVARCHAR(100),
    @username        NVARCHAR(100),
    @phone_number    NVARCHAR(30),
    @email           NVARCHAR(255)     
AS
BEGIN
    DECLARE @users_id INT;
    SET NOCOUNT ON;

    IF NOT EXISTS (
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
            @password_hashed, 
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

       
        INSERT INTO ROLE (type_r, users_id)
        VALUES ('passenger', @users_id);

      
        SELECT @users_id AS users_id;
    END
    ELSE
    BEGIN
        SET @users_id = NULL;
        SELECT @users_id AS users_id;  
    END
END;
GO
