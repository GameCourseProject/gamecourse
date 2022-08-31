<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;
use ZipArchive;

/**
 * This is the Skill Tree model, which implements the necessary methods
 * to interact with skill trees in the MySQL database.
 */
class SkillTree
{
    const TABLE_SKILL_TREE = 'skill_tree';

    const HEADERS = [   // headers for import/export functionality
        "name", "maxReward"
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

    public function getName(): ?string
    {
        return $this->getData("name");
    }

    public function getMaxReward(): int
    {
        return $this->getData("maxReward");
    }

    /**
     * Gets skill tree data from the database.
     *
     * @example getData() --> gets all skill tree data
     * @example getData("name") --> gets skill tree name
     * @example getData("name, maxReward") --> gets skill tree name & max. reward
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_SKILL_TREE;
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
    public function setName(?string $name)
    {
        $this->setData(["name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function setMaxReward(int $maxReward)
    {
        $this->setData(["maxReward" => $maxReward]);
    }

    /**
     * Sets skill tree data on the database.
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "maxReward" => 500])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($fieldValues["name"]);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_SKILL_TREE, $fieldValues, ["id" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a skill tree by its ID.
     * Returns null if skill tree doesn't exist.
     *
     * @param int $id
     * @return SkillTree|null
     */
    public static function getSkillTreeById(int $id): ?SkillTree
    {
        $skillTree = new SkillTree($id);
        if ($skillTree->exists()) return $skillTree;
        else return null;
    }

    /**
     * Gets a skill tree by its name.
     * Returns null if skill tree doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return SkillTree|null
     */
    public static function getSkillTreeByName(int $courseId, string $name): ?SkillTree
    {
        $skillTreeId = intval(Core::database()->select(self::TABLE_SKILL_TREE, ["course" => $courseId, "name" => $name], "id"));
        if (!$skillTreeId) return null;
        else return new SkillTree($skillTreeId);
    }

    /**
     * Gets all skill trees of course.
     * Option for ordering.
     *
     * @param int $courseId
     * @param string $orderBy
     * @return array
     */
    public static function getSkillTrees(int $courseId, string $orderBy = "name"): array
    {
        $field = "id, name, maxReward";
        $skillTrees = Core::database()->selectMultiple(self::TABLE_SKILL_TREE, ["course" => $courseId], $field, $orderBy);
        foreach ($skillTrees as &$skillTree) { $skillTree = self::parse($skillTree); }
        return $skillTrees;
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------- Skill Tree Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new skill tree to the database.
     * Returns the newly created skill tree.
     *
     * @param int $courseId
     * @param string|null $name
     * @param int $maxReward
     * @return SkillTree
     * @throws Exception
     */
    public static function addSkillTree(int $courseId, ?string $name, int $maxReward): SkillTree
    {
        self::validateName($name);
        $id = Core::database()->insert(self::TABLE_SKILL_TREE, [
            "course" => $courseId,
            "name" => $name,
            "maxReward" => $maxReward
        ]);
        Tier::addTier($id, Tier::WILDCARD, 0);
        return new SkillTree($id);
    }

    /**
     * Edits an existing skill tree in the database.
     * Returns the edited skill tree.
     *
     * @param string|null $name
     * @param int $maxReward
     * @return SkillTree
     * @throws Exception
     */
    public function editSkillTree(?string $name, int $maxReward): SkillTree
    {
        $this->setData([
            "name" => $name,
            "maxReward" => $maxReward
        ]);
        return $this;
    }

    /**
     * Deletes a skill tree from the database.
     *
     * @param int $skillTreeId
     * @return void
     * @throws Exception
     */
    public static function deleteSkillTree(int $skillTreeId) {
        $skillTree = SkillTree::getSkillTreeById($skillTreeId);
        if ($skillTree) {
            // Delete tiers
            $tiers = $skillTree->getTiers();
            foreach ($tiers as $tier) {
                Tier::deleteTier($tier["id"], true);
            }

            // Delete skill tree from database
            Core::database()->delete(self::TABLE_SKILL_TREE, ["id" => $skillTreeId]);
        }
    }

    /**
     * Checks whether skill tree exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tiers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets skill tree tiers.
     * Option for 'active'.
     *
     * @param bool|null $active
     * @return array
     */
    public function getTiers(bool $active = null): array
    {
        return Tier::getTiersOfSkillTree($this->id, $active);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Skills ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets skill tree skills.
     * Option for 'active', 'extra', 'collab'.
     *
     * @param bool|null $active
     * @param bool|null $extra
     * @param bool|null $collab
     * @return array
     * @throws Exception
     */
    public function getSkills(bool $active = null, bool $extra = null, bool $collab = null): array
    {
        return Skill::getSkillsOfSkillTree($this->id, $active, $extra, $collab);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports skill trees into a given course from a .zip file containing
     * a FIXME: complete.
     *
     * Returns the nr. of skill trees imported.
     *
     * @param int $courseId
     * @param string $contents
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importSkillTrees(int $courseId, string $contents, bool $replace = true): int
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Extract contents
        $zipPath = $tempFolder . "/skillTrees.zip";
        file_put_contents($zipPath, $contents);
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Exception("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        Utils::deleteFile($tempFolder, "skillTrees.zip");

        $nrSkillTreesImported = 0;
        // TODO: import skill trees

        // Remove temporary folder
        Utils::deleteDirectory($tempFolder);
        if (Utils::getDirectorySize(ROOT_PATH . "temp") == 0)
            Utils::deleteDirectory(ROOT_PATH . "temp");

        return $nrSkillTreesImported;
    }

    /**
     * Exports skill trees to a .zip file.
     *
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public static function exportSkillTrees(int $courseId): array
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store skill trees' info
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/skillTrees.zip";
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
     * Validates skill tree name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (is_null($name)) return;

        if (empty(trim($name)))
            throw new Exception("Skill tree name can't be empty.");

        if (iconv_strlen($name) > 50)
            throw new Exception("Skill tree name is too long: maximum of 50 characters.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a skill tree coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $skillTree
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    public static function parse(array $skillTree = null, $field = null, string $fieldName = null)
    {
        if ($skillTree) {
            if (isset($skillTree["id"])) $skillTree["id"] = intval($skillTree["id"]);
            if (isset($skillTree["course"])) $skillTree["course"] = intval($skillTree["course"]);
            if (isset($skillTree["maxReward"])) $skillTree["maxReward"] = intval($skillTree["maxReward"]);
            return $skillTree;

        } else {
            if ($fieldName == "id" || $fieldName == "course" || $fieldName == "maxReward")
                return is_numeric($field) ? intval($field) : $field;
            return $field;
        }
    }
}
