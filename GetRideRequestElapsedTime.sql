CREATE PROCEDURE [eioann09].[GetRideRequestElapsedTime]
    @users_id INT,
    @elapsed_seconds INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @request_time DATETIME;
    
    -- Get the request_time for the user's pending request
    SELECT TOP 1 @request_time = request_time
    FROM RIDEREQUEST
    WHERE users_id = @users_id 
      AND status = 'Pending'
    ORDER BY request_time DESC;
    
    -- Calculate elapsed seconds
    IF @request_time IS NOT NULL
    BEGIN
        SET @elapsed_seconds = DATEDIFF(SECOND, @request_time, GETDATE());
    END
    ELSE
    BEGIN
        SET @elapsed_seconds = 0;
    END
END
