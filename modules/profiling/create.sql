create table profiling_config(
	lastRun timestamp NULL,
	course int unsigned primary key,
	foreign key(course) references course(id) on delete cascade
);

create table user_profile(
	user int unsigned not null,
	course int unsigned not null,
	date timestamp default CURRENT_TIMESTAMP,
	cluster int unsigned not null,
	primary key(user, date, cluster),
	foreign key(cluster) references role(id) on delete cascade,
	foreign key(user, course) references course_user(id, course) on delete cascade
);

create table saved_user_profile(
	user int unsigned not null,
	course int unsigned not null,
	cluster varchar(50) not null,
	primary key(user, course),
	foreign key(user, course) references course_user(id, course) on delete cascade
);