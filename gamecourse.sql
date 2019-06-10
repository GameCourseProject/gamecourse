drop table if exists qr_error;
drop table if exists participation;
drop table if exists qr_code;
drop table if exists system_info;
drop table if exists pending_invite;
drop table if exists view_template;
drop table if exists view_part;
drop table if exists view_role;
drop table if exists view;
drop table if exists level;
drop table if exists skill_dependency;
drop table if exists user_skill;
drop table if exists skill;
drop table if exists skill_tier;
drop table if exists badge_level;
drop table if exists progress_indicator;
drop table if exists badge_level_time;
drop table if exists user_badge;
drop table if exists badge;
drop table if exists notification;
drop table if exists award;
drop table if exists role_hierarchy;
drop table if exists user_role;
drop table if exists role;
drop table if exists enabled_module;
drop table if exists module;
drop table if exists course_user;
drop table if exists course;
drop table if exists user;

create table user(
	id 	int unsigned not null primary key, #81205
    name 	varchar(50) not null ,
    email 	varchar(255),
    username varchar(50),        #ist181205
    isAdmin boolean not null default false
);

create table course(
	id   int unsigned not null primary key auto_increment,
	name 		varchar(100),
	numBadges  int unsigned default 0,
	active boolean default true,
	headerLink varchar(255) default "",#?
	defaultLandingPage varchar(100) default "",
	fenixLink varchar(255) default "",
	apiKey varchar(50),
	lastUpdate timestamp default CURRENT_TIMESTAMP
);

create table course_user
   (id  int unsigned not null,
   	course  int unsigned not null,
    campus 	char(1),
    XP 	    int unsigned default 0,
    level 	int unsigned default 0,
    lastActivity timestamp default 0,
    prevActivity timestamp default 0,
    totalTreeXP int unsigned default 0,
    countedTreeXP int unsigned default 0,
    totalBadgeXP int unsigned default 0,
    normalBadgeXP int unsigned default 0,
    extraBadgeXP int unsigned default 0,
    countedBadgeXP int unsigned default 0,
    numBadgeLvls int unsigned default 0,
    labsXP int unsigned default 0,
    quizXP int unsigned default 0,
    presentationXP int unsigned default 0,
    primary key(id, course),
    foreign key(id) references user(id) on delete cascade,
    foreign key(course) references course(id) on delete cascade
);

create table role(
	role varchar(50) not null,
	landingPage varchar(100) default '',
	course int unsigned not null,
	#isCourseAdmin boolean default false,
	primary key(role, course),
	foreign key(course) references course(id) on delete cascade
);
create table user_role(
	id int unsigned not null,
	course  int unsigned not null,
	role varchar(50) not null,
	primary key(id, course, role),
	foreign key(id, course) references course_user(id, course) on delete cascade,
	foreign key(role, course) references role(role,course) on delete cascade
);
create table role_hierarchy(
	course int unsigned not null primary key,
	hierarchy text,
	foreign key(course) references course(id) on delete cascade
);

create table award(
	student int unsigned not null,
	course int unsigned not null,
	name varchar(100) not null,
	type varchar(50), #(ex:grade)
	level int default 0, #badge level
	num int,   #lab or quiz number 
	subtype varchar(50),
	reward int unsigned default 0,
	awardDate timestamp default CURRENT_TIMESTAMP, 
	primary key (student,course,name,level,type),
    foreign key(student, course) references course_user(id, course) on delete cascade
);

create table notification(#ToDo decide if this table is needed
	student int unsigned not null,
	course int unsigned not null,
	name varchar(100) not null,
	level int not null default 0,
	type varchar(50),
	#color, badgeprogress, next level, is max level, description,
	primary key (student,course,name,level,type),
	foreign key(student,course, name,level,type) references award(student,course,name,level,type) on delete cascade
);

create table level(
	minXP int unsigned not null,
	title varchar(100),
	lvlNum int unsigned not null,
	course int unsigned not null,
	primary key(course,lvlNum),
	foreign key(course) references course(id) on delete cascade
);

create table skill_tier(
	tier int unsigned not null,
	reward int unsigned not null,
	course int unsigned not null,
	primary key(course,tier),
	foreign key(course) references course(id) on delete cascade
);
create table skill(
	name varchar(50) not null,
	color varchar(10),
	page TEXT,
	tier int unsigned not null,
	course int unsigned not null,
	primary key(name,course),
	foreign key(course,tier) references skill_tier(course, tier) on delete cascade
);
create table skill_dependency(
	dependencyNum int not null,
	dependencyA varchar(50) not null,
	dependencyB varchar(50) not null,
	skillName varchar(50) not null,
	course int unsigned not null,
	primary key(dependencyNum,skillName,course),
	foreign key(skillName,course) references skill(name, course) on delete cascade
);
create table user_skill(
	skillTime timestamp default CURRENT_TIMESTAMP,
	post 	varchar(255) not null,
	quality int not null,
	student int unsigned not null,
	name varchar(50) not null,
	course int unsigned not null,
	primary key(student,course,name),
	foreign key(student, course) references course_user(id, course) on delete cascade,
	foreign key(name, course) references skill(name, course) on delete cascade
);

create table badge(
	course int unsigned not null,
	name varchar(70) not null,
	description  varchar(200) not null,
	maxLvl int not null,
	isExtra boolean not null default false,
	isBragging boolean not null default false,
	isCount boolean not null default false,
	isPost boolean not null default false,
	isPoint boolean not null default false,
	foreign key(course) references course(id) on delete cascade,
	primary key(name, course)
);
create table badge_level(
	level int not null,
	xp int not null,
	description varchar(200),
	progressNeeded int not null,
	course int unsigned not null,
	badgeName varchar(70) not null,
	foreign key(badgeName, course) references badge(name, course) on delete cascade,
	primary key(level,badgeName,course)
);
create table user_badge(
	level int default 0,
	progress int default 0,
	name varchar(70) not null,
	course int unsigned not null,
	student int unsigned not null,
	foreign key(student,course) references course_user(id, course) on delete cascade,
	foreign key(name, course) references badge(name, course) on delete cascade,
	primary key(student,name,course)
);
create table progress_indicator(
	link varchar(255),
	post varchar(255), 
	quality int,
	indicatorText varchar(50) not null,
	indicatorIndex int default 0,#used for indicator that can have repeated indicatorTexts
	badgeName varchar(70) not null,
	course int unsigned not null,
	student int unsigned not null,
	foreign key(student,badgeName,course) references user_badge(student,name,course) on delete cascade,
	primary key(indicatorIndex,indicatorText,student,badgeName,course)
);
create table badge_level_time(
	badgeName varchar(70) not null,
	badgeLevel int not null,
	badgeLvlTime timestamp default CURRENT_TIMESTAMP,
	course int unsigned not null,
	student int unsigned not null,
	foreign key(student,badgeName,course) references user_badge(student,name,course) on delete cascade,
	primary key(badgeLevel,student,badgeName,course)
);

create table module(
	moduleId varchar(50) not null primary key,
	name varchar(50)
);
create table enabled_module(
	moduleId varchar(50) not null,
	course int unsigned not null,
	primary key(moduleId, course),
	foreign key(moduleId) references module(moduleId) on delete cascade,
	foreign key(course) references course(id) on delete cascade
);

create table view(	
	viewId varchar(50),
	module varchar(50),
	type enum('VT_SINGLE','VT_ROLE_SINGLE','VT_ROLE_INTERACTION') default 'VT_ROLE_SINGLE',
	course int unsigned not null,
	name varchar(50),
	primary key(viewID, course),
	foreign key(module,course) references enabled_module(moduleId,course) on delete cascade
);
create table view_role(
	part varchar(40),
	viewId varchar(50),
	course int unsigned not null,
	replacements text, 
	role varchar(100), #if role interaction separate by '>'
	primary key(viewId,course,role),
	foreign key(viewId,course) references view(viewId,course) on delete cascade
);
create table view_part(
	viewId varchar(50),
	course int unsigned not null,
	role varchar(100),
	partContents text,
	type varchar(50),
	pid varchar(40), 
	primary key(pid,course), 
	foreign key(viewId,course,role) references view_role(viewId,course,role) on delete cascade
);
create table view_template(
	module varchar(50),
	course int unsigned not null,
	id varchar(100),
	content text,
	primary key(id,course),
	foreign key(module,course) references enabled_module(moduleId,course) on delete cascade
);

create table pending_invite(
	id 	int unsigned,
    username varchar(50),
    primary key(id)
);

create table system_info(
	theme varchar(50) default 'default',
	apiKey varchar(50),
    primary key(theme)
);

create table qr_code(
	qrkey varchar(61) not null primary key,
	course int unsigned not null,
	foreign key(course) references course(id) on delete cascade
);
create table participation(
	qrkey varchar(61) not null primary key,
	student int unsigned not null,
	course int unsigned not null,
	classNumber int not null,
	classType varchar(15) not null,
	foreign key (qrkey) references qr_code(qrkey),
	foreign key (student,course) references course_user(id,course)
);
create table qr_error (
    error_id int NOT NULL primary key auto_increment,
    student int unsigned not null,
    course int unsigned not null,
    ip varchar(15) NOT NULL,
    qrkey varchar(61) not null,
    errorDate timestamp default CURRENT_TIMESTAMP,
    #datetime timestamp without time zone NOT NULL,
    msg varchar(250) NOT NULL,
    foreign key (qrkey) references qr_code(qrkey),
    foreign key (student,course) references course_user(id,course)
);

