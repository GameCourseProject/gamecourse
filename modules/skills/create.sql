create table skill_tree(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	maxReward int unsigned,
	foreign key(course) references course(id) on delete cascade
);
create table skill_tier(
	id 	int unsigned auto_increment unique,
	tier varchar(50) not null,
	seqId int unsigned not null,
	reward int unsigned not null,
	treeId int unsigned not null,
	primary key(treeId,tier),
	foreign key(treeId) references skill_tree(id) on delete cascade
);
create table skill(
	id 	int unsigned auto_increment primary key,
	seqId int unsigned not null,
	name varchar(50) not null,
	color varchar(10),
	page TEXT,
	tier varchar(50) not null,
	treeId int unsigned not null,
	isActive boolean not null default true,
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
	isTier boolean not null default false,
	primary key(dependencyId, normalSkillId, isTier),
	foreign key (dependencyId) references dependency(id) on delete cascade
);
create table award_wildcard(
	awardId int unsigned not null,
	tierId int unsigned not null,
	primary key(awardId,tierId),
	foreign key (awardId) references award(id) on delete cascade,
	foreign key (tierId) references skill_tier(id) on delete cascade
);
