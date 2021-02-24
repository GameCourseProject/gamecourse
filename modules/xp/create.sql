create table level(
    id 		int unsigned auto_increment primary key,
    number int not null,
    course int unsigned not null,
    goal int not null,
    description varchar(200),
    foreign key(course) references course(id) on delete cascade
);

create table user_xp(
    course  int unsigned,
    user int unsigned not null,
    xp int not null,
    primary key (course,user),
    foreign key(course) references course(id) on delete cascade,
    foreign key(user) references course_user(id) on delete cascade
);