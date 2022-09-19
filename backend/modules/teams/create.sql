create table teams_config(
    nrTeamMembers 	int not null default 3,
    isTeamNameActive boolean not null default false,
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
    level       int not null,
    primary key (course,teamId),
    foreign key(course) references course(id) on delete cascade,
    foreign key(teamId) references teams(id) on delete cascade
);
/*
create table teams_participation(
    id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    team 	                    int unsigned NOT NULL,
    course 	                    int unsigned NOT NULL,
    description                 varchar(500) NOT NULL,
    type 	                    varchar(50) NOT NULL, #(ex:grade,skill,badge, lab,quiz,presentation,bonus)
    post 	                    varchar(255),
    date                        TIMESTAMP,
    rating                      int,
    evaluator                   int unsigned,

    FOREIGN key(evaluator,course) REFERENCES course_user(id,course) ON DELETE CASCADE, #needs trigger to set eval to null
    FOREIGN key(team, course) REFERENCES teams(id, course) ON DELETE CASCADE
    );
*/
create table award_teams(
    id                          int unsigned auto_increment primary key,
    team 	                    int unsigned not null,
    course 	                    int unsigned not null,
    description                 varchar(100) NOT NULL,
    type                        varchar(50) NOT NULL, #(ex:grade,skills, labs,quiz,presentation,bonus)
    moduleInstance              int unsigned ,#id of badge/skill (will be null for other types)
    reward                      int unsigned DEFAULT 0,
    date                        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    foreign key(team) references teams(id) on delete cascade,
    foreign key(course) references course(id) on delete cascade
);

CREATE TABLE award_teams_test(
   id 		                    int unsigned auto_increment primary key,
   team 	                    int unsigned NOT NULL,
   course 	                    int unsigned NOT NULL,
   description                 varchar(100) NOT NULL,
   type                        varchar(50) NOT NULL, #(ex:grade,skills, labs,quiz,presentation,bonus)
   moduleInstance              int unsigned , #id of badge/skill (will be null for other types)
   reward                      int unsigned DEFAULT 0,
   date                        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

   FOREIGN key(team, course) REFERENCES teams(id, course) ON DELETE CASCADE
);

create table award_teams_participation(
    award                       int unsigned,
    participation               int unsigned,

    PRIMARY key(award,participation),
    FOREIGN key(award) REFERENCES award(id) ON DELETE CASCADE,
    FOREIGN key(participation) REFERENCES participation(id) ON DELETE CASCADE
);