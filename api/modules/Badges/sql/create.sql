CREATE TABLE IF NOT EXISTS badges_config(
    course                      int unsigned PRIMARY KEY,
    maxExtraCredit              int unsigned NOT NULL DEFAULT 0,
    imageExtra                  varchar(50),
    imageBragging               varchar(50),
    imageLevel2                 varchar(50),
    imageLevel3                 varchar(50),

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badge(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
	name                        varchar(50) NOT NULL,
	description                 varchar(150) NOT NULL,
	nrLevels                    int NOT NULL DEFAULT 0,
	isExtra                     boolean NOT NULL DEFAULT FALSE,
	isBragging                  boolean NOT NULL DEFAULT FALSE,
	isCount                     boolean NOT NULL DEFAULT FALSE,
	isPost                      boolean NOT NULL DEFAULT FALSE,
	isPoint                     boolean NOT NULL DEFAULT FALSE,
	isActive                    boolean NOT NULL DEFAULT TRUE,
    rule                        int unsigned NOT NULL,

    UNIQUE key(name, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(rule) REFERENCES rule(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badge_level(
	id 	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
	badge 	                    int unsigned NOT NULL,
    number                      int unsigned NOT NULL,
    goal                        int unsigned NOT NULL DEFAULT 0,
    description                 varchar(100) NOT NULL,
	reward 		                int unsigned NOT NULL DEFAULT 0,

    UNIQUE key(number, badge),
    UNIQUE key(goal, badge),
    FOREIGN key(badge) REFERENCES badge(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badge_progression(
	course                      int unsigned NOT NULL,
	user                        int unsigned NOT NULL,
	badge 	                    int unsigned NOT NULL,
	participation 	        int unsigned NOT NULL,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(badge) REFERENCES badge(id) ON DELETE CASCADE,
    FOREIGN key(participation) REFERENCES participation(id) ON DELETE CASCADE
);
