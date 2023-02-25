<?php
namespace GameCourse\Module\Badges;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;
use ZipArchive;

/**
 * This is the Badge model, which implements the necessary methods
 * to interact with badges in the MySQL database.
 */
class Badge
{
    const TABLE_BADGE = 'badge';
    const TABLE_BADGE_LEVEL = 'badge_level';
    const TABLE_BADGE_PROGRESSION = 'badge_progression';

    const HEADERS = [   // headers for import/export functionality
        "name", "description", "nrLevels", "isExtra", "isBragging", "isCount", "isPost", "isPoint", "isActive"
    ];
    const LEVEL_HEADERS = [
        "badge_name", "number", "goal", "description", "reward", "tokens"
    ];

    const DEFAULT_IMAGE = "blank.png";
    const DEFAULT_IMAGE_EXTRA = "blank_extra.png";

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

    public function getNrLevels(): int
    {
        return $this->getData("nrLevels");
    }

    public function getImage(): string
    {
        if ($this->hasImage()) return API_URL . "/" . $this->getCourse()->getDataFolder(false) . "/" .
            $this->getDataFolder(false) . "/badge.png";
        else return API_URL . "/" . Utils::getDirectoryName(MODULES_FOLDER) . "/" . Badges::ID . "/assets/" .
                ($this->isExtra() ? self::DEFAULT_IMAGE_EXTRA : self::DEFAULT_IMAGE);
    }

    public function hasImage(): bool
    {
        return file_exists($this->getDataFolder() . "/badge.png");
    }

    public function isExtra(): bool
    {
        return $this->getData("isExtra");
    }

    public function isBragging(): bool
    {
        return $this->getData("isBragging");
    }

    public function isCount(): bool
    {
        return $this->getData("isCount");
    }

    public function isPost(): bool
    {
        return $this->getData("isPost");
    }

    public function isPoint(): bool
    {
        return $this->getData("isPoint");
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Gets badge data from the database.
     *
     * @example getData() --> gets all badge data
     * @example getData("name") --> gets badge name
     * @example getData("name, description") --> gets badge name & description
     *
     * @param string $field
     * @return array|int|string|boolean|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_BADGE;
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
    public function setImage(string $base64)
    {
        Utils::uploadFile($this->getDataFolder(), $base64, "badge.png");
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
    public function setBragging(bool $isBragging)
    {
        $this->setData(["isBragging" => +$isBragging]);
    }

    /**
     * @throws Exception
     */
    public function setCount(bool $isCount)
    {
        $this->setData(["isCount" => +$isCount]);
    }

    /**
     * @throws Exception
     */
    public function setPost(bool $isPost)
    {
        $this->setData(["isPost" => +$isPost]);
    }

    /**
     * @throws Exception
     */
    public function setPoint(bool $isPoint)
    {
        $this->setData(["isPoint" => +$isPoint]);
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
            $oldName = $this->getName();
        }
        if (key_exists("description", $fieldValues)) {
            $newDescription = $fieldValues["description"];
            self::validateDescription($newDescription);
        }
        if (key_exists("isActive", $fieldValues)) {
            $newStatus = $fieldValues["isActive"];
            $oldStatus = $this->isActive();
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_BADGE, $fieldValues, ["id" => $this->id]);

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
        if (key_exists("isBragging", $fieldValues) && $fieldValues["isBragging"]) {
            // Set all levels' reward as 0
            $levels = array_map(function ($level) {
                $level["reward"] = 0;
                return $level;
            }, $this->getLevels());
            $this->setLevels($levels, $fieldValues["isBragging"]);
        }
        if (key_exists("name", $fieldValues) || key_exists("description", $fieldValues)) {
            // Update badge rule
            $name = key_exists("name", $fieldValues) ? $newName : $this->getName();
            $description = key_exists("description", $fieldValues) ? $newDescription : $this->getDescription();
            $isPoint = key_exists("isPoint", $fieldValues) ? $fieldValues["isPoint"] : $this->isPoint();
            self::updateRule($rule->getId(), $name, $description, $isPoint, $this->getLevels());
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a badge by its ID.
     * Returns null if badge doesn't exist.
     *
     * @param int $id
     * @return Badge|null
     */
    public static function getBadgeById(int $id): ?Badge
    {
        $badge = new Badge($id);
        if ($badge->exists()) return $badge;
        else return null;
    }

    /**
     * Gets a badge by its name.
     * Returns null if badge doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return Badge|null
     */
    public static function getBadgeByName(int $courseId, string $name): ?Badge
    {
        $badgeId = intval(Core::database()->select(self::TABLE_BADGE, ["course" => $courseId, "name" => $name], "id"));
        if (!$badgeId) return null;
        else return new Badge($badgeId);
    }

    /**
     * Gets all badges of course.
     * Option for 'active' and ordering.
     *
     * @param int $courseId
     * @param bool|null $active
     * @param string $orderBy
     * @return array
     */
    public static function getBadges(int $courseId, bool $active = null, string $orderBy = "name"): array
    {
        $where = ["course" => $courseId];
        if ($active !== null) $where["isActive"] = $active;
        $badges = Core::database()->selectMultiple(self::TABLE_BADGE, $where, "*", $orderBy);
        foreach ($badges as &$badgeInfo) {
            $badgeInfo = self::parse($badgeInfo);

            // Get image
            $badge = new Badge($badgeInfo["id"]);
            $badgeInfo["image"] = $badge->getImage();

            // Get levels
            // NOTE: limit of 3 levels
            $levels = $badge->getLevels();
            foreach ($levels as $level) {
                $i = $level["number"];
                $badgeInfo["desc" . $i] = $level["description"];
                $badgeInfo["goal" . $i] = $level["goal"];
                $badgeInfo["reward" . $i] = $level["reward"];
                $badgeInfo["tokens" . $i] = $level["tokens"];
            }
        }
        return $badges;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Badge Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new badge to the database.
     * Returns the newly created badge.
     *
     * @param int $courseId
     * @param string $name
     * @param string|null $description
     * @param bool $isExtra
     * @param bool $isBragging
     * @param bool $isCount
     * @param bool $isPost
     * @param bool $isPoint
     * @param array $levels
     * @return Badge
     * @throws Exception
     */
    public static function addBadge(int $courseId, string $name, string $description, bool $isExtra,
                                    bool $isBragging, bool $isCount, bool $isPost, bool $isPoint, array $levels): Badge
    {
        self::trim($name, $description);
        self::validateBadge($courseId, $name, $description, $isExtra, $isBragging, $isCount, $isPost, $isPoint, $levels);

        // Create badge rule
        $rule = self::addRule($courseId, $name, $description, $isPoint, $levels);

        // Insert in database
        $id = Core::database()->insert(self::TABLE_BADGE, [
            "course" => $courseId,
            "name" => $name,
            "description" => $description,
            "isExtra" => +$isExtra,
            "isBragging" => +$isBragging,
            "isCount" => +$isCount,
            "isPost" => +$isPost,
            "isPoint" => +$isPoint,
            "rule" => $rule->getId()
        ]);
        $badge = new Badge($id);

        // Set badge levels
        $badge->setLevels($levels, $isBragging);

        // Create badge data folder
        self::createDataFolder($courseId, $name);

        return $badge;
    }

    /**
     * Edits an existing badge in the database.
     * Returns the edited badge.
     *
     * @param string $name
     * @param string|null $description
     * @param bool $isExtra
     * @param bool $isBragging
     * @param bool $isCount
     * @param bool $isPost
     * @param bool $isPoint
     * @param bool $isActive
     * @param array $levels
     * @return Badge
     * @throws Exception
     */
    public function editBadge(string $name, string $description, bool $isExtra, bool $isBragging,
                              bool $isCount, bool $isPost, bool $isPoint, bool $isActive, array $levels): Badge
    {
        $this->setData([
            "name" => $name,
            "description" => $description,
            "isExtra" => +$isExtra,
            "isBragging" => +$isBragging,
            "isCount" => +$isCount,
            "isPost" => +$isPost,
            "isPoint" => +$isPoint,
            "isActive" => +$isActive
        ]);
        $this->setLevels($levels, $isBragging);
        return $this;
    }

    /**
     * Copies an existing badge into another given course.
     *
     * @param Course $copyTo
     * @return void
     * @throws Exception
     */
    public function copyBadge(Course $copyTo): Badge
    {
        $badgeInfo = $this->getData();

        // Copy badge
        $levels = $this->getLevels();
        $copiedBadge = self::addBadge($copyTo->getId(), $badgeInfo["name"], $badgeInfo["description"], $badgeInfo["isExtra"],
            $badgeInfo["isBragging"], $badgeInfo["isCount"], $badgeInfo["isPost"], $badgeInfo["isPoint"], $levels);
        $copiedBadge->setActive($badgeInfo["isActive"]);

        // Copy image
        if ($this->hasImage()) {
            $path = $this->getDataFolder() . "/badge.png";
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
            $copiedBadge->setImage($base64);
        }

        // Copy rule
        $this->getRule()->mirrorRule($copiedBadge->getRule());

        return $copiedBadge;
    }

    /**
     * Deletes a badge from the database.
     *
     * @param int $badgeId
     * @return void
     * @throws Exception
     */
    public static function deleteBadge(int $badgeId) {
        $badge = self::getBadgeById($badgeId);
        if ($badge) {
            $courseId = $badge->getCourse()->getId();

            // Remove badge data folder
            self::removeDataFolder($courseId, $badge->getName());

            // Remove badge rule
            self::removeRule($courseId, $badge->getRule()->getId());

            // Delete badge from database
            Core::database()->delete(self::TABLE_BADGE, ["id" => $badgeId]);
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
    /*** ---------------------- Levels ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets badge levels.
     *
     * @return array
     */
    public function getLevels(): array
    {
        $levels = Core::database()->selectMultiple(self::TABLE_BADGE_LEVEL, ["badge" => $this->id], "id, number, goal, description, reward, tokens", "number");
        foreach ($levels as &$level) {
            $level["number"] = intval($level["number"]);
            $level["goal"] = intval($level["goal"]);
            $level["reward"] = intval($level["reward"]);
            $level["tokens"] = intval($level["tokens"]);
            $level["image"] = $this->getImage(); // FIXME: add overlay on image
        }
        return $levels;
    }

    /**
     * Sets badge levels.
     *
     * @param array $levels
     * @param bool $isBragging
     * @return void
     * @throws Exception
     */
    public function setLevels(array $levels, bool $isBragging = false)
    {
        self::validateLevels($levels, $isBragging);

        // Delete all levels
        Core::database()->delete(self::TABLE_BADGE_LEVEL, ["badge" => $this->id]);

        // Set new levels
        foreach ($levels as $i => $level) {
            self::trim($level["description"]);
            self::validateLevel($level["description"]);
            Core::database()->insert(self::TABLE_BADGE_LEVEL, [
                "badge" => $this->id,
                "number" => $i + 1,
                "goal" => $level["goal"],
                "description" => $level["description"],
                "reward" => !$isBragging ? $level["reward"] : 0,
                "tokens" => $level["tokens"] ?? 0
            ]);
        }
        Core::database()->update(self::TABLE_BADGE, ["nrLevels" => count($levels)], ["id" => $this->id]);

        // Update badge rule
        self::updateRule($this->getRule()->getId(), $this->getName(), $this->getDescription(), $this->isPost(), $levels);
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
     * Adds a new badge rule to the Rule System.
     * Returns the newly created rule.
     *
     * @param int $courseId
     * @param string $name
     * @param string $description
     * @param bool $isPoint
     * @param array $levels
     * @return Rule
     * @throws Exception
     */
    private static function addRule(int $courseId, string $name, string $description, bool $isPoint, array $levels): Rule
    {
        // Add rule to badges section
        $badgesModule = new Badges(new Course($courseId));
        return $badgesModule->addRuleOfItem(null, $name, $description, $isPoint, $levels);
    }

    /**
     * Updates badge rule in the Rule System.
     *
     * @param int $ruleId
     * @param string $name
     * @param string $description
     * @param bool $isPoint
     * @param array $levels
     * @return void
     * @throws Exception
     */
    private static function updateRule(int $ruleId, string $name, string $description, bool $isPoint, array $levels)
    {
        $rule = new Rule($ruleId);
        $params = self::generateRuleParams($name, $description, $isPoint, $levels, false, $ruleId);
        $rule->setName($params["name"]);
        $rule->setDescription($params["description"]);
        $rule->setWhen($params["when"]);
        $rule->setThen($params["then"]);
    }

    /**
     * Deletes badge rule from the Rule System.
     *
     * @param int $courseId
     * @param int $ruleId
     * @return void
     * @throws Exception
     */
    private static function removeRule(int $courseId, int $ruleId)
    {
        $badgesModule = new Badges(new Course($courseId));
        $badgesModule->deleteRuleOfItem($ruleId);
    }

    /**
     * Generates badge rule parameters.
     * Option to generate params fresh from templates, or to keep
     * existing data intact.
     *
     * @param string $name
     * @param string $description
     * @param bool $isPoint
     * @param array $levels
     * @param bool $fresh
     * @param int|null $ruleId
     * @return array
     * @throws Exception
     */
    public static function generateRuleParams(string $name, string $description, bool $isPoint, array $levels,
                                              bool $fresh = true, int $ruleId = null): array
    {
        // Generate description
        foreach ($levels as $i => $level) {
            $description .= "\n\tlvl." . ($i + 1) . ": " . $level["description"];
        }

        // Generate rule clauses
        $goals = implode(", ", array_column($levels, "goal"));
        if ($fresh) { // generate from templates
            $when = str_replace("<progress>", $isPoint ? "compute_rating(logs)" : "len(logs)", file_get_contents(__DIR__ . "/rules/when_template.txt"));
            $when = str_replace("<goals>", $goals, $when);
            $then = str_replace("<badge-name>", $name, file_get_contents(__DIR__ . "/rules/then_template.txt"));

        } else { // keep data intact
            if (is_null($ruleId))
                throw new Exception("Can't generate rule parameters for badge: no rule ID found.");

            $rule = Rule::getRuleById($ruleId);

            $when = $rule->getWhen();
            preg_match('/compute_lvl\((.*)\)/', $when, $matches);
            $progress = explode(", ", $matches[1])[0];
            $when = preg_replace("/compute_lvl\((.*)\)/", "compute_lvl($progress, $goals)", $when);

            $then = $rule->getThen();
            preg_match('/award_badge\((.*)\)/', $then, $matches);
            $args = explode(", ", $matches[1]);
            $args[1] = "\"$name\"";
            $args = implode(", ", $args);
            $then = preg_replace("/award_badge\((.*)\)/", "award_badge($args)", $then);
        }

        return ["name" => $name, "description" => $description, "when" => $when, "then" => $then];
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Badge Data -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets badge data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param bool $fullPath
     * @param string|null $badgeName
     * @return string
     */
    public function getDataFolder(bool $fullPath = true, string $badgeName = null): string
    {
        if (!$badgeName) $badgeName = $this->getName();
        $badgesModule = new Badges($this->getCourse());
        return $badgesModule->getDataFolder($fullPath) . "/" . Utils::strip($badgeName, "_");
    }

    /**
     * Gets badge data folder contents.
     *
     * @return array
     * @throws Exception
     */
    public function getDataFolderContents(): array
    {
        return Utils::getDirectoryContents($this->getDataFolder());
    }

    /**
     * Creates a data folder for a given badge. If folder exists, it
     * will delete its contents.
     *
     * @param int $courseId
     * @param string $badgeName
     * @return string
     * @throws Exception
     */
    public static function createDataFolder(int $courseId, string $badgeName): string
    {
        $dataFolder = self::getBadgeByName($courseId, $badgeName)->getDataFolder();
        if (file_exists($dataFolder)) self::removeDataFolder($courseId, $badgeName);
        mkdir($dataFolder, 0777, true);
        return $dataFolder;
    }

    /**
     * Deletes a given badges's data folder.
     *
     * @param int $courseId
     * @param string $badgeName
     * @return void
     * @throws Exception
     */
    public static function removeDataFolder(int $courseId, string $badgeName)
    {
        $dataFolder = self::getBadgeByName($courseId, $badgeName)->getDataFolder();
        if (file_exists($dataFolder)) Utils::deleteDirectory($dataFolder);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports badges into a given course from a .zip file.
     * Returns the nr. of badges imported.
     *
     * @param int $courseId
     * @param string $contents
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importBadges(int $courseId, string $contents, bool $replace = true): int
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Extract contents
        $zipPath = $tempFolder . "/badges.zip";
        Utils::uploadFile($tempFolder, $contents, "badges.zip");
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Exception("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        Utils::deleteFile($tempFolder, "badges.zip");

        // Get badges images
        $images = [];
        foreach (Utils::getDirectoryContents($tempFolder . "/images") as $file) {
            $fileName = explode(".", $file["name"])[0];
            if ($file["type"] != "file") throw new Exception("Badge image for '" . $fileName . "' is not a file.");
            $images[$fileName] = $tempFolder . "/images/" . $file["name"];
        }

        // Import badges levels
        $file = file_get_contents($tempFolder . "/levels.csv");
        $levels = [];
        Utils::importFromCSV(self::LEVEL_HEADERS, function ($level, $indexes) use ($courseId, $replace, &$levels) {
            $levels[$level[$indexes["badge_name"]]][] = [
                "number" => intval($level[$indexes["number"]]),
                "goal" => intval($level[$indexes["goal"]]),
                "description" => $level[$indexes["description"]],
                "reward" => intval($level[$indexes["reward"]]),
                "tokens" => intval($level[$indexes["tokens"]])
            ];
            return 0;
        }, $file);

        // Import badges
        $file = file_get_contents($tempFolder . "/badges.csv");
        $nrBadgesImported = Utils::importFromCSV(self::HEADERS, function ($badge, $indexes) use ($courseId, $replace, $levels, $images) {
            $name = Utils::nullify($badge[$indexes["name"]]);
            $description = Utils::nullify($badge[$indexes["description"]]);
            $isExtra = self::parse(null, $badge[$indexes["isExtra"]], "isExtra");
            $isBragging = self::parse(null, $badge[$indexes["isBragging"]], "isBragging");
            $isCount = self::parse(null, $badge[$indexes["isCount"]], "isCount");
            $isPost = self::parse(null, $badge[$indexes["isPost"]], "isPost");
            $isPoint = self::parse(null, $badge[$indexes["isPoint"]], "isPoint");
            $isActive = self::parse(null, $badge[$indexes["isActive"]], "isActive");

            $badge = self::getBadgeByName($courseId, $name);
            $image = $images[$name] ?? null;
            if ($badge) {  // badge already exists
                if ($replace) {  // replace
                    $badge->editBadge($name, $description, $isExtra, $isBragging, $isCount, $isPost, $isPoint, $isActive, $levels[$name]);
                    if ($image) copy($image, $badge->getDataFolder() . "/badge.png");
                }
            } else {  // badge doesn't exist
                $badge = self::addBadge($courseId, $name, $description, $isExtra, $isBragging, $isCount, $isPost, $isPoint, $levels[$name]);
                if ($image) copy($image, $badge->getDataFolder() . "/badge.png");
                return 1;
            }
            return 0;
        }, $file);

        // Remove temporary folder
        Utils::deleteDirectory($tempFolder);
        if (Utils::getDirectorySize(ROOT_PATH . "temp") == 0)
            Utils::deleteDirectory(ROOT_PATH . "temp");

        return $nrBadgesImported;
    }

    /**
     * Exports badges to a .zip file.
     *
     * @param int $courseId
     * @param array $badgeIds
     * @return array
     * @throws Exception
     */
    public static function exportBadges(int $courseId, array $badgeIds): array
    {
        $course = new Course($courseId);

        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store badges' info
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/" . Utils::strip($course->getShort() ?? $course->getName(), '_') . "-badges.zip";
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE))
            throw new Exception("Failed to create zip archive.");

        // Add badges .csv file
        $badgesToExport = array_values(array_filter(self::getBadges($courseId), function ($badge) use ($badgeIds) { return in_array($badge["id"], $badgeIds); }));
        $zip->addFromString("badges.csv", Utils::exportToCSV($badgesToExport, function ($badge) {
            return [$badge["name"], $badge["description"], $badge["nrLevels"], +$badge["isExtra"], +$badge["isBragging"],
                +$badge["isCount"], +$badge["isPost"], +$badge["isPoint"], +$badge["isActive"]];
        }, self::HEADERS));

        // Add images folder
        $zip->addEmptyDir("images");

        $levels = [];
        foreach ($badgesToExport as $badgeInfo) {
            $badge = self::getBadgeById($badgeInfo["id"]);
            $badgeLevels = $badge->getLevels();
            foreach ($badgeLevels as &$level) { $level["badge_name"] = $badgeInfo["name"]; }
            $levels = array_merge($levels, $badgeLevels);

            // Export image
            if ($badgeInfo["image"] && !self::isDefaultImage($badgeInfo["image"])) {
                $imagePath = str_replace(API_URL . "/", ROOT_PATH, $badgeInfo["image"]);
                $zip->addFile($imagePath, "images/" . $badgeInfo["name"] . ".png");
            }
        }

        // Add levels .csv file
        $zip->addFromString("levels.csv", Utils::exportToCSV($levels, function ($level) {
            return [$level["badge_name"], $level["number"], $level["goal"], $level["description"], $level["reward"], $level["tokens"]];
        }, self::LEVEL_HEADERS));

        $zip->close();
        return ["extension" => ".zip", "path" => str_replace(ROOT_PATH, API_URL . "/", $zipPath)];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates badge parameters.
     *
     * @param int $courseId
     * @param $name
     * @param $description
     * @param $isExtra
     * @param $isBragging
     * @param $isCount
     * @param $isPost
     * @param $isPoint
     * @param $levels
     * @return void
     * @throws Exception
     */
    private static function validateBadge(int $courseId, $name, $description, $isExtra, $isBragging, $isCount, $isPost, $isPoint, $levels)
    {
        self::validateName($courseId, $name);
        self::validateDescription($description);
        if (!is_bool($isExtra)) throw new Exception("'isExtra' must be either true or false.");
        if (!is_bool($isBragging)) throw new Exception("'isBragging' must be either true or false.");
        if (!is_bool($isCount)) throw new Exception("'isCount' must be either true or false.");
        if (!is_bool($isPost)) throw new Exception("'isPost' must be either true or false.");
        if (!is_bool($isPoint)) throw new Exception("'isPoint' must be either true or false.");
        self::validateLevels($levels, $isBragging);
    }

    /**
     * Validates badge name.
     *
     * @param int $courseId
     * @param $name
     * @param int|null $badgeId
     * @return void
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $badgeId = null)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Badge name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Badge name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Badge name is too long: maximum of 50 characters.");

        $whereNot = [];
        if ($badgeId) $whereNot[] = ["id", $badgeId];
        $badgeNames = array_column(Core::database()->selectMultiple(self::TABLE_BADGE, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $badgeNames))
            throw new Exception("Duplicate badge name: '$name'");
    }

    /**
     * Validates badge description.
     *
     * @param $description
     * @return void
     * @throws Exception
     */
    private static function validateDescription($description)
    {
        if (!is_string($description) || empty(trim($description)))
            throw new Exception("Badge description can't be null neither empty.");

        if (is_numeric($description))
            throw new Exception("Badge description can't be composed of only numbers.");

        if (iconv_strlen($description) > 150)
            throw new Exception("Badge description is too long: maximum of 150 characters.");
    }

    /**
     * Validates badge levels.
     *
     * @param $levels
     * @param bool $isBragging
     * @return void
     * @throws Exception
     */
    private static function validateLevels($levels, bool $isBragging)
    {
        if (!is_array($levels) || empty($levels))
            throw new Exception("Badge needs to have at least one level.");

        $size = count($levels);
        if ($size > 3)
            throw new Exception("Badge can't have more than three levels.");

        for ($i = 0; $i < $size; $i++) {
            if (!isset($levels[$i]["description"]) || !is_string($levels[$i]["description"]) || empty(trim($levels[$i]["description"])))
                throw new Exception("Badge level " . ($i+1) . " description can't be null nor empty.");

            if (!isset($levels[$i]["goal"]) || !is_numeric($levels[$i]["goal"]) || $levels[$i]["goal"] < 0)
                throw new Exception("Badge level " . ($i+1) . " goal can't be null nor negative.");

            if (!$isBragging && (!isset($levels[$i]["reward"]) || !is_numeric($levels[$i]["reward"]) || $levels[$i]["reward"] < 0))
                throw new Exception("Badge level " . ($i+1) . " reward can't be null nor negative.");

            if (isset($levels[$i]["tokens"]) && (!is_numeric($levels[$i]["tokens"]) || $levels[$i]["tokens"] < 0))
                throw new Exception("Badge level " . ($i+1) . " tokens can't be null nor negative.");

            if ($i != 0) {
                if ($levels[$i - 1]["goal"] >= $levels[$i]["goal"])
                    throw new Exception("Badge goal for level " . ($i+1) . " needs to be bigger than goal for level " . ($i) . ".");
            }
        }
    }

    /**
     * Validates badge level.
     *
     * @param $description
     * @return void
     * @throws Exception
     */
    private static function validateLevel($description)
    {
        if (is_null($description)) return;

        if (iconv_strlen($description) > 100)
            throw new Exception("Badge level description is too long: maximum of 100 characters.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private static function isDefaultImage(string $img): bool {
        return strpos($img, self::DEFAULT_IMAGE) || strpos($img, self::DEFAULT_IMAGE_EXTRA);
    }

    /**
     * Parses a badge coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $badge
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $badge = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "nrLevels", "rule"];
        $boolValues = ["isExtra", "isBragging", "isCount", "isPost", "isPoint", "isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $badge, $field, $fieldName);
    }

    /**
     * Trims badge parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "description", "desc1", "desc2", "desc3"];
        Utils::trim($params, ...$values);
    }
}
