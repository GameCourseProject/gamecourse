CREATE TABLE IF NOT EXISTS badges_config(
    course                      int unsigned PRIMARY KEY,
    maxBonusReward              int NOT NULL,
    imageExtra                  varchar(50),
    imageBragging               varchar(50),
    imageLevel2                 varchar(50),
    imageLevel3                 varchar(50),

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badge(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
	name                        varchar(70) NOT NULL,
	description                 varchar(200) NOT NULL,
    image                       varchar(50),
	nrLevels                    int NOT NULL,
	isExtra                     boolean NOT NULL DEFAULT FALSE,
	isBragging                  boolean NOT NULL DEFAULT FALSE,
	isCount                     boolean NOT NULL DEFAULT FALSE,
	isPost                      boolean NOT NULL DEFAULT FALSE,
	isPoint                     boolean NOT NULL DEFAULT FALSE,
	isActive                    boolean NOT NULL DEFAULT TRUE,

    UNIQUE key(name, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badge_level(
	id 	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
	badgeId 	                int unsigned NOT NULL,
    number                      int unsigned NOT NULL,
    goal                        int unsigned NOT NULL DEFAULT 0,
    description                 varchar(200),
	reward 		                int unsigned NOT NULL DEFAULT 0,

    UNIQUE key(number, badgeId),
    FOREIGN key(badgeId) REFERENCES badge(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badge_progression(
	course                      int unsigned NOT NULL,
	user                        int unsigned NOT NULL,
	badgeId 	                int unsigned NOT NULL,
	participationId 	        int unsigned NOT NULL,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(badgeId) REFERENCES badge(id) ON DELETE CASCADE,
    FOREIGN key(participationId) REFERENCES participation(id) ON DELETE CASCADE
);
