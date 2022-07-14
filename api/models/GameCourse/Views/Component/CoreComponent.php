<?php
namespace GameCourse\Views\Component;


use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Views\Category\Category;
use GameCourse\Views\ViewHandler;

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

    public function getPosition(): int
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
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_CORE_COMPONENT, $fieldValues, ["viewRoot" => $this->viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets core components in the system.
     *
     * @return array
     */
    public static function getComponents(): array
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
        // Add view tree of component
        $viewRoot = ViewHandler::insertViewTree($viewTree, 0);

        // Create new component
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
    public static function deleteComponen(int $viewRoot) {
        Core::database()->delete(self::TABLE_CORE_COMPONENT, ["viewRoot" => $viewRoot]);
    }

    /**
     * Checks whether core component exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("viewRoot"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a core component coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $component
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    public static function parse(array $component = null, $field = null, string $fieldName = null)
    {
        if ($component) {
            if (isset($component["viewRoot"])) $component["viewRoot"] = intval($component["viewRoot"]);
            if (isset($component["category"])) $component["category"] = intval($component["category"]);
            if (isset($component["position"])) $component["position"] = intval($component["position"]);
            return $component;

        } else {
            if ($fieldName == "viewRoot" || $fieldName == "category" || $fieldName == "position") return intval($field);
            return $field;
        }
    }
}
