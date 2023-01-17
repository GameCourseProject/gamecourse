CREATE TABLE IF NOT EXISTS award(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    user                        int unsigned NOT NULL,
    course                      int unsigned NOT NULL,
    description                 varchar(100) NOT NULL,
    type                        ENUM ('assignment', 'badge', 'bonus', 'exam', 'labs', 'post', 'presentation', 'quiz', 'skill', 'streak', 'tokens') NOT NULL,
    moduleInstance              int unsigned DEFAULT NULL,
    reward                      int unsigned NOT NULL DEFAULT 0,
    date                        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS award_test(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    user                        int unsigned NOT NULL,
    course                      int unsigned NOT NULL,
    description                 varchar(100) NOT NULL,
    type                        ENUM ('assignment', 'badge', 'bonus', 'exam', 'labs', 'post', 'presentation', 'quiz', 'skill', 'streak', 'tokens') NOT NULL,
    moduleInstance              int unsigned DEFAULT NULL,
    reward                      int unsigned DEFAULT 0,
    date                        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);
