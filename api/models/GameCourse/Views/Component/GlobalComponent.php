<?php
namespace GameCourse\Views\Component;


use GameCourse\Core\Core;
use GameCourse\User\User;
use GameCourse\Views\Category\Category;

/**
 * This is the Global Component model, which implements the necessary methods
 * to interact with global view components (shared by admins) in the MySQL database.
 */
class GlobalComponent extends Component
{
    const TABLE_GLOBAL_COMPONENT = 'component_global';


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

    public function getSharingUser(): User
    {
        return User::getUserById($this->getData("sharedBy"));
    }

    public function getSharingTimestamp(): string
    {
        return $this->getData("sharedTimestamp");
    }

    /**
     * Gets global component data from the database.
     *
     * @example getData() --> gets all global component data
     * @example getData("description") --> gets global component description
     * @example getData("description, category") --> gets global component description & category ID
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select(self::TABLE_GLOBAL_COMPONENT, ["viewRoot" => $this->viewRoot], $field);
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

    public function setOwner(int $userId)
    {
        $this->setData(["sharedBy" => $userId]);
    }

    public function setSharingTimestamp(string $timestamp)
    {
        $this->setData(["sharedTimestamp" => $timestamp]);
    }

    /**
     * Sets global component data on the database.
     *
     * @example setData(["description" => "New description"])
     * @example setData(["description" => "New description", "category" => 1])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_GLOBAL_COMPONENT, $fieldValues, ["viewRoot" => $this->viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets global components in the system.
     *
     * @param int|null $courseId
     * @return array
     */
    public static function getComponents(int $courseId = null): array
    {
        $components = Core::database()->selectMultiple(self::TABLE_GLOBAL_COMPONENT, [], "*", "viewRoot");
        foreach ($components as &$component) { $component = self::parse($component); }
        return $components;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------- Component Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a global component to the database.
     * Returns the newly created component.
     *
     * @param int $viewRoot
     * @param string|null $description
     * @param Category $category
     * @param User $owner
     * @return GlobalComponent
     */
    public static function addComponent(int $viewRoot, ?string $description, Category $category, User $owner): GlobalComponent
    {
        // Create new component
        Core::database()->insert(self::TABLE_GLOBAL_COMPONENT, [
            "viewRoot" => $viewRoot,
            "description" => $description,
            "category" => $category->getId(),
            "sharedBy" => $owner->getId()
        ]);
        return new GlobalComponent($viewRoot);
    }

    /**
     * Deletes a global component from the database.
     *
     * @param int $viewRoot
     * @return void
     */
    public static function deleteComponent(int $viewRoot) {
        Core::database()->delete(self::TABLE_GLOBAL_COMPONENT, ["viewRoot" => $viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a global component coming from the database to appropriate types.
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
            if (isset($component["sharedBy"])) $component["sharedBy"] = intval($component["sharedBy"]);
            return $component;

        } else {
            if ($fieldName == "viewRoot" || $fieldName == "category" || $fieldName == "sharedBy") return intval($field);
            return $field;
        }
    }
}
