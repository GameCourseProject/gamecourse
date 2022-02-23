<?php
namespace Modules\Skills;

use GameCourse\API;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\RuleSystem;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Expression\ValueNode;
use GameCourse\Views\Views;
use Modules\AwardList\AwardList;
use Modules\XP\XPLevels;
use Utils;

class Skills extends Module
{
    const ID = 'skills';

    const TABLE = 'skill';
    const TABLE_TREES = self::TABLE . '_tree';
    const TABLE_TIERS = self::TABLE . '_tier';
    const TABLE_DEPENDENCIES = self::TABLE . '_dependency';
    const TABLE_SUPER_SKILLS = 'dependency';
    const TABLE_WILDCARD = 'award_wildcard';

    const SKILL_TREE_TEMPLATE = 'Skill Tree - by skills';
    const SKILLS_OVERVIEW_TEMPLATE = 'Skills Overview - by skills';

    const SKILL_RULE_TEMPLATE = 'rule_skill_template_basic.txt';
    const WILDCARD_RULE_TEMPLATE = 'rule_skill_template_wildcard.txt';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->setupData($this->getCourseId());
        $this->initDictionary();
        $this->initTemplates();
    }

    public function initDictionary()
    {
        $courseId = $this->getCourseId();

        /*** ------------ Libraries ------------ ***/

        Dictionary::registerLibrary(self::ID, "skillTrees", "This library provides information regarding Skill Trees. It is provided by the skills module.");


        /*** ------------ Functions ------------ ***/

        //skillTrees.getTree(id), returns tree object
        Dictionary::registerFunction(
            'skillTrees',
            'getTree',
            function (int $id) {
                //this is slightly pointless if the skill tree only has id and course
                //but it could eventualy have more atributes
                return Dictionary::createNode(Core::$systemDB->select(self::TABLE_TREES, ["id" => $id]), 'skillTrees');
            },
            'Returns the object skillTree with the id id.',
            'object',
            'tree',
            'library',
            null,
            true
        );

        //skillTrees.trees, returns collection w all trees
        Dictionary::registerFunction(
            'skillTrees',
            'trees',
            function () use ($courseId) {
                return Dictionary::createNode(Core::$systemDB->selectMultiple(self::TABLE_TREES, ["course" => $courseId]), 'skillTrees', "collection");
            },
            'Returns a collection will all the Skill Trees in the Course.',
            'collection',
            'tree',
            'library',
            null,
            true
        );

        //%tree.getAllSkills(...) returns collection
        Dictionary::registerFunction(
            'skillTrees',
            'getAllSkills',
            function ($tree = null, $tier = null, $dependsOn = null, $requiredBy = null, $isActive = true) use ($courseId) {
                //can be called by skillTrees or by %tree
                $skillWhere = ["course" => $courseId];
                $parent = null;
                if ($tree !== null) {
                    if (is_array($tree)) {
                        $skillWhere["treeId"] = $tree["value"]["id"];
                        $parent = $tree;
                    } else
                        $skillWhere["treeId"] = $tree;
                }
                if ($tier !== null) {
                    if (is_array($tier))
                        $skillWhere["tier"] = $tier["value"]["tier"];
                    else
                        $skillWhere["tier"] = $tier;
                }
                if ($isActive) {
                    $skillWhere["isActive"] = true;
                }
                //if there are dependencies arguments we do more complex selects
                if ($dependsOn !== null) {
                    if ($requiredBy != null)
                        return $this->getSkillsDependantAndRequired($dependsOn, $requiredBy, $skillWhere, $parent);
                    return $this->getSkillsDependantof($dependsOn, $skillWhere, $parent);
                } else if ($requiredBy !== null) {
                    return $this->getSkillsRequiredBy($dependsOn, $skillWhere, $parent);
                }
                return Dictionary::createNode(Core::$systemDB->selectMultiple(
                    self::TABLE . " s natural join " . self::TABLE_TIERS . " t join " . self::TABLE_TREES . " tr on tr.id=treeId",
                    $skillWhere,
                    "s.*,t.*"
                ), 'skillTrees', "collection", $parent);
            },
            "Returns a collection with all the skills in the Course. The optional parameters can be used to find skills that specify a given combination of conditions:\ntree: The skillTree object or the id of the skillTree object to which the skill belongs to.\ntier: The tier object or tier of the tier object of the skill.\ndependsOn: a skill that is used to unlock a specific skill.\nrequiredBy: a skill that unlocks a collection of skills.\nisActive: a skill that is active.",
            'collection',
            'skill',
            'object',
            'tree',
            true
        );

        //%tree.getSkill(name), returns skill object
        Dictionary::registerFunction(
            'skillTrees',
            'getSkill',
            function ($tree, string $name) {
                Dictionary::checkArray($tree, "object", "getSkill()");
                $skill = Core::$systemDB->select(
                    self::TABLE . " natural join " . self::TABLE_TIERS,
                    ["treeId" => $tree["value"]["id"], "name" => $name]
                );
                if (empty($skill)) {
                    throw new \Exception("In function getSkill(...): No skill found with name=" . $name);
                }
                return Dictionary::createNode($skill, 'skillTrees');
            },
            'Returns a skill object from a skillTree with a specific name.',
            'object',
            'skill',
            'object',
            'tree',
            true
        );

        //%tree.getTier(number), returns tier object
        Dictionary::registerFunction(
            'skillTrees',
            'getTier',
            function ($tree, int $number) {
                $tier = Core::$systemDB->select(
                    self::TABLE_TIERS,
                    ["treeId" => $tree["value"]["id"], "tier" => $number]
                );
                if (empty($tier)) {
                    throw new \Exception("In function getTier(...): No tier found with number=" . $number);
                }
                return Dictionary::createNode($tier, 'skillTrees');
            },
            'Returns a tier object with a specific number from a skillTree.',
            'object',
            'tier',
            'object',
            'tree',
            true
        );

        //%tree.tiers, returns collection w all tiers of the tree
        Dictionary::registerFunction(
            'skillTrees',
            'tiers',
            function ($tree) {
                return Dictionary::createNode(
                    Core::$systemDB->selectMultiple(
                        self::TABLE_TIERS,
                        ["treeId" => $tree["value"]["id"]],
                        "*",
                        "seqId asc"
                    ),
                    'skillTrees',
                    "collection",
                    $tree
                );
            },
            'Returns a string with name of the tier.',
            'collection',
            'tier',
            'object',
            'tree',
            true
        );

        //%tier.skills(isActive), returns collection w all skills of the tier
        Dictionary::registerFunction(
            'skillTrees',
            'skills',
            function ($tier, bool $isActive = true) {
                Dictionary::checkArray($tier, "object", "skills");

                $where = [
                    "s.treeId" => $tier["value"]["treeId"],
                    "t.treeId" => $tier["value"]["treeId"],
                    "s.tier" => $tier["value"]["tier"]
                ];

                if ($isActive) {
                    $where["s.isActive"] = true;
                }
                $skills = Core::$systemDB->selectMultiple(
                    self::TABLE . " s join " . self::TABLE_TIERS . " t on s.tier = t.tier",
                    $where,
                    "s.*",
                    "s.seqId asc"
                );
                return Dictionary::createNode(
                    $skills,
                    'skillTrees',
                    "collection",
                    $tier
                );
            },
            'Returns a collection of skill objects from a specific tier.',
            'collection',
            'skill',
            'object',
            'tier',
            true
        );

        //%tier.nextTier, returns tier object
        Dictionary::registerFunction(
            'skillTrees',
            'nextTier',
            function ($tier) {
                $nexttier = Core::$systemDB->select(
                    self::TABLE_TIERS,
                    ["treeId" => $tier["value"]["treeId"], "tier" => $tier["value"]["tier"] + 1]
                );
                if (empty($nexttier))
                    throw new \Exception("In function .nextTier: Couldn't findo tier after tier nº" . $tier["value"]["tier"]);
                return Dictionary::createNode($nexttier, 'skillTrees');
            },
            'Returns the next tier object from a skillTree.',
            'object',
            'tier',
            'object',
            'tier',
            true
        );

        //%tier.previousTier, returns tier object
        Dictionary::registerFunction(
            'skillTrees',
            'previousTier',
            function ($tier) {
                $prevtier = Core::$systemDB->select(
                    self::TABLE_TIERS,
                    ["treeId" => $tier["value"]["treeId"], "tier" => $tier["value"]["tier"] - 1]
                );
                if (empty($prevtier))
                    throw new \Exception("In function .previousTier: Couldn't findo tier before tier nº" . $tier["value"]["tier"]);
                return Dictionary::createNode($prevtier, 'skillTrees');
            },
            'Returns the previous tier object from a skillTree.',
            'object',
            'tier',
            'object',
            'tier',
            true
        );

        //%tier.reward
        Dictionary::registerFunction(
            'skillTrees',
            'reward',
            function ($arg) {
                return Dictionary::basicGetterFunction($arg, "reward");
            },
            'Returns a string with the reward of completing a skill from that tier.',
            'string',
            null,
            'object',
            'tier',
            true
        );

        //%tier.usedWildcards
        Dictionary::registerFunction(
            'skillTrees',
            'usedWildcards',
            function ($arg, $user) {
                $tierName = $arg["value"]["tier"];
                $course = $arg["value"]["parent"]["value"]["course"];
                return new ValueNode($this->getUsedWildcards($tierName, $user,  $course));
            },
            'Returns a string with the number of wildcards from this tier that have been used by a user.',
            'string',
            null,
            'object',
            'tier',
            true
        );

        //%tier.hasWildcards
        Dictionary::registerFunction(
            'skillTrees',
            'hasWildcards',
            function ($arg) {
                $tierName = $arg["value"]["tier"];
                $course = $arg["value"]["parent"]["value"]["course"];
                return new ValueNode($this->tierHasWildcards($tierName,  $course));
            },
            'Returns a bool that indicates if a tier has wildcards (i.e. if other skills depend on this tier).',
            'boolean',
            null,
            'object',
            'tier',
            true
        );

        //%tier.tier
        Dictionary::registerFunction(
            'skillTrees',
            'tier',
            function ($arg) {
                return Dictionary::basicGetterFunction($arg, "tier");
            },
            'Returns a string with the numeric value of the tier.',
            'string',
            null,
            'object',
            'tier',
            true
        );

        //%skill.id
        Dictionary::registerFunction(
            'skillTrees',
            'id',
            function ($skill) {
                return Dictionary::basicGetterFunction($skill, "id");
            },
            'Returns a number with the id of the skill.',
            'number',
            null,
            'object',
            'skill',
            true
        );

        //%skill.tier
        Dictionary::registerFunction(
            'skillTrees',
            'tier',
            function ($arg) {
                return Dictionary::basicGetterFunction($arg, "tier");
            },
            'Returns a string with the numeric value of the tier.',
            'string',
            null,
            'object',
            'skill',
            true
        );

        //%skill.color
        Dictionary::registerFunction(
            'skillTrees',
            'color',
            function ($skill) {
                return Dictionary::basicGetterFunction($skill, "color");
            },
            'Returns a string with the reference of the color in hexadecimal of the skill.',
            'string',
            null,
            'object',
            'skill',
            true
        );

        //%skill.name
        Dictionary::registerFunction(
            'skillTrees',
            'name',
            function ($skill) {
                return Dictionary::basicGetterFunction($skill, "name");
            },
            'Returns a string with the name of the skill.',
            'string',
            null,
            'object',
            'skill',
            true
        );

        //%skill.isActive
        Dictionary::registerFunction(
            'skillTrees',
            'isActive',
            function ($skill) {
                return Dictionary::basicGetterFunction($skill, "isActive");
            },
            'Returns true if the skill is active, and false otherwise.',
            'boolean',
            null,
            'object',
            'skill',
            true
        );

        //%skill.getPost(user)
        Dictionary::registerFunction(
            'skillTrees',
            'getPost',
            function ($skill, $user) use ($courseId) {
                Dictionary::checkArray($skill, "object", "getPost()");
                $userId = $this->getUserId($user);

                $columns = AwardList::TABLE . " left join award_participation on " . AwardList::TABLE . ".id=award_participation.award left join participation on award_participation.participation=participation.id";
                $post = Core::$systemDB->select(
                    $columns,
                    [AwardList::TABLE . ".type" => "skill", AwardList::TABLE . ".moduleInstance" => $skill["value"]["id"], AwardList::TABLE . ".user" => $userId, AwardList::TABLE . ".course" => $courseId],
                    "post"
                );

                if (!empty($post)) {
                    $postURL = "https://pcm.rnl.tecnico.ulisboa.pt/moodle/" . $post;
                } else {
                    $postURL = $post;
                }
                return new ValueNode($postURL);
            },
            'Returns a string with the link to the post of the skill made by the GameCourseUser identified by user.',
            'string',
            null,
            'object',
            'skill',
            true
        );

        //%skill.isUnlocked(user), returns true if skill is available to the user
        Dictionary::registerFunction(
            'skillTrees',
            'isUnlocked',
            function ($skill, $user) use ($courseId) {
                Dictionary::checkArray($skill, "object", "isUnlocked(...)");
                return new ValueNode($this->isSkillUnlocked($skill, $user, $courseId));
            },
            'Returns a boolean regarding whether the GameCourseUser identified by user has unlocked a skill.',
            'boolean',
            null,
            'object',
            'skill',
            true
        );

        //%skill.isCompleted(user), returns true if skill has been achieved by the user
        Dictionary::registerFunction(
            'skillTrees',
            'isCompleted',
            function ($skill, $user) use ($courseId) {
                Dictionary::checkArray($skill, "object", "isCompleted(...)");
                return new ValueNode($this->isSkillCompleted($skill, $user, $courseId));
            },
            'Returns a boolean regarding whether the GameCourseUser identified by user has completed a skill.',
            'boolean',
            null,
            'object',
            'skill',
            true
        );

        //%skill.completedBy(), returns a collection with the users that completed the skill
        Dictionary::registerFunction(
            'skillTrees',
            'completedBy',
            function ($skill) use ($courseId) {
                return Dictionary::createNode($this->skillCompletedBy($skill['value']['id'], $courseId), 'users', "collection");
            },
            'Returns a collection of the users that completed the skill.',
            'collection',
            null,
            'object',
            'skill',
            true
        );

        //%skill.dependsOn,return colection of dependencies, each has a colection of skills
        Dictionary::registerFunction(
            'skillTrees',
            'dependsOn',
            function ($skill) {
                Dictionary::checkArray($skill, "object", "dependsOn");
                $dep = Core::$systemDB->selectMultiple(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skill["value"]["id"]]);
                return Dictionary::createNode($dep, 'skillTrees', "collection", $skill);
            },
            'Returns a collection of dependency objects that require the skill on any dependency.',
            'collection',
            'dependency',
            'object',
            'skill',
            true
        );

        //%skill.requiredBy, returns collection of skills that depend on the given skill
        Dictionary::registerFunction(
            'skillTrees',
            'requiredBy',
            function ($skill) {
                Dictionary::checkArray($skill, "object", "requiredBy");
                return $this->getSkillsDependantof($skill);
            },
            'Returns a collection of skill objects that are required by the skill on any dependency.',
            'collection',
            'skill',
            'object',
            'skill',
            true
        );

        //%dependency.simpleSkills, returns collection of the required/normal/simple skills of a dependency
        Dictionary::registerFunction(
            'skillTrees',
            'simpleSkills',
            function ($dep) {
                Dictionary::checkArray($dep, "object", "simpleSkills");
                $depSkills = Core::$systemDB->selectMultiple(
                    self::TABLE_DEPENDENCIES . " join " . self::TABLE . " s on s.id=normalSkillId",
                    ["dependencyId" => $dep["value"]["id"]],
                    "s.*"
                );
                return Dictionary::createNode($depSkills, 'skillTrees', "collection", $dep);
            },
            'Returns a collection of skills that are required to unlock a super skill from a dependency.',
            'collection',
            'skill',
            'object',
            'dependency',
            true
        );

        //%dependency.dependencies, returns names of the required/normal/simple skills/tiers of a dependency
        Dictionary::registerFunction(
            'skillTrees',
            'dependencies',
            function ($dep) {
                $depSkills = Core::$systemDB->selectMultiple(
                    self::TABLE_DEPENDENCIES . " join " . self::TABLE . " s on s.id=normalSkillId",
                    ["dependencyId" => $dep["value"]["id"], "isTier" => false],
                    "s.*"
                );
                $tiers = Core::$systemDB->selectMultiple(
                    self::TABLE_DEPENDENCIES . " join " . self::TABLE_TIERS . " t on t.id=normalSkillId",
                    ["dependencyId" => $dep["value"]["id"], "isTier" => true],
                    "t.*"
                );
                if (!empty($tiers)) {
                    foreach ($tiers as &$tier) {
                        $tier["name"] = $tier["tier"];
                        array_push($depSkills, $tier);
                    }
                }
                return Dictionary::createNode($depSkills, 'skillTrees', "collection", $dep);
            },
            'Returns the names of skills and tiers that are required to unlock a super skill from a dependency.',
            'collection',
            'skill',
            'object',
            'dependency',
            true
        );

        //%dependency.superSkill, returns skill object
        Dictionary::registerFunction(
            'skillTrees',
            'superSkill',
            function ($dep) {
                Dictionary::checkArray($dep, "object", "superSkill", "superSkill");
                return Dictionary::createNode($dep["value"]["superSkill"], 'skillTrees');
            },
            'Returns the super skill of a dependency.',
            'object',
            'skill',
            'object',
            'dependency',
            true
        );

        //%skill.getStyle(user)
        Dictionary::registerFunction(
            'skillTrees',
            'getStyle',
            function ($skill, $user) use ($courseId) {
                Dictionary::checkArray($skill, "object", "getStyle");
                $style = "background-color: ";
                if ($this->isSkillUnlocked($skill, $user, $courseId)) {
                    $style .= $skill["value"]["color"] . ";";
                    if ($this->isSkillCompleted($skill, $user, $courseId)) {
                        $style .= "box-shadow: 0 0 30px 5px green;";
                    }
                } else {
                    $style .= "#6d6d6d;";
                }
                return new ValueNode($style);
            },
            'Returns a string with the style of the skill from a GameCourseUser identified by user. This function is used to render a skill block in a view.',
            'string',
            null,
            'object',
            'skill',
            true
        );

        //skillTrees.wildcardAvailable(tierName,user)
        Dictionary::registerFunction(
            'skillTrees',
            'wildcardAvailable',
            function ($skill, $tier, $user) use ($courseId) {
                return Dictionary::createNode($this->getAvailableWildcards($skill, $tier, $user, $courseId), "skillTrees", "object");
            },
            'Returns a boolean regarding whether the GameCourseUser identified by user has "wildcards" to use from a certain tier.',
            'boolean',
            null,
            'library',
            null,
            true
        );
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::SKILL_TREE_TEMPLATE))
            Views::createTemplateFromFile(self::SKILL_TREE_TEMPLATE, file_get_contents(__DIR__ . '/skillTree.txt'), $courseId, self::ID);

//        if (!Views::templateExists($courseId, self::SKILLS_OVERVIEW_TEMPLATE)) // FIXME: needs refactor
//            Views::createTemplateFromFile(self::SKILLS_OVERVIEW_TEMPLATE, file_get_contents(__DIR__ . '/skillsOverview.txt'), $courseId, self::ID);
    }

    public function initAPIEndpoints()
    {
        // Tiers
        /**
         * Gets all skill tiers in course.
         *
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getTiers', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(["tiers" => $this->getTiers($courseId, true)]);
        });

        /**
         * Creates a new tier in the course skill tree.
         *
         * @param int $courseId
         * @param $tier
         */
        API::registerFunction(self::ID, 'createTier', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'tier');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(["tier" => $this->newTier(API::getValue('tier'), $courseId)]);
        });

        /**
         * Edit an existing tier in the course skill tree.
         *
         * @param int $courseId
         * @param $tier
         */
        API::registerFunction(self::ID, 'editTier', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'tier');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $this->editTier(API::getValue('tier'), $courseId);
        });

        /**
         * Deletes a tier from the course skill tree.
         *
         * @param int $courseId
         * @param $tier
         */
        API::registerFunction(self::ID, 'deleteTier', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'tier');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $this->deleteTier(API::getValue('tier'), $courseId);
        });


        // Skills
        /**
         * Gets all skills in course.
         *
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getSkills', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(["skills" => $this->getSkills($courseId)]);
        });

        /**
         * Creates a new skill in the course skill tree.
         *
         * @param int $courseId
         * @param $skill
         */
        API::registerFunction(self::ID, 'createSkill', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'skill');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $this->newSkill(API::getValue('skill'), $courseId);
        });

        /**
         * Edit an existing skill in the course skill tree.
         *
         * @param int $courseId
         * @param $skill
         */
        API::registerFunction(self::ID, 'editSkill', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'skill');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $this->editSkill(API::getValue('skill'), $courseId);
        });

        /**
         * Deletes a skill from the course skill tree.
         *
         * @param int $courseId
         * @param int $skillId
         */
        API::registerFunction(self::ID, 'deleteSkill', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'skillId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $this->deleteSkill(API::getValue('skillId'), $courseId);
        });

        /**
         * Renders a skill page.
         *
         * @param int $courseId
         * @param int $skillId
         */
        API::registerFunction(self::ID, 'renderSkillPage', function () {
            API::requireValues('courseId', 'skillId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $skillId = API::getValue('skillId');
            API::verifySkillExists($courseId, $skillId);

            $skill = Core::$systemDB->select(self::TABLE, ["id" => $skillId], "id,name,page,color,tier,seqId,isActive");
            $skill['isActive'] = boolval($skill["isActive"]);
            $skill["description"] = $this->getDescriptionFromPage($skill, $courseId);
            unset($skill["page"]);

            API::response(['skill' => $skill]);
        });
    }

    public function setupResources()
    {
        parent::addResources('css/skills.css');
        parent::addResources('imgs');
    }

    public function setupData($courseId)
    {
        if ($this->addTables(self::ID, self::TABLE) || empty(Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId]))) {
            Core::$systemDB->insert(self::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        }
        $folder = Course::getCourseDataFolder($courseId);
        if (!file_exists($folder . "/" . self::ID))
            mkdir($folder . "/" . self::ID);
    }

    public function update_module($compatibleVersions)
    {
        //obter o ficheiro de configuração do module para depois o apagar
        $configFile = MODULES_FOLDER . "/" . self::ID . "/config.json";
        $contents = array();
        if (file_exists($configFile)) {
            $contents = json_decode(file_get_contents($configFile));
            unlink($configFile);
        }
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson($courseId)
    {
        $skillModuleArr = array();
        $skillTreeArray = array();
        $skillTierArray = array();
        $skillArray = array();
        $dependencyArray = array();
        $skillDependencyArray = array();

        if (Core::$systemDB->tableExists(self::TABLE_TREES)) {
            $skillTreeVarDB_ = Core::$systemDB->selectMultiple(self::TABLE_TREES, ["course" => $courseId], "*");
            if ($skillTreeVarDB_) {
                //values da skill_tree
                foreach ($skillTreeVarDB_ as $skillTreeVarDB) {
                    array_push($skillTreeArray, $skillTreeVarDB);

                    if (Core::$systemDB->tableExists(self::TABLE_TIERS)) {
                        $skillTierVarDB_ = Core::$systemDB->selectMultiple(self::TABLE_TIERS, ["treeId" => $skillTreeVarDB["id"]], "*");
                        if ($skillTierVarDB_) {
                            //values da skill_tier
                            foreach ($skillTierVarDB_ as $skillTierVarDB) {
                                array_push($skillTierArray, $skillTierVarDB);

                                if (Core::$systemDB->tableExists(self::TABLE)) {
                                    $skillVarDB_ = Core::$systemDB->selectMultiple(self::TABLE, ["treeId" => $skillTreeVarDB["id"], "tier" => $skillTierVarDB["tier"]], "*");
                                    if ($skillVarDB_) {
                                        //values da skill
                                        foreach ($skillVarDB_ as $skillVarDB) {
                                            array_push($skillArray, $skillVarDB);
                                            if (Core::$systemDB->tableExists(self::TABLE_SUPER_SKILLS)) {
                                                $dependencyDB_ = Core::$systemDB->selectMultiple(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skillVarDB["id"]], "*");
                                                if ($dependencyDB_) {
                                                    //values da dependency
                                                    foreach ($dependencyDB_ as $dependencyDB) {
                                                        array_push($dependencyArray, $dependencyDB);
                                                        if (Core::$systemDB->tableExists(self::TABLE_DEPENDENCIES)) {
                                                            $skillDependencyDB_ = Core::$systemDB->selectMultiple(self::TABLE_DEPENDENCIES, ["dependencyId" => $dependencyDB["id"]], "*");
                                                            if ($skillDependencyDB_) {
                                                                foreach ($skillDependencyDB_ as $skillDependencyDB) {
                                                                    array_push($skillDependencyArray, $skillDependencyDB);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($skillTreeArray) {
            $skillModuleArr[self::TABLE_TREES] = $skillTreeArray;
        }
        if ($skillTierArray) {
            $skillModuleArr[self::TABLE_TIERS] = $skillTierArray;
        }
        if ($skillArray) {
            $skillModuleArr["skill"] = $skillArray;
        }
        if ($dependencyArray) {
            $skillModuleArr[self::TABLE_SUPER_SKILLS] = $dependencyArray;
        }
        if ($skillDependencyArray) {
            $skillModuleArr[self::TABLE_DEPENDENCIES] = $skillDependencyArray;
        }

        if ($skillModuleArr) {
            return $skillModuleArr;
        } else {
            return false;
        }
    }

    public function readConfigJson($courseId, $tables, $update): array
    {
        $tableName = array_keys($tables);
        $skillTreeIds = array();
        $skillIds = array();
        $dependencyIds = array();
        $skillTierIds = array();

        $i = 0;
        foreach ($tables as $table) {
            foreach ($table as $entry) {
                if ($tableName[$i] == self::TABLE_TREES) {
                    $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], ["maxReward" => $entry["maxReward"]], ["course" => $existingCourse]);
                        $updatedTreeId = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "id");
                        $skillTreeIds[$entry["id"]] = $updatedTreeId;
                    } else {
                        $idImport = $entry["id"];
                        unset($entry["id"]);
                        $entry["course"] = $courseId;
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                        $skillTreeIds[$idImport] = $newId;
                    }
                } else if ($tableName[$i] == self::TABLE_TIERS) {
                    $existingCourse = Core::$systemDB->select(self::TABLE_TIERS, ["treeId" => $skillTreeIds[$entry["treeId"]], "tier" => $entry["tier"]]);
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], ["reward" => $entry["reward"]], ["treeId" => $skillTreeIds[$entry["treeId"]], "tier" => $entry["tier"]]);
                    } else {
                        $treeIdImport = $entry["treeId"]; //old tree id
                        $entry["treeId"] = $skillTreeIds[$treeIdImport]; //new tree id
                        $tierIdImport = $entry["id"]; //old tier id
                        unset($entry["id"]);
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                        $skillTierIds[$tierIdImport] = $newId;
                    }
                } else if ($tableName[$i] == self::TABLE) {
                    $existingSkill = Core::$systemDB->select(self::TABLE, ["treeId" => $skillTreeIds[$entry["treeId"]], "tier" => $entry["tier"]]);
                    if ($update && $existingSkill) {
                        $newTreeId = $skillTreeIds[$entry["treeId"]];
                        $newTierId = $entry["tier"];
                        unset($entry["treeId"]);
                        unset($entry["tier"]);
                        unset($entry["id"]);
                        Core::$systemDB->update($tableName[$i], $entry, ["treeId" => $newTreeId, "tier" => $newTierId]);
                    } else {
                        $idImport = $entry["id"];
                        unset($entry["id"]);
                        $treeIdImport = $entry["treeId"];
                        $entry["treeId"] = $skillTreeIds[$treeIdImport];
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                        $skillIds[$idImport] = $newId;
                    }
                } else if ($tableName[$i] == self::TABLE_SUPER_SKILLS) {
                    if (!$update) {
                        $idImport = $entry["id"];
                        unset($entry["id"]);

                        $skillIdImport = $entry["superSkillId"];
                        $entry["superSkillId"] = $skillIds[$skillIdImport];
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);

                        $dependencyIds[$idImport] = $newId;
                    }
                } else if ($tableName[$i] == self::TABLE_DEPENDENCIES) {
                    if (!$update) {
                        $entry["dependencyId"] = $dependencyIds[$entry["dependencyId"]];
                        if ($entry["isTier"]) //depends on a tier (wildcard)
                            $entry["normalSkillId"] = $skillTierIds[$entry["normalSkillId"]];
                        else //depends on a normal skill
                            $entry["normalSkillId"] = $skillIds[$entry["normalSkillId"]];

                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                    }
                }
            }
            $i++;
        }
        return $skillIds;
    }

    public function is_configurable(): bool
    {
        return true;
    }

    public function has_general_inputs(): bool
    {
        return true;
    }

    public function get_general_inputs($courseId): array
    {
        $input = array('name' => "Max Skill Tree Reward", 'id' => 'maxReward', 'type' => "number", 'options' => "", 'current_val' => intval($this->getMaxReward($courseId)));
        return [$input];
    }

    public function save_general_inputs($generalInputs, $courseId)
    {
        $maxVal = $generalInputs["maxReward"];
        $this->saveMaxReward($maxVal, $courseId);
    }

    public function has_personalized_config(): bool
    {
        return true;
    }

    public function get_personalized_function(): string
    {
        return self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows($courseId)
    {
        Core::$systemDB->delete(self::TABLE_TREES, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    public function importItems(string $fileData, bool $replace = true): int
    {
        $courseId = $this->getCourseId();
        $moduleObject = new Skills();

        $newItemNr = 0;
        $lines = explode("\n", $fileData);
        $has1stLine = false;
        $tierIndex = "";
        $nameIndex = "";
        $dependenciesIndex = "";
        $colorIndex = "";
        $xpIndex = "";
        $i = 0;
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");
        if ($lines[0]) {
            $lines[0] = trim($lines[0]);
            $firstLine = explode(";", $lines[0]);
            $firstLine = array_map('trim', $firstLine);
            if (
                in_array("tier", $firstLine)
                && in_array("name", $firstLine) && in_array("dependencies", $firstLine)
                && in_array("color", $firstLine) && in_array("xp", $firstLine)
            ) {
                $has1stLine = true;
                $tierIndex = array_search("tier", $firstLine);
                $nameIndex = array_search("name", $firstLine);
                $dependenciesIndex = array_search("dependencies", $firstLine);
                $colorIndex = array_search("color", $firstLine);
                $xpIndex = array_search("xp", $firstLine);
            }
        }
        foreach ($lines as $line) {
            $line = trim($line);
            $item = explode(";", $line);
            $item = array_map('trim', $item);
            if (count($item) > 1) {
                if (!$has1stLine) {
                    $tierIndex = 0;
                    $nameIndex = 1;
                    $dependenciesIndex = 2;
                    $colorIndex = 3;
                    $xpIndex = 4;
                }
                if (!$has1stLine || ($i != 0 && $has1stLine)) {
                    $itemId = Core::$systemDB->select(self::TABLE, ["treeId" => $treeId, "name" => $item[$nameIndex]], "id");

                    $skillData = [
                        "tier" => $item[$tierIndex],
                        "name" => $item[$nameIndex],
                        "dependencies" => $item[$dependenciesIndex],
                        "dependenciesList" => (new self)->transformStringToList($item[$dependenciesIndex]),
                        "color" => $item[$colorIndex],
                        "xp" => $item[$xpIndex],
                        "treeId" => $treeId
                    ];
                    $tierData = [
                        "tier" => $item[$tierIndex],
                        "reward" => $item[$xpIndex],
                        "treeId" => $treeId
                    ];
                    $tierExists = Core::$systemDB->select(self::TABLE_TIERS, ["treeId" => $treeId, "tier" => $item[$tierIndex]]);
                    if (empty($tierExists)) {
                        $moduleObject->newTier($tierData, $courseId);
                    }
                    if (!empty($itemId)) {
                        if ($replace) {
                            $skillData["id"] = $itemId;
                            $skillData["description"] = "";
                            $moduleObject->editSkill($skillData, $courseId);
                        }
                    } else {
                        $moduleObject->newSkill($skillData, $courseId);
                        $newItemNr++;
                    }
                }
            }
            $i++;
        }
        return $newItemNr;
    }

    public function exportItems(int $itemId = null): array
    {
        // FIXME: export in .csv and is not exporting dependencies
        $courseId = $this->getCourseId();
        $course = Course::getCourse($courseId, false);

        $listOfSkills = $this->getSkills($course);
        $file = "";
        $i = 0;
        $len = count($listOfSkills);
        $file .= "tier;name;dependencies;color;xp\n";
        foreach ($listOfSkills as $skill) {
            $file .= $skill["tier"] . ";" . $skill["name"] . ";" . trim($skill["dependencies"]) . ";" . $skill["color"] . ";" .  $skill["xp"];
            if ($i != $len - 1) {
                $file .= "\n";
            }
            $i++;
        }
        return ["Skills - " . $course->getName(), $file];
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ----------- Tiers ---------- ***/

    public function getTiers(int $courseId, bool $withXP = false): array
    {
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");
        $tiers = Core::$systemDB->selectMultiple(self::TABLE_TIERS, ["treeId" => $treeId], "*", "seqId");
        if ($withXP) return $tiers;
        return array_column($tiers, 'tier');
    }

    public function newTier($tier, int $courseId): array
    {
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");
        if (!empty($treeId)) {
            $numTiers =  sizeof(Core::$systemDB->selectMultiple(self::TABLE_TIERS));
            $tierData = [
                "tier" => $tier["tier"],
                "treeId" => $treeId,
                "reward" => $tier['reward'],
                "seqId" => $numTiers + 1
            ];
            $tierId = Core::$systemDB->insert(self::TABLE_TIERS, $tierData);
            $tierData['id'] = $tierId;
            return $tierData;
        }
        API::error("There's no skill tree for course with id = " . $courseId);
        return array();
    }

    public function editTier($tier, int $courseId)
    {
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");
        if (!empty($treeId)) {
            $tierData = [
                "tier" => $tier['tier'],
                "treeId" => $treeId,
                "reward" => intval($tier['reward'])
            ];
            Core::$systemDB->update(self::TABLE_TIERS, $tierData, ["treeId" => $treeId, "id" => $tier["id"]]);
            return;
        }
        API::error("There's no skill tree for course with id = " . $courseId);
    }

    public function deleteTier($tier, int $courseId)
    {
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");

        $tierSkills = Core::$systemDB->selectMultiple(self::TABLE, ["treeId" => $treeId, "tier" => $tier["tier"]]);
        if (empty($tierSkills))
            Core::$systemDB->delete(self::TABLE_TIERS, ["treeId" => $treeId, "id" => $tier['id']]);
        else
            API::error("This tier has skills. Please delete them first or change their tier.");
    }

    public function tierHasWildcards($tier, int $courseId): bool
    {
        $tierSkills = Core::$systemDB->selectMultiple(
            self::TABLE_DEPENDENCIES . " d left join " . self::TABLE_TIERS . " t on d.normalSkillId = t.id left join " . self::TABLE_TREES . " s on t.treeId=s.id",
            ["course" => $courseId, "t.tier" => $tier, "d.isTier" => true],
            "count(*) as numWild"
        );

        return $tierSkills[0]["numWild"] > 0;
    }

    public function getNumberOfSkillsInTier(int $treeId, string $tier): int
    {
        $skills = Core::$systemDB->selectMultiple(self::TABLE, ["treeId" => $treeId, "tier" => $tier]);

        return sizeof($skills);
    }


    /*** ---------- Skills ---------- ***/

    public function getSkills(int $courseId): array
    {
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");
        $tiers = Core::$systemDB->selectMultiple(self::TABLE_TIERS, ["treeId" => $treeId], "*", "seqId");

        $skillsArray = array();

        foreach ($tiers as &$tier) {
            $skillsInTier = Core::$systemDB->selectMultiple(self::TABLE, ["treeId" => $treeId, "tier" => $tier["tier"]], "id,name,page,color,tier,seqId,isActive", "seqId");;
            foreach ($skillsInTier as &$skill) {
                //information to match needing fields
                $skill['xp'] = $tier["reward"];
                $skill['dependencies'] = '';
                $skill['allActive'] = true;
                $skill['isActive'] = boolval($skill["isActive"]);
                if (!empty(Core::$systemDB->selectMultiple(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skill["id"]]))) {
                    $dependencyData = $this->getSkillDependencies($skill["id"]);
                    $dependencies = $dependencyData['dependencies'];
                    $skill['allActive'] = $dependencyData['allActive'];
                    for ($i = 0; $i < sizeof($dependencies); $i++) {
                        $skill['dependencies'] .= $dependencies[$i] .  " + ";
                        if ($i % 2 !== 0 && sizeof($dependencies) > 2) {
                            $skill['dependencies'] = substr_replace($skill['dependencies'], ' | ', -3, -1);
                        }
                    }
                    $skill['dependencies'] = substr_replace($skill['dependencies'], '', -3, -1);
                }
                $skill["dependenciesList"] = $this->transformStringToList($skill["dependencies"]);
                $skill["description"] = $this->getDescriptionFromPage($skill, $courseId);

                unset($skill["page"]);
                array_push($skillsArray, $skill);
            }
        }
        return $skillsArray;
    }

    public function newSkill($skill, int $courseId)
    {
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");

        $numSkills = $this->getNumberOfSkillsInTier($treeId, $skill["tier"]);
        $skillData = [
            "name" => $skill['name'],
            "treeId" => $treeId,
            "tier" => $skill['tier'],
            "color" => $skill['color'],
            "seqId" => $numSkills + 1,
        ];

        // Swap absolute URLs to relative ones
        $skillData['page'] = preg_replace_callback('/src="(.*?)"/', function ($matches) use ($courseId) {
            return "src=\"" . Utils::transformURL($matches[1], "relative", $courseId) . "\"";
        }, $skill['description']);

        Core::$systemDB->insert(self::TABLE, $skillData);
        $skillId = Core::$systemDB->getLastId();

        // Add dependencies
        $dependencyList = array();
        if ($skill["dependencies"] != "") {
            $pairDep = explode("|", str_replace(" | ", "|", $skill["dependencies"]));

            foreach ($pairDep as $dep) {
                Core::$systemDB->insert(self::TABLE_SUPER_SKILLS, [
                    "superSkillId" => $skillId
                ]);
                $dependencyId = Core::$systemDB->getLastId();

                $dependencies = explode("+", str_replace(" + ", "+", $dep));
                $dependency = [];
                foreach ($dependencies as $d) {
                    $isTier = false;
                    $normalSkillId = Core::$systemDB->select(self::TABLE, ["name" => trim($d)], "id");
                    if (empty($normalSkillId)) {
                        $skillTierId = Core::$systemDB->select(self::TABLE_TIERS, ["tier" => trim($d)], "id");
                        if (!empty($skillTierId)) {
                            Core::$systemDB->insert(self::TABLE_DEPENDENCIES, [
                                "dependencyId" => $dependencyId,
                                "normalSkillId" => $skillTierId,
                                "isTier" => true
                            ]);
                            $isTier = true;
                        } else {
                            echo "The skill " . $d . " does not exist";
                        }
                    } else {
                        Core::$systemDB->insert(self::TABLE_DEPENDENCIES, [
                            "dependencyId" => $dependencyId,
                            "normalSkillId" => $normalSkillId
                        ]);
                    }
                    $dependencySkill = array('name' => $d, 'isTier' => $isTier);
                    array_push($dependency, $dependencySkill);
                }
                array_push($dependencyList, $dependency);
            }
            $skill['dependencies'] = trim($skill['dependencies']);
        }

        // Create rule
        $course = Course::getCourse($courseId, false);
        if (strpos($skill["dependencies"], "Wildcard") !== false) // has wildcard dependency
            $this->generateWildcardRule($course, $skill['name'], $dependencyList);
        else $this->generateSkillRule($course, $skill['name'], $dependencyList);
    }

    public function editSkill($skill, $courseId)
    {
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");

        $skillData = [
            "name" => $skill['name'],
            "treeId" => $treeId,
            "tier" => $skill['tier'],
            "color" => $skill['color']
        ];

        // Swap absolute URLs to relative ones
        $skillData['page'] = preg_replace_callback('/src="(.*?)"/', function ($matches) use ($courseId) {
            return "src=\"" . Utils::transformURL($matches[1], "relative", $courseId) . "\"";
        }, $skill['description']);

        Core::$systemDB->update(self::TABLE, $skillData, ["id" => $skill["id"]]);
        $skillId = $skill["id"];

        // Update dependencies
        $dependencyIds = Core::$systemDB->selectMultiple(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skillId], "id");
        if ($skill['tier'] == 1 || ($skill["dependencies"] == "" && !empty($dependencyIds))) { // 1st tier or no dependencies
            // Delete dependencies
            Core::$systemDB->delete(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skillId]);
            foreach ($dependencyIds as $dependency) {
                Core::$systemDB->delete(self::TABLE_DEPENDENCIES, ["dependencyId" => $dependency["id"]]);
            }

        } else if ($skill["dependencies"] != "") { // has dependencies
            $pairDep = explode("|", str_replace(" | ", "|", $skill["dependencies"]));

            $numOfDep = count($dependencyIds);
            $numOfNewDep =  count($pairDep);

            if ($numOfDep > $numOfNewDep) {
                // Delete original dependencies
                Core::$systemDB->delete(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skillId]);
                foreach ($dependencyIds as $dependency) {
                    Core::$systemDB->delete(self::TABLE_DEPENDENCIES, ["dependencyId" => $dependency["id"]]);
                }

                // Create new ones
                foreach ($pairDep as $dep) {
                    Core::$systemDB->insert(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skillId]);
                    $dependencyId = Core::$systemDB->getLastId();

                    $dependencies = explode("+", str_replace(" + ", "+", $dep));
                    foreach ($dependencies as $d) {
                        $normalSkillId = Core::$systemDB->select(self::TABLE, ["name" => trim($d)], "id");
                        if (empty($normalSkillId)) {
                            $skillTierId = Core::$systemDB->select(self::TABLE_TIERS, ["tier" => trim($d)], "id");
                            if (!empty($skillTierId)) {
                                Core::$systemDB->insert(self::TABLE_DEPENDENCIES, [
                                    "dependencyId" => $dependencyId,
                                    "normalSkillId" => $skillTierId,
                                    "isTier" => true
                                ]);
                            } else {
                                API::error("The skill " . $d . " does not exist");
                            }
                        } else {
                            Core::$systemDB->insert(self::TABLE_DEPENDENCIES, [
                                "dependencyId" => $dependencyId,
                                "normalSkillId" => $normalSkillId
                            ]);
                        }
                    }
                }

            } else {
                for ($i = 0; $i < $numOfNewDep; $i++) {
                    $dependencies = explode("+", str_replace(" + ", "+", $pairDep[$i]));

                    if ($i + 1 > $numOfDep) {
                        Core::$systemDB->insert(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skillId]);
                        $dependencyId = Core::$systemDB->getLastId();
                        foreach ($dependencies as $d) {
                            $normalSkillId = Core::$systemDB->select(self::TABLE, ["name" => trim($d)], "id");
                            if (empty($normalSkillId)) {
                                $skillTierId = Core::$systemDB->select(self::TABLE_TIERS, ["tier" => trim($d)], "id");
                                if (!empty($skillTierId)) {
                                    Core::$systemDB->insert(self::TABLE_DEPENDENCIES, [
                                        "dependencyId" => $dependencyId,
                                        "normalSkillId" => $skillTierId,
                                        "isTier" => true
                                    ]);
                                } else {
                                    API::error("The skill " . $d . " does not exist");
                                }
                            } else {
                                Core::$systemDB->insert(self::TABLE_DEPENDENCIES, [
                                    "dependencyId" => $dependencyId,
                                    "normalSkillId" => $normalSkillId
                                ]);
                            }
                        }
                    } else {
                        $originalDepID = $dependencyIds[$i]["id"];
                        Core::$systemDB->delete(self::TABLE_DEPENDENCIES, ["dependencyId" => $originalDepID]);
                        foreach ($dependencies as $d) {
                            $normalSkillId = Core::$systemDB->select(self::TABLE, ["name" => trim($d)], "id");
                            if (empty($normalSkillId)) {
                                $skillTierId = Core::$systemDB->select(self::TABLE_TIERS, ["tier" => trim($d)], "id");
                                if (!empty($skillTierId)) {
                                    Core::$systemDB->insert(self::TABLE_DEPENDENCIES, [
                                        "dependencyId" => $originalDepID,
                                        "normalSkillId" => $skillTierId,
                                        "isTier" => true
                                    ]);
                                } else {
                                    echo "The skill " . $d . " does not exist";
                                }
                            } else {
                                Core::$systemDB->insert(self::TABLE_DEPENDENCIES, [
                                    "dependencyId" => $originalDepID,
                                    "normalSkillId" => $normalSkillId
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // Update rule
        // TODO: edit rule based on changes; only change the necessary
    }

    public function deleteSkill(int $skillId, int $courseId)
    {
        if (!empty(Core::$systemDB->selectMultiple(self::TABLE_DEPENDENCIES, ["normalSkillId" => $skillId, "isTier" => false])))
            API::error("This skill is a dependency of others skills. You must remove them first.");

        $skillInfo = Core::$systemDB->select(
            self::TABLE . " left join " . self::TABLE_TREES . " on " . self::TABLE . ".treeId=" . self::TABLE_TREES . ".id",
            [self::TABLE . ".id" => $skillId, "course" => $courseId],
            "name, tier, treeId"
        );

        if (!empty($skillInfo)) {
            Core::$systemDB->delete(self::TABLE, ["id" => $skillId]);
            $course = Course::getCourse($courseId);
            $this->deleteGeneratedRule($course, $skillInfo['name']);

            // Delete skill resources
            $dir = Course::getCourseDataFolder($courseId) . "/skills/" . str_replace(" ", "", $skillInfo['name']);
            Utils::deleteDirectory($dir);
        }
    }

    //gets skills that depend on a skill and are required by another skill
    public function getSkillsDependantAndRequired($normalSkill, $superSkill, $restrictions = [], $parent = null): ValueNode
    {
        $table = self::TABLE_DEPENDENCIES . " sk join " . self::TABLE_SUPER_SKILLS . " d on id=dependencyId join " . self::TABLE . " s on s.id=normalSkillId"
            . " natural join tier t join " . self::TABLE_TREES . " tr on tr.id=treeId " .
            "join " . self::TABLE_SUPER_SKILLS . " d2 on d2.superSkillId=s.id join " . self::TABLE_DEPENDENCIES . " sd2 on sd2.dependencyId=d2.id";

        $restrictions["sd2.normalSkillId"] = $normalSkill["value"]["id"];
        $restrictions["d.superSkillId"] = $superSkill["value"]["id"];

        $skills = Core::$systemDB->selectMultiple($table, $restrictions, "s.*,t.*", null, [], [], "s.id");
        return Dictionary::createNode($skills, 'skillTrees', "collection", $parent);
    }

    public function getSkillsAux($restrictions, $joinOn, $parentSkill, $parentTree): ValueNode
    {
        $skills = Core::$systemDB->selectMultiple(
            self::TABLE_DEPENDENCIES . " join " . self::TABLE_SUPER_SKILLS . " on id=dependencyId join "
                . self::TABLE . " s on s.id=" . $joinOn . " natural join tier t join " . self::TABLE_TREES . " tr on tr.id=treeId",
            $restrictions,
            "s.*,t.*",
            null,
            [],
            [],
            "s.id"
        );
        if ($parentTree === null)
            return Dictionary::createNode($skills, 'skillTrees', "collection", $parentSkill);
        else
            return Dictionary::createNode($skills, 'skillTrees', "collection", $parentTree);
    }

    //returns collection of skills that depend of the given skill
    public function getSkillsDependantof($skill, $restrictions = [], $parent = false): ValueNode
    {
        $restrictions["normalSkillId"] = $skill["value"]["id"];
        if ($parent === false)
            return $this->getSkillsAux($restrictions, 'skillTrees', "superSkillId", $skill);
        else
            return $this->getSkillsAux($restrictions, 'skillTrees', "superSkillId", $parent);
    }

    //returns collection of skills that are required by the given skill
    public function getSkillsRequiredBy($skill, $restrictions = [], $parent = false): ValueNode
    {
        $restrictions["superSkillId"] = $skill["value"]["id"];
        if ($parent === false)
            return $this->getSkillsAux($restrictions, 'skillTrees', "normalSkillId", $skill);
        else
            return $this->getSkillsAux($restrictions, 'skillTrees', "normalSkillId", $parent);
    }

    //check if skill has been completed by the user
    public function isSkillCompleted($skill, $user, $courseId): bool
    {
        if (is_array($skill)) //$skill can be object or id
            $skillId = $skill["value"]["id"];
        else $skillId = $skill;
        $award = Dictionary::getAwardOrParticipation($courseId, $user, "skill", (int) $skillId, null, null, [], "award", false, false);
        return (!empty($award));
    }

    //check if skill is unlocked to the user
    public function isSkillUnlocked($skill, $user, $courseId, $isActive = true): bool
    {
        $dependency = Core::$systemDB->selectMultiple(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skill["value"]["id"]]);
        $skillName = $skill["value"]["name"];
        //goes through all dependencies to check if they unlock the skill
        $unlocked = true;
        foreach ($dependency as $dep) {
            $unlocked = true;
            $dependencySkill = Core::$systemDB->selectMultiple(self::TABLE_DEPENDENCIES . " left join " . self::TABLE . " on normalSkillId = " . self::TABLE . ".id", ["dependencyId" => $dep["id"]]);
            foreach ($dependencySkill as $depSkill) {
                if (!($depSkill["isTier"])) {
                    if (!$this->isSkillCompleted($depSkill["normalSkillId"], $user, $courseId) or ($isActive and !$depSkill["isActive"])) {
                        $unlocked = false;
                        break;
                    }
                } else if ($depSkill["isTier"]) {
                    // if it depends on a tier, check every skill from that tier
                    $tierName = Core::$systemDB->select(self::TABLE_TIERS, ["id" => $depSkill["normalSkillId"]], "tier");
                    $where = ["tier" => $tierName, "t.course" => $courseId];
                    if ($isActive)
                        $where["isActive"] = true;
                    $tierSkills = Core::$systemDB->selectMultiple(self::TABLE . " s join " . self::TABLE_TREES . " t on s.treeId = t.id", $where, "s.id");
                    foreach ($tierSkills as $tierSkill) {
                        //if one skill from tier is completed AND the super skill is completed or there are wildcards to use
                        if ($this->isSkillCompleted($tierSkill["id"], $user, $courseId) and $this->getAvailableWildcards($skillName, $tierName, $user, $courseId)) {
                            $unlocked = true;
                            break;
                        }
                        $unlocked = false;
                    }
                }
            }
            if ($unlocked) {
                break;
            }
        }
        return ($unlocked);
    }

    //returns students who completed a skill
    private function skillCompletedBy($skill, $courseId)
    {
        $students = Core::$systemDB->selectMultiple(
            AwardList::TABLE . " a left join game_course_user u on a.user = u.id left join course_user c on u.id = c.id",
            ["a.course" => $courseId, "type" => "skill", "moduleInstance" => $skill],
            "u.id, a.course, lastActivity, previousActivity, name, email, major, nickname, studentNumber, isAdmin, isActive"
        );
        return $students;
    }

    //returns array with all dependencies of a skill
    public function getSkillDependencies($skillId): array
    {
        $depArray = [];
        $allActive = true;
        $dependencyIDs = Core::$systemDB->selectMultiple(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skillId], "id");

        foreach ($dependencyIDs as $id) {
            $individualDeps = Core::$systemDB->selectMultiple(self::TABLE_DEPENDENCIES, ["dependencyId" => $id["id"]]);
            foreach ($individualDeps as $dep) {
                if ($dep["isTier"]) {
                    $name = Core::$systemDB->select(self::TABLE_TIERS, ["id" => $dep["normalSkillId"]], "tier");
                } else {
                    $data = Core::$systemDB->select(self::TABLE, ["id" => $dep["normalSkillId"]], "name, isActive");
                    $name = $data['name'];
                    if (!$data['isActive']) {
                        $allActive = false;
                    }
                }
                array_push($depArray, $name);
            }
        }
        return array('dependencies' => $depArray, 'allActive' => $allActive);
    }

    public function getDescriptionFromPage($skill, $courseId)
    {
        // Swap relative URLs to absolute ones
        return htmlspecialchars_decode(preg_replace_callback('/src="(.*?)"/', function ($matches) use ($courseId) {
            return "src=\"" . Utils::transformURL($matches[1], "absolute", $courseId) . "\"";
        }, $skill['page']));
    }

    public function transformStringToList($skillDependencyString): array
    {
        $skillDependencyArray = [];
        if ($skillDependencyString != "") {

            if (strpos($skillDependencyString, '|')) {
                $pairDep = explode("|", str_replace(" | ", "|", $skillDependencyString));
                foreach ($pairDep as $dep) {
                    $newDep = [];
                    $dependencies = explode("+", str_replace(" + ", "+", $dep));
                    foreach ($dependencies as $d) {
                        $newDep[] = trim($d);
                    }
                    $skillDependencyArray[] = $newDep;
                }
            } else {
                $newDep = [];
                $dependencies = explode("+", str_replace(" + ", "+", $skillDependencyString));
                foreach ($dependencies as $d) {
                    $newDep[] = trim($d);
                }
                $skillDependencyArray[] = $newDep;
            }
        }
        return $skillDependencyArray;
    }


    /*** -------- Wildcards --------- ***/

    public function getAvailableWildcards($skill, $tier, $user, $course): bool
    {
        //this works because only one insertion is made in award_wildcard
        //on the first time that a skill rule is triggered
        $completedWildcards = Core::$systemDB->selectMultiple(
            AwardList::TABLE . " a left join " . self::TABLE . " s on a.moduleInstance = s.id left join " . self::TABLE_TIERS . " t on s.tier = t.tier and t.treeId = s.treeId",
            ["a.user" => $user, "t.tier" => $tier, "a.course" => $course],
            "count(a.id) as numCompleted"
        );

        $usedWildcards = $this->getUsedWildcards($tier, $user, $course);

        $isCompleted = Core::$systemDB->selectMultiple(
            AwardList::TABLE . " a left join " . self::TABLE . " s on a.moduleInstance = s.id",
            ["a.user" => $user, "a.course" => $course, "s.name" => $skill]
        );

        return (($usedWildcards < $completedWildcards[0]["numCompleted"]) or !empty($isCompleted));
    }

    public function getUsedWildcards($tier, $user, $course)
    {

        $usedWildcards = Core::$systemDB->selectMultiple(
            self::TABLE_WILDCARD . " w left join " . AwardList::TABLE . " a on w.awardId = a.id left join " . self::TABLE_TIERS . " t on w.tierId = t.id",
            ["a.user" => $user, "t.tier" => $tier, "a.course" => $course],
            "count(w.awardId) as numUsed"
        );

        return $usedWildcards[0]["numUsed"];
    }


    /*** ---------- Rewards --------- ***/

    public function saveMaxReward($max, $courseId)
    {
        Core::$systemDB->update(self::TABLE_TREES, ["maxReward" => $max], ["course" => $courseId]);
    }

    public function getMaxReward($courseId)
    {
        return Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "maxReward");
    }


    /*** ----------- Rules ---------- ***/

    public function generateSkillRule(Course $course, string $skillName, array $dependencies = null)
    {
        $template = file_get_contents(MODULES_FOLDER . "/" . self::ID . "/rules/" . self::SKILL_RULE_TEMPLATE);

        // Write skill name
        $newRule = str_replace("<skill-name>", $skillName, $template);

        // Write skill dependencies
        if (sizeof($dependencies) == 0) { // no dependencies
            $txt = preg_replace("/\t\t<skill-dependencies>(\r*\n)*/", "", $newRule);

        } else if (sizeof($dependencies) > 0) { // has dependencies
            $ruletxt = explode("<skill-dependencies>", $newRule);
            $linesDependencies = "";
            $conditiontxt = array();
            $comboNr = 1;
            foreach ($dependencies as $dependency) {
                $deptxt = "combo" . $comboNr . " = rule_unlocked(\"" . $dependency[0]['name'] . "\", target) and rule_unlocked(\"" . $dependency[1]['name'] . "\", target)\n\t\t";
                $linesDependencies .= $deptxt;
                array_push($conditiontxt, "combo" . $comboNr);
                $comboNr += 1;
            }
            $linesDependencies = trim($linesDependencies, "\t\n");
            $lineCombo = implode(" or ", $conditiontxt);
            $linesDependencies .= "\n\t\t";
            $linesDependencies .= $lineCombo;
            array_splice($ruletxt, 1, 0, $linesDependencies);
            $txt = implode("", $ruletxt);
        }

        // Add generated rule
        $rs = new RuleSystem($course);
        $rule = array();
        $rule["module"] = self::ID;
        $filename = $rs->getFilename(self::ID);
        if ($filename == null) {
            $filename = $rs->createNewRuleFile(self::ID, 1);
            $rs->fixPrecedences();
            $filename = $rs->getFilename(self::ID);
        }
        $rule["rulefile"] = $filename;
        $rs->addRule($txt, null, $rule); // add to end
    }

    public function generateWildcardRule(Course $course, string $skillName, array $dependencies = null)
    {
        $template = file_get_contents(MODULES_FOLDER . "/" . self::ID . "/rules/" . self::WILDCARD_RULE_TEMPLATE);

        // Write skill name
        $newRule = str_replace("<skill-name>", $skillName, $template);

        // Write tier name
        $wildcard = "Wildcard";
        $newRule = str_replace("<tier-name>", $wildcard, $newRule);

        if (sizeof($dependencies) == 0)
            API::error("No dependencies found when generating wildcard rule");

        // Write skill dependencies
        $ruletxt = explode("<skill-dependencies>", $newRule);
        $linesDependencies = "";
        $skillBasedCombos = array();
        $conditiontxt = array();
        $comboNr = 1;
        foreach ($dependencies as $dependency) {
            if ($dependency[0]['name'] === $wildcard || $dependency[1]['name'] === $wildcard) { // has wildcard(s)
                $deptxt = "combo" . $comboNr . " = " .
                    ($dependency[0]['name'] === $wildcard ? "wildcard" : "rule_unlocked(\"" . $dependency[0]['name'] . "\", target)") . " and " .
                    ($dependency[1]['name'] === $wildcard ? "wildcard" : "rule_unlocked(\"" . $dependency[1]['name'] . "\", target)\n\t\t");

            } else { // no wildcard(s)
                $deptxt = "combo" . $comboNr . " = rule_unlocked(\"" . $dependency[0]['name'] . "\", target) and rule_unlocked(\"" . $dependency[1]['name'] . "\", target)\n\t\t";
                array_push($skillBasedCombos, "combo" . $comboNr);
            }
            $linesDependencies .= $deptxt;
            array_push($conditiontxt, "combo" . $comboNr);
            $comboNr += 1;
        }
        $linesDependencies = trim($linesDependencies, "\t\n");
        $lineCombo = implode(" or ", $conditiontxt);
        $linesDependencies .= "\n\t\t";
        $linesDependencies .= $lineCombo;
        array_splice($ruletxt, 1, 0, $linesDependencies);
        $txt = implode("", $ruletxt);

        // Write skill_based
        if (count($skillBasedCombos) > 0) {
            $skillBased = $skillBasedCombos[0];
            foreach ($skillBasedCombos as $index => $combo) {
                if ($index == 0) continue;
                $skillBased .= " or " . $combo;
            }

        } else $skillBased = "False";
        $txt = str_replace("<skill-based>", $skillBased, $txt);

        // Add generated rule
        $rs = new RuleSystem($course);
        $rule = array();
        $rule["module"] = self::ID;
        $filename = $rs->getFilename(self::ID);
        if ($filename == null) {
            $filename = $rs->createNewRuleFile(self::ID, 1);
            $rs->fixPrecedences();
            $filename = $rs->getFilename(self::ID);
        }
        $rule["rulefile"] = $filename;
        $rs->addRule($txt, 0, $rule); // add to top
    }

    public function deleteGeneratedRule(Course $course, string $skillName)
    {
        $rs = new RuleSystem($course);
        $rule = array();

        $rule["name"] = $skillName;
        $rule["rulefile"] = $rs->getFilename(self::ID);
        $position = $rs->getRulePosition($rule);

        if ($position !== false)
            $rs->removeRule($rule, $position);
    }


    /*** ----------- Misc ---------- ***/

    public function changeItemSequence(int $courseId, int $itemId, int $oldSeq, int $nextSeq, string $tierOrSkill)
    {
        $treeId = Core::$systemDB->select(self::TABLE_TREES, ["course" => $courseId], "id");

        if ($tierOrSkill == "tier") {
            // If changing first place, delete dependencies on its skills
            if ($nextSeq == 1 || $oldSeq == 1) {
                if ($oldSeq == 1) { // 2nd tier will become first
                    $tierName = Core::$systemDB->select(self::TABLE_TIERS, ["seqId" => 2], "tier");

                } else { // this tier will become first
                    $tierName = Core::$systemDB->select(self::TABLE_TIERS, ["id" => $itemId], "tier");
                }
                $skillsInTier = Core::$systemDB->selectMultiple(self::TABLE, ["treeId" => $treeId, "tier" => $tierName]);
                foreach ($skillsInTier as $skill) {
                    $dependencies = Core::$systemDB->selectMultiple(self::TABLE_SUPER_SKILLS, ["superSkillId" => $skill["id"]], "id");
                    if (!empty($dependencies)) {
                        foreach ($dependencies as $dep) {
                            Core::$systemDB->delete(self::TABLE_SUPER_SKILLS, ["id" => $dep["id"]]);
                        }
                    }
                }
            }
            Core::$systemDB->update(self::TABLE_TIERS, ["seqId" => $oldSeq], ["seqId" => $nextSeq, "treeId" => $treeId]);
            Core::$systemDB->update(self::TABLE_TIERS, ["seqId" => $nextSeq], ["seqId" => $oldSeq, "id" => $itemId, "treeId" => $treeId]);

        } else if ($tierOrSkill == "skill") {
            $tier = Core::$systemDB->select(self::TABLE, ["treeId" => $treeId, "id" => $itemId], "tier");
            Core::$systemDB->update(self::TABLE, ["seqId" => $oldSeq], ["seqId" => $nextSeq, "treeId" => $treeId, "tier" => $tier]);
            Core::$systemDB->update(self::TABLE, ["seqId" => $nextSeq], ["seqId" => $oldSeq, "id" => $itemId, "treeId" => $treeId]);
        }
    }

    public function toggleItemParam(int $itemId, string $param)
    {
        $state = Core::$systemDB->select(self::TABLE, ["id" => $itemId], $param);
        Core::$systemDB->update(self::TABLE, [$param => $state ? 0 : 1], ["id" => $itemId]);
    }
}

ModuleLoader::registerModule(array(
    'id' => Skills::ID,
    'name' => 'Skills',
    'description' => 'Generates a skill tree where students have to complete several skills to achieve a higher layer',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => XPLevels::ID, 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Skills();
    }
));
