CREATE OR ALTER PROCEDURE [eioann09].[insertVehicleDoc]
    @vehicle_id         INT,
    @v_doc_type_id         NVARCHAR(50),  
    @v_doc_publish_date DATE,
    @v_doc_exp_date     DATE = NULL,
    @image_pdf          NVARCHAR(1000)
AS 
BEGIN
    SET NOCOUNT ON;

    

    INSERT INTO [eioann09].[VEHICLE_DOC]
    (
        v_doc_type_id,
        v_doc_exp_date,
        image_pdf,
        v_doc_publish_date,
        vehicle_id
    )
    VALUES
    (
        @v_doc_type_id,
        ISNULL(@v_doc_exp_date, @v_doc_publish_date), -- αν το θες always NOT NULL
        @image_pdf,
        @v_doc_publish_date,
        @vehicle_id
    );
END;
GO
