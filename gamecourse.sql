
drop table if exists course_template;
drop table if exists template_parameter;
drop table if exists view_parameter;
drop table if exists parameter;
drop table if exists view;
drop table if exists view_template;
drop table if exists page;
drop table if exists skill_dependency;
drop table if exists dependency;
drop table if exists skill;
drop table if exists skill_tier;
drop table if exists skill_tree;
drop table if exists level;
drop table if exists badge_has_level;
drop table if exists badge;
drop table if exists grade;
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

create table dictionary(# conect with enable_module? course and module? course?
	moduleId varchar(50),
	course int unsigned,
	keyword varchar(50),
	description varchar(255),
	primary key(moduleId,course,keyword),
	foreign key(moduleId) references module (moduleId),
	foreign key(course) references course(id)
);

create table award(
	id 		int unsigned auto_increment primary key,
	user 	int unsigned not null,
	course 	int unsigned not null,
	description varchar(100) not null,
	module varchar(50) not null, #(ex:grade,skills, labs,quiz,presentation,bonus)
	moduleInstance int unsigned ,#id of badge/skill (will be null for other types)
	reward int unsigned default 0,
	date timestamp default CURRENT_TIMESTAMP, 
	isEnabled boolean default true,
    foreign key(user, course) references course_user(id, course) on delete cascade,
    foreign key(module,course) references course_module(moduleId,course)
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
	module 	varchar(50) not null, #(ex:grade,skills, labs,quiz,presentation,bonus)
	moduleInstance int unsigned,#id of badge/skill (will be null for other types)
	post 	varchar(255),
	date timestamp, 
	isEnabled boolean default true,
    foreign key(user, course) references course_user(id, course) on delete cascade,
    foreign key(module,course) references course_module(moduleId,course)
);

create table grade(
	id 		int unsigned auto_increment primary key,
	participation int unsigned not null,
	evaluator 	int unsigned,
	course 	int unsigned,
	grade 	int unsigned not null,
    foreign key(evaluator, course) references course_user(id, course) on delete set null,
    foreign key(participation) references participation(id) on delete cascade
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
	primary key(levelId,badgeId)
);
create table level(
	id 		int unsigned auto_increment primary key,
	number int not null,
	course int unsigned not null,
	goal int not null,
	description varchar(200),
	foreign key(course) references course(id) on delete cascade
);

create table skill_tree(
	id 		int unsigned auto_increment primary key,
	course int unsigned not null,
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
	superSkill int unsigned not null,
	foreign key(superSkill) references skill(id) on delete cascade
);
create table skill_dependency(
	dependencyId int unsigned not null,
	normalSkillId int unsigned not null,
	foreign key (dependencyId) references dependency(id) on delete cascade,
	foreign key(normalSkillId) references skill(id) on delete cascade
);

create table page(
	id int unsigned auto_increment primary key,
	course int unsigned not null,
	roleType enum('VT_SINGLE','VT_ROLE_SINGLE','VT_ROLE_INTERACTION') default 'VT_ROLE_SINGLE',	
	name varchar(50) not null,
	theme varchar(50),
	foreign key(course) references course(id) on delete cascade
);
create table view_template(
	id int unsigned auto_increment primary key,
	name varchar(100) not null,#
	role varchar(100),
	partType enum ('view','aspect','block','text','image','table','heardRow','row','header','instance'),
	parent int unsigned,
	viewIndex int unsigned,
	isGlobal boolean default false,
	aspectClass int unsigned,
	foreign key (parent) references view_template(id) on delete cascade
);
create table view(
	id int unsigned auto_increment primary key,
	pageId int unsigned not null,
	role varchar(100) not null,
	partType enum ('aspect','block','text','image','table','heardRow','row','header','instance'),
	parent int unsigned,
	viewIndex int unsigned,
	template int unsigned,
	foreign key (parent) references view(id) on delete cascade,
	foreign key (template) references view_template(id) on delete cascade,
	foreign key(pageId) references page(id) on delete cascade
);
create table parameter(
	id int unsigned auto_increment primary key,
	type enum ('loopData','variables','value','class','style','link','if','events') not null,
	value varchar(500) not null
);
create table view_parameter(
	viewId int unsigned,
	parameterId int unsigned,
	primary key(viewId,parameterId),
	foreign key (viewId) references view(id) on delete cascade,
	foreign key (parameterId) references parameter(id) on delete cascade
);
create table template_parameter(
	templateId int unsigned,
	parameterId int unsigned,
	primary key(templateId,parameterId),
	foreign key (templateId) references view_template(id) on delete cascade,
	foreign key (parameterId) references parameter(id) on delete cascade
);
create table course_template(
	template int unsigned,
	course 	int unsigned,
	primary key (template,course),
	foreign key (template) references view_template(id) on delete cascade,
	foreign key (course) references course(id) on delete cascade
);

