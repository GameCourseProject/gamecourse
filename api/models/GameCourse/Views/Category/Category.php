<?php
namespace GameCourse\Views\Category;

use GameCourse\Core\Core;
use Utils\Utils;

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
    /*** ----------------------- Setup ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Registers view categories available in the system.
     * This is only performed once during system setup.
     *
     * @return void
     * @throws Exception
     */
    public static function setupViewCategories()
    {
        $categories = [
            ["id" => 1, "name" => "Modules"],
            ["id" => 2, "name" => "Components"],
            ["id" => 3, "name" => "Titles"],
            ["id" => 4, "name" => "Paragraphs"],
            ["id" => 5, "name" => "Styled"],
            ["id" => 6, "name" => "Solid"],
            ["id" => 7, "name" => "Outlined"],
            ["id" => 8, "name" => "With Icon"],
            ["id" => 9, "name" => "Rounded"],
            ["id" => 10, "name" => "Squared"],
            ["id" => 11, "name" => "With Footers"],
            ["id" => 12, "name" => "With Column Filtering"],
            ["id" => 13, "name" => "Bar"],
            ["id" => 14, "name" => "Combo"],
            ["id" => 15, "name" => "Line"],
            ["id" => 16, "name" => "Progress"],
            ["id" => 17, "name" => "Radar"],
            ["id" => 18, "name" => "Pie"],
            ["id" => 19, "name" => "Non-Styled"]
        ];

        $sql = "INSERT INTO " . self::TABLE_CATEGORY . " (id, name) VALUES ";
        $values = [];

        foreach ($categories as $category) {
            $values[] = "(" . $category["id"] . ", '" . $category["name"] . "')";
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
        }
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
        // Trim values
        self::trim($fieldValues);

        $orderValues = []; // values that need to go to 'view_category_order' table
        if (key_exists("parent", $fieldValues)) {
            $orderValues["parent"] = $fieldValues["parent"];
            unset($fieldValues["parent"]);
        }
        if (key_exists("position", $fieldValues)) {
            $orderValues["position"] = $fieldValues["position"];
            unset($fieldValues["position"]);
        }

        // Update data
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
        self::trim($name);
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
     * Parses a category coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $category
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $category = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "parent", "child", "position"];

        return Utils::parse(["int" => $intValues], $category, $field, $fieldName);
    }

    /**
     * Trims category parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name"];
        Utils::trim($params, ...$values);
    }
}
