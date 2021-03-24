create table qr_code(
	qrkey varchar(50) not null,
	course int unsigned not null,
	studentNumber int unsigned,
	classNumber int,
	classType  varchar(50),
	foreign key (course) references course(id) on delete cascade
);
create table qr_error(
	user int unsigned not null,
	course  int unsigned not null,
	ip varchar(50),
	qrkey varchar(50), 
	msg varchar(500),
	date timestamp default CURRENT_TIMESTAMP,
	foreign key (course) references course(id) on delete cascade,
	foreign key (user) references game_course_user(id) on delete cascade
);