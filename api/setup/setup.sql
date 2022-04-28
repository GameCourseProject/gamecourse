/** -----------------------------------
  * -- Create Tables
  * ----------------------------------- */

CREATE TABLE game_course_user(
	id                          int unsigned PRIMARY KEY AUTO_INCREMENT,
    name 	                    varchar(50) NOT NULL,
    email 	                    varchar(255) UNIQUE,
	major 	                    varchar(8),
	nickname                    varchar(50),
	studentNumber               int UNIQUE,
    isAdmin                     boolean NOT NULL DEFAULT FALSE,
	isActive                    boolean NOT NULL DEFAULT TRUE
);

CREATE TABLE auth(
	id                          int unsigned PRIMARY KEY AUTO_INCREMENT,
	game_course_user_id         int unsigned NOT NULL,
	username                    varchar(50) NOT NULL,
	authentication_service      ENUM ('fenix', 'google', 'facebook', 'linkedin'),

	UNIQUE key(username, authentication_service),
	FOREIGN key(game_course_user_id) REFERENCES game_course_user(id) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE course(
	id 		                    int unsigned PRIMARY KEY AUTO_INCREMENT,
	name 	                    varchar(100) NOT NULL,
	short	                    varchar(20),
	color	                    varchar(7),
	year	                    varchar(10),
	startDate                   TIMESTAMP NULL DEFAULT NULL,
	endDate                     TIMESTAMP NULL DEFAULT NULL,
    landingPage                 int unsigned DEFAULT NULL,
	lastUpdate                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	isActive                    boolean DEFAULT TRUE,
	isVisible                   boolean DEFAULT TRUE,
	roleHierarchy               text,
	theme                       varchar(50),

    UNIQUE key(name, year),
    FOREIGN key(landingPage) REFERENCES page(id) ON DELETE CASCADE
);
SET FOREIGN_KEY_CHECKS=1;

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

CREATE TABLE page(
     id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
     course                      int unsigned NOT NULL,
     name                        varchar(50) NOT NULL,
     theme                       varchar(50),
     viewId                      int unsigned,
     isEnabled                   boolean DEFAULT FALSE,
     seqId                       int unsigned NOT NULL,

     FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE role(
	id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
	name                        varchar(50) NOT NULL,
	landingPage                 int unsigned DEFAULT NULL,
	course                      int unsigned NOT NULL,

	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
	FOREIGN key(landingPage) REFERENCES page(id) ON DELETE CASCADE
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
	id                          varchar(50) NOT NULL PRIMARY KEY,
	name                        varchar(50) NOT NULL,
	description                 varchar(100) NOT NULL,
	type                        ENUM ('GameElement', 'DataSource') NOT NULL,
	version                     varchar(10) NOT NULL,
    minProjectVersion           varchar(10) NOT NULL,
    maxProjectVersion           varchar(10),
    minAPIVersion               varchar(10) NOT NULL,
    maxAPIVersion               varchar(10)
);

CREATE TABLE module_dependency(
    module                      varchar(50) NOT NULL,
    dependency                  varchar(50) NOT NULL,
    minDependencyVersion        varchar(10) NOT NULL,
    maxDependencyVersion        varchar(10),
    mode                        ENUM ('hard', 'soft') NOT NULL,  -- Hard: mandatory to work; Soft: some features might not work

    PRIMARY key(module, dependency),
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE,
    FOREIGN key(dependency) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE course_module(
    course                      int unsigned NOT NULL,
	module                      varchar(50) NOT NULL,
	isEnabled                   boolean DEFAULT FALSE,
	minModuleVersion            varchar(10) NOT NULL,
	maxModuleVersion            varchar(10),

	PRIMARY key(module, course),
	FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE,
	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE dictionary_view_type(
   id	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
   name                         varchar(50) UNIQUE NOT NULL,
   description                  varchar(255)
);

CREATE TABLE dictionary_library(
	id	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
	module                      varchar(50),
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
    type                        ENUM ('text', 'image', 'header', 'block', 'table', 'row', 'chart'),
    role                        varchar(100) DEFAULT 'role.Default',
    style                       varchar(200),
    cssId                       varchar(50),
    class                       varchar(200),
    label                       varchar(50),
    visibilityType              ENUM ('visible', 'invisible', 'conditional'),
    visibilityCondition         varchar(200),
    loopData                    varchar(500),
    variables                   varchar(500),
    events                      varchar(500)
);

CREATE TABLE view_text(
     id                          int unsigned NOT NULL PRIMARY KEY,
     value                       varchar(500) NOT NULL,
     link                        varchar(200),

     FOREIGN key(id) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_image(
     id                          int unsigned NOT NULL PRIMARY KEY,
     src                         varchar(200) NOT NULL,
     link                        varchar(200),

     FOREIGN key(id) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_header(
     id                          int unsigned NOT NULL PRIMARY KEY,
     image                       int unsigned NOT NULL,
     title                       int unsigned NOT NULL,

     FOREIGN key(id) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_table_header(
    id                          int unsigned NOT NULL,
    headerRow                   int unsigned NOT NULL,
    viewIndex                   int unsigned NOT NULL,

    FOREIGN key(id) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_table_row(
    id                          int unsigned NOT NULL,
    row                         int unsigned NOT NULL,
    viewIndex                   int unsigned NOT NULL,

    FOREIGN key(id) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_chart(
    id                          int unsigned NOT NULL,
    chartType                   ENUM ('line', 'bar', 'star', 'progress'),
    info                        varchar(500),

    FOREIGN key(id) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_parent(
    parentId                    int unsigned,
    childId                     int unsigned,
    viewIndex                   int unsigned,

    FOREIGN key(parentId) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE template(
	id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
	name                        varchar(100) NOT NULL,
	roleType                    ENUM ('ROLE_SINGLE','ROLE_INTERACTION') DEFAULT 'ROLE_SINGLE',
	course                      int unsigned NOT NULL,
	isGlobal                    boolean DEFAULT FALSE,

	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE view_template(
	viewId                      int unsigned NOT NULL,
	templateId                  int unsigned NOT NULL,

	FOREIGN key(templateId) REFERENCES template(id) ON DELETE CASCADE
);

CREATE TABLE template_role(
    templateId                  int unsigned NOT NULL,
    role                        varchar(100),

    FOREIGN key(templateId) REFERENCES template(id) ON DELETE CASCADE
);

CREATE TABLE template_module(
    templateId                  int unsigned NOT NULL,
    module                      varchar(50),

    FOREIGN key(templateId) REFERENCES template(id) ON DELETE CASCADE,
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE autogame(
	course 	                    int unsigned NOT NULL PRIMARY KEY,
	startedRunning              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	finishedRunning             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	isRunning                   boolean DEFAULT FALSE,
    periodicityNumber           int NULL DEFAULT 10,
    periodicityTime             varchar(25) NULL DEFAULT 'Minutes',

	FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);


SET FOREIGN_KEY_CHECKS=0;
INSERT INTO autogame (course, periodicityNumber, periodicityTime) values (0, NULL, NULL);
SET FOREIGN_KEY_CHECKS=1;