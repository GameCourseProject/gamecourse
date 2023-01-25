<?php
namespace GameCourse\Module\VirtualCurrency;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the AutoAction model, which implements the necessary methods
 * to interact with automatic actions in the MySQL database.
 */
class AutoAction
{
    const TABLE_VC_AUTO_ACTION = "virtual_currency_auto_action";

    const HEADERS = [   // headers for import/export functionality
        "name", "description", "type", "amount", "isActive"
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

    public function getCourse(): Course
    {
        return new Course($this->getData("course"));
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getDescription(): string
    {
        return $this->getData("description");
    }

    public function getType(): string
    {
        return $this->getData("type");
    }

    public function getAmount(): int
    {
        return $this->getData("amount");
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Gets action data from the database.
     *
     * @example getData() --> gets all action data
     * @example getData("name") --> gets action name
     * @example getData("name, description") --> gets action name & description
     *
     * @param string $field
     * @return array|int|string|boolean|null
     */
    public function getData(string $field = "*")
    {
        // Get data
        $table = self::TABLE_VC_AUTO_ACTION;
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
    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function setDescription(string $description)
    {
        $this->setData(["description" => $description]);
    }

    /**
     * @throws Exception
     */
    public function setType(string $type)
    {
        $this->setData(["type" => $type]);
    }

    /**
     * @throws Exception
     */
    public function setAmount(int $amount)
    {
        $this->setData(["amount" => $amount]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets action data on the database.
     * @example setData(["name" => "New name])
     * @example setData(["name" => "New name", "description" => "New description"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        $rule = $this->getRule();

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) {
            $newName = $fieldValues["name"];
            self::validateName($newName);
            $oldName = $this->getName();
        }
        if (key_exists("description", $fieldValues)) {
            $newDescription = $fieldValues["description"];
            self::validateDescription($newDescription);
        }
        if (key_exists("type", $fieldValues)) {
            $newType = $fieldValues["type"];
            self::validateType($newType);
        }
        if (key_exists("amount", $fieldValues)) {
            $newAmount = $fieldValues["amount"];
        }
        if (key_exists("isActive", $fieldValues)) {
            $newStatus = $fieldValues["isActive"];
            $oldStatus = $this->isActive();
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_VC_AUTO_ACTION, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("isActive", $fieldValues)) {
            if ($oldStatus != $newStatus) {
                // Update rule status
                $rule->setActive($newStatus);
            }
        }
        if (key_exists("name", $fieldValues) || key_exists("description", $fieldValues) ||
            key_exists("type", $fieldValues) || key_exists("amount", $fieldValues)) {
            // Update action rule
            $name = key_exists("name", $fieldValues) ? $newName : $this->getName();
            $description = key_exists("description", $fieldValues) ? $newDescription : $this->getDescription();
            $type = key_exists("type", $fieldValues) ? $newType : $this->getType();
            $amount = key_exists("amount", $fieldValues) ? $newAmount : $this->getAmount();
            self::updateRule($rule->getId(), $name, $description, $type, $amount);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets an action by its ID.
     * Returns null if action doesn't exist.
     *
     * @param int $id
     * @return AutoAction|null
     */
    public static function getActionById(int $id): ?AutoAction
    {
        $action = new AutoAction($id);
        if ($action->exists()) return $action;
        else return null;
    }

    /**
     * Gets an action by its name.
     * Returns null if action doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return AutoAction|null
     */
    public static function getActionByName(int $courseId, string $name): ?AutoAction
    {
        $actionId = intval(Core::database()->select(self::TABLE_VC_AUTO_ACTION, ["course" => $courseId, "name" => $name], "id"));
        if (!$actionId) return null;
        else return new AutoAction($actionId);
    }

    /**
     * Gets all actions of course.
     * Option for 'active' and ordering.
     *
     * @param int $courseId
     * @param bool|null $active
     * @param string $orderBy
     * @return array
     */
    public static function getActions(int $courseId, bool $active = null, string $orderBy = "name"): array
    {
        $where = ["course" => $courseId];
        if ($active !== null) $where["isActive"] = $active;
        $actions = Core::database()->selectMultiple(self::TABLE_VC_AUTO_ACTION, $where, "*", $orderBy);
        foreach ($actions as &$actionInfo) { $actionInfo = self::parse($actionInfo); }
        return $actions;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Action Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new action to the database.
     * Returns the newly created action.
     *
     * @param int $courseId
     * @param string $name
     * @param string $description
     * @param string $type
     * @param int $amount
     * @return AutoAction
     * @throws Exception
     */
    public static function addAction(int $courseId, string $name, string $description, string $type, int $amount): AutoAction
    {
        self::trim($name, $description, $type);
        self::validateAction($name, $description, $type);

        // Create action rule
        $rule = self::addRule($courseId, $name, $description, $type, $amount);

        // Insert in database
        $id = Core::database()->insert(self::TABLE_VC_AUTO_ACTION, [
            "course" => $courseId,
            "name" => $name,
            "description" => $description,
            "type" => $type,
            "amount" => $amount,
            "rule" => $rule->getId()
        ]);
        return new AutoAction($id);
    }

    /**
     * Edits an existing action in the database.
     * Returns the edited action.
     *
     * @param string $name
     * @param string $description
     * @param string $type
     * @param int $amount
     * @param bool $isActive
     * @return AutoAction
     * @throws Exception
     */
    public function editAction(string $name, string $description, string $type, int $amount, bool $isActive): AutoAction
    {
        $this->setData([
            "name" => $name,
            "description" => $description,
            "type" => $type,
            "amount" => $amount,
            "isActive" => +$isActive
        ]);
        return $this;
    }

    /**
     * Copies an existing action into another given course.
     *
     * @param Course $copyTo
     * @return void
     * @throws Exception
     */
    public function copyAction(Course $copyTo)
    {
        $actionInfo = $this->getData();

        // Copy action
        $copiedAction = self::addAction($copyTo->getId(), $actionInfo["name"], $actionInfo["description"],
            $actionInfo["type"], $actionInfo["amount"]);
        $copiedAction->setActive($actionInfo["isActive"]);

        // Copy rule
        $this->getRule()->mirrorRule($copiedAction->getRule());
    }

    /**
     * Deletes an action from the database.
     *
     * @param int $actionId
     * @return void
     * @throws Exception
     */
    public static function deleteAction(int $actionId) {
        $action = self::getActionById($actionId);
        if ($action) {
            $courseId = $action->getCourse()->getId();

            // Remove action rule
            self::removeRule($courseId, $action->getRule()->getId());

            // Delete action from database
            Core::database()->delete(self::TABLE_VC_AUTO_ACTION, ["id" => $actionId]);
        }
    }

    /**
     * Checks whether badge exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Rules ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets action rule.
     *
     * @return Rule
     */
    public function getRule(): Rule
    {
        return Rule::getRuleById($this->getData("rule"));
    }

    /**
     * Adds a new action rule to the Rule System.
     * Returns the newly created rule.
     *
     * @param int $courseId
     * @param string $name
     * @param string $description
     * @param string $type
     * @param int $amount
     * @return Rule
     * @throws Exception
     */
    private static function addRule(int $courseId, string $name, string $description, string $type, int $amount): Rule
    {
        // Add rule to virtual currency section
        $VCModule = new VirtualCurrency(new Course($courseId));
        return $VCModule->addRuleOfItem(null, $name, $description, $type, $amount);
    }

    /**
     * Updates action rule in the Rule System.
     *
     * @param int $ruleId
     * @param string $name
     * @param string $description
     * @param string $type
     * @param int $amount
     * @return void
     * @throws Exception
     */
    private static function updateRule(int $ruleId, string $name, string $description, string $type, int $amount)
    {
        $rule = new Rule($ruleId);
        $params = self::generateRuleParams($name, $description, $type, $amount);
        $rule->setName($params["name"]);
        $rule->setDescription($params["description"]);
        $rule->setWhen($params["when"]);
        $rule->setThen($params["then"]);
    }

    /**
     * Deletes action rule from the Rule System.
     *
     * @param int $courseId
     * @param int $ruleId
     * @return void
     * @throws Exception
     */
    private static function removeRule(int $courseId, int $ruleId)
    {
        $VCModule = new VirtualCurrency(new Course($courseId));
        $VCModule->deleteRuleOfItem($ruleId);
    }

    /**
     * Generates action rule parameters.
     *
     * @param string $name
     * @param string $description
     * @param string $type
     * @param int $amount
     * @return array
     * @throws Exception
     */
    public static function generateRuleParams(string $name, string $description, string $type, int $amount): array
    {
        // Generate when clause
        $logs = "get_logs(" . ($type !== "peergraded post" ? "target" : "None") . ", \"$type\"" . ($type === "peergraded post" ? ", None, target" : "") . ")";
        $when = str_replace("<logs>", $logs, file_get_contents(__DIR__ . "/rules/when_template.txt"));

        // Generate then clause
        $then = str_replace("<action>", $amount > 0 ? "award" : "spend", file_get_contents(__DIR__ . "/rules/then_template.txt"));
        $then = str_replace("<name>", "\"$description\"", $then);
        $then = str_replace("<amount>", abs($amount), $then);

        return ["name" => $name, "description" => $description, "when" => $when, "then" => $then];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports actions into a given course from a .csv file.
     * Returns the nr. of actions imported.
     *
     * @param int $courseId
     * @param string $file
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importActions(int $courseId, string $file, bool $replace = true): int
    {
        return Utils::importFromCSV(self::HEADERS, function ($action, $indexes) use ($courseId, $replace) {
            $name = Utils::nullify($action[$indexes["name"]]);
            $description = Utils::nullify($action[$indexes["description"]]);
            $type = Utils::nullify($action[$indexes["type"]]);
            $amount = Utils::nullify($action[$indexes["amount"]]);
            $isActive = self::parse(null, $action[$indexes["isActive"]], "isActive");

            $action = self::getActionByName($courseId, $name);
            if ($action) {  // action already exists
                if ($replace)  // replace
                    $action->editAction($name, $description, $type, $amount, $isActive);

            } else {  // action doesn't exist
                AutoAction::addAction($courseId, $name, $description, $type, $amount);
                return 1;
            }
            return 0;
        }, $file);
    }

    /**
     * Exports actions into a .csv file.
     *
     * @param int $courseId
     * @param array $actionIds
     * @return string
     */
    public static function exportActions(int $courseId, array $actionIds): string
    {
        $actionsToExport = array_values(array_filter(self::getActions($courseId), function ($action) use ($actionIds) { return in_array($action["id"], $actionIds); }));
        return Utils::exportToCSV(
            $actionsToExport,
            function ($action) {
                return [$action["name"], $action["description"], $action["type"], $action["amount"], +$action["isActive"]];
            },
            self::HEADERS);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates action parameters.
     *
     * @param $name
     * @param $description
     * @param $type
     * @return void
     * @throws Exception
     */
    private static function validateAction($name, $description, $type)
    {
        self::validateName($name);
        self::validateDescription($description);
        self::validateType($type);
    }

    /**
     * Validates action name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Action name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Action name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Action name is too long: maximum of 50 characters.");
    }

    /**
     * Validates action description.
     *
     * @param $description
     * @return void
     * @throws Exception
     */
    private static function validateDescription($description)
    {
        if (!is_string($description) || empty(trim($description)))
            throw new Exception("Action description can't be null nor empty.");

        if (is_numeric($description))
            throw new Exception("Action description can't be composed of only numbers.");

        if (iconv_strlen($description) > 150)
            throw new Exception("Badge description is too long: maximum of 150 characters.");
    }

    /**
     * Validates action type.
     *
     * @param $type
     * @return void
     * @throws Exception
     */
    private static function validateType($type)
    {
        if (!is_string($type) || empty(trim($type)))
            throw new Exception("Action type can't be null neither empty.");

        if (iconv_strlen($type) > 50)
            throw new Exception("Action type is too long: maximum of 50 characters.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses an action coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $action
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $action = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "amount", "rule"];
        $boolValues = ["isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $action, $field, $fieldName);
    }

    /**
     * Trims action parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "description", "type"];
        Utils::trim($params, ...$values);
    }
}
