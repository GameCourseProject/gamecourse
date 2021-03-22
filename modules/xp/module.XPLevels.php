<?php

use GameCourse\Module;
use Modules\Views\Expression\ValueNode;
use GameCourse\Core;
use GameCourse\ModuleLoader;
use GameCourse\API;
use GameCourse\Course;

class XPLevels extends Module
{

    //const LEVEL_TABLE = "level left join badge_has_level on levelId=id";
    
    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/user-awards.css');
    }

    public function deleteDataRows($courseId)
    {
        $lvls = Core::$systemDB->selectMultiple("level", ["course" => $courseId]);
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
        $value = min($bonusBadgeXP, $maxBonusXP);
        return (is_null($value))? 0 : $value;
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

    //returns the total xp from user_xp table for the course user
    public function getUserXP($user, $courseId)
    {
        $userId = $this->getUserId($user);
        $totalXP = Core::$systemDB->select("user_xp", ["course" => $courseId, "user" => $userId], "xp");
        return $totalXP;
    }

    //returns the current level from user_xp table for the course user
    public function getUserLevel($user, $courseId)
    {
        $userId = $this->getUserId($user);
        $level = Core::$systemDB->select("user_xp", ["course" => $courseId, "user" => $userId], "level");
        return Core::$systemDB->select("level", ["id" => $level]);
    }

    public function init()
    {

        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $course = $this->getParent();
        $courseId = $course->getId();
        $this->addTables("xp", "level");

        //create level zero
        $levelZero = Core::$systemDB->select("level", ["course" => $courseId, "number" => 0], "id");
        if(empty($levelZero))
            $levelZero = Core::$systemDB->insert("level", ["course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL"]);

        //create first entry for every user of the course so that we only have to update later
        $students = $course->getUsersWithRole("Student");
        foreach ($students as $student){
            $entry = Core::$systemDB->select("user_xp", ["course" => $courseId, "user" => $student["id"]]);
            if(!$entry)
                Core::$systemDB->insert("user_xp", ["course" => $courseId, "user" => $student["id"], "xp" => 0 ,"level" => $levelZero]);
        }

        /*$levelTable = function ($badgesExist) {
            return (!$badgesExist) ? "level" : "level left join badge_has_level on levelId=id";
        };*/
        /*$levelWhere = function ($badgesExist) use ($courseId) {
            return (!$badgesExist) ? ["course" => $courseId] : ["course" => $courseId, "badgeId" => null];
        };*/
        $viewHandler->registerLibrary("xp", "xp", "This library provides information regarding XP and Levels. It is provided by the xp module.");

        //xp.allLevels returns collection of level objects
        $viewHandler->registerFunction(
            'xp',
            'getAllLevels',
            function () use ($courseId)/*use ($levelWhere, $levelTable)*/ {
                $badgesExist = ($this->getParent()->getModule("badges") !== null);
                $table = "level";
                $where = ["course" => $courseId];
                $levels = Core::$systemDB->selectMultiple($table, $where);
                return $this->createNode($levels, 'xp', "collection");
            },
            'Returns a collection with all the levels on a Course.',
            'collection',
            'level',
            'library',
            null
        );
        //xp.getLevel(user,number,goal) returns level object
        $viewHandler->registerFunction(
            'xp',
            'getLevel',
            function ($user = null, int $number = null, string $goal = null) use ($courseId)/*use ($levelWhere, $levelTable)*/ {
                //$badgesExist = ($this->getParent()->getModule("badges") !== null);
                //$table = $levelTable($badgesExist);
                //$where = $levelWhere($badgesExist);
                $table = "level";
                $where = ["course" => $courseId];
                if ($user !== null) {
                    //calculate the level of the user
                    //$xp = $this->calculateXP($user, $where["course"]);
                    $xp = $this->getUserXP($user, $where["course"]);
                    
                    $goal = Core::$systemDB->select(
                        $table,
                        $where,
                        "max(goal)",
                        null,
                        [],
                        [["goal", "<=", $xp]]
                    );

                    //enable once gamerules can calculate the level
                    //$goal = $this->getUserLevel($user, $where["course"])["goal"];
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
            },
            "Returns a level object. The optional parameters can be used to find levels that specify a given combination of conditions:\nuser: The id of a GameCourseUser.\nnumber: The number to which the level corresponds to.\ngoal: The goal required to achieve the target level.",
            'object',
            'level',
            'library',
            null
        );
        //xp.getBadgesXP(user) returns value of badge xp for user
        $viewHandler->registerFunction(
            'xp',
            'getBadgesXP',
            function ($user) use ($courseId) {
                $userId = $this->getUserId($user);
                $badgeXP = $this->calculateBadgeXP($userId, $courseId);
                return new ValueNode($badgeXP);
            },
            'Returns the sum of XP that all Badges provide as reward from a GameCourseUser identified by user.',
            'integer',
            null,
            'library',
            null
        );
        //xp.getBonusBadgesXP(user) returns value xp of extra credit badges for user
        $viewHandler->registerFunction(
            'xp',
            'getBonusBadgesXP',
            function ($user) use ($courseId) {
                $userId = $this->getUserId($user);
                $badgeXP = $this->calculateBonusBadgeXP($userId, $courseId);
                return new ValueNode($badgeXP);
            },
            'Returns the sum of XP that all Bonus Badges provide as reward from a GameCourseUser identified by user.',
            'integer',
            null,
            'library',
            null
        );
        //xp.getSkillTreeXP(user) returns value of skill xp for user
        $viewHandler->registerFunction(
            'xp',
            'getSkillTreeXP',
            function ($user) use ($courseId) {
                $userId = $this->getUserId($user);
                $skillXP = $this->calculateSkillXP($userId, $courseId);
                return new ValueNode($skillXP);
            },
            'Returns the sum of XP that all SkillTrees provide as reward from a GameCourseUser identified by user.',
            'integer',
            null,
            'library',
            null
        );
        //xp.getXP(user) returns value of xp for user
        $viewHandler->registerFunction(
            'xp',
            'getXP',
            function ($user) use ($courseId) {
                //return new ValueNode($this->calculateXP($user, $courseId));
                return new ValueNode($this->getUserXP($user, $courseId));
            },
            'Returns the sum of XP that all Modules provide as reward from a GameCourseUser identified by user.',
            'integer',
            null,
            'library',
            null
        );
        //%level.description
        $viewHandler->registerFunction(
            'xp',
            'description',
            function ($level) {
                return $this->basicGetterFunction($level, "description");
            },
            'Returns a string with information regarding the level.',
            'string',
            null,
            'object',
            'level'
        );
        //%level.goal
        $viewHandler->registerFunction(
            'xp',
            'goal',
            function ($level) {
                return $this->basicGetterFunction($level, "goal");
            },
            'Returns a string with the goal regarding the level.',
            'string',
            null,
            'object',
            'level'
        );
        //%level.number
        $viewHandler->registerFunction(
            'xp',
            'number',
            function ($level) {
                return $this->basicGetterFunction($level, "number");
            },
            'Returns a string with the number regarding the level.',
            'string',
            null,
            'object',
            'level'
        );

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

        //update list of skills of the course skill tree, from the skills configuration page
        //ToDo make ths work for multiple skill trees
        API::registerFunction('settings', 'courseSkills', function() {
            API::requireCourseAdminPermission();
            $courseId=API::getValue('course');
            $folder = Course::getCourseLegacyFolder($courseId);
            //For now we only have 1 skill tree per course, if we have more this line needs to change
            $tree = Core::$systemDB->select("skill_tree",["course"=>$courseId]);
            $treeId=$tree["id"];
            if (API::hasKey('maxReward')) {
                $max=API::getValue('maxReward');
                if ($tree["maxReward"] != $max) {
                    Core::$systemDB->update("skill_tree", ["maxReward" => $max], ["id" => $treeId]);
                }
                API::response(["updatedData"=>["Max Reward set to ".$max] ]);
                return;
            }
            if (API::hasKey('skillsList')) {
                updateSkills(API::getValue('skillsList'), $treeId, true, $folder);
                return;
            }if (API::hasKey('tiersList')) {
                $keys = array('tier', 'reward');
                $tiers = preg_split('/[\r]?\n/', API::getValue('tiersList'), -1, PREG_SPLIT_NO_EMPTY);
                
                $tiersInDB= array_column(Core::$systemDB->selectMultiple("skill_tier",
                                                ["treeId"=>$treeId],"tier"),'tier');
                $tiersToDelete= $tiersInDB;
                $updatedData=[];
                foreach($tiers as $tier) {
                    $splitInfo =preg_split('/;/', $tier);
                    if (sizeOf($splitInfo) != sizeOf($keys)) {
                        echo "Tier information was incorrectly formatted";
                        return null;
                    }
                    $tier = array_combine($keys, $splitInfo);
                    
                    if (!in_array($tier["tier"], $tiersInDB)){
                        Core::$systemDB->insert("skill_tier",
                                ["tier"=>$tier["tier"],"reward"=>$tier["reward"],"treeId"=>$treeId]);
                        $updatedData[]= "Added Tier ".$tier["tier"];
                    }else{
                        Core::$systemDB->update("skill_tier",["reward"=>$tier["reward"]],
                                                ["tier"=>$tier["tier"],"treeId"=>$treeId]);           
                        unset($tiersToDelete[array_search($tier['tier'], $tiersToDelete)]);
                    }
                }
                foreach ($tiersToDelete as $tierToDelete){
                    Core::$systemDB->delete("skill_tier",["tier"=>$tierToDelete,"treeId"=>$treeId]);
                    $updatedData[]= "Deleted Tier ".$tierToDelete." and all its skills. The Skill List may need to be updated";
                }
                API::response(["updatedData"=>$updatedData ]);
                return;
            }
            /*else if (API::hasKey('newSkillsList')) {
                updateSkills(API::getValue('newSkillsList'), $courseId, false, $folder);
                return;
            }*/
            
            $tierText="";
            $tiers = Core::$systemDB->selectMultiple("skill_tier",
                                        ["treeId"=>$treeId],'tier,reward',"tier");
            $tiersAndSkills=[];
            foreach ($tiers as &$t){//add page, have deps working, have 3 3 dependencies
                $skills = Core::$systemDB->selectMultiple("skill",["treeId"=>$treeId, "tier"=>$t["tier"]],
                                            'id,tier,name,color',"name");
                $tiersAndSkills[$t["tier"]]=array_merge($t,["skills" => $skills]);
                $tierText.=$t["tier"].';'.$t["reward"]."\n";
            }
            foreach ($tiersAndSkills as &$t){
                foreach ($t["skills"] as &$s){
                    $s['dependencies'] = getSkillDependencies($s['id']);
                }
            }
            
            $file = @file_get_contents($folder . '/tree.txt');
            if ($file===FALSE){$file="";}
            API::response(array('skillsList' => $tiersAndSkills, "file"=>$file, "file2"=>$tierText, "maxReward"=>$tree["maxReward"]));
        });
    }

    public function moduleConfigJson($courseId)
    {
        $xpArray = array();
        $xpArr = array();

        $xpVarDB_ = Core::$systemDB->selectMultiple("level", ["course" => $courseId], "*");
        foreach ($xpVarDB_ as $xpVarDB) {
            unset($xpVarDB["course"]);
            array_push($xpArray, $xpVarDB);
        }

        $xpArr["level"] = $xpArray;

        if ($xpArray) {
            return $xpArr;
        } else {
            return false;
        }
    }

    public function readConfigJson($courseId, $tables, $update)
    {
        $levelIds = array();
        if($tables) {
            $tableName = array_keys($tables);
            $i = 0;
            foreach ($tables as $table) {
                foreach ($table as $entry) {
                    $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    } else {
                        $entry["course"] = $courseId;
                        $idImported = $entry["id"];
                        unset($entry["id"]);
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                        $levelIds[$idImported] = $newId;
                    }
                }
                $i++;
            }
        }
        return $levelIds;
    }
    
    public function is_configurable(){
        return true;
    }

    public function getLevels($courseId){
        $levels = Core::$systemDB->selectMultiple("level",["course"=>$courseId],"*", "number");

        foreach($levels as &$lvl){
            $lvl["goal"] = intval($lvl["goal"]);
        }
        
        return $levels;
    }

    public function newLevel($level, $courseId){
        $levelData = ["number"=>$level['goal'] / 1000,
                    "course"=>$courseId,"description"=>$level['description'],
                    "goal"=> $level['goal']];

        Core::$systemDB->insert("level",$levelData);
    }

    public function editLevel($level, $courseId){

        $levelData = ["number"=> $level['goal'] / 1000,
                    "course"=>$courseId,
                    "description"=>$level['description'],
                    "goal"=> $level['goal']];
        Core::$systemDB->update("level",$levelData,["id"=>$level["id"]]);
    }


    public function deleteLevel($level, $courseId){
        Core::$systemDB->delete("level",["id"=>$level['id']]);
    }

    public function has_general_inputs (){ return false; }

    public function has_listing_items (){ return  true; }

    public function get_listing_items ($courseId){
        //tenho de dar header
        $header = ['Level', 'Title', 'Minimum XP'] ;
        $displayAtributes = ['number', 'description', 'goal'];
        // items (pela mesma ordem do header)
        $items = $this->getLevels($courseId);
        //argumentos para add/edit
        $allAtributes = [
            array('name' => "Title", 'id'=> 'description', 'type' => "text", 'options' => ""),
            array('name' => "Minimum XP", 'id'=> 'goal', 'type' => "number", 'options' => ""),
        ];
        return array( 'listName'=> 'Levels', 'itemName'=> 'Level','header' => $header, 'displayAtributes'=> $displayAtributes, 'items'=> $items, 'allAtributes'=>$allAtributes);
    }
    public function save_listing_item ($actiontype, $listingItem, $courseId){
        if($actiontype == 'new'){
            $this->newLevel($listingItem, $courseId);
        }
        elseif ($actiontype == 'edit'){
            $this->editLevel($listingItem, $courseId);

        }elseif($actiontype == 'delete'){
            $this->deleteLevel($listingItem, $courseId);
        }
    }

    public static function importItems($course, $fileData, $replace = true){
        $newItemNr = 0;
        $lines = explode("\n", $fileData);
        $has1stLine = false;
        $descriptionIndex = "";
        $goalIndex = "";
        $i = 0;
        if ($lines[0]) {
            $lines[0] = trim($lines[0]);
            $firstLine = explode(";", $lines[0]);
            $firstLine = array_map('trim', $firstLine);
            if (in_array("Title", $firstLine)
                && in_array("Minimum XP", $firstLine)) {
                $has1stLine = true;
                $descriptionIndex = array_search("Title", $firstLine);
                $goalIndex = array_search("Minimum XP", $firstLine);
            }
        }
        foreach ($lines as $line) {
            $line = trim($line);
            $item = explode(";", $line);
            $item = array_map('trim', $item);
            if (count($item) > 1){
                if (!$has1stLine){
                    $descriptionIndex = 0;
                    $goalIndex = 1;
                }
                if (!$has1stLine || ($i != 0 && $has1stLine)) {
                    $itemId = Core::$systemDB->select("level", ["course"=> $course, "goal"=> $item[$goalIndex]], "id");
                    $courseObject = Course::getCourse($course);
                    $moduleObject = $courseObject->getModule("xp");
                

                    $levelData = [
                        "description"=>$item[$descriptionIndex],
                        "goal"=>$item[$goalIndex]
                        ];
                    if ($itemId){
                        if ($replace) {
                            $levelData["id"] = $itemId;
                            $moduleObject->editLevel($levelData, $course);
                        }
                    } else {
                        $moduleObject->newLevel($levelData, $course);
                    }
                }
            }
            $i++;
        }
        return $newItemNr;
    }

    public static function exportItems($course)
    {
        $courseInfo = Core::$systemDB->select("course", ["id"=>$course]);
        $listOfLevels = Core::$systemDB->selectMultiple("level", ["course"=> $course], '*');
        $file = "";
        $i = 0;
        $len = count($listOfLevels);
        $file .= "Title;Minimum XP\n";
        foreach ($listOfLevels as $badge) {

            $file .= $badge["description"] . ";" . $badge["goal"];
            if ($i != $len - 1) {
                $file .= "\n";
            }
            $i++;
        }
        return ["Levels - " . $courseInfo["name"], $file];
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'xp',
    'name' => 'XP and Levels',
    'description' => 'Enables user vocabulary to use the terms xp and points to use around the course.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new XPLevels();
    }
));
?>
