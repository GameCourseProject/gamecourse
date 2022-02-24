create table qr_code(
	qrkey               varchar(50) NOT NULL,
	course              int unsigned NOT NULL,
	user                int unsigned,
	classNumber         int,
	classType           varchar(50),
	FOREIGN key (course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key (user) REFERENCES game_course_user(id) ON DELETE CASCADE
);
create table qr_error(
	user                int unsigned NOT NULL,
	course              int unsigned NOT NULL,
	ip                  varchar(50),
	qrkey               varchar(50),
	msg                 varchar(500),
	date                timestamp default CURRENT_TIMESTAMP,
    FOREIGN key (course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key (user) REFERENCES game_course_user(id) ON DELETE CASCADE
);