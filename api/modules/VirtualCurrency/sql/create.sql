CREATE TABLE IF NOT EXISTS virtual_currency_config(
    course                      int unsigned PRIMARY KEY,
    name                        varchar(50) NOT NULL DEFAULT 'Token(s)',

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_wallet(
    course                      int unsigned NOT NULL,
    user                        int unsigned NOT NULL,
    tokens                      int NOT NULL,
    exchanged                   boolean NOT NULL DEFAULT FALSE,

    PRIMARY key (course, user),
    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS virtual_currency_spending(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    user                        int unsigned NOT NULL,
    course                      int unsigned NOT NULL,
    description                 varchar(100) NOT NULL,
    amount                      int unsigned NOT NULL DEFAULT 0,
    date                        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS virtual_currency_auto_action(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    description                 varchar(150),
    type                        varchar(50) NOT NULL,
    amount                      int NOT NULL DEFAULT 0,
    isActive                    boolean NOT NULL DEFAULT TRUE,
    rule                        int unsigned NOT NULL,

    UNIQUE key(name, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(rule) REFERENCES rule(id) ON DELETE CASCADE
);
