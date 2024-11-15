CREATE TABLE IF NOT EXISTS journey_config(
    course                      int unsigned PRIMARY KEY,
    maxXP                       int unsigned DEFAULT NULL,
    maxExtraCredit              int unsigned DEFAULT NULL,
    minRating                   int unsigned NOT NULL DEFAULT 3,
    isRepeatable                boolean NOT NULL DEFAULT FALSE,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS journey_path(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
	name                        varchar(50) NOT NULL,
    color                       varchar(7) NOT NULL,
    isActive                    boolean NOT NULL DEFAULT TRUE,

    UNIQUE key(name, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS journey_path_skills(
    skill 		                int unsigned NOT NULL,
    path                        int unsigned NOT NULL,
    position                    int unsigned,
    rule                        int unsigned NOT NULL,
    reward                      int unsigned,

    UNIQUE key(path, position),
    FOREIGN key(skill) REFERENCES skill(id) ON DELETE CASCADE,
    FOREIGN key(path) REFERENCES journey_path(id) ON DELETE CASCADE,
    FOREIGN key(rule) REFERENCES rule(id) ON DELETE CASCADE
);