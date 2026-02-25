--insertServiceTypeRide SP No user input
CREATE PROC insertServiceTypeRide
AS
BEGIN 
	INSERT INTO SERVICE_TYPE (ride_type) --Mono gia thn stili ride_type dioti to allo einai identity
	VALUES   
    (N'Απλή διαδρομή επιβάτη'),
    (N'Πολυτελής διαδρομή επιβάτη'),
    (N'Μεταφορά ελαφριού οικιακού φορτίου'),
    (N'Μεταφορά μεγάλου οικιακού φορτίου'),
    (N'Μεταφορά με ενδιάμεση σημεία');
END
