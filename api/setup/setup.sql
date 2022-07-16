/*** ---------------------------------------------------- ***/
/*** ------------------- User tables -------------------- ***/
/*** ---------------------------------------------------- ***/

CREATE TABLE user(
	id                          int unsigned PRIMARY KEY AUTO_INCREMENT,
    name 	                    varchar(60) NOT NULL,
    email 	                    varchar(60) UNIQUE,
	major 	                    varchar(8),
	nickname                    varchar(50),
	studentNumber               int UNIQUE,
    isAdmin                     boolean NOT NULL DEFAULT FALSE,
	isActive                    boolean NOT NULL DEFAULT TRUE
);

CREATE TABLE auth(
	user                        int unsigned NOT NULL,
	username                    varchar(50) NOT NULL,
	authentication_service      ENUM ('fenix', 'google', 'facebook', 'linkedin'),
    lastLogin                   TIMESTAMP NULL,

    PRIMARY key(username, authentication_service),
	FOREIGN key(user) REFERENCES user(id) ON DELETE CASCADE
);


/*** ---------------------------------------------------- ***/
/*** ------------------ Course tables ------------------- ***/
/*** ---------------------------------------------------- ***/

SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE course(
    id 		                    int unsigned PRIMARY KEY AUTO_INCREMENT,
    name 	                    varchar(100) NOT NULL,
    short	                    varchar(20),
    color	                    varchar(7),
    year	                    varchar(10) NOT NULL,
    startDate                   TIMESTAMP NULL DEFAULT NULL,
    endDate                     TIMESTAMP NULL DEFAULT NULL,
    landingPage                 int unsigned DEFAULT NULL,
    isActive                    boolean DEFAULT TRUE,
    isVisible                   boolean DEFAULT TRUE,
    roleHierarchy               text,
    theme                       varchar(50) DEFAULT NULL,

    UNIQUE key(name, year),
    FOREIGN key(landingPage) REFERENCES page(id) ON DELETE CASCADE
);
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE course_user(
    id                          int unsigned NOT NULL,
    course                      int unsigned NOT NULL,
    lastActivity                TIMESTAMP NULL,
    isActive                    boolean NOT NULL DEFAULT TRUE,

    PRIMARY key(id, course),
    FOREIGN key(id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);


/*** ---------------------------------------------------- ***/
/*** -------------------- Role tables ------------------- ***/
/*** ---------------------------------------------------- ***/

SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE role(
    id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    name                        varchar(50) NOT NULL,
    landingPage                 int unsigned DEFAULT NULL,
    course                      int unsigned NOT NULL,
    module                      varchar(50) DEFAULT NULL,

    UNIQUE key(course, name),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(landingPage) REFERENCES page(id) ON DELETE CASCADE,
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE user_role(
    user                        int unsigned NOT NULL,
    course                      int unsigned NOT NULL,
    role                        int unsigned NOT NULL,

    PRIMARY key(user, course, role),
    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(role) REFERENCES role(id) ON DELETE CASCADE
);


/*** ---------------------------------------------------- ***/
/*** ------------------ Module tables ------------------- ***/
/*** ---------------------------------------------------- ***/

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
    mode                        ENUM ('hard', 'soft') NOT NULL,

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


/*** ---------------------------------------------------- ***/
/*** ------------------- Views tables ------------------- ***/
/*** ---------------------------------------------------- ***/

CREATE TABLE aspect(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    viewerRole                  int unsigned DEFAULT NULL,
    userRole                    int unsigned DEFAULT NULL,

    UNIQUE key(course, viewerRole, userRole),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(viewerRole) REFERENCES role(id) ON DELETE CASCADE,
    FOREIGN key(userRole) REFERENCES role(id) ON DELETE CASCADE
);

CREATE TABLE view_type(
    id                          varchar(50) NOT NULL PRIMARY KEY,
    description                 varchar(100) NOT NULL,
    module                      varchar(50) DEFAULT NULL,

    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE view(
    id                          bigint unsigned NOT NULL PRIMARY KEY,
    type                        varchar(50) NOT NULL,
    cssId                       varchar(50) DEFAULT NULL,
    class                       varchar(200) DEFAULT NULL,
    style                       varchar(255) DEFAULT NULL,
    visibilityType              ENUM ('visible', 'invisible', 'conditional') DEFAULT 'visible',
    visibilityCondition         varchar(200) DEFAULT NULL,
    loopData                    varchar(500) DEFAULT NULL,

    FOREIGN key(type) REFERENCES view_type(id) ON DELETE CASCADE
);

CREATE TABLE view_aspect(
    viewRoot                    bigint unsigned NOT NULL,
    aspect                      int unsigned NOT NULL,
    view                        bigint unsigned NOT NULL,

    PRIMARY key(viewRoot, aspect),
    FOREIGN key(aspect) REFERENCES aspect(id) ON DELETE CASCADE,
    FOREIGN key(view) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_variable(
    view                        bigint unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    value                       varchar(200) NOT NULL,
    position                    int unsigned,

    UNIQUE key(view, name),
    UNIQUE key(view, position),
    FOREIGN key(view) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_event(
    view                        bigint unsigned NOT NULL,
    type                        ENUM ('click', 'dblclick', 'mouseover', 'mouseout', 'mouseup', 'wheel', 'drag') NOT NULL,
    action                      varchar(200) NOT NULL,

    UNIQUE key(view, type),
    FOREIGN key(view) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_parent(
    parent                      bigint unsigned NOT NULL,
    child                       bigint unsigned NOT NULL,
    position                    int unsigned,

    UNIQUE key(parent, position),
    FOREIGN key(parent) REFERENCES view(id) ON DELETE CASCADE,
    FOREIGN key(child) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE
);

CREATE TABLE view_tree(
    viewTree                    bigint unsigned NOT NULL,
    viewRoot                    bigint unsigned NOT NULL,

    PRIMARY key(viewTree, viewRoot),
    FOREIGN key(viewTree) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE
);

CREATE TABLE view_category(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    name                        varchar(25) NOT NULL
);

CREATE TABLE view_category_order(
    parent                      int unsigned DEFAULT NULL,
    child                       int unsigned NOT NULL,
    position                    int unsigned NOT NULL,

    UNIQUE key(parent, position),
    UNIQUE key(parent, child),
    FOREIGN key(parent) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(child) REFERENCES view_category(id) ON DELETE CASCADE
);

CREATE TABLE component_core(
    viewRoot                    bigint unsigned PRIMARY KEY,
    description                 varchar(50) DEFAULT NULL,
    category                    int unsigned NOT NULL,
    position                    int unsigned NOT NULL,
    module                      varchar(50) DEFAULT NULL,

    UNIQUE key(category, position),
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(category) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE component_custom(
    viewRoot                    bigint unsigned PRIMARY KEY,
    name                        varchar(25) NOT NULL,
    creationTimestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateTimestamp             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    course                      int unsigned NOT NULL,
    module                      varchar(50) DEFAULT NULL,

    UNIQUE key(course, name),
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE component_global(
    viewRoot                    bigint unsigned PRIMARY KEY,
    description                 varchar(50) DEFAULT NULL,
    category                    int unsigned NOT NULL,
    sharedBy                    int unsigned NOT NULL,
    sharedTimestamp             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(category) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(sharedBy) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE template_core(
    viewRoot                    bigint unsigned PRIMARY KEY,
    name                        varchar(50) NOT NULL,
    category                    int unsigned NOT NULL,
    module                      varchar(50) DEFAULT NULL,

    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(category) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE template_global(
    viewRoot                    bigint unsigned PRIMARY KEY,
    name                        varchar(50) NOT NULL,
    category                    int unsigned NOT NULL,
    sharedBy                    int unsigned NOT NULL,

    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(category) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(sharedBy) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE page(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    name                        varchar(25) NOT NULL,
    viewRoot                    bigint unsigned NOT NULL,
    creationTimestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateTimestamp             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    visibleFrom                 TIMESTAMP NULL DEFAULT NULL,
    visibleUntil                TIMESTAMP NULL DEFAULT NULL,
    position                    int unsigned NOT NULL,
    course                      int unsigned NOT NULL,

    UNIQUE key(course, name),
    UNIQUE key(course, position),
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);


/*** ---------------------------------------------------- ***/
/*** ----------------- AutoGame tables ------------------ ***/
/*** ---------------------------------------------------- ***/

CREATE TABLE autogame(
    course 	                    int unsigned NOT NULL PRIMARY KEY,
    startedRunning              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finishedRunning             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    isRunning                   boolean DEFAULT FALSE,
    periodicityNumber           int unsigned DEFAULT 10,
    periodicityTime             varchar(25) DEFAULT 'Minutes',

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);


SET FOREIGN_KEY_CHECKS=0;
INSERT INTO autogame (course, periodicityNumber, periodicityTime) values (0, NULL, NULL);
SET FOREIGN_KEY_CHECKS=1;

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

    FOREIGN key(evaluator, course) REFERENCES course_user(id, course) ON DELETE CASCADE, #needs trigger to set eval to null
    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

