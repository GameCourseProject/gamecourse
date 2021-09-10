/** -----------------------------------
  * -- Drop Triggers
  * ----------------------------------- */

DROP TRIGGER IF EXISTS parameterDelete;
DROP TRIGGER IF EXISTS viewDelete;


/** -----------------------------------
  * -- Drop Tables  
  * ----------------------------------- */
  
DROP TABLE IF EXISTS aspect_class;

DROP TABLE IF EXISTS auth;

DROP TABLE IF EXISTS autogame;

DROP TABLE IF EXISTS award;
DROP TABLE IF EXISTS award_test;
DROP TABLE IF EXISTS award_participation;

DROP TABLE IF EXISTS badge;
DROP TABLE IF EXISTS badges_config;
DROP TABLE IF EXISTS badge_level;
DROP TABLE IF EXISTS badge_progression;

DROP TABLE IF EXISTS config_class_check;
DROP TABLE IF EXISTS config_google_sheets;
DROP TABLE IF EXISTS config_moodle;
DROP TABLE IF EXISTS config_qr;

DROP TABLE IF EXISTS course;
DROP TABLE IF EXISTS course_module;
DROP TABLE IF EXISTS course_user;

DROP TABLE IF EXISTS dependency;

DROP TABLE IF EXISTS dictionary_function;
DROP TABLE IF EXISTS dictionary_library;
DROP TABLE IF EXISTS dictionary_variable;

DROP TABLE IF EXISTS game_course_user;

DROP TABLE IF EXISTS level;

DROP TABLE IF EXISTS module;

DROP TABLE IF EXISTS notification;

DROP TABLE IF EXISTS page;

DROP TABLE IF EXISTS parameter;

DROP TABLE IF EXISTS participation;

DROP TABLE IF EXISTS profiling_config;

DROP TABLE IF EXISTS qr_code;
DROP TABLE IF EXISTS qr_error;

DROP TABLE IF EXISTS role;

DROP TABLE IF EXISTS saved_user_profile;

DROP TABLE IF EXISTS skill;
DROP TABLE IF EXISTS skill_dependency;
DROP TABLE IF EXISTS skill_tier;
DROP TABLE IF EXISTS skill_tree;

DROP TABLE IF EXISTS template;

DROP TABLE IF EXISTS user_profile;
DROP TABLE IF EXISTS user_role;
DROP TABLE IF EXISTS user_xp;

DROP TABLE IF EXISTS view;
DROP TABLE IF EXISTS view_parameter;
DROP TABLE IF EXISTS view_template;


/** -----------------------------------
  * -- Create Tables
  * ----------------------------------- */
  
CREATE TABLE game_course_user(
	id                          int unsigned PRIMARY KEY AUTO_INCREMENT,
    name 	                    varchar(50) NOT NULL,
    email 	                    varchar(255),
	major 	                    varchar(8),
	nickname                    varchar(50),
	studentNumber               int UNIQUE,
    isAdmin                     boolean NOT NULL DEFAULT FALSE,
	isActive                    boolean NOT NULL DEFAULT TRUE
);

CREATE TABLE auth(
	id                          int unsigned PRIMARY KEY AUTO_INCREMENT,
	game_course_user_id         int unsigned NOT NULL,
	username                    varchar(50),
	authentication_service      enum ('fenix', 'google', 'facebook', 'linkedin'),

	UNIQUE key(username, authentication_service),
	FOREIGN key(game_course_user_id) REFERENCES game_course_user(id) ON DELETE CASCADE
);

CREATE TABLE course(
	id 		                    int unsigned PRIMARY KEY AUTO_INCREMENT,
	name 	                    varchar(100),
	short	                    varchar(20),
	color	                    varchar(7),
	year	                    varchar(10),
    defaultLandingPage          varchar(100) DEFAULT '',
	lastUpdate                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	isActive                    boolean DEFAULT TRUE,
	isVisible                   boolean DEFAULT TRUE, #?
	roleHierarchy               text,
	theme                       varchar(50)
);

CREATE TABLE course_user(
    id                          int unsigned,
   	course                      int unsigned,
    lastActivity                TIMESTAMP NULL,
    previousActivity            TIMESTAMP NULL,
	isActive                    boolean NOT NULL DEFAULT TRUE,

    PRIMARY key(id, course),
    FOREIGN key(id) REFERENCES game_course_user(id) ON DELETE CASCADE,
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE role(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
	name                        varchar(50) NOT NULL,
	landingPage                 varchar(100) DEFAULT '',
	course                      int unsigned NOT NULL,

	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE user_role(
	id                          int unsigned NOT NULL, #user id
	course                      int unsigned NOT NULL,
	role                        int unsigned NOT NULL,

	PRIMARY key(id, course, role),
	FOREIGN key(id, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
	FOREIGN key(role) REFERENCES role(id) ON DELETE CASCADE
);

CREATE TABLE module(
	moduleId                    varchar(50) NOT NULL PRIMARY KEY,
	name                        varchar(50),
	description                 varchar(100),
	version                     varchar(10),
	compatibleVersions          varchar(100)
);

CREATE TABLE course_module(
	moduleId                    varchar(50) NOT NULL,
	course                      int unsigned NOT NULL,
	isEnabled                   boolean DEFAULT FALSE,

	PRIMARY key(moduleId, course),
	FOREIGN key(moduleId) REFERENCES module(moduleId) ON DELETE CASCADE,
	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE dictionary_library(
	id	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
	moduleId                    varchar(50),
	name                        varchar(50) UNIQUE NOT NULL,
	description                 varchar(255)
);

CREATE TABLE dictionary_function(
	id	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
	libraryId                   int unsigned NULL,
	returnType                  varchar(50),
	returnName                  varchar(50),
	refersToType                varchar(50) NOT NULL,
	refersToName                varchar(50),
	keyword                     varchar(50),
	args                        varchar(1000),
	description                 varchar(1000),

	FOREIGN key(libraryId) REFERENCES dictionary_library(id) ON DELETE CASCADE
);

CREATE TABLE dictionary_variable(
	id	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
	libraryId                   int unsigned NULL,
	name                        varchar(50) UNIQUE,
	returnType                  varchar(50) NOT NULL,
	returnName                  varchar(50),
	description                 varchar(1000),

	FOREIGN key(libraryId) REFERENCES dictionary_library(id) ON DELETE CASCADE
);

CREATE TABLE award(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
	user 	                    int unsigned NOT NULL,
	course 	                    int unsigned NOT NULL,
	description                 varchar(100) NOT NULL,
	type                        varchar(50) NOT NULL, #(ex:grade,skills, labs,quiz,presentation,bonus)
	moduleInstance              int unsigned ,#id of badge/skill (will be null for other types)
	reward                      int unsigned DEFAULT 0,
	date                        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

CREATE TABLE award_test(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
	user 	                    int unsigned NOT NULL,
	course 	                    int unsigned NOT NULL,
    description                 varchar(100) NOT NULL,
	type                        varchar(50) NOT NULL, #(ex:grade,skills, labs,quiz,presentation,bonus)
	moduleInstance              int unsigned , #id of badge/skill (will be null for other types)
	reward                      int unsigned DEFAULT 0,
	date                        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

CREATE TABLE notification(
	id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
	award                       int unsigned NOT NULL,
	checked                     boolean DEFAULT FALSE,

	FOREIGN key(award) REFERENCES award(id) ON DELETE CASCADE
);

CREATE TABLE participation(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
	user 	                    int unsigned NOT NULL,
	course 	                    int unsigned NOT NULL,
	description                 varchar(500) NOT NULL,
    type 	                    varchar(50) NOT NULL, #(ex:grade,skill,badge, lab,quiz,presentation,bonus)
	post 	                    varchar(255),
	date                        TIMESTAMP,
	rating                      int,
	evaluator                   int unsigned,

	FOREIGN key(evaluator,course) REFERENCES course_user(id,course) ON DELETE CASCADE, #needs trigger to set eval to null
    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

CREATE TABLE award_participation(
	award                       int unsigned,
	participation               int unsigned,

	PRIMARY key(award,participation),
    FOREIGN key(award) REFERENCES award(id) ON DELETE CASCADE,
    FOREIGN key(participation) REFERENCES participation(id) ON DELETE CASCADE
);

CREATE TABLE view(
	id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
	viewId                      int unsigned,
	#aspectClass int unsigned,
	role                        varchar(100) DEFAULT 'role.Default',
	partType                    enum ('block', 'text', 'image', 'table', 'headerRow', 'row', 'header', 'chart'),
	#parent int unsigned,
	#viewIndex int unsigned,
	label                       varchar(50),
	loopData                    varchar(200),
	variables                   varchar(500),
	value                       varchar(200),
	class                       varchar(50),
	cssId                       varchar(50),
	style                       varchar(200),
	link                        varchar(100),
	visibilityCondition         varchar(200),
	visibilityType              enum ('visible', 'invisible', 'conditional'),
	events                      varchar(500),
	info                        varchar(500)

	#FOREIGN key (aspectClass) REFERENCES aspect_class(aspectClass) on delete set null
	#FOREIGN key (parent) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_parent(
	parentId                    int unsigned,
	childId                     int unsigned,
	viewIndex                   int unsigned,

	#PRIMARY key(parentId,childId),
	FOREIGN key(parentId) REFERENCES view(id) ON DELETE CASCADE
	#FOREIGN key(childId) REFERENCES view(viewId) ON DELETE CASCADE
);

CREATE TABLE page(
	id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
	course                      int unsigned NOT NULL,
	#roleType enum('ROLE_SINGLE','ROLE_INTERACTION') DEFAULT 'ROLE_SINGLE',
	name                        varchar(50) NOT NULL,
	theme                       varchar(50),
	viewId                      int unsigned,
	isEnabled                   boolean DEFAULT FALSE,
	seqId                       int unsigned NOT NULL,

	#FOREIGN key(viewId) REFERENCES view(id) on delete set null,
	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE template(
	id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
	name                        varchar(100) NOT NULL,
	roleType                    enum('ROLE_SINGLE','ROLE_INTERACTION') DEFAULT 'ROLE_SINGLE',
	course                      int unsigned NOT NULL,
	isGlobal                    boolean DEFAULT FALSE,

	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE view_template(
	viewId                      int unsigned PRIMARY KEY,
	templateId                  int unsigned,

	FOREIGN key (templateId) REFERENCES template(id) ON DELETE CASCADE
	#FOREIGN key (viewId) REFERENCES view(viewId) ON DELETE CASCADE
);

CREATE TABLE autogame( #table used for gamerules related info
	course 	                    int unsigned NOT NULL PRIMARY KEY,
	startedRunning              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	finishedRunning             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	isRunning                   boolean DEFAULT FALSE,

	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);


SET FOREIGN_KEY_CHECKS=0;
INSERT INTO autogame (course, isRunning) values (0, FALSE);
SET FOREIGN_KEY_CHECKS=1;


#ToDO add trigger when delete level or badge -> delete bagde_has_level