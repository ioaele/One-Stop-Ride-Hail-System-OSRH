CREATE PROCEDURE [eioann09].[MakeDriverOnline]
    @driver_users_id INT
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE DRIVER
    SET status = 'N'
    WHERE users_id = @driver_users_id;
END