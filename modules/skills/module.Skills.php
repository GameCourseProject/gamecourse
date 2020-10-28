<?php

use GameCourse\API;
use GameCourse\Module;
use Modules\Views\Expression\ValueNode;
use GameCourse\ModuleLoader;
use GameCourse\Core;
use GameCourse\Course;

class Skills extends Module
{

    const SKILL_TREE_TEMPLATE = 'Skill Tree - by skills';
    const SKILLS_OVERVIEW_TEMPLATE = 'Skills Overview - by skills';

    public function __construct()
    {
        parent::__construct('skills', 'Skills', '0.1', array(
            array('id' => 'views', 'mode' => 'hard')
        ));
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/skills.css');
    }

    public function moduleConfigJson($courseId){
        $skillModuleArr = array();
        $skillTreeArray = array();
        $skillTierArray = array();
        $skillArray = array();
        $dependencyArray = array();
        $skillDependencyArray = array();

        $skillTreeVarDB_ = Core::$systemDB->selectMultiple("skill_tree", ["course" => $courseId], "*");
        if ($skillTreeVarDB_) {
            //values da skill_tree
            foreach ($skillTreeVarDB_ as $skillTreeVarDB) {
                array_push($skillTreeArray, $skillTreeVarDB);

                $skillTierVarDB_ = Core::$systemDB->selectMultiple("skill_tier", ["treeId" => $skillTreeVarDB["id"]], "*");
                if ($skillTierVarDB_) {
                    //values da skill_tier
                    foreach ($skillTierVarDB_ as $skillTierVarDB) {
                        array_push($skillTierArray, $skillTierVarDB);

                        $skillVarDB_ = Core::$systemDB->selectMultiple("skill", ["treeId" => $skillTreeVarDB["id"], "tier" =>  $skillTierVarDB["tier"]], "*");
                        if ($skillVarDB_) {
                            //values da skill
                            foreach ($skillVarDB_ as $skillVarDB) {
                                array_push($skillArray, $skillVarDB);

                                $dependencyDB_ = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skillVarDB["id"]], "*");
                                if ($dependencyDB_) {
                                    //values da dependency
                                    foreach ($dependencyDB_ as $dependencyDB) {
                                        array_push($dependencyArray, $dependencyDB);

                                        $skillDependencyDB_ = Core::$systemDB->selectMultiple("skill_dependency", ["dependencyId" => $dependencyDB["id"], "normalSkillId" => $skillVarDB["id"]], "*");
                                        if ($skillDependencyDB_) {
                                            // values da skill_dependency
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

        if($skillModuleArr){
            return $skillModuleArr;
        }else{
            return false;
        }
    }

    public function readConfigJson($courseId, $tables, $update)
    {
        $tableName = array_keys($tables);
        $skillTreeIds = array();
        $skillIds = array();
        $dependencyIds = array();

        $i = 0;
        foreach ($tables as $table) {
            foreach ($table as $entry) {
                if($tableName[$i] == "skill_tree"){
                    $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
                    if($update && $existingCourse){
                        Core::$systemDB->update($tableName[$i], ["maxReward" => $entry["maxReward"]], ["course" => $existingCourse]);
                        $updatedTreeId = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "id");
                        $skillTreeIds[$entry["id"]] = $updatedTreeId;
                    }else{
                        $idImport = $entry["id"];
                        unset($entry["id"]);
                        $entry["course"] = $courseId;
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                        $skillTreeIds[$idImport] = $newId;
                    }
                }else if($tableName[$i] == "skill_tier"){
                    $existingCourse = Core::$systemDB->select("skill_tier", ["treeId" => $skillTreeIds[$entry["treeId"]], "tier" => $entry["tier"]]);
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], ["reward"=>$entry["reward"]], ["treeId" =>$skillTreeIds[$entry["treeId"]], "tier" => $entry["tier"]]);
                    } else {
                        $treeIdImport = $entry["treeId"];
                        $entry["treeId"] = $skillTreeIds[$treeIdImport];
                        Core::$systemDB->insert($tableName[$i], $entry);
                    }
                }else if($tableName[$i] == "skill"){
                    $existingSkill = Core::$systemDB->select("skill", ["treeId" =>$skillTreeIds[$entry["treeId"]], "tier" => $entry["tier"]]);
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
                }else if($tableName[$i] == "dependency"){
                    if (!$update) {
                        $idImport = $entry["id"];
                        unset($entry["id"]);

                        $skillIdImport = $entry["superSkillId"];
                        $entry["superSkillId"] = $skillIds[$skillIdImport];
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);

                        $dependencyIds[$idImport] = $newId;
                    }
                }else if($tableName[$i] == "skill_dependency"){
                    if(!$update){
                        $entry["dependencyId"] = $dependencyIds[$entry["dependencyId"]];
                        $entry["normalSkillId"] = $skillIds[$entry["normalSkillId"]];
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                    }
                }
            }
            $i++;
        }
        return false;
    }
    //gets skills that depend on a skill and are required by another skill
    public function getSkillsDependantAndRequired($normalSkill, $superSkill, $restrictions = [], $parent = null)
    {
        $table = "skill_dependency sk join dependency d on id=dependencyId join skill s on s.id=normalSkillId"
            . " natural join tier t join skill_tree tr on tr.id=treeId " .
            "join dependency d2 on d2.superSkillId=s.id join skill_dependency sd2 on sd2.dependencyId=d2.id";

        $restrictions["sd2.normalSkillId"] = $normalSkill["value"]["id"];
        $restrictions["d.superSkillId"] = $superSkill["value"]["id"];

        $skills = Core::$systemDB->selectMultiple($table, $restrictions, "s.*,t.*", null, [], [], "s.id");
        return $this->createNode($skills, 'skillTrees', "collection", $parent);
    }
    public function getSkillsAux($restrictions, $joinOn, $parentSkill, $parentTree)
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
            return $this->createNode($skills, 'skillTrees', "collection", $parentSkill);
        else
            return $this->createNode($skills, 'skillTrees', "collection", $parentTree);
    }
    //returns collection of skills that depend of the given skill
    public function getSkillsDependantof($skill, $restrictions = [], $parent = false)
    {
        $restrictions["normalSkillId"] = $skill["value"]["id"];
        if ($parent === false)
            return $this->getSkillsAux($restrictions, 'skillTrees', "superSkillId", $skill);
        else
            return $this->getSkillsAux($restrictions, 'skillTrees', "superSkillId", $parent);
    }
    //returns collection of skills that ae required by the given skill
    public function getSkillsRequiredBy($skill, $restrictions = [], $parent = false)
    {
        $restrictions["superSkillId"] = $skill["value"]["id"];
        if ($parent === false)
            return $this->getSkillsAux($restrictions, 'skillTrees', "normalSkillId", $skill);
        else
            return $this->getSkillsAux($restrictions, 'skillTrees', "normalSkillId", $parent);
    }
    //

    //check if skill has been completed by the user
    public function isSkillCompleted($skill, $user, $courseId)
    {
        if (is_array($skill)) //$skill can be object or id
            $skillId = $skill["value"]["id"];
        else $skillId = $skill;
        $award = $this->getAwardOrParticipation($courseId, $user, "skill", (int) $skillId);
        return (!empty($award));
    }
    //check if skill is unlocked to the user
    public function isSkillUnlocked($skill, $user, $courseId)
    {
        $dependency = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skill["value"]["id"]]);
        //goes through all dependencies to check if they unlock the skill
        $unlocked = true;
        foreach ($dependency as $dep) {
            $unlocked = true;
            $dependencySkill = Core::$systemDB->selectMultiple("skill_dependency", ["dependencyId" => $dep["id"]]);
            foreach ($dependencySkill as $depSkill) {
                if (!$this->isSkillCompleted($depSkill["normalSkillId"], $user, $courseId)) {
                    $unlocked = false;
                    break;
                }
            }
            if ($unlocked) {
                break;
            }
        }
        return ($unlocked);
    }
    //adds skills tables if they dont exist, and fills it with tiers, the remaining info needs to be setup in config page
    private function setupData($courseId)
    {
        if ($this->addTables("skills", "skill") || empty(Core::$systemDB->select("skill_tree", ["course" => $courseId]))) {
            Core::$systemDB->insert("skill_tree", ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
            $skillTree = Core::$systemDB->getLastId();
            Core::$systemDB->insert("skill_tier", ["tier" => 1, "reward" => 150, "treeId" => $skillTree]);
            Core::$systemDB->insert("skill_tier", ["tier" => 2, "reward" => 400, "treeId" => $skillTree]);
            Core::$systemDB->insert("skill_tier", ["tier" => 3, "reward" => 750, "treeId" => $skillTree]);
            Core::$systemDB->insert("skill_tier", ["tier" => 4, "reward" => 1150, "treeId" => $skillTree]);
        }
    }

    public function deleteDataRows($courseId)
    {
        Core::$systemDB->delete("skill_tree", ["course" => $courseId]);
    }

    public function init()
    {
        $courseId = $this->getParent()->getId();
        $this->setupData($courseId);

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        //functions for the expression language
        $viewHandler->registerLibrary("skills", "skillTrees", "This library provides information regarding Skill Trees. It is provided by the skills module.");

        //skillTrees.getTree(id), returns tree object
        $viewHandler->registerFunction(
            'skillTrees',
            'getTree',
            function (int $id) {
                //this is slightly pointless if the skill tree only has id and course
                //but it could eventualy have more atributes
                return $this->createNode(Core::$systemDB->select("skill_tree", ["id" => $id]), 'skillTrees');
            },
            'Returns the object skillTree with the id id.',
            'object',
            'tree',
            'library',
            null
        );

        //skillTrees.trees, returns collection w all trees
        $viewHandler->registerFunction(
            'skillTrees',
            'trees',
            function () use ($courseId) {
                return $this->createNode(Core::$systemDB->selectMultiple("skill_tree", ["course" => $courseId]), 'skillTrees', "collection");
            },
            'Returns a collection will all the Skill Trees in the Course.',
            'collection',
            'tree',
            'library',
            null
        );

        //skillTrees.getAllSkills(...) returns collection
        $viewHandler->registerFunction(
            'skillTrees',
            'getAllSkills',
            function ($tree = null, $tier = null, $dependsOn = null, $requiredBy = null) use ($courseId) {
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
                //if there are dependencies arguments we do more complex selects
                if ($dependsOn !== null) {
                    if ($requiredBy != null)
                        return $this->getSkillsDependantAndRequired($dependsOn, $requiredBy, $skillWhere, $parent);
                    return $this->getSkillsDependantof($dependsOn, $skillWhere, $parent);
                } else if ($requiredBy !== null) {
                    return $this->getSkillsRequiredBy($dependsOn, $skillWhere, $parent);
                }
                return $this->createNode(Core::$systemDB->selectMultiple(
                    "skill s natural join skill_tier t join skill_tree tr on tr.id=treeId",
                    $skillWhere,
                    "s.*,t.*"
                ), 'skillTrees', "collection", $parent);
            },
            'Returns a collection with all the skills in the Course. The optional parameters can be used to find skills that specify a given combination of conditions:\ntree: The skillTree object or the id of the skillTree object to which the skill belongs to.\ntier: The tier object or tier of the tier object of the skill.\ndependsOn: a skill that is used to unlock a specific skill.\nrequiredBy: a skill that unlocks a collection of skills.',
            'collection',
            'skill',
            'library',
            null
        );
        //%tree.getAllSkills(...) returns collection
        $viewHandler->registerFunction(
            'skillTrees',
            'getAllSkills',
            function ($tree = null, $tier = null, $dependsOn = null, $requiredBy = null) use ($courseId) {
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
                //if there are dependencies arguments we do more complex selects
                if ($dependsOn !== null) {
                    if ($requiredBy != null)
                        return $this->getSkillsDependantAndRequired($dependsOn, $requiredBy, $skillWhere, $parent);
                    return $this->getSkillsDependantof($dependsOn, $skillWhere, $parent);
                } else if ($requiredBy !== null) {
                    return $this->getSkillsRequiredBy($dependsOn, $skillWhere, $parent);
                }
                return $this->createNode(Core::$systemDB->selectMultiple(
                    "skill s natural join skill_tier t join skill_tree tr on tr.id=treeId",
                    $skillWhere,
                    "s.*,t.*"
                ), 'skillTrees', "collection", $parent);
            },
            'Returns a collection with all the skills in the Course. The optional parameters can be used to find skills that specify a given combination of conditions:\ntree: The skillTree object or the id of the skillTree object to which the skill belongs to.\ntier: The tier object or tier of the tier object of the skill.\ndependsOn: a skill that is used to unlock a specific skill.\nrequiredBy: a skill that unlocks a collection of skills.',
            'collection',
            'skill',
            'object',
            'tree'
        );

        //%tree.getSkill(name), returns skill object
        $viewHandler->registerFunction(
            'skillTrees',
            'getSkill',
            function ($tree, string $name) {
                $this->checkArray($tree, "object", "getSkill()");
                $skill = Core::$systemDB->select(
                    "skill natural join skill_tier",
                    ["treeId" => $tree["value"]["id"], "name" => $name]
                );
                if (empty($skill)) {
                    throw new \Exception("In function getSkill(...): No skill found with name=" . $name);
                }
                return $this->createNode($skill, 'skillTrees');
            },
            'Returns a skill object from a skillTree with a specific name.',
            'object',
            'skill',
            'object',
            'tree'
        );
        //%tree.getTier(number), returns tier object
        $viewHandler->registerFunction(
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
                return $this->createNode($tier, 'skillTrees');
            },
            'Returns a tier object with a specific number from a skillTree.',
            'object',
            'tier',
            'object',
            'tree'
        );
        //%tree.tiers, returns collection w all tiers of the tree
        $viewHandler->registerFunction(
            'skillTrees',
            'tiers',
            function ($tree) {
                return $this->createNode(
                    Core::$systemDB->selectMultiple(
                        "skill_tier",
                        ["treeId" => $tree["value"]["id"]]
                    ),
                    'skillTrees',
                    "collection",
                    $tree
                );
            },
            'Returns a string with the numeric value of the tier.',
            'collection',
            'tier',
            'object',
            'tree'
        );
        //%tier.skills, returns collection w all skills of the tier
        $viewHandler->registerFunction(
            'skillTrees',
            'skills',
            function ($tier) {
                $this->checkArray($tier, "object", "skills");
                return $this->createNode(
                    Core::$systemDB->selectMultiple(
                        "skill natural join skill_tier",
                        ["treeId" => $tier["value"]["treeId"], "tier" => $tier["value"]["tier"]]
                    ),
                    'skillTrees',
                    "collection",
                    $tier
                );
            },
            'Returns a collection of skill objects from a specific tier.',
            'collection',
            'skill',
            'object',
            'tier'
        );
        //%tier.nextTier, returns tier object
        $viewHandler->registerFunction(
            'skillTrees',
            'nextTier',
            function ($tier) {
                $nexttier = Core::$systemDB->select(
                    "skill_tier",
                    ["treeId" => $tier["value"]["treeId"], "tier" => $tier["value"]["tier"] + 1]
                );
                if (empty($nexttier))
                    throw new \Exception("In function .nextTier: Couldn't findo tier after tier nº" . $tier["value"]["tier"]);
                return $this->createNode($nexttier, 'skillTrees');
            },
            'Returns the next tier object from a skillTree.',
            'object',
            'tier',
            'object',
            'tier'
        );
        //%tier.previousTier, returns tier object
        $viewHandler->registerFunction(
            'skillTrees',
            'previousTier',
            function ($tier) {
                $prevtier = Core::$systemDB->select(
                    "skill_tier",
                    ["treeId" => $tier["value"]["treeId"], "tier" => $tier["value"]["tier"] - 1]
                );
                if (empty($prevtier))
                    throw new \Exception("In function .previousTier: Couldn't findo tier before tier nº" . $tier["value"]["tier"]);
                return $this->createNode($prevtier, 'skillTrees');
            },
            'Returns the previous tier object from a skillTree.',
            'object',
            'tier',
            'object',
            'tier'
        );
        //%tier.reward
        $viewHandler->registerFunction(
            'skillTrees',
            'reward',
            function ($arg) {
                return $this->basicGetterFunction($arg, "reward");
            },
            'Returns a string with the reward of completing a skill from that tier.',
            'string',
            null,
            'object',
            'tier'
        );
        //%skill.reward
        $viewHandler->registerFunction(
            'skillTrees',
            'reward',
            function ($arg) {
                return $this->basicGetterFunction($arg, "reward");
            },
            'Returns a string with the reward of completing a skill from that tier.',
            'string',
            null,
            'object',
            'skill'
        );
        //%tier.tier 
        $viewHandler->registerFunction(
            'skillTrees',
            'tier',
            function ($arg) {
                return $this->basicGetterFunction($arg, "tier");
            },
            'Returns a string with the numeric value of the tier.',
            'string',
            null,
            'object',
            'tier'
        );

        //%skill.tier
        $viewHandler->registerFunction(
            'skillTrees',
            'tier',
            function ($arg) {
                return $this->basicGetterFunction($arg, "tier");
            },
            'Returns a string with the numeric value of the tier.',
            'string',
            null,
            'object',
            'skill'
        );

        //%skill.color
        $viewHandler->registerFunction(
            'skillTrees',
            'color',
            function ($skill) {
                return $this->basicGetterFunction($skill, "color");
            },
            'Returns a string with the reference of the color in hexadecimal of the skill.',
            'string',
            null,
            'object',
            'skill'
        );
        //%skill.name
        $viewHandler->registerFunction(
            'skillTrees',
            'name',
            function ($skill) {
                return $this->basicGetterFunction($skill, "name");
            },
            'Returns a string with the name of the skill.',
            'string',
            null,
            'object',
            'skill'
        );
        //%skill.getPost(user)
        $viewHandler->registerFunction(
            'skillTrees',
            'getPost',
            function ($skill, $user) use ($courseId) {
                $this->checkArray($skill, "object", "getPost()");
                $userId = $this->getUserId($user);
                $post = Core::$systemDB->select(
                    "participation",
                    ["type" => "skill", "moduleInstance" => $skill["value"]["id"], "user" => $userId, "course" => $courseId],
                    "post"
                );
                return new ValueNode($post);
            },
            'Returns a string with the link to the post of the skill made by the GameCourseUser identified by user.',
            'string',
            null,
            'object',
            'skill'
        );
        //%skill.isUnlocked(user), returns true if skill is available to the user
        $viewHandler->registerFunction(
            'skillTrees',
            'isUnlocked',
            function ($skill, $user) use ($courseId) {
                $this->checkArray($skill, "object", "isUnlocked(...)");
                return new ValueNode($this->isSkillUnlocked($skill, $user, $courseId));
            },
            'Returns a boolean regarding whether the GameCourseUser identified by user has unlocked a skill.',
            'boolean',
            null,
            'object',
            'skill'
        );
        //%skill.isCompleted(user), returns true if skill has been achieved by the user
        $viewHandler->registerFunction(
            'skillTrees',
            'isCompleted',
            function ($skill, $user) use ($courseId) {
                $this->checkArray($skill, "object", "isCompleted(...)");
                return new ValueNode($this->isSkillCompleted($skill, $user, $courseId));
            },
            'Returns a boolean regarding whether the GameCourseUser identified by user has completed a skill.',
            'boolean',
            null,
            'object',
            'skill'
        );
        //%skill.dependsOn,return colection of dependencies, each has a colection of skills
        $viewHandler->registerFunction(
            'skillTrees',
            'dependsOn',
            function ($skill) {
                $this->checkArray($skill, "object", "dependsOn");
                $dep = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skill["value"]["id"]]);
                return $this->createNode($dep, 'skillTrees', "collection", $skill);
            },
            'Returns a collection of dependency objects that require the skill on any dependency.',
            'collection',
            'dependency',
            'object',
            'skill'
        );
        //%skill.requiredBy, returns collection of skills that depend on the given skill
        $viewHandler->registerFunction(
            'skillTrees',
            'requiredBy',
            function ($skill) {
                $this->checkArray($skill, "object", "requiredBy");
                return $this->getSkillsDependantof($skill);
            },
            'Returns a collection of skill objects that are required by the skill on any dependency.',
            'collection',
            'skill',
            'object',
            'skill'
        );
        //%dependency.simpleSkills, returns collection of the required/normal/simple skills of a dependency
        $viewHandler->registerFunction(
            'skillTrees',
            'simpleSkills',
            function ($dep) {
                $this->checkArray($dep, "object", "simpleSkills");
                $depSkills = Core::$systemDB->selectMultiple(
                    "skill_dependency join skill s on s.id=normalSkillId",
                    ["dependencyId" => $dep["value"]["id"]],
                    "s.*"
                );
                return $this->createNode($depSkills, 'skillTrees', "collection", $dep);
            },
            'Returns a collection of skills that are required to unlock a super skill from a dependency.',
            'collection',
            'skill',
            'object',
            'dependency'
        );
        //%dependency.superSkill, returns skill object
        $viewHandler->registerFunction(
            'skillTrees',
            'superSkill',
            function ($dep) {
                $this->checkArray($dep, "object", "superSkill", "superSkill");
                return $this->createNode($dep["value"]["superSkill"], 'skillTrees');
            },
            'Returns the super skill of a dependency.',
            'object',
            'skill',
            'object',
            'dependency'
        );
        //%skill.getStyle(user)
        $viewHandler->registerFunction(
            'skillTrees',
            'getStyle',
            function ($skill, $user) use ($courseId) {
                $this->checkArray($skill, "object", "getStyle");
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
            'skill'
        );

        /*$viewHandler->registerFunction('skillStyle', function($skill, $user) {
            $courseId = $this->getParent()->getId();
            $unlockedSkills=array_column(Core::$systemDB->selectMultiple("user_skill",["course"=>$courseId,"student"=> $user],"name"),"name");
                  
            if ($unlockedSkills == null)
                $unlockedSkills = array();
            $dependencies = Core::$systemDB->selectMultiple("skill_dependency",["course"=>$courseId,"skillName"=>$skill['name']]);
            $unlocked = (count($dependencies) == 0);

            foreach($dependencies as $dependency) {
                $unlock = true;
                if (!in_array($dependency['dependencyA'], $unlockedSkills) || !in_array($dependency['dependencyB'], $unlockedSkills)) {
                    $unlock = false;  
                }
                if ($unlock) {
                    $unlocked = true;
                    break;
                }
            }
            $val = 'background-color: ' . ($unlocked ? $skill['color'] : '#6d6d6d') . '; ';

            if (in_array($skill['name'], $unlockedSkills)) {
                $val .= 'box-shadow: 0 0 30px 5px green;';
            }

            return new \Modules\Views\Expression\ValueNode($val);
        });

        $skillsCache = array();
        $viewHandler->registerFunction('usersSkillsCache', function() use (&$skillsCache) {
            $course = $this->getParent();
            $students = $course->getUsersWithRole('Student');
            $studentsArray=[];
            foreach ($students as $student) {
                $studentsArray[$student['id']]=$student;
            }
            //$studentsArray = array_combine(array_column($students,"id"),$students);
                    
            $skillsCache = array();
            $skills = Core::$systemDB->selectMultiple("skill_tier natural join skill",
                                                   ["course"=>$course->getId()]);
            foreach ($skills as $skill) {
                $skillName = $skill['name'];
                $skillsCache[$skillName] = array();
                $skillStudents = Core::$systemDB->selectMultiple("user_skill",["name"=>$skillName,"course"=>$course->getId()]);
               
                foreach($skillStudents as $skillStudent) {
                    $id= $skillStudent['student'];
                    $timestamp=  strtotime($skillStudent['skillTime']);
                    $skillsCache[$skillName][] = array(
                        'id' => $id,
                        'name' => $studentsArray[$id]['name'],
                        'campus' => $studentsArray[$id]['campus'],
                        'username' => $studentsArray[$id]['username'],
                        'timestamp' => $timestamp,
                        'when' => date('d-M-Y', $timestamp)
                    );    
                }

                usort($skillsCache[$skillName], function($v1, $v2) {
                        return $v1['timestamp'] - $v2['timestamp'];
                });
                
            }
            return new Modules\Views\Expression\ValueNode('');
        });

        $viewHandler->registerFunction('numStudentsWithSkill', function($skillName) use (&$skillsCache) {
            return new \Modules\Views\Expression\ValueNode(count($skillsCache[$skillName]));
        });

        $viewHandler->registerFunction('studentsWithSkill', function($skillName) use (&$skillsCache) {
            return new \Modules\Views\Expression\ValueNode($skillsCache[$skillName]);
        });
        */
        if (!$viewsModule->templateExists(self::SKILL_TREE_TEMPLATE))
            $viewsModule->setTemplate(self::SKILL_TREE_TEMPLATE, file_get_contents(__DIR__ . '/skillTree.txt'));
        //if ($viewsModule->getTemplate(self::SKILLS_OVERVIEW_TEMPLATE) == NULL)
        //    $viewsModule->setTemplate(self::SKILLS_OVERVIEW_TEMPLATE, file_get_contents(__DIR__ . '/skillsOverview.txt'),$this->getId());

        API::registerFunction('skills', 'page', function () {
            API::requireValues('skillName');
            $skillName = API::getValue('skillName');
            $courseId = $this->getParent()->getId();

            if ($skillName) {
                $skills = Core::$systemDB->selectMultiple(
                    "skill_tier natural join skill s join skill_tree t on t.id=treeId",
                    ["course" => $courseId],
                    "name,page"
                );
                foreach ($skills as $skill) {
                    $compressedName = str_replace(' ', '', $skill['name']);
                    if ($compressedName == $skillName) {
                        $page = htmlspecialchars_decode($skill['page']);
                        //to support legacy, TODO: Remove this when skill editing is supported in GameCourse
                        preg_match_all('/\shref="([A-z]+)[.]html/', $page, $matches);
                        foreach ($matches[0] as $id => $match) {
                            $linkSkillName = $matches[1][$id];
                            $page = str_replace($match, ' ui-sref="skill({skillName:\'' . $linkSkillName . '\'})', $page);
                        }
                        $page = str_replace('src="http:', 'src="https:', $page);
                        $page = str_replace(' href="' . $compressedName, ' target="_self" ng-href="' . $this->getDir() . 'resources/' . $compressedName, $page);
                        $page = str_replace(' src="' . $compressedName, ' src="' . $this->getDir() . 'resources/' . $compressedName, $page);
                        API::response(array('name' => $skill['name'], 'description' => $page));
                    }
                }
            }
            API::error('Skill ' . $skillName . ' not found.', 404);
        });
    }
    public function is_configurable(){
        return true;
    }


}

ModuleLoader::registerModule(array(
    'id' => 'skills',
    'name' => 'Skills',
    'description' => 'Generates a skill tree where students have to complete several skills to achieve a higher layer',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Skills();
    }
));
