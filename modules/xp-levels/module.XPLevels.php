<?php

use GameCourse\Module;
use Modules\Views\Expression\ValueNode;
use GameCourse\Core;
use GameCourse\ModuleLoader;

class XPLevels extends Module
{

    //const LEVEL_TABLE = "level left join badge_has_level on levelId=id";

    public function setupResources()
    {
        parent::addResources('css/awards.css');
    }

    public function deleteDataRows()
    {
        $lvls = Core::$systemDB->selectMultiple("level left join badge_has_level on levelId=id", ["course" => $this->getCourseId(), "badgeId" => null]);
        foreach ($lvls as $lvl) {
            Core::$systemDB->delete("level", ["id" => $lvl["id"]]);
        }
    }

    public function calculateBonusBadgeXP($userId, $courseId)
    {
        $table = "award a join badge b on moduleInstance=b.id";
        $where = ["a.course" => $courseId, "user" => $userId, "type" => "badge"];
        $maxBonusXP = Core::$systemDB->select("badges_config", ["course" => $courseId], "maxBonusReward");
        $bonusBadgeXP = Core::$systemDB->select($table, array_merge($where, ["isExtra" => true]), "sum(reward)");
        return min($bonusBadgeXP, $maxBonusXP);
    }
    public function calculateBadgeXP($userId, $courseId)
    {
        //badges XP (bonus badges have a maximum value of XP)
        $table = "award a join badge b on moduleInstance=b.id";
        $where = ["a.course" => $courseId, "user" => $userId, "type" => "badge"];
        $normalBadgeXP = Core::$systemDB->select($table, array_merge($where, ["isExtra" => false]), "sum(reward)");
        $badgeXP = $normalBadgeXP + $this->calculateBonusBadgeXP($userId, $courseId);
        return $badgeXP;
    }
    public function calculateSkillXP($userId, $courseId)
    {
        //skills XP (skill trees have a maximum value of XP)
        $skillTrees = Core::$systemDB->selectMultiple("skill_tree", ["course" => $courseId]);
        $skillTreeXP = 0;
        foreach ($skillTrees as $tree) {
            $fullTreeXP = Core::$systemDB->select(
                "award a join skill s on moduleInstance=s.id",
                ["a.course" => $courseId, "user" => $userId, "type" => "skill", "treeId" => $tree["id"]],
                "sum(reward)"
            );
            $skillTreeXP += min($fullTreeXP, $tree["maxReward"]);
        }
        return $skillTreeXP;
    }

    public function calculateXPComponents($user, $courseId)
    {
        $userId = $this->getUserId($user);
        $xp = [];
        //badge XP
        $xp["badgeXP"] = $this->calculateBadgeXP($userId, $courseId);
        //skills XP 
        $xp["skillXP"] = $this->calculateSkillXP($userId, $courseId);

        $xp["labXP"] = Core::$systemDB->select(
            "award",
            ["course" => $courseId, "user" => $userId, "type" => "labs"],
            "sum(reward)"
        );
        $xp["quizXP"] = Core::$systemDB->select(
            "award",
            ["course" => $courseId, "user" => $userId, "type" => "quiz"],
            "sum(reward)"
        );
        $xp["presentationXP"] = Core::$systemDB->select(
            "award",
            ["course" => $courseId, "user" => $userId, "type" => "presentation"],
            "sum(reward)"
        );
        $xp["bonusXP"] = Core::$systemDB->select(
            "award",
            ["course" => $courseId, "user" => $userId, "type" => "bonus"],
            "sum(reward)"
        );
        $xp["xp"] = array_sum($xp);
        return $xp;
    }
    //calculates total xp of an user
    public function calculateXP($user, $courseId)
    {
        $userId = $this->getUserId($user);
        //badge XP
        $badgeXP = $this->calculateBadgeXP($userId, $courseId);
        //skills XP 
        $skillXP = $this->calculateSkillXP($userId, $courseId);
        //XP of everything else
        $otherXP = Core::$systemDB->select(
            "award",
            ["course" => $courseId, "user" => $userId],
            "sum(reward)",
            null, //where
            [["type", "skill"], ["type", "badge"]]
        ); //where not
        return $badgeXP + $skillXP + $otherXP;
    }
    public function init()
    {

        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $course = $this->getParent();
        $courseId = $course->getId();

        $levelTable = function ($badgesExist) {
            return (!$badgesExist) ? "level" : "level left join badge_has_level on levelId=id";
        };
        $levelWhere = function ($badgesExist) use ($courseId) {
            return (!$badgesExist) ? ["course" => $courseId] : ["course" => $courseId, "badgeId" => null];
        };
        //xp.allLevels returns collection of level objects
        $viewHandler->registerFunction('xp', 'getAllLevels', function () use ($levelWhere, $levelTable) {
            $badgesExist = ($this->getParent()->getModule("badges") !== null);
            $table = $levelTable($badgesExist);
            $where = $levelWhere($badgesExist);
            $levels = Core::$systemDB->selectMultiple($table, $where);
            return $this->createNode($levels, 'xp', "collection");
        }, 'collection');
        //xp.getLevel(user,number,goal) returns level object
        $viewHandler->registerFunction('xp', 'getLevel', function (int $user = null, int $number = null, string $goal = null) use ($levelWhere, $levelTable) {
            $badgesExist = ($this->getParent()->getModule("badges") !== null);
            $table = $levelTable($badgesExist);
            $where = $levelWhere($badgesExist);
            if ($user !== null) {
                //calculate the level of the user
                $xp = $this->calculateXP($user, $where["course"]);
                $goal = Core::$systemDB->select(
                    $table,
                    $where,
                    "max(goal)",
                    null,
                    [],
                    [["goal", "<=", $xp]]
                );
            }
            //get a level with a specific number or reward
            if ($number !== null)
                $where["number"] = $number;
            else if ($goal !== null)
                $where["goal"] = $goal;

            $level = Core::$systemDB->select($table, $where);
            if (empty($level))
                throw new Exception("In function xp.getLevel(...): couldn't find level with the given information");
            return $this->createNode($level, 'xp');
        }, 'object');
        //xp.getBadgesXP(user) returns value of badge xp for user
        $viewHandler->registerFunction('xp', 'getBadgesXP', function ($user) use ($courseId) {
            $userId = $this->getUserId($user);
            $badgeXP = $this->calculateBadgeXP($userId, $courseId);
            return new ValueNode($badgeXP);
        }, 'integer');
        //xp.getBonusBadgesXP(user) returns value xp of extra credit badges for user
        $viewHandler->registerFunction('xp', 'getBonusBadgesXP', function ($user) use ($courseId) {
            $userId = $this->getUserId($user);
            $badgeXP = $this->calculateBonusBadgeXP($userId, $courseId);
            return new ValueNode($badgeXP);
        }, 'integer');
        //xp.getSkillTreeXP(user) returns value of skill xp for user
        $viewHandler->registerFunction('xp', 'getSkillTreeXP', function ($user) use ($courseId) {
            $userId = $this->getUserId($user);
            $skillXP = $this->calculateSkillXP($userId, $courseId);
            return new ValueNode($skillXP);
        }, 'integer');
        //xp.getXP(user) returns value of xp for user
        $viewHandler->registerFunction('xp', 'getXP', function ($user) use ($courseId) {
            return new ValueNode($this->calculateXP($user, $courseId));
        }, 'integer'); //same function 
        $viewHandler->registerFunction('xp', 'getXp', function ($user) use ($courseId) {
            return new ValueNode($this->calculateXP($user, $courseId));
        }, 'integer');
        //%level.description
        $viewHandler->registerFunction('xp', 'description', function ($level) {
            return $this->basicGetterFunction($level, "description");
        }, 'string');
        //%level.goal
        $viewHandler->registerFunction('xp', 'goal', function ($level) {
            return $this->basicGetterFunction($level, "goal");
        }, 'string');
        //%level.number
        $viewHandler->registerFunction('xp', 'number', function ($level) {
            return $this->basicGetterFunction($level, "number");
        }, 'string');

        /*$viewHandler->registerFunction('awardLatestImage', function($award, $course) {
            switch ($award['type']) {
                case 'grade':
                    return new Modules\Views\Expression\ValueNode('<img src="images/quiz.svg">');
                case 'badge':
                    $imgName = str_replace(' ', '', $award['name']) . '-' . $award['level'];
                    return new Modules\Views\Expression\ValueNode('<img src="badges/' . $imgName . '.png">');
                    break;
                case 'skill':
                    $color = '#fff';
                    $skillColor = \GameCourse\Core::$systemDB->select("skill",["name"=>$award['name'],"course"=>$course],"color");
                    if($skillColor)
                        $color=$skillColor;
                    return new Modules\Views\Expression\ValueNode('<div class="skill" style="background-color: ' . $color . '">');
                case 'bonus':
                    return new Modules\Views\Expression\ValueNode('<img src="images/awards.svg">');
                default:
                    return new Modules\Views\Expression\ValueNode('<img src="images/quiz.svg">');
            }
        });

        $viewHandler->registerFunction('formatAward', function($award) {
            switch ($award['type']) {
                case 'grade':
                    return new Modules\Views\Expression\ValueNode('Grade from ' . $award['name']);
                case 'badge':
                    $imgName = str_replace(' ', '', $award['name']) . '-' . $award['level'];
                    return new Modules\Views\Expression\ValueNode('Earned ' . $award['name'] . ' (level ' . $award['level'] . ') <img src="badges/' . $imgName . '.png">');
                    break;
                case 'skill':
                    return new Modules\Views\Expression\ValueNode('Skill Tree ' . $award['name']);
                case 'bonus':
                default:
                    return new Modules\Views\Expression\ValueNode($award['name']);
            }
        });

        $viewHandler->registerFunction('formatAwardLatest', function($award) {
            switch ($award['type']) {
                case 'badge':
                    return new Modules\Views\Expression\ValueNode($award['name'] . ' (level ' . $award['level'] . ')');
                case 'skill':
                    return new Modules\Views\Expression\ValueNode('Skill Tree ' . $award['name']);
                default:
                    return new Modules\Views\Expression\ValueNode($award['name']);
            }
        });

        $viewHandler->registerFunction('awardsXP', function($userData) {
            if (is_array($userData) && sizeof($userData)==1 && array_key_exists(0, $userData))
                $userData=$userData[0];
            $mandatory = $userData['XP'] - $userData['countedTreeXP'] - min($userData['extraBadgeXP'], 1000);
            return new Modules\Views\Expression\ValueNode($userData['XP'] . ' total, ' . $mandatory . ' mandatory, ' . $userData['countedTreeXP'] .  ' from tree, ' . min($userData['extraBadgeXP'], 1000) . ' bonus');
        });*/
    }
}

ModuleLoader::registerModule(array(
    'id' => 'xp',
    'name' => 'XP and Levels',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new XPLevels();
    }
));
