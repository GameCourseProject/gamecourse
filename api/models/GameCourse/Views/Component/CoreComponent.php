<?php
namespace GameCourse\Views\Component;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Views\Category\Category;
use GameCourse\Views\ViewHandler;
use PDOException;
use Utils\Utils;

/**
 * This is the Core Component model, which implements the necessary methods
 * to interact with core view components (from system + modules) in the
 * MySQL database.
 */
class CoreComponent extends Component
{
    const TABLE_CORE_COMPONENT = 'component_core';


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getDescription(): ?string
    {
        return $this->getData("description");
    }

    public function getCategory(): Category
    {
        return Category::getCategoryById($this->getData("category"));
    }

    public function getPosition(): ?int
    {
        return $this->getData("position");
    }

    public function getModule(): ?Module
    {
        return Module::getModuleById($this->getData("module"), null);
    }

    /**
     * Gets core component data from the database.
     *
     * @example getData() --> gets all core component data
     * @example getData("description") --> gets core component description
     * @example getData("description, category") --> gets core component description & category ID
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select(self::TABLE_CORE_COMPONENT, ["viewRoot" => $this->viewRoot], $field);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setDescription(?string $description)
    {
        $this->setData(["description" => $description]);
    }

    public function setCategory(Category $category)
    {
        $this->setData(["category" => $category->getId()]);
    }

    public function setPosition(int $position)
    {
        $this->setData(["position" => $position]);
    }

    public function setModule(?Module $module)
    {
        $this->setData(["module" => $module ? $module->getId() : null]);
    }

    /**
     * Sets core component data on the database.
     *
     * @example setData(["description" => "New description"])
     * @example setData(["description" => "New description", "category" => 1])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        // Trim values
        self::trim($fieldValues);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_CORE_COMPONENT, $fieldValues, ["viewRoot" => $this->viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets core components in the system.
     *
     * @param int|null $courseId
     * @return array
     */
    public static function getComponents(int $courseId = null): array
    {
        $components = Core::database()->selectMultiple(self::TABLE_CORE_COMPONENT, [], "*", "viewRoot");
        foreach ($components as &$component) { $component = self::parse($component); }
        return $components;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------- Component Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a core component to the database.
     * Returns the newly created component.
     *
     * @param array $viewTree
     * @param string|null $description
     * @param int $categoryId
     * @param int $position
     * @param string|null $moduleId
     * @return CoreComponent
     * @throws Exception
     */
    public static function addComponent(array $viewTree, ?string $description, int $categoryId, int $position, string $moduleId = null): CoreComponent
    {
        // Verify view tree only has system and/or module aspects
        try {
            ViewHandler::getAspectsInViewTree(null, $viewTree, 0);

        } catch (PDOException $e) {
            $error = $e->getMessage();
            preg_match("/Role with name '(.+)' doesn't exist/", $error, $matches);
            if (!empty($matches)) {
                $roleName = $matches[1];
                throw new Exception("Cannot use role with name '" . $roleName . "' as an aspect in a core component. 
                Only system and/or module aspects are allowed.");
            }
        }

        // Add view tree of component
        $viewRoot = ViewHandler::insertViewTree($viewTree, 0);

        // Create new component
        self::trim($description);
        Core::database()->insert(self::TABLE_CORE_COMPONENT, [
            "viewRoot" => $viewRoot,
            "description" => $description,
            "category" => $categoryId,
            "position" => $position,
            "module" => $moduleId
        ]);
        return new CoreComponent($viewRoot);
    }

    /**
     * Deletes a core component from the database.
     *
     * @param int $viewRoot
     * @return void
     */
    public static function deleteComponent(int $viewRoot) {
        ViewHandler::deleteViewTree($viewRoot);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a core component coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $component
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $component = null, $field = null, string $fieldName = null)
    {
        $intValues = ["viewRoot", "category", "position"];

        return Utils::parse(["int" => $intValues], $component, $field, $fieldName);
    }

    /**
     * Trims core component parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["description"];
        Utils::trim($params, ...$values);
    }
}
