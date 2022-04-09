<?php
namespace Modules\XP;

use Exception;
use GameCourse\API;
use GameCourse\Module;
use GameCourse\Core;
use GameCourse\ModuleLoader;
use GameCourse\Course;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Expression\ValueNode;
use Modules\AwardList\AwardList;
use Modules\Badges\Badges;
use Modules\Skills\Skills;
use Streaks\Streaks;
use VirtualCurrency\VirtualCurrency;

class XPLevels extends Module
{
    const ID = 'xp';

    const TABLE_LEVELS = 'level';
    const TABLE_XP = 'user_xp';


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

        Dictionary::registerLibrary(self::ID, self::ID, "This library provides information regarding XP and Levels. It is provided by the xp module.");


        /*** ------------ Functions ------------ ***/

        //xp.allLevels returns collection of level objects
        Dictionary::registerFunction(
            self::ID,
            'getAllLevels',
            function () use ($courseId)/*use ($levelWhere, $levelTable)*/ {
                $badgesExist = ($this->getParent()->getModule(Badges::ID) !== null);
                $table = self::TABLE_LEVELS;
                $where = ["course" => $courseId];
                $levels = Core::$systemDB->selectMultiple($table, $where);
                return Dictionary::createNode($levels, self::ID, "collection");
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
            self::ID,
            'getLevel',
            function ($user = null, int $number = null, string $goal = null) use ($courseId) {

                $table = self::TABLE_LEVELS;
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
                return Dictionary::createNode($level, self::ID);
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
            self::ID,
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
            self::ID,
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

        //xp.getStreaksXP(user) returns value of streak xp for user
        Dictionary::registerFunction(
            self::ID,
            'getStreaksXP',
            function ($user) use ($courseId) {
                $userId = $this->getUserId($user);
                $streakXP = $this->calculateStreakXP($userId, $courseId);
                return new ValueNode($streakXP);
            },
            'Returns the sum of XP that all Streaks provide as reward from a GameCourseUser identified by user.',
            'integer',
            null,
            'library',
            null,
            true
        );

        // TODO: xp.getStreaksTokens(user)
        // return the sum of all the tokens earned with streaks so far

        //xp.getXPByType(user, type) returns value xp of a type of award for user
        Dictionary::registerFunction(
            self::ID,
            'getXPByType',
            function ($user, $type) use ($courseId) {
                $userId = $this->getUserId($user);
                $xp = $this->calculateXPByType($userId, $courseId, $type);
                if (is_null($xp))
                    $xp = 0;
                return new ValueNode($xp);
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
            self::ID,
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
            self::ID,
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

        //xp.getExtraGradeLimit() returns value of xp for user
        Dictionary::registerFunction(
            self::ID,
            'getExtraGradeLimit',
            function () use ($courseId) {
                //return new ValueNode($this->calculateXP($user, $courseId));
                return new ValueNode(1000); // FIXME: hard-coded
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
            self::ID,
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
            self::ID,
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
            self::ID,
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

    public function initAPIEndpoints()
    {
        /**
         * Checks if students XP is correct according to awards
         * received, skill tree maximum and extra credit maximum.
         *
         * @param int $courseId
         * @param int $userId (optional)
         */
        API::registerFunction(self::ID, 'verifyXP', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);
            $enabledModules = $course->getEnabledModules();

            $studentsWithIncorrectGrade = [];
            $students = API::hasKey('userId') ? [["id" => API::getValue('userId')]] : $course->getUsersWithRole("Student");

            foreach ($students as $student) {
                // Check skill tree grade
                if (in_array(Skills::ID, $enabledModules))
                    $skillTotal = Skills::checkGrade($courseId, $student, $studentsWithIncorrectGrade);

                // Check extra credit
                if (in_array(Badges::ID, $enabledModules) || in_array(Streaks::ID, $enabledModules))
                    $extraCreditTotal = self::checkExtraCreditGrade($courseId, $enabledModules, $student, $studentsWithIncorrectGrade);

                // Check grades for badges
                if (in_array(Badges::ID, $enabledModules))
                    $badgeTotal = Badges::checkGrade($courseId, $student, $studentsWithIncorrectGrade);

                // Check grades for streaks
                // FIXME: streaks should have isExtra parameter

                // Check grades for: assignments, labs, bonus, presentation, quizzes
                $otherTotal = self::checkGeneralGrade($courseId, $student, $studentsWithIncorrectGrade);

                // Check total grade
                $studentId = $student["id"];
                $actualTotalXP = intval(Core::$systemDB->select("user_xp", ["course" => $courseId, "user" => $studentId], "xp"));
                $levelGoal = intval(Core::$systemDB->select("user_xp ux JOIN level l on ux.level=l.id", ["ux.course" => $courseId, "l.course" => $courseId, "user" => $studentId], "l.goal"));
                $awardsTotalXP = intval(Core::$systemDB->select(AwardList::TABLE, ["course" => $courseId, "user" => $studentId], "sum(reward)", null, [["type", "tokens"]]));
                $error = null;

                $totalXP = $otherTotal;
                if (in_array(Skills::ID, $enabledModules)) $totalXP += $skillTotal;
                if (in_array(Badges::ID, $enabledModules) || in_array(Streaks::ID, $enabledModules)) $totalXP += $extraCreditTotal;
                if (in_array(Badges::ID, $enabledModules)) $totalXP += $badgeTotal;

                if ($totalXP != $actualTotalXP)
                    $error["message"] = "Incorrect total grade. Total grade awarded was " . $actualTotalXP . " XP and should have been " . $totalXP . " XP.";

                if ($awardsTotalXP != $actualTotalXP)
                    $error["message"] = "Incorrect total grade. Total grade awarded was " . $actualTotalXP . " XP and should have been " . $awardsTotalXP . " XP.";

                if ($levelGoal != (floor($actualTotalXP/1000)*1000))
                    $error["message"] = "Incorrect level. Level awarded was " . $levelGoal . " and should have been " . (floor($actualTotalXP/1000)*1000) . ".";

                if (!empty($error)) {
                    if (isset($studentsWithIncorrectGrade[$studentId])) $studentsWithIncorrectGrade[$studentId][] = ["total" => $error];
                    else $studentsWithIncorrectGrade[$studentId] = [["total" => $error]];
                }

                // Check tokens
//            if (in_array(VirtualCurrency::ID, $enabledModules)) TODO
//                $tokensPerStudent = VirtualCurrency::checkTokens($courseId, $students, $studentsWithIncorrectGrade);

                // Check total tokens
                // TODO
            }

            API::response(["students" => $students, "incorrect" => $studentsWithIncorrectGrade]);
        });

        /**
         * Fixes 1st error on students XP.
         *
         * @param int $courseId
         * @param int $userId (optional)
         */
        API::registerFunction(self::ID, 'fixXP', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);
            $enabledModules = $course->getEnabledModules();

            $studentsWithIncorrectGrade = [];
            $students = API::hasKey('userId') ? [["id" => API::getValue('userId')]] : $course->getUsersWithRole("Student");

            $nrErrorsFixed = 0;
            foreach ($students as $student) {
                // Check skill tree grade
                if (in_array(Skills::ID, $enabledModules))
                    $skillTotal = Skills::checkGrade($courseId, $student, $studentsWithIncorrectGrade);

                // Check extra credit
                if (in_array(Badges::ID, $enabledModules) || in_array(Streaks::ID, $enabledModules))
                    $extraCreditTotal = self::checkExtraCreditGrade($courseId, $enabledModules, $student, $studentsWithIncorrectGrade);

                // Check grades for badges
                if (in_array(Badges::ID, $enabledModules))
                    $badgeTotal = Badges::checkGrade($courseId, $student, $studentsWithIncorrectGrade);

                // Check grades for streaks
                // FIXME: streaks should have isExtra parameter

                // Check grades for: assignments, labs, bonus, presentation, quizzes
                $otherTotal = self::checkGeneralGrade($courseId, $student, $studentsWithIncorrectGrade);

                // Check total grade
                $studentId = $student["id"];
                $actualTotalXP = intval(Core::$systemDB->select("user_xp", ["course" => $courseId, "user" => $studentId], "xp"));
                $levelGoal = intval(Core::$systemDB->select("user_xp ux JOIN level l on ux.level=l.id", ["ux.course" => $courseId, "l.course" => $courseId, "user" => $studentId], "l.goal"));
                $awardsTotalXP = intval(Core::$systemDB->select(AwardList::TABLE, ["course" => $courseId, "user" => $studentId], "sum(reward)", null, [["type", "tokens"]]));
                $error = null;

                $totalXP = $otherTotal;
                if (in_array(Skills::ID, $enabledModules)) $totalXP += $skillTotal;
                if (in_array(Badges::ID, $enabledModules) || in_array(Streaks::ID, $enabledModules)) $totalXP += $extraCreditTotal;
                if (in_array(Badges::ID, $enabledModules)) $totalXP += $badgeTotal;

                if ($totalXP != $actualTotalXP)
                    $error["message"] = "Incorrect total grade. Total grade awarded was " . $actualTotalXP . " XP and should have been " . $totalXP . " XP.";

                if ($awardsTotalXP != $actualTotalXP)
                    $error["message"] = "Incorrect total grade. Total grade awarded was " . $actualTotalXP . " XP and should have been " . $awardsTotalXP . " XP.";

                if ($levelGoal != (floor($actualTotalXP/1000)*1000))
                    $error["message"] = "Incorrect level. Level awarded was " . $levelGoal . " and should have been " . (floor($actualTotalXP/1000)*1000) . ".";

                if (!empty($error)) {
                    if (isset($studentsWithIncorrectGrade[$studentId])) $studentsWithIncorrectGrade[$studentId][] = ["total" => $error];
                    else $studentsWithIncorrectGrade[$studentId] = [["total" => $error]];
                }

                if (isset($studentsWithIncorrectGrade[$studentId])) {
                    $type = array_key_first($studentsWithIncorrectGrade[$studentId][0]);
                    if ($type != "total") {
                        $this->fixStudentGrade($courseId, $studentId, $studentsWithIncorrectGrade[$studentId][0][$type]);
                        $nrErrorsFixed++;
                    }
                }
            }

            API::response(["nrErrorsFixed" => $nrErrorsFixed]);
        });
    }

    public function setupResources() {
        parent::addResources('css/user-awards.css');
    }

    public function setupData(int $courseId){
        $this->addTables(self::ID, self::TABLE_LEVELS);

        //create level zero
        $levelZero = Core::$systemDB->select(self::TABLE_LEVELS, ["course" => $courseId, "number" => 0], "id");
        if(empty($levelZero))
            $levelZero = Core::$systemDB->insert(self::TABLE_LEVELS, ["course" => $courseId, "number" => 0, "goal" => 0, "description" => "AWOL"]);

        //create first entry for every user of the course so that we only have to update later
        $course = new Course($courseId);
        $students = $course->getUsersWithRole("Student");
        foreach ($students as $student){
            $entry = Core::$systemDB->select(self::TABLE_XP, ["course" => $courseId, "user" => $student["id"]]);
            if(!$entry)
                Core::$systemDB->insert(self::TABLE_XP, ["course" => $courseId, "user" => $student["id"], "xp" => 0 ,"level" => $levelZero]);
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

        $xpVarDB_ = Core::$systemDB->selectMultiple(self::TABLE_LEVELS, ["course" => $courseId], "*");
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

    public function has_listing_items(): bool
    {
        return  true;
    }

    public function get_listing_items(int $courseId): array
    {
        //tenho de dar header
        $header = ['Level', 'Title', 'Minimum XP'] ;
        $displayAtributes = [
            ['id' => 'number', 'type' => 'number'],
            ['id' => 'description', 'type' => 'text'],
            ['id' => 'goal', 'type' => 'number']
        ];
        $actions = ['edit', 'delete'];
        // items (pela mesma ordem do header)
        $items = $this->getLevels($courseId);
        //argumentos para add/edit
        $allAtributes = [
            array('name' => "Level", 'id' => 'number', 'type' => "number", 'options' => ["edit" => false]),
            array('name' => "Title", 'id' => 'description', 'type' => "text", 'options' => ""),
            array('name' => "Minimum XP", 'id' => 'goal', 'type' => "number", 'options' => ""),
        ];
        return array('listName'=> 'Levels', 'itemName'=> 'level', 'header' => $header, 'displayAttributes'=> $displayAtributes, 'actions' => $actions, 'items'=> $items, 'allAttributes'=>$allAtributes);
    }

    public function save_listing_item(string $actiontype, array $listingItem, int $courseId){
        if ($actiontype == 'new' || $actiontype == 'duplicate') $this->newLevel($listingItem, $courseId);
        elseif ($actiontype == 'edit') $this->editLevel($listingItem, $courseId);
        elseif($actiontype == 'delete') $this->deleteLevel($listingItem, $courseId);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows(int $courseId)
    {
        Core::$systemDB->delete(self::TABLE_XP, ["course" => $courseId]);
        Core::$systemDB->delete(self::TABLE_LEVELS, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    public function importItems(string $fileData, bool $replace = true): int
    {
        $courseId = $this->getCourseId();
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
                    $itemId = Core::$systemDB->select(self::TABLE_LEVELS, ["course"=> $courseId, "goal"=> $item[$goalIndex]], "id");

                    $levelData = [
                        "description"=>$item[$descriptionIndex],
                        "goal"=>$item[$goalIndex]
                    ];
                    if ($itemId){
                        if ($replace) {
                            $levelData["id"] = $itemId;
                            $moduleObject->editLevel($levelData, $courseId);
                        }
                    } else {
                        $toInsert .= "(" . $levelData['goal'] / 1000 . "," . $courseId . ",\"" . $levelData['description'] . "\"," . $levelData['goal'] . "),";
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

    public function exportItems(int $itemId = null): array
    {
        $courseId = $this->getCourseId();
        $course = Course::getCourse($courseId, false);

        $listOfLevels = Core::$systemDB->selectMultiple(self::TABLE_LEVELS, ["course"=> $course], '*');
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
        return ["Levels - " . $course->getName(), $file];
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ------------ XP ------------ ***/

    public function calculateBonusBadgeXP($userId, $courseId)
    {
        $table = AwardList::TABLE . " a join " . Badges::TABLE . " b on moduleInstance=b.id";
        $where = ["a.course" => $courseId, "user" => $userId, "type" => "badge"];
        $value = Core::$systemDB->select($table, array_merge($where, ["isExtra" => true, "isActive" => true]), "sum(reward)");
        return (is_null($value))? 0 : $value;
    }

    public function calculateBadgeXP($userId, $courseId)
    {
        $table = AwardList::TABLE . " a join " . Badges::TABLE . " b on moduleInstance=b.id";
        $where = ["a.course" => $courseId, "user" => $userId, "type" => "badge"];
        $badgeXP = Core::$systemDB->select($table, array_merge($where, ["isActive" => true]), "sum(reward)");
        return $badgeXP;
    }

    public function calculateStreakXP($userId, $courseId)
    {
        //streaks XP
        $table = AwardList::TABLE . " a join " . Streaks::TABLE . " s on moduleInstance=s.id";
        $where = ["a.course" => $courseId, "user" => $userId, "type" => "streak"];
        $streaks = Core::$systemDB->selectMultiple($table, array_merge($where, ["isActive" => true]), "a.reward");
        $streakXP = array_reduce($streaks, function ($carry, $streak) {
            $carry += intval($streak["reward"]);
            return $carry;
        });
        return $streakXP ?? 0;
    }

    public function calculateSkillXP($userId, $courseId, $isActive = true)
    {
        //skills XP (skill trees have a maximum value of XP)
        $skillTrees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, ["course" => $courseId]);
        $skillTreeXP = 0;
        foreach ($skillTrees as $tree) {
            $where = ["a.course" => $courseId, "user" => $userId, "type" => "skill", "treeId" => $tree["id"]];
            if ($isActive){
                $where["isActive"] = true;
            }
            $fullTreeXP = Core::$systemDB->select(
                AwardList::TABLE . " a join skill s on moduleInstance=s.id",
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
        //streaks XP
        $xp["streakXP"] = $this->calculateStreakXP($userId, $courseId);

        $xp["labXP"] = intval(Core::$systemDB->select(
            AwardList::TABLE,
            ["course" => $courseId, "user" => $userId, "type" => "labs"],
            "sum(reward)"
        ));
        $xp["quizXP"] = intval(Core::$systemDB->select(
            AwardList::TABLE,
            ["course" => $courseId, "user" => $userId, "type" => "quiz"],
            "sum(reward)"
        ));
        $xp["presentationXP"] = intval(Core::$systemDB->select(
            AwardList::TABLE,
            ["course" => $courseId, "user" => $userId, "type" => "presentation"],
            "sum(reward)"
        ));
        $xp["bonusXP"] = intval(Core::$systemDB->select(
            AwardList::TABLE,
            ["course" => $courseId, "user" => $userId, "type" => "bonus"],
            "sum(reward)"
        ));
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
            AwardList::TABLE,
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
        $xp = Core::$systemDB->select(AwardList::TABLE, ["course" => $courseId, "user" => $userId, "type" => $type], "sum(reward)");
        return $xp;
    }

    /**
     * Returns the total XP from user_xp table for the course user.
     */
    public function getUserXP($user, $courseId)
    {
        $userId = $this->getUserId($user);
        $totalXP = Core::$systemDB->select(self::TABLE_XP, ["course" => $courseId, "user" => $userId], "xp");
        return $totalXP;
    }


    /*** ---------- Levels ---------- ***/

    /**
     * Returns the current level from user_xp table for the course user.
     */
    public function getUserLevel($user, $courseId)
    {
        $userId = $this->getUserId($user);
        $level = Core::$systemDB->select(self::TABLE_XP, ["course" => $courseId, "user" => $userId], "level");
        return Core::$systemDB->select(self::TABLE_LEVELS, ["id" => $level]);
    }

    public function getLevels($courseId) {
        $levels = Core::$systemDB->selectMultiple(self::TABLE_LEVELS,["course"=>$courseId],"*", "number");

        foreach($levels as &$lvl){
            $lvl["goal"] = intval($lvl["goal"]);
        }
        
        return $levels;
    }

    public function insertLevels($string){
        $sql = "insert into " . self::TABLE_LEVELS . " (number, course, description, goal) values ";
        $sql .= $string . ";";
        Core::$systemDB->executeQuery($sql);
    }

    public function newLevel($level, $courseId){
        $levelData = [
            "number" => $level['goal'] / 1000,
            "course" => $courseId,
            "description" => $level['description'],
            "goal"=> $level['goal']
        ];
        Core::$systemDB->insert(self::TABLE_LEVELS, $levelData);
    }

    public function editLevel($level, $courseId){

        $levelData = ["number"=> $level['goal'] / 1000,
                    "course"=>$courseId,
                    "description"=>$level['description'],
                    "goal"=> $level['goal']];
        Core::$systemDB->update(self::TABLE_LEVELS, $levelData, ["id"=>$level["id"]]);
    }

    public function deleteLevel($level, $courseId){
        Core::$systemDB->delete(self::TABLE_LEVELS, ["id"=>$level['id']]);
    }


    /*** ----------- Misc ----------- ***/

    public function checkExtraCreditGrade(int $courseId, array $enabledModules, array $student, array &$studentsWithIncorrectGrade): int {
        $extraCreditMax = intval(Core::$systemDB->select(Badges::TABLE_CONFIG, ["course" => $courseId], "maxBonusReward")); // FIXME: general extra credit on XP_Levels

        $badges = [];
        if (in_array(Badges::ID, $enabledModules)) { // badges enabled
            $allBadges = Core::$systemDB->selectMultiple(Badges::TABLE . " b JOIN " . Badges::TABLE_LEVEL . " l on b.id=l.badgeId", ["b.course" => $courseId], "b.id, b.name, b.isExtra, l.number, l.reward");
            foreach ($allBadges as $badge) {
                if (isset($badges[$badge["id"]])) $badges[$badge["id"]][$badge["number"]] = $badge;
                else $badges[$badge["id"]] = [$badge["number"] => $badge];
            }
        }

        $streaks = [];
        if (in_array(Streaks::ID, $enabledModules)) { // streaks enabled
            $allStreaks = Core::$systemDB->selectMultiple(Streaks::TABLE, ["course" => $courseId], "id, name, reward");
            foreach ($allStreaks as $streak) {
                $streaks[$streak["id"]] = $streak;
            }
        }

        $studentId = $student["id"];
        $awards = Core::$systemDB->executeQuery("SELECT * FROM " . AwardList::TABLE . " WHERE course=" . $courseId . " AND user=" . $studentId . " AND (type='badge' OR type='streak') ORDER BY date;")->fetchAll(\PDO::FETCH_ASSOC);
        $extraCreditAwards = array_filter($awards, function ($award) use ($badges) {
            if ($award["type"] == "badge") {
                preg_match('/\(level (\d)\)/', $award["description"], $matches);
                return boolval($badges[$award["moduleInstance"]][$matches[1]]["isExtra"]);
            }
            if ($award["type"] == "streak") return true;
            return false;
        });

        $extraCreditTotal = 0;
        foreach ($extraCreditAwards as $award) {
            preg_match('/\(level (\d)\)/', $award["description"], $matches);
            $typedAward = $award["type"] == "badge" ? $badges[$award["moduleInstance"]][$matches[1]] : $streaks[$award["moduleInstance"]];
            $total = $extraCreditTotal + $typedAward["reward"];
            $diff = $extraCreditMax - $extraCreditTotal;
            $error = null;

            if ($total <= $extraCreditMax && $award["reward"] != $typedAward["reward"]) { // award full XP
                $error["message"] = "Incorrect grade on " . $award["type"] . " '" . $award["description"] . "'. Grade awarded was " . $award["reward"] . " XP and should have been " . $typedAward["reward"] . " XP.";
                $error["id"] = $award["id"];
                $error["reward"] = $typedAward["reward"];

            } else if ($total > $extraCreditMax && $extraCreditTotal < $extraCreditMax && $award["reward"] != $diff) { // award partial XP (reaching limit)
                $error["message"] = "Incorrect grade on " . $award["type"] . " '" . $award["description"] . "'. Grade awarded was " . $award["reward"] . " XP and should have been " . $diff . " XP.";
                $error["id"] = $award["id"];
                $error["reward"] = $diff;

            } else if ($total > $extraCreditMax && $extraCreditTotal >= $extraCreditMax && $award["reward"] != 0) { // award 0 XP (limit reached)
                $error["message"] = "Incorrect grade on " . $award["type"] . " '" . $award["description"] . "'. Grade awarded was " . $award["reward"] . " XP and should have been 0 XP.";
                $error["id"] = $award["id"];
                $error["reward"] = 0;
            }

            if (!empty($error)) {
                if (isset($studentsWithIncorrectGrade[$studentId])) $studentsWithIncorrectGrade[$studentId][] = [$award["type"] => $error];
                else $studentsWithIncorrectGrade[$studentId] = [[$award["type"] => $error]];
            }
            $extraCreditTotal += $award["reward"];
        }
        return $extraCreditTotal;
    }

    public function checkGeneralGrade(int $courseId, array $student, array &$studentsWithIncorrectGrade): int {
        $studentId = $student["id"];
        $generalAwards = Core::$systemDB->selectMultiple(AwardList::TABLE, ["course" => $courseId, "user" => $studentId], "*", null, [["type", "badge"], ["type", "streak"], ["type", "skill"], ["type", "tokens"]]);

        $generalTotal = 0;
        foreach ($generalAwards as $award) {
            // TODO: check if matches participation table
            $generalTotal += $award["reward"];
        }
        return $generalTotal;
    }

    public function fixStudentGrade(int $courseId, int $studentId, array $fix)
    {
        Core::$systemDB->update(AwardList::TABLE, ["reward" => $fix["reward"]], ["course" => $courseId, "user" => $studentId, "id" => $fix["id"]]);
    }
}

ModuleLoader::registerModule(array(
    'id' => XPLevels::ID,
    'name' => 'XP and Levels',
    'description' => 'Enables user vocabulary to use the terms xp and points to use around the course.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new XPLevels();
    }
));
