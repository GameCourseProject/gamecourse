<?php
namespace GameCourse\Views\Page\Template;


use Exception;
use GameCourse\Core\Core;
use GameCourse\User\User;
use GameCourse\Views\Category\Category;
use GameCourse\Views\ViewHandler;
use PDOException;

/**
 * This is the Global Template model, which implements the necessary methods
 * to interact with global page templates (shared by admins) in the MySQL database.
 */
class GlobalTemplate extends Template
{
    const TABLE_GLOBAL_TEMPLATE = 'template_global';


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

    public function getSharingUser(): User
    {
        return User::getUserById($this->getData("sharedBy"));
    }

    public function getSharingTimestamp(): string
    {
        return $this->getData("sharedTimestamp");
    }

    /**
     * Gets global template data from the database.
     *
     * @example getData() --> gets all global template data
     * @example getData("name") --> gets global template name
     * @example getData("name, category") --> gets global template name & category ID
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select(self::TABLE_GLOBAL_TEMPLATE, ["viewRoot" => $this->viewRoot], $field);
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

    public function setOwner(int $userId)
    {
        $this->setData(["sharedBy" => $userId]);
    }

    public function setSharingTimestamp(string $timestamp)
    {
        $this->setData(["sharedTimestamp" => $timestamp]);
    }

    /**
     * Sets global template data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "category" => 1])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        // Update data
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_GLOBAL_TEMPLATE, $fieldValues, ["viewRoot" => $this->viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets global templates in the system.
     *
     * @return array
     */
    public static function getTemplates(): array
    {
        $templates = Core::database()->selectMultiple(self::TABLE_GLOBAL_TEMPLATE, [], "*", "viewRoot");
        foreach ($templates as &$template) { $template = self::parse($template); }
        return $templates;
    }

    /*** ---------------------------------------------------- ***/
    /*** -------------- Component Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a global template to the database.
     * Returns the newly created template.
     *
     * @param int $viewRoot
     * @param string $name
     * @param Category $category
     * @param User $owner
     * @return GlobalTemplate
     * @throws Exception
     */
    public static function addTemplate(int $viewRoot, string $name, Category $category, User $owner): GlobalTemplate
    {
        // Verify view tree only has system and/or module aspects
        try {
            ViewHandler::getAspectsInViewTree($viewRoot);

        } catch (PDOException $e) {
            $error = $e->getMessage();
            preg_match("/Role with name '(.+)' doesn't exist/", $error, $matches);
            if (!empty($matches)) {
                $roleName = $matches[1];
                throw new Exception("Cannot use role with name '" . $roleName . "' as an aspect in a global template. 
                Only system and/or module aspects are allowed.");
            }
        }

        // Create new template
        Core::database()->insert(self::TABLE_GLOBAL_TEMPLATE, [
            "viewRoot" => $viewRoot,
            "name" => $name,
            "category" => $category->getId(),
            "sharedBy" => $owner->getId()
        ]);
        return new GlobalTemplate($viewRoot);
    }

    /**
     * Deletes a global template from the database.
     *
     * @param int $viewRoot
     * @return void
     */
    public static function deleteTemplate(int $viewRoot) {
        Core::database()->delete(self::TABLE_GLOBAL_TEMPLATE, ["viewRoot" => $viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a global template coming from the database to appropriate types.
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
            if (isset($template["sharedBy"])) $template["sharedBy"] = intval($template["sharedBy"]);
            return $template;

        } else {
            if ($fieldName == "viewRoot" || $fieldName == "category" || $fieldName == "sharedBy")
                return is_numeric($field) ? intval($field) : $field;
            return $field;
        }
    }
}
