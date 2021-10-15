create table config_class_check(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	tsvCode varchar(200) not null,
	isEnabled boolean,
	periodicityNumber int,
	periodicityTime varchar(25),
	foreign key(course) references course(id) on delete cascade
);
