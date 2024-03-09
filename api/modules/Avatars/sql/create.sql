CREATE TABLE IF NOT EXISTS avatar(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    user                        int unsigned NOT NULL,
    course                      int unsigned NOT NULL,
    colors                      TEXT,
    types                       TEXT,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);