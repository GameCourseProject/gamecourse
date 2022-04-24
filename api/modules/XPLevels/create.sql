CREATE TABLE IF NOT EXISTS level(
    id                          int unsigned PRIMARY KEY AUTO_INCREMENT,
    number                      int NOT NULL,
    course                      int unsigned NOT NULL,
    goal                        int NOT NULL,
    description                 varchar(200),

    UNIQUE key(number, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_xp(
    course                      int unsigned NOT NULL,
    user                        int unsigned NOT NULL,
    xp                          int NOT NULL,
    level                       int unsigned NOT NULL,

    PRIMARY key (course, user),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(user) REFERENCES course_user(id) ON DELETE CASCADE,
    FOREIGN key(level) REFERENCES level(id)
);