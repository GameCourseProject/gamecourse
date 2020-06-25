create table config_fenix(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	fenixCourseId int unsigned null,
	foreign key(course) references course(id) on delete cascade
);
