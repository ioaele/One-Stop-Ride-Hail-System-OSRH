USE [eioann09]
GO

-- Add driver_id column to RIDEREQUEST table
ALTER TABLE [eioann09].[RIDEREQUEST]
ADD driver_id INT NULL;
GO

-- Add foreign key constraint to DRIVER table
ALTER TABLE [eioann09].[RIDEREQUEST]
ADD CONSTRAINT FK_RIDEREQUEST_DRIVER
FOREIGN KEY (driver_id) REFERENCES [eioann09].[DRIVER](driver_id);
GO
