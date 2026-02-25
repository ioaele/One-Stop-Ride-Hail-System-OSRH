CREATE TABLE [forma4].[USER_SIGNUPS] (
    [users_id]        INT            IDENTITY (1, 1) NOT NULL,
    [username]        VARCHAR (50)   NOT NULL,
    [First Name]      NVARCHAR (30)  NULL,
    [Last Name]       NVARCHAR (30)  NULL,
    [email]           VARCHAR (50)   NOT NULL,
    [password_hashed] VARBINARY (64) NOT NULL,
    [time_create]     DATETIME       DEFAULT (sysdatetime()) NOT NULL,
    [phone_number]    VARCHAR (30)   NULL,
    [datebirth]       DATE           NOT NULL,
    PRIMARY KEY CLUSTERED ([users_id] ASC),
    UNIQUE NONCLUSTERED ([email] ASC),
    UNIQUE NONCLUSTERED ([phone_number] ASC),
    UNIQUE NONCLUSTERED ([username] ASC)
);

CREATE TABLE [forma4].[LOGIN] (
    [login_id]    INT           IDENTITY (1, 1) NOT NULL,
    [login_time]  DATETIME      NOT NULL,
    [browser]     VARCHAR (100) NULL,
    [ip_address]  VARCHAR (45)  NULL,
    [logout_time] DATETIME      NULL,
    [users_id]    INT           NOT NULL,
    PRIMARY KEY CLUSTERED ([login_id] ASC),
    CONSTRAINT [fk_login_user_signup] FOREIGN KEY ([users_id]) REFERENCES [forma4].[USER_SIGNUPS] ([users_id])
);


CREATE TABLE [forma4].[SESSION] (
    [session_id] INT          IDENTITY (1, 1) NOT NULL,
    [level]      VARCHAR (20) NOT NULL,
    [time_start] DATETIME     NOT NULL,
    [time_end]   DATETIME     NOT NULL,
    [status]     VARCHAR (20) NOT NULL, -- open or closed
    [date]       DATE         NOT NULL,
    [capacity]   INT          NOT NULL,
    PRIMARY KEY CLUSTERED ([session_id] ASC)
);

CREATE TABLE [forma4].[BOOKING] (
    [booking_id]   INT          IDENTITY (1, 1) NOT NULL,
    [time_created] DATETIME     DEFAULT (getdate()) NOT NULL,
    [status]       VARCHAR (20) NOT NULL, 
    [users_id]     INT          NOT NULL,
    [session_id]   INT          NOT NULL,
    PRIMARY KEY CLUSTERED ([booking_id] ASC),
    CONSTRAINT [fk_bookings_session] FOREIGN KEY ([session_id]) REFERENCES [forma4].[SESSION] ([session_id]) ON DELETE CASCADE,
    CONSTRAINT [fk_bookings_user] FOREIGN KEY ([users_id]) REFERENCES [forma4].[USER_SIGNUPS] ([users_id]) ON DELETE CASCADE
);

