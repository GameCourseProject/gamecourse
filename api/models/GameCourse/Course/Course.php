<?php
namespace GameCourse\Course;

use Api\API;
use FilesystemIterator;
use GameCourse\AutoGame\GameRules;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Role\Role;
use GameCourse\User\CourseUser;
use GameCourse\Views\Template;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Utils\CronJob;

/**
 * This is the Course model, which implements the necessary methods
 * to interact with courses in the MySQL database.
 */
class Course
{
    const TABLE_COURSE = "course";

    const HEADERS = [   // headers for import/export functionality
        "name", "short", "color", "year", "startDate", "endDate", "defaultLandingPage",
        "lastUpdate", "isActive", "isVisible", "roleHierarchy", "theme"
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

    public function getShort(): string
    {
        return $this->getData("short");
    }

    public function getColor(): string
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

    public function getLandingPage(): string
    {
        return $this->getData("defaultLandingPage");
    }

    public function getLastUpdate(): string
    {
        return $this->getData("lastUpdate");
    }

    public function getTheme(): ?string
    {
        return $this->getData("theme");
    }

    public function isActive(): bool
    {
        return boolval($this->getData("isActive"));
    }

    public function isVisible(): bool
    {
        return boolval($this->getData("isVisible"));
    }

    /**
     * Gets course data from the database.
     * @example getData() --> gets all course data
     * @example getData("name") --> gets course name
     * @example getData("name, short") --> gets course name & short
     *
     * @param string $field
     * @return mixed|void
     */
    public function getData(string $field = "*")
    {
        if ($field != "folder")
            $data = Core::database()->select(self::TABLE_COURSE, ["id" => $this->id], $field);
        if ($field == "*" || $field == "folder")
            $data["folder"] = Course::getCourseDataFolder($this->id);
        return $data;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    public function setShort(string $short)
    {
        $this->setData(["short" => $short]);
    }

    public function setColor(string $color)
    {
        $this->setData(["color" => $color]);
    }

    public function setYear(string $year)
    {
        $this->setData(["year" => $year]);
    }

    public function setStartDate(string $start)
    {
        $this->setData(["startDate" => $start]);
    }

    public function setEndDate(string $end)
    {
        $this->setData(["endDate" => $end]);
    }

    public function setLandingPage(string $page)
    {
        $this->setData(["defaultLandingPage" => $page]);
    }

    public function setLastUpdate(string $lastUpdate)
    {
        $this->setData(["lastUpdate" => $lastUpdate]);
    }

    public function setTheme(string $theme)
    {
        $this->setData(["theme" => $theme]);
    }

    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
        $this->setAutoGame($isActive);
    }

    public function setVisible(bool $isVisible)
    {
        $this->setData(["isVisible" => +$isVisible]);
    }

    /**
     * Sets course data on the database.
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "short" => "New short"])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_COURSE, $fieldValues, ["id" => $this->id]);
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
        if ($courseId == null) return null;
        else return new Course($courseId);
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Course Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a course to the database.
     * Returns the newly created course.
     *
     * @param string $name
     * @param string $short
     * @param string $year
     * @param string $color
     * @param string $startDate
     * @param string $endDate
     * @param bool $isActive
     * @param bool $isVisible
     * @param int|null $copyFrom
     * @return Course
     */
    public static function addCourse(string $name, string $short, string $year, string $color, string $startDate,
                                     string $endDate, bool $isActive, bool $isVisible, int $copyFrom = null): Course
    {
        // Check if there's a user logged in first
        $loggedUser = Core::getLoggedUser();
        if (!$loggedUser) API::error("No user currently logged in. Can't create new course.");

        // Add time to date strings
        if ($startDate) $startDate .= " 00:00:00";
        if ($endDate) $endDate .= " 00:00:00";

        // Insert in database & create data folder
        $id = Core::database()->insert(self::TABLE_COURSE, [
            "name" => $name,
            "short" => $short,
            "year" => $year,
            "color" => $color,
            "startDate"=> $startDate,
            "endDate"=> $endDate,
            "isActive" => +$isActive,
            "isVisible" => +$isVisible
        ]);
        $dataFolder = Course::createCourseDataFolder($id, $name);

        // Add current user to the course
        Core::database()->insert(CourseUser::TABLE_COURSE_USER, ["id" => $loggedUser->getId(), "course" => $id]);

        if ($copyFrom !== null) {   // Make a copy of a course
            $copyFromCourse = Course::getCourseById($copyFrom);
            $copyDataFolder = Course::getCourseDataFolder($copyFrom);

            // Copy data folder contents
            Course::copyCourseDataFolder($copyDataFolder, $dataFolder);

            // Update course in database
            $keys = ["defaultLandingPage", "roleHierarchy", "theme"];
            $fromCourseData = $copyFromCourse->getData();
            $newData = [];
            foreach ($keys as $key)
                $newData[$key] = $fromCourseData[$key];
            Core::database()->update(self::TABLE_COURSE, $newData, ["id" => $id]);

            // Copy roles info
            $oldRoles = Course::copyCourseContent(Role::TABLE_ROLE, $copyFrom, $id, true);
            $oldRolesByName = array_combine(array_column($oldRoles, "name"), $oldRoles);
            $newRoles = Core::database()->selectMultiple(Role::TABLE_ROLE, ["course" => $id]);
            $newRolesByName = array_combine(array_column($newRoles, "name"), $newRoles);
            Core::database()->insert(Role::TABLE_USER_ROLE, ["id" => $loggedUser->getId(), "course" => $id, "role" => $newRolesByName["Teacher"]["id"]]);

            // Copy modules info
            Course::copyCourseContent(Module::TABLE_COURSE_MODULE, $copyFrom, $id);
            $enabledModules = $copyFromCourse->getModules(true);
//            foreach ($enabledModules as $moduleName) { FIXME: refactoring architecture
//                $module = ModuleLoader::getModule($moduleName);
//                $handler = $module["factory"]();
//                if ($handler->is_configurable() && $moduleName != "awardList") {
//                    $moduleArray = $handler->moduleConfigJson($copyFrom);
//                    $result = $handler->readConfigJson($courseId, $moduleArray, false);
//                }
//            }

            // Copy pages & templates
            $templates = Core::database()->selectMultiple(
                Template::TABLE_VIEW_TEMPLATE . " vt join " . Template::TABLE_TEMPLATE . " t on vt.templateId=id",
                ["course" => $copyFrom, "isGlobal" => 0]
            );
//            foreach ($templates as $t) { FIXME: refactoring architecture
//                $view = Core::database()->selectMultiple(
//                    Template::TABLE_VIEW_TEMPLATE . " vt join " . View::TABLE_VIEW . " v on vt.viewId=v.viewId",
//                    ["templateId" => $t["id"]],
//                    "v.*"
//                );
//                ViewHandler::buildView($view, true);
//                [$templateId, $viewId] = Views::createTemplate($view, $courseId, $t["name"], $t["roleType"]);
//
//                $pagesOfTemplate = Core::$systemDB->selectMultiple("page", ["viewId" => $t["viewId"]]);
//                foreach ($pagesOfTemplate as $page) {
//                    Views::createPage($courseId, $page["name"], (int)$viewId, 0);
//                }
//            }

            // Copy autogame info
            $functionsFolder = ROOT_PATH . "autogame/imported-functions/" . $id . "/";
            $functionsFolderPrev = ROOT_PATH . "autogame/imported-functions/" . $copyFrom . "/";
            mkdir($functionsFolder);
            if (is_dir($functionsFolderPrev)) {
                $funcDirListing = scandir($functionsFolderPrev, SCANDIR_SORT_ASCENDING);
                $ruleFileList = preg_grep('~\.(py)$~i', $funcDirListing);
                foreach ($ruleFileList as $file) {
                    $txt = file_get_contents($functionsFolderPrev . $file);
                    file_put_contents($functionsFolder . $file, $txt);
                }
            }
            $metadataFilePrev = ROOT_PATH . "autogame/config/config_" . $copyFrom . ".txt";
            $metadataFile = ROOT_PATH . "autogame/config/config_" . $id . ".txt";
            if (file_exists($metadataFilePrev)) {
                $txt = file_get_contents($metadataFilePrev);
                file_put_contents($metadataFile, $txt);
            }

        } else {    // Create a new course
            // Add current user as a Teacher of the course
            $teacherRoleId = Role::addDefaultRolesToCourse($id);
            Core::database()->insert(Role::TABLE_USER_ROLE, ["id" => $loggedUser->getId(), "course" => $id, "role" => $teacherRoleId]);

            // Add modules to course
            $modules = Core::database()->selectMultiple(Module::TABLE_MODULE);
            foreach ($modules as $mod) {
                Core::database()->insert(Module::TABLE_COURSE_MODULE, ["course" => $id, "moduleId" => $mod["moduleId"]]);
            }

            // autogame configs
            $rulesfolder = join("/", array($dataFolder, "rules"));
            $functionsFolder = ROOT_PATH . "autogame/imported-functions/" . $id;
            $functionsFileDefault = ROOT_PATH . "autogame/imported-functions/defaults.py";
            $defaultFunctionsFile = "/defaults.py";
            $metadataFile = ROOT_PATH . "autogame/config/config_" . $id . ".txt";
            mkdir($rulesfolder);
            mkdir($functionsFolder);
            $defaults = file_get_contents($functionsFileDefault);
            file_put_contents($functionsFolder . $defaultFunctionsFile, $defaults);
            file_put_contents($metadataFile, "");
        }

        // Insert line in autogame table
        Core::database()->insert(GameRules::TABLE_AUTOGAME, ["course" => $id]);
        $logsFile = ROOT_PATH . "logs/log_course_" . $id . ".txt";
        file_put_contents($logsFile, "");
        return new Course($id);
    }

    /**
     * Edits an existing course in database.
     * Returns the edited course.
     *
     * @param string $name
     * @param string $short
     * @param string $year
     * @param string $color
     * @param string $startDate
     * @param string $endDate
     * @param bool $isActive
     * @param bool $isVisible
     * @return Course
     */
    public function editCourse(string $name, string $short, string $year, string $color, string $startDate,
                               string $endDate, bool $isActive, bool $isVisible): Course
    {
        // Update course data folder if name has changed
        $oldName = $this->getData("name");
        if (strcmp($oldName, $name) !== 0)
            rename(Course::getCourseDataFolder($this->getId(), $oldName), Course::getCourseDataFolder($this->getId(), $name));

        Core::database()->update(self::TABLE_COURSE, [
            "name" => $name,
            "short" => $short,
            "year" => $year,
            "color" => $color,
            "startDate" => $startDate . " 00:00:00",
            "endDate" => $endDate . " 00:00:00",
            "isActive" => +$isActive,
            "isVisible" => +$isVisible
        ], ["id" => $this->id]);
        return $this;
    }

    /**
     * Deletes a course from the database and removes
     * its data folder.
     *
     * @param int $courseId
     * @return void
     */
    public static function deleteCourse(int $courseId)
    {
        // Remove data folder
        Course::removeCourseDataFolder(Course::getCourseDataFolder($courseId));

        // Disable autogame
        CronJob::removeCronJob("AutoGame", $courseId);

        // Delete from database
        Core::database()->delete(self::TABLE_COURSE, ["id" => $courseId]);

        // Remove autogame related folders
        Course::removeCourseDataFolder("autogame/imported-functions/" . $courseId);
        if (file_exists(ROOT_PATH . "autogame/config/config_" . $courseId . ".txt"))
            unlink(ROOT_PATH . "autogame/config/config_" . $courseId . ".txt");
        if (file_exists(ROOT_PATH . "logs/log_course_" . $courseId . ".txt"))
            unlink(ROOT_PATH . "logs/log_course_" . $courseId . ".txt");
    }

    /**
     * Checks whether course exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return (!empty($this->getData("id")));
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Course Users ------------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Course Modules ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public function getModules(bool $enabled = null): array
    {
        $where = ["course" => $this->getId()];
        if ($enabled != null) $where["isEnabled"] = $enabled;
        return array_column(Core::database()->selectMultiple(
            Module::TABLE_COURSE_MODULE, $where, "moduleId", "moduleId"), "moduleId");
    }

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Roles ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Styles ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Resources -------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function getCourseDataFolder(int $courseId, string $courseName = null): string
    {
        if (!$courseName) $courseName = Core::database()->select(self::TABLE_COURSE, ["id" => $courseId], "name");
        $courseName = preg_replace("/[^a-zA-Z0-9_ ]/", "", $courseName);
        return COURSE_DATA_FOLDER . '/' . $courseId . '-' . $courseName;
    }

    public static function createCourseDataFolder(int $courseId, string $courseName): string
    {
        $folder = ROOT_PATH . Course::getCourseDataFolder($courseId, $courseName);
        if (!file_exists($folder))
            mkdir($folder);
        return $folder;
    }

    public static function copyCourseDataFolder(string $source, string $destination)
    {
        $source = ROOT_PATH . $source;
        $destination = ROOT_PATH . $destination;

        $dir = opendir($source);
        if (!file_exists($destination))
            mkdir($destination);

        while ($file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($source . '/' . $file)) {
                    // Recursively calling custom copy function for subdirectory
                    Course::copyCourseDataFolder($source . '/' . $file, $destination . '/' . $file);
                } else {
                    copy($source . '/' . $file, $destination . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    public static function removeCourseDataFolder(string $target)
    {
        $target = ROOT_PATH . $target;
        $directory = new RecursiveDirectoryIterator($target,  FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if (is_dir($file)) {
                rmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($target);
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
        $courseDataFolder = Course::getCourseDataFolder($courseId);
        $courseDataFolderPath = API_URL . "/" . $courseDataFolder . "/";

        if ($to === "absolute" && strpos($url, 'http') !== 0) return $courseDataFolderPath . $url;
        elseif ($to === "relative" && strpos($url, API_URL) === 0) return str_replace($courseDataFolderPath, "", $url);
        return $url;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Dictionary -------------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Pages ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** -------------- Awards & Participation -------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** --------------------- AutoGame --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setAutoGame(bool $enabled)
    {
        if ($enabled && $this->isActive()) { // enable autogame
            $periodicity = Core::database()->select(GameRules::TABLE_AUTOGAME, ["course" => $this->getId()], "periodicityNumber, periodicityTime");
            new CronJob("AutoGame", $this->id, intval($periodicity["periodicityNumber"]),  $periodicity["periodicityTime"], null);

        } else { // disable autogame
            CronJob::removeCronJob("AutoGame", $this->id);
        }
    }

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Copies content of a specified table in DB to
     * new rows for the new course.
     *
     * @param string $content
     * @param int $fromCourseId
     * @param int $newCourseId
     * @param bool $ignoreID
     * @return mixed
     */
    private static function copyCourseContent(string $content, int $fromCourseId, int $newCourseId, bool $ignoreID = false)
    {
        $fromData = Core::database()->selectMultiple($content, ["course" => $fromCourseId]);
        foreach ($fromData as $data) {
            $data['course'] = $newCourseId;
            if ($ignoreID) unset($data['id']);
            Core::database()->insert($content, $data);
        }
        return $fromData;
    }
}
