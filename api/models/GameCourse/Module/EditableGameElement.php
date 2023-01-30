<?php
namespace GameCourse\EditableGameElement;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use Utils\Utils;

class EditableGameElement
{
    const TABLE_EDITABLE_GAME_ELEMENT = "editable_game_element";

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

    public function getCourse(): int
    {
        return $this->getData("course");
    }

    public function getModule(): string
    {
        return $this->getData("module");
    }

    public function isEditable(): bool
    {
        return $this->getData("isEditable");
    }

    public function nDays(): int{
        return $this->getData("nDays");
    }

    public function notify(): bool{
        return $this->getData("notify");
    }

    /**
     * Gets editableGameElement data from the database
     *
     * @example getData --> gets all the editableGameElement data
     * @example getData("module") --> gets module id
     *
     * @param string $field
     * @return int|null|string|bool
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_EDITABLE_GAME_ELEMENT;
        $where = ["id" => $this->id];
        $res = Core::database()->select($table, $where, $field);
        return is_array($res) ? self::parse($res) : self::parse(null, $res, $field);
    }

    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setEditable(bool $isEditable){
        $this->setData(["isEditable" => +$isEditable]);
    }

    /**
     * @throws Exception
     */
    public function setNDays(int $nDays){
        $this->setData(["nDays" => $nDays]);
    }

    /**
     * @throws Exception
     */
    public function setNotify(bool $notify){
        $this->setData(["notify" => $notify]);
    }

    /**
     * Sets editableGameElement data on the dabase.
     *
     * @example setData(["nDays" => "new nDays"])
     * @example setData(["isEditable" => "new isEditable", "nDays" => "new nDays"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    // TODO
    public function setData(array $fieldValues){

        // Trim values (not needed for now -- all ints and bools)
        self::trim($fieldValues);

        // Validate data
        if (key_exists("isEditable", $fieldValues)){
            $newStatus = $fieldValues["isEditable"];
            $oldStatus = $this->isEditable();
        }
        if (key_exists("nDays", $fieldValues)){
            $newNDays = $fieldValues["nDays"];
            $oldNDays = $this->nDays();
        }
        if (key_exists("notify", $fieldValues)){
            $newNotify = $fieldValues["notify"];
            $oldNotify = $this->notify();
        }

        // Update values
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_EDITABLE_GAME_ELEMENT, $fieldValues, ["id" => $this->id]);

        // Additional actions TODO
    }

    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a editableGameElements in the system given an id
     *
     * @param int $id
     * @return EditableGameElement
     */
    public static function getEditableGameElementById(int $id): ?EditableGameElement {
        $editableGameElement = new EditableGameElement($id);
        if ($editableGameElement->exists()) return $editableGameElement;
        else return null;
    }

    /**
     * Gets all editableGameElements in the system given a course
     *
     * @param int $courseId
     * @param bool $isEditable (optional)
     * @param bool $IDsOnly (optional)
     * @return array
     */
    public static function getEditableGameElements(int $courseId, ?bool $isEditable = null, bool $IDsOnly = false): array{
        $table = self::TABLE_EDITABLE_GAME_ELEMENT;
        $where = ["course" => $courseId];
        if ($isEditable !== null) $where["isEditable"] = $isEditable;
        $editableGameElements = Core::database()->selectMultiple($table, $where, "*", "id");

        if ($IDsOnly) return array_column($editableGameElements,"id");
        foreach ($editableGameElements as &$editableGameElementInfo){
            $editableGameElementInfo = self::parse($editableGameElementInfo);
        }
        return $editableGameElements;
    }

    /**
     * Gets a editableGameElement given a course and module.
     *
     * @param int $course
     * @param string $moduleId
     * @return EditableGameElement
     */
    public static function getEditableGameElementByModule(int $course, string $moduleId): ?EditableGameElement{
        $editableGameElementId = intval(Core::database()->select(
            self::TABLE_EDITABLE_GAME_ELEMENT, ["course" => $course, "module" => $moduleId], "id"));
        if (!$editableGameElementId) return null;
        else return new EditableGameElement($editableGameElementId);
    }

    /*** ---------------------------------------------------- ***/
    /*** -------- EditableGameElement Manipulation ---------- ***/
    /*** ---------------------------------------------------- ***/

    public static function addEditableGameElement(int $course, string $moduleId, bool $isEditable = true): EditableGameElement{
        // TODO
    }

    public static function removeEditableGameElement(){
        // TODO
    }

    public static function updateEditableGameElement(): EditableGameElement{
        // TODO
    }

    /**
     * Checks whether editableGameElement exists.
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
     * Parses a editableGameElement from the database to appropriate types.
     * Option to pass a specific field to parse intsead
     *
     * @param array|null $editableGameElement
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $editableGameElement = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "nDays"];
        $boolValues = ["isEditable", "notify"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $editableGameElement, $field, $fieldName);
    }

    /**
     * Trims editableGameElement parameter's whitespace at start/end
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["module"];
        Utils::parse($params, ...$values);
    }

}
