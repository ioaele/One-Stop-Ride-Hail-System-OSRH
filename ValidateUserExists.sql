CREATE PROCEDURE [eioann09].[ValidateUserExists]
    @users_id INT,
    @exists BIT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    IF EXISTS (SELECT 1 FROM USERS WHERE users_id = @users_id)
        SET @exists = 1;
    ELSE
        SET @exists = 0;
END
