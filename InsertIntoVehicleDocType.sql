CREATE PROCEDURE [eioann09].[InsertIntoVehicleDocType]
AS
BEGIN 
INSERT INTO VEHICLE_DOC_TYPE (v_doc_type)
VALUES
    (N'Άδεια κυκλοφορίας οχήματος'),
    (N'Πιστοποιητικό ΜΟΤ'),
    (N'Πιστοποιητικό Ταξινόμησης Οχήματος');


END