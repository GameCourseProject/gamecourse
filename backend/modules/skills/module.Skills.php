<?php
namespace Modules\Skills;

use DOMDocument;
use GameCourse\API;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\RuleSystem;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Expression\ValueNode;
use GameCourse\Views\Views;

class Skills extends Module
{
    const ID = 'skills';

    const SKILL_TREE_TEMPLATE = 'Skill Tree - by skills';
    const SKILLS_OVERVIEW_TEMPLATE = 'Skills Overview - by skills';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->setupData($this->getCourseId());
        $this->initDictionary();
        $this->initTemplates();
        $this->initAPIEndpoints();
    }

    public function initDictionary()
    {
        $courseId = $this->getCourseId();

        /*** ------------ Libraries ------------ ***/

        Dictionary::registerLibrary("skills", "skillTrees", "This library provides information regarding Skill Trees. It is provided by the skills module.");


        /*** ------------ Functions ------------ ***/

        //skillTrees.getTree(id), returns tree object
        Dictionary::registerFunction(
            'skillTrees',
            'getTree',
            function (int $id) {
                //this is slightly pointless if the skill tree only has id and course
                //but it could eventualy have more atributes
                return Dictionary::createNode(Core::$systemDB->select("skill_tree", ["id" => $id]), 'skillTrees');
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
                return Dictionary::createNode(Core::$systemDB->selectMultiple("skill_tree", ["course" => $courseId]), 'skillTrees', "collection");
            },
            'Returns a collection will all the Skill Trees in the Course.',
            'collection',
            'tree',
            'library',
            null,
            true
        );

        //skillTrees.getAllSkills(...) returns collection
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
                    "skill s natural join skill_tier t join skill_tree tr on tr.id=treeId",
                    $skillWhere,
                    "s.*,t.*"
                ), 'skillTrees', "collection", $parent);
            },
            "Returns a collection with all the skills in the Course. The optional parameters can be used to find skills that specify a given combination of conditions:\ntree: The skillTree object or the id of the skillTree object to which the skill belongs to.\ntier: The tier object or tier of the tier object of the skill.\ndependsOn: a skill that is used to unlock a specific skill.\nrequiredBy: a skill that unlocks a collection of skills.\nisActive: a skill that is active.",
            'collection',
            'skill',
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
                    "skill s natural join skill_tier t join skill_tree tr on tr.id=treeId",
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
                    "skill natural join skill_tier",
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
                    "skill_tier",
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
                        "skill_tier",
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
                    "skill s join skill_tier t on s.tier = t.tier",
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
                    "skill_tier",
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
                    "skill_tier",
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

                $columns = "award left join award_participation on award.id=award_participation.award left join participation on award_participation.participation=participation.id";
                $post = Core::$systemDB->select(
                    $columns,
                    ["award.type" => "skill", "award.moduleInstance" => $skill["value"]["id"], "award.user" => $userId, "award.course" => $courseId],
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
                $dep = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skill["value"]["id"]]);
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
                    "skill_dependency join skill s on s.id=normalSkillId",
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
                    "skill_dependency join skill s on s.id=normalSkillId",
                    ["dependencyId" => $dep["value"]["id"], "isTier" => false],
                    "s.*"
                );
                $tiers = Core::$systemDB->selectMultiple(
                    "skill_dependency join skill_tier t on t.id=normalSkillId",
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
        API::registerFunction('skills', 'page', function () {
            API::requireValues('skillName');
            $skillName = API::getValue('skillName');
            $courseId = $this->getParent()->getId();
            $folder = Course::getCourseDataFolder($courseId);

            if ($skillName) {
                $skills = Core::$systemDB->selectMultiple(
                    "skill_tier st left join skill s on st.tier=s.tier join skill_tree t on t.id=st.treeId",
                    ["course" => $courseId],
                    "name,page"
                );
                foreach ($skills as $skill) {
                    $compressedName = str_replace(' ', '', $skill['name']);
                    if ($compressedName == $skillName) {
                        $page = $this->getDescriptionFromPage($skill, $courseId);
                        //to support legacy, TODO: Remove this when skill editing is supported in GameCourse
                        // preg_match_all('/\shref="([A-z]+)[.]html/', $page, $matches);
                        // foreach ($matches[0] as $id => $match) {
                        //     $linkSkillName = $matches[1][$id];
                        //     $page = str_replace($match, ' ui-sref="skill({skillName:\'' . $linkSkillName . '\'})', $page);
                        // }
                        // $page = str_replace('src="http:', 'src="https:', $page);
                        // $page = str_replace(' href="' . $compressedName, ' target="_self" ng-href="' . $folder . '/tree/' . $compressedName, $page);
                        // $page = str_replace(' src="' . $compressedName, ' src="' . $folder . '/tree/' . $compressedName, $page);
                        API::response(array('name' => $skill['name'], 'description' => $page));
                    }
                }
            }
            API::error('Skill ' . $skillName . ' not found.', 404);
        });
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/skills.css');
        parent::addResources('imgs');
    }

    public function setupData($courseId)
    {
        if ($this->addTables("skills", "skill") || empty(Core::$systemDB->select("skill_tree", ["course" => $courseId]))) {
            Core::$systemDB->insert("skill_tree", ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        }
        $folder = Course::getCourseDataFolder($courseId);
        if (!file_exists($folder . "/skills"))
            mkdir($folder . "/skills");
    }

    public function update_module($compatibleVersions)
    {
        //obter o ficheiro de configuração do module para depois o apagar
        $configFile = MODULES_FOLDER . "/skills/config.json";
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

        if (Core::$systemDB->tableExists("skill_tree")) {
            $skillTreeVarDB_ = Core::$systemDB->selectMultiple("skill_tree", ["course" => $courseId], "*");
            if ($skillTreeVarDB_) {
                //values da skill_tree
                foreach ($skillTreeVarDB_ as $skillTreeVarDB) {
                    array_push($skillTreeArray, $skillTreeVarDB);

                    if (Core::$systemDB->tableExists("skill_tier")) {
                        $skillTierVarDB_ = Core::$systemDB->selectMultiple("skill_tier", ["treeId" => $skillTreeVarDB["id"]], "*");
                        if ($skillTierVarDB_) {
                            //values da skill_tier
                            foreach ($skillTierVarDB_ as $skillTierVarDB) {
                                array_push($skillTierArray, $skillTierVarDB);

                                if (Core::$systemDB->tableExists("skill")) {
                                    $skillVarDB_ = Core::$systemDB->selectMultiple("skill", ["treeId" => $skillTreeVarDB["id"], "tier" => $skillTierVarDB["tier"]], "*");
                                    if ($skillVarDB_) {
                                        //values da skill
                                        foreach ($skillVarDB_ as $skillVarDB) {
                                            array_push($skillArray, $skillVarDB);
                                            if (Core::$systemDB->tableExists("dependency")) {
                                                $dependencyDB_ = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skillVarDB["id"]], "*");
                                                if ($dependencyDB_) {
                                                    //values da dependency
                                                    foreach ($dependencyDB_ as $dependencyDB) {
                                                        array_push($dependencyArray, $dependencyDB);
                                                        if (Core::$systemDB->tableExists("skill_dependency")) {
                                                            $skillDependencyDB_ = Core::$systemDB->selectMultiple("skill_dependency", ["dependencyId" => $dependencyDB["id"]], "*");
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
            $skillModuleArr["skill_tree"] = $skillTreeArray;
        }
        if ($skillTierArray) {
            $skillModuleArr["skill_tier"] = $skillTierArray;
        }
        if ($skillArray) {
            $skillModuleArr["skill"] = $skillArray;
        }
        if ($dependencyArray) {
            $skillModuleArr["dependency"] = $dependencyArray;
        }
        if ($skillDependencyArray) {
            $skillModuleArr["skill_dependency"] = $skillDependencyArray;
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
                if ($tableName[$i] == "skill_tree") {
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
                } else if ($tableName[$i] == "skill_tier") {
                    $existingCourse = Core::$systemDB->select("skill_tier", ["treeId" => $skillTreeIds[$entry["treeId"]], "tier" => $entry["tier"]]);
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
                } else if ($tableName[$i] == "skill") {
                    $existingSkill = Core::$systemDB->select("skill", ["treeId" => $skillTreeIds[$entry["treeId"]], "tier" => $entry["tier"]]);
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
                } else if ($tableName[$i] == "dependency") {
                    if (!$update) {
                        $idImport = $entry["id"];
                        unset($entry["id"]);

                        $skillIdImport = $entry["superSkillId"];
                        $entry["superSkillId"] = $skillIds[$skillIdImport];
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);

                        $dependencyIds[$idImport] = $newId;
                    }
                } else if ($tableName[$i] == "skill_dependency") {
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
        return "skillsPersonalizedConfig";
    }

    public function has_listing_items(): bool
    {
        return  true;
    }

    public function get_listing_items($courseId): array
    {
        //tenho de dar header
        $header = ['Tier', 'Name', 'Dependencies', 'Color', 'XP', 'Active'];
        $displayAtributes = ['tier', 'name', 'dependencies', 'color', 'xp', 'isActive'];
        // items (pela mesma ordem do header)
        $items = $this->getSkills($courseId);
        //argumentos para add/edit
        $allAtributes = [
            array('name' => "Tier", 'id' => 'tier', 'type' => "select", 'options' => $this->getTiers($courseId)),
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => ""),
            array('name' => "Dependencies", 'id' => 'dependencies', 'type' => "button", 'options' => ""),
            array('name' => "DependenciesList", 'id' => 'dependenciesList', 'type' => "", 'options' => ""),
            array('name' => "Color", 'id' => 'color', 'type' => "color", 'options' => "", 'current_val' => ""),
            array('name' => "Description", 'id' => 'description', 'type' => "editor", 'options' => "")
        ];
        return array('listName' => 'Skills', 'itemName' => 'Skill', 'header' => $header, 'displayAttributes' => $displayAtributes, 'items' => $items, 'allAttributes' => $allAtributes);
    }

    public function save_listing_item($actiontype, $listingItem, $courseId)
    {
        if ($actiontype == 'new') {
            $this->newSkill($listingItem, $courseId);
        } elseif ($actiontype == 'edit') {
            $this->editSkill($listingItem, $courseId);
        } elseif ($actiontype == 'delete') {
            $this->deleteSkill($listingItem, $courseId);
        }
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows($courseId)
    {
        Core::$systemDB->delete("skill_tree", ["course" => $courseId]);
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
        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");
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
                    $itemId = Core::$systemDB->select("skill", ["treeId" => $treeId, "name" => $item[$nameIndex]], "id");

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
                    $tierExists = Core::$systemDB->select("skill_tier", ["treeId" => $treeId, "tier" => $item[$tierIndex]]);
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

    /*** ---------- Skills ---------- ***/

    //gets skills that depend on a skill and are required by another skill
    public function getSkillsDependantAndRequired($normalSkill, $superSkill, $restrictions = [], $parent = null): ValueNode
    {
        $table = "skill_dependency sk join dependency d on id=dependencyId join skill s on s.id=normalSkillId"
            . " natural join tier t join skill_tree tr on tr.id=treeId " .
            "join dependency d2 on d2.superSkillId=s.id join skill_dependency sd2 on sd2.dependencyId=d2.id";

        $restrictions["sd2.normalSkillId"] = $normalSkill["value"]["id"];
        $restrictions["d.superSkillId"] = $superSkill["value"]["id"];

        $skills = Core::$systemDB->selectMultiple($table, $restrictions, "s.*,t.*", null, [], [], "s.id");
        return Dictionary::createNode($skills, 'skillTrees', "collection", $parent);
    }

    public function getSkillsAux($restrictions, $joinOn, $parentSkill, $parentTree): ValueNode
    {
        $skills = Core::$systemDB->selectMultiple(
            "skill_dependency join dependency on id=dependencyId join "
                . "skill s on s.id=" . $joinOn . " natural join tier t join skill_tree tr on tr.id=treeId",
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
        $dependency = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skill["value"]["id"]]);
        $skillName = $skill["value"]["name"];
        //goes through all dependencies to check if they unlock the skill
        $unlocked = true;
        foreach ($dependency as $dep) {
            $unlocked = true;
            $dependencySkill = Core::$systemDB->selectMultiple("skill_dependency left join skill on normalSkillId = skill.id", ["dependencyId" => $dep["id"]]);
            foreach ($dependencySkill as $depSkill) {
                if (!($depSkill["isTier"])) {
                    if (!$this->isSkillCompleted($depSkill["normalSkillId"], $user, $courseId) or ($isActive and !$depSkill["isActive"])) {
                        $unlocked = false;
                        break;
                    }
                } else if ($depSkill["isTier"]) {
                    // if it depends on a tier, check every skill from that tier
                    $tierName = Core::$systemDB->select("skill_tier", ["id" => $depSkill["normalSkillId"]], "tier");
                    $where = ["tier" => $tierName, "t.course" => $courseId];
                    if ($isActive)
                        $where["isActive"] = true;
                    $tierSkills = Core::$systemDB->selectMultiple("skill s join skill_tree t on s.treeId = t.id", $where, "s.id");
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
            "award a left join game_course_user u on a.user = u.id left join course_user c on u.id = c.id",
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
        $dependencyIDs = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skillId], "id");

        foreach ($dependencyIDs as $id) {
            $individualDeps = Core::$systemDB->selectMultiple("skill_dependency", ["dependencyId" => $id["id"]]);
            foreach ($individualDeps as $dep) {
                if ($dep["isTier"]) {
                    $name = Core::$systemDB->select("skill_tier", ["id" => $dep["normalSkillId"]], "tier");
                } else {
                    $data = Core::$systemDB->select("skill", ["id" => $dep["normalSkillId"]], "name, isActive");
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

    public function getSkills($courseId): array
    {
        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");
        $tiers = Core::$systemDB->selectMultiple("skill_tier", ["treeId" => $treeId], "*", "seqId");
        $skillsArray = array();

        foreach ($tiers as &$tier) {
            $skillsInTier = Core::$systemDB->selectMultiple("skill", ["treeId" => $treeId, "tier" => $tier["tier"]], "id,name,page,color,tier,seqId,isActive", "seqId");
            foreach ($skillsInTier as &$skill) {
                //information to match needing fields
                $skill['xp'] = $tier["reward"];
                $skill['dependencies'] = '';
                $skill['allActive'] = true;
                $skill['isActive'] = boolval($skill["isActive"]);
                if (!empty(Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skill["id"]]))) {
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

    public function newSkill($skill, $courseId)
    {
        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");

        $numSkills = $this->getNumberOfSkillsInTier($treeId, $skill["tier"]);
        $skillData = [
            "name" => $skill['name'],
            "treeId" => $treeId,
            "tier" => $skill['tier'],
            "color" => $skill['color'],
            "seqId" => $numSkills + 1
        ];

        $folder = Course::getCourseDataFolder($courseId);
        $path = $folder . '/skills/' . str_replace(' ', '', $skill['name']) . '.html';
        $descriptionPage = @file_get_contents($path);
        if ($descriptionPage === FALSE) {
            if (array_key_exists("description", $skill)) {
                $htmlDom = new DOMDocument;
                $htmlDom->preserveWhiteSpace = false;
                $htmlDom->loadHTML($skill['description']);

                $this->insertHeadHtml($htmlDom, $skill['name']);
                $htmlDom->formatOutput = true;
                $imageTags = $htmlDom->getElementsByTagName('img');
                foreach ($imageTags as $imageTag) {
                    //Get the src attribute of the image.
                    $imgSrc = $imageTag->getAttribute('src');
                    $exploded = explode("/", $imgSrc);
                    $imageName = end($exploded);
                    $imageTag->setAttribute('src', str_replace(' ', '', $skill['name']) . '/' . $imageName);
                }

                $skill['description'] = $htmlDom->saveXML($htmlDom->documentElement);
                file_put_contents($path, $skill['description']);
                $descriptionPage = @file_get_contents($path);

                $start = strpos($descriptionPage, '<body>') + 6;
                $end = stripos($descriptionPage, '</body>');
                $descriptionPage = substr($descriptionPage, $start, $end - $start);

                $skillData['page'] = htmlspecialchars(utf8_encode($descriptionPage));
            }
            //echo "Error: The skill ".$skill['name']." does not have a html file in the legacy data folder";
            //return null;
        }

        Core::$systemDB->insert("skill", $skillData);
        $skillId = Core::$systemDB->getLastId();
        $dependencyList = array();
        if ($skill["dependencies"] != "") {
            $pairDep = explode("|", str_replace(" | ", "|", $skill["dependencies"]));

            foreach ($pairDep as $dep) {
                Core::$systemDB->insert("dependency", [
                    "superSkillId" => $skillId
                ]);
                $dependencyId = Core::$systemDB->getLastId();

                $dependencies = explode("+", str_replace(" + ", "+", $dep));
                $dependency = [];
                foreach ($dependencies as $d) {
                    $isTier = false;
                    $normalSkillId = Core::$systemDB->select("skill", ["name" => trim($d)], "id");
                    if (empty($normalSkillId)) {
                        $skillTierId = Core::$systemDB->select("skill_tier", ["tier" => trim($d)], "id");
                        if (!empty($skillTierId)) {
                            Core::$systemDB->insert("skill_dependency", [
                                "dependencyId" => $dependencyId,
                                "normalSkillId" => $skillTierId,
                                "isTier" => true
                            ]);
                            $isTier = true;
                        } else {
                            echo "The skill " . $d . " does not exist";
                        }
                    } else {
                        Core::$systemDB->insert("skill_dependency", [
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
        // create rule
        $tiers = Core::$systemDB->selectMultiple("skill_tier", ["treeId" => $treeId], "*", "seqId");
        $course = Course::getCourse($courseId, false);
        //$this->generateSkillRule($course, $skill['name'], $skill['isWildcard'], $dependencyList);
        $this->generateSkillRule($course, $skill['name'], $dependencyList);
    }

    public function editSkill($skill, $courseId)
    {

        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");
        $originalSkill = Core::$systemDB->selectMultiple("skill", ["treeId" => $treeId, 'id' => $skill['id']], "*", "name");
        if(empty($originalSkill))
            return;
        else
            $originalSkill = $originalSkill[0];

        $skillData = [
            "name" => $skill['name'],
            "treeId" => $treeId,
            "tier" => $skill['tier'],
            "color" => $skill['color']
        ];

        // update description
        $folder = Course::getCourseDataFolder($courseId);
        $path = $folder . '/skills/' . str_replace(' ', '', $skill['name']); //ex: course_data/1-PCM/skills/Director
        $descriptionPage = @file_get_contents($path . '.html');
        if(!empty($skill['description'])){
            if ($descriptionPage === FALSE) {

                // update image folder if exists
                $oldDir = $folder . '/skills/' . str_replace(' ', '', $originalSkill['name']);
                if (file_exists($oldDir)) {
                    if (!file_exists($path)) {
                        // if there are no new images simply rename old folder
                        rename($oldDir, $path);
                    } else {
                        // if we have new and old images, copy each image from old folder to the new one
                        if ($dh = opendir($oldDir)) {
                            // ignore hidden files and directories
                            $ignore = array('cgi-bin', '.', '..', '._');
                            while (($file = readdir($dh)) !== false) {
                                if (!in_array($file, $ignore) and substr($file, 0, 1) != '.') {
                                    copy($oldDir . '/' . $file, $path . '/' . $file);
                                }
                            }
                            closedir($dh);
                            //rmdir($oldDir);
                        }
                    }
                    //replace image source links in the html file
                    $htmlDom = new DOMDocument;
                    $htmlDom->preserveWhiteSpace = false;
                    $htmlDom->loadHTML($skill['description']);
                    $this->insertHeadHtml($htmlDom, $skill['name']);
                    $htmlDom->formatOutput = true;
                    $imageTags = $htmlDom->getElementsByTagName('img');
                    foreach ($imageTags as $imageTag) {
                        //Get the src attribute of the image.
                        $imgSrc = $imageTag->getAttribute('src');
                        $exploded = explode("/", $imgSrc);
                        $imageName = end($exploded);
                        $imageTag->setAttribute('src', "../gamecourse/" . $path . '/' . $imageName);
                    }
                    $skill['description'] = $htmlDom->saveXML($htmlDom->documentElement);
                } else {
                    $htmlDom = new DOMDocument;

                    $htmlDom->preserveWhiteSpace = false;
                    $htmlDom->loadHTML($skill['description']);

                    $this->insertHeadHtml($htmlDom, $skill['name']);
                    $htmlDom->formatOutput = true;
                    $skill['description'] = $htmlDom->saveXML($htmlDom->documentElement);
                }
            } else {
                $htmlDom = new DOMDocument;
                $htmlDom->preserveWhiteSpace = false;
                $htmlDom->loadHTML($skill['description']);

                $this->insertHeadHtml($htmlDom, $skill['name']);
                $htmlDom->formatOutput = true;
                $skill['description'] = $htmlDom->saveXML($htmlDom->documentElement);
            }
        }
        file_put_contents($path . '.html', $skill['description']);
        $descriptionPage = @file_get_contents($path . '.html');

        $start = strpos($descriptionPage, '<body>') + 6;
        $end = stripos($descriptionPage, '</body>');
        $descriptionPage = substr($descriptionPage, $start, $end - $start);

        $skillData['page'] = htmlspecialchars(utf8_encode($descriptionPage));


        Core::$systemDB->update("skill", $skillData, ["id" => $skill["id"]]);
        $skillId = $originalSkill["id"];

        $dependencyIds = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skillId], "id");
        if ($skill["dependencies"] != "") {
            $pairDep = explode("|", str_replace(" | ", "|", $skill["dependencies"]));

            $numOfDep = count($dependencyIds);
            $numOfNewDep =  count($pairDep);

            if ($numOfDep > $numOfNewDep) {
                //delete original dependencies
                Core::$systemDB->delete("dependency", ["superSkillId" => $skillId]);

                //create new ones
                foreach ($pairDep as $dep) {
                    Core::$systemDB->insert("dependency", ["superSkillId" => $skillId]);
                    $dependencyId = Core::$systemDB->getLastId();

                    $dependencies = explode("+", str_replace(" + ", "+", $dep));
                    foreach ($dependencies as $d) {
                        $normalSkillId = Core::$systemDB->select("skill", ["name" => trim($d)], "id");
                        if (empty($normalSkillId)) {
                            $skillTierId = Core::$systemDB->select("skill_tier", ["tier" => trim($d)], "id");
                            if (!empty($skillTierId)) {
                                Core::$systemDB->insert("skill_dependency", [
                                    "dependencyId" => $dependencyId,
                                    "normalSkillId" => $skillTierId,
                                    "isTier" => true
                                ]);
                            } else {
                                echo "The skill " . $d . " does not exist";
                            }
                        } else {
                            Core::$systemDB->insert("skill_dependency", [
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
                        Core::$systemDB->insert("dependency", ["superSkillId" => $skillId]);
                        $dependencyId = Core::$systemDB->getLastId();
                        foreach ($dependencies as $d) {
                            $normalSkillId = Core::$systemDB->select("skill", ["name" => trim($d)], "id");
                            if (empty($normalSkillId)) {
                                $skillTierId = Core::$systemDB->select("skill_tier", ["tier" => trim($d)], "id");
                                if (!empty($skillTierId)) {
                                    Core::$systemDB->insert("skill_dependency", [
                                        "dependencyId" => $dependencyId,
                                        "normalSkillId" => $skillTierId,
                                        "isTier" => true
                                    ]);
                                } else {
                                    echo "The skill " . $d . " does not exist";
                                }
                            } else {
                                Core::$systemDB->insert("skill_dependency", [
                                    "dependencyId" => $dependencyId,
                                    "normalSkillId" => $normalSkillId
                                ]);
                            }
                        }
                    } else {
                        $originalDepID = $dependencyIds[$i]["id"];
                        Core::$systemDB->delete("skill_dependency", ["dependencyId" => $originalDepID]);
                        foreach ($dependencies as $d) {
                            $normalSkillId = Core::$systemDB->select("skill", ["name" => trim($d)], "id");
                            if (empty($normalSkillId)) {
                                $skillTierId = Core::$systemDB->select("skill_tier", ["tier" => trim($d)], "id");
                                if (!empty($skillTierId)) {
                                    Core::$systemDB->insert("skill_dependency", [
                                        "dependencyId" => $originalDepID,
                                        "normalSkillId" => $skillTierId,
                                        "isTier" => true
                                    ]);
                                } else {
                                    echo "The skill " . $d . " does not exist";
                                }
                            } else {
                                Core::$systemDB->insert("skill_dependency", [
                                    "dependencyId" => $originalDepID,
                                    "normalSkillId" => $normalSkillId
                                ]);
                            }
                        }
                    }
                }
            }
        } else if (!empty($dependencyIds) and $skill["dependencies"] == "") {
            //delete dependencies
            Core::$systemDB->delete("dependency", ["superSkillId" => $skillId]);
        }
    }

    public function deleteSkill($skill, $courseId)
    {
        $hasDep = Core::$systemDB->selectMultiple("skill_dependency", ["normalSkillId" => $skill["id"], "isTier" => false]);
        if (!empty($hasDep)) {
            echo "This skill is a dependency of others skills. You must remove them first.";
            return null;
        }
        $skillInfo = Core::$systemDB->select("skill left join skill_tree on skill.treeId=skill_tree.id", ["skill.id" => $skill["id"], "course" => $courseId], "name, tier, treeId");
        if(!empty($skillInfo)){
            Core::$systemDB->delete("skill", ["id" => $skill['id']]);
            $course = Course::getCourse($courseId);
            $this->deleteGeneratedRule($course, $skillInfo['name']);
        }
    }

    public function getDescriptionFromPage($skill, $courseId)
    {
        $folder = Course::getCourseDataFolder($courseId);
        $description = htmlspecialchars_decode($skill['page']);
        $description = str_replace("\"" . str_replace(' ', '',  $skill['name']), "\"" . $folder . "/skills/" . str_replace(' ', '', $skill['name']), $description);
        //$page = preg_replace( "/\r|\n/", "", $page );
        return $description;
    }

    public function createFolderForSkillResources($skill, $courseId)
    {
        $courseFolder = Course::getCourseDataFolder($courseId);
        $hasFolder = is_dir($courseFolder . "/skills/" . str_replace(' ', '',  $skill));
        if (!$hasFolder) {
            mkdir($courseFolder . "/skills/" . str_replace(' ', '',  $skill));
        }
    }

    public function insertHeadHtml(&$htmlDom, $skillName)
    {
        $htmlTag = $htmlDom->getElementsByTagName('html')->item(0);
        $bodyTag = $htmlDom->getElementsByTagName('body')->item(0);

        $headNode = $htmlDom->createElement("head");
        $titleNode = $htmlDom->createElement("title", "Skill Tree - " . $skillName);
        $headNode->appendChild($titleNode);
        $htmlTag->insertBefore($headNode, $bodyTag);
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
            "award a left join skill s on a.moduleInstance = s.id left join skill_tier t on s.tier = t.tier and t.treeId = s.treeId",
            ["a.user" => $user, "t.tier" => $tier, "a.course" => $course],
            "count(a.id) as numCompleted"
        );

        $usedWildcards = $this->getUsedWildcards($tier, $user, $course);

        $isCompleted = Core::$systemDB->selectMultiple(
            "award a left join skill s on a.moduleInstance = s.id",
            ["a.user" => $user, "a.course" => $course, "s.name" => $skill]
        );

        return (($usedWildcards < $completedWildcards[0]["numCompleted"]) or !empty($isCompleted));
    }

    public function getUsedWildcards($tier, $user, $course)
    {

        $usedWildcards = Core::$systemDB->selectMultiple(
            "award_wildcard w left join award a on w.awardId = a.id left join skill_tier t on w.tierId = t.id",
            ["a.user" => $user, "t.tier" => $tier, "a.course" => $course],
            "count(w.awardId) as numUsed"
        );

        return $usedWildcards[0]["numUsed"];
    }


    /*** ----------- Tiers ---------- ***/

    public function tierHasWildcards($tier, $course): bool
    {
        $tierSkills = Core::$systemDB->selectMultiple(
            "skill_dependency d left join skill_tier t on d.normalSkillId = t.id left join skill_tree s on t.treeId=s.id",
            ["course" => $course, "t.tier" => $tier, "d.isTier" => true],
            "count(*) as numWild"
        );

        return $tierSkills[0]["numWild"] > 0;
    }

    public function getNumberOfSkillsInTier($treeId, $tier): int
    {
        $skills = Core::$systemDB->selectMultiple("skill", ["treeId" => $treeId, "tier" => $tier]);

        return sizeof($skills);
    }

    public function getTiers($courseId, $withXP = false): array
    {
        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");
        $tiers = Core::$systemDB->selectMultiple("skill_tier", ["treeId" => $treeId], "tier,reward,seqId", "seqId");
        if ($withXP) {
            return $tiers;
        }
        return array_column($tiers, 'tier');
    }

    public static function newTier($tier, $courseId)
    {
        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");
        if(!empty($treeId)){
            $numTiers =  sizeof(Core::$systemDB->selectMultiple("skill_tier"));

            $tierData = [
                "tier" => $tier["tier"],
                "treeId" => $treeId,
                "reward" => $tier['reward'],
                "seqId" => $numTiers + 1
            ];

            Core::$systemDB->insert("skill_tier", $tierData);
        }
    }

    public function editTier($tier, $courseId)
    {
        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");
        if(!empty($treeId)){
            $tierData = [
                "tier" => $tier['tier'],
                "treeId" => $treeId,
                "reward" => intval($tier['reward'])
            ];

            Core::$systemDB->update("skill_tier", $tierData, ["treeId" => $treeId, "id" => $tier["id"]]);
        }
    }

    public function deleteTier($tier, $courseId)
    {
        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");

        $tierSkills = Core::$systemDB->selectMultiple("skill", ["treeId" => $treeId, "tier" => $tier["tier"]]);
        if (empty($tierSkills))
            Core::$systemDB->delete("skill_tier", ["treeId" => $treeId, "id" => $tier['id']]);
        else
            echo "This tier has skills. Please delete them first or change their tier.";
    }

    public function get_tiers_items($courseId): array
    {
        //tenho de dar header
        $header = ['Tier', 'XP'];
        $displayAtributes = ['tier', 'reward'];
        // items (pela mesma ordem do header)
        $items = $this->getTiers($courseId, true);
        //argumentos para add/edit
        $allAtributes = [
            array('name' => "Tier", 'id' => 'tier', 'type' => "text", 'options' => ""),
            array('name' => "XP", 'id' => 'reward', 'type' => "number", 'options' => "")
        ];
        return array('listName' => 'Tiers', 'itemName' => 'Tier', 'header' => $header, 'displayAttributes' => $displayAtributes, 'items' => $items, 'allAttributes' => $allAtributes);
    }

    public function save_tiers($actiontype, $item, $courseId)
    {
        if ($actiontype == 'new') {
            $this->newTier($item, $courseId);
        } elseif ($actiontype == 'edit') {
            $this->editTier($item, $courseId);
        } elseif ($actiontype == 'delete') {
            $this->deleteTier($item, $courseId);
        }
    }


    /*** ---------- Rewards --------- ***/

    public function saveMaxReward($max, $courseId)
    {
        Core::$systemDB->update("skill_tree", ["maxReward" => $max], ["course" => $courseId]);
    }

    public function getMaxReward($courseId)
    {
        return Core::$systemDB->select("skill_tree", ["course" => $courseId], "maxReward");
    }


    /*** ----------- Rules ---------- ***/

    public function generateSkillRule($course, $skillName, $dependencies = null)
    {
        $rs = new RuleSystem($course);
        $template = file_get_contents($rs->getTemplateRulePath());
        $newRule = str_replace("$", $skillName, $template);

        if (sizeof($dependencies) == 0) {
            $txt = str_replace("\t\t%\n", "", $newRule);
        }
        else if (sizeof($dependencies) > 0) {
            $ruletxt = explode("%", $newRule);
            $linesDependencies = "";
            $conditiontxt = array();
            $nrdependencies = sizeof($dependencies);
            foreach ($dependencies as $dependency) {
                $deptxt = "combo" . strval($nrdependencies) . " = rule_unlocked(\"" . $dependency[0]['name'] . "\", target) and rule_unlocked(\"" . $dependency[1]['name'] . "\", target)\n\t\t";
                $linesDependencies .= $deptxt;
                array_push($conditiontxt, "combo" . strval($nrdependencies));
                $nrdependencies -= 1;
            }
            $linesDependencies = trim($linesDependencies, "\t\n");
            $lineCombo = implode(" or ", $conditiontxt);
            $linesDependencies .= "\n\t\t";
            $linesDependencies .= $lineCombo;
            array_splice($ruletxt, 1, 0, $linesDependencies);
            $txt = implode("", $ruletxt);
        }
        // add generated
        $rule = array();
        $rule["module"] = "skills";
        $filename = $rs->getFilename("skills");
        if ($filename == null) {
            $filename = $rs->createNewRuleFile("skills", 1);
            $rs->fixPrecedences();
            $filename = $rs->getFilename("skills");
        }
        $rule["rulefile"] = $filename;
        if (sizeof($dependencies) == 0 || $dependencies == null) { // if is wilcard will be added to top
            $rs->addRule($txt, 0, $rule);
        }
        else { // add to end
            $rs->addRule($txt, null, $rule);
        }
    }

    public function generateWildcardRule($course, $skillName, $dependencies = ["Doppleganger", "Alien Invasions"])
    {
        $rs = new RuleSystem($course);
        $template = file_get_contents($rs->getTemplateWildcardRulePath());
        $tierName = "Wildcard";
        $newRule = str_replace("$", $skillName, $template);
        $newRuleAll = str_replace("~", $tierName, $newRule);

        $ruletxt = explode("%", $newRuleAll);

        $skillLines = array();
        $skillsBoolList = array();
        $condsLines = array();
        $condsLineLines = array();

        foreach ($dependencies as $i => $dependency) {
            # skill dependencies
            $skillLine = "skill" . strval($i + 1) . ' = rule_unlocked("' . $dependency . '", target)';
            array_push($skillLines, $skillLine);
            array_push($skillsBoolList, "skill" . strval($i + 1));
            array_push($condsLines, "cond" . strval($i + 1) . " = skill" . strval($i + 1) . " and wildcard");
            array_push($condsLineLines, "cond" . strval($i + 1));
        }

        $skillLine = implode("\n\t\t", $skillLines);

        $bools = implode(" and ", $skillsBoolList);
        $lineBools = "skill_based = " . $bools . "\n\t\t";

        $conds = implode("\n\t\t", $condsLines);
        $condsLine = implode(" or ", $condsLineLines);
        $allConds = "\n\t\t" . "skill_based or " . $condsLine . "\n";

        array_splice($ruletxt, 1, 0, $skillLine);
        array_splice($ruletxt, 3, 0, $conds);
        array_splice($ruletxt, 3, 0, $lineBools);
        array_splice($ruletxt, 5, 0, $allConds);

        $txt = implode("", $ruletxt);

        // add generated
        $rule = array();
        $rule["module"] = "skills";
        $filename = $rs->getFilename("skills");
        if ($filename == null) {
            $filename = $rs->createNewRuleFile("skills", 1);
            $rs->fixPrecedences();
        }
        $rule["rulefile"] = $filename;
        $rs->addRule($txt, 0, $rule);
    }

    public function deleteGeneratedRule($course, $skillName)
    {
        $rs = new RuleSystem($course);
        $rule = array();

        $rule["name"] = $skillName;
        $rule["rulefile"] = $rs->getFilename("skills");
        $position = $rs->getRulePosition($rule);

        if ($position !== false)
            $rs->removeRule($rule, $position);
    }


    /*** ----------- Misc ---------- ***/

    public function changeSeqId($courseId, $itemId, $oldSeq, $nextSeq, $tierOrSkill)
    {
        $treeId = Core::$systemDB->select("skill_tree", ["course" => $courseId], "id");
        if ($tierOrSkill == "tier") {
            // if this tier will be the first one
            if ($nextSeq + 1 == 1) {
                $skillsInTier = Core::$systemDB->selectMultiple("skill", ["treeId" => $treeId, "tier" => $itemId]);
                foreach ($skillsInTier as $skill) {
                    $dependencies = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skill["id"]], "id");
                    if (!empty($dependencies)) {
                        foreach ($dependencies as $dep) {
                            Core::$systemDB->delete("dependency", ["id" => $dep["id"]]);
                        }
                    }
                }
            }
            Core::$systemDB->update("skill_tier", ["seqId" => $oldSeq + 1], ["seqId" => $nextSeq + 1, "treeId" => $treeId]);
            Core::$systemDB->update("skill_tier", ["seqId" => $nextSeq + 1], ["seqId" => $oldSeq + 1, "tier" => $itemId, "treeId" => $treeId]);
        } else {
            $tier = Core::$systemDB->select("skill", ["treeId" => $treeId, "id" => $itemId], "tier");
            Core::$systemDB->update("skill", ["seqId" => $oldSeq + 1], ["seqId" => $nextSeq + 1, "treeId" => $treeId, "tier" => $tier]);
            Core::$systemDB->update("skill", ["seqId" => $nextSeq + 1], ["seqId" => $oldSeq + 1, "id" => $itemId, "treeId" => $treeId]);
        }
    }

    public function toggleItemParam(int $itemId, string $param)
    {
        $state = Core::$systemDB->select("skill", ["id" => $itemId], $param);
        Core::$systemDB->update("skill", [$param => $state ? 0 : 1], ["id" => $itemId]);
    }
}

ModuleLoader::registerModule(array(
    'id' => 'skills',
    'name' => 'Skills',
    'description' => 'Generates a skill tree where students have to complete several skills to achieve a higher layer',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'xp', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Skills();
    }
));
