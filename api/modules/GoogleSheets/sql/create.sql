CREATE TABLE IF NOT EXISTS googlesheets_config(
    course                      int unsigned PRIMARY KEY,
    key_                        TEXT DEFAULT NULL,
    clientId                    TEXT DEFAULT NULL,
    projectId                   TEXT DEFAULT NULL,
    authUri                     TEXT DEFAULT NULL,
    tokenUri                    TEXT DEFAULT NULL,
    authProvider                TEXT DEFAULT NULL,
    clientSecret                TEXT DEFAULT NULL,
    redirectUris                TEXT DEFAULT NULL,
    accessToken                 TEXT DEFAULT NULL,
    spreadsheetId               TEXT DEFAULT NULL,
    sheetsInfo                  TEXT DEFAULT NULL,
    frequency                   varchar(50) DEFAULT '*/10 * * * *',

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS googlesheets_status(
    course                      int unsigned PRIMARY KEY,
    isEnabled                   boolean DEFAULT FALSE,
    startedRunning              TIMESTAMP NULL DEFAULT NULL,
    finishedRunning             TIMESTAMP NULL DEFAULT NULL,
    isRunning                   boolean DEFAULT FALSE,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);