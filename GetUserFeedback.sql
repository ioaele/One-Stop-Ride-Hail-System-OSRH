CREATE PROCEDURE GetUserFeedback
    @user_id INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        F.feedback_id,
        F.rating,
        F.comments,
        GF.users_id AS reviewer_driver_id,   
        GF.ride_id,
        R.date AS ride_date
    FROM GIVEN_FEEDBACK GF
    INNER JOIN FEEDBACK F ON GF.feedback_id = F.feedback_id
    INNER JOIN RIDE R ON GF.ride_id = R.ride_id
    WHERE GF.is_driver = 1            
      AND R.users_id = @user_id;      
END;
GO
