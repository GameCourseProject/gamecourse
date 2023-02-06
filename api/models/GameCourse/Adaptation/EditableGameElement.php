<?php
namespace GameCourse\Adaptation;

use Exception;
use GameCourse\NotificationSystem\Notification;
use GameCourse\Role\Role;
use GameCourse\Core\Core;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use Mockery\Matcher\Not;
use Utils\CronJob;
use Utils\Utils;

class EditableGameElement
{
    const TABLE_EDITABLE_GAME_ELEMENT = "editable_game_element";
    const TABLE_USER_GAME_ELEMENT_PREFERENCES = "user_game_element_preferences";
    const TABLE_ELEMENT_USER = "element_user";

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

    public function usersMode(): string {
        return $this->getData("usersMode");
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

    public function setUsersMode(string $usersMode){
        $this->setData(["usersMode" => $usersMode]);
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
        // Trim values
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
        if (key_exists("usersMode", $fieldValues)){
            $newUsersMode = $fieldValues["usersMode"];
            $oldUsersMode = $this->usersMode();
        }

        // Update values
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_EDITABLE_GAME_ELEMENT, $fieldValues, ["id" => $this->id]);

        // Additional actions TODO - add more?
        if (key_exists("nDays", $fieldValues)) {
            $d = strtotime("+" . $fieldValues["nDays"]. " Days");
            $endDate = date("Y-m-d h:i:sa", $d);
            $this->setAutomation("AutoNotEditable", $endDate);
        }
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
     * @param bool $onlyNames (optional)
     * @return array
     */
    public static function getEditableGameElements(int $courseId, ?bool $isEditable = null, ?bool $onlyNames = false): array{
        $table = self::TABLE_EDITABLE_GAME_ELEMENT;
        $where = ["course" => $courseId];
        if ($isEditable !== null) $where["isEditable"] = $isEditable;
        $editableGameElements = Core::database()->selectMultiple($table, $where, "*", "id");

        if ($onlyNames) return array_column($editableGameElements,"module");
        foreach ($editableGameElements as &$editableGameElementInfo){
            $editableGameElementInfo = self::parse($editableGameElementInfo);
        }
        return $editableGameElements;
    }

    /**
     * Gets all users allowed to customize a specific editableGameElements in the system given a course
     *
     * @param int $courseId
     * @param string $moduleId
     * @return array
     */
    public static function getEditableGameElementUsers(int $courseId, string $moduleId): array {
        $table = self::TABLE_EDITABLE_GAME_ELEMENT . " ege JOIN " . self::TABLE_ELEMENT_USER . " eu on ege.id=eu.element JOIN " . User::TABLE_USER . " u on eu.user=u.id";
        $where = ["course" => $courseId, "module" => $moduleId];
        $users = Core::database()->selectMultiple($table, $where, "u.*");

        foreach ($users as &$user) { $user = User::parse($user); }
        return $users;
        /*if ($usersMode && $usersMode != UsersMode::ALL_EXCEPT_USERS){
            foreach ($users as &$user) { $user = User::parse($user); }
            return $users;
        } else return [];*/
    }

    /**
     * Gets an editableGameElement given a course and module.
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

    /**
     * Gets all children of specific editableGameElement
     *
     * @return array
     * @throws Exception
     */
    public function getEditableGameElementChildren(): array{
        $roles = Role::getCourseRoles($this->getCourse(), false, true);
        $module = $this->getModule();

        $response = [];
        foreach ($roles as $role){
            // role belongs to the module and has children
            if ($role["module"] == $module && in_array("children", array_keys($role))){
                // iterates through children and saves the names
                foreach ($role["children"] as $child) {
                    array_push($response, $child["name"]);
                }
                break;
            }
        }
        return $response;
    }

    /*** ---------------------------------------------------- ***/
    /*** -------- EditableGameElement Manipulation ---------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds an editableGameElement to the database.
     * Returns the newly created editableGameElement.
     *
     * @param int $course
     * @param string $moduleId
     * @param bool $isEditable (optional)
     * @return EditableGameElement
     * @throws Exception
     */
    public static function addEditableGameElement(int $course, string $moduleId, bool $isEditable = false): EditableGameElement{
        $table = self::TABLE_EDITABLE_GAME_ELEMENT;
        $id = Core::database()->insert($table, [
            "course" => $course,
            "module" => $moduleId,
            "isEditable" => +$isEditable
        ]);

        return new EditableGameElement($id);
    }

    /**
     * Removes an editableGameElement from the database.
     *
     * @param int $courseId
     * @param string $moduleId
     * @throws Exception
     */
    public static function removeEditableGameElement(int $courseId, string $moduleId){
        $table = self::TABLE_EDITABLE_GAME_ELEMENT;
        Core::database()->delete($table, ["course" => $courseId, "module" => $moduleId]);
    }

    /**
     * Updates a specific editableGameElement
     * Returns the editableGameElement with the updated information
     *
     * @param bool $isEditable
     * @param int $nDays
     * @param array $users
     * @param string $usersMode
     * @param bool $notify (optional)
     * @return EditableGameElement
     * @throws Exception
     */
    public function updateEditableGameElement(bool $isEditable, int $nDays, array $users, string $usersMode, bool $notify = false): EditableGameElement{

        if ($usersMode == UsersMode::ALL_USERS){
            $this->setData(["nDays" => $nDays, "notify" => +$notify);
        }

        // delete all users allowed to customize game element before inserting new ones
        Core::database()->delete(self::TABLE_ELEMENT_USER, ["element" => $this->id]);

        foreach ($users as $userId) {
            Core::database()->insert(self::TABLE_ELEMENT_USER, ["element" => $this->id, "user" => $userId]);

            if ($isEditable && $notify){
                $message = "Game element '" . $this->getModule() . "' is ready for customization! Go to 'Adaptation' tab for more details";

                if (!Notification::isNotificationInDB($this->getCourse(), $userId, $message)){
                    Notification::addNotification($this->getCourse(), $userId, $message);
                }
            }
        }

        return $this;
    }

    /**
     * Gets user's previous editableGameElement preference to adapt
     *
     * @param int $courseId
     * @param int $userId
     * @param string $moduleId
     * @return string
     * @throws Exception
     */
    public static function getPreviousUserPreference(int $courseId, int $userId, string $moduleId): string{
        $table = self::TABLE_USER_GAME_ELEMENT_PREFERENCES;
        $where = ["course" => $courseId, "user" => $userId, "module" => $moduleId];
        $preferences = Core::database()->selectMultiple($table, $where, "*", "date");

        if (count($preferences)){
            $response = $preferences[0]["newPreference"];

        } else{
            return "No data for current preference.";
        }

        return Role::getRoleName($response);
    }

    /**
     * Adds a new student to table element_user to allow him/her to edit game element
     * NOTE: It is only added if editableGameElement is editable to all users. Other cases are not covered
     *
     * @param int $courseId
     * @param int $studentId
     * @return void
     * @throws Exception
     */
    public static function addStudentToEdit(int $courseId, int $studentId){
        $editableGameElements = self::getEditableGameElements($courseId);

        foreach ($editableGameElements as $gameElement){
            // NOTE: Only covered for "all-users" case
            if ($gameElement->getUsersMode() == UsersMode::ALL_USERS){
                // Add to table
                Core::database()->insert(self::TABLE_ELEMENT_USER, ["element" => $gameElement->getId(), "user" => $studentId]);
            }
        }
    }

    /**
     * Removes student from table element_user
     *
     * @param int $studentId
     * @return void
     * @throws Exception
     */
    public static function removeStudentToEdit(int $studentId){
        $table = self::TABLE_ELEMENT_USER;
        $where = ["user" => $studentId];
        Core::database()->delete($table, $where);
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
    /*** -------------------- Automation -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Sets automation for some editableGameElement processes.
     *
     * @param string $script
     * @param ...$data
     * @return void
     * @throws Exception
     */
    private function setAutomation(string $script, ...$data)
    {
        switch ($script){
            case "AutoNotEditable":
                $this->setAutoNotEditable($data[0]);
                break;

            default:
                throw new Exception("Automation script '" . $script . "' not found for course.");
        }
    }

    /**
     * Sets isEditable = 0 to a given editableGameElement on a specific date
     *
     * @param string|null $endDate
     * @return void
     * @throws Exception
     */
    public function setAutoNotEditable(?string $endDate){
        $script = ROOT_PATH . "models/GameCourse/Adaptation/AutoNotEditableScript.php";
        if ($endDate) new CronJob($script, CronJob::dateToExpression($endDate), $this->id);
        else CronJob::removeCronJob($script, $this->id);
    }

    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates module ID or name.
     *
     * @param $module
     * @return void
     * @throws Exception
     */
    private static function validateModule($module)
    {
        if (!is_string($module) || empty($module))
            throw new Exception("Module name can't be null neither empty.");

        if (is_numeric($module))
            throw new Exception("Module name can't be composed of only numbers.");

        if (iconv_strlen($module) > 50)
            throw new Exception("Module name is too long: maximum of 50 characters.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a editableGameElement from the database to appropriate types.
     * Option to pass a specific field to parse instead
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
        $params = ["module", "usersMode"];
        Utils::trim($params, ...$values);
    }

}
