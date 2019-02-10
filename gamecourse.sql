drop table if exists qr_error;
drop table if exists qr_participation;
drop table if exists qr;
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
drop table if exists award;
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
	headerLink varchar(255) default "",
	defaultLandingPage varchar(100) default ""
);

create table course_user
   (id  int unsigned not null,
   	course  int unsigned not null,
    campus 	char(1),
    XP 	    int unsigned default 0,
    level 	int unsigned default 0,
    roles   set('Student','Teacher') default 'Student',
    lastActivity int unsigned,
    prevActivity int unsigned,
    totalTreeXP int unsigned default 0,
    countedTreeXP int unsigned default 0,
    numSkills 	int unsigned default 0,
    totalBadgeXP int unsigned default 0,
    normslBadgeXP int unsigned default 0,
    extraBadgeXP int unsigned default 0,
    countedBadgeXP int unsigned default 0,
    numBadgeLvls int unsigned default 0,
    primary key(id, course),
    foreign key(id) references user(id),
    foreign key(course) references course(id)
);
create table role(
	name varchar(50) not null,
	landingPage varchar(100) default '/',
	hierarchy int not null,
	course int unsigned not null,
	primary key(name, course),
	foreign key(course) references course(id)
);
create table award(
	student int unsigned not null,
	course int unsigned not null,
	type varchar(50), #(ex:grade)
	subtype varchar(50),
	reward int unsigned default 0,
	awardDate timestamp default CURRENT_TIMESTAMP, #para usar defaulr tem que ser timestamp, o SB usa apenas ints em vez de datas
	awardName varchar(100) not null,
	level int, #badge level
	num int,   #lab or quiz number (basically the same thing as lvl)
	primary key (student,course,awardName),
    foreign key(student, course) references course_user(id, course)
);
create table module(
	moduleId varchar(100) not null primary key,
	name varchar(100),
	version varchar(10)
	#dependencies,directory,parent,resources(files),factory
);
create table enabled_module(
	moduleId varchar(100) not null,
	course int unsigned not null,
	primary key(moduleId, course),
	foreign key(moduleId) references module(moduleId),
	foreign key(course) references course(id)
);
create table level(
	minXP int unsigned not null,
	title varchar(100),
	lvlNum int unsigned not null,
	course int unsigned not null,
	primary key(course,lvlNum),
	foreign key(course) references course(id)
);

create table qr(
	qrCode varchar(61) not null primary key,
	course int unsigned not null,
	foreign key(course) references course(id)
);
create table qr_error(
	errorID int not null,
	course int unsigned not null,
	studentID int unsigned not null, 
	ip varchar(15) not null,
	errorDate timestamp default CURRENT_TIMESTAMP,
	qrCode varchar(61) not null,
	primary key(errorID,qrCode),
	foreign key(studentID, course) references course_user(id, course),
	foreign key(qrCode) references qr(qrCode)
);
create table qr_participation(
	course int unsigned not null,
	studentID int  unsigned not null, 
	classNum int not null,
	classType varchar(15) not null,
	qrCode varchar(61) not null,
	primary key(qrCode),
	foreign key(qrCode) references qr(qrCode),
	foreign key(studentID, course) references course_user(id, course)
);

create table skill_tier(
	tier int unsigned not null,
	reward int unsigned not null,
	course int unsigned not null,
	primary key(course,tier),
	foreign key(course) references course(id)
);
create table skill(
	skillName varchar(50) not null,
	color varchar(10),
	page varchar(255),
	tier int unsigned not null,
	course int unsigned not null,
	primary key(skillName,course),
	foreign key(course,tier) references skill_tier(course, tier)
);
create table skill_dependency(
	dependencyNum int not null,
	dependencyA varchar(50) not null,
	dependencyB varchar(50) not null,
	skillName varchar(50) not null,
	course int unsigned not null,
	primary key(dependencyNum,skillName,course),
	foreign key(skillName,course) references skill(skillName, course)
);
create table user_skill(
	skillTime timestamp default CURRENT_TIMESTAMP,
	post 	varchar(255) not null,
	quality int not null,
	student int unsigned not null,
	skillName varchar(50) not null,
	course int unsigned not null,
	primary key(student,course,skillName),
	foreign key(student, course) references course_user(id, course),
	foreign key(skillName, course) references skill(skillName, course)
);

create table badge(
	course int unsigned not null,
	badgeName varchar(70) not null,
	badgeDescription  varchar(200) not null,
	maxLvl int not null,
	isExtra boolean not null default false,
	isBragging boolean not null default false,
	isCount boolean not null default false,
	isPost boolean not null default false,
	isPoint boolean not null default false,
	foreign key(course) references course(id),
	primary key(badgeName, course)
);
create table badge_level(
	level int not null,
	xp int not null,
	description varchar(200),
	progressNeeded int not null,
	course int unsigned not null,
	badgeName varchar(70) not null,
	foreign key(badgeName, course) references badge(badgeName, course),
	primary key(level,badgeName,course)
);
create table user_badge(
	level int not null,
	progress int not null,
	badgeName varchar(70) not null,
	course int unsigned not null,
	student int unsigned not null,
	foreign key(student,course) references course_user(id, course),
	foreign key(badgeName, course) references badge(badgeName, course),
	primary key(student,badgeName,course)
);
create table progress_indicator(
	progressNum int not null,
	quality int,
	link varchar(255),
	post varchar(255), #same as link? 
	indicatorText varchar(50) not null,
	badgeName varchar(70) not null,
	course int unsigned not null,
	student int unsigned not null,
	foreign key(student,badgeName,course) references user_badge(student,badgeName,course),
	primary key(progressNum,student,badgeName,course)
);
create table badge_level_time(
	badgeLevel int not null,
	badgeLvlTime timestamp default CURRENT_TIMESTAMP,
	badgeName varchar(70) not null,
	course int unsigned not null,
	student int unsigned not null,
	foreign key(student,badgeName,course) references user_badge(student,badgeName,course),
	primary key(badgeLevel,student,badgeName,course)
);