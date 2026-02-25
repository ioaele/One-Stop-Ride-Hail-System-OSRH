CREATE PROCEDURE [eioann09].[InsertIntoServiceType]
AS
BEGIN

    INSERT INTO SERVICE_TYPE (ride_type)
VALUES
    (N'Απλή διαδρομή επιβάτη'),
    (N'Πολυτελής διαδρομή επιβάτη'),
    (N'Μεταφορά ελαφριού οικιακού φορτίου'),
    (N'Μεταφορά μεγάλου οικιακού φορτίου'),
    (N'Μεταφορά με ενδιάμεση σημεία');

END