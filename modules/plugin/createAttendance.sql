create table attendance(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	studentId int unsigned not null,
	timeCreated timestamp default CURRENT_TIMESTAMP,
	action varchar(200) not null,
	class int unsigned not null,
	foreign key(course) references course(id) on delete cascade
);
