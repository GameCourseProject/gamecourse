create table config_virtual_currency(
    name varchar(70) not null,,
    course int unsigned not null,
    initialTokens int not null,
    cost1 int not null,
    cost2 int not null,
    cost3 int not null,
    costWildcard int not null,
    foreign key(course) references course(id) on delete cascade
);

create table user_wallet(
    course int unsigned not null,
    user int unsigned not null,
    tokens int not null,
    foreign key(course) references course(id) on delete cascade,
    foreign key(user) references game_course_user(id) on delete cascade
);
