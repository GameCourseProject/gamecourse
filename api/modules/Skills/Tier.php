<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;
use ZipArchive;

/**
 * This is the Tier model, which implements the necessary methods
 * to interact with tiers in the MySQL database.
 */
class Tier
{
    const TABLE_SKILL_TIER = 'skill_tier';
    const TABLE_SKILL_TIER_COST = 'skill_tier_cost';

    const HEADERS = [   // headers for import/export functionality
        "name", "reward", "position", "isActive", "costType", "cost", "increment, minRating"
    ];

    const WILDCARD = "Wildcard";

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
        return $this->getSkillTree()->getCourse();
    }

    public function getSkillTree(): SkillTree
    {
        return new SkillTree($this->getData("skillTree"));
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getReward(): int
    {
        return $this->getData("reward");
    }

    public function getPosition(): ?int
    {
        return $this->getData("position");
    }

    public function getCostType(): string
    {
        return $this->getData("costType");
    }

    public function getCost(): int
    {
        return $this->getData("cost");
    }

    public function getIncrement(): int
    {
        return $this->getData("increment");
    }

    public function getMinRating(): int
    {
        return $this->getData("minRating");
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Checks whether tier is the wildcard tier.
     *
     * @return bool
     */
    public function isWildcard(): bool
    {
        return $this->getName() == self::WILDCARD;
    }

    /**
     * Gets skill tier data from the database.
     *
     * @example getData() --> gets all skill tier data
     * @example getData("name") --> gets skill tier name
     * @example getData("name, reward") --> gets skill tier name & reward
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_SKILL_TIER . " t LEFT JOIN " . self::TABLE_SKILL_TIER_COST . " tc on tc.tier=t.id";
        $where = ["id" => $this->id];
        if ($field == "*") $field = "t.*, tc.costType, tc.cost, tc.increment, tc.minRating";
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
    public function setReward(int $reward)
    {
        $this->setData(["reward" => $reward]);
    }

    /**
     * @throws Exception
     */
    public function setPosition(int $position)
    {
        $this->setData(["position" => $position]);
    }

    /**
     * @throws Exception
     */
    public function setCostType(string $type)
    {
        $this->setData(["costType" => $type]);
    }

    /**
     * @throws Exception
     */
    public function setCost(int $cost)
    {
        $this->setData(["cost" => $cost]);
    }

    /**
     * @throws Exception
     */
    public function setIncrement(int $increment)
    {
        $this->setData(["increment" => $increment]);
    }

    /**
     * @throws Exception
     */
    public function setMinRating(int $rating)
    {
        $this->setData(["minRating" => $rating]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets skill tier data on the database.
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "reward" => 500])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        // Trim values
        self::trim($fieldValues);

        $costValues = []; // values that need to go to 'skill_tier_cost' table
        if (key_exists("costType", $fieldValues)) {
            $costValues["costType"] = $fieldValues["costType"];
            unset($fieldValues["costType"]);
        }
        if (key_exists("cost", $fieldValues)) {
            $costValues["cost"] = $fieldValues["cost"];
            unset($fieldValues["cost"]);
        }
        if (key_exists("increment", $fieldValues)) {
            $costValues["increment"] = $fieldValues["increment"];
            unset($fieldValues["increment"]);
        }
        if (key_exists("minRating", $fieldValues)) {
            $costValues["minRating"] = $fieldValues["minRating"];
            unset($fieldValues["minRating"]);
        }

        // Validate data
        if (key_exists("name", $fieldValues)) {
            self::validateName($fieldValues["name"]);
            if ($this->isWildcard() && $fieldValues["name"] != self::WILDCARD)
                throw new Exception("Can't change name of '" . self::WILDCARD . "' tier.");
        }
        if (key_exists("position", $fieldValues)) {
            $newPosition = $fieldValues["position"];
            $oldPosition = $this->getPosition();
            Utils::updateItemPosition($oldPosition, $newPosition, self::TABLE_SKILL_TIER, "position",
                $this->id, $this->getSkillTree()->getTiers());
        }
        if (key_exists("costType", $costValues)) {
            $newCostType = $costValues["costType"];
            $oldCostType = $this->getCostType();
        }

        // Update data
        if (count($costValues) != 0) Core::database()->update(self::TABLE_SKILL_TIER_COST, $costValues, ["tier" => $this->id]);
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_SKILL_TIER, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("isActive", $fieldValues) && !$fieldValues["isActive"]) {
            // Disable skills of tier
            $skills = $this->getSkills();
            foreach ($skills as $skill) {
                $skill = new Skill($skill["id"]);
                $skill->setActive(false);
            }
        }
        if (key_exists("costType", $costValues)) {
            if ($newCostType !== $oldCostType && $newCostType === "fixed")
                $this->setData(["increment" => 0, "minRating" => 3]);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a tier by its ID.
     * Returns null if tier doesn't exist.
     *
     * @param int $id
     * @return Tier|null
     */
    public static function getTierById(int $id): ?Tier
    {
        $tier = new Tier($id);
        if ($tier->exists()) return $tier;
        else return null;
    }

    /**
     * Gets a tier by its name.
     * Returns null if tier doesn't exist.
     *
     * @param int $skillTreeId
     * @param string $name
     * @return Tier|null
     */
    public static function getTierByName(int $skillTreeId, string $name): ?Tier
    {
        $tierId = intval(Core::database()->select(self::TABLE_SKILL_TIER, ["skillTree" => $skillTreeId, "name" => $name], "id"));
        if (!$tierId) return null;
        else return new Tier($tierId);
    }

    /**
     * Gets a tier by its position.
     * Returns null if tier doesn't exist.
     *
     * @param int $skillTreeId
     * @param int $position
     * @return Tier|null
     */
    public static function getTierByPosition(int $skillTreeId, int $position): ?Tier
    {
        $tierId = intval(Core::database()->select(self::TABLE_SKILL_TIER, ["skillTree" => $skillTreeId, "position" => $position], "id"));
        if (!$tierId) return null;
        else return new Tier($tierId);
    }

    /**
     * Gets wildcard tier.
     *
     * @param int $skillTreeId
     * @return Tier
     */
    public static function getWildcard(int $skillTreeId): Tier
    {
        return self::getTierByName($skillTreeId, self::WILDCARD);
    }

    /**
     * Gets all tiers of course.
     * Option for 'active' and ordering.
     *
     * @param int $courseId
     * @param bool|null $active
     * @param string $orderBy
     * @return array
     */
    public static function getTiers(int $courseId, bool $active = null, string $orderBy = "st.id, t.position"): array
    {
        $table = SkillTree::TABLE_SKILL_TREE . " st RIGHT JOIN " . self::TABLE_SKILL_TIER . " t on t.skillTree=st.id" .
            " LEFT JOIN " . self::TABLE_SKILL_TIER_COST . " tc on tc.tier=t.id";
        $where = ["course" => $courseId];
        if ($active !== null) $where["t.isActive"] = $active;
        $tiers = Core::database()->selectMultiple($table, $where, "t.*, tc.costType, tc.cost, tc.increment, tc.minRating", $orderBy);
        foreach ($tiers as &$tier) { $tier = self::parse($tier); }
        return $tiers;
    }

    /**
     * Gets all tiers of a skill tree.
     * Option for 'active' and ordering.
     *
     * @param int $skillTreeId
     * @param bool|null $active
     * @param string $orderBy
     * @return array
     */
    public static function getTiersOfSkillTree(int $skillTreeId, bool $active = null, string $orderBy = "position"): array
    {
        $table = self::TABLE_SKILL_TIER . " t LEFT JOIN " . self::TABLE_SKILL_TIER_COST . " tc on tc.tier=t.id";
        $where = ["skillTree" => $skillTreeId];
        if ($active !== null) $where["isActive"] = $active;
        $tiers = Core::database()->selectMultiple($table, $where, "t.*, tc.costType, tc.cost, tc.increment, tc.minRating", $orderBy);
        foreach ($tiers as &$tier) { $tier = self::parse($tier); }
        return $tiers;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Tier Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new tier to the database.
     * Returns the newly created tier.
     *
     * @param int $skillTreeId
     * @param string $name
     * @param int $reward
     * @param string|null $costType
     * @param int|null $cost
     * @param int|null $increment
     * @param int|null $minRating
     * @return Tier
     * @throws Exception
     */
    public static function addTier(int $skillTreeId, string $name, int $reward, string $costType = null, int $cost = null,
                                   int $increment = null, int $minRating = null): Tier
    {
        self::trim($name);
        self::validateName($name);
        $id = Core::database()->insert(self::TABLE_SKILL_TIER, [
            "skillTree" => $skillTreeId,
            "name" => $name,
            "reward" => $reward
        ]);
        Core::database()->insert(self::TABLE_SKILL_TIER_COST, [
            "tier" => $id,
            "costType" => $costType ?? "fixed",
            "cost" => $cost ?? 0,
            "increment" => $increment ?? 0,
            "minRating" => $minRating ?? 3
        ]);
        $tier = new Tier($id);

        // Update position
        $tiers = self::getTiersOfSkillTree($skillTreeId);
        if ($tier->isWildcard()) $position = 0;
        else $position = count($tiers) - 2;
        Utils::updateItemPosition(null, $position, self::TABLE_SKILL_TIER, "position", $id, $tiers);

        return $tier;
    }

    /**
     * Edits an existing tier in the database.
     * Returns the edited tier.
     *
     * @param string $name
     * @param int $reward
     * @param int $position
     * @param bool $isActive
     * @param string|null $costType
     * @param int|null $cost
     * @param int|null $increment
     * @param int|null $minRating
     * @return Tier
     * @throws Exception
     */
    public function editTier(string $name, int $reward, int $position, bool $isActive, string $costType = null, int $cost = null,
                             int $increment = null, int $minRating = null): Tier
    {
        $this->setData([
            "name" => $name,
            "reward" => $reward,
            "isActive" => +$isActive,
            "position" => $position,
            "costType" => $costType ?? "fixed",
            "cost" => $cost ?? 0,
            "increment" => $increment ?? 0,
            "minRating" => $minRating ?? 3
        ]);
        return $this;
    }

    /**
     * Copies an existing tier into another given skill tree.
     *
     * @param SkillTree $copyTo
     * @return void
     * @throws Exception
     */
    public function copyTier(SkillTree $copyTo)
    {
        // Copy tier
        $tierInfo = $this->getData();
        if ($this->isWildcard()) {
            $copiedWildcard = self::getWildcard($copyTo->getId());
            $copiedWildcard->setReward($tierInfo["reward"]);
            $copiedWildcard->setActive($tierInfo["isActive"]);
            $copiedWildcard->setCostType($tierInfo["costType"]);
            $copiedWildcard->setCost($tierInfo["cost"]);
            $copiedWildcard->setIncrement($tierInfo["increment"]);
            $copiedWildcard->setMinRating($tierInfo["minRating"]);
            $copiedTier = $copiedWildcard;

        } else {
            $copiedTier = self::addTier($copyTo->getId(), $tierInfo["name"], $tierInfo["reward"], $tierInfo["costType"],
                $tierInfo["cost"], $tierInfo["increment"], $tierInfo["minRating"]);
            $copiedTier->setActive($tierInfo["isActive"]);
        }

        // Copy skills of tier
        $skills = $this->getSkills();
        foreach ($skills as $skill) {
            $skill = new Skill($skill["id"]);
            $skill->copySkill($copiedTier);
        }
    }

    /**
     * Deletes a tier from the database.
     *
     * @param int $tierId
     * @param bool $allowWildcard
     * @return void
     * @throws Exception
     */
    public static function deleteTier(int $tierId, bool $allowWildcard = false) {
        $tier = self::getTierById($tierId);
        if ($tier) {
            if (!$allowWildcard && $tierId == self::getWildcard($tier->getSkillTree()->getId())->getId())
                throw new Exception("It's not possible to delete '" . self::WILDCARD . "' tier.");

            // Delete skills
            $skills = $tier->getSkills();
            foreach ($skills as $skill) {
                Skill::deleteSkill($skill["id"]);
            }

            // Update position
            $position = $tier->getPosition();
            Utils::updateItemPosition($position, null, self::TABLE_SKILL_TIER, "position", $tierId, $tier->getSkillTree()->getTiers());

            // Delete tier from database
            Core::database()->delete(self::TABLE_SKILL_TIER, ["id" => $tierId]);
        }
    }

    /**
     * Checks whether tier exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Skills ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets skills of tier.
     * Option for 'active', 'extra', 'collab'.
     *
     * @param bool|null $active
     * @param bool|null $extra
     * @param bool|null $collab
     * @param string $orderBy
     * @return array
     * @throws Exception
     */
    public function getSkills(bool $active = null, bool $extra = null, bool $collab = null, string $orderBy = "s.position"): array
    {
        return Skill::getSkillsOfTier($this->id, $active, $extra, $collab, $orderBy);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports tiers into a given skill tree from a .zip file containing
     * a FIXME: complete.
     *
     * Returns the nr. of tiers imported.
     *
     * @param int $skillTreeId
     * @param string $contents
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importTiers(int $skillTreeId, string $contents, bool $replace = true): int
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Extract contents
        $zipPath = $tempFolder . "/tiers.zip";
        file_put_contents($zipPath, $contents);
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Exception("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        Utils::deleteFile($tempFolder, "tiers.zip");

        $nrTiersImported = 0;
        // TODO: import skill tiers

        // Remove temporary folder
        Utils::deleteDirectory($tempFolder);
        if (Utils::getDirectorySize(ROOT_PATH . "temp") == 0)
            Utils::deleteDirectory(ROOT_PATH . "temp");

        return $nrTiersImported;
    }

    /**
     * Exports tiers of a skill tree to a .zip file.
     *
     * @param int $skillTreeId
     * @return array
     * @throws Exception
     */
    public static function exportTiers(int $skillTreeId): array
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store skill tiers' info
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/tiers.zip";
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE))
            throw new Exception("Failed to create zip archive.");

        // TODO: export

        $zip->close();
        return ["extension" => ".zip", "path" => $zipPath];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates tier name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Tier name can't be null neither empty.");

        if (iconv_strlen($name) > 50)
            throw new Exception("Tier name is too long: maximum of 50 characters.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a tier coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $tier
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $tier = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "skillTree", "reward", "position", "cost", "increment", "minRating"];
        $boolValues = ["isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $tier, $field, $fieldName);
    }

    /**
     * Trims tier parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "costType"];
        Utils::trim($params, ...$values);
    }
}
