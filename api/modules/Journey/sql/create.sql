CREATE TABLE IF NOT EXISTS journey_config(
    course                      int unsigned PRIMARY KEY,
    maxXP                       int unsigned DEFAULT NULL,
    repeatable                  int unsigned NOT NULL,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS journey_path(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
	name                        varchar(50) NOT NULL,
    color                       varchar(7) NOT NULL,

    UNIQUE key(name, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS journey_path_skills(
    id 		                    int unsigned NOT NULL,
    course                      int unsigned NOT NULL,
    path                        int unsigned NOT NULL,
    position                    int unsigned NOT NULL,

    PRIMARY key(path, position),
    FOREIGN key(id) REFERENCES skill(id) ON DELETE CASCADE,
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(path) REFERENCES journey_path(id) ON DELETE CASCADE
);