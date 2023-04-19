CREATE TABLE IF NOT EXISTS profiling_config(
    course                      int unsigned PRIMARY KEY,
    lastRun                     TIMESTAMP NULL DEFAULT NULL,
    lastRunUntil                TIMESTAMP NULL DEFAULT NULL,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS profiling_user_profile(
    course                      int unsigned NOT NULL,
    user                        int unsigned NOT NULL,
    date                        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cluster                     int unsigned NOT NULL,

    PRIMARY key(user, date, cluster),
    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(cluster) REFERENCES role(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS profiling_saved_user_profile(
    course                      int unsigned NOT NULL,
    user                        int unsigned NOT NULL,
    cluster                     varchar(50) NOT NULL,

    PRIMARY key(user, course),
    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);