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
	theme                       ENUM ('light', 'dark') DEFAULT NULL,
    isAdmin                     boolean NOT NULL DEFAULT FALSE,
	isActive                    boolean NOT NULL DEFAULT TRUE
);

CREATE TABLE auth(
	user                        int unsigned NOT NULL,
	username                    varchar(50) NOT NULL,
	auth_service                ENUM ('fenix', 'google', 'facebook', 'linkedin'),
    lastLogin                   TIMESTAMP NULL,

    PRIMARY key(username, auth_service),
	FOREIGN key(user) REFERENCES user(id) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE IF NOT EXISTS user_page_history(
    course          int unsigned NOT NULL,
    page            int unsigned NOT NULL,
    viewer          int unsigned NOT NULL,
    user            int unsigned DEFAULT NULL,
    date            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(page) REFERENCES page(id) ON DELETE CASCADE,
    FOREIGN key(viewer, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);
SET FOREIGN_KEY_CHECKS=1;


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
    description                 varchar(150) NOT NULL,
    type                        ENUM ('GameElement', 'DataSource', 'Util') NOT NULL,
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
/*** -------------- Adaptation tables ------------------- ***/
/*** ---------------------------------------------------- ***/

CREATE TABLE game_element(
    id          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course      int unsigned NOT NULL,
    module      varchar(50) NOT NULL,
    isActive    boolean NOT NULL DEFAULT FALSE,
    notify      boolean DEFAULT FALSE,

    UNIQUE key(course, module),
    FOREIGN KEY (course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN KEY (module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE user_game_element_preferences(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    user                        int unsigned NOT NULL,
    module                      varchar(50) NOT NULL,
    previousPreference          int unsigned,
    newPreference               int unsigned NOT NULL,
    date                        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE key(user, previousPreference, date),
    FOREIGN KEY (course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN KEY (user) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (module) REFERENCES module(id) ON DELETE CASCADE,
    FOREIGN KEY (newPreference) REFERENCES role(id) ON DELETE CASCADE
);

CREATE TABLE preferences_questionnaire_answers(
    id          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course      int unsigned NOT NULL,
    user        int unsigned NOT NULL,
    question1   boolean NOT NULL DEFAULT FALSE,
    question2   varchar(250),   /* default here is null */
    question3   int unsigned,   /* default here is 0 */
    element     int unsigned NOT NULL,

    UNIQUE(course, user, element),
    FOREIGN KEY (course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN KEY (user) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (element) REFERENCES game_element(id) ON DELETE CASCADE
);

CREATE TABLE element_user(
     element     int unsigned NOT NULL,
     user        int unsigned NOT NULL,

     PRIMARY key(element, user),
     FOREIGN KEY (element) REFERENCES game_element(id) ON DELETE CASCADE,
     FOREIGN KEY (user) REFERENCES course_user(id) ON DELETE CASCADE
);

CREATE TABLE element_versions_descriptions (
    element         int unsigned NOT NULL,
    description     varchar(150) NOT NULL,

    PRIMARY KEY (element),
    FOREIGN KEY (element) REFERENCES role(id) ON DELETE CASCADE
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
    description                 TEXT NOT NULL
);

CREATE TABLE view(
    id                          bigint unsigned NOT NULL PRIMARY KEY,
    type                        varchar(50) NOT NULL,
    cssId                       varchar(50) DEFAULT NULL,
    class                       TEXT DEFAULT NULL,
    style                       TEXT DEFAULT NULL,
    visibilityType              ENUM ('visible', 'invisible', 'conditional') DEFAULT 'visible',
    visibilityCondition         TEXT DEFAULT NULL,
    loopData                    TEXT DEFAULT NULL,

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
    value                       TEXT NOT NULL,
    position                    int unsigned,

    UNIQUE key(view, name),
    UNIQUE key(view, position),
    FOREIGN key(view) REFERENCES view(id) ON DELETE CASCADE
);

CREATE TABLE view_event(
    view                        bigint unsigned NOT NULL,
    type                        ENUM ('click', 'dblclick', 'mouseover', 'mouseout', 'mouseup', 'wheel', 'drag') NOT NULL,
    action                      TEXT NOT NULL,

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
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    viewRoot                    bigint unsigned NOT NULL,
    description                 varchar(70) DEFAULT NULL,
    category                    int unsigned NOT NULL,
    position                    int unsigned,
    module                      varchar(50) DEFAULT NULL,

    UNIQUE key(category, position),
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(category) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE component_custom(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    viewRoot                    bigint unsigned NOT NULL,
    name                        varchar(25) NOT NULL,
    creationTimestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateTimestamp             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    course                      int unsigned NOT NULL,

    UNIQUE key(course, name),
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE component_custom_shared(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    description                 varchar(70) DEFAULT NULL,
    category                    int unsigned NOT NULL,
    sharedBy                    int unsigned NOT NULL,
    sharedTimestamp             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(id) REFERENCES component_custom(id) ON DELETE CASCADE,
    FOREIGN key(category) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(sharedBy) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE template_core(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    viewRoot                    bigint unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    category                    int unsigned NOT NULL,
    position                    int unsigned,
    module                      varchar(50) DEFAULT NULL,

    UNIQUE key(category, position),
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(category) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE template_custom(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    viewRoot                    bigint unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    creationTimestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateTimestamp             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    course                      int unsigned NOT NULL,

    UNIQUE key(course, name),
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE template_custom_shared(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    description                 varchar(70) DEFAULT NULL,
    category                    int unsigned NOT NULL,
    sharedBy                    int unsigned NOT NULL,
    sharedTimestamp             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN key(id) REFERENCES component_custom(id) ON DELETE CASCADE,
    FOREIGN key(category) REFERENCES view_category(id) ON DELETE CASCADE,
    FOREIGN key(sharedBy) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE page(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    name                        varchar(100) NOT NULL,
    isVisible                   boolean DEFAULT FALSE,
    viewRoot                    bigint unsigned NOT NULL,
    creationTimestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateTimestamp             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    visibleFrom                 TIMESTAMP NULL DEFAULT NULL,
    visibleUntil                TIMESTAMP NULL DEFAULT NULL,
    position                    int unsigned DEFAULT NULL,

    UNIQUE key(course, name),
    UNIQUE key(course, position),
    FOREIGN key(viewRoot) REFERENCES view_aspect(viewRoot) ON DELETE CASCADE,
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

/*** ------------------------------------------------------------ ***/
/*** ------------------- Notification tables -------------------- ***/
/*** ------------------------------------------------------------ ***/

CREATE TABLE notification(
     id             int unsigned PRIMARY KEY AUTO_INCREMENT,
     course         int unsigned DEFAULT NULL,
     user           int unsigned NOT NULL,
     message        TEXT NOT NULL,
     isShowed       boolean NOT NULL DEFAULT FALSE,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(user) REFERENCES user(id) ON DELETE CASCADE
);

/*** ---------------------------------------------------- ***/
/*** ----------------- AutoGame tables ------------------ ***/
/*** ---------------------------------------------------- ***/

CREATE TABLE autogame(
    course 	                    int unsigned NOT NULL PRIMARY KEY,
    isEnabled                   boolean DEFAULT FALSE,
    startedRunning              TIMESTAMP NULL DEFAULT NULL,
    finishedRunning             TIMESTAMP NULL DEFAULT NULL,
    isRunning                   boolean DEFAULT FALSE,
    runNext                     boolean DEFAULT FALSE,
    checkpoint                  TIMESTAMP NULL DEFAULT NULL,
    frequency                   varchar(50) DEFAULT '*/10 * * * *',

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);


SET FOREIGN_KEY_CHECKS=0;
INSERT INTO autogame (course, frequency) values (0, NULL);
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE rule_section(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    position                    int unsigned,
    module                      varchar(50) DEFAULT NULL,

    UNIQUE key(name, course),
    UNIQUE key(position, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(module) REFERENCES module(id) ON DELETE CASCADE
);

CREATE TABLE rule_tag(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    color                       varchar(7) NOT NULL,

    UNIQUE key(name, course),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE rule(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    section                     int unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    description                 TEXT,
    whenClause                  TEXT NOT NULL,
    thenClause                  TEXT NOT NULL,
    isActive                    boolean NOT NULL DEFAULT TRUE,
    position                    int unsigned,

    UNIQUE key(name, course),
    UNIQUE key(name, section),
    UNIQUE key(position, section),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(section) REFERENCES rule_section(id) ON DELETE CASCADE
);

CREATE TABLE rule_tags(
    rule                        int unsigned NOT NULL,
    tag                         int unsigned NOT NULL,

    FOREIGN key(rule) REFERENCES rule(id) ON DELETE CASCADE,
    FOREIGN key(tag) REFERENCES rule_tag(id) ON DELETE CASCADE
);

CREATE TABLE participation(
    id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    user 	                    int unsigned NOT NULL,
    course 	                    int unsigned NOT NULL,
    source                      varchar(50) NOT NULL DEFAULT 'GameCourse',
    description                 TEXT NOT NULL,
    type 	                    varchar(50) NOT NULL,
    post 	                    TEXT DEFAULT NULL,
    date                        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rating                      int DEFAULT NULL,
    evaluator                   int unsigned DEFAULT NULL,

    FOREIGN key(evaluator, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE
);

