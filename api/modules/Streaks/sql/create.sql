CREATE TABLE IF NOT EXISTS streaks_config(
    course                      int unsigned PRIMARY KEY,
    maxXP                       int unsigned DEFAULT NULL,
    maxExtraCredit              int unsigned DEFAULT NULL,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS streak(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
	name                        varchar(50) NOT NULL,
	description                 varchar(150) NOT NULL,
    color                       varchar(7),
    count                       int unsigned NOT NULL DEFAULT 1,
    periodicity                 int unsigned DEFAULT NULL,
    periodicityTime             varchar(25) DEFAULT NULL,
    reward                      int unsigned NOT NULL,
    tokens                      int unsigned DEFAULT NULL,
    isRepeatable                boolean NOT NULL DEFAULT FALSE,
	isCount                     boolean NOT NULL DEFAULT FALSE,
	isPeriodic                  boolean NOT NULL DEFAULT FALSE,
    isAtMost                    boolean NOT NULL DEFAULT FALSE,
    isExtra                     boolean NOT NULL DEFAULT FALSE,
	isActive                    boolean NOT NULL DEFAULT TRUE,
    rule                        int unsigned NOT NULL,

    UNIQUE key(name, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(rule) REFERENCES rule(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS streak_progression(
	course                      int unsigned NOT NULL,
	user                        int unsigned NOT NULL,
	streak 	                    int unsigned NOT NULL,
	participation 	            int unsigned NOT NULL,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(streak) REFERENCES streak(id) ON DELETE CASCADE,
    FOREIGN key(participation) REFERENCES participation(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS streak_participations(
    course                      int unsigned NOT NULL,
    user                        int unsigned NOT NULL,
    streak 	                    int unsigned NOT NULL,
    participation 	            int unsigned NOT NULL,
    isValid                     boolean NOT NULL DEFAULT FALSE,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(streak) REFERENCES streak(id) ON DELETE CASCADE,
    FOREIGN key(participation) REFERENCES participation(id) ON DELETE CASCADE
);
