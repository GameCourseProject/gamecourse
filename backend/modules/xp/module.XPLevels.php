<?php
namespace Modules\XP;

use Exception;
use GameCourse\Module;
use GameCourse\Core;
use GameCourse\ModuleLoader;
use GameCourse\Course;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Expression\ValueNode;

class XPLevels extends Module
{
    const ID = 'xp';

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->setupData($this->getCourseId());
        $this->initDictionary();
    }

    public function initDictionary()
    {
        $courseId = $this->getCourseId();

        /*** ------------ Libraries ------------ ***/

        Dictionary::registerLibrary("xp", "xp", "This library provides information regarding XP and Levels. It is provided by the xp module.");


        /*** ------------ Functions ------------ ***/

        //xp.allLevels returns collection of level objects
        Dictionary::registerFunction(
            'xp',
            'getAllLevels',
            function () use ($courseId)/*use ($levelWhere, $levelTable)*/ {
                $badgesExist = ($this->getParent()->getModule("badges") !== null);
                $table = "level";
                $where = ["course" => $courseId];
                $levels = Core::$systemDB->selectMultiple($table, $where);
                return Dictionary::createNode($levels, 'xp', "collection");
            },
            'Returns a collection with all the levels on a Course.',
            'collection',
            'level',
            'library',
            null,
            true
        );

        //xp.getLevel(user,number,goal) returns level object
        Dictionary::registerFunction(
            'xp',
            'getLevel',
            function ($user = null, int $number = null, string $goal = null) use ($courseId) {

                $table = "level";
                $where = ["course" => $courseId];
                if ($user !== null) {
                    //calculate the level of the user
                    $xp = $this->getUserXP($user, $where["course"]);

                    /*$goal = Core::$systemDB->select(
                        $table,
                        $where,
                        "max(goal)",
                        null,
                        [],
                        [["goal", "<=", $xp]]
                    );*/

                    $goal = $this->getUserLevel($user, $where["course"])["goal"];
                }
                //get a level with a specific number or reward
                if ($number !== null)
                    $where["number"] = $number;
                else if ($goal !== null)
                    $where["goal"] = $goal;
                $level = Core::$systemDB->select($table, $where);
                if (empty($level))
                    throw new Exception("In function xp.getLevel(...): couldn't find level with the given information");
                return Dictionary::createNode($level, 'xp');
            },
            "Returns a level object. The optional parameters can be used to find levels that specify a given combination of conditions:\nuser: The id of a GameCourseUser.\nnumber: The number to which the level corresponds to.\ngoal: The goal required to achieve the target level.",
            'object',
            'level',
            'library',
            null,
            true
        );

        //xp.getBadgesXP(user) returns value of badge xp for user
        Dictionary::registerFunction(
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
            null,
            true
        );

        //xp.getBonusBadgesXP(user) returns value xp of extra credit badges for user
        Dictionary::registerFunction(
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
            null,
            true
        );

        //xp.getXPByType(user, type) returns value xp of a type of award for user
        Dictionary::registerFunction(
            'xp',
            'getXPByType',
            function ($user, $type) use ($courseId) {
                $userId = $this->getUserId($user);
                $xp = $this->calculateXPByType($userId, $courseId, $type);
                if (is_null($xp))
                    $xp = 0;
                return new ValueNode(["type" => 'integer', "value" => $xp]);
            },
            'Returns the sum of XP that a type of award provide as reward from a GameCourseUser identified by user.',
            'integer',
            null,
            'library',
            null,
            true
        );

        //xp.getSkillTreeXP(user) returns value of skill xp for user
        Dictionary::registerFunction(
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
            null,
            true
        );

        //xp.getXP(user) returns value of xp for user
        Dictionary::registerFunction(
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
            null,
            true
        );

        //%level.description
        Dictionary::registerFunction(
            'xp',
            'description',
            function ($level) {
                return Dictionary::basicGetterFunction($level, "description");
            },
            'Returns a string with information regarding the level.',
            'string',
            null,
            'object',
            'level',
            true
        );

        //%level.goal
        Dictionary::registerFunction(
            'xp',
            'goal',
            function ($level) {
                return Dictionary::basicGetterFunction($level, "goal");
            },
            'Returns a string with the goal regarding the level.',
            'string',
            null,
            'object',
            'level',
            true
        );

        //%level.number
        Dictionary::registerFunction(
            'xp',
            'number',
            function ($level) {
                return Dictionary::basicGetterFunction($level, "number");
            },
            'Returns a string with the number regarding the level.',
            'string',
            null,
            'object',
            'level',
            true
        );
    }

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/user-awards.css');
    }

    public function setupData(int $courseId){
        $this->addTables("xp", "level");

        //create level zero
        $levelZero = Core::$systemDB->select("level", ["course" => $courseId, "number" => 0], "id");
        if(empty($levelZero))
            $levelZero = Core::$systemDB->insert("level", ["course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL"]);

        //create first entry for every user of the course so that we only have to update later
        $course = new Course($courseId);
        $students = $course->getUsersWithRole("Student");
        foreach ($students as $student){
            $entry = Core::$systemDB->select("user_xp", ["course" => $courseId, "user" => $student["id"]]);
            if(!$entry)
                Core::$systemDB->insert("user_xp", ["course" => $courseId, "user" => $student["id"], "xp" => 0 ,"level" => $levelZero]);
        }
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson(int $courseId)
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

    public function readConfigJson(int $courseId, array $tables, bool $update): array
    {
        $levelIds = array();
        if($tables) {
            $tableName = array_keys($tables);
            $i = 0;
            foreach ($tables as $table) {
                foreach ($table as $entry) {
                    $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId, "id" => $entry["id"]]);
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

    public function is_configurable(): bool
    {
        return true;
    }

    public function has_general_inputs(): bool
    {
        return false;
    }

    public function has_listing_items(): bool
    {
        return  true;
    }

    public function get_listing_items (int $courseId): array
    {
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

    public function save_listing_item (string $actiontype, array $listingItem, int $courseId){
        if($actiontype == 'new'){
            $this->newLevel($listingItem, $courseId);
        }
        elseif ($actiontype == 'edit'){
            $this->editLevel($listingItem, $courseId);

        }elseif($actiontype == 'delete'){
            $this->deleteLevel($listingItem, $courseId);
        }
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows(int $courseId)
    {
        Core::$systemDB->delete("user_xp", ["course" => $courseId]);
        Core::$systemDB->delete("level", ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    public static function importItems(int $course, string $fileData, bool $replace = true): int
    {
        /*$courseObject = Course::getCourse($course, false);
        $moduleObject = $courseObject->getModule("xp");*/
        $moduleObject = new XPLevels();

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
        $toInsert = "";
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
                        $toInsert .= "(" . $levelData['goal'] / 1000 . "," . $course . ",\"" . $levelData['description'] . "\"," . $levelData['goal'] . "),";
                        $newItemNr++;
                    }
                }
            }
            $i++;
        }
        if($newItemNr > 0) {
            $moduleObject->insertLevels(rtrim($toInsert , ","));
        }
        return $newItemNr;
    }

    public static function exportItems(int $course): array
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


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ------------ XP ------------ ***/

    public function calculateBonusBadgeXP($userId, $courseId)
    {
        $table = "award a join badge b on moduleInstance=b.id";
        $where = ["a.course" => $courseId, "user" => $userId, "type" => "badge"];
        $maxBonusXP = Core::$systemDB->select("badges_config", ["course" => $courseId], "maxBonusReward");
        $bonusBadgeXP = Core::$systemDB->select($table, array_merge($where, ["isExtra" => true, "isActive" => true]), "sum(reward)");
        $value = min($bonusBadgeXP, $maxBonusXP);
        return (is_null($value))? 0 : $value;
    }

    public function calculateBadgeXP($userId, $courseId)
    {
        //badges XP (bonus badges have a maximum value of XP)
        $table = "award a join badge b on moduleInstance=b.id";
        $where = ["a.course" => $courseId, "user" => $userId, "type" => "badge"];
        $normalBadgeXP = Core::$systemDB->select($table, array_merge($where, ["isExtra" => false, "isActive" => true]), "sum(reward)");
        $badgeXP = $normalBadgeXP + $this->calculateBonusBadgeXP($userId, $courseId);
        return $badgeXP;
    }

    public function calculateSkillXP($userId, $courseId, $isActive = true)
    {
        //skills XP (skill trees have a maximum value of XP)
        $skillTrees = Core::$systemDB->selectMultiple("skill_tree", ["course" => $courseId]);
        $skillTreeXP = 0;
        foreach ($skillTrees as $tree) {
            $where = ["a.course" => $courseId, "user" => $userId, "type" => "skill", "treeId" => $tree["id"]];
            if ($isActive){
                $where["isActive"] = true;
            }
            $fullTreeXP = Core::$systemDB->select(
                "award a join skill s on moduleInstance=s.id",
                $where,
                "sum(reward)"
            );
            $skillTreeXP += min($fullTreeXP, $tree["maxReward"]);
        }
        return $skillTreeXP;
    }

    public function calculateXPComponents($user, $courseId): array
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

    /**
     * Calculates total XP of a user.
     */
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

    /**
     * Calculates a user's total XP for a type of award.
     */
    public function calculateXPByType($user, $courseId, $type)
    {
        $userId = $this->getUserId($user);
        $xp = Core::$systemDB->select("award", ["course" => $courseId, "user" => $userId, "type" => $type], "sum(reward)");
        return $xp;
    }

    /**
     * Returns the total XP from user_xp table for the course user.
     */
    public function getUserXP($user, $courseId)
    {
        $userId = $this->getUserId($user);
        $totalXP = Core::$systemDB->select("user_xp", ["course" => $courseId, "user" => $userId], "xp");
        return $totalXP;
    }


    /*** ---------- Levels ---------- ***/

    /**
     * Returns the current level from user_xp table for the course user.
     */
    public function getUserLevel($user, $courseId)
    {
        $userId = $this->getUserId($user);
        $level = Core::$systemDB->select("user_xp", ["course" => $courseId, "user" => $userId], "level");
        return Core::$systemDB->select("level", ["id" => $level]);
    }

    public function getLevels($courseId) {
        $levels = Core::$systemDB->selectMultiple("level",["course"=>$courseId],"*", "number");

        foreach($levels as &$lvl){
            $lvl["goal"] = intval($lvl["goal"]);
        }
        
        return $levels;
    }

    public function insertLevels($string){
        $sql = "insert into level (number, course, description, goal) values ";
        $sql .= $string . ";";
        Core::$systemDB->executeQuery($sql);
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
}

ModuleLoader::registerModule(array(
    'id' => 'xp',
    'name' => 'XP and Levels',
    'description' => 'Enables user vocabulary to use the terms xp and points to use around the course.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new XPLevels();
    }
));
?>
