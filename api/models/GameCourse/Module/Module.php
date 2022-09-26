<?php
namespace GameCourse\Module;

use Error;
use Event\Event;
use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\AutoGame\RuleSystem\Section;
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
        return API_URL . "/" . Utils::getDirectoryName(MODULES_FOLDER) . "/" . $this->id. "/icon.svg";
    }

    public function getIconSVG(): string
    {
        return file_get_contents(MODULES_FOLDER . "/" . $this->id . "/icon.svg");
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
     * NOTE: only works with single files or one-level directories
     *
     * @return array
     * @throws Exception
     */
    public function getResources(): array
    {
        $resources = [];
        foreach ($this::RESOURCES as $resource) {
            $path = MODULES_FOLDER . "/" . $this->id . "/" . $resource;
            $realPath = API_URL . "/" . Utils::getDirectoryName(MODULES_FOLDER) . "/" . $this->id . "/" . $resource;

            if (!file_exists($path))
                throw new Exception("Resource '" . $resource . "' doesn't exist in module '" . $this->id . "'.");

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
        $modulesFolders = array_filter(Utils::getDirectoryContents(MODULES_FOLDER), function ($item) { return $item["type"] == "folder"; });
        foreach ($modulesFolders as $folder) {
            $moduleId = $folder["name"];
            $mainFile = MODULES_FOLDER . "/" . $moduleId . "/" . $moduleId. ".php";

            if (!file_exists($mainFile))
                throw new Exception("Can't find main file for module '" . $moduleId . "'.");

            $moduleClass = "\\GameCourse\\Module\\" . $moduleId . "\\" . $moduleId;
            $module = new $moduleClass(null);

            self::addModule($moduleId, $module::NAME, $module::DESCRIPTION, $module::TYPE, $module::VERSION,
                $module::PROJECT_VERSION, $module::API_VERSION, $module::DEPENDENCIES);
        }
        Core::database()->setForeignKeyChecks(true);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a module by its ID.
     * Returns null if module doesn't exist.
     *
     * @param string $id
     * @param Course|null $course
     * @return Module|null
     */
    public static function getModuleById(string $id, ?Course $course): ?Module
    {
        try {
            $moduleClass = "\\GameCourse\\Module\\" . $id . "\\" . $id;
            $module = new $moduleClass($course);
            if ($module->exists()) return $module;
            else return null;

        } catch (Error $error) {
            return null;
        }
    }

    /**
     * Gets modules available in the system.
     * Option to retrieve module IDs only.
     *
     * @param bool $IDsOnly
     * @return array
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

    /**
     * Gets all modules available in a course.
     * Option for 'enabled' and to retrieve module IDs only.
     *
     * @param int $courseId
     * @param bool|null $enabled
     * @param bool $IDsOnly
     * @return array
     * @throws Exception
     */
    public static function getModulesInCourse(int $courseId, ?bool $enabled = null, bool $IDsOnly = false): array
    {
        $table = self::TABLE_MODULE . " m JOIN " . self::TABLE_COURSE_MODULE . " cm on cm.module=m.id";
        $where = ["cm.course" => $courseId];
        if ($enabled !== null) $where["cm.isEnabled"] = $enabled;
        $modules = Core::database()->selectMultiple($table, $where, "m.*, cm.isEnabled, cm.minModuleVersion, cm.maxModuleVersion", "m.id");
        if ($IDsOnly) return array_column($modules, "id");
        foreach ($modules as &$moduleInfo) {
            $moduleInfo = self::getExtraInfo($moduleInfo, new Course($courseId));
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
        self::trim($id, $name, $description, $type, $version);
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
            $dependencyInfo = self::getExtraInfo($dependencyInfo, $this->course, false);
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

    /**
     * Sets module dependencies.
     *
     * @param array $dependencies
     * @return void
     */
    public function setDependencies(array $dependencies)
    {
        // Remove all module dependencies
        Core::database()->delete(self::TABLE_MODULE_DEPENDENCY, ["module" => $this->id]);

        // Add new dependencies
        foreach ($dependencies as $dependency) {
            $this->addDependency($dependency);
        }
    }

    /**
     * Adds a new dependency to module.
     *
     * @param array $dependency
     * @return void
     */
    public function addDependency(array $dependency)
    {
        if (!$this->hasDependency($dependency["id"])) {
            Core::database()->insert(self::TABLE_MODULE_DEPENDENCY, [
                "module" => $this->id,
                "dependency" => $dependency["id"],
                "minDependencyVersion" => $dependency["minVersion"],
                "maxDependencyVersion" => $dependency["maxVersion"],
                "mode" => $dependency["mode"]
            ]);
        }
    }

    /**
     * Removes dependency from module.
     *
     * @param string $dependencyId
     * @return void
     */
    public function removeDependency(string $dependencyId)
    {
        Core::database()->delete(self::TABLE_MODULE_DEPENDENCY, ["module" => $this->id, "dependency" => $dependencyId]);
    }

    /**
     * Checks whether module has a given dependency.
     *
     * @param string $dependencyId
     * @return bool
     */
    public function hasDependency(string $dependencyId): bool
    {
        return !empty(Core::database()->select(self::TABLE_MODULE_DEPENDENCY, ["module" => $this->id, "dependency" => $dependencyId]));
    }

    /**
     * Checks if a given dependency exists and is enabled in the course.
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

    // Initialization

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
     *
     * @return void
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
     * Creates Rule System section for module rules.
     *
     * @return void
     * @throws Exception
     */
    protected function initRules()
    {
        RuleSystem::addSection($this->course->getId(), $this::RULE_SECTION, 0, $this->id);
    }


    // Copying

    abstract function copyTo(Course $copyTo);


    // Cleaning

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
        $sql = file_get_contents(MODULES_FOLDER . "/" . $this->id . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            Core::database()->delete($table, ["course" => $this->course->getId()]);
        }
    }

    protected function removeTemplates()
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

    /**
     * Removes Rule System section and rules for module.
     *
     * @return void
     * @throws Exception
     */
    protected function removeRules()
    {
        $sectionId = Section::getSectionByName($this->course->getId(), $this::RULE_SECTION)->getId();
        RuleSystem::deleteSection($sectionId);
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
     * Gets general inputs to show on configuration page.
     *
     * @return array
     */
    public function getGeneralInputs(): ?array
    {
        return null;
    }

    /**
     * Updates general inputs in a given section.
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
     *  - parent?: list parent ID
     *  - listActions?: actions available for list (check Config/Action.php for more info)
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
     * @param array $items
     * @return array|null
     */
    public function exportListingItems(string $listName, array $items): ?array
    {
        return null;
    }

    /**
     * Gets module personalized configuration ID.
     *
     * @return string
     */
    public function getPersonalizedConfig(): ?string
    {
        return null;
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Rule System -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new module item rule to the Rule System.
     * Returns the newly created rule.
     *
     * @param int|null $position
     * @param mixed ...$args
     * @return Rule
     * @throws Exception
     */
    public function addRuleOfItem(int $position = null, ...$args): Rule
    {
        // Generate rule params
        $params = $this->generateRuleParams(...$args);
        $name = $params["name"];
        $tags = key_exists("tags", $params) ? $params["tags"] : [];
        $description = key_exists("description", $params) ? $params["description"] : null;
        $when = $params["when"];
        $then = $params["then"];

        // Add rule
        $section = Section::getSectionByName($this->course->getId(), $this::RULE_SECTION);
        return $section->addRule($name, $description, $when, $then, $position, true, $tags);
    }

    /**
     * Updates rule of a module item.
     *
     * @param int $ruleId
     * @param int|null $position
     * @param bool $isActive
     * @param mixed ...$args
     * @return void
     * @throws Exception
     */
    public function updateRuleOfItem(int $ruleId, int $position = null, bool $isActive = true, ...$args)
    {
        // Re-generate rule params
        $params = $this->generateRuleParams(...$args);
        $name = $params["name"];
        $tags = key_exists("tags", $params) ? $params["tags"] : [];
        $description = key_exists("description", $params) ? $params["description"] : null;
        $when = $params["when"];
        $then = $params["then"];

        // Update rule
        $rule = Rule::getRuleById($ruleId);
        $rule->editRule($name, $description, $when, $then, $position, $isActive, $tags);
    }

    /**
     * Deletes rule of a module item.
     *
     * @param int $ruleId
     * @return void
     * @throws Exception
     */
    public function deleteRuleOfItem(int $ruleId)
    {
        // Delete rule
        $section = Section::getSectionByName($this->course->getId(), $this::RULE_SECTION);
        $section->removeRule($ruleId);
    }

    /**
     * Generates rule parameters for a module item.
     *
     * @param mixed ...$args
     * @return array
     */
    protected function generateRuleParams(...$args): array
    {
        return [];
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Module Data ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets module data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param bool $fullPath
     * @return string
     */
    public function getDataFolder(bool $fullPath = true): string
    {
        if ($fullPath) return $this->getCourse()->getDataFolder($fullPath) . "/" . $this::DATA_FOLDER;
        else return $this::DATA_FOLDER;
    }

    /**
     * Gets module data folder contents.
     *
     * @return array
     * @throws Exception
     */
    public function getDataFolderContents(): array
    {
        return Utils::getDirectoryContents($this->getDataFolder());
    }

    /**
     * Creates a data folder for a given module. If folder exists, it
     * will delete its contents.
     *
     * @return string
     * @throws Exception
     */
    public function createDataFolder(): string
    {
        $dataFolder = $this->getDataFolder();
        if (file_exists($dataFolder)) self::removeDataFolder();
        mkdir($dataFolder, 0777, true);
        return $dataFolder;
    }

    /**
     * Deletes a module's data folder.
     *
     * @return void
     * @throws Exception
     */
    public function removeDataFolder()
    {
        $dataFolder = $this->getDataFolder();
        if (file_exists($dataFolder)) Utils::deleteDirectory($dataFolder);
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
        // NOTE: if module has personalized config, need to 1st add
        //       it manually, re-build frontend & deploy new app

        $moduleId = "";
        // TODO: upload module files
        // TODO: upload module tests

        // TODO: update composer for controllers
        $hasControllers = false;
        if ($hasControllers) self::updateAutoload(true, "controllers", $moduleId);

        // TODO: update composer for dictionary
        $hasDictionary = false;
        if ($hasDictionary) self::updateAutoload(true, "dictionary", $moduleId);
    }

    public static function uninstallModule()
    {
        // NOTE: if module has personalized config, need to remove
        //       it manually, re-build frontend & deploy new app

        $moduleId = "";
        // TODO: remove module files (ver function deleteModule() on Module.php
        // TODO: remove module tests

        // TODO: update composer for controllers
        $hasControllers = false;
        if ($hasControllers) self::updateAutoload(false, "controllers", $moduleId);

        // TODO: update composer for dictionary
        $hasDictionary = false;
        if ($hasDictionary) self::updateAutoload(false, "dictionary", $moduleId);
    }

    /**
     * Updates modules composer file by adding/removing autoload
     * information on installation/uninstallation.
     *
     * @param bool $install
     * @param string $category
     * @param string $moduleId
     * @return void
     * @throws Exception
     */
    private static function updateAutoload(bool $install, string $category, string $moduleId)
    {
        $composerFile = ROOT_PATH . "modules/composer.json";
        $contents = file_get_contents($composerFile);

        if ($category === "controllers") $namespace = "API\\\\";
        else if ($category === "dictionary") $namespace = "GameCourse\\\\Views\\\\Dictionary\\\\";
        else throw new Exception("Can't update modules autoload: category '" . $category . "' not found.");

        $item = "\"" . $moduleId . "/" . $category . "\"";
        if ($install) { // install
            $pos = strpos($contents, $namespace) + strlen($namespace) + strlen(": [") + 1;
            $isEmpty = $contents[$pos] === "]";
            $contents = substr_replace($contents, $item . (!$isEmpty ? ", " : ""), $pos, 0);

        } else { // uninstall
            $isBegin = !!strpos($contents, "[" . $item);
            $isEnd = !!strpos($contents, $item . "]");
            $search = $isBegin ? ($isEnd ? $item : $item . ", ") : ", " . $item;
            $contents = str_replace($search, "", $contents);
        }

        file_put_contents($composerFile, $contents);
        exec("composer dump-autoload");
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
     * @param array $projectCompatibility
     * @param array $APICompatibility
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

        if (iconv_strlen($description) > 150)
            throw new Exception("Module description is too long: maximum of 150 characters.");
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
    private function canChangeState(bool $enable, bool $throwErrors = false): bool
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
     * @param bool $dependencies
     * @return void
     * @throws Exception
     */
    public static function getExtraInfo(array $moduleInfo, ?Course $course, bool $dependencies = true): array
    {
        $module = self::getModuleById($moduleInfo["id"], $course);
        $moduleInfo = array_merge($moduleInfo, $module->getData());
        $moduleInfo["icon"] = $module->getIcon();
        $moduleInfo["iconSVG"] = $module->getIconSVG();
        if ($dependencies) $moduleInfo["dependencies"] = $module->getDependencies();
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
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $module = null, $field = null, string $fieldName = null)
    {
        $boolValues = ["isEnabled"];

        return Utils::parse(["bool" => $boolValues], $module, $field, $fieldName);
    }

    /**
     * Trims module parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["id", "name", "description", "type", "version", "minProjectVersion", "maxProjectVersion", "minAPIVersion", "maxAPIVersion"];
        Utils::trim($params, ...$values);
    }
}
