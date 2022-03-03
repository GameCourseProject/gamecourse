create table config_virtual_currency(
    name varchar(70) not null,
    course int unsigned not null,
    skillCost int not null,
    wildcardCost int not null,
    foreign key(course) references course(id) on delete cascade
);

create table user_wallet(
    course int unsigned not null,
    user int unsigned not null,
    tokens int not null,
    primary key(user, course),
    foreign key(course) references course(id) on delete cascade,
    foreign key(user) references game_course_user(id) on delete cascade
);

