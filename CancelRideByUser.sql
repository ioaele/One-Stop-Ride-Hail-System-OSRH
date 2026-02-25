CREATE PROCEDURE CancelRideByUser
(
    @users_id INT
)
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        --------------------------------------------------------
        -- 1. Check if user has a pending ride request
        --------------------------------------------------------
        IF NOT EXISTS (SELECT 1 FROM RIDEREQUEST WHERE users_id = @users_id AND status = 'Pending')
        BEGIN
            RAISERROR('No pending ride request found for this user.', 16, 1);
            ROLLBACK TRANSACTION;
            RETURN;
        END;

        --------------------------------------------------------
        -- 2. Delete the pending ride request
        --------------------------------------------------------
        DELETE FROM RIDEREQUEST 
        WHERE users_id = @users_id 
        AND status = 'Pending';

        COMMIT TRANSACTION;
    END TRY

    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO
