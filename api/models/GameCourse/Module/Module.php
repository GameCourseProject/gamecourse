<?php
namespace GameCourse\Module;

use Event\Event;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the Module model, which implements the necessary methods
 * to interact with modules in the MySQL database.
 */
abstract class Module
{
    const TABLE_MODULE = "module";
    const TABLE_MODULE_DEPENDENCY = "module_dependency";
    const TABLE_COURSE_MODULE = "course_module";

    protected $id;
    protected $course;

    public function __construct(?Course $course)
    {
        $this->course = $course;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getDescription(): string
    {
        return $this->getData("description");
    }

    public function getType(): string
    {
        return $this->getData("type");
    }

    /**
     * Gets module's current version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->getData("version");
    }

    /**
     * Gets compatible versions for project and API, i.e. the
     * min/max versions the module needs to work properly.
     *
     * @return array
     */
    public function getCompatibleVersions(): array
    {
        $compatibility = $this->getData("minProjectVersion, maxProjectVersion, minAPIVersion, maxAPIVersion");
        return [
            "project" => ["min" => $compatibility["minProjectVersion"], "max" => $compatibility["maxProjectVersion"]],
            "api" => ["min" => $compatibility["minAPIVersion"], "max" => $compatibility["maxAPIVersion"]]
        ];
    }

    /**
     * Gets module's registered resources.
     * NOTE: only works with one-level directories
     *
     * @return array
     */
    public function getResources(): array
    {
        $resources = [];
        foreach ($this::RESOURCES as $resource) {
            $path = MODULES_FOLDER . "/" . $this->id . "/" . $resource;
            $realPath = API_URL . "/modules/" . $this->id . "/" . $resource;

            if (is_dir($path)) {
                $contents = Utils::getDirectoryContents($path);
                foreach ($contents as $file) {
                    $filePath = $realPath . $file["name"];
                    $resources[] = $filePath;
                }

            } else $resources[] = $realPath;
        }
        return $resources;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    /**
     * @throws Exception
     */
    public function isEnabled(): bool
    {
        if (!$this->course)
            throw new Exception("Can't check whether module '" . $this->id . "' is enabled: no course given.");

        return boolval(Core::database()->select(
            self::TABLE_COURSE_MODULE,
            ["module" => $this->id, "course" => $this->course->getId()],
            "isEnabled"
        ));
    }

    /**
     * Gets module data from the database.
     *
     * @example getData() --> gets all module data
     * @example getData("name") --> gets module name
     * @example getData("name, description") --> gets module name & description
     *
     * @param string $field
     * @return mixed|void
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_MODULE;
        $where = ["id" => $this->id];
        return Core::database()->select($table, $where, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    public function setDescription(string $description)
    {
        $this->setData(["description" => $description]);
    }

    public function setType(string $type)
    {
        $this->setData(["type" => $type]);
    }

    public function setVersion(string $version)
    {
        $this->setData(["version" => $version]);
    }

    public function setProjectCompatibility(string $min, ?string $max)
    {
        $this->setData(["minProjectVersion" => $min]);
        $this->setData(["maxProjectVersion" => $max]);
    }

    public function setAPICompatibility(string $min, ?string $max)
    {
        $this->setData(["minAPIVersion" => $min]);
        $this->setData(["maxAPIVersion" => $max]);
    }

    public function setCourse(Course $course)
    {
        $this->course = $course;
    }

    /**
     * @throws Exception
     */
    public function setEnabled(bool $isEnabled)
    {
        if (!$this->course)
            throw new Exception("Can't enable/disable module '" . $this->id . "': no course given.");

        // Check course/module compatibility
        $compatibleModuleVersions = $this->course->getCompatibleModuleVersions($this->id);
        $moduleVersion = $this->getVersion();
        if (!(Utils::compareVersions($moduleVersion, $compatibleModuleVersions["min"]) >= 0 &&
            (is_null($compatibleModuleVersions["max"]) || Utils::compareVersions($moduleVersion, $compatibleModuleVersions["max"]) <= 0)))
            throw new Exception("Course with ID = " . $this->course->getId() . " is not compatible with module '" . $this->id . "' v" . $moduleVersion . ".
                            Needs module version >= " . $compatibleModuleVersions["min"] . (!is_null($compatibleModuleVersions["max"]) ? " & <= " . $compatibleModuleVersions["max"] : "") . ".");

        // Check project compatibility
        $compatibleVersions = $this->getCompatibleVersions();
        if (!(Utils::compareVersions(PROJECT_VERSION, $compatibleVersions["project"]["min"]) >= 0 &&
            (is_null($compatibleVersions["project"]["max"]) || Utils::compareVersions(PROJECT_VERSION, $compatibleVersions["project"]["max"]) <= 0)))
            throw new Exception("Module '" . $this->id . "' v" . $this->getVersion() . " is not compatible with project v" . PROJECT_VERSION . ".
                            Needs project version >= " . $compatibleVersions["project"]["min"] . (!is_null($compatibleVersions["project"]["max"]) ? " & <= " . $compatibleVersions["project"]["max"] : "") . ".");

        // Check API compatibility
        if (!(Utils::compareVersions(API_VERSION, $compatibleVersions["api"]["min"]) >= 0 &&
            (is_null($compatibleVersions["api"]["max"]) || Utils::compareVersions(API_VERSION, $compatibleVersions["api"]["max"]) <= 0)))
            throw new Exception("Module '" . $this->id . "' v" . $this->getVersion() . " is not compatible with API v" . API_VERSION . ".
                            Needs API version >= " . $compatibleVersions["api"]["min"] . (!is_null($compatibleVersions["api"]["max"]) ? " & <= " . $compatibleVersions["api"]["max"] : "") . ".");

        // Check dependencies
        if ($isEnabled) {
            // Check dependencies of module are enabled
            $hardDependencies = $this->getDependencies(DependencyMode::HARD);
            foreach ($hardDependencies as $dependency) {
                $depModule = $this->course->getModuleById($dependency["id"]);
                if (!$depModule->isEnabled())
                    throw new Exception("Can't enable module '" . $this->id . "' as its hard dependency '" . $dependency["id"] . "' is disabled.");
            }

        } else {
            // Check there's no modules depending on it
            $dependants = $this->getDependants(DependencyMode::HARD);
            if (count($dependants) > 0)
                throw new Exception("Can't disable module '" . $this->id . "' as module '" . $dependants[0]["id"] . "' depends on it.");
        }

        Core::database()->update(self::TABLE_COURSE_MODULE, ["isEnabled" => +$isEnabled],
            ["module" => $this->id, "course" => $this->course->getId()]);

        if ($isEnabled) $this->init();
        else $this->disable();
    }

    /**
     * Sets module data on the database.
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "description" => "New description"])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_MODULE, $fieldValues, ["id" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Setup ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Registers modules available in the system.
     * This is only performed once during system setup.
     *
     * @return void
     * @throws Exception
     */
    public static function setupModules()
    {
        Core::database()->setForeignKeyChecks(false);
        $modulesFolders = Utils::getDirectoryContents(MODULES_FOLDER);
        foreach ($modulesFolders as $folder) {
            $moduleId = $folder["name"];
            $mainFile = MODULES_FOLDER . "/" . $moduleId . "/" . $moduleId. ".php";

            if (!file_exists($mainFile))
                throw new Exception("Can't find main file for module '" . $moduleId . "'.");

            $moduleClass = "\\GameCourse\\" . $moduleId . "\\" . $moduleId;
            $module = new $moduleClass(null);

            self::addModule($moduleId, $module::NAME, $module::DESCRIPTION, $module::TYPE, $module::VERSION,
                $module::PROJECT_VERSION, $module::API_VERSION, $module::DEPENDENCIES);
        }
        Core::database()->setForeignKeyChecks(true);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function getModuleById(string $id, ?Course $course): ?Module
    {
        $moduleClass = "\\GameCourse\\" . $id . "\\" . $id;
        $module = new $moduleClass($course);
        if ($module->exists()) return $module;
        else return null;
    }

    public static function getModules(bool $IDsOnly = false): array
    {
        $field = $IDsOnly ? "id" : "*";
        $modules = Core::database()->selectMultiple(self::TABLE_MODULE, [], $field, "id");
        foreach ($modules as &$module) { $module = self::parse($module); }
        return $modules;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Module Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a module to the database.
     * Returns the newly created module.
     *
     * @param string $id
     * @param string $name
     * @param string $description
     * @param string $type
     * @param string $version
     * @param array $projectCompatibility
     * @param array $APICompatibility
     * @param array $dependencies
     * @return Module
     */
    public static function addModule(string $id, string $name, string $description, string $type, string $version,
                                    array $projectCompatibility, array $APICompatibility, array $dependencies): Module
    {
        Core::database()->insert(self::TABLE_MODULE, [
            "id" => $id,
            "name" => $name,
            "description" => $description,
            "type" => $type,
            "version" => $version,
            "minProjectVersion" => $projectCompatibility["min"],
            "maxProjectVersion" => $projectCompatibility["max"],
            "minAPIVersion" => $APICompatibility["min"],
            "maxAPIVersion" => $APICompatibility["max"]
        ]);
        $module = self::getModuleById($id, null);
        $module->setDependencies($dependencies);
        return $module;
    }

    /**
     * Deletes a module from the database.
     *
     * @param string $moduleId
     * @return void
     */
    public static function deleteModule(string $moduleId)
    {
        Core::database()->delete(self::TABLE_MODULE, ["id" => $moduleId]);
    }

    /**
     * Checks whether module exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Dependencies ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getDependencies(string $mode = null, bool $IDsOnly = false): array
    {
        $field = $IDsOnly ? "id" : "dependency as id, minDependencyVersion, maxDependencyVersion, mode";
        $where = ["module" => $this->id];
        if ($mode !== null) $where["mode"] = $mode;
        return Core::database()->selectMultiple(self::TABLE_MODULE_DEPENDENCY, $where, $field, "id");
    }

    public function getDependants(string $mode = null, bool $IDsOnly = false): array
    {
        $field = $IDsOnly ? "id" : "module as id, mode";
        $where = ["dependency" => $this->id];
        if ($mode !== null) $where["mode"] = $mode;
        return Core::database()->selectMultiple(self::TABLE_MODULE_DEPENDENCY, $where, $field, "id");
    }

    public function setDependencies(array $dependencies)
    {
        // Remove all module dependencies
        Core::database()->delete(self::TABLE_MODULE_DEPENDENCY, ["module" => $this->getId()]);

        // Add new dependencies
        foreach ($dependencies as $dependency) {
            $this->addDependency($dependency);
        }
    }

    public function addDependency(array $dependency)
    {
        if (!$this->hasDependency($dependency["id"])) {
            Core::database()->insert(self::TABLE_MODULE_DEPENDENCY, [
                "module" => $this->getId(),
                "dependency" => $dependency["id"],
                "minDependencyVersion" => $dependency["minVersion"],
                "maxDependencyVersion" => $dependency["maxVersion"],
                "mode" => $dependency["mode"]
            ]);
        }
    }

    public function removeDependency(string $dependencyId)
    {
        Core::database()->delete(self::TABLE_MODULE_DEPENDENCY, ["module" => $this->getId(), "dependency" => $dependencyId]);
    }

    public function hasDependency(string $dependencyId): bool
    {
        return !empty(Core::database()->select(self::TABLE_MODULE_DEPENDENCY, ["module" => $this->getId(), "dependency" => $dependencyId]));
    }

    /**
     * Checks if a given dependecy exists and is enabled in the course.
     * This is most useful to check soft dependencies.
     *
     * @param string $dependencyId
     * @return void
     * @throws Exception
     */
    public function checkDependency(string $dependencyId)
    {
        $module = $this->course->getModuleById($dependencyId);
        if (!$module) throw new Exception("Module '" . $dependencyId . "' doesn't exist in the system.");
        if (!$module->isEnabled()) throw new Exception("Module '" . $dependencyId . "' is not enabled.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Actions --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Actions to be performed when enabling the module.
     */
    abstract function init();

    /**
     * Creates necessary tables and sets up default data.
     *
     * @return void
     */
    protected function initDatabase()
    {
        $sql = file_get_contents(MODULES_FOLDER . "/" . $this->id . "/sql/create.sql");
        Core::database()->executeQuery($sql);
    }

    /**
     * Creates default templates of module.
     */
    protected function initTemplates()
    {}

    /**
     * Sets events to listen to right from the start and their
     * callback functions.
     *
     * @return void
     */
    protected function initEvents()
    {
    }

    /**
     * Actions to be performed when disabling the module.
     */
    abstract function disable();

    /**
     * Deletes module data and tables if not enabled in any course.
     *
     * @return void
     */
    protected function cleanDatabase()
    {
        if (empty(Core::database()->select(self::TABLE_COURSE_MODULE, ["module" => $this->id, "isEnabled" => true]))) {
            // Drop module tables if is not enabled in any course
            $sql = file_get_contents(MODULES_FOLDER . "/" . $this->id . "/sql/delete.sql");
            Core::database()->executeQuery($sql);

        } else {
            // Delete module entries
            $this->deleteEntries();
        }
    }

    /**
     * Deletes entries related to the module from module tables.
     */
    protected function deleteEntries()
    {
    }

    protected function cleanTemplates()
    {
    }

    /**
     * Stops listening to any events of module.
     *
     * @return void
     */
    protected function removeEvents()
    {
        Event::stopAll($this->getId());
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Configuration ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Whether the module has a configuration page.
     *
     * @return bool
     */
    protected function isConfigurable(): bool
    {
        return false;
    }

    /**
     * Whether the module has inputs to configure general attributes
     * in its configuration page.
     *
     * @return bool
     */
    protected function hasGeneralInputs(): bool
    {
        return false;
    }

    /**
     * Gets general inputs to show on configuration page where each
     * input has:
     *  - id: unique ID
     *  - label: label to show
     *  - type: type of input (check Config/InputType.php for more info)
     *  - value: current value
     *  - options?: list of options (check Config/InputType.php for more info)
     * @return array
     */
    protected function getGeneralInputs(): array
    {
        return [];
    }

    /**
     * Updates general inputs.
     *
     * @param array $inputs
     * @return mixed
     */
    protected function saveGeneralInputs(array $inputs)
    {
    }

    /**
     * Whether the module has a list of items in its configuration page.
     *
     * @return bool
     */
    protected function hasListingItems(): bool
    {
        return false;
    }

    /**
     * Gets listing info to show on configuration page where info has:
     *  - listName: name of the list
     *  - itemName: name for an item of the list
     *  - listInfo: information for every collumn
     *  - items: items of the list
     *  - actions?: actions available for items (check Config/Action.php for more info)
     *  - edit?: information for editing items
     * @return array
     */
    protected function getListingItems(): array
    {
        return [];
    }

    /**
     * Updates listing item.
     *
     * @param string $action
     * @param array $item
     * @return mixed
     */
    protected function saveListingItem(string $action, array $item)
    {
    }

    /**
     * Whether the module has a personalized section in its configuration page.
     *
     * @return bool
     */
    protected function hasPersonalizedConfig(): bool
    {
        return false;
    }

    /**
     * Gets module personalized configuration info like:
     *  - HTML to render
     *  - Styles it might have (css format)
     *  - Scripts it might have
     * @return array
     */
    protected function getPersonalizedConfig(): array
    {
        return [];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Import/Export ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public static function importModules()
    {
        // TODO: install modules
    }

    public static function exportModules()
    {
        // TODO: export modules code
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Installation ------------------- ***/
    /*** ---------------------------------------------------- ***/

    private static function installModule()
    {
        // TODO: upload module files
    }

    public static function uninstallModule()
    {
        // TODO: remove module files (ver function deleteModule() on Module.php
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a module coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $module
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|null
     */
    public static function parse(array $module = null, $field = null, string $fieldName = null)
    {
        if ($module) {
            if (isset($module["isEnabled"])) $module["isEnabled"] = boolval($module["isEnabled"]);
            return $module;

        } else {
            if ($fieldName == "isEnabled") return boolval($field);
            return $field;
        }
    }
}
