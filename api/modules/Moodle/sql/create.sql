CREATE TABLE IF NOT EXISTS moodle_config(
    course                      int unsigned PRIMARY KEY,
    dbServer                    varchar(100),
    dbUser                      varchar(25),
    dbPass                      varchar(50) DEFAULT NULL,
    dbName                      varchar(50),
    dbPort                      int unsigned,
    tablesPrefix                varchar(25),
    moodleURL                   varchar(100),
    moodleCourse                int unsigned DEFAULT NULL,
    periodicityNumber           int unsigned DEFAULT 10,
    periodicityTime             varchar(25) DEFAULT 'minute',

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS moodle_status(
    course                      int unsigned PRIMARY KEY,
    isEnabled                   boolean DEFAULT FALSE,
    startedRunning              TIMESTAMP NULL DEFAULT NULL,
    finishedRunning             TIMESTAMP NULL DEFAULT NULL,
    isRunning                   boolean DEFAULT FALSE,
    checkpoint                  TIMESTAMP NULL DEFAULT NULL,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);