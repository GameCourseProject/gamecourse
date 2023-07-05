<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
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

    public function getMaxReward(): ?int
    {
        return $this->getData("maxReward");
    }

    public function inView(): bool {
        return $this->getData("inView");
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
    public function setMaxReward(?int $maxReward)
    {
        $this->setData(["maxReward" => $maxReward]);
    }

    /**
     * @throws Exception
     */
    public function setInView(?bool $inView){
        $this->setData(["inView" => +$inView]);
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
        $courseId = $this->getCourse()->getId();

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($courseId, $fieldValues["name"], $this->id);

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
        $field = "id, name, maxReward, inView";
        $skillTrees = Core::database()->selectMultiple(self::TABLE_SKILL_TREE, ["course" => $courseId], $field, $orderBy);
        foreach ($skillTrees as &$skillTree) { $skillTree = self::parse($skillTree); }
        return $skillTrees;
    }

    /**
     * Gets skill tree in config view inside Skill Tree module given a specific course
     *
     * @param int $courseId
     * @return mixed|null
     */
    public static function getSkillTreeInView(int $courseId){
        return Core::database()->select(self::TABLE_SKILL_TREE, ["course" => $courseId, "inView" => true]);
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
     * @param int|null $maxReward
     * @param bool|null $inView
     * @return SkillTree
     * @throws Exception
     */
    public static function addSkillTree(int $courseId, ?string $name, ?int $maxReward, bool $inView = false): SkillTree
    {
        self::trim($name);
        self::validateName($courseId, $name);
        $id = Core::database()->insert(self::TABLE_SKILL_TREE, [
            "course" => $courseId,
            "name" => $name,
            "maxReward" => $maxReward,
            "inView" => +$inView
        ]);
        Tier::addTier($id, Tier::WILDCARD, 0);
        return new SkillTree($id);
    }

    /**
     * Edits an existing skill tree in the database.
     * Returns the edited skill tree.
     *
     * @param string|null $name
     * @param int|null $maxReward
     * @param bool|null $inView
     * @return SkillTree
     * @throws Exception
     */
    public function editSkillTree(?string $name, ?int $maxReward): SkillTree
    {
        $this->setData([
            "name" => $name,
            "maxReward" => $maxReward
        ]);
        return $this;
    }

    /**
     * Copies an existing skill tree into another given course.
     *
     * @param Course $copyTo
     * @return void
     * @throws Exception
     */
    public function copySkillTree(Course $copyTo): SkillTree
    {
        // Copy skill tree
        $skillTreeInfo = $this->getData();
        $copiedSkillTree = self::addSkillTree($copyTo->getId(), $skillTreeInfo["name"], $skillTreeInfo["maxReward"]);

        // Copy wildcard tier
        // NOTE: wildcard tier needs to come 1st as other tiers might have skills with wildcard dependencies
        $wildcardTier = Tier::getWildcard($this->id);
        $wildcardTier->copyTier($copiedSkillTree);

        // Copy other tiers
        $tiers = $this->getTiers();
        foreach ($tiers as $tier) {
            $tier = new Tier($tier["id"]);
            if ($tier->isWildcard()) continue;
            $tier->copyTier($copiedSkillTree);
        }

        return $copiedSkillTree;
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
     * @param string $orderBy
     * @return array
     */
    public function getTiers(bool $active = null, string $orderBy = "position"): array
    {
        return Tier::getTiersOfSkillTree($this->id, $active, $orderBy);
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
        Utils::uploadFile($tempFolder, $contents, "skillTrees.zip");
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Exception("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        Utils::deleteFile($tempFolder, "skillTrees.zip");

        //file_put_contents($zipPath, $contents);
        $file = file_get_contents($tempFolder . "/skillTrees.csv");
        $nrSkillTreesImported = Utils::importFromCSV(self::HEADERS, function($skillTree, $indexes) use ($courseId, $replace, $tempFolder) {
            $name = Utils::nullify($skillTree[$indexes["name"]]);
            $maxReward = self::parse(null, Utils::nullify($skillTree[$indexes["maxReward"]]), "maxReward");

            $skillTree = self::getSkillTreeByName($courseId, $name);
            if ($skillTree) { // skillTree already exists
                if ($replace){ // replace
                    $skillTree->editSkillTree($name, $maxReward);

                    // Import tiers and skills
                    $tierFile = file_get_contents($tempFolder . "/tiers.csv");
                    if ($tierFile){ // There are tiers to be imported
                        $skillTree->setInView(true); // Specifies which skill tree is being manipulated at the moment
                        Tier::importTiers($courseId, $tierFile, $replace);
                        $skillTree->setInView(false);

                        // FIXME -- skills left
                    }
                }
            } else { // skillTree doesn't exist
                self::addSkillTree($courseId, $name, $maxReward);

                $tierFile = file_get_contents($tempFolder . "/tiers.csv");
                if ($tierFile) { // There are tiers to be imported
                    $skillTree->setInView(true); // Specifies which skill tree is being manipulated at the moment
                    Tier::importTiers($courseId, $tierFile, $replace);
                    $skillTree->setInView(false);

                    // FIXME -- skills left
                }

                return 1;
            }
            return 0;
        }, $file);

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
     * @param array $skillTreeIds
     * @return array
     * @throws Exception
     */
    public static function exportSkillTrees(int $courseId, array $skillTreeIds): array
    {
        $course = new Course($courseId);

        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store skill trees' info
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/skillTrees.zip";
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE))
            throw new Exception("Failed to create zip archive.");

        // Add skill trees .csv file
        $skillTreesToExport = array_values(array_filter(self::getSkillTrees($courseId), function ($skillTree) use ($skillTreeIds) { return in_array($skillTree["id"], $skillTreeIds); }));
        $zip->addFromString("skillTrees.csv", Utils::exportToCSV($skillTreesToExport, function ($skillTree) {
            return [$skillTree["name"], $skillTree["maxReward"]];
        }, self::HEADERS));

        // Add each skill tree tiers & skills
        foreach ($skillTreesToExport as $st) {
            $skillTree = new SkillTree($st["id"]);

            // Add tiers .csv file
            $tiers = $skillTree->getTiers();
            $zip->addFromString("tiers.csv", Utils::exportToCSV($tiers, function ($tier) {
                return [$tier["name"], $tier["reward"], $tier["position"], +$tier["isActive"],
                    $tier["costType"], $tier["cost"], $tier["increment"], $tier["minRating"]];
            }, Tier::HEADERS));

            // Add skills .csv file
            $skills = $skillTree->getSkills();
            $zip->addFromString("skills.csv", Utils::exportToCSV($skills, function ($skill) {
                return [$skill["name"], $skill["color"], $skill["page"], +$skill["isCollab"], +$skill["isExtra"], +$skill["isActive"], $skill["position"]];
            }, Skill::HEADERS));

            // Skill's rules as well
            $skillsSection = RuleSystem::getSectionIdByModule($courseId, "Skills");
            $skillRules = Rule::getRulesOfSection($skillsSection);
            $zip->addFromString("skillsRules.csv", Utils::exportToCSV($skillRules, function ($skillRule) {
                $whenClause = Rule::parseToExportAndImport($skillRule["whenClause"], "export");
                $thenClause = Rule::parseToExportAndImport($skillRule["thenClause"], "export");

                return [$skillRule["name"], $skillRule["description"], $whenClause, $thenClause,
                    +$skillRule["isActive"], $skillRule["position"], ""]; // tags are omitted
            }, Rule::HEADERS));
        }

        $zip->close();
        return ["extension" => ".zip", "path" => str_replace(ROOT_PATH, API_URL . "/", $zipPath)];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates skill tree name.
     *
     * @param int $courseId
     * @param $name
     * @param int|null $skillTreeId
     * @return void
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $skillTreeId = null)
    {
        if (is_null($name)) return;

        if (empty(trim($name)))
            throw new Exception("Skill tree name can't be empty.");

        if (iconv_strlen($name) > 50)
            throw new Exception("Skill tree name is too long: maximum of 50 characters.");

        $whereNot = [];
        if ($skillTreeId) $whereNot[] = ["id", $skillTreeId];
        $skillTreeNames = array_column(Core::database()->selectMultiple(self::TABLE_SKILL_TREE, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $skillTreeNames))
            throw new Exception("Duplicate skill tree name: '$name'");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a skill tree coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $skillTree
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $skillTree = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "maxReward"];
        $boolValues = ["inView"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $skillTree, $field, $fieldName);
    }

    /**
     * Trims skill tree parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name"];
        Utils::trim($params, ...$values);
    }
}
