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
    goal                        int unsigned NOT NULL DEFAULT 0,
    periodicityGoal             int unsigned DEFAULT NULL,
    periodicityNumber           int unsigned DEFAULT NULL,
    periodicityTime             ENUM ('second', 'minute', 'hour', 'day', 'week', 'month', 'year') DEFAULT NULL,
    periodicityType             ENUM ('absolute', 'relative') DEFAULT NULL,
    reward                      int unsigned NOT NULL DEFAULT 0,
    tokens                      int unsigned NOT NULL DEFAULT 0,
    isExtra                     boolean NOT NULL DEFAULT FALSE,
    isRepeatable                boolean NOT NULL DEFAULT FALSE,
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
	repetition                  int unsigned NOT NULL,
	participation 	            int unsigned NOT NULL,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(streak) REFERENCES streak(id) ON DELETE CASCADE,
    FOREIGN key(participation) REFERENCES participation(id) ON DELETE CASCADE
);
