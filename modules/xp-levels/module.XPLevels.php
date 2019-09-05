<?php
use SmartBoards\Module;
use Modules\Views\Expression\ValueNode;
use SmartBoards\Core;
use SmartBoards\ModuleLoader;

define("LEVEL_TABLE", "level left join badge_has_level on levelId=id");
class XPLevels extends Module {
    
    //const LEVEL_TABLE = "level left join badge_has_level on levelId=id";
    
    
    public function setupResources() {
        parent::addResources('css/awards.css');
    }
    public function calculateBadgeXP($userId,$courseId){
        //badges XP (bonus badges have a maximum value of XP)
        $table = "award a join badge b on moduleInstance=b.id";
        $where = ["a.course"=>$courseId, "user"=>$userId, "type"=>"badge"];
            $normalBadgeXP = Core::$systemDB->select($table, array_merge($where,["isExtra"=>false]),"sum(reward)");
            $bonusBadgeXP = Core::$systemDB->select($table, array_merge($where,["isExtra"=>true]),"sum(reward)");
        $maxBonusXP = Core::$systemDB->select("badges_config",["course"=>$courseId],"maxBonusReward");
        $badgeXP = $normalBadgeXP + min($bonusBadgeXP,$maxBonusXP);
        return $badgeXP;
    }
    public function calculateSkillXP($userId,$courseId){
        //skills XP (skill trees have a maximum value of XP)
        $skillTrees = Core::$systemDB->selectMultiple("skill_tree",["course"=>$courseId]);
        $skillTreeXP=0;
        foreach($skillTrees as $tree){
            $fullTreeXP = Core::$systemDB->select("award a join skill s on moduleInstance=s.id",
                    ["a.course"=>$courseId,"user"=>$userId,"type"=>"skill","treeId"=>$tree["id"]],
                    "sum(reward)");
            $skillTreeXP += min($fullTreeXP,$tree["maxReward"]);
        }  
        return $skillTreeXP;
    }
    //calculates total xp of an user
    public function calculateXP($user,$courseId){
        $userId=$this->getUserId($user);
        //badge XP
        $badgeXP= $this->calculateBadgeXP($userId,$courseId);
        //skills XP 
        $skillTreeXP = $this->calculateSkillXP($userId,$courseId);
        //XP of everything else
        $otherXP = Core::$systemDB->select("award",
                ["course"=>$courseId,"user"=>$userId],"sum(reward)",null,//where
                [["type","skill"],["type","badge"]]);//where not
        return $otherXP + $skillTreeXP + $badgeXP;
    }
    
    public function init() {
       
        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $course = $this->getParent();
        $courseId = $course->getId();
        $levelWhere = ["course"=>$courseId, "badgeId"=>null];
        $viewHandler->registerFunction('xp','allLevels',function()use ($levelWhere){
            $levels = Core::$systemDB->selectMultiple(LEVEL_TABLE,$levelWhere);
            return $this->createNode($levels, 'xp',"collection");
        });
        $viewHandler->registerFunction('xp','getLevel',function($user=null,$number=null,$goal=null)use ($levelWhere){
            if ($user!==null){
                //calculate the level of the user
                $xp = $this->calculateXP($user,$levelWhere["course"]); 
                $goal = Core::$systemDB->select(LEVEL_TABLE,$levelWhere,
                        "max(goal)",null,[],[["goal","<=",$xp]]);
            }
            //get a level with a specific number or reward
            $where = $levelWhere;
            if($number!==null)
                $where["number"]=$number;
            else if ($goal!==null)
                $where["goal"]=$goal;

            $level = Core::$systemDB->select(LEVEL_TABLE,$where);
            
            return $this->createNode($level, 'xp');
        });
        $viewHandler->registerFunction('xp','getBadgesXP',function($user) use ($courseId){
            $userId=$this->getUserId($user);
            $badgeXP = $this->calculateBadgeXP($userId,$courseId);
            return new ValueNode($badgeXP);
        });
        $viewHandler->registerFunction('xp','getSkillTreeXP',function($user) use ($courseId){
            $userId=$this->getUserId($user);
            $skillXP = $this->calculateSkillXP($userId,$courseId);
            return new ValueNode($skillXP);
        });
        $viewHandler->registerFunction('xp','getXP',function($user) use ($courseId){
            $XP = $this->calculateXP($user,$courseId);
            return new ValueNode($XP);
        });
        $viewHandler->registerFunction('xp','description',function($level) {
            return new ValueNode($level["value"]["description"]);
        });
        $viewHandler->registerFunction('xp','goal',function($level) {
            return new ValueNode($level["value"]["goal"]);
        });
        $viewHandler->registerFunction('xp','number',function($level) {
            return new ValueNode($level["value"]["number"]);
        });
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
                    $skillColor = \SmartBoards\Core::$systemDB->select("skill",["name"=>$award['name'],"course"=>$course],"color");
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
        'factory' => function() {
            return new XPLevels();
        }
    ));
?>
