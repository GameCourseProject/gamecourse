<?php
namespace GameCourse\Badges;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\XPLevels\XPLevels;
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
        "badge_name", "number", "goal", "description", "reward"
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
     * @return array|int|null
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
        if (key_exists("name", $fieldValues)) {
            $newName = $fieldValues["name"];
            self::validateName($newName);

            // Update badge folder if name has changed
            $oldName = $this->getName();
            if (strcmp($oldName, $newName) !== 0)
                rename($this->getDataFolder(true, $oldName), $this->getDataFolder(true, $newName));
        }
        if (key_exists("description", $fieldValues)) self::validateDescription($fieldValues["description"]);
        if (key_exists("isExtra", $fieldValues) && $fieldValues["isExtra"]) {
            $course = $this->getCourse();
            $xpLevelsModule = new XPLevels($course);
            if ($xpLevelsModule->isEnabled() && !$xpLevelsModule->getMaxExtraCredit())
                throw new Exception("You're attempting to set a badge as extra credit while there's no extra credit available to earn.\n
            Go to " . XPLevels::NAME . " module and set a max. global extra credit value first.");

            $badgesModule = new Badges($course);
            if (!$badgesModule->getMaxExtraCredit())
                throw new Exception("You're attempting to set a badge as extra credit while there's no badge extra credit available to earn.\n
            Set a max. badge extra credit value first.");
        }

        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_BADGE, $fieldValues, ["id" => $this->id]);
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
        $field = "id, name, description, nrLevels, isExtra, isBragging, isCount, isPost, isPoint, isActive";
        $where = ["course" => $courseId];
        if ($active !== null) $where["isActive"] = $active;
        $badges = Core::database()->selectMultiple(self::TABLE_BADGE, $where, $field, $orderBy);
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
        self::validateBadge($name, $description, $isExtra, $isBragging, $isCount, $isPost, $isPoint);
        $id = Core::database()->insert(self::TABLE_BADGE, [
            "course" => $courseId,
            "name" => $name,
            "description" => $description,
            "isExtra" => +$isExtra,
            "isBragging" => +$isBragging,
            "isCount" => +$isCount,
            "isPost" => +$isPost,
            "isPoint" => +$isPoint
        ]);
        self::setLevels($id, $levels);
        self::createDataFolder($courseId, $name);
        return new Badge($id);
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
        self::validateBadge($name, $description, $isExtra, $isBragging, $isCount, $isPost, $isPoint);
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
        self::setLevels($this->id, $levels);
        return $this;
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
        self::removeDataFolder($badge->getCourse()->getId(), $badge->getName());
        Core::database()->delete(self::TABLE_BADGE, ["id" => $badgeId]);
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
        $levels = Core::database()->selectMultiple(self::TABLE_BADGE_LEVEL, ["badge" => $this->id], "id, number, goal, description, reward", "number");
        foreach ($levels as &$level) {
            $level["number"] = intval($level["number"]);
            $level["goal"] = intval($level["goal"]);
            $level["reward"] = intval($level["reward"]);
        }
        return $levels;
    }

    /**
     * Sets badge levels.
     *
     * @param int $badgeId
     * @param array $levels
     * @return void
     * @throws Exception
     */
    private static function setLevels(int $badgeId, array $levels)
    {
        // Delete all levels
        Core::database()->delete(self::TABLE_BADGE_LEVEL, ["badge" => $badgeId]);

        // Set new levels
        foreach ($levels as $i => $level) {
            self::validateLevel($level["description"]);
            Core::database()->insert(self::TABLE_BADGE_LEVEL, [
                "badge" => $badgeId,
                "number" => $i + 1,
                "goal" => $level["goal"],
                "description" => $level["description"],
                "reward" => $level["reward"]
            ]);
        }
        Core::database()->update(self::TABLE_BADGE, ["nrLevels" => count($levels)], ["id" => $badgeId]);
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
     * Imports badges into a given course from a .zip file containing
     * a .csv file with all badges information, a .csv file with
     * badges levels information and a folder with all badges images.
     *
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
        file_put_contents($zipPath, $contents);
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Exception("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        unlink($zipPath);

        // Get badges images
        $images = [];
        foreach (Utils::getDirectoryContents($tempFolder . "/images") as $file) {
            if ($file["type"] != "file") throw new Exception("Badge image for '" . $file["name"] . "' is not a file.");
            $images[$file["name"]] = $tempFolder . "/images/" . $file["name"] . $file["extension"];
        }

        // Import badges levels
        $file = file_get_contents($tempFolder . "/levels.csv");
        $levels = [];
        Utils::importFromCSV(self::LEVEL_HEADERS, function ($level, $indexes) use ($courseId, $replace, &$levels) {
            $levels[$level[$indexes["badge_name"]]][] = [
                "number" => intval($level[$indexes["number"]]),
                "goal" => intval($level[$indexes["goal"]]),
                "description" => $level[$indexes["description"]],
                "reward" => intval($level[$indexes["reward"]])
            ];
            return 0;
        }, $file);

        // Import badges
        $file = file_get_contents($tempFolder . "/badges.csv");
        $nrBadgesImported = Utils::importFromCSV(self::HEADERS, function ($badge, $indexes) use ($courseId, $replace, $levels, $images) {
            $name = $badge[$indexes["name"]];
            $description = $badge[$indexes["description"]];
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
     * @return array
     * @throws Exception
     */
    public static function exportBadges(int $courseId): array
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store badges' info
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/badges.zip";
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE))
            throw new Exception("Failed to create zip archive.");

        // Add badges .csv file
        $badges = self::getBadges($courseId);
        $zip->addFromString("badges.csv", Utils::exportToCSV($badges, function ($badge) {
            return [$badge["name"], $badge["description"], $badge["nrLevels"], +$badge["isExtra"], +$badge["isBragging"],
                +$badge["isCount"], +$badge["isPost"], +$badge["isPoint"], +$badge["isActive"]];
        }, self::HEADERS));

        // Add images folder
        $zip->addEmptyDir("images");

        $levels = [];
        foreach ($badges as $badgeInfo) {
            $badge = self::getBadgeById($badgeInfo["id"]);
            $badgeLevels = $badge->getLevels();
            foreach ($badgeLevels as &$level) { $level["badge_name"] = $badgeInfo["name"]; }
            $levels = array_merge($levels, $badgeLevels);

            // Export image
            if ($badgeInfo["image"] && !self::isDefaultImage($badgeInfo["image"]))
                $zip->addFile($badgeInfo["image"], "images/" . $badgeInfo["name"] . ".png");
        }

        // Add levels .csv file
        $zip->addFromString("levels.csv", Utils::exportToCSV($levels, function ($level) {
            return [$level["badge_name"], $level["number"], $level["goal"], $level["description"], $level["reward"]];
        }, self::LEVEL_HEADERS));

        $zip->close();
        return ["extension" => ".zip", "path" => $zipPath];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates badge parameters.
     *
     * @param $name
     * @param $description
     * @param $isExtra
     * @param $isBragging
     * @param $isCount
     * @param $isPost
     * @param $isPoint
     * @return void
     * @throws Exception
     */
    private static function validateBadge($name, $description, $isExtra, $isBragging, $isCount, $isPost, $isPoint)
    {
        self::validateName($name);
        self::validateDescription($description);
        if (!is_bool($isExtra)) throw new Exception("'isExtra' must be either true or false.");
        if (!is_bool($isBragging)) throw new Exception("'isBragging' must be either true or false.");
        if (!is_bool($isCount)) throw new Exception("'isCount' must be either true or false.");
        if (!is_bool($isPost)) throw new Exception("'isPost' must be either true or false.");
        if (!is_bool($isPoint)) throw new Exception("'isPoint' must be either true or false.");
    }

    /**
     * Validates badge name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Badge name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Badge name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 70)
            throw new Exception("Badge name is too long: maximum of 70 characters.");
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
        if (!is_string($description) || empty($description))
            throw new Exception("Badge description can't be null neither empty.");

        if (is_numeric($description))
            throw new Exception("Badge description can't be composed of only numbers.");

        if (iconv_strlen($description) > 150)
            throw new Exception("Badge description is too long: maximum of 150 characters.");
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
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    private static function parse(array $badge = null, $field = null, string $fieldName = null)
    {
        if ($badge) {
            if (isset($badge["id"])) $badge["id"] = intval($badge["id"]);
            if (isset($badge["course"])) $badge["course"] = intval($badge["course"]);
            if (isset($badge["nrLevels"])) $badge["nrLevels"] = intval($badge["nrLevels"]);
            if (isset($badge["isExtra"])) $badge["isExtra"] = boolval($badge["isExtra"]);
            if (isset($badge["isBragging"])) $badge["isBragging"] = boolval($badge["isBragging"]);
            if (isset($badge["isCount"])) $badge["isCount"] = boolval($badge["isCount"]);
            if (isset($badge["isPost"])) $badge["isPost"] = boolval($badge["isPost"]);
            if (isset($badge["isPoint"])) $badge["isPoint"] = boolval($badge["isPoint"]);
            if (isset($badge["isActive"])) $badge["isActive"] = boolval($badge["isActive"]);
            return $badge;

        } else {
            if ($fieldName == "id" || $fieldName == "course" || $fieldName == "nrLevels")
                return is_numeric($field) ? intval($field) : $field;
            if ($fieldName == "isExtra" || $fieldName == "isBragging" || $fieldName == "isCount" || $fieldName == "isPost"
                || $fieldName == "isPoint" || $fieldName == "isActive") return boolval($field);
            return $field;
        }
    }
}
