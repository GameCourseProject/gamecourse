create table badges_config(
	maxBonusReward 	int not null,
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