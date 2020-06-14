create table moodle_votes(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	timeCreated timestamp not null,
	user int unsigned not null,
	forum varchar(200) not null,
	topic varchar(200) not null,
	grade int not null,
	url varchar(200) null,
	foreign key(course) references course(id) on delete cascade
);
