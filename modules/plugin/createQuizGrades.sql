create table moodle_quiz_grades(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	timeModified timestamp not null,
	user int unsigned not null,
	quizName varchar(200) not null,
	grade decimal(10,5) not null,
	url varchar(200) null,
	foreign key(course) references course(id) on delete cascade
);

