CREATE TABLE IF NOT EXISTS classcheck_config(
    course                      int unsigned PRIMARY KEY,
    tsvCode                     varchar(200) DEFAULT NULL,
    periodicityNumber           int unsigned DEFAULT 10,
    periodicityTime             varchar(25) DEFAULT 'minute',

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS classcheck_status(
    course                      int unsigned PRIMARY KEY,
    isEnabled                   boolean DEFAULT FALSE,
    startedRunning              TIMESTAMP NULL DEFAULT NULL,
    finishedRunning             TIMESTAMP NULL DEFAULT NULL,
    isRunning                   boolean DEFAULT FALSE,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);