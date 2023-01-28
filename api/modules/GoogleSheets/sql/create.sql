CREATE TABLE IF NOT EXISTS googlesheets_config(
    course                      int unsigned PRIMARY KEY,
    key_                        varchar(200) DEFAULT NULL,
    clientId                    TEXT DEFAULT NULL,
    projectId                   TEXT DEFAULT NULL,
    authUri                     TEXT DEFAULT NULL,
    tokenUri                    TEXT DEFAULT NULL,
    authProvider                TEXT DEFAULT NULL,
    clientSecret                TEXT DEFAULT NULL,
    redirectUris                TEXT DEFAULT NULL,
    authUrl                     TEXT DEFAULT NULL,
    accessToken                 TEXT DEFAULT NULL,
    expiresIn                   TEXT DEFAULT NULL,
    scope                       TEXT DEFAULT NULL,
    tokenType                   TEXT DEFAULT NULL,
    created                     TEXT DEFAULT NULL,
    refreshToken                TEXT DEFAULT NULL,
    spreadsheetId               TEXT DEFAULT NULL,
    sheetName                   TEXT DEFAULT NULL,
    periodicityNumber           int unsigned DEFAULT 10,
    periodicityTime             varchar(25) DEFAULT 'minute',

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