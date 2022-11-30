<?php
namespace GameCourse\Course;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\AutoGame\RuleSystem\CourseRule;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\Core\Auth;
use GameCourse\Core\Core;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Role\Role;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use GameCourse\Views\Component\CustomComponent;
use GameCourse\Views\Page\Page;
use Utils\Cache;
use Utils\CronJob;
use Utils\Utils;
use ZipArchive;

/**
 * This is the Course model, which implements the necessary methods
 * to interact with courses in the MySQL database.
 */
class Course
{
    const TABLE_COURSE = "course";

    const HEADERS = [   // headers for import/export functionality
        "name", "short", "color", "year", "startDate", "endDate", "landingPage", "isActive", "isVisible",
        "roleHierarchy", "theme"
    ];

    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getShort(): ?string
    {
        return $this->getData("short");
    }

    public function getColor(): ?string
    {
        return $this->getData("color");
    }

    public function getYear(): string
    {
        return $this->getData("year");
    }

    public function getStartDate(): ?string
    {
        return $this->getData("startDate");
    }

    public function getEndDate(): ?string
    {
        return $this->getData("endDate");
    }

    public function getLandingPage(): ?Page
    {
        $pageId = $this->getData("landingPage");
        return $pageId ? Page::getPageById($pageId) : null;
    }

    public function getRolesHierarchy(): array
    {
        return $this->getData("roleHierarchy");
    }

    public function getTheme(): ?string
    {
        return $this->getData("theme");
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    public function isVisible(): bool
    {
        return $this->getData("isVisible");
    }

    /**
     * Gets course data from the database.
     *
     * @example getData() --> gets all course data
     * @example getData("name") --> gets course name
     * @example getData("name, short") --> gets course name & short
     *
     * @param string $field
     * @return array|bool|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_COURSE;
        $where = ["id" => $this->id];
        $res = Core::database()->select($table, $where, $field);
        return is_array($res) ? self::parse($res) : self::parse(null, $res, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function setShort(?string $short)
    {
        $this->setData(["short" => $short]);
    }

    /**
     * @throws Exception
     */
    public function setColor(?string $color)
    {
        $this->setData(["color" => $color]);
    }

    /**
     * @throws Exception
     */
    public function setYear(?string $year)
    {
        $this->setData(["year" => $year]);
    }

    /**
     * @throws Exception
     */
    public function setStartDate(?string $start)
    {
        $this->setData(["startDate" => $start]);
    }

    /**
     * @throws Exception
     */
    public function setEndDate(?string $end)
    {
        $this->setData(["endDate" => $end]);
    }

    /**
     * @throws Exception
     */
    public function setLandingPage(?int $pageId)
    {
        $this->setData(["landingPage" => $pageId]);
    }

    /**
     * @throws Exception
     */
    public function setRolesHierarchy(array $hierarchy)
    {
        $this->setData(["roleHierarchy" => json_encode($hierarchy)]);
    }

    /**
     * @throws Exception
     */
    public function setTheme(?string $theme)
    {
        $this->setData(["theme" => $theme]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * @throws Exception
     */
    public function setVisible(bool $isVisible)
    {
        $this->setData(["isVisible" => +$isVisible]);
    }

    /**
     * Sets course data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "short" => "New short"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) {
            $newName = $fieldValues["name"];
            self::validateName($newName);
            $oldName = $this->getName();
        }
        if (key_exists("short", $fieldValues)) self::validateShort($fieldValues["short"]);
        if (key_exists("color", $fieldValues)) self::validateColor($fieldValues["color"]);
        if (key_exists("year", $fieldValues)) self::validateYear($fieldValues["year"]);
        if (key_exists("startDate", $fieldValues)) {
            self::validateDateTime($fieldValues["startDate"]);
            $endDate = key_exists("endDate", $fieldValues) ? $fieldValues["endDate"] : $this->getEndDate();
            if ($endDate) self::validateStartAndEndDates($fieldValues["startDate"], $endDate);
        }
        if (key_exists("endDate", $fieldValues)) {
            self::validateDateTime($fieldValues["endDate"]);
            $startDate = key_exists("startDate", $fieldValues) ? $fieldValues["startDate"] : $this->getStartDate();
            if ($startDate) self::validateStartAndEndDates($startDate, $fieldValues["endDate"]);
        }
        if (key_exists("landingPage", $fieldValues)) self::validateLandingPage($this->id, $fieldValues["landingPage"]);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_COURSE, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("name", $fieldValues)) {
            // Update course data folder if name has changed
            if (strcmp($oldName, $newName) !== 0)
                rename($this->getDataFolder(true, $oldName), $this->getDataFolder(true, $newName));
        }
        if (key_exists("startDate", $fieldValues)) {
            $this->setAutomation("AutoEnabling", $fieldValues["startDate"]);
        }
        if (key_exists("endDate", $fieldValues)) {
            $this->setAutomation("AutoDisabling", $fieldValues["endDate"]);
        }
        if (key_exists("isActive", $fieldValues)) {
            AutoGame::setAutoGame($this->id, $fieldValues["isActive"]);
            Event::trigger($fieldValues["isActive"] ? EventType::COURSE_ENABLED : EventType::COURSE_DISABLED, $this->id);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a course by its ID.
     * Returns null if course doesn't exist.
     *
     * @param int $id
     * @return Course|null
     */
    public static function getCourseById(int $id): ?Course
    {
        $course = new Course($id);
        if ($course->exists()) return $course;
        else return null;
    }

    /**
     * Gets course by its name and year.
     * Returns null if course doesn't exist.
     *
     * @param string $name
     * @param string $year
     * @return Course|null
     */
    public static function getCourseByNameAndYear(string $name, string $year): ?Course
    {
        $courseId = intval(Core::database()->select(self::TABLE_COURSE, ["name" => $name, "year" => $year], "id"));
        if (!$courseId) return null;
        else return new Course($courseId);
    }

    /**
     * Gets courses in the system.
     * Option for 'active' and/or 'visible'.
     *
     * @param bool|null $active
     * @param bool|null $visible
     * @return array
     */
    public static function getCourses(?bool $active = null, ?bool $visible = null): array
    {
        $table = self::TABLE_COURSE;
        $where = [];
        if ($active !== null) $where["isActive"] = $active;
        if ($visible !== null) $where["isVisible"] = $visible;
        $courses = Core::database()->selectMultiple($table, $where, "*", "id");
        foreach ($courses as &$course) { $course = self::parse($course); }
        return $courses;
    }

    /**
     * Gets user courses.
     * Option for 'active' and/or 'visible'.
     *
     * @param int $userId
     * @param bool|null $active
     * @param bool|null $visible
     * @return array
     */
    public static function getCoursesOfUser(int $userId, ?bool $active = null, ?bool $visible = null): array
    {
        $table = CourseUser::TABLE_COURSE_USER . " cu JOIN " . self::TABLE_COURSE . " c on cu.course=c.id";
        $where = ["cu.id" => $userId];
        if ($active !== null) $where["c.isActive"] = $active;
        if ($visible !== null) $where["c.isVisible"] = $visible;
        $courses = Core::database()->selectMultiple($table, $where, "c.*", "c.id");
        foreach ($courses as &$course) { $course = self::parse($course); }
        return $courses;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Course Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a course to the database.
     * Returns the newly created course.
     *
     * @param string $name
     * @param string|null $short
     * @param string|null $year
     * @param string|null $color
     * @param string|null $startDate
     * @param string|null $endDate
     * @param bool $isActive
     * @param bool $isVisible
     * @return Course
     * @throws Exception
     */
    public static function addCourse(string $name, ?string $short, ?string $year, ?string $color, ?string $startDate,
                                     ?string $endDate, bool $isActive, bool $isVisible): Course
    {
        // Check if user logged in is an admin
        $loggedUser = Core::getLoggedUser();
        if (!$loggedUser) throw new Exception("No user currently logged in. Can't create new course.");
        if (!$loggedUser->isAdmin()) throw new Exception("Only admins can create new courses.");

        // Insert in database & create data folder
        self::trim($name, $short, $color, $year, $startDate, $endDate);
        self::validateCourse($name, $short, $color, $year, $startDate, $endDate, $isActive, $isVisible);
        $id = Core::database()->insert(self::TABLE_COURSE, [
            "name" => $name,
            "short" => $short,
            "color" => $color,
            "year" => $year,
            "startDate"=> $startDate,
            "endDate"=> $endDate,
            "isActive" => +$isActive,
            "isVisible" => +$isVisible
        ]);
        Event::trigger($isActive ? EventType::COURSE_ENABLED : EventType::COURSE_DISABLED, $id);

        // Set automations
        $course = new Course($id);
        $course->setAutomation("AutoEnabling", $startDate);
        $course->setAutomation("AutoDisabling", $endDate);

        // Add default roles
        Role::addDefaultRolesToCourse($id);
        $teacherRoleId = Role::getRoleId("Teacher", $id);

        // Add current user as a teacher of the course
        $course->addUserToCourse($loggedUser->getId(), "Teacher", $teacherRoleId);

        // Init modules
        $modules = Module::getModules();
        foreach ($modules as $module) {
            Core::database()->insert(Module::TABLE_COURSE_MODULE, [
                "module" => $module["id"],
                "course" => $id,
                "minModuleVersion" => $module["version"],
                "maxModuleVersion" => null
            ]);
        }

        // Prepare AutoGame
        AutoGame::initAutoGame($id);
        if ($isActive) AutoGame::setAutoGame($id, true);

        return $course;
    }

    /**
     * Adds a course to the database by copying from another
     * existing course.
     *
     * @param int $copyFrom
     * @return Course
     * @throws Exception
     */
    public static function copyCourse(int $copyFrom): Course
    {
        $courseToCopy = Course::getCourseById($copyFrom);
        if (!$courseToCopy) throw new Exception("Course to copy from with ID = " . $copyFrom . " doesn't exist.");
        $courseInfo = $courseToCopy->getData();

        // Create a copy
        $name = $courseInfo["name"] . " (Copy)";
        $course = self::addCourse($name, $courseInfo["short"], $courseInfo["year"], $courseInfo["color"],
            null, null, false, false);
        $course->setTheme($courseInfo["theme"]);

        // Copy roles
        $course->setRolesHierarchy($courseToCopy->getRolesHierarchy());
        $course->setRoles($courseToCopy->getRoles());

        // Copy modules info
        // NOTE: module dependencies are copied before the module
        $modulesEnabled = $courseToCopy->getModules(true, true);
        $modulesCopied = [];
        while (count($modulesCopied) != count($modulesEnabled)) {
            foreach ($modulesEnabled as $moduleId) {
                if (in_array($moduleId, $modulesCopied)) // already copied
                    continue;

                // Check if dependencies have already been copied
                $allDependenciesCopied = true;
                foreach (($course->getModuleById($moduleId))->getDependencies(DependencyMode::HARD, true) as $dependencyId) {
                    $dependency = $course->getModuleById($dependencyId);
                    if (!$dependency->isEnabled()) {
                        $allDependenciesCopied = false;
                        break;
                    }
                }

                // Copy module
                if ($allDependenciesCopied) {
                    // Enable module
                    $copiedModule = $course->getModuleById($moduleId);
                    $copiedModule->setEnabled(true);

                    // Copy module
                    $module = $courseToCopy->getModuleById($moduleId);
                    $module->copyTo($course);
                    $modulesCopied[] = $moduleId;
                }
            }
        }

        // TODO: copy views
        // TODO: copy default landing page, roles landingPages

        // Copy AutoGame info
        AutoGame::copyAutoGameInfo($course->getId(), $courseToCopy->getId());

        // Copy Rule System
        RuleSystem::copyRuleSystem($courseToCopy, $course);

        // Copy styles
        $styles = $courseToCopy->getStyles();
        if ($styles) $course->updateStyles($styles["contents"]);

        return $course;
    }

    /**
     * Edits an existing course in database.
     * Returns the edited course.
     *
     * @param string $name
     * @param string|null $short
     * @param string|null $year
     * @param string|null $color
     * @param string|null $startDate
     * @param string|null $endDate
     * @param bool $isActive
     * @param bool $isVisible
     * @return Course
     * @throws Exception
     */
    public function editCourse(string $name, ?string $short, ?string $year, ?string $color, ?string $startDate,
                               ?string $endDate, bool $isActive, bool $isVisible): Course
    {
        $this->setData([
            "name" => $name,
            "short" => $short,
            "year" => $year,
            "color" => $color,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "isActive" => +$isActive,
            "isVisible" => +$isVisible
        ]);
        return $this;
    }

    /**
     * Deletes a course from the database and removes all its data.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function deleteCourse(int $courseId)
    {
        // Remove data folder
        Course::removeDataFolder($courseId);

        // Disable autogame & remove info
        AutoGame::setAutoGame($courseId, false);
        AutoGame::deleteAutoGameInfo($courseId);

        // Remove automations
        $course = new Course($courseId);
        $course->setAutomation("AutoEnabling", null);
        $course->setAutomation("AutoDisabling", null);

        // Delete cache
        Cache::clean($courseId);

        // Delete from database
        Core::database()->delete(self::TABLE_COURSE, ["id" => $courseId]);
    }

    /**
     * Checks whether course exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }

    /*** ---------------------------------------------------- ***/
    /*** ------------------- Course Rules ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets course rules.
     * Option for 'active'.
     *
     * @param bool|null $active
     * @return array
     */
    public function getCourseRules(?bool $active = null): array
    {
        $table = Rule::TABLE_RULE . " r JOIN " . Course::TABLE_COURSE . " c on r.course=c.name "; // not sure
        $where = ["r.course" => $this->id];
        if ($active !== null) $where["r.isActive"] = $active;
        $courseRules =  Core::database()->selectMultiple($table, $where,"r.*","r.id");
        foreach ($courseRules as &$courseRule) {
            $courseRule = CourseRule::parse($courseRule);
        }
        return $courseRules;
    }

    /**
     * Gets a course user by its ID.
     * Returns null if course user doesn't exist.
     *
     * @param int $ruleId
     * @return CourseRule|null
     */
    public function getCourseRuleById(int $ruleId): ?CourseRule
    {
        return CourseRule::getCourseRuleById($ruleId, $this);
    }

    /*** ---------------------------------------------------- ***/
    /*** ------------------- Course Users ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a course user by its ID.
     * Returns null if course user doesn't exist.
     *
     * @param int $userId
     * @return CourseUser|null
     */
    public function getCourseUserById(int $userId): ?CourseUser
    {
        return CourseUser::getCourseUserById($userId, $this);
    }

    /**
     * Gets a course user by its username.
     * Returns null if course user doesn't exist.
     *
     * @param string $username
     * @param string|null $authService
     * @return CourseUser|null
     * @throws Exception
     */
    public function getCourseUserByUsername(string $username, string $authService = null): ?CourseUser
    {
        return CourseUser::getCourseUserByUsername($username, $this, $authService);
    }

    /**
     * Gets a course user by its e-mail.
     * Returns null if course user doesn't exist.
     *
     * @param string $email
     * @return CourseUser|null
     */
    public function getCourseUserByEmail(string $email): ?CourseUser
    {
        return CourseUser::getCourseUserByEmail($email, $this);
    }

    /**
     * Gets a course user by its student number.
     * Returns null if course user doesn't exist.
     *
     * @param int $studentNumber
     * @return CourseUser|null
     */
    public function getCourseUserByStudentNumber(int $studentNumber): ?CourseUser
    {
        return CourseUser::getCourseUserByStudentNumber($studentNumber, $this);
    }

    /**
     * Gets course users.
     * Option for 'active'.
     *
     * @param bool|null $active
     * @return array
     */
    public function getCourseUsers(?bool $active = null): array
    {
        $table = User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.user=u.id JOIN " . CourseUser::TABLE_COURSE_USER . " cu on cu.id=u.id";
        $where = ["cu.course" => $this->id];
        if ($active !== null) $where["cu.isActive"] = $active;
        $courseUsers =  Core::database()->selectMultiple($table,
            $where,
            "u.*, a.username, a.auth_service, a.lastLogin, cu.lastActivity, cu.isActive as isActiveInCourse",
            "u.id"
        );
        foreach ($courseUsers as &$courseUser) { $courseUser = CourseUser::parse($courseUser); }
        return $courseUsers;
    }

    /**
     * Gets course users with a given role name and/or role ID.
     * Option for 'active'.
     *
     * @param bool|null $active
     * @param string|null $roleName
     * @param int|null $roleId
     * @return array
     * @throws Exception
     */
    public function getCourseUsersWithRole(?bool $active = null, string $roleName = null, int $roleId = null): array
    {
        if ($roleName === null && $roleId === null)
            throw new Exception("Need either role name or ID to get course users with a specific role.");

        $where = ["cu.course" => $this->id, "r.course" => $this->id];
        if ($active !== null) $where["cu.isActive"] = $active;
        if ($roleName !== null) $where["r.name"] = $roleName;
        if ($roleId !== null) $where["r.id"] = $roleId;

        $courseUsers = Core::database()->selectMultiple(
            User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.user=u.id JOIN " .
            CourseUser::TABLE_COURSE_USER . " cu on cu.id=u.id JOIN " . Role::TABLE_USER_ROLE . " ur on ur.user=u.id JOIN " .
            Role::TABLE_ROLE . " r on r.id=ur.role and r.course=cu.course",
            $where,
            "u.*, a.username, a.auth_service, a.lastLogin, cu.lastActivity, cu.isActive as isActiveInCourse",
            "u.id"
        );
        foreach ($courseUsers as &$courseUser) { $courseUser = CourseUser::parse($courseUser); }
        return $courseUsers;
    }

    /**
     * Gets students of course.
     * Option for 'active'.
     *
     * @param bool|null $active
     * @return array
     * @throws Exception
     */
    public function getStudents(?bool $active = null): array
    {
        return $this->getCourseUsersWithRole($active, "Student");
    }

    /**
     * Gets teachers of course.
     * Option for 'active'.
     *
     * @param bool|null $active
     * @return array
     * @throws Exception
     */
    public function getTeachers(?bool $active = null): array
    {
        return $this->getCourseUsersWithRole($active, "Teacher");
    }

    /**
     * Gets users not enrolled in course.
     * Option for 'active'.
     *
     * @param bool|null $active
     * @return array
     */
    public function getUsersNotInCourse(?bool $active = null): array
    {
        $table = User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.user=u.id LEFT JOIN " . CourseUser::TABLE_COURSE_USER . " cu on cu.id=u.id AND cu.course=" . $this->id;
        $where = ["cu.id" => null];
        if ($active !== null) $where["u.isActive"] = $active;
        $users =  Core::database()->selectMultiple($table,
            $where,
            "u.*, a.username, a.auth_service, a.lastLogin",
            "u.id"
        );
        foreach ($users as &$user) { $user = User::parse($user); }
        return $users;
    }

    /**
     * Adds a given user to a course.
     * Option to pass a role name or ID to be added as well.
     * Returns the newly created course user.
     *
     * @param int $userId
     * @param string|null $roleName
     * @param int|null $roleId
     * @param bool $isActive
     * @return CourseUser
     * @throws Exception
     */
    public function addUserToCourse(int $userId, string $roleName = null, int $roleId = null, bool $isActive = true): CourseUser
    {
        return CourseUser::addCourseUser($userId, $this->id, $roleName, $roleId, $isActive);
    }

    /**
     * Removes a given user from the course.
     *
     * @param int $userId
     * @return void
     */
    public function removeUserFromCourse(int $userId)
    {
        CourseUser::deleteCourseUser($userId, $this->id);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Roles ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets course's roles. Option to retrieve only roles' names and/or to
     * sort them hierarchly. Sorting works like this:
     *  - if only names --> with the more specific roles first, followed
     *                      by the less specific ones
     *  - else --> retrieve roles' hierarchy
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getRoles() --> ["Watcher", "Student", "StudentB", "StudentA", "Teacher"] (no fixed order)
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getRoles(false) --> [
     *                                  ["name" => "Watcher", "id" => 3, "landingPage" => null],
     *                                  ["name" => "Student", "id" => 2, "landingPage" => null],
     *                                      ["name" => "StudentB", "id" => 5, "landingPage" => null],
     *                                          ["name" => "StudentA", "id" => 4, "landingPage" => null],
     *                                  ["name" => "Teacher", "id" => 1, "landingPage" => null]
     *                              ] (no fixed order)
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getRoles(true, true) --> ["StudentA", "StudentB", "Teacher", "Student", "Watcher"]
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getRoles(false, true) --> [
     *                                      ["name" => "Teacher", "id" => 1, "landingPage" => null],
     *                                          ["name" => "Student", "id" => 2, "landingPage" => null, "children" => [
     *                                              ["name" => "StudentA", "id" => 4, "landingPage" => null],
     *                                              ["name" => "StudentB", "id" => 5, "landingPage" => null]
     *                                          ]],
     *                                      ["name" => "Watcher", "id" => 3, "landingPage" => null]
     *                                    ]
     *
     * @param bool $onlyNames
     * @param bool $sortByHierarchy
     * @return array
     */
    public function getRoles(bool $onlyNames = true, bool $sortByHierarchy = false): array
    {
        return Role::getCourseRoles($this->id, $onlyNames, $sortByHierarchy);
    }

    /**
     * Replaces course's roles in the database.
     *
     * @param array|null $rolesNames
     * @param array|null $hierarchy
     * @return void
     * @throws Exception
     */
    public function setRoles(array $rolesNames = null, array $hierarchy = null)
    {
        Role::setCourseRoles($this->id, $rolesNames, $hierarchy);
    }

    /**
     * Adds a new role to course if it isn't already added.
     * Option to pass either landing page name or ID.
     *
     * @param string|null $roleName
     * @param string|null $landingPageName
     * @param int|null $landingPageId
     * @return void
     * @throws Exception
     */
    public function addRole(string $roleName, string $landingPageName = null, int $landingPageId = null, string $moduleId = null)
    {
        Role::addRoleToCourse($this->id, $roleName, $landingPageName, $landingPageId, $moduleId);
    }

    /**
     * Updates course's roles in the database, without fully replacing them.
     *
     * @param array $roles
     * @return void
     * @throws Exception
     */
    public function updateRoles(array $roles)
    {
        Role::updateCourseRoles($this->id, $roles);
    }

    /**
     * Removes a given role from course.
     * Option to pass either role name or role ID.
     *
     * @param string|null $roleName
     * @param int|null $roleId
     * @param string|null $moduleId
     * @return void
     * @throws Exception
     */
    public function removeRole(string $roleName = null, int $roleId = null, string $moduleId = null)
    {
        Role::removeRoleFromCourse($this->id, $roleName, $roleId, $moduleId);
    }

    /**
     * Checks whether course has a given role.
     * Option to pass either role name or role ID.
     *
     * @param string|null $roleName
     * @param int|null $roleId
     * @return bool
     * @throws Exception
     */
    public function hasRole(string $roleName = null, int $roleId = null): bool
    {
        return Role::courseHasRole($this->id, $roleName, $roleId);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Modules --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a module available in a course by its ID.
     *
     * @param string $moduleId
     * @return Module|null
     */
    public function getModuleById(string $moduleId): ?Module
    {
        return Module::getModuleById($moduleId, $this);
    }

    /**
     * Gets all modules available in a course.
     * Option for 'enabled' and to retrieve only IDs.
     *
     * @param bool|null $enabled
     * @param bool $IDsOnly
     * @return array
     * @throws Exception
     */
    public function getModules(?bool $enabled = null, bool $IDsOnly = false): array
    {
        return Module::getModulesInCourse($this->id, $enabled, $IDsOnly);
    }

    /**
     * Enables/disables a module in a course.
     *
     * @param string $moduleId
     * @param bool $isEnabled
     * @return void
     * @throws Exception
     */
    public function setModuleState(string $moduleId, bool $isEnabled)
    {
        $module = $this->getModuleById($moduleId);
        $module->setEnabled($isEnabled);
    }

    /**
     * Checks whether a given module is enabled in a course.
     *
     * @param string $moduleId
     * @return bool
     * @throws Exception
     */
    public function isModuleEnabled(string $moduleId): bool
    {
        $module = $this->getModuleById($moduleId);
        return $module->isEnabled();
    }

    /**
     * Gets min. and max. compatible versions of a module for
     * a course.
     *
     * @param string $moduleId
     * @return array
     */
    public function getCompatibleModuleVersions(string $moduleId): array
    {
        $compatibility = Core::database()->select(Module::TABLE_COURSE_MODULE,
            ["course" => $this->id, "module" => $moduleId], "minModuleVersion, maxModuleVersion");
        return [
            "min" => $compatibility["minModuleVersion"],
            "max" => $compatibility["maxModuleVersion"]
        ];
    }

    /**
     * Gets modules resources available in a course.
     *
     * @param bool|null $enabled
     * @return array
     * @throws Exception
     */
    public function getModulesResources(bool $enabled = null): array
    {
        $resources = [];

        $moduleIds = $this->getModules($enabled, true);
        foreach ($moduleIds as $moduleId) {
            $module = $this->getModuleById($moduleId);
            $resources[$moduleId] = $module->getResources();
        }

        return $resources;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Views ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets course pages.
     * Option for 'visible'.
     *
     * @param bool|null $visible
     * @return array
     */
    public function getPages(?bool $visible = null): array
    {
        return Page::getPagesOfCourse($this->id, $visible);
    }

    /**
     * Gets course components.
     *
     * @return array
     * @throws Exception
     */
    public function getComponents(): array
    {
        return CustomComponent::getComponents($this->id);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Course Data -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a readable copy of all course's records of users, like
     * awards given to students, their grade, etc., so teachers can
     * keep hard copies for themselves.
     */
    // TODO: getDataRecords

    /**
     * Gets course data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param bool $fullPath
     * @param string|null $courseName
     * @return string
     */
    public function getDataFolder(bool $fullPath = true, string $courseName = null): string
    {
        if (!$courseName) $courseName = $this->getName();
        $courseFolderName = $this->id . '-' . Utils::strip($courseName, "_");

        if ($fullPath) return COURSE_DATA_FOLDER . "/" . $courseFolderName;
        else return Utils::getDirectoryName(COURSE_DATA_FOLDER) . "/" . $courseFolderName;
    }

    /**
     * Gets course data folder contents.
     *
     * @return array
     * @throws Exception
     */
    public function getDataFolderContents(): array
    {
        return Utils::getDirectoryContents($this->getDataFolder());
    }

    /**
     * Creates a data folder for a given course. If folder exists, it
     * will delete its contents.
     *
     * @param int $courseId
     * @param string|null $courseName
     * @return string
     * @throws Exception
     */
    public static function createDataFolder(int $courseId, string $courseName = null): string
    {
        $dataFolder = (new Course($courseId))->getDataFolder(true, $courseName);
        if (file_exists($dataFolder)) self::removeDataFolder($courseId, $courseName);
        mkdir($dataFolder, 0777, true);
        return $dataFolder;
    }

    /**
     * Deletes a given course's data folder.
     *
     * @param int $courseId
     * @param string|null $courseName
     * @return void
     * @throws Exception
     */
    public static function removeDataFolder(int $courseId, string $courseName = null)
    {
        $dataFolder = (new Course($courseId))->getDataFolder(true, $courseName);
        if (file_exists($dataFolder)) Utils::deleteDirectory($dataFolder);
    }

    /**
     * Transforms a given URL path inside a course's folder:
     *  - from absolute to relative
     *  - from relative to absolute
     *
     * Useful so that only relative paths are saved on database,
     * but absolute paths can be retrieved when needed.
     *
     * @example absolute -> relative
     *  URL: <API_URL>/<COURSE_DATA_FOLDER>/<courseFolder>/<somePath>
     *  NEW URL: <somePath>
     *
     * @example relative -> absolute
     *  URL: <somePath>
     *  NEW URL: <API_URL>/<COURSE_DATA_FOLDER>/<courseFolder>/<somePath>
     *
     * @param string $url
     * @param string $to (absolute | relative)
     * @param int $courseId
     * @return string
     */
    public static function transformURL(string $url, string $to, int $courseId): string
    {
        $dataFolder = (new Course($courseId))->getDataFolder(false);
        $dataFolderPath = API_URL . "/" . $dataFolder . "/";

        if (strpos($url, "?")) $url = substr($url, 0, strpos($url, "?"));
        if ($to === "absolute" && strpos($url, 'http') !== 0) return $dataFolderPath . $url;
        elseif ($to === "relative" && strpos($url, API_URL) === 0) return str_replace($dataFolderPath, "", $url);
        return $url;
    }

    /**
     * Uploads a given file to a given directory inside course data.
     *
     * @example uploadFile("dir1", <file>, "file.txt") --> uploads file to directory 'dir1' inside course data with name 'file.txt'
     *
     * @param string $to
     * @param string $base64
     * @param string $filename
     * @return string
     * @throws Exception
     */
    public function uploadFile(string $to, string $base64, string $filename): string
    {
        $path = Utils::uploadFile($this->getDataFolder() . "/$to", $base64, $filename);
        return substr($path, strpos($path, Utils::getDirectoryName(COURSE_DATA_FOLDER)));
    }

    /**
     * Deletes a given file from a given directory inside course data.
     * Option to delete given directory if it becomes empty.
     *
     * @param string $from
     * @param string $filename
     * @param bool $deleteIfEmpty
     * @return void
     * @throws Exception
     */
    public function deleteFile(string $from, string $filename, bool $deleteIfEmpty = true)
    {
        Utils::deleteFile($from, $filename, $deleteIfEmpty);
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Styling ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets global styles available in a course.
     * Returns null if no global styles set.
     *
     * @return array|null
     */
    public function getStyles(): ?array
    {
        $path = $this->getDataFolder(false) . "/styles/main.css";
        if (file_exists(ROOT_PATH . $path)) return ["path" => API_URL . "/" . $path, "contents" => file_get_contents(ROOT_PATH . $path)];
        return null;
    }

    /**
     * Updates global styles for a given course.
     *
     * @param string $contents
     * @return void
     * @throws Exception
     */
    public function updateStyles(string $contents)
    {
        $stylesFolder = $this->getDataFolder() . "/styles";

        if (empty($contents)) { // remove styles
            if (file_exists($stylesFolder . "/main.css"))
                Utils::deleteFile($stylesFolder, "main.css");

        } else { // update styles
            if (!file_exists($stylesFolder))
                mkdir($stylesFolder, 0777, true);

            $mainStyles = $stylesFolder . "/main.css";
            file_put_contents($mainStyles, $contents);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Automation -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Sets automation for some course processes.
     *
     * @param string $script
     * @param ...$data
     * @return void
     * @throws Exception
     */
    private function setAutomation(string $script, ...$data)
    {
        switch ($script) {
            case "AutoEnabling":
                $this->setAutoEnabling($data[0]);
                break;

            case "AutoDisabling":
                $this->setAutoDisabling($data[0]);
                break;

            default:
                throw new Exception("Automation script '" . $script . "' not found for course.");
        }
    }

    /**
     * Enables auto enabling for a given course on a specific date.
     * If date is null, it will disable it.
     *
     * @param string|null $startDate
     * @return void
     */
    private function setAutoEnabling(?string $startDate)
    {
        if ($startDate) new CronJob("AutoCourseEnabling", $this->id, null, null, null, $startDate);
        else CronJob::removeCronJob("AutoCourseEnabling", $this->id);
    }

    /**
     * Enables auto disabling for a given course on a specific date.
     * If date is null, it will disable it.
     *
     * @param string|null $endDate
     * @return void
     */
    private function setAutoDisabling(?string $endDate)
    {
        if ($endDate) new CronJob("AutoCourseDisabling", $this->id, null, null, null, $endDate);
        else CronJob::removeCronJob("AutoCourseDisabling", $this->id);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports courses into the system from a .zip file containing
     * a .csv file with all courses information and individual folders
     * for each course with additional data such as course data and
     * autogame configurations.
     *
     * Returns the nr. of courses imported.
     *
     * NOTE: Can't have two courses with same pair name/year as they
     *       will be overwritten
     *
     * @param string $contents
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importCourses(string $contents, bool $replace = true): int
    {
        // FIXME: import autogame imported functions and config

        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Extract contents
        $zipPath = $tempFolder . "/courses.zip";
        file_put_contents($zipPath, $contents);
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Exception("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        Utils::deleteFile($tempFolder, "courses.zip");

        // Import courses
        $file = file_get_contents($tempFolder . "/courses.csv");
        $nrCoursesImported = Utils::importFromCSV(self::HEADERS, function ($course, $indexes) use ($tempFolder, $replace) {
            $nrCoursesImported = 0;

            $name = $course[$indexes["name"]];
            $short = $course[$indexes["short"]];
            $color = $course[$indexes["color"]];
            $year = $course[$indexes["year"]];
            $startDate = $course[$indexes["startDate"]];
            $endDate = $course[$indexes["endDate"]];
            $landingPage = $course[$indexes["landingPage"]];
            $isActive = $course[$indexes["isActive"]];
            $isVisible = $course[$indexes["isVisible"]];
            $roleHierarchy = $course[$indexes["roleHierarchy"]];
            $theme = $course[$indexes["theme"]];

            $mode = null;
            $course = self::getCourseByNameAndYear($name, $year);
            if ($course) {  // course already exists
                if ($replace) { // replace
                    $mode = "update";
                    $course->editCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible);
                    $course->setTheme($theme);
                }

            } else {  // course doesn't exist
                $mode = "create";
                $course = self::addCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible);
                $course->setTheme($theme);
                $nrCoursesImported++;
            }

            // Create or update course information
            if ($mode) {
                // Import course data
                $zipCourseFolder = $tempFolder . "/" . Utils::swapNonENChars($name) . " (" . $year . ")";
                $dataFolder = $course->getDataFolder(true, $name);
                if ($mode == "update") self::removeDataFolder($course->getId(), $name);
                Utils::copyDirectory($zipCourseFolder . "/", $dataFolder . "/", ["rules/data", "autogame"]);

                // Import roles
                $course->setRolesHierarchy(json_decode($roleHierarchy, true));
                $course->setRoles(null, $roleHierarchy);

                // Import modules
                // TODO: copy modules enabled and data

                // Import views
                // TODO: default landing page, roles landingPages (copy pages and views first)

                // Import AutoGame info
                if ($mode == "update") AutoGame::deleteAutoGameInfo($course->getId());
                Utils::copyDirectory($zipCourseFolder . "/autogame/imported-functions/", ROOT_PATH . "autogame/imported-functions/" . $course->getId() . "/");
                file_put_contents(AUTOGAME_FOLDER . "/config/config_" . $course->getId() . ".txt", file_get_contents($zipCourseFolder . "/autogame/config.txt"));
            }
            return $nrCoursesImported;
        }, $file);

        // Remove temporary folder
        Utils::deleteDirectory($tempFolder);
        if (Utils::getDirectorySize(ROOT_PATH . "temp") == 0)
            Utils::deleteDirectory(ROOT_PATH . "temp");

        return $nrCoursesImported;
    }

    /**
     * Exports courses information and individual folders for each
     * course, with additional data such as course data and autogame
     * configuration, to a .zip file.
     *
     * @return string
     * @throws Exception
     */
    public static function exportCourses(): string
    {
        // FIXME: export autogame imported functions and config
        $courses = self::getCourses();

        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store courses' info
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/courses.zip";
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE))
            throw new Exception("Failed to create zip archive.");

        // Add .csv file
        $zip->addFromString("courses.csv", Utils::exportToCSV($courses, function ($course) {
            return [$course["name"], $course["short"], $course["color"], $course["year"], $course["startDate"],
                $course["endDate"], $course["landingPage"], +$course["isActive"], +$course["isVisible"], $course["roleHierarchy"],
                $course["theme"]];
        }, self::HEADERS));

        // Add each course
        foreach ($courses as $course) {
            // Add course folder
            $courseFolder = Utils::swapNonENChars($course["name"]) . " (" . $course["year"] . ")";
            $zip->addEmptyDir($courseFolder);
            $dataFolder = (new Course($course["id"]))->getDataFolder(true, $course["name"]) . "/";
            $dir = opendir($dataFolder);
            while ($f = readdir($dir)) {
                if (is_file($dataFolder . $f))
                    $zip->addFile($dataFolder . $f, $courseFolder . "/" . $f);
            }

            // Import modules
            // TODO: add modules enabled and data

            // Import views
            // TODO: default landing page, roles landingPages (copy pages and views first)

            // Add AutoGame configurations
            $courseFunctions = AUTOGAME_FOLDER . "/imported-functions/" . $course["id"] . "/";
            $dir = opendir($courseFunctions);
            while ($f = readdir($dir)) {
                if (is_file($courseFunctions . $f))
                    $zip->addFile($courseFunctions . $f, $courseFolder . "/autogame/imported-functions");
            }
            $zip->addFile(AUTOGAME_FOLDER . "/config/config_" . $course["id"] . ".txt", $courseFolder . "/autogame/config.txt");
        }

        $zip->close();
        return $zipPath;
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates course parameters.
     *
     * @param $name
     * @param $short
     * @param $color
     * @param $year
     * @param $startDate
     * @param $endDate
     * @param $isActive
     * @param $isVisible
     * @return void
     * @throws Exception
     */
    private static function validateCourse($name, $short, $color, $year, $startDate, $endDate, $isActive, $isVisible)
    {
        self::validateName($name);
        self::validateShort($short);
        self::validateColor($color);
        self::validateYear($year);
        self::validateDateTime($startDate);
        self::validateDateTime($endDate);
        self::validateStartAndEndDates($startDate, $endDate);

        if (!is_bool($isActive)) throw new Exception("'isActive' must be either true or false.");
        if (!is_bool($isVisible)) throw new Exception("'isVisible' must be either true or false.");
    }

    /**
     * Validates course name.
     *
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Course name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Course name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 100)
            throw new Exception("Course name is too long: maximum of 100 characters.");
    }

    /**
     * Validates course short.
     *
     * @throws Exception
     */
    private static function validateShort($short)
    {
        if (is_null($short)) return;

        if (empty($short))
            throw new Exception("Course short can't be empty.");

        if (is_numeric($short))
            throw new Exception("Course short can't be composed of only numbers.");

        if (iconv_strlen($short) > 20)
            throw new Exception("Course short is too long: maximum of 20 characters.");
    }

    /**
     * Validates course color.
     *
     * @throws Exception
     */
    private static function validateColor($color)
    {
        if (is_null($color)) return;

        if (!Utils::isValidColor($color, "HEX"))
            throw new Exception("Course color needs to be in HEX format.");
    }

    /**
     * Validates course year.
     *
     * @throws Exception
     */
    private static function validateYear($year)
    {
        preg_match("/^\d{4}-\d{4}$/", $year, $matches);
        if (!is_string($year) || empty($year) || count($matches) == 0)
            throw new Exception("Course year needs to be in 'yyyy-yyyy' format.");
    }

    /**
     * Validates course start and end dates.
     *
     * @throws Exception
     */
    private static function validateStartAndEndDates($startDateTime, $endDateTime)
    {
        if ($startDateTime && $endDateTime && strtotime($startDateTime) >= strtotime($endDateTime))
            throw new Exception("Course end date must come later than start date.");
    }

    /**
     * Validates datetime.
     *
     * @throws Exception
     */
    private static function validateDateTime($dateTime)
    {
        if (is_null($dateTime)) return;
        if (!is_string($dateTime) || !Utils::isValidDate($dateTime, "Y-m-d H:i:s"))
            throw new Exception("Datetime '" . $dateTime . "' should be in format 'yyyy-mm-dd HH:mm:ss'");
    }

    /**
     * Validates landing page.
     *
     * @throws Exception
     */
    private static function validateLandingPage(int $courseId, $pageId)
    {
        if (is_null($pageId)) return;
        $page = Page::getPageById($pageId);
        if (!$page) throw new Exception("Page with ID = " . $pageId . " doesn't exist.");
        if ($page->getCourse()->getId() != $courseId)
            throw new Exception("Page with ID = " . $pageId . " doesn't belong to course with ID = " . $courseId . ".");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a course coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $course
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $course = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "landingPage"];
        $boolValues = ["isActive", "isVisible"];
        $jsonValues = ["roleHierarchy"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues, "json" => $jsonValues], $course, $field, $fieldName);
    }

    /**
     * Trims course parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "short", "color", "year", "startDate", "endDate", "roleHierarchy", "theme"];
        Utils::trim($params, ...$values);
    }
}
