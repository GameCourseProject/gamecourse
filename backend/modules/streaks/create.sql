create table streaks_config(
    maxBonusReward 	int not null,
    course int unsigned primary key,
    foreign key(course) references course(id) on delete cascade
);

create table streak(
    id 		int unsigned auto_increment primary key,
    name varchar(70) not null,
    course int unsigned not null,
    description  varchar(200) not null,
    color char(9), /* include the # for the color code */
    periodicity int,
    periodicityTime varchar(25),
    count int,
    reward int,
    tokens int,
    isRepeatable boolean not null default false,
    isCount boolean not null default false,
    isPeriodic boolean not null default false,
    isAtMost boolean not null default false,
    isActive boolean not null default true,
    image varchar(50),
    foreign key(course) references course(id) on delete cascade
);


create table streak_progression(
    course int unsigned not null,
    user int unsigned not null,
    streakId 	int unsigned,
    participationId 	int unsigned,
    foreign key(course) references course(id) on delete cascade,
    foreign key(user) references game_course_user(id) on delete cascade,
    foreign key(streakId) references streak(id) on delete cascade
);

create table streak_participations(
    course int unsigned not null,
    user int unsigned not null,
    streakId 	int unsigned,
    participationId 	int unsigned,
    isValid boolean not null default false,
    foreign key(course) references course(id) on delete cascade,
    foreign key(user) references game_course_user(id) on delete cascade,
    foreign key(streakId) references streak(id) on delete cascade
);

