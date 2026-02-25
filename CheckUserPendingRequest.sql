CREATE PROCEDURE [eioann09].[CheckUserPendingRequest]
    @users_id INT,
    @has_pending BIT OUTPUT,
    @ride_request_id INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    SELECT TOP 1 
        @has_pending = 1,
        @ride_request_id = ride_request_id
    FROM RIDEREQUEST 
    WHERE users_id = @users_id AND status = 'Pending'
    ORDER BY request_time DESC;
    
    IF @ride_request_id IS NULL
    BEGIN
        SET @has_pending = 0;
    END
END
