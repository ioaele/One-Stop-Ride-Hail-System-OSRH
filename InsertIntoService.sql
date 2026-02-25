CREATE PROCEDURE [eioann09].[InsertIntoService]
AS
BEGIN
INSERT INTO SERVICE (service_type)
VALUES
    (N'όχημα με οδηγό'),
    (N'όχημα χωρίς οδηγό'),
    (N'όχημα χωρίς οδηγό στη θέση του χρήστη'),
    (N'αυτόνομο όχημα'),
    (N'Μίνι βαν για μεταφορά φορτίων');
END