
drop trigger if exists parameterDelete;
drop trigger if exists viewDelete;
drop table if exists view_parameter;
drop table if exists parameter;
drop table if exists aspect_class;
drop table if exists view_template;
drop table if exists template;
drop table if exists page;
drop table if exists view;
drop table if exists skill_dependency;
drop table if exists dependency;
drop table if exists skill;
drop table if exists skill_tier;
drop table if exists skill_tree;
drop table if exists badge_has_level;
drop table if exists level;
drop table if exists badge;
drop table if exists badges_config;
drop table if exists grade;
drop table if exists award_participation;
drop table if exists participation;
drop table if exists notification;
drop table if exists award;
drop table if exists dictionary;
drop table if exists course_module;
drop table if exists module;
drop table if exists user_role;
drop table if exists role;
drop table if exists course_user;
drop table if exists course;
drop table if exists game_course_user;

create table game_course_user(
	id 		int unsigned primary key, #81205
    name 	varchar(50) not null,
    email 	varchar(255),
    username varchar(50),        #ist181205
    isAdmin boolean not null default false
);

create table course(
	id 		int unsigned primary key auto_increment,
	name 	varchar(100),
	defaultLandingPage varchar(100) default "",
	lastUpdate timestamp default CURRENT_TIMESTAMP,
	isActive boolean default true,
	isVisible boolean default true, #?
	roleHierarchy text,
	theme varchar(50)
);

create table course_user
   (id  int unsigned,
   	course  int unsigned,
    campus 	char(1),
    lastActivity timestamp default 0,
    previousActivity timestamp default 0,
    primary key(id, course),
    foreign key(id) references game_course_user(id) on delete cascade,
    foreign key(course) references course(id) on delete cascade
);

create table role(
	id 		int unsigned auto_increment primary key,
	name varchar(50) not null,
	landingPage varchar(100) default '',
	course int unsigned not null,
	#isCourseAdmin boolean default false,
	foreign key(course) references course(id) on delete cascade
);
create table user_role(
	id int unsigned not null,#user id
	course  int unsigned not null,
	role int unsigned not null,
	primary key(id, course, role),
	foreign key(id, course) references course_user(id, course) on delete cascade,
	foreign key(role) references role(id) on delete cascade
);

create table module(
	moduleId varchar(50) not null primary key,
	name varchar(50),
	description varchar(100)
);
create table course_module(
	moduleId varchar(50) not null,
	course int unsigned not null,
	isEnabled boolean default false,
	primary key(moduleId, course),
	foreign key(moduleId) references module(moduleId) on delete cascade,
	foreign key(course) references course(id) on delete cascade
);

create table dictionary(
	moduleId varchar(50),
	keyword varchar(50),
	description varchar(255),
	primary key(moduleId,keyword),
	foreign key(moduleId) references module (moduleId)
);

create table award(
	id 		int unsigned auto_increment primary key,
	user 	int unsigned not null,
	course 	int unsigned not null,
	description varchar(100) not null,
	type varchar(50) not null, #(ex:grade,skills, labs,quiz,presentation,bonus)
	moduleInstance int unsigned ,#id of badge/skill (will be null for other types)
	reward int unsigned default 0,
	date timestamp default CURRENT_TIMESTAMP,
    foreign key(user, course) references course_user(id, course) on delete cascade
);

create table notification(
	id int unsigned auto_increment primary key,
	award int unsigned not null,
	checked boolean default false,
	foreign key(award) references award(id) on delete cascade
);

create table participation(#for now this is just used for badges
	id 		int unsigned auto_increment primary key,
	user 	int unsigned not null,
	course 	int unsigned not null,
	description varchar(50) not null,
	type 	varchar(50) not null, #(ex:grade,skills, labs,quiz,presentation,bonus)
	moduleInstance int unsigned,#id of badge/skill (will be null for other types)
	post 	varchar(255),
	date timestamp,
	rating int,
	evaluator int unsigned,
	foreign key(evaluator,course) references course_user(id,course) on delete cascade, #needs triger to set eval to null
    foreign key(user, course) references course_user(id, course) on delete cascade
);

create table award_participation(#this table may be pointles if participations haven't got more than 1 award
	award int unsigned,
	participation int unsigned,
	primary key (award,participation),
    foreign key(award) references award(id) on delete cascade,
    foreign key(participation) references participation(id) on delete cascade
);

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

create table level(
	id 		int unsigned auto_increment primary key,
	number int not null,
	course int unsigned not null,
	goal int not null,
	description varchar(200),
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

create table skill_tree(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
	maxReward int unsigned,
	foreign key(course) references course(id) on delete cascade
);
create table skill_tier(
	tier int unsigned not null,
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
	tier int unsigned not null,
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

create table view(
	id int unsigned auto_increment primary key,
	#pageId int unsigned not null,
	#aspectClass int,
	role varchar(100) default "role.Default",
	partType enum ('aspect','block','text','image','table','headerRow','row','header','templateRef'),
	parent int unsigned,
	viewIndex int unsigned,
	label varchar(50),
	#template int unsigned,
	foreign key (parent) references view(id) on delete cascade
	#foreign key (template) references view_template(id) on delete cascade,
	#foreign key(pageId) references page(id) on delete cascade
);
create table page(
	id int unsigned auto_increment primary key,
	course int unsigned not null,
	roleType enum('VT_SINGLE','VT_ROLE_SINGLE','VT_ROLE_INTERACTION') default 'VT_ROLE_SINGLE',	
	name varchar(50) not null,
	theme varchar(50),
	viewId int unsigned,
	#module varchar(50),
	#foreign key(module, course) references course_module(moduleId,course) on delete cascade,
	foreign key(viewId) references view(id) on delete set null,
	foreign key(course) references course(id) on delete cascade
);
create table template(
	id int unsigned auto_increment primary key,
	name varchar(100) not null,#
	roleType enum('VT_SINGLE','VT_ROLE_SINGLE','VT_ROLE_INTERACTION') default 'VT_ROLE_SINGLE',
	course int unsigned not null,
	isGlobal boolean default false,
	foreign key (course) references course(id) on delete cascade
);
create table view_template(
	viewId int unsigned primary key,
	templateId int unsigned,
	foreign key (templateId) references template(id) on delete cascade,
	foreign key (viewId) references view(id) on delete cascade
);
create table aspect_class(
	viewId int unsigned primary key,
	aspectClass int unsigned,
	foreign key (viewId) references view(id) on delete cascade
);
create table parameter(
	id int unsigned auto_increment primary key,
	type enum ('loopData','variables','value','class','style','link','visibilityCondition','visibilityType','events') not null,#angular directive?
	value varchar(500) not null
);
create table view_parameter(
	viewId int unsigned,
	parameterId int unsigned,
	primary key(viewId,parameterId),
	foreign key (viewId) references view(id) on delete cascade,
	foreign key (parameterId) references parameter(id) on delete cascade
);

#ToDO add trigger when delete level or badge -> delete bagde_has_level

# if no view is using a parameter then delete it
create trigger parameterDelete before delete on view_parameter
	for each row
		BEGIN
		declare paramCount int;
		set paramCount = (select count(*) from view_parameter where parameterId=OLD.parameterId);
		if (paramCount=1)
		then
			delete from parameter where id=OLD.parameterId;
		END if;

		end;

#delete view_parameter of view (same as on delete cascade but it works for triggers)
create trigger viewDelete before delete on view
	for each row
	BEGIN
			delete from view_parameter where viewId=OLD.id;
	end;