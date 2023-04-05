<?php
namespace GameCourse\Adaptation;

use DateTime;
use Exception;
use GameCourse\Course\Course;
use GameCourse\NotificationSystem\Notification;
use GameCourse\Role\Role;
use GameCourse\Core\Core;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use Utils\Utils;

class GameElement
{
    const TABLE_ADAPTATION_GAME_ELEMENT = "adaptation_game_element";
    const TABLE_ADAPTATION_USER_NOTIFICATION = "adaptation_user_notification";
    const TABLE_ADAPTATION_USER_PREFERENCES = "adaptation_user_preferences";
    const TABLE_ADAPTATION_QUESTIONNAIRE_ANSWERS = "adaptation_questionnaire_answers";
    const TABLE_ADAPTATION_ELEMENT_DESCRIPTIONS ="adaptation_element_descriptions";

    const HEADERS = [ // headers for import/export functionality
        "course", "user", "question1", "question2", "question3", "element", "date"
    ];

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

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    public function notify(): bool{
        return $this->getData("notify");
    }

    /**
     * Gets gameElement data from the database
     *
     * @example getData --> gets all the GameElement data
     * @example getData("module") --> gets module id
     *
     * @param string $field
     * @return int|null|string|bool
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_ADAPTATION_GAME_ELEMENT;
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
    public function setActive(bool $isActive){
        $this->setData(["isActive" => +$isActive]);
        $this->updateUsers($isActive);
    }

    /**
     * @throws Exception
     */
    public function setNotify(bool $notify){
        if ($notify && !$this->isActive()){
            throw new Exception("Cannot send notification with game element still inactive");
        }

        $this->setData(["notify" => +$notify]);
        if ($notify) $this->sendNotification();
     }

    /**
     * Sets gameElement data on the database.
     *
     * @example setData(["isActive" => "new isActive"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    // TODO - check if incomplete
    public function setData(array $fieldValues){
        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("isActive", $fieldValues) && key_exists("notify", $fieldValues)){
            $newStatus = $fieldValues["isActive"];
            $newNotify = $fieldValues["notify"];

            if (!$newStatus && $newNotify){
                throw new Exception("Cannot set game element as inactive and still notify");
            }
        }

        // Update values
        if (count($fieldValues) != 0){
            Core::database()->update(self::TABLE_ADAPTATION_GAME_ELEMENT, $fieldValues, ["id" => $this->id]);
        }
    }

    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a gameElement in the system given an id
     *
     * @param int $id
     * @return GameElement
     */
    public static function getGameElementById(int $id): ?GameElement {
        $gameElement = new GameElement($id);
        if ($gameElement->exists()) return $gameElement;
        else return null;
    }

    /**
     * Gets all gameElements in the system given a course
     *
     * @param int $courseId
     * @param bool $isActive (optional)
     * @param bool $onlyNames (optional)
     * @return array
     */
    public static function getGameElements(int $courseId, ?bool $isActive = null, ?bool $onlyNames = true): array{
        $table = self::TABLE_ADAPTATION_GAME_ELEMENT;
        $where = ["course" => $courseId];
        if ($isActive !== null) $where["isActive"] = $isActive;
        $gameElements = Core::database()->selectMultiple($table, $where, "*", "id");

        if ($onlyNames){
            return array_column($gameElements,"module");
        } else return $gameElements;

    }

    /**
     * Gets a GameElement given a course and module.
     *
     * @param int $course
     * @param string $moduleId
     * @return GameElement
     */
    public static function getGameElementByModule(int $course, string $moduleId): ?GameElement{
        $gameElementId = intval(Core::database()->select(
            self::TABLE_ADAPTATION_GAME_ELEMENT, ["course" => $course, "module" => $moduleId], "id"));
        if (!$gameElementId) return null;
        else return new GameElement($gameElementId);
    }

    /**
     * Gets all children of specific GameElement
     *
     * @return array
     * @throws Exception
     */
    public function getGameElementChildren(): array{
        $course = new Course($this->getCourse());
        $studentIndex = array_search("Student", Role::DEFAULT_ROLES);
        $module = $this->getModule();

        $response = [];
        // Iterate through hierarchy
        $hierarchy = $course->getRolesHierarchy();
        foreach($hierarchy[$studentIndex]["children"] as $value){
            // Sees if adaptation role has children
            if ($value["name"] == Role::ADAPTATION_ROLE && in_array("children", array_keys($value))){

                // Iterates through children (at this point will be game elements "badges", "leaderboard" etc)
                foreach ($value["children"] as $item){
                    if ($item["name"] == $module && in_array("children", array_keys($item))){
                        // iterates through children and saves the names
                        foreach ($item["children"] as $child) {
                            $roleId = Role::getRoleId($child["name"],$course->getId());
                            $where = ["element" => $roleId];
                            $description = Core::database()->select(self::TABLE_ADAPTATION_ELEMENT_DESCRIPTIONS, $where, "description");
                            $response[$child["name"]] = $description;
                        }
                        break;
                    }
                }
                break;
            }
        }
        return $response;

    }

    /**
     * Gets all children of specific GameElement
     *
     * @param int $courseId
     * @param int $userId
     * @param int $gameElementId
     * @return boolean
     * @throws Exception
     */
    public static function isQuestionnaireAnswered(int $courseId, int $userId, int $gameElementId): bool {
        $table = self::TABLE_ADAPTATION_QUESTIONNAIRE_ANSWERS;
        $where = ["course" => $courseId, "user" => $userId, "element" => $gameElementId];
        $response = Core::database()->select($table, $where);

        if ($response) return true;
        else return false;
    }

    /**
     * Adds a new preference questionnaire answer to the database
     *
     * @param int $course
     * @param int $user
     * @param bool $q1
     * @param string|null $q2
     * @param int|null $q3
     * @param int $element
     * @return void
     */
    public static function submitGameElementQuestionnaire(int $course, int $user, bool $q1, ?string $q2, ?int $q3, int $element){
        $table = self::TABLE_ADAPTATION_QUESTIONNAIRE_ANSWERS;
        $date = new DateTime();
        Core::database()->insert($table,[
            "course" => $course,
            "user" => $user,
            "question1" => +$q1,
            "question2" => $q2,
            "question3" => $q3,
            "element" => $element,
            "date" => $date->format("Y-m-d H:i:s")
        ]);
    }

    /**
     * Gets questions statistics for data presentation on frontend
     *
     * @param Course $course
     * @param int $gameElement
     * @return array
     * @throws Exception
     */
    public static function getElementStatistics(Course $course, int $gameElement): array{
        $table = self::TABLE_ADAPTATION_QUESTIONNAIRE_ANSWERS;
        $response = [];

        // Statistics question 1
        $nrStudents = count($course->getStudents());
        if ($nrStudents == 0) $nrStudents = 1;

        $entriesTrue = Core::database()->select($table, ["course" => $course->getId(), "element" => $gameElement, "question1" => "0"], "count(*)");
        $entriesFalse = Core::database()->select($table, ["course" => $course->getId(), "element" => $gameElement, "question1" => "1"], "count(*)");
        $response["question1"]["false"] = $entriesTrue;

        $response["question1"]["true"] = $entriesFalse;

        // Statistics question 2
        $aux = Core::database()->selectMultiple($table, ["course" => $course->getId(), "element" => $gameElement], "question2");

        $final = [];
        foreach ($aux as $description){
            array_push($final, $description["question2"]);
        }
        $response["question2"] = $final;

        // Statistics question 3
        for ($i = 1; $i <= 10; $i++) {
            $entries = Core::database()->select($table, ["course" => $course->getId(), "element" => $gameElement, "question3" => $i], "count(*)");
            $response["question3"][$i] = $entries;
        }

        return $response;
    }

    /**
     * Gets number of answers in questionnaire given a course and a gameElement
     *
     * @param Course $course
     * @param int $gameElement
     * @return int
     * @throws Exception
     */
    public static function getNrAnswersQuestionnaire(Course $course, int $gameElement): int {
        $table = self::TABLE_ADAPTATION_QUESTIONNAIRE_ANSWERS;
        $where = ["course" => $course->getId(), "element" => $gameElement];
        return Core::database()->select($table, $where, "count(*)");
    }

    /**
     * Export questionnaires answers of a specific game element given a course and game element id into a .csv file.
     *
     * @param int $courseId
     * @param int $gameElement
     * @return string
     * @throws Exception
     */
    public static function exportAnswersQuestionnaire(int $courseId, int $gameElement): string {
        $course = new Course($courseId);
        if (!$course->exists())
            throw new Exception("Course with ID = " . $courseId . " doesn't exist.");

        $table = self::TABLE_ADAPTATION_QUESTIONNAIRE_ANSWERS;
        $where = ["course" => $courseId, "element" => $gameElement];
        $response = Core::database()->selectMultiple($table, $where);
        return Utils::exportToCSV($response, function ($element) {
                return [$element["course"], $element["user"], $element["question1"], $element["question2"],
                    $element["question3"], $element["element"], $element["date"]];
                }, self::HEADERS);
    }

    /*** ---------------------------------------------------- ***/
    /*** ------------ GameElement Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a GameElement to the database.
     * Returns the newly created GameElement.
     *
     * @param int $course
     * @param string $moduleId
     * @param bool $isActive (optional)
     * @return GameElement
     * @throws Exception
     */
    public static function addGameElement(int $course, string $moduleId, bool $isActive = false): GameElement{
        $table = self::TABLE_ADAPTATION_GAME_ELEMENT;
        $id = Core::database()->insert($table, [
            "course" => $course,
            "module" => $moduleId,
            "isActive" => +$isActive
        ]);

        return new GameElement($id);
    }

    /**
     * Removes a GameElement from the database.
     *
     * @param int $courseId
     * @param string $moduleId
     * @throws Exception
     */
    public static function removeGameElement(int $courseId, string $moduleId){
        $table = self::TABLE_ADAPTATION_GAME_ELEMENT;
        Core::database()->delete($table, ["course" => $courseId, "module" => $moduleId]);
    }

    /**
     * Gets user's previous GameElement preference to adapt
     *
     * @param int $courseId
     * @param int $userId
     * @param string $moduleId
     * @return string
     * @throws Exception
     */
    public static function getPreviousUserPreference(int $courseId, int $userId, string $moduleId): string{
        $table = self::TABLE_ADAPTATION_USER_PREFERENCES;
        $where = ["course" => $courseId, "user" => $userId, "module" => $moduleId];
        $preferences = Core::database()->select($table, $where, "*", "date desc");

        if ($preferences){
            $response = $preferences["newPreference"];

        } else{
            return "No data for current preference.";
        }

        return Role::getRoleName($response);
    }

    /**
     * Updates user's preference regarding GameElement customization and their adaptation roles accordingly
     *
     * @param int $courseId
     * @param int $userId
     * @param string $module
     * @param int|null $previousPreference
     * @param int $newPreference
     * @return void
     * @throws Exception
     */
    public static function updateUserPreference(int $courseId, int $userId, string $module, ?int $previousPreference, int $newPreference){
        $course = new Course($courseId);
        $courseUser = $course->getCourseUserById($userId);
        $userRoles = Role::getUserRoles($userId, $courseId);

        $table = self::TABLE_ADAPTATION_USER_PREFERENCES;
        $where = ["course" => $courseId, "user" => $userId, "newPreference" => $previousPreference];

        $date = new DateTime();
        $data = ["course" => $courseId,
                 "user" => $userId,
                 "module" => $module,
                 "previousPreference" =>  $previousPreference,
                 "newPreference" => $newPreference,
                 "date" => $date->format("Y-m-d H:i:s")];

        $lastPreference = Core::database()->select($table, $where, "*", "date desc");
        // NOTE: if lastPreference date was less than 1 day ago and student had already chosen something: it does not save another entry, just updates it
        if ($lastPreference["date"]) {
            if (strtotime('-1 day') < strtotime($lastPreference["date"])) {
                Core::database()->update($table, $data, ["id" => $lastPreference["id"]]);

                // Updates roles of user
                $oldRoleIndex = array_search(Role::getRoleName($previousPreference), $userRoles);
                array_splice($userRoles, $oldRoleIndex, 1, Role::getRoleName($newPreference));
                $courseUser->setRoles($userRoles);

                return;
            }
        }

        Core::database()->insert($table, $data); // add to db
        array_push($userRoles, Role::getRoleName($newPreference));
        $courseUser->setRoles($userRoles);
    }

    /**
     * Adds description regarding the different game element versions into the database
     *
     * @param int $id
     * @param string $description
     * @return void
     * @throws Exception
     */
    public static function addGameElementDescription(int $id, string $description){
        $table = self::TABLE_ADAPTATION_ELEMENT_DESCRIPTIONS;
        Core::database()->insert($table, ["element" => $id, "description" => $description]);
    }

    /**
     * Clears descriptions regarding the different game element versions into the database
     *
     * @param int $id
     * @param string $description
     * @return void
     * @throws Exception
     */
    public static function removeGameElementDescription(int $id, string $description){
        $table = self::TABLE_ADAPTATION_ELEMENT_DESCRIPTIONS;
        Core::database()->delete($table, ["element" => $id, "description" => $description]);
    }

    /**
     * Adds a new student to table element_user to allow him/her to edit game element
     *
     * @param int $courseId
     * @param int $studentId
     * @return void
     * @throws Exception
     */
    public static function addStudentToEdit(int $courseId, int $studentId){
        $gameElements = self::getGameElements($courseId);

        foreach ($gameElements as $gameElement){
            $gameElement = GameElement::getGameElementByModule($courseId, $gameElement);
            Core::database()->insert(self::TABLE_ADAPTATION_USER_NOTIFICATION, ["element" => $gameElement->getId(), "user" => $studentId]);
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
        $table = self::TABLE_ADAPTATION_USER_NOTIFICATION;
        $where = ["user" => $studentId];
        Core::database()->delete($table, $where);
    }

    /**
     * Checks whether gameElement exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }

    /*** ---------------------------------------------------- ***/
    /*** ------------------ Other Actions ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Sees if notification needs to be sent to students about questionnaire availability
     *
     * @return void
     * @throws Exception
     */
    public function sendNotification(){
        if ($this->isActive()){
            $message = $this->getModule() . " Preference Questionnaire is available! Go to 'Adaptation' tab for more";
            $users = Core::database()->selectMultiple(self::TABLE_ADAPTATION_USER_NOTIFICATION, ["element" => $this->id], "user");
            $users = array_map(function ($user) {return $user["user"];}, $users);

            foreach ($users as $user){
                if (!Notification::isNotificationInDB($this->getCourse(), $user, $message) &&
                    Role::userHasRole($user["id"], $this->getCourse(), "Student") && $user->isActive()){
                    Notification::addNotification($this->getCourse(), $user, $message);
                }
            }
        }
    }

    /**
     * Updates users that are allowed to answer questionnaire + custom game elements
     *
     * If game element is active => all courseUsers can answer questionnaire and custom that game element
     * If game element is not active => users are removed from answering questionnaire and custom that game element
     *
     * @return void
     * @throws Exception
     */
    public function updateUsers(bool $isActive){
        $course = $this->getCourse();
        $gameElement = $this->getId();
        $users = CourseUser::getCourseUsersByCourse($course);

        // add all courseUsers to table element_user
        if ($isActive) {
            foreach ($users as $user){
                if (Role::userHasRole($user["id"], $course, "Student") && $user->isActive()){    // only add notification to students
                    Core::database()->insert(self::TABLE_ADAPTATION_USER_NOTIFICATION, ["element" => $gameElement, "user" => $user["id"]]);
                }
            }
        }

        // remove all courseUsers from table element_user
        else {
            foreach ($users as $user){
                if (Role::userHasRole($user["id"], $course, "Student") && $user->isActive()){
                    Core::database()->delete(self::TABLE_ADAPTATION_USER_NOTIFICATION, ["element" => $gameElement, "user" => $user["id"]]);
                }
            }
        }
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
     * Parses a gameElement from the database to appropriate types.
     * Option to pass a specific field to parse instead
     *
     * @param array|null $gameElement
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $gameElement = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course"];
        $boolValues = ["isActive", "notify"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $gameElement, $field, $fieldName);
    }

    /**
     * Trims gameElement parameter's whitespace at start/end
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["module"];
        Utils::trim($params, ...$values);
    }

}
