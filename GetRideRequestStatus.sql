CREATE PROCEDURE [eioann09].[GetRideRequestStatus]
    @users_id INT,
    @status VARCHAR(50) OUTPUT,
    @response_time DATETIME OUTPUT,
    @ride_id INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @request_status VARCHAR(50);
    
    -- Check for pending request first
    SELECT TOP 1 
        @request_status = status,
        @response_time = response_time
    FROM RIDEREQUEST 
    WHERE users_id = @users_id AND status = 'Pending'
    ORDER BY request_time DESC;
    
    -- If no pending, get most recent request
    IF @request_status IS NULL
    BEGIN
        SELECT TOP 1 
            @request_status = status,
            @response_time = response_time
        FROM RIDEREQUEST 
        WHERE users_id = @users_id
        ORDER BY request_time DESC;
    END
    
    -- Return 'none' if no request found
    IF @request_status IS NULL
    BEGIN
        SET @status = 'none';
        RETURN;
    END
    
    -- Map database status to return status
    IF @request_status = 'Accepted' OR @request_status = 'Confirmed'
    BEGIN
        SET @status = 'accepted';
        
        -- Get ride_id if exists
        SELECT TOP 1 @ride_id = ride_id 
        FROM RIDE 
        WHERE users_id = @users_id 
        ORDER BY ride_datetime_start DESC;
    END
    ELSE IF @request_status = 'Cancelled' OR @request_status = 'Declined'
    BEGIN
        SET @status = 'cancelled';
    END
    ELSE IF @request_status = 'Pending'
    BEGIN
        SET @status = 'pending';
    END
    ELSE
    BEGIN
        SET @status = @request_status;
    END
END
