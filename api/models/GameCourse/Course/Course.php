<?php
namespace GameCourse\Course;

use Error;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Role\Role;
use GameCourse\User\Auth;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
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

    public function getYear(): ?string
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

    public function getLandingPage(): ?int
    {
        return $this->getData("landingPage");
    }

    public function getLastUpdate(): string
    {
        return $this->getData("lastUpdate");
    } // FIXME: not really being used for anything

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
     * NOTE: folder can only be retrieve with either '*' or
     *       alone as a field
     *
     * @param string $field
     * @return array|bool|int|null
     */
    public function getData(string $field = "*")
    {
        if ($field != "folder")
            $data = Core::database()->select(self::TABLE_COURSE, ["id" => $this->id], $field);
        if ($field == "*")
            $data["folder"] = $this->getDataFolder(false);
        if ($field == "folder")
            $data = $this->getDataFolder(false);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setName(string $name)
    {
        self::validateName($name);
        $this->setData(["name" => $name]);
    }

    public function setShort(?string $short)
    {
        $this->setData(["short" => $short]);
    }

    public function setColor(?string $color)
    {
        self::validateColor($color);
        $this->setData(["color" => $color]);
    }

    public function setYear(?string $year)
    {
        self::validateYear($year);
        $this->setData(["year" => $year]);
    }

    public function setStartDate(?string $start)
    {
        self::validateDateTime($start);
        $this->setData(["startDate" => $start]);
    }

    public function setEndDate(?string $end)
    {
        self::validateDateTime($end);
        $this->setData(["endDate" => $end]);
    }

    public function setLandingPage(?int $pageId)
    {
        $this->setData(["landingPage" => $pageId]);
    }

    public function setLastUpdate(string $lastUpdate)
    {
        self::validateDateTime($lastUpdate);
        $this->setData(["lastUpdate" => $lastUpdate]);
    }

    public function setRolesHierarchy(array $hierarchy)
    {
        $this->setData(["roleHierarchy" => json_encode($hierarchy)]);
    }

    public function setTheme(?string $theme)
    {
        $this->setData(["theme" => $theme]);
    }

    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
        AutoGame::setAutoGame($this->id, $isActive);
    }

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
     */
    public function setData(array $fieldValues)
    {
        if (key_exists("name", $fieldValues)) {
            self::validateName($fieldValues["name"]);

            // Update course data folder if name has changed
            $oldName = $this->getName();
            if (strcmp($oldName, $fieldValues["name"]) !== 0)
                rename($this->getDataFolder(true, $oldName), $this->getDataFolder(true, $fieldValues["name"]));
        }
        if (key_exists("color", $fieldValues)) self::validateColor($fieldValues["color"]);
        if (key_exists("year", $fieldValues)) self::validateYear($fieldValues["year"]);
        if (key_exists("startDate", $fieldValues)) self::validateDateTime($fieldValues["startDate"]);
        if (key_exists("endDate", $fieldValues)) self::validateDateTime($fieldValues["endDate"]);
        if (key_exists("lastUpdate", $fieldValues)) self::validateDateTime($fieldValues["lastUpdate"]);

        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_COURSE, $fieldValues, ["id" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function getCourseById(int $id): ?Course
    {
        $course = new Course($id);
        if ($course->exists()) return $course;
        else return null;
    }

    public static function getCourseByNameAndYear(string $name, string $year): ?Course
    {
        $courseId = intval(Core::database()->select(self::TABLE_COURSE, ["name" => $name, "year" => $year], "id"));
        if (!$courseId) return null;
        else return new Course($courseId);
    }

    public static function getCourses(?bool $active = null): array
    {
        $where = [];
        if ($active !== null) $where["isActive"] = $active;
        $courses = Core::database()->selectMultiple(self::TABLE_COURSE, $where);
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
     */
    public static function addCourse(string $name, ?string $short, ?string $year, ?string $color, ?string $startDate,
                                     ?string $endDate, bool $isActive, bool $isVisible): Course
    {
        // Check if user logged in is an admin
        $loggedUser = Core::getLoggedUser();
        if (!$loggedUser) throw new Error("No user currently logged in. Can't create new course.");
        if (!$loggedUser->isAdmin()) throw new Error("Only admins can create new courses.");

        // Insert in database & create data folder
        self::validateCourse($name, $color, $year, $startDate, $endDate, $isActive, $isVisible);
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
        $dataFolder = Course::createDataFolder($id, $name);

        // Add default roles
        $teacherRoleId = Role::addDefaultRolesToCourse($id);

        // Add current user as a teacher of the course
        $course = new Course($id);
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
        AutoGame::initAutoGame($id, $name, $dataFolder);
        if ($isActive) AutoGame::setAutoGame($id, true);

        return $course;
    }

    /**
     * Adds a course to the database by copying from another
     * existing course.
     *
     * @param int $copyFrom
     * @return void
     */
    public static function copyCourse(int $copyFrom)
    {
        $courseToCopy = Course::getCourseById($copyFrom);
        if (!$courseToCopy) throw new Error("Course to copy from with ID = " . $copyFrom . " doesn't exist.");
        $courseInfo = $courseToCopy->getData();

        // Create a copy
        $name = $courseInfo["name"] . " (Copy)";
        $course = self::addCourse($name, $courseInfo["short"], $courseInfo["year"], $courseInfo["color"],
            null, null, $courseInfo["isActive"], $courseInfo["isVisible"]);
        $course->setTheme($courseInfo["theme"]);

        // Copy course data
        Utils::copyDirectory(ROOT_PATH . $courseInfo["folder"] . "/",  $course->getDataFolder(true, $name) . "/", ["rules/data"]);

        // Copy roles
        $course->setRolesHierarchy($courseToCopy->getRolesHierarchy());
        $course->setRoles($courseToCopy->getRoles());

        // TODO: copy modules enabled and data

        // TODO: default landing page, roles landingPages (copy pages and views first)

        // Copy AutoGame info
        AutoGame::copyAutoGameInfo($course->getId(), $courseToCopy->getId());
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
     */
    public function editCourse(string $name, ?string $short, ?string $year, ?string $color, ?string $startDate,
                               ?string $endDate, bool $isActive, bool $isVisible): Course
    {
        self::validateCourse($name, $color, $year, $startDate, $endDate, $isActive, $isVisible);
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
     */
    public static function deleteCourse(int $courseId)
    {
        // Remove data folder
        Course::removeDataFolder($courseId);

        // Disable autogame & remove info
        AutoGame::setAutoGame($courseId, false);
        AutoGame::deleteAutoGameInfo($courseId);

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
    /*** ------------------- Course Users ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getCourseUserById(int $userId): ?CourseUser
    {
        $courseUser = new CourseUser($userId, $this);
        if ($courseUser->exists()) return $courseUser;
        else return null;
    }

    public function getCourseUserByUsername(string $username): ?CourseUser
    {
        $userId = intval(Core::database()->select(Auth::TABLE_AUTH, ["username" => $username], "game_course_user_id"));
        if (!$userId) return null;
        else return new CourseUser($userId, $this);
    }

    public function getCourseUserByEmail(string $email): ?CourseUser
    {
        $userId = intval(Core::database()->select(User::TABLE_USER, ["email" => $email], "id"));
        if (!$userId) return null;
        else return new CourseUser($userId, $this);
    }

    public function getCourseUserByStudentNumber(int $studentNumber): ?CourseUser
    {
        $userId = intval(Core::database()->select(User::TABLE_USER, ["studentNumber" => $studentNumber], "id"));
        if (!$userId) return null;
        else return new CourseUser($userId, $this);
    }

    public function getCourseUsers(?bool $active = null): array
    {
        $where = ["cu.course" => $this->id];
        if ($active !== null) $where["isActiveInCourse"] = $active;
        $courseUsers =  Core::database()->selectMultiple(
            User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.game_course_user_id=u.id JOIN " . CourseUser::TABLE_COURSE_USER . " cu on cu.id=u.id",
            $where,
            "u.*, a.username, a.authentication_service, cu.lastActivity, cu.previousActivity, cu.isActive as isActiveInCourse"
        );
        foreach ($courseUsers as &$courseUser) { $courseUser = CourseUser::parse($courseUser); }
        return $courseUsers;
    }

    public function getCourseUsersWithRole(?bool $active = null, string $roleName = null, int $roleId = null): array
    {
        $where = ["cu.course" => $this->id, "r.course" => $this->id];
        if ($active !== null) $where["isActiveInCourse"] = $active;
        if ($roleName !== null) $where["r.name"] = $roleName;
        if ($roleId !== null) $where["r.id"] = $roleId;

        $courseUsers = Core::database()->selectMultiple(
            User::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on a.game_course_user_id=u.id JOIN " .
            CourseUser::TABLE_COURSE_USER . " cu on cu.id=u.id JOIN " . Role::TABLE_USER_ROLE . " ur on ur.id=u.id JOIN " .
            Role::TABLE_ROLE . " r on r.id=ur.role and r.course=cu.course",
            $where,
            "u.*, a.username, a.authentication_service, cu.lastActivity, cu.previousActivity, cu.isActive as isActiveInCourse"
        );
        foreach ($courseUsers as &$courseUser) { $courseUser = CourseUser::parse($courseUser); }
        return $courseUsers;
    }

    public function getStudents(?bool $active = null): array
    {
        return $this->getCourseUsersWithRole($active, "Student");
    }

    public function getTeachers(?bool $active = null): array
    {
        return $this->getCourseUsersWithRole($active, "Teacher");
    }

    public function addUserToCourse(int $userId, string $roleName = null, int $roleId = null): CourseUser
    {
        return CourseUser::addCourseUser($userId, $this->id, $roleName, $roleId);
    }

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
     */
    public function addRole(string $roleName, string $landingPageName = null, int $landingPageId = null)
    {
        Role::addRoleToCourse($this->id, $roleName, $landingPageName, $landingPageId);
    }

    /**
     * Removes a given role from course.
     * Option to pass either role name or role ID.
     *
     * @param string|null $roleName
     * @param int|null $roleId
     * @return void
     */
    public function removeRole(string $roleName = null, int $roleId = null)
    {
        Role::removeRoleFromCourse($this->id, $roleName, $roleId);
    }

    /**
     * Checks whether course has a given role.
     * Option to pass either role name or role ID.
     *
     * @param string|null $roleName
     * @param int|null $roleId
     * @return bool
     */
    public function hasRole(string $roleName = null, int $roleId = null): bool
    {
        return Role::courseHasRole($this->id, $roleName, $roleId);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Modules --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getModuleById(string $moduleId): ?Module
    {
        return Module::getModuleById($moduleId, $this);
    }

    public function getModules(?bool $enabled = null): array
    {
        $table = Module::TABLE_MODULE . " m JOIN " . Module::TABLE_COURSE_MODULE . " cm on cm.module=m.id";
        $where = [];
        if ($enabled !== null) $where["cm.isEnabled"] = $enabled;
        $modules = Core::database()->selectMultiple($table, $where, "m.*, cm.isEnabled, cm.minModuleVersion, cm.maxModuleVersion");
        foreach ($modules as &$module) { $module = Module::parse($module); }
        return $modules;
    }

    public function setModuleEnabled(string $moduleId, bool $isEnabled)
    {
        $module = $this->getModuleById($moduleId);
        $module->setEnabled($isEnabled);
    }

    public function isModuleEnabled(string $moduleId): bool
    {
        $module = $this->getModuleById($moduleId);
        return $module->isEnabled();
    }

    public function getCompatibleModuleVersions(string $moduleId): array
    {
        $compatibility = Core::database()->select(Module::TABLE_COURSE_MODULE,
            ["course" => $this->id, "module" => $moduleId], "minModuleVersion, maxModuleVersion");
        return [
            "min" => $compatibility["minModuleVersion"],
            "max" => $compatibility["maxModuleVersion"]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Course Data -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a readable copy of all course's records of users, like
     * awards given to students, their grade, etc, so teachers can
     * keep hard copies for themselves.
     */
    // TODO: getDataRecords

    public function getDataFolder(bool $fullPath = true, string $courseName = null): string
    {
        if (!$courseName) $courseName = $this->getName();
        $courseFolderName = $this->id . '-' . Utils::swapNonENChars($courseName);

        if ($fullPath) return COURSE_DATA_FOLDER . "/" . $courseFolderName;
        else {
            $parts = explode("/", COURSE_DATA_FOLDER);
            return end($parts) . "/" . $courseFolderName;
        }
    }

    public function getDataFolderContents(): array
    {
        return Utils::getDirectoryContents($this->getDataFolder());
    }

    public static function createDataFolder(int $courseId, string $courseName = null): string
    {
        $dataFolder = (new Course($courseId))->getDataFolder(true, $courseName);
        if (!file_exists($dataFolder)) mkdir($dataFolder, 0777, true);
        return $dataFolder;
    }

    public static function removeDataFolder(int $courseId, string $courseName = null)
    {
        $dataFolder = (new Course($courseId))->getDataFolder(true, $courseName);
        Utils::deleteDirectory($dataFolder);
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
     *  URL: http://localhost/gamecourse/api/course_data/<courseFolder>/skills/<skillName>/<filename>
     *  NEW URL: skills/<skillName>/<filename>
     *
     * @example relative -> absolute
     *  URL: skills/<skillName>/<filename>
     *  NEW URL: http://localhost/gamecourse/api/course_data/<courseFolder>/skills/<skillName>/<filename>
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

        if ($to === "absolute" && strpos($url, 'http') !== 0) return $dataFolderPath . $url;
        elseif ($to === "relative" && strpos($url, API_URL) === 0) return str_replace($dataFolderPath, "", $url);
        return $url;
    }

    /**
     * Uploads a given file to a directory inside course's
     * data folder.
     *
     * @example uploadFile("skills", <base64>, "name", "txt") --> uploads file with name "name" to skills folder inside course data
     * @example uploadFile("skills/skill1", <base64>, "name", "txt") --> uploads file with name "name" to skill1 folder inside skills folder in course_data
     *
     * @param string $to
     * @param string $base64
     * @param string $name
     * @param string $extension
     * @return string
     */
    public function uploadFile(string $to, string $base64, string $name, string $extension): string
    {
        $path = $this->getDataFolder() . "/" . $to;
        return Utils::uploadFile($path, $base64, $name . "." . $extension);
    }

    /**
     * Deletes a given file from a directory inside course's
     * date folder.
     *
     * @example deleteFile("skills", "file1.txt") --> deletes file "file1.txt" from skills folder inside course data
     * @example deleteFile("skills/skill1", "file1.txt") --> deletes file "file1.txt" from skill1 folder inside skills folder in course_data
     *
     * @param string $from
     * @param string $filename
     * @return void
     */
    public function deleteFile(string $from, string $filename)
    {
        $path = $this->getDataFolder() . "/" . $from;
        Utils::deleteFile($path, $filename);
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Styling ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getStyleFile(): ?array
    {
        $path = $this->getDataFolder(false) . "/css/styling.css";
        if (file_exists(ROOT_PATH . $path)) return ["path" => API_URL . "/" . $path, "contents" => file_get_contents($path)];
        return null;
    }

    public function createStyleFile(): string
    {
        return $this->updateStyleFile("");
    }

    public function updateStyleFile(string $contents): string
    {
        $cssFolder = $this->getDataFolder(false) . "/css";
        if (!file_exists(ROOT_PATH . $cssFolder))
            mkdir($cssFolder, 0777, true);

        $path = $cssFolder . "/styling.css";
        if (file_put_contents(ROOT_PATH . $path, $contents) !== false) return API_URL . "/" . $path;
        else throw new Error("An error ocurred when creating style file for course with ID = " . $this->id . ".");
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
     */
    public static function importCourses(string $contents, bool $replace = true): int
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Extract contents
        $zipPath = $tempFolder . "/courses.zip";
        file_put_contents($zipPath, $contents);
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Error("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        unlink($zipPath);

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
        if (count(glob(ROOT_PATH . "temp/*")) == 0)
            Utils::deleteDirectory(ROOT_PATH . "temp");

        return $nrCoursesImported;
    }

    /**
     * Exports courses information and individual folders for each
     * course, with additional data such as course data and autogame
     * configuration, to a .zip file.
     *
     * @return string
     */
    public static function exportCourses(): string
    {
        $courses = self::getCourses();

        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store courses' info
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/courses.zip";
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE))
            throw new Error("Failed to create zip archive.");

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
                    $zip->addFile($dataFolder . $f, $courseFolder . $f);
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
     * @param $color
     * @param $year
     * @param $startDate
     * @param $endDate
     * @param $isActive
     * @param $isVisible
     * @return void
     */
    private static function validateCourse($name, $color, $year, $startDate, $endDate, $isActive, $isVisible)
    {
        self::validateName($name);
        self::validateColor($color);
        self::validateYear($year);
        self::validateDateTime($startDate);
        self::validateDateTime($endDate);
        if (!is_bool($isActive)) throw new Error("'isActive' must be either true or false.");
        if (!is_bool($isVisible)) throw new Error("'isVisible' must be either true or false.");
    }

    private static function validateName($name)
    {
        if (!is_string($name) || empty($name))
            throw new Error("Course name can't be null neither empty.");

        preg_match("/[^\w()\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Error("Course name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-'");
    }

    private static function validateColor($color)
    {
        if (is_null($color)) return;
        preg_match("/^#[\w\d]{6}$/", $color, $matches);
        if (!is_string($color) || empty($color) || count($matches) == 0)
            throw new Error("Course color needs to be in HEX format.");
    }

    private static function validateYear($year)
    {
        if (is_null($year)) return;
        preg_match("/^\d{4}-\d{4}$/", $year, $matches);
        if (!is_string($year) || empty($year) || count($matches) == 0)
            throw new Error("Course year needs to be 'yyyy-yyyy' format.");
    }

    private static function validateDateTime($dateTime)
    {
        if (is_null($dateTime)) return;
        if (!is_string($dateTime) || !Utils::validateDate($dateTime, "Y-m-d H:i:s"))
            throw new Error("Datetime '" . $dateTime . "' should be in format 'yyyy-mm-dd HH:mm:ss'");
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
     * @return array|bool|int|null
     */
    public static function parse(array $course = null, $field = null, string $fieldName = null)
    {
        if ($course) {
            if (isset($course["id"])) $course["id"] = intval($course["id"]);
            if (isset($course["landingPage"])) $course["landingPage"] = intval($course["landingPage"]);
            if (isset($course["isActive"])) $course["isActive"] = boolval($course["isActive"]);
            if (isset($course["isVisible"])) $course["isVisible"] = boolval($course["isVisible"]);
            if (isset($course["roleHierarchy"])) $course["roleHierarchy"] = json_decode($course["roleHierarchy"], true);
            return $course;

        } else {
            if ($fieldName == "id" || ($fieldName == "landingPage" && $field)) return intval($field);
            if ($fieldName == "isActive" || $fieldName == "isVisible") return boolval($field);
            if ($fieldName == "roleHierarchy") return json_decode($field, true);
            return $field;
        }
    }
}
