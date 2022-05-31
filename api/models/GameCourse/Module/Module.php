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

    public function getIcon(): string
    {
        $parts = explode("/", MODULES_FOLDER);
        $modulesFolder = end($parts);
        return API_URL . "/" . $modulesFolder . "/" . $this->id. "/icon.svg";
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

            $parts = explode("/", MODULES_FOLDER);
            $modulesFolder = end($parts);
            $realPath = API_URL . "/" . $modulesFolder . "/" . $this->id . "/" . $resource;

            if (is_dir($path)) {
                $contents = Utils::getDirectoryContents($path);
                foreach ($contents as $file) {
                    $filePath = $realPath . $file["name"];
                    $resources[basename($path)][] = $filePath;
                }

            } else $resources["single_files"][] = $realPath;
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
    public function setDescription(string $description)
    {
        $this->setData(["description" => $description]);
    }

    /**
     * @throws Exception
     */
    public function setType(string $type)
    {
        $this->setData(["type" => $type]);
    }

    /**
     * @throws Exception
     */
    public function setVersion(string $version)
    {
        $this->setData(["version" => $version]);
    }

    /**
     * @throws Exception
     */
    public function setProjectCompatibility(string $min, ?string $max)
    {
        $this->setData(["minProjectVersion" => $min]);
        $this->setData(["maxProjectVersion" => $max]);
    }

    /**
     * @throws Exception
     */
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

        $this->canChangeState($isEnabled, true);
        Core::database()->update(self::TABLE_COURSE_MODULE, ["isEnabled" => +$isEnabled],
            ["module" => $this->id, "course" => $this->course->getId()]);

        if ($isEnabled) $this->init();
        else $this->disable();
    }

    /**
     * Sets module data on the database.
     * @param array $fieldValues
     * @return void
     * @throws Exception
     * @example setData(["name" => "New name", "description" => "New description"])
     *
     * @example setData(["name" => "New name"])
     */
    public function setData(array $fieldValues)
    {
        if (key_exists("id", $fieldValues)) self::validateName($fieldValues["id"]);
        if (key_exists("name", $fieldValues)) self::validateName($fieldValues["name"]);
        if (key_exists("description", $fieldValues)) self::validateDescription($fieldValues["description"]);
        if (key_exists("type", $fieldValues)) self::validateType($fieldValues["type"]);
        if (key_exists("version", $fieldValues)) self::validateVersion($fieldValues["version"]);
        if (key_exists("minProjectVersion", $fieldValues)) self::validateVersion($fieldValues["minProjectVersion"]);
        if (key_exists("maxProjectVersion", $fieldValues)) self::validateVersion($fieldValues["maxProjectVersion"]);
        if (key_exists("minAPIVersion", $fieldValues)) self::validateVersion($fieldValues["minAPIVersion"]);
        if (key_exists("maxAPIVersion", $fieldValues)) self::validateVersion($fieldValues["maxAPIVersion"]);

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

    /**
     * @throws Exception
     */
    public static function getModules(bool $IDsOnly = false): array
    {
        $modules = Core::database()->selectMultiple(self::TABLE_MODULE, [], "*", "id");
        if ($IDsOnly) return array_column($modules, "id");
        foreach ($modules as &$moduleInfo) {
            $moduleInfo = self::getExtraInfo($moduleInfo, null);
            $moduleInfo = self::parse($moduleInfo);
        }
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
     * @throws Exception
     */
    public static function addModule(string $id, string $name, string $description, string $type, string $version,
                                    array $projectCompatibility, array $APICompatibility, array $dependencies): Module
    {
        self::validateModule($id, $name, $description, $type, $version, $projectCompatibility, $APICompatibility);
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

    /**
     * Gets module dependencies.
     * Option to get dependencies of a certain mode and/or dependencies'
     * IDs only.
     *
     * @param string|null $mode
     * @param bool $IDsOnly
     * @return array
     * @throws Exception
     */
    public function getDependencies(string $mode = null, bool $IDsOnly = false): array
    {
        $field = "dependency as id, minDependencyVersion, maxDependencyVersion, mode";
        $where = ["module" => $this->id];
        if ($mode !== null) $where["mode"] = $mode;
        $dependencies = Core::database()->selectMultiple(self::TABLE_MODULE_DEPENDENCY, $where, $field, "id");
        if ($IDsOnly) return array_column($dependencies, "id");
        foreach ($dependencies as &$dependencyInfo) {
            $dependencyInfo = self::getExtraInfo($dependencyInfo, $this->course);
            $dependencyInfo = self:: parse($dependencyInfo);
        }
        return $dependencies;
    }

    /**
     * Gets module dependants.
     * Option to get dependants of a certain mode and/or dependants'
     * IDs only.
     *
     * @param string|null $mode
     * @param bool $IDsOnly
     * @return array
     * @throws Exception
     */
    public function getDependants(string $mode = null, bool $IDsOnly = false): array
    {
        $field = "module as id, mode";
        $where = ["dependency" => $this->id];
        if ($mode !== null) $where["mode"] = $mode;
        $dependants = Core::database()->selectMultiple(self::TABLE_MODULE_DEPENDENCY, $where, $field, "id");
        if ($IDsOnly) return array_column($dependants, "id");

        foreach ($dependants as &$dependantInfo) {
            $dependantInfo = self::getExtraInfo($dependantInfo, $this->course);
            $dependantInfo = self:: parse($dependantInfo);
        }
        return $dependants;
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
    public function isConfigurable(): bool
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
    public function getGeneralInputs(): ?array
    {
        return null;
    }

    /**
     * Updates general inputs.
     *
     * @param array $inputs
     * @return void
     */
    public function saveGeneralInputs(array $inputs)
    {
    }

    /**
     * Gets lists to show on configuration page where each list has:
     *  - listName: name of the list
     *  - itemName: name for an item of the list
     *  - listInfo: information for every collumn
     *  - items: items of the list
     *  - actions?: actions available for items (check Config/Action.php for more info)
     *  - <action>?: information for acting on items
     * @return array
     */
    public function getLists(): ?array
    {
        return null;
    }

    /**
     * Updates a listing item of a specific list.
     *
     * @param string $listName
     * @param string $action
     * @param array $item
     * @return void
     */
    public function saveListingItem(string $listName, string $action, array $item)
    {
    }

    /**
     * Imports items into a specific list from a .csv file.
     * Returns the nr. of items imported.
     *
     * @param string $listName
     * @param string $file
     * @param bool $replace
     * @return int|null
     */
    public function importListingItems(string $listName, string $file, bool $replace = true): ?int
    {
        return null;
    }

    /**
     * Exports items from a specific list into a .csv file.
     *
     * @param string $listName
     * @param int|null $itemId
     * @return string|null
     */
    public function exportListingItems(string $listName, int $itemId = null): ?string
    {
        return null;
    }

    /**
     * Gets module personalized configuration info like:
     *  - HTML to render
     *  - Styles it might have (.css format)
     *  - Scripts it might have (.js format)
     * @return array
     * @throws Exception
     */
    public function getPersonalizedConfig(): ?array
    {
        $parts = explode("/", MODULES_FOLDER);
        $modulesFolder = end($parts);
        $configFolder = $modulesFolder . "/" . $this->id . "/config/";

        if (!file_exists($configFolder)) return null;

        $contents = Utils::getDirectoryContents(ROOT_PATH . $configFolder);
        if (count(array_filter($contents, function ($file) { return $file["extension"] == ".html"; })) > 1)
            throw new Exception("Can't have more than one HTML configuration file for module '" . $this->id . "'.");

        return [
            "html" => file_get_contents(ROOT_PATH . $configFolder . "config.html"),
            "styles" => array_map(function ($file) use ($configFolder) {
                return API_URL . "/" . $configFolder . $file["name"];
            }, array_values(array_filter($contents, function ($file) { return $file["extension"] == ".css"; }))),
            "scripts" => array_map(function ($file) use ($configFolder) {
                return API_URL . "/" . $configFolder . $file["name"];
            }, array_values(array_filter($contents, function ($file) { return $file["extension"] == ".js"; })))
        ];
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
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates module parameters.
     *
     * @param $id
     * @param $name
     * @param $description
     * @param $type
     * @param $version
     * @param $projectCompatibility
     * @param $APICompatibility
     * @return void
     * @throws Exception
     */
    private static function validateModule($id, $name, $description, $type, $version, array $projectCompatibility, array $APICompatibility)
    {
        self::validateName($id);
        self::validateName($name);
        self::validateDescription($description);
        self::validateType($type);
        self::validateVersion($version);
        self::validateVersion($projectCompatibility["min"]);
        self::validateVersion($projectCompatibility["max"]);
        self::validateVersion($APICompatibility["min"]);
        self::validateVersion($APICompatibility["max"]);
    }

    /**
     * Validates module ID or name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Module name can't be null neither empty.");

        if (is_numeric($name))
            throw new Exception("Module name can't be composed of only numbers.");

        if (iconv_strlen($name) > 50)
            throw new Exception("Module name is too long: maximum of 50 characters.");
    }

    /**
     * Validates module description.
     *
     * @param $description
     * @return void
     * @throws Exception
     */
    private static function validateDescription($description)
    {
        if (!is_string($description) || empty($description))
            throw new Exception("Module description can't be null neither empty.");

        if (is_numeric($description))
            throw new Exception("Module description can't be composed of only numbers.");

        if (iconv_strlen($description) > 100)
            throw new Exception("Module description is too long: maximum of 100 characters.");
    }

    /**
     * Validates module type.
     *
     * @param $type
     * @return void
     * @throws Exception
     */
    private static function validateType($type)
    {
        if (!is_string($type) || empty($type))
            throw new Exception("Module type can't be null neither empty.");

        if (!ModuleType::exists($type))
            throw new Exception("Module type '" . $type . "' is not available.");
    }

    /**
     * Validates module's versions.
     *
     * @param $version
     * @return void
     * @throws Exception
     */
    private static function validateVersion($version)
    {
        if (!Utils::isValidVersion($version))
            throw new Exception("Version '" . $version . "' is not in a valid format.");

        if (iconv_strlen($version) > 10)
            throw new Exception("Version is too long: maximum of 10 characters.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Checks whether a module can be enabled/disabled.
     *
     * @param bool $enable
     * @param bool $throwErrors
     * @return bool
     * @throws Exception
     */
    public function canChangeState(bool $enable, bool $throwErrors = false): bool
    {
        $error = null;

        // Check course/module compatibility
        if ($this->course) {
            $compatibleModuleVersions = $this->course->getCompatibleModuleVersions($this->id);
            $moduleVersion = $this->getVersion();

            if (!(Utils::compareVersions($moduleVersion, $compatibleModuleVersions["min"]) >= 0 &&
                (is_null($compatibleModuleVersions["max"]) || Utils::compareVersions($moduleVersion, $compatibleModuleVersions["max"]) <= 0))) {

                if ($throwErrors) $error = "Course with ID = " . $this->course->getId() . " is not compatible with module '" . $this->id . "' v" . $moduleVersion . ".
                        Needs module version >= " . $compatibleModuleVersions["min"] . (!is_null($compatibleModuleVersions["max"]) ? " & <= " . $compatibleModuleVersions["max"] : "") . ".";
                else return false;
            }
        }

        // Check project compatibility
        $compatibleVersions = $this->getCompatibleVersions();
        if (!(Utils::compareVersions(PROJECT_VERSION, $compatibleVersions["project"]["min"]) >= 0 &&
            (is_null($compatibleVersions["project"]["max"]) || Utils::compareVersions(PROJECT_VERSION, $compatibleVersions["project"]["max"]) <= 0))) {

            if ($throwErrors) $error = "Module '" . $this->id . "' v" . $this->getVersion() . " is not compatible with project v" . PROJECT_VERSION . ".
                        Needs project version >= " . $compatibleVersions["project"]["min"] . (!is_null($compatibleVersions["project"]["max"]) ? " & <= " . $compatibleVersions["project"]["max"] : "") . ".";
            else return false;
        }

        // Check API compatibility
        if (!(Utils::compareVersions(API_VERSION, $compatibleVersions["api"]["min"]) >= 0 &&
            (is_null($compatibleVersions["api"]["max"]) || Utils::compareVersions(API_VERSION, $compatibleVersions["api"]["max"]) <= 0))) {

            if ($throwErrors) $error = "Module '" . $this->id . "' v" . $this->getVersion() . " is not compatible with API v" . API_VERSION . ".
                        Needs API version >= " . $compatibleVersions["api"]["min"] . (!is_null($compatibleVersions["api"]["max"]) ? " & <= " . $compatibleVersions["api"]["max"] : "") . ".";
            else return false;
        }

        // Check dependencies
        if ($enable) {
            // Check dependencies of module are enabled
            $hardDependenciesIDs = $this->getDependencies(DependencyMode::HARD, true);
            foreach ($hardDependenciesIDs as $dependencyID) {
                $depModule = $this->course->getModuleById($dependencyID);
                if (!$depModule->isEnabled()) {
                    if ($throwErrors) $error = "Can't enable module '" . $this->id . "' as its hard dependency '" . $dependencyID . "' is disabled.";
                    else return false;
                }
            }

        } else {
            // Check there's no modules depending on it
            $dependantsIDs = $this->getDependants(DependencyMode::HARD, true);
            foreach ($dependantsIDs as $dependantID) {
                $depModule = $this->course->getModuleById($dependantID);
                if ($depModule->isEnabled()) {
                    if ($throwErrors) $error = "Can't disable module '" . $this->id . "' as module '" . $dependantID . "' depends on it.";
                    else return false;
                }
            }
        }

        if ($error) throw new Exception($error);
        return true;
    }

    /**
     * Gets extra module information that complement's database
     * information, like its icon, dependencies, etc.
     *
     * @param array $moduleInfo
     * @param Course|null $course
     * @return void
     * @throws Exception
     */
    public static function getExtraInfo(array $moduleInfo, ?Course $course): array
    {
        $module = self::getModuleById($moduleInfo["id"], $course);
        $moduleInfo = array_merge($moduleInfo, $module->getData());
        $moduleInfo["icon"] = $module->getIcon();
        $moduleInfo["dependencies"] = $module->getDependencies();
        $moduleInfo["configurable"] = $module->isConfigurable();
        $moduleInfo["compatibility"] = [
            "project" => Utils::compareVersions(PROJECT_VERSION, $moduleInfo["minProjectVersion"]) >= 0 &&
                (is_null($moduleInfo["maxProjectVersion"]) || Utils::compareVersions(PROJECT_VERSION, $moduleInfo["maxProjectVersion"]) <= 0),
            "api" => Utils::compareVersions(API_VERSION, $moduleInfo["minAPIVersion"]) >= 0 &&
                (is_null($moduleInfo["maxAPIVersion"]) || Utils::compareVersions(PROJECT_VERSION, $moduleInfo["maxAPIVersion"]) <= 0)
        ];

        if ($course) {
            $moduleInfo["isEnabled"] = $module->isEnabled();
            $moduleInfo["canChangeState"] = $module->canChangeState(!$moduleInfo["isEnabled"]);
        }
        return $moduleInfo;
    }

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
