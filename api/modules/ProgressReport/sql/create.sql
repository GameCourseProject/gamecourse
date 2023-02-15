CREATE TABLE IF NOT EXISTS progress_report_config(
    course                      int unsigned PRIMARY KEY,
    isEnabled                   boolean NOT NULL DEFAULT FALSE,
    frequency                   varchar(50) DEFAULT '*/10 * * * *',

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS progress_report(
    course                      int unsigned PRIMARY KEY,
    seqNr                       int unsigned NOT NULL,
    reportsSent                 int unsigned DEFAULT 0,
    periodStart                 TIMESTAMP,
    periodEnd                   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateSent                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS progress_report_history(
    course              int unsigned PRIMARY KEY,
    user                int unsigned NOT NULL,
    seqNr               int unsigned NOT NULL,
    totalXP             int unsigned DEFAULT 0,
    periodXP            int unsigned DEFAULT 0,
    diffXP              int NOT NULL DEFAULT 0,
    timeLeft            int unsigned,
    prediction          int unsigned DEFAULT NULL,
    pieChart            TEXT,
    areaChart           TEXT,
    emailSend           TEXT,
    dateSent            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(user) REFERENCES course_user(id) ON DELETE CASCADE
);