CREATE PROCEDURE CreateAdmin
    @SA_users_id  INT   -- poios diaxiristis systhmatos tha ton kani 
AS
BEGIN
    SET NOCOUNT ON;

    INSERT INTO ROLE (type_r, users_id)
    VALUES (N'System Admin', @SA_users_id);
    PRINT ('Successful operation.')
END;
GO
