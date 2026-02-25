SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [eioann09].[CreateSystemOperator]
    @SO_username      NVARCHAR(100),  -- to user id tou user p tha gini litourgos
    @SA_users_id  INT   -- poios diaxiristis systhmatos tha ton kani 
AS
BEGIN
    
    DECLARE @SO_users_id INT;

            SELECT @SO_users_id
                FROM USERS
                WHERE @SO_username=username

IF (@SO_users_id IS NULL)
 RAISERROR('The requested S.O is not a sign up user.', 16, 1);
 RETURN;

    -- an den einai idi litourgos sistimatos 
     IF EXISTS (
        SELECT 1
        FROM ROLE R ,USERS U
        WHERE username = @SO_username
          AND type_r   = N'System Operator' AND NOT type_r=N'driver' AND R.users_id=U.users_id
    )
    BEGIN
        RAISERROR('User is already a System Operator.', 16, 1);
        RETURN;
    END;


    INSERT INTO ROLE (type_r, users_id)
    VALUES (N'System Operator', @SO_users_id);
    PRINT ('Successful operation.')
END;
GO
