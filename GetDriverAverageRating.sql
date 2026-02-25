CREATE PROCEDURE GetDriverAverageRating
    @driver_id INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        AVG(CAST(F.rating AS FLOAT)) AS average_rating,
        COUNT(*) AS total_reviews
    FROM GIVEN_FEEDBACK GF
    INNER JOIN FEEDBACK F ON GF.feedback_id = F.feedback_id
    WHERE GF.from_who = 0 
      AND GF.driver_id = @driver_id;
END;
GO
