<?php
namespace GameCourse\Views\Component;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Views\Category\Category;
use GameCourse\Views\ViewHandler;
use Utils\Utils;

/**
 * This is the Core Component model, which implements the necessary methods
 * to interact with core view components (from system + modules) in the
 * MySQL database.
 */
class CoreComponent extends Component
{
    const TABLE_COMPONENT = 'component_core';


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
        $moduleId = $this->getData("module");
        if ($moduleId) return Module::getModuleById($moduleId, null);
        return null;
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
            Core::database()->update(self::TABLE_COMPONENT, $fieldValues, ["id" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets core components in the system.
     * Options for a specific category or module.
     *
     * @param int|null $categoryId
     * @param string|null $moduleId
     * @return array
     */
    public static function getComponents(int $categoryId = null, string $moduleId = null): array
    {
        $where = [];
        if ($categoryId) $where[] = ["category" => $categoryId];
        if ($moduleId) $where[] = ["module" => $moduleId];

        $components = Core::database()->selectMultiple(self::TABLE_COMPONENT, $where, "*", "category, position");
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
    public static function addComponent(array $viewTree, ?string $description, int $categoryId, int $position,
                                        string $moduleId = null): CoreComponent
    {
        // Verify view tree only has system and/or module aspects
        try {
            // NOTE: will throw an exception if aspect not found
            ViewHandler::getAspectsInViewTree(null, $viewTree, 0);

        } catch (Exception $e) {
            $error = $e->getMessage();
            preg_match("/Role with name '(.+)' doesn't exist/", $error, $matches);
            if (!empty($matches)) {
                $roleName = $matches[1];
                throw new Exception("Cannot use role with name '" . $roleName . "' as an aspect in a component. 
                Only system and/or module aspects are allowed.");
            }
        }

        // Add view tree of component
        $viewRoot = ViewHandler::insertViewTree($viewTree, 0);

        // Create new component
        self::trim($description);
        $id = Core::database()->insert(self::TABLE_COMPONENT, [
            "viewRoot" => $viewRoot,
            "description" => $description,
            "category" => $categoryId,
            "module" => $moduleId
        ]);
        Utils::updateItemPosition(null, $position, self::TABLE_COMPONENT, "position", $id, self::getComponents($categoryId));

        return new CoreComponent($id);
    }

    /**
     * Deletes a core component from the database.
     * Option to keep views linked (created by reference) or delete
     * them as well.
     *
     * @param int $id
     * @param bool $keepViewsLinked
     * @return void
     */
    public static function deleteComponent(int $id, bool $keepViewsLinked = true)
    {
        parent::deleteComponent($id, $keepViewsLinked);
        Core::database()->delete(self::TABLE_COMPONENT, ["id" => $id]);
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
     * @return mixed
     */
    public static function parse(array $component = null, $field = null, string $fieldName = null)
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
