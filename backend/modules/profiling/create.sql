create table profiling_config(
	lastRun         TIMESTAMP NULL,
	course          int unsigned PRIMARY KEY,

	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

create table user_profile(
	user            int unsigned NOT NULL,
	course          int unsigned NOT NULL,
	date            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	cluster         int unsigned NOT NULL,

    PRIMARY key(user, date, cluster),
	FOREIGN key(cluster) REFERENCES role(id) ON DELETE CASCADE,
	FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

create table saved_user_profile(
	user            int unsigned NOT NULL,
	course          int unsigned NOT NULL,
	cluster         varchar(50) NOT NULL,
	
	PRIMARY key(user, course),
	FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

create table user_page_history(
    user            int unsigned NOT NULL,
    page            int unsigned NOT NULL,
    timestamp       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(user) REFERENCES game_course_user(id) ON DELETE CASCADE,
    FOREIGN key(page) REFERENCES page(id) ON DELETE CASCADE
);