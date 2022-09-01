CREATE TABLE IF NOT EXISTS virtual_currency_config(
    course                      int unsigned PRIMARY KEY,
    name                        varchar(50) NOT NULL DEFAULT 'Token(s)',
    skillCost                   int unsigned NOT NULL DEFAULT 0,
    wildcardCost                int unsigned NOT NULL DEFAULT 0,
    attemptRating               int unsigned NOT NULL DEFAULT 0,

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

CREATE TABLE IF NOT EXISTS remove_tokens_participation(
    course                      int unsigned NOT NULL,
    user                        int unsigned NOT NULL,
    participation               int unsigned NOT NULL,
    tokensRemoved               int unsigned NOT NULL,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(participation) REFERENCES participation(id) ON DELETE CASCADE
);
