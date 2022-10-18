CREATE TABLE "logs" (
    "log_id" integer not null primary key autoincrement,
    "source" varchar(100) not null,
    "message" text not null,
    "level" integer NOT NULL default '400',
    "level_name" varchar(50) NOT NULL DEFAULT 'error',
    "context" text,
    "channel" varchar(100) DEFAULT NULL,
    "extra" text,
    "created_at" datetime
);
