CREATE PROCEDURE [eioann09].[InsertIntoDriverDocType]
AS
BEGIN 
INSERT INTO DRIVER_DOC_TYPE (d_doc_type_name)
VALUES
    (N'Ταυτότητα ή Διαβατήριο'),
    (N'Άδεια παραμονής'),
    (N'Άδεια οδήγησης'),
    (N'Πιστοποιητικό λευκού ποινικού μητρώου'),
    (N'Ιατρικό πιστοποιητικό'),
    (N'Ψυχολογικό πιστοποιητικό');


END