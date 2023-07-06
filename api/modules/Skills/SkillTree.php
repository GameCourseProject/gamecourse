<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\AutoGame\RuleSystem\Tag;
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
    public static function getSkillTreeInView(int $courseId): ?SkillTree {
        $skillTreeId = Core::database()->select(self::TABLE_SKILL_TREE, ["course" => $courseId, "inView" => true], "id");
        if (!$skillTreeId) return null;
        else return new SkillTree($skillTreeId);
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
     * a FIXME: refactor.
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
                        Utils::importFromCSV(Tier::HEADERS, function ($tier, $indexes) use ($courseId, $replace, $skillTree) {
                            $name = Utils::nullify($tier[$indexes["name"]]);
                            $reward = self::parse(null, $tier[$indexes["reward"]], "reward");
                            $position = self::parse(null, $tier[$indexes["position"]], "position");
                            $isActive = self::parse(null, Utils::nullify($tier[$indexes["isActive"]]), "isActive");
                            $costType = Utils::nullify($tier[$indexes["costType"]]);
                            $cost = self::parse(null, $tier[$indexes["cost"]], "cost");
                            $increment = self::parse(null, $tier[$indexes["increment"]], "increment");
                            $minRating = self::parse(null, Utils::nullify($tier[$indexes["minRating"]]), "minRating");


                            $tier = Tier::getTierByName($skillTree->getId(), $name);

                            if ($tier){ // Tier already exists
                                if ($replace) { // replace
                                    $tier->editTier($name, $reward, $position, $isActive, $costType, $cost, $increment, $minRating);
                                }
                            } else { // tier doesn't exist
                                Tier::addTier($skillTree->getId(), $name, $reward, $costType, $cost, $increment, $minRating);
                                return 1;
                            }
                            return 0;
                        }, $tierFile);

                        $skillsFile = file_get_contents($tempFolder . "/skills.csv");
                        $skillsAndTiersFile = file_get_contents($tempFolder . "/skills-tiers.csv");
                        $skillsRulesFile = file_get_contents($tempFolder . "/skillsRules.csv");

                        // Skills exist, have connection with tiers and have rules associated
                        if ($skillsFile && $skillsAndTiersFile && $skillsRulesFile) {

                            // Prepares relation between tiers and skills
                            $skillsAndTiers = [];
                            Utils::importFromCSV(["tier", "skill"], function ($element, $indexes) use (&$skillsAndTiers) {
                                $tier = Utils::nullify($element[$indexes["tier"]]);
                                $skills = Utils::nullify($element[$indexes["skill"]]);

                                $skillsArray = explode(", ", $skills);
                                array_push($skillsAndTiers, ["tier" => $tier, "skill" => $skillsArray]);
                            }, $skillsAndTiersFile);

                            // Prepare relation between skills and rules
                            $skillsRules = [];
                            Utils::importFromCSV(Rule::HEADERS, function($rule, $indexes) use (&$skillsRules, $replace, $courseId) {
                                $name = Utils::nullify($rule[$indexes["name"]]);
                                $description = Utils::nullify($rule[$indexes["description"]]);
                                $whenClause = Utils::nullify(Rule::parseToExportAndImport($rule[$indexes["whenClause"]], "import"));
                                $thenClause = Utils::nullify(Rule::parseToExportAndImport($rule[$indexes["thenClause"]], "import"));
                                $position = self::parse(null, Utils::nullify($rule[$indexes["position"]]), "position");
                                $isActive = self::parse(null, Utils::nullify($rule[$indexes["isActive"]]), "isActive");

                                $tags = [];
                                $tagsIds = Utils::nullify($rule[$indexes["tags"]]);
                                if ($tagsIds) {
                                    $tagsIds = array_filter(array_map("trim", preg_split("/\s+/", $tagsIds)), function ($tag) use ($courseId) {
                                        return Rule::courseHasTag($courseId, $tag);
                                    });

                                    foreach ($tagsIds as $tagId){
                                        $tag = Tag::getTagById($tagId);
                                        array_push($tags, $tag);
                                    }
                                }

                                array_push($skillsRules, ["name" => $name, "rule" => [
                                    "name" => $name, "description" => $description, "whenClause" => $whenClause,
                                    "thenClause" => $thenClause, "position" => $position, "isActive" => $isActive, "tags" => $tags
                                ]]);
                            }, $skillsRulesFile);

                            // Import skills
                            Utils::importFromCSV(Skill::HEADERS, function ($skill, $indexes) use ($courseId, $replace,
                                $skillsAndTiers, $skillTree, $skillsRules) {
                                $name = Utils::nullify($skill[$indexes["name"]]);
                                $color = Utils::nullify($skill[$indexes["color"]]);
                                $page = Utils::nullify($skill[$indexes["page"]]);
                                $isCollab = self::parse(null, Utils::nullify($skill[$indexes["isCollab"]]), "isCollab");
                                $isExtra = self::parse(null, Utils::nullify($skill[$indexes["isExtra"]]), "isExtra");
                                $isActive = self::parse(null, Utils::nullify($skill[$indexes["isActive"]]), "isActive");
                                $position = self::parse(null, $skill[$indexes["position"]], "position");

                                $skills = Skill::getSkillsOfSkillTree($skillTree->getId());
                                $flagFound = false;
                                $index = 0;
                                foreach ($skills as $skillElement) {
                                    if ($skillElement["name"] === $name){ // skill already exists
                                        $skill = Skill::getSkillById($skillElement["id"]);
                                        $flagFound = true;
                                        if ($replace){ // replace
                                            $dependencies = $skill->getDependencies();

                                            $skillsTiersIndex = null;
                                            foreach ($skillsAndTiers as $index => $element) {
                                                if (isset($element["skill"]) && is_array($element["skill"]) && in_array($name, $element["skill"])) {
                                                    $skillsTiersIndex = $index;
                                                    break;
                                                }
                                            }

                                            $tierId = Tier::getTierByName($skillTree->getId(), $skillsAndTiers[$skillsTiersIndex]["tier"])->getId();
                                            // FIXME - Dependencies should also be imported as a new file
                                            $skill->editSkill($tierId, $name, $color, $page, $isCollab, $isExtra, $isActive, $position, $dependencies);

                                            $rule = $skillsRules[$name];
                                            if ($rule) { // rule exists in skill and needs to be edited
                                                $sectionId = Section::getSectionIdByModule($courseId, "Skills");
                                                $sectionRules = Rule::getRulesOfSection($sectionId);

                                                foreach ($sectionRules as $sectionRule){
                                                    if ($sectionRule["name"] === $name){
                                                        $ruleToEdit = Rule::getRuleById($sectionRule["id"]);
                                                        $ruleToEdit->editRule($rule["name"], $rule["description"],
                                                            $rule["whenClause"], $rule["thenClause"], intval($rule["position"]),
                                                            Rule::parse(null, Utils::nullify($rule["isActive"]), "isActive"),
                                                            $rule["tags"]
                                                        );
                                                        break;
                                                    }
                                                }
                                            } else { // rule doesn't exist
                                                $skill->addRule($courseId, $tierId, $index, $skill->hasWildcardDependency(), $skill->getName(), $dependencies);
                                            }
                                        }
                                        break;
                                    }
                                    $index++;
                                }

                                if (!$flagFound) { // skill doesn't exist

                                    $skillsTiersIndex = null;
                                    foreach ($skillsAndTiers as $index => $element) {
                                        if (isset($element["skill"]) && is_array($element["skill"]) && in_array($name, $element["skill"])) {
                                            $skillsTiersIndex = $index;
                                            break;
                                        }
                                    }

                                    $importedTier = Tier::getTierByName($skillTree->getId(), $skillsAndTiers[$skillsTiersIndex]["tier"]);
                                    Skill::addSkill($importedTier->getId(), $name, $color, $page, $isCollab, $isExtra, []);  // FIXME dependencies missing

                                    $sectionId = Section::getSectionIdByModule($courseId, "Skills");
                                    $rule = $skillsRules[$name];
                                    Rule::addRule($courseId, $sectionId, $rule["name"], $rule["description"],
                                        $rule["whenClause"], $rule["thenClause"], intval($rule["position"]),
                                        Rule::parse(null, Utils::nullify($rule["isActive"]), "isActive"),
                                        $rule["tags"]);
                                    return 1;
                                }
                                return 0;

                            }, $skillsFile);
                        }
                    }
                }
            } else { // skillTree doesn't exist
                $skillTree = self::addSkillTree($courseId, $name, $maxReward);

                $tierFile = file_get_contents($tempFolder . "/tiers.csv");
                if ($tierFile) { // There are tiers to be imported

                    Utils::importFromCSV(Tier::HEADERS, function ($tier, $indexes) use ($courseId, $replace, $skillTree) {
                        $name = Utils::nullify($tier[$indexes["name"]]);
                        $reward = self::parse(null, $tier[$indexes["reward"]], "reward");
                        $position = self::parse(null, $tier[$indexes["position"]], "position");
                        $isActive = self::parse(null, Utils::nullify($tier[$indexes["isActive"]]), "isActive");
                        $costType = Utils::nullify($tier[$indexes["costType"]]);
                        $cost = self::parse(null, $tier[$indexes["cost"]], "cost");
                        $increment = self::parse(null, $tier[$indexes["increment"]], "increment");
                        $minRating = self::parse(null, Utils::nullify($tier[$indexes["minRating"]]), "minRating");

                        $tier = Tier::getTierByName($skillTree->getId(), $name);

                        if ($tier){ // Tier already exists
                            if ($replace) { // replace
                                $tier->editTier($name, $reward, $position, $isActive, $costType, $cost, $increment, $minRating);
                            }
                        } else { // tier doesn't exist
                            Tier::addTier($skillTree->getId(), $name, $reward, $costType, $cost, $increment, $minRating);
                            return 1;
                        }
                        return 0;

                    }, $tierFile);


                    $skillsFile = file_get_contents($tempFolder . "/skills.csv");
                    $skillsAndTiersFile = file_get_contents($tempFolder . "/skills-tiers.csv");
                    $skillsRulesFile = file_get_contents($tempFolder . "/skillsRules.csv");

                    // Skills exist, have connection with tiers and have rules associated
                    if ($skillsFile && $skillsAndTiersFile && $skillsRulesFile) {

                        // Prepares relation between tiers and skills
                        $skillsAndTiers = [];
                        Utils::importFromCSV(["tier", "skill"], function ($element, $indexes) use (&$skillsAndTiers) {
                            $tier = Utils::nullify($element[$indexes["tier"]]);
                            $skills = Utils::nullify($element[$indexes["skill"]]);

                            $skillsArray = explode(", ", $skills);
                            array_push($skillsAndTiers, ["tier" => $tier, "skill" => $skillsArray]);
                        }, $skillsAndTiersFile);

                        // Prepare relation between skills and rules
                        $skillsRules = [];
                        Utils::importFromCSV(Rule::HEADERS, function($rule, $indexes) use (&$skillsRules, $replace, $courseId) {
                            $name = Utils::nullify($rule[$indexes["name"]]);
                            $description = Utils::nullify($rule[$indexes["description"]]);
                            $whenClause = Utils::nullify(Rule::parseToExportAndImport($rule[$indexes["whenClause"]], "import"));
                            $thenClause = Utils::nullify(Rule::parseToExportAndImport($rule[$indexes["thenClause"]], "import"));
                            $position = self::parse(null, Utils::nullify($rule[$indexes["position"]]), "position");
                            $isActive = self::parse(null, Utils::nullify($rule[$indexes["isActive"]]), "isActive");

                            $tags = [];
                            $tagsIds = Utils::nullify($rule[$indexes["tags"]]);
                            if ($tagsIds) {
                                $tagsIds = array_filter(array_map("trim", preg_split("/\s+/", $tagsIds)), function ($tag) use ($courseId) {
                                    return Rule::courseHasTag($courseId, $tag);
                                });

                                foreach ($tagsIds as $tagId){
                                    $tag = Tag::getTagById($tagId);
                                    array_push($tags, $tag);
                                }
                            }

                            array_push($skillsRules, ["name" => $name, "rule" => [
                                "name" => $name, "description" => $description, "whenClause" => $whenClause,
                                "thenClause" => $thenClause, "position" => $position, "isActive" => $isActive, "tags" => $tags
                            ]]);
                        }, $skillsRulesFile);

                        // Import Skills
                        Utils::importFromCSV(Skill::HEADERS, function ($skill, $indexes) use ($courseId, $replace,
                            $skillsAndTiers, $skillTree, $skillsRules) {
                            $name = Utils::nullify($skill[$indexes["name"]]);
                            $color = Utils::nullify($skill[$indexes["color"]]);
                            $page = Utils::nullify($skill[$indexes["page"]]);
                            $isCollab = self::parse(null, $skill[$indexes["isCollab"]], "isCollab");
                            $isExtra = self::parse(null, $skill[$indexes["isExtra"]], "isExtra");

                            $skillsTiersIndex = null;
                            foreach ($skillsAndTiers as $index => $element) {
                                if (isset($element["skill"]) && is_array($element["skill"]) && in_array($name, $element["skill"])) {
                                    $skillsTiersIndex = $index;
                                    break;
                                }
                            }

                            $importedTier = Tier::getTierByName($skillTree->getId(), $skillsAndTiers[$skillsTiersIndex]["tier"]);
                            $skill = Skill::addSkill($importedTier->getId(), $name, $color, $page, $isCollab, $isExtra, []);  // FIXME dependencies missing

                            $ruleIndex = null;
                            foreach($skillsRules as $index => $skillsRule){
                                if ($skillsRule["name"] == $name){
                                    $ruleIndex = $index;
                                    break;
                                }
                            }

                            if ($ruleIndex) { // it means there's a rule to be imported
                                $rule = $skillsRules[$ruleIndex]["rule"];
                                $sectionId = Section::getSectionIdByModule($courseId, "Skills");
                                $sectionRulesNames = array_map(function ($sectionRule) {return $sectionRule["name"];}, Rule::getRulesOfSection($sectionId));

                                // rule already exists in system
                                if (in_array($rule["name"], $sectionRulesNames)){
                                    $ruleToEdit = Rule::getRuleByName($courseId, $rule["name"]);
                                    $ruleToEdit->editRule($rule["name"], $rule["description"],
                                        $rule["whenClause"], $rule["thenClause"], intval($rule["position"]),
                                        Rule::parse(null, Utils::nullify($rule["isActive"]), "isActive"),
                                        $rule["tags"]
                                    );
                                } else { // rule doesn't exist in system
                                    Rule::addRule($courseId, $sectionId, $rule["name"], $rule["description"],
                                        $rule["whenClause"], $rule["thenClause"], intval($rule["position"]),
                                        Rule::parse(null, Utils::nullify($rule["isActive"]), "isActive"),
                                        $rule["tags"]);
                                }
                            }
                        }, $skillsFile);
                    }

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
            $skillsAndTiers = [];

            // Add tiers .csv file
            $tiers = $skillTree->getTiers();

            $zip->addFromString("tiers.csv", Utils::exportToCSV($tiers, function ($tier) use (&$skillsAndTiers) {
                array_push($skillsAndTiers, ["tier" => $tier["name"], "skill" => null ]);
                return [$tier["name"], $tier["reward"], $tier["position"], +$tier["isActive"],
                    $tier["costType"], $tier["cost"], $tier["increment"], $tier["minRating"]];
            }, Tier::HEADERS));

            // Add skills .csv file
            $skills = $skillTree->getSkills();
            $zip->addFromString("skills.csv", Utils::exportToCSV($skills, function ($skill) use (&$skillsAndTiers) {
                $tier = Tier::getTierById($skill["tier"]);
                $index = array_search($tier->getName(), $skillsAndTiers);

                if ($skillsAndTiers[$index]["skill"] == null ){
                    $skillsAndTiers[$index]["skill"] = $skill["name"];

                } else {
                    $skillsAndTiers[$index]["skill"] .=  ", " . $skill["name"];
                }

                return [$skill["name"], $skill["color"], $skill["page"], +$skill["isCollab"], +$skill["isExtra"], +$skill["isActive"], $skill["position"]];
            }, Skill::HEADERS));

            $zip->addFromString("skills-tiers.csv", Utils::exportToCSV($skillsAndTiers, function($element) {
                return [$element["tier"], $element["skill"]];
            }, ["tier", "skill"]));

            // Skill's rules as well
            $skillsSection = RuleSystem::getSectionIdByModule($courseId, "Skills");
            $skillRules = Rule::getRulesOfSection($skillsSection);
            $zip->addFromString("skillsRules.csv", Utils::exportToCSV($skillRules, function ($skillRule) {
                $whenClause = Rule::parseToExportAndImport($skillRule["whenClause"], "export");
                $thenClause = Rule::parseToExportAndImport($skillRule["thenClause"], "export");

                return [$skillRule["name"], $skillRule["description"], $whenClause, $thenClause,
                    +$skillRule["isActive"], $skillRule["position"], ""]; // tags are omitted
            }, Rule::HEADERS));


            // FIXME -- dependencies missing
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
