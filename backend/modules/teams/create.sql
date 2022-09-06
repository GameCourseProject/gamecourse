create table teams_config(
    nrTeamMembers 	int not null default 3,
    course          int unsigned primary key,
    foreign key(course) references course(id) on delete cascade
);

create table teams(
    id          int unsigned auto_increment primary key,
    course      int unsigned not null,
    teamNumber  int unsigned,
    teamName    varchar(70) ,
    foreign key(course) references course(id) on delete cascade
);

create table teams_members(
    id          int unsigned auto_increment primary key,
    teamId      int unsigned not null,
    memberId    int unsigned not null,
    foreign key(teamId) references teams(id) on delete cascade,
    foreign key(memberId) references game_course_user(id) on delete cascade
);

create table teams_xp(
    course  int unsigned not null,
    teamId 	    int unsigned not null,
    xp          int not null,
    primary key (course,teamId),
    foreign key(course) references course(id) on delete cascade,
    foreign key(teamId) references teams(id) on delete cascade
);