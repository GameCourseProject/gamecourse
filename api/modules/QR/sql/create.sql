CREATE TABLE IF NOT EXISTS qr_code(
    qrkey                       varchar(50) PRIMARY KEY,
    qrcode                      TEXT NOT NULL,
    qrURL                       TEXT NOT NULL,
    course                      int unsigned NOT NULL,
    user                        int unsigned DEFAULT NULL,
    classNumber                 int unsigned DEFAULT NULL,
    classType                   ENUM ('Lecture', 'Invited Lecture') DEFAULT NULL,
    participation               int unsigned DEFAULT NULL,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(participation) REFERENCES participation(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS qr_error(
    course                      int unsigned NOT NULL,
    user                        int unsigned NOT NULL,
    qrkey                       varchar(50) NOT NULL,
    msg                         TEXT NOT NULL,
    date                        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);