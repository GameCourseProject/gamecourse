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

create table badge_has_level(#this table exists to prevent empty parameters on level table
	levelId 	int unsigned,
	badgeId 	int unsigned,
	reward 		int unsigned,
	foreign key(badgeId) references badge(id) on delete cascade,
	foreign key(levelId) references level(id) on delete cascade,
	primary key(levelId,badgeId)
);