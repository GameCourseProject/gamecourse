create table badges_config(
	maxBonusReward 	int not null,
	imageExtra varchar(50),
	imageLevel2 varchar(50),
	imageLevel3 varchar(50),
	course int unsigned primary key,
	foreign key(course) references course(id) on delete cascade
);

create table badge(
	id 		int unsigned auto_increment primary key,
	name varchar(70) not null,
	course int unsigned not null,
	description  varchar(200) not null,
	maxLevel int not null,
	isExtra boolean not null default false,
	isBragging boolean not null default false,
	isCount boolean not null default false,
	isPost boolean not null default false,
	isPoint boolean not null default false,
	image varchar(50),
	foreign key(course) references course(id) on delete cascade
);

create table badge_level(
	id 	        int unsigned auto_increment primary key,
	badgeId 	int unsigned,
    number      int not null,
    goal        int not null,
    description varchar(200),
	reward 		int unsigned,
	foreign key(badgeId) references badge(id) on delete cascade
);

create table badge_progression(
	course int unsigned not null,
	user int unsigned not null,
	badgeId 	int unsigned,
	participationId 	int unsigned,
	foreign key(course) references course(id) on delete cascade,
	foreign key(user) references game_course_user(id) on delete cascade,
	foreign key(badgeId) references badge(id) on delete cascade,
	foreign key(participationId) references participation(id) on delete cascade
);
