CREATE TABLE IF NOT EXISTS skills_config(
    course                      int unsigned PRIMARY KEY,
    maxExtraCredit              int unsigned NOT NULL DEFAULT 0,

    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS skill_tree(
    id 		                    int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    name                        varchar(50),
    maxReward                   int unsigned NOT NULL DEFAULT 0,

    UNIQUE key(course, name),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS skill_tier(
    id 	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
    skillTree                   int unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    reward                      int unsigned NOT NULL,
    position                    int unsigned,
    isActive                    boolean NOT NULL DEFAULT TRUE,

    UNIQUE key(skillTree, name),
    UNIQUE key(skillTree, position),
    FOREIGN key(skillTree) REFERENCES skill_tree(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS skill(
    id 	                        int unsigned AUTO_INCREMENT PRIMARY KEY,
    course                      int unsigned NOT NULL,
    tier                        int unsigned NOT NULL,
    name                        varchar(50) NOT NULL,
    color                       varchar(7),
    page                        TEXT,
    isCollab                    boolean NOT NULL DEFAULT FALSE,
    isExtra                     boolean NOT NULL DEFAULT FALSE,
    isActive                    boolean NOT NULL DEFAULT TRUE,
    position                    int unsigned,
    rule                        int unsigned NOT NULL,

    UNIQUE key(course, name),
    UNIQUE key(tier, position),
    FOREIGN key(course) REFERENCES course(id) ON DELETE CASCADE,
    FOREIGN key(tier) REFERENCES skill_tier(id) ON DELETE CASCADE,
    FOREIGN key(rule) REFERENCES rule(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS skill_dependency(
    id                          int unsigned AUTO_INCREMENT PRIMARY KEY,
    skill                       int unsigned NOT NULL,

    FOREIGN key(skill) REFERENCES skill(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS skill_dependency_combo(
    dependency                  int unsigned NOT NULL,
    skill                       int unsigned DEFAULT NULL,
    wildcard                    boolean NOT NULL DEFAULT FALSE,

    UNIQUE key(dependency, skill),
    FOREIGN key(dependency) REFERENCES skill_dependency(id) ON DELETE CASCADE,
    FOREIGN key(skill) REFERENCES skill(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS award_wildcard( /* awards that used wildcards */
    user                        int unsigned NOT NULL,
    course                      int unsigned NOT NULL,
    award                       int unsigned NOT NULL,
    nrWildcardsUsed             int unsigned DEFAULT 1,

    FOREIGN key(user, course) REFERENCES course_user(id, course) ON DELETE CASCADE,
    FOREIGN key(award) REFERENCES award(id) ON DELETE CASCADE
);