
drop trigger if exists parameterDelete;
drop trigger if exists viewDelete;
drop table if exists qr_error;
drop table if exists qr_code;
drop table if exists config_qr;
drop table if exists view_parameter;
drop table if exists parameter;
drop table if exists view_template;
drop table if exists template;
drop table if exists page;
drop table if exists view;
drop table if exists aspect_class;
drop table if exists skill_dependency;
drop table if exists dependency;
drop table if exists skill;
drop table if exists skill_tier;
drop table if exists skill_tree;
drop table if exists badge_has_level;
drop table if exists level;
drop table if exists badge;
drop table if exists badges_config;
drop table if exists award_participation;
drop table if exists participation;
drop table if exists notification;
drop table if exists award;
drop table if exists dictionary_function;
drop table if exists dictionary_variable;
drop table if exists dictionary_library;
drop table if exists course_module;
drop table if exists module;
drop table if exists user_role;
drop table if exists role;
drop table if exists course_user;
drop table if exists config_class_check;
drop table if exists config_google_sheets;
drop table if exists config_moodle;
drop table if exists course;
drop table if exists auth;
drop table if exists game_course_user;

create table game_course_user(
	id 		int unsigned primary key auto_increment, #81205
    name 	varchar(50) not null,
    email 	varchar(255),
	major 	varchar(8),
	nickname varchar(50),
	studentNumber int unique,
    isAdmin boolean not null default false,
	isActive boolean not null default true
);

create table auth(
	id int unsigned primary key auto_increment,
	game_course_user_id int unsigned not null,
	username varchar(50),
	authentication_service enum ('fenix','google','facebook','linkedin'),
	foreign key(game_course_user_id) references game_course_user(id) on delete cascade
);

create table course(
	id 		int unsigned primary key auto_increment,
	name 	varchar(100),
	short	varchar(20),
	color	varchar(7),
	year	varchar(10),
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
    lastActivity timestamp default CURRENT_TIMESTAMP,
    previousActivity timestamp default  CURRENT_TIMESTAMP,
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
	description varchar(100),
	version varchar(10),
	compatibleVersions varchar(100)
);
create table course_module(
	moduleId varchar(50) not null,
	course int unsigned not null,
	isEnabled boolean default false,
	primary key(moduleId, course),
	foreign key(moduleId) references module(moduleId) on delete cascade,
	foreign key(course) references course(id) on delete cascade
);

create table dictionary_library(
	id	int unsigned auto_increment primary key,
	moduleId varchar(50),
	name varchar(50) unique not null,
	description varchar(255)
);

create table dictionary_function(	
	id	int unsigned auto_increment primary key,
	libraryId int unsigned null,
	returnType varchar(50),
	returnName varchar(50),
	refersToType varchar(50) not null,
	refersToName varchar(50),
	keyword varchar(50),
	args varchar(1000),
	description varchar(1000),
	foreign key(libraryId) references dictionary_library(id) on delete cascade
);

create table dictionary_variable(
	id	int unsigned auto_increment primary key,
	libraryId int unsigned null,
	name varchar(50) unique,
	returnType varchar(50) not null,
	returnName varchar(50),
	description varchar(1000),
	foreign key(libraryId) references dictionary_library(id) on delete cascade
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
	description varchar(500) not null,
	type 	varchar(50) not null, #(ex:grade,skill,badge, lab,quiz,presentation,bonus)
	moduleInstance VARCHAR(200) ,#id of badge/skill (will be null for other types)
	post 	varchar(255),
	date timestamp,
	rating int,
	evaluator int unsigned,
	foreign key(evaluator,course) references course_user(id,course) on delete cascade, #needs triger to set eval to null
    foreign key(user, course) references course_user(id, course) on delete cascade
);

create table award_participation(#this table may be pointles if participations havent got more than 1 award
	award int unsigned,
	participation int unsigned,
	primary key (award,participation),
    foreign key(award) references award(id) on delete cascade,
    foreign key(participation) references participation(id) on delete cascade
);

create table level( #levels of xp and levels of badges
	id 		int unsigned auto_increment primary key,
	number int not null,
	course int unsigned not null,
	goal int not null,
	description varchar(200),
	foreign key(course) references course(id) on delete cascade
);

create table aspect_class(
	aspectClass int unsigned auto_increment primary key
	#foreign key (viewId) references view(id) on delete cascade
);
create table view(
	id int unsigned auto_increment primary key,
	aspectClass int unsigned,
	role varchar(100) default "role.Default",
	partType enum ('block','text','image','table','headerRow','row','header','templateRef','chart'),
	parent int unsigned,
	viewIndex int unsigned,
	label varchar(50),
	loopData varchar(200),
	variables varchar(500),
	value varchar(200),
	class varchar(50),
	style varchar(200),
	link varchar(100),
	visibilityCondition varchar(200),
	visibilityType enum ("visible","invisible","conditional"),
	events varchar(500),
	info varchar(500),
	foreign key (aspectClass) references aspect_class(aspectClass) on delete set null,
	foreign key (parent) references view(id) on delete cascade
);
create table page(
	id int unsigned auto_increment primary key,
	course int unsigned not null,
	roleType enum('ROLE_SINGLE','ROLE_INTERACTION') default 'ROLE_SINGLE',	
	name varchar(50) not null,
	theme varchar(50),
	viewId int unsigned,
	isEnabled boolean default false,
	foreign key(viewId) references view(id) on delete set null,
	foreign key(course) references course(id) on delete cascade
);
create table template(
	id int unsigned auto_increment primary key,
	name varchar(100) not null,#
	roleType enum('ROLE_SINGLE','ROLE_INTERACTION') default 'ROLE_SINGLE',
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
#ToDO add trigger when delete level or badge -> delete bagde_has_level