SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE   PROCEDURE [eioann09].[insertDriverDoc]
    @driver_id          INT NULL,
    @users_id           INT,
    @d_doc_type_id       INT,  
    @doc_code           NVARCHAR(50),
    @d_doc_publish_date DATE,
    @d_doc_ex_date      DATE = NULL,
    @image_pdf          NVARCHAR(1000)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @d_doc_type_id INT;

  
    INSERT INTO [eioann09].[DRIVER_DOC]
    (
        doc_code,
        d_doc_publish_date,
        d_doc_ex_date,
        image_pdf,
        d_doc_type_id,
        driver_id,
        users_id
    )
    VALUES
    (
        @doc_code,
        @d_doc_publish_date,
        @d_doc_ex_date,
        @image_pdf,
        @d_doc_type_id,
        @driver_id,
        @users_id
    );
END;
GO
