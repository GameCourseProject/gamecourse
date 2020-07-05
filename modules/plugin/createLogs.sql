create table moodle_logs(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	timeCreated timestamp not null,
	ip  varchar(200) null,
	user int unsigned not null,
	module varchar(200) not null,
	action varchar(200) not null,
	info varchar(200) null,
	url varchar(200) null,
	foreign key(course) references course(id) on delete cascade
);