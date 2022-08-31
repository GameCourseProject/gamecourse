<?php
namespace GameCourse\Module\Streaks;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;
use Utils\Utils;

/**
 * This is the Streak model, which implements the necessary methods
 * to interact with streaks in the MySQL database.
 */
class Streak
{
    const TABLE_STREAK = 'streak';
    const TABLE_STREAK_PROGRESSION = 'streak_progression';
    const TABLE_STREAK_PARTICIPATIONS = 'streak_participations';

    const HEADERS = [   // headers for import/export functionality
        "name", "description", "color", "count", "periodicity", "periodicityTime", "reward", "tokens", "isRepeatable",
        "isCount", "isPeriodic", "isAtMost", "isExtra", "isActive"
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

    public function getCount(): int
    {
        return $this->getData("count");
    }

    public function getPeriodicity(): ?int
    {
        return $this->getData("periodicity");
    }

    public function getPeriodicityTime(): ?string
    {
        return $this->getData("periodicityTime");
    }

    public function getReward(): int
    {
        return $this->getData("reward");
    }

    /**
     * @throws Exception
     */
    public function getTokens(): ?int
    {
        $virtualCurrencyModule = $this->getCourse()->getModuleById(VirtualCurrency::ID);
        if ($virtualCurrencyModule && $virtualCurrencyModule->isEnabled())
            return $this->getData("tokens");
        return null;
    }

    public function getImage(): string
    {
        $img = file_get_contents(self::FULL_IMAGE);

        // Change image color
        $color = $this->getColor();
        if ($color) $img = preg_replace("/fill=\"(.*?)\"/", "fill=\"$color\"", $img);

        return "data:image/svg+xml;base64," . base64_encode($img);
    }

    public function isRepeatable(): bool
    {
        return $this->getData("isRepeatable");
    }

    public function isCount(): bool
    {
        return $this->getData("isCount");
    }

    public function isPeriodic(): bool
    {
        return $this->getData("isPeriodic");
    }

    public function isAtMost(): bool
    {
        return $this->getData("isAtMost");
    }

    public function isExtra(): bool
    {
        return $this->getData("isExtra");
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
    public function setCount(int $count)
    {
        $this->setData(["count" => $count]);
    }

    /**
     * @throws Exception
     */
    public function setPeriodicity(?int $periodicity)
    {
        $this->setData(["periodicity" => $periodicity]);
    }

    /**
     * @throws Exception
     */
    public function setPeriodicityTime(?string $periodicityTime)
    {
        $this->setData(["periodicityTime" => $periodicityTime]);
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
    public function setTokens(?int $tokens)
    {
        $this->setData(["tokens" => $tokens]);
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
    public function setIsCount(bool $isCount)
    {
        $this->setData(["isCount" => +$isCount]);
    }

    /**
     * @throws Exception
     */
    public function setPeriodic(bool $isPeriodic)
    {
        $this->setData(["isPeriodic" => +$isPeriodic]);
    }

    /**
     * @throws Exception
     */
    public function setIsAtMost(bool $isAtMost)
    {
        $this->setData(["isAtMost" => +$isAtMost]);
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
        $rule = $this->getRule();

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
        if (key_exists("color", $fieldValues)) self::validateColor($fieldValues["color"]);
        if (key_exists("count", $fieldValues)) self::validateInteger("count", $fieldValues["count"]);
        if (key_exists("periodicity", $fieldValues) && !is_null($fieldValues["periodicity"])) self::validateInteger("periodicity", $fieldValues["periodicity"]);
        if (key_exists("reward", $fieldValues)) self::validateInteger("reward", $fieldValues["reward"]);
        if (key_exists("tokens", $fieldValues)) {
            $course = $this->getCourse();
            $virtualCurrencyModule = $course->getModuleById(VirtualCurrency::ID);
            if (!$virtualCurrencyModule || !$virtualCurrencyModule->isEnabled())
                throw new Exception($virtualCurrencyModule::NAME . " is not enabled for course with ID = " . $course->getId() . ".");

            $newTokens = $fieldValues["tokens"];
            if (!is_null($newTokens)) self::validateInteger("tokens", $newTokens);
        }
        if (key_exists("isExtra", $fieldValues) && $fieldValues["isExtra"]) {
            $course = $this->getCourse();
            $xpLevelsModule = new XPLevels($course);
            if ($xpLevelsModule->isEnabled() && !$xpLevelsModule->getMaxExtraCredit())
                throw new Exception("You're attempting to set a streak as extra credit while there's no extra credit available to earn. Go to " . XPLevels::NAME . " module and set a max. global extra credit value first.");

            $streaksModule = new Streaks($course);
            if (!$streaksModule->getMaxExtraCredit())
                throw new Exception("You're attempting to set a streak as extra credit while there's no streak extra credit available to earn. Set a max. streak extra credit value first.");
        }
        if (key_exists("isActive", $fieldValues)) {
            $newStatus = $fieldValues["isActive"];
            $oldStatus = $this->isActive();
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_STREAK, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("name", $fieldValues)) {
            if (strcmp($oldName, $newName) !== 0) {
                // Update badge folder
                rename($this->getDataFolder(true, $oldName), $this->getDataFolder(true, $newName));
            }
        }
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
            $tokens = key_exists("tokens", $fieldValues) ? $newTokens : $this->getTokens();
            self::updateRule($rule->getId(), $name, $description, $tokens);
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
     * @param int $count
     * @param int|null $periodicity
     * @param string|null $periodicityTime
     * @param int $reward
     * @param int|null $tokens
     * @param bool $isRepeatable
     * @param bool $isCount
     * @param bool $isPeriodic
     * @param bool $isAtMost
     * @param bool $isExtra
     * @return Streak
     * @throws Exception
     */
    public static function addStreak(int $courseId, string $name, string $description, ?string $color, int $count,
                                     ?int $periodicity, ?string $periodicityTime, int $reward, ?int $tokens,
                                     bool $isRepeatable, bool $isCount, bool $isPeriodic, bool $isAtMost, bool $isExtra): Streak
    {
        self::validateStreak($name, $description, $color, $count, $periodicity, $reward, $tokens, $isRepeatable, $isCount, $isPeriodic, $isAtMost, $isExtra);

        // Create streak rule
        $rule = self::addRule($courseId, $name, $description, $tokens);

        // Insert in database
        $id = Core::database()->insert(self::TABLE_STREAK, [
            "course" => $courseId,
            "name" => $name,
            "description" => $description,
            "color" => $color,
            "count" => $count,
            "periodicity" => $periodicity,
            "periodicityTime" => $periodicityTime,
            "reward" => $reward,
            "tokens" => $tokens,
            "isRepeatable" => +$isRepeatable,
            "isCount" => +$isCount,
            "isPeriodic" => +$isPeriodic,
            "isAtMost" => +$isAtMost,
            "isExtra" => +$isExtra,
            "rule" => $rule->getId()
        ]);

        // Create streak data folder
        self::createDataFolder($courseId, $name);

        return new Streak($id);
    }

    /**
     * Edits an existing streak in the database.
     * Returns the edited streak.
     *
     * @param string $name
     * @param string|null $description
     * @param string|null $color
     * @param int $count
     * @param int|null $periodicity
     * @param string|null $periodicityTime
     * @param int $reward
     * @param int|null $tokens
     * @param bool $isRepeatable
     * @param bool $isCount
     * @param bool $isPeriodic
     * @param bool $isAtMost
     * @param bool $isExtra
     * @param bool $isActive
     * @return Streak
     * @throws Exception
     */
    public function editStreak(string $name, string $description, ?string $color, int $count, ?int $periodicity,
                               ?string $periodicityTime, int $reward, ?int $tokens, bool $isRepeatable, bool $isCount,
                               bool $isPeriodic, bool $isAtMost, bool $isExtra, bool $isActive): Streak
    {
        $this->setData([
            "name" => $name,
            "description" => $description,
            "color" => $color,
            "count" => $count,
            "periodicity" => $periodicity,
            "periodicityTime" => $periodicityTime,
            "reward" => $reward,
            "tokens" => $tokens,
            "isRepeatable" => +$isRepeatable,
            "isCount" => +$isCount,
            "isPeriodic" => +$isPeriodic,
            "isAtMost" => +$isAtMost,
            "isExtra" => +$isExtra,
            "isActive" => +$isActive
        ]);
        return $this;
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

            // Remove streak data folder
            self::removeDataFolder($courseId, $streak->getName());

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
     * @param int|null $tokens
     * @return Rule
     * @throws Exception
     */
    private static function addRule(int $courseId, string $name, string $description, ?int $tokens): Rule
    {
        // Add rule to streaks section
        $streaksModule = new Streaks(new Course($courseId));
        return $streaksModule->addRuleOfItem(null, $name, $description, $tokens);
    }

    /**
     * Updates streak rule in the Rule System.
     *
     * @param int $ruleId
     * @param string $name
     * @param string $description
     * @param int|null $tokens
     * @return void
     * @throws Exception
     */
    private static function updateRule(int $ruleId, string $name, string $description, ?int $tokens)
    {
        $rule = new Rule($ruleId);
        $params = self::generateRuleParams($name, $description, $tokens, false, $ruleId);
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
     * @param int|null $tokens
     * @param bool $fresh
     * @param int|null $ruleId
     * @return array
     * @throws Exception
     */
    public static function generateRuleParams(string $name, string $description, ?int $tokens, bool $fresh = true,
                                              int $ruleId = null): array
    {
        // Generate rule clauses
        if ($fresh) { // generate from templates
            $when = file_get_contents(__DIR__ . "/rules/when_template.txt");
            $then = file_get_contents(__DIR__ . "/rules/then_template.txt");

            // Fill-in streak name
            $then = str_replace("<streak-name>", $name, $then);

            // Fill-in streak tokens
            $then = preg_replace("/(\r*\n)*<award-tokens>/", !is_null($tokens) ? "\naward_tokens_type(target, \"streak\", $tokens, \"$name\", progress)" : "", $then);

        } else { // keep data intact
            if (is_null($ruleId))
                throw new Exception("Can't generate rule parameters for streak: no rule ID found.");

            $rule = Rule::getRuleById($ruleId);
            $when = $rule->getWhen();

            $then = $rule->getThen();
            preg_match('/award_streak\((.*)\)/', $then, $matches);
            $args = explode(", ", $matches[1]);
            $args[1] = "\"$name\"";
            $args = implode(", ", $args);
            $then = preg_replace("/award_streak\((.*)\)/", "award_streak($args)", $then);

            preg_match('/award_tokens_type\((.*)\)/', $then, $matches);
            if (!is_null($tokens)) {
                if (empty($matches))
                    $then .= "\naward_tokens_type(target, \"streak\", $tokens, \"$name\", progress)";
                else {
                    $args = explode(", ", $matches[1]);
                    $args[2] = $tokens;
                    $args[3] = "\"$name\"";
                    $args = implode(", ", $args);
                    $then = preg_replace("/award_tokens_type\((.*)\)/", "award_tokens_type($args)", $then);
                }

            } else {
                if (!empty($matches))
                    $then = preg_replace("/award_tokens_type\((.*)\)(\r*\n*)*/", "", $then);
            }
        }

        return ["name" => $name, "description" => $description, "when" => $when, "then" => $then];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Streak Data -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets streak data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param bool $fullPath
     * @param string|null $streakName
     * @return string
     */
    public function getDataFolder(bool $fullPath = true, string $streakName = null): string
    {
        if (!$streakName) $streakName = $this->getName();
        $streaksModule = new Streaks($this->getCourse());
        return $streaksModule->getDataFolder($fullPath) . "/" . Utils::strip($streakName, "_");
    }

    /**
     * Gets streak data folder contents.
     *
     * @return array
     * @throws Exception
     */
    public function getDataFolderContents(): array
    {
        return Utils::getDirectoryContents($this->getDataFolder());
    }

    /**
     * Creates a data folder for a given streak. If folder exists, it
     * will delete its contents.
     *
     * @param int $courseId
     * @param string $streakName
     * @return string
     * @throws Exception
     */
    public static function createDataFolder(int $courseId, string $streakName): string
    {
        $dataFolder = self::getStreakByName($courseId, $streakName)->getDataFolder();
        if (file_exists($dataFolder)) self::removeDataFolder($courseId, $streakName);
        mkdir($dataFolder, 0777, true);
        return $dataFolder;
    }

    /**
     * Deletes a given streak's data folder.
     *
     * @param int $courseId
     * @param string $streakName
     * @return void
     * @throws Exception
     */
    public static function removeDataFolder(int $courseId, string $streakName)
    {
        $dataFolder = self::getStreakByName($courseId, $streakName)->getDataFolder();
        if (file_exists($dataFolder)) Utils::deleteDirectory($dataFolder);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates streak parameters.
     *
     * @param $name
     * @param $description
     * @param $color
     * @param $count
     * @param $periodicity
     * @param $reward
     * @param $tokens
     * @param $isRepeatable
     * @param $isCount
     * @param $isPeriodic
     * @param $isAtMost
     * @param $isExtra
     * @return void
     * @throws Exception
     */
    private static function validateStreak($name, $description, $color, $count, $periodicity, $reward, $tokens, $isRepeatable, $isCount, $isPeriodic, $isAtMost, $isExtra)
    {
        self::validateName($name);
        self::validateDescription($description);
        self::validateColor($color);
        self::validateInteger("count", $count);
        if (!is_null($periodicity)) self::validateInteger("periodicity", $periodicity);
        self::validateInteger("reward", $reward);
        if (!is_null($tokens)) self::validateInteger("tokens", $tokens);
        if (!is_bool($isRepeatable)) throw new Exception("'isRepeatable' must be either true or false.");
        if (!is_bool($isCount)) throw new Exception("'isCount' must be either true or false.");
        if (!is_bool($isPeriodic)) throw new Exception("'isPeriodic' must be either true or false.");
        if (!is_bool($isAtMost)) throw new Exception("'isAtMost' must be either true or false.");
        if (!is_bool($isExtra)) throw new Exception("'isExtra' must be either true or false.");
    }

    /**
     * Validates streak name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Streak name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Streak name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Streak name is too long: maximum of 50 characters.");
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
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    public static function parse(array $streak = null, $field = null, string $fieldName = null)
    {
        if ($streak) {
            if (isset($streak["id"])) $streak["id"] = intval($streak["id"]);
            if (isset($streak["course"])) $streak["course"] = intval($streak["course"]);
            if (isset($streak["count"])) $streak["count"] = intval($streak["count"]);
            if (isset($streak["periodicity"])) $streak["periodicity"] = intval($streak["periodicity"]);
            if (isset($streak["reward"])) $streak["reward"] = intval($streak["reward"]);
            if (isset($streak["tokens"])) $streak["tokens"] = intval($streak["tokens"]);
            if (isset($streak["rule"])) $streak["rule"] = intval($streak["rule"]);
            if (isset($streak["isRepeatable"])) $streak["isRepeatable"] = boolval($streak["isRepeatable"]);
            if (isset($streak["isCount"])) $streak["isCount"] = boolval($streak["isCount"]);
            if (isset($streak["isPeriodic"])) $streak["isPeriodic"] = boolval($streak["isPeriodic"]);
            if (isset($streak["isAtMost"])) $streak["isAtMost"] = boolval($streak["isAtMost"]);
            if (isset($streak["isExtra"])) $streak["isExtra"] = boolval($streak["isExtra"]);
            if (isset($streak["isActive"])) $streak["isActive"] = boolval($streak["isActive"]);
            return $streak;

        } else {
            if ($fieldName == "id" || $fieldName == "course" || $fieldName == "count" || $fieldName == "periodicity"
                || $fieldName == "reward" || $fieldName == "tokens" || $fieldName == "rule")
                return is_numeric($field) ? intval($field) : $field;
            if ($fieldName == "isRepeatable" || $fieldName == "isCount" || $fieldName == "isPeriodic" || $fieldName == "isAtMost"
                || $fieldName == "isExtra" || $fieldName == "isActive") return boolval($field);
            return $field;
        }
    }
}
