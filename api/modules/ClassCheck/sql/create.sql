CREATE TABLE IF NOT EXISTS classcheck_config(
    course                      int unsigned PRIMARY KEY,
    tsvCode                     varchar(200) DEFAULT NULL,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS classcheck_status(
    course                      int unsigned PRIMARY KEY,
    startedRunning              TIMESTAMP NULL DEFAULT NULL,
    finishedRunning             TIMESTAMP NULL DEFAULT NULL,
    isRunning                   boolean DEFAULT FALSE,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);