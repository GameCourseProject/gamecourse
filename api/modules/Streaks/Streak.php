<?php
namespace GameCourse\Module\Streaks;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Theme\Theme;
use Utils\Time;
use Utils\Utils;

/**
 * This is the Streak model, which implements the necessary methods
 * to interact with streaks in the MySQL database.
 */
class Streak
{
    const TABLE_STREAK = 'streak';
    const TABLE_STREAK_PROGRESSION = 'streak_progression';
    const TABLE_STREAK_DEADLINE = 'streak_deadline';

    const HEADERS = [   // headers for import/export functionality
        "name", "description", "color", "goal", "periodicityGoal", "periodicityNumber", "periodicityTime", "periodicityType",
        "reward", "tokens", "isExtra", "isRepeatable", "isActive"
    ];

    const EMPTY_IMAGE = __DIR__ . "/assets/empty.svg";
    const FULL_IMAGE = __DIR__ . "/assets/full.svg";

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

    public function getColor(): ?string
    {
        return $this->getData("color");
    }

    public function getGoal(): int
    {
        return $this->getData("goal");
    }

    public function getPeriodicityGoal(): ?int
    {
        return $this->getData("periodicityGoal");
    }

    public function getPeriodicityNumber(): ?int
    {
        return $this->getData("periodicityNumber");
    }

    public function getPeriodicityTime(): ?string
    {
        return $this->getData("periodicityTime");
    }

    public function getPeriodicityType(): ?string
    {
        return $this->getData("periodicityType");
    }

    public function getReward(): int
    {
        return $this->getData("reward");
    }

    public function getTokens(): int
    {
        return $this->getData("tokens");
    }

    public function getImage(): string
    {
        $img = file_get_contents(__DIR__ . "/icon.svg");

        // Change image color
        $color = $this->getColor() ?? (Core::getLoggedUser()->getTheme() == Theme::DARK ? "#BFC6D4" : "#DDDDDD");
        $img = preg_replace("/fill=\"(.*?)\"/", "fill=\"$color\"", $img);

        return "data:image/svg+xml;base64," . base64_encode($img);
    }

    public function getDeadline(int $userId): ?string
    {
        if (!$this->isPeriodic())
            return null;

        $courseEndDate = $this->getCourse()->getEndDate();
        $deadline =  Core::database()->select(self::TABLE_STREAK_DEADLINE, [
            "course" => $this->getCourse()->getId(),
            "user" => $userId,
            "streak" => $this->id
        ], "deadline");

        // Deadline is over
        if ($deadline && strtotime($deadline) < strtotime(date("Y-m-d H:i:s", time())))
            return null;

        // Deadline is after the end of course
        if ($deadline && $courseEndDate && strtotime($deadline) > strtotime($courseEndDate))
            return $courseEndDate;

        return $deadline;
    }

    public function isExtra(): bool
    {
        return $this->getData("isExtra");
    }

    public function isRepeatable(): bool
    {
        return $this->getData("isRepeatable");
    }

    public function isPeriodic(): bool
    {
        return !is_null($this->getPeriodicityNumber()) && !is_null($this->getPeriodicityTime());
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Gets streak data from the database.
     *
     * @example getData() --> gets all streak data
     * @example getData("name") --> gets streak name
     * @example getData("name, description") --> gets streak name & description
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_STREAK;
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
    public function setColor(?string $color)
    {
        $this->setData(["color" => $color]);
    }

    /**
     * @throws Exception
     */
    public function setGoal(int $goal)
    {
        $this->setData(["goal" => $goal]);
    }

    /**
     * @throws Exception
     */
    public function setPeriodicityGoal(?int $goal)
    {
        $this->setData(["periodicityGoal" => $goal]);
    }

    /**
     * @throws Exception
     */
    public function setPeriodicityNumber(?int $number)
    {
        $this->setData(["periodicityNumber" => $number]);
    }

    /**
     * @throws Exception
     */
    public function setPeriodicityTime(?string $time)
    {
        $this->setData(["periodicityTime" => $time]);
    }

    /**
     * @throws Exception
     */
    public function setPeriodicityType(?string $type)
    {
        $this->setData(["periodicityType" => $type]);
    }

    /**
     * @throws Exception
     */
    public function setReward(int $reward)
    {
        $this->setData(["reward" => $reward]);
    }

    /**
     * @throws Exception
     */
    public function setTokens(int $tokens)
    {
        $this->setData(["tokens" => $tokens]);
    }

    /**
     * @throws Exception
     */
    public function setExtra(bool $isExtra)
    {
        $this->setData(["isExtra" => +$isExtra]);
    }

    /**
     * @throws Exception
     */
    public function setRepeatable(bool $isRepeatable)
    {
        $this->setData(["isRepeatable" => +$isRepeatable]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets badge data on the database.
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "description" => "New description"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        $courseId = $this->getCourse()->getId();
        $rule = $this->getRule();

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) {
            $newName = $fieldValues["name"];
            self::validateName($courseId, $newName, $this->id);
        }
        if (key_exists("description", $fieldValues)) {
            $newDescription = $fieldValues["description"];
            self::validateDescription($newDescription);
        }
        if (key_exists("color", $fieldValues)) self::validateColor($fieldValues["color"]);
        if (key_exists("goal", $fieldValues)) self::validateInteger("goal", $fieldValues["goal"]);
        if (key_exists("periodicityGoal", $fieldValues) && !is_null($fieldValues["periodicityGoal"]))
            self::validateInteger("periodicityGoal", $fieldValues["periodicityGoal"]);
        if (key_exists("periodicityNumber", $fieldValues) && !is_null($fieldValues["periodicityNumber"]))
            self::validateInteger("periodicityNumber", $fieldValues["periodicityNumber"]);
        if (key_exists("periodicityTime", $fieldValues) && !is_null($fieldValues["periodicityTime"]))
            self::validatePeriodicityTime($fieldValues["periodicityTime"]);
        if (key_exists("periodicityType", $fieldValues) && !is_null($fieldValues["periodicityType"]))
            self::validatePeriodicityType($fieldValues["periodicityType"]);
        if (key_exists("reward", $fieldValues)) self::validateInteger("reward", $fieldValues["reward"]);
        if (key_exists("tokens", $fieldValues)) {
            $newTokens = $fieldValues["tokens"];
            self::validateInteger("tokens", $newTokens);
        }
        if (key_exists("isActive", $fieldValues)) {
            $newStatus = $fieldValues["isActive"];
            $oldStatus = $this->isActive();
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_STREAK, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("isActive", $fieldValues)) {
            if ($oldStatus != $newStatus) {
                // Update rule status
                $rule->setActive($newStatus);
            }
        }
        if (key_exists("name", $fieldValues) || key_exists("description", $fieldValues) || key_exists("tokens", $fieldValues)) {
            // Update streak rule
            $name = key_exists("name", $fieldValues) ? $newName : $this->getName();
            $description = key_exists("description", $fieldValues) ? $newDescription : $this->getDescription();
            $periodicityNumber = key_exists("periodicityNumber", $fieldValues) ? $fieldValues["periodicityNumber"] : $this->getPeriodicityNumber();
            $periodicityTime = key_exists("periodicityTime", $fieldValues) ? $fieldValues["periodicityTime"] : $this->getPeriodicityTime();
            $periodicityType = key_exists("periodicityType", $fieldValues) ? $fieldValues["periodicityType"] : $this->getPeriodicityType();
            self::updateRule($rule->getId(), $name, $description, $periodicityNumber, $periodicityTime, $periodicityType);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a streak by its ID.
     * Returns null if streak doesn't exist.
     *
     * @param int $id
     * @return Streak|null
     */
    public static function getStreakById(int $id): ?Streak
    {
        $streak = new Streak($id);
        if ($streak->exists()) return $streak;
        else return null;
    }

    /**
     * Gets a streak by its name.
     * Returns null if streak doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return Streak|null
     */
    public static function getStreakByName(int $courseId, string $name): ?Streak
    {
        $streakId = intval(Core::database()->select(self::TABLE_STREAK, ["course" => $courseId, "name" => $name], "id"));
        if (!$streakId) return null;
        else return new Streak($streakId);
    }

    /**
     * Gets all streaks of course.
     * Option for 'active' and ordering.
     *
     * @param int $courseId
     * @param bool|null $active
     * @param string $orderBy
     * @return array
     */
    public static function getStreaks(int $courseId, bool $active = null, string $orderBy = "name"): array
    {
        $where = ["course" => $courseId];
        if ($active !== null) $where["isActive"] = $active;
        $streaks = Core::database()->selectMultiple(self::TABLE_STREAK, $where, "*", $orderBy);
        foreach ($streaks as &$streakInfo) {
            $streakInfo = self::parse($streakInfo);

            // Get image
            $streak = new Streak($streakInfo["id"]);
            $streakInfo["image"] = $streak->getImage();
        }
        return $streaks;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Streak Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new streak to the database.
     * Returns the newly created streak.
     *
     * @param int $courseId
     * @param string $name
     * @param string|null $description
     * @param string|null $color
     * @param int $goal
     * @param int|null $periodicityGoal
     * @param int|null $periodicityNumber
     * @param string|null $periodicityTime
     * @param string|null $periodicityType
     * @param int $reward
     * @param int|null $tokens
     * @param bool $isExtra
     * @param bool $isRepeatable
     * @return Streak
     * @throws Exception
     */
    public static function addStreak(int $courseId, string $name, string $description, ?string $color, int $goal,
                                     ?int $periodicityGoal, ?int $periodicityNumber, ?string $periodicityTime,
                                     ?string $periodicityType, int $reward, int $tokens, bool $isExtra, bool $isRepeatable): Streak
    {
        self::trim($name, $description, $color, $periodicityTime, $periodicityType);
        self::validateStreak($courseId, $name, $description, $color, $goal, $periodicityGoal, $periodicityNumber,
            $periodicityTime, $periodicityType, $reward, $tokens, $isExtra, $isRepeatable);

        // Create streak rule
        $rule = self::addRule($courseId, $name, $description, $periodicityNumber, $periodicityTime, $periodicityType);

        // Insert in database
        $id = Core::database()->insert(self::TABLE_STREAK, [
            "course" => $courseId,
            "name" => $name,
            "description" => $description,
            "color" => $color,
            "goal" => $goal,
            "periodicityGoal" => $periodicityGoal,
            "periodicityNumber" => $periodicityNumber,
            "periodicityTime" => $periodicityTime,
            "periodicityType" => $periodicityType,
            "reward" => $reward,
            "tokens" => $tokens,
            "isExtra" => +$isExtra,
            "isRepeatable" => +$isRepeatable,
            "rule" => $rule->getId()
        ]);
        return new Streak($id);
    }

    /**
     * Edits an existing streak in the database.
     * Returns the edited streak.
     *
     * @param string $name
     * @param string|null $description
     * @param string|null $color
     * @param int $goal
     * @param int|null $periodicityGoal
     * @param int|null $periodicityNumber
     * @param string|null $periodicityTime
     * @param string|null $periodicityType
     * @param int $reward
     * @param int|null $tokens
     * @param bool $isExtra
     * @param bool $isRepeatable
     * @param bool $isActive
     * @return Streak
     * @throws Exception
     */
    public function editStreak(string $name, string $description, ?string $color, int $goal, ?int $periodicityGoal,
                               ?int $periodicityNumber, ?string $periodicityTime, ?string $periodicityType, int $reward,
                               int $tokens, bool $isExtra, bool $isRepeatable, bool $isActive): Streak
    {
        $this->setData([
            "name" => $name,
            "description" => $description,
            "color" => $color,
            "goal" => $goal,
            "periodicityGoal" => $periodicityGoal,
            "periodicityNumber" => $periodicityNumber,
            "periodicityTime" => $periodicityTime,
            "periodicityType" => $periodicityType,
            "reward" => $reward,
            "tokens" => $tokens,
            "isExtra" => +$isExtra,
            "isRepeatable" => +$isRepeatable,
            "isActive" => +$isActive
        ]);
        return $this;
    }

    /**
     * Copies an existing streak into another given course.
     *
     * @param Course $copyTo
     * @return void
     * @throws Exception
     */
    public function copyStreak(Course $copyTo): Streak
    {
        $streakInfo = $this->getData();

        // Copy streak
        $copiedStreak = self::addStreak($copyTo->getId(), $streakInfo["name"], $streakInfo["description"], $streakInfo["color"],
            $streakInfo["goal"], $streakInfo["periodicityGoal"], $streakInfo["periodicityNumber"], $streakInfo["periodicityTime"],
            $streakInfo["periodicityType"], $streakInfo["reward"], $streakInfo["tokens"], $streakInfo["isExtra"], $streakInfo["isRepeatable"]);
        $copiedStreak->setActive($streakInfo["isActive"]);

        // Copy rule
        $this->getRule()->mirrorRule($copiedStreak->getRule());

        return $copiedStreak;
    }

    /**
     * Deletes a streak from the database.
     *
     * @param int $streakId
     * @return void
     * @throws Exception
     */
    public static function deleteStreak(int $streakId) {
        $streak = self::getStreakById($streakId);
        if ($streak) {
            $courseId = $streak->getCourse()->getId();

            // Remove streak rule
            self::removeRule($courseId, $streak->getRule()->getId());

            // Delete streak from database
            Core::database()->delete(self::TABLE_STREAK, ["id" => $streakId]);
        }
    }

    /**
     * Checks whether streak exists.
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
     * Gets badge rule.
     *
     * @return Rule
     */
    public function getRule(): Rule
    {
        return Rule::getRuleById($this->getData("rule"));
    }

    /**
     * Adds a new streak rule to the Rule System.
     * Returns the newly created rule.
     *
     * @param int $courseId
     * @param string $name
     * @param string $description
     * @param int|null $periodicityNumber
     * @param string|null $periodicityTime
     * @param string|null $periodicityType
     * @return Rule
     * @throws Exception
     */
    private static function addRule(int $courseId, string $name, string $description, ?int $periodicityNumber,
                                    ?string $periodicityTime, ?string $periodicityType): Rule
    {
        // Add rule to streaks section
        $streaksModule = new Streaks(new Course($courseId));
        return $streaksModule->addRuleOfItem(null, $name, $description, $periodicityNumber, $periodicityTime, $periodicityType);
    }

    /**
     * Updates streak rule in the Rule System.
     *
     * @param int $ruleId
     * @param string $name
     * @param string $description
     * @param int|null $periodicityNumber
     * @param string|null $periodicityTime
     * @param string|null $periodicityType
     * @return void
     * @throws Exception
     */
    private static function updateRule(int $ruleId, string $name, string $description, ?int $periodicityNumber,
                                       ?string $periodicityTime, ?string $periodicityType)
    {
        $rule = new Rule($ruleId);
        $params = self::generateRuleParams($name, $description, $periodicityNumber, $periodicityTime, $periodicityType, false, $ruleId);
        $rule->setName($params["name"]);
        $rule->setDescription($params["description"]);
        $rule->setWhen($params["when"]);
        $rule->setThen($params["then"]);
    }

    /**
     * Deletes streak rule from the Rule System.
     *
     * @param int $courseId
     * @param int $ruleId
     * @return void
     * @throws Exception
     */
    private static function removeRule(int $courseId, int $ruleId)
    {
        $streaksModule = new Streaks(new Course($courseId));
        $streaksModule->deleteRuleOfItem($ruleId);
    }

    /**
     * Generates streak rule parameters.
     * Option to generate params fresh from templates, or to keep
     * existing data intact.
     *
     * @param string $name
     * @param string $description
     * @param int|null $periodicityNumber
     * @param string|null $periodicityTime
     * @param string|null $periodicityType
     * @param bool $fresh
     * @param int|null $ruleId
     * @return array
     * @throws Exception
     */
    public static function generateRuleParams(string $name, string $description, ?int $periodicityNumber,
                                              ?string $periodicityTime, ?string $periodicityType, bool $fresh = true,
                                              int $ruleId = null): array
    {
        $isPeriodic = !is_null($periodicityNumber) && !is_null($periodicityTime);

        // Generate rule clauses
        if ($fresh) { // generate from templates
            $when = file_get_contents(__DIR__ . "/rules/" . ($isPeriodic ? "periodic" : "consecutive") . "/when_template.txt");
            $then = file_get_contents(__DIR__ . "/rules/" . ($isPeriodic ? "periodic" : "consecutive") . "/then_template.txt");

            // Fill-in streak name
            $then = str_replace("<streak-name>", $name, $then);

            // Fill-in streak info
            if ($isPeriodic) {
                $when = str_replace("<period-number>", $periodicityNumber, $when);
                $when = str_replace("<period-time>", $periodicityTime, $when);
                $when = str_replace("<period-type>", $periodicityType, $when);
            }

        } else { // keep data intact
            if (is_null($ruleId))
                throw new Exception("Can't generate rule parameters for streak: no rule ID found.");

            $rule = Rule::getRuleById($ruleId);
            $when = $rule->getWhen();
            $then = $rule->getThen();

            if ($isPeriodic) {
                $wasConsecutive = strpos($when, "get_consecutive");
                if ($wasConsecutive) {
                    $when = preg_replace("/# Get .* consecutive(.|\n)*/", "# Get only periodic progress
        plogs = get_periodic_logs(logs, $periodicityNumber, \"$periodicityTime\", \"$periodicityType\")", $when);
                    $then = str_replace("plogs", "clogs", $then);

                } else {
                    preg_match('/get_periodic_logs\((.*)\)/', $when, $matches);
                    $args = explode(", ", $matches[1]);
                    $args[1] = "$periodicityNumber";
                    $args[2] = "\"$periodicityTime\"";
                    $args[3] = "\"$periodicityType\"";
                    $args = implode(", ", $args);
                    $when = preg_replace("/get_periodic_logs\((.*)\)/", "get_periodic_logs($args)", $when);
                }

            } else {
                $wasPeriodic = strpos($when, "get_periodic");
                if ($wasPeriodic)
                    return self::generateRuleParams($name, $description, null, null,
                        null, true, $ruleId);
            }

            preg_match('/award_streak\((.*)\)/', $then, $matches);
            $args = explode(", ", $matches[1]);
            $args[1] = "\"$name\"";
            $args = implode(", ", $args);
            $then = preg_replace("/award_streak\((.*)\)/", "award_streak($args)", $then);
        }

        return ["name" => $name, "description" => $description, "when" => $when, "then" => $then];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports streaks into a given course from a .csv file.
     * Returns the nr. of streaks imported.
     *
     * @param int $courseId
     * @param string $file
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importStreaks(int $courseId, string $file, bool $replace = true): int
    {
        return Utils::importFromCSV(self::HEADERS, function ($streak, $indexes) use ($courseId, $replace) {
            $name = Utils::nullify($streak[$indexes["name"]]);
            $description = Utils::nullify($streak[$indexes["description"]]);
            $color = Utils::nullify($streak[$indexes["color"]]);
            $goal = self::parse(null, $streak[$indexes["goal"]], "goal");
            $periodicityGoal = self::parse(null, $streak[$indexes["periodicityGoal"]], "periodicityGoal");
            $periodicityNumber = self::parse(null, $streak[$indexes["periodicityNumber"]], "periodicityNumber");
            $periodicityTime = Utils::nullify($streak[$indexes["periodicityTime"]]);
            $periodicityType = Utils::nullify($streak[$indexes["periodicityType"]]);
            $reward = self::parse(null, $streak[$indexes["reward"]], "reward");
            $tokens = self::parse(null, $streak[$indexes["tokens"]], "tokens");
            $isExtra = self::parse(null, $streak[$indexes["isExtra"]], "isExtra");
            $isRepeatable = self::parse(null, $streak[$indexes["isRepeatable"]], "isRepeatable");
            $isActive = self::parse(null, $streak[$indexes["isActive"]], "isActive");

            $streak = self::getStreakByName($courseId, $name);
            if ($streak) {  // streak already exists
                if ($replace)  // replace
                    $streak->editStreak($name, $description, $color, $goal, $periodicityGoal, $periodicityNumber,
                        $periodicityTime, $periodicityType, $reward, $tokens, $isExtra, $isRepeatable, $isActive);

            } else {  // streak doesn't exist
                Streak::addStreak($courseId, $name, $description, $color, $goal, $periodicityGoal, $periodicityNumber,
                    $periodicityTime, $periodicityType, $reward, $tokens, $isExtra, $isRepeatable);
                return 1;
            }
            return 0;
        }, $file);
    }

    /**
     * Exports streaks from a given course into a .csv file.
     *
     * @param int $courseId
     * @param array $streakIds
     * @return array
     */
    public static function exportStreaks(int $courseId, array $streakIds): array
    {
        $streaksToExport = array_values(array_filter(self::getStreaks($courseId), function ($streak) use ($streakIds) { return in_array($streak["id"], $streakIds); }));
        return ["extension" => ".csv", "file" => Utils::exportToCSV($streaksToExport, function ($streak) {
            return [$streak["name"], $streak["description"], $streak["color"], $streak["goal"], $streak["periodicityGoal"],
                $streak["periodicityNumber"], $streak["periodicityTime"], $streak["periodicityType"], $streak["reward"],
                $streak["tokens"], $streak["isExtra"], $streak["isRepeatable"], $streak["isActive"]];
        }, self::HEADERS)];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates streak parameters.
     *
     * @param int $courseId
     * @param $name
     * @param $description
     * @param $color
     * @param $goal
     * @param $periodicityGoal
     * @param $periodicityNumber
     * @param $periodicityTime
     * @param $periodicityType
     * @param $reward
     * @param $tokens
     * @param $isExtra
     * @param $isRepeatable
     * @return void
     * @throws Exception
     */
    private static function validateStreak(int $courseId, $name, $description, $color, $goal, $periodicityGoal, $periodicityNumber,
                                           $periodicityTime, $periodicityType, $reward, $tokens, $isExtra, $isRepeatable)
    {
        self::validateName($courseId, $name);
        self::validateDescription($description);
        self::validateColor($color);
        self::validateInteger("goal", $goal);
        if (!is_null($periodicityGoal)) self::validateInteger("periodicityGoal", $periodicityGoal);
        if (!is_null($periodicityNumber)) self::validateInteger("periodicityNumber", $periodicityNumber);
        self::validatePeriodicityTime($periodicityTime);
        self::validatePeriodicityType($periodicityType);
        self::validateInteger("reward", $reward);
        self::validateInteger("tokens", $tokens);
        if (!is_bool($isExtra)) throw new Exception("'isExtra' must be either true or false.");
        if (!is_bool($isRepeatable)) throw new Exception("'isRepeatable' must be either true or false.");
    }

    /**
     * Validates streak name.
     *
     * @param int $courseId
     * @param $name
     * @param int|null $streakId
     * @return void
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $streakId = null)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Streak name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Streak name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Streak name is too long: maximum of 50 characters.");

        $whereNot = [];
        if ($streakId) $whereNot[] = ["id", $streakId];
        $streakNames = array_column(Core::database()->selectMultiple(self::TABLE_STREAK, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $streakNames))
            throw new Exception("Duplicate streak name: '$name'");
    }

    /**
     * Validates streak description.
     *
     * @param $description
     * @return void
     * @throws Exception
     */
    private static function validateDescription($description)
    {
        if (!is_string($description) || empty(trim($description)))
            throw new Exception("Streak description can't be null neither empty.");

        if (is_numeric($description))
            throw new Exception("Streak description can't be composed of only numbers.");

        if (iconv_strlen($description) > 150)
            throw new Exception("Streak description is too long: maximum of 150 characters.");
    }

    /**
     * Validates streak color.
     *
     * @throws Exception
     */
    private static function validateColor($color)
    {
        if (is_null($color)) return;

        if (!is_string($color) || empty($color))
            throw new Exception("Streak color can't be empty.");

        if (!Utils::isValidColor($color, "HEX"))
            throw new Exception("Streak color needs to be in HEX format.");
    }

    /**
     * Validates streak periodicity time.
     *
     * @throws Exception
     */
    private static function validatePeriodicityTime($time)
    {
        if (is_null($time)) return;

        if (!is_string($time) || empty($time))
            throw new Exception("Streak periodicity time can't be empty.");

        if (!Time::exists($time))
            throw new Exception("Streak periodicity time doesn't exist.");
    }

    /**
     * Validates streak periodicity type.
     *
     * @throws Exception
     */
    private static function validatePeriodicityType($type)
    {
        if (is_null($type)) return;

        if (!is_string($type) || empty($type))
            throw new Exception("Streak periodicity type can't be empty.");

        if (!in_array($type, ["absolute", "relative"]))
            throw new Exception("Streak periodicity type doesn't exist.");
    }

    /**
     * Validates streak value is >= 0.
     *
     * @param string $param
     * @param $value
     * @return void
     * @throws Exception
     */
    private static function validateInteger(string $param, $value)
    {
        if (is_null($value))
            throw new Exception("Streak $param can't be null neither empty.");

        if (!is_numeric($value))
            throw new Exception("Streak $param needs to be a number.");

        if ($value < 0)
            throw new Exception("Streak $param needs to be bigger or equal to zero.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a streak coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $streak
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $streak = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "goal", "periodicityGoal", "periodicityNumber", "reward", "tokens", "rule"];
        $boolValues = ["isExtra", "isRepeatable", "isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $streak, $field, $fieldName);
    }

    /**
     * Trims streak parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "description", "color", "periodicityTime", "periodicityType"];
        Utils::trim($params, ...$values);
    }
}
