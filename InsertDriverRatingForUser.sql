CREATE PROCEDURE InsertDriverRatingForUser
    @driver_id INT,      
    @user_id INT,       
    @ride_id INT,
    @rating INT,
    @comments NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRAN;

        
        INSERT INTO FEEDBACK (comments, rating)
        VALUES (@comments, @rating);

        DECLARE @feedback_id INT = SCOPE_IDENTITY();

        
        INSERT INTO GIVEN_FEEDBACK (users_id, feedback_id, driver_id, ride_id, is_driver)
        VALUES (@driver_id, @feedback_id, NULL, @ride_id, 1);

        COMMIT;
    END TRY
    BEGIN CATCH
        ROLLBACK;
        THROW;
    END CATCH
END;
GO
