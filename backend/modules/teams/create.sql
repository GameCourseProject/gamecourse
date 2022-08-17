create table teams_config(
    nrTeamMembers 	int not null default 3,
    course          int unsigned primary key,
    foreign key(course) references course(id) on delete cascade
);

create table teams(
    id          int unsigned auto_increment primary key,
    course      int unsigned not null,
    teamId      int unsigned auto_increment,
    teamName    varchar(70) not null,
    memberId    int unsigned not null,
    memberName  varchar(70) not null,
    foreign key(course) references course(id) on delete cascade,
    foreign key(memberId) references game_course_user(id) on delete cascade
);

create table teams_xp(
    course  int unsigned not null,
    teamId 	    int unsigned not null,
    xp          int not null,
    primary key (course,user),
    foreign key(course) references course(id) on delete cascade,
    foreign key(teamId) references teams(teamId) on delete cascade
);