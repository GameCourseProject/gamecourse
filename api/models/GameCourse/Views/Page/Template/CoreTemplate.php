<?php
namespace GameCourse\Views\Page\Template;


use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Views\Category\Category;
use GameCourse\Views\ViewHandler;
use PDOException;

/**
 * This is the Core Template model, which implements the necessary methods
 * to interact with core page templates (from system + modules) in the
 * MySQL database.
 */
class CoreTemplate extends Template
{
    const TABLE_CORE_TEMPLATE = 'template_core';


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getName(): string
    {
        return $this->getData("name");
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
     * Gets core template data from the database.
     *
     * @example getData() --> gets all core template data
     * @example getData("name") --> gets core template name
     * @example getData("name, category") --> gets core template name & category ID
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select(self::TABLE_CORE_TEMPLATE, ["viewRoot" => $this->viewRoot], $field);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
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
     * Sets core template data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "category" => 1])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_CORE_TEMPLATE, $fieldValues, ["viewRoot" => $this->viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets core templates in the system.
     *
     * @return array
     */
    public static function getTemplates(): array
    {
        $templates = Core::database()->selectMultiple(self::TABLE_CORE_TEMPLATE, [], "*", "viewRoot");
        foreach ($templates as &$template) { $template = self::parse($template); }
        return $templates;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Template Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a core template to the database.
     * Returns the newly created template.
     *
     * @param array $viewTree
     * @param string $name
     * @param int $categoryId
     * @param int $position
     * @param string|null $moduleId
     * @return CoreTemplate
     * @throws Exception
     */
    public static function addTemplate(array $viewTree, string $name, int $categoryId, int $position, string $moduleId = null): CoreTemplate
    {
        // Verify view tree only has system and/or module aspects
        try {
            ViewHandler::getAspectsInViewTree(null, $viewTree, 0);

        } catch (PDOException $e) {
            $error = $e->getMessage();
            preg_match("/Role with name '(.+)' doesn't exist/", $error, $matches);
            if (!empty($matches)) {
                $roleName = $matches[1];
                throw new Exception("Cannot use role with name '" . $roleName . "' as an aspect in a core template. 
                Only system and/or module aspects are allowed.");
            }
        }

        // Add view tree of template
        $viewRoot = ViewHandler::insertViewTree($viewTree, 0);

        // Create new template
        Core::database()->insert(self::TABLE_CORE_TEMPLATE, [
            "viewRoot" => $viewRoot,
            "name" => $name,
            "category" => $categoryId,
            "position" => $position,
            "module" => $moduleId
        ]);
        return new CoreTemplate($viewRoot);
    }

    /**
     * Deletes a core template from the database.
     *
     * @param int $viewRoot
     * @return void
     */
    public static function deleteTemplate(int $viewRoot) {
        ViewHandler::deleteViewTree($viewRoot);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a core template coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $template
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    public static function parse(array $template = null, $field = null, string $fieldName = null)
    {
        if ($template) {
            if (isset($template["viewRoot"])) $template["viewRoot"] = intval($template["viewRoot"]);
            if (isset($template["category"])) $template["category"] = intval($template["category"]);
            if (isset($template["position"])) $template["position"] = intval($template["position"]);
            return $template;

        } else {
            if ($fieldName == "viewRoot" || $fieldName == "category" || $fieldName == "position") return intval($field);
            return $field;
        }
    }
}
