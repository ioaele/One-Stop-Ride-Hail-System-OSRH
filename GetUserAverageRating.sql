CREATE PROCEDURE GetUserAverageRating
    @user_id INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        AVG(CAST(F.rating AS FLOAT)) AS average_rating,
        COUNT(*) AS total_reviews
    FROM GIVEN_FEEDBACK GF
    INNER JOIN FEEDBACK F ON GF.feedback_id = F.feedback_id
    INNER JOIN RIDE R ON GF.ride_id = R.ride_id
    WHERE GF.from_who = 1
      AND R.users_id = @user_id;
END;
GO
