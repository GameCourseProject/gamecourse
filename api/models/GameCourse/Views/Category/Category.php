<?php
namespace GameCourse\Views\Category;

use GameCourse\Core\Core;

/**
 * This is the Category model, which implements the necessary methods
 * to interact with view editor categories in the MySQL database.
 */
class Category
{
    const TABLE_CATEGORY = 'view_category';
    const TABLE_CATEGORY_ORDER = 'view_category_order';

    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getParent(): ?int
    {
        return $this->getData("parent");
    }

    public function getPosition(): ?int
    {
        return $this->getData("position");
    }

    /**
     * Gets category data from the database.
     *
     * @example getData() --> gets all category data
     * @example getData("name") --> gets category name
     * @example getData("name, parent") --> gets category name & parent
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_CATEGORY . " c LEFT JOIN " . self::TABLE_CATEGORY_ORDER . " co on c.id=co.child";
        $where = ["c.id" => $this->id];
        if ($field == "*") $fields = "c.*, co.parent, co.position";
        else $fields = str_replace("id", "c.id", $field);
        $data = Core::database()->select($table, $where, $fields);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    public function setParent(?int $parent)
    {
        $this->setData(["parent" => $parent]);
    }

    public function setPosition(int $position)
    {
        $this->setData(["position" => $position]);
    }

    /**
     * Sets category data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "position" => 1])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        $orderValues = []; // values that need to go to 'view_category_order' table
        if (key_exists("parent", $fieldValues)) {
            $orderValues["parent"] = $fieldValues["parent"];
            unset($fieldValues["parent"]);
        }
        if (key_exists("position", $fieldValues)) {
            $orderValues["position"] = $fieldValues["position"];
            unset($fieldValues["position"]);
        }

        if (count($orderValues) != 0) Core::database()->update(self::TABLE_CATEGORY_ORDER, $orderValues, ["child" => $this->id]);
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_CATEGORY, $fieldValues, ["id" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a view editor category by its ID.
     * Returns null if category doesn't exist.
     *
     * @param int $id
     * @return Category|null
     */
    public static function getCategoryById(int $id): ?Category
    {
        $category = new Category($id);
        if ($category->exists()) return $category;
        else return null;
    }

    /**
     * Gets view editor categories in the system.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        $categories = Core::database()->selectMultiple(
            self::TABLE_CATEGORY . " c JOIN " . self::TABLE_CATEGORY_ORDER . " co on c.id = co.child",
            [],
            "c.*, co.parent, co.position",
            "parent, position"
        );
        foreach ($categories as &$category) { $category = self::parse($category); }
        return $categories;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------- Category Manipulation --------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a category to the database.
     * Returns the newly created category.
     *
     * @param string $name
     * @param int|null $parent
     * @param int $position
     * @return Category
     */
    public static function addCategory(string $name, ?int $parent, int $position): Category
    {
        $id = Core::database()->insert(self::TABLE_CATEGORY, ["name" => $name]);
        Core::database()->insert(self::TABLE_CATEGORY_ORDER, [
            "parent" => $parent,
            "child" => $id,
            "position" => $position
        ]);
        return new Category($id);
    }

    /**
     * Deletes a category from the database.
     *
     * @param int $id
     * @return void
     */
    public static function deleteCategory(int $id) {
        Core::database()->delete(self::TABLE_CATEGORY, ["id" => $id]);
    }

    /**
     * Checks whether category exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a user coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $category
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    public static function parse(array $category = null, $field = null, string $fieldName = null)
    {
        if ($category) {
            if (isset($category["id"])) $category["id"] = intval($category["id"]);
            if (isset($category["parent"])) $category["parent"] = intval($category["parent"]);
            if (isset($category["position"])) $category["position"] = intval($category["position"]);
            return $category;

        } else {
            if ($fieldName == "id" || $fieldName == "parent" || $fieldName == "child" || $fieldName == "position") return intval($field);
            return $field;
        }
    }
}
