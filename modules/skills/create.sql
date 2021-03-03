create table skill_tree(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	maxReward int unsigned,
	foreign key(course) references course(id) on delete cascade
);
create table skill_tier(
	tier varchar(50) not null,
	reward int unsigned not null,
	treeId int unsigned not null,
	primary key(treeId,tier),
	foreign key(treeId) references skill_tree(id) on delete cascade
);
create table skill(
	id 	int unsigned auto_increment primary key,
	name varchar(50) not null,
	color varchar(10),
	page TEXT,
	tier varchar(50) not null,
	treeId int unsigned not null,
	foreign key(treeId,tier) references skill_tier(treeId, tier) on delete cascade
);
create table dependency(
	id 	int unsigned auto_increment primary key,
	superSkillId int unsigned not null,
	foreign key(superSkillId) references skill(id) on delete cascade
);
create table skill_dependency(
	dependencyId int unsigned not null,
	normalSkillId int unsigned not null,
	foreign key (dependencyId) references dependency(id) on delete cascade,
	foreign key(normalSkillId) references skill(id) on delete cascade
);
