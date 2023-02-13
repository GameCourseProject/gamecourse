<?php
namespace GameCourse\Views\ViewType;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;
use Utils\Utils;

/**
 * This is the ViewType model, which implements the necessary methods
 * to interact with view types in the MySQL database.
 */
abstract class ViewType
{
    const TABLE_VIEW_TYPE = "view_type";

    protected $id;


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): string
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->getData("description");
    }

    /**
     * Gets view type data from the database.
     *
     * @example getData() --> gets all view type data
     * @example getData("description") --> gets view type description
     * @example getData("id, description") --> gets view type ID & description
     *
     * @param string $field
     * @return mixed|void
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_VIEW_TYPE;
        $where = ["id" => $this->id];
        return Core::database()->select($table, $where, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setDescription(string $description)
    {
        $this->setData(["description" => $description]);
    }

    /**
     * Sets view type data on the database.
     *
     * @example setData(["description" => "New description"])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_VIEW_TYPE, $fieldValues, ["id" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Setup ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Registers view types available in the system.
     * This is only performed once during system setup.
     *
     * @return void
     * @throws Exception
     */
    public static function setupViewTypes()
    {
        // Get view types
        $viewTypesFolder = ROOT_PATH . "models/GameCourse/Views/ViewType/";
        $viewTypes = self::getViewTypesInFolder($viewTypesFolder, ["ViewType"]);

        // Add view types to database and initialize them
        foreach ($viewTypes as $viewType) {
            self::addViewType($viewType::ID, $viewType::DESCRIPTION);
            $viewType->init();
        }
    }

    /**
     * Gets all view types defined in a given folder.
     *
     * @param string $folder
     * @param array $ignore
     * @return array
     * @throws Exception
     */
    private static function getViewTypesInFolder(string $folder, array $ignore = []): array
    {
        $viewTypes = [];
        $files = Utils::getDirectoryContents($folder);
        foreach ($files as $file) {
            $fileName = substr($file["name"], 0, -4);
            if (!in_array($fileName, $ignore)) {
                $viewTypeClass = "\\GameCourse\\Views\\ViewType\\" . $fileName;
                $viewTypes[] = new $viewTypeClass();
            }
        }
        return $viewTypes;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function getViewTypeById(string $id): ?ViewType
    {
        $viewTypeClass = "\\GameCourse\\Views\\ViewType\\" . ucfirst($id);
        $viewType = new $viewTypeClass();
        if ($viewType->exists()) return $viewType;
        else return null;
    }

    public static function getViewTypes(bool $IDsOnly = false): array
    {
        $viewTypes = Core::database()->selectMultiple(self::TABLE_VIEW_TYPE, [], "*", "id");
        if ($IDsOnly) return array_column($viewTypes, "id");
        return $viewTypes;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------- View Type Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a view type to the database.
     * Returns the newly created view type.
     *
     * @param string $id
     * @param string $description
     * @return ViewType
     */
    public static function addViewType(string $id, string $description): ViewType
    {
        Core::database()->insert(self::TABLE_VIEW_TYPE, [
            "id" => $id,
            "description" => $description
        ]);
        return self::getViewTypeById($id);
    }

    /**
     * Deletes a view type from the database.
     *
     * @param string $id
     * @return void
     */
    public static function deleteViewType(string $id)
    {
        Core::database()->delete(self::TABLE_VIEW_TYPE, ["id" => $id]);
    }

    /**
     * Checks whether view type exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Actions --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Actions to be performed when adding the view type.
     */
    abstract function init();

    /**
     * Creates necessary tables for view type information.
     *
     * @return void
     */
    protected function initDatabase()
    {
    }

    /**
     * Actions to be performed when removing the view type.
     */
    abstract function end();

    /**
     * Deletes view type tables.
     *
     * @return void
     */
    protected function cleanDatabase()
    {
        Core::database()->delete(ViewHandler::TABLE_VIEW, ["type" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a view of a specific type from the database.
     *
     * @param int $viewId
     * @return array
     */
    abstract function get(int $viewId): array;

    /**
     * Inserts a new view of a specific type in the database.
     *
     * @param array $view
     * @return void
     */
    abstract function insert(array $view);

    /**
     * Updates an existing view of a specific type in the database.
     *
     * @param array $view
     * @return void
     */
    abstract function update(array $view);

    /**
     * Deletes an existing view of a specific type from the database.
     *
     * @param int $viewId
     * @return void
     */
    abstract function delete(int $viewId);

    /**
     * Builds a view of a specific type.
     * Option to build for a specific set of aspects
     *
     * @param array $view
     * @param array|null $sortedAspects
     * @return void
     */
    abstract function build(array &$view, array $sortedAspects = null);

    /**
     * Translates a view into logs.
     *
     * @param array $view
     * @param array $logs
     * @param array $views
     * @param array|null $parent
     * @return void
     */
    abstract function translate(array $view, array &$logs, array &$views, array $parent = null);

    /**
     * Traverses a view of a specific type and performs a given function.
     *
     * @param array $view
     * @param $func
     * @param $parent
     * @param ...$data
     * @return mixed
     */
    abstract function traverse(array &$view, $func, &$parent, &...$data);


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Dictionary -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Compiles a view of a specific type.
     * If view type has parameters that can contain expressions,
     * those parameters need to be compiled.
     *
     * @param array $view
     * @return void
     */
    abstract function compile(array &$view);

    /**
     * Evaluates a view of a specific type.
     * If view type has parameters that can contain expressions,
     * those parameters need to be evaluated.
     *
     * @param array $view
     * @param EvaluateVisitor $visitor
     * @return void
     */
    abstract function evaluate(array &$view, EvaluateVisitor $visitor);


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a view of a specific type coming from the database
     * to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $view
     * @param null $field
     * @param string|null $fieldName
     */
    abstract function parse(array $view = null, $field = null, string $fieldName = null);
}
