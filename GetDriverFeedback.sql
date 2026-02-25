CREATE PROCEDURE GetDriverFeedback
    @driver_id INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        F.rating,
        F.comments,
        GF.users_id AS reviewer_user_id,       
        R.ride_datetime_start,
        R.ride_datetime_end
    FROM GIVEN_FEEDBACK GF
    INNER JOIN FEEDBACK F ON GF.feedback_id = F.feedback_id
    INNER JOIN RIDE R ON GF.ride_id = R.ride_id
    WHERE GF.from_who = 0                 
      AND GF.driver_id = @driver_id;       
END;
GO
