create table virtual_currency_config(
    name varchar(70) not null,
    course int unsigned not null,
    skillCost int not null,
    wildcardCost int not null,
    attemptRating int not null,
    /* costFormula varchar(70) not null,
     firstCounts boolean not null default false, */
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

create table remove_tokens_participation(
    course          int unsigned not null,
    user            int unsigned not null,
    participation   int unsigned,
   /* description     varchar(500) NOT NULL,
    type            varchar(50) NOT NULL, */
    tokensRemoved   int not null,
    /*primary key(user, course),*/
    foreign key(course) references course(id) on delete cascade,
    foreign key(user) references game_course_user(id) on delete cascade,
    foreign key(participation) references participation(id) on delete cascade
);
