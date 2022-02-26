create table moodle_config (
    id 		                int unsigned PRIMARY KEY AUTO_INCREMENT,
    course                  int unsigned NOT NULL,
    dbServer                varchar(200) NOT NULL,
    dbUser                  varchar(200) NOT NULL,
    dbPass                  varchar(200) NULL,
    dbName                  varchar(200) NOT NULL,
    dbPort                  int NULL,
    tablesPrefix            varchar(200) NULL,
    moodleTime              int null,
    moodleCourse            varchar(200) NULL,
    moodleUser              varchar(200) NULL,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);
