<?php

use GameCourse\Core;
use Modules\Views\Expression\ValueNode;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Course;

class Badges extends Module
{
    const BADGES_TEMPLATE_NAME = 'Badges block - by badges';
    const MAX_BONUS_BADGES = 500;

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/badges.css');
    }
    public function getBadge($selectMultiple, $where)
    {
        $where["course"] = $this->getCourseId();
        if ($selectMultiple) {
            $badgeArray = Core::$systemDB->selectMultiple("badge", $where);
            $type = "collection";
        } else {
            $badgeArray = Core::$systemDB->select("badge", $where);
            if (empty($badgeArray))
                throw new \Exception("In function badges.getBadge(name): couldn't find badge with name '" . $where["name"] . "'.");
            $type = "object";
        }
        return $this->createNode($badgeArray, 'badges', $type);
    }

    public function getLevel($levelNum, $badge)
    {
        $type = "object";
        $parent = null;
        if ($levelNum === 0) {
            $level = ["number" => 0, "description" => ""];
        } else if ($levelNum > $badge["value"]["maxLevel"] || $levelNum < 0) {
            $level = ["number" => null, "description" => null];
        } else {
            $table = "level join badge_has_level on id=levelId";
            $badgeId = $badge["value"]["id"];
            if ($levelNum == null) {
                $level = Core::$systemDB->selectMultiple($table, ["badgeId" => $badgeId]);
                $type = "collection";
                $parent = $badge;
            } else {
                $level = Core::$systemDB->select($table, ["badgeId" => $badgeId, "number" => $levelNum]);
            }
        }
        unset($badge["value"]["description"]);
        unset($level["id"]);
        if ($type == "collection") {
            foreach ($level as &$l) {
                $l["libraryOfVariable"] = "badges";
                $l = array_merge($badge["value"], $l);
            }
        } else {
            $level["libraryOfVariable"] = "badges";
            $level = array_merge($badge["value"], $level);
        }
        return $this->createNode($level, 'badges', $type, $parent);
    }

    public function getLevelNum($badge, $user)
    {
        $id = $this->getUserId($user);
        $badgeId = $badge["value"]["id"];
        $levelNum = Core::$systemDB->select("award", ["user" => $id, "type" => "badge", "moduleInstance" => $badgeId], "count(*)");
        return (int)$levelNum;
    }
    public function deleteLevels()
    {
        $levels = Core::$systemDB->selectMultiple(
            "level left join badge_has_level on levelId=id",
            ["course" => $this->getCourseId()],
            'id',
            null,
            [["levelId", null]]
        );
        foreach ($levels as $lvl) {
            Core::$systemDB->delete("level", ["id" => $lvl["id"]]);
        }
    }
    public function dropTables($moduleName)
    {
        $this->deleteLevels();
        parent::dropTables($moduleName);
    }
    public function deleteDataRows()
    {
        $this->deleteLevels();
        Core::$systemDB->delete("badge", ["course" => $this->getCourseId()]);
    }
    public function getBadgeCount($user = null)
    {
        $courseId = $this->getCourseId();
        if ($user === null) {
            return Core::$systemDB->select("badge", ["course" => $courseId], "sum(maxLevel)");
        }
        $id = $this->getUserId($user);
        return  Core::$systemDB->select("award", ["course" => $courseId, "type" => "badge", "user" => $id], "count(*)");
    }
    public function init()
    {
        if ($this->addTables("badges", "badge")) {
            Core::$systemDB->insert("badges_config", ["maxBonusReward" => MAX_BONUS_BADGES, "course" => $this->getCourseId()]);
        }
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();

        $viewHandler->registerLibrary("badges", "badges", "This library provides information regarding Badges and their levels. It is provided by the badges module.");

        //badges.getAllBadges(isExtra,IsBragging)
        $viewHandler->registerFunction(
            'badges',
            'getAllBadges',
            function (bool $isExtra = null, bool $isBragging = null) {
                $where = [];
                if ($isExtra !== null)
                    $where["isExtra"] = $isExtra;
                if ($isBragging !== null)
                    $where["isBragging"] = $isBragging;
                return $this->getBadge(true, $where);
            },
            'collection',
            "Returns a collection with all the badges in the Course. The optional parameters can be used to find badges that specify a given combination of conditions: ( isExtra: Badge has a reward; isBragging: Badge has no reward).",
            'library'
        );
        //badges.getBadge(name)
        $viewHandler->registerFunction('badges', 'getBadge', function (string $name = null) {
            return $this->getBadge(false, ["name" => $name]);
        }, 'object', 'library', "Returns the badge object with the specific name.");
        //badges.getCountBadges(user) returns num of badges of user (if specified) or of course 
        $viewHandler->registerFunction(
            'badges',
            'getBadgesCount',
            function ($user = null) {
                return new ValueNode($this->getBadgeCount($user));
            },
            'integer',
            "Returns an integer with the number of badges of the GameCourseUser identified by user. If no argument is provided, the function returns the number of badges of the course.",
            'library'
        );
        //%badge.description
        $viewHandler->registerFunction(
            'badges',
            'description',
            function ($arg) {
                return $this->basicGetterFunction($arg, "description");
            },
            'string',
            "Returns a string with information regarding the name of the badge, the goal to obtain it and the reward associated to it."
        );
        //%badge.name
        $viewHandler->registerFunction(
            'badges',
            'name',
            function ($badge) {
                return $this->basicGetterFunction($badge, "name");
            },
            'string',
            "Returns a string with the name of the badge."
        );
        //%badge.maxLevel
        $viewHandler->registerFunction(
            'badges',
            'maxLevel',
            function ($badge) {
                return $this->basicGetterFunction($badge, "maxLevel");
            },
            'object',
            "Returns a Level object corresponding to the maximum Level from that badge."
        );
        //%badge.isExtra
        $viewHandler->registerFunction(
            'badges',
            'isExtra',
            function ($badge) {
                return $this->basicGetterFunction($badge, "isExtra");
            },
            'boolean',
            "Returns a boolean regarding whether the badge provides reward."
        );
        //%badge.isCount
        $viewHandler->registerFunction('badges', 'isCount', function ($badge) {
            return $this->basicGetterFunction($badge, "isCount");
        }, 'boolean');
        //%badge.isPost
        $viewHandler->registerFunction('badges', 'isPost', function ($badge) {
            return $this->basicGetterFunction($badge, "isPost");
        }, 'boolean');
        //%badge.isBragging
        $viewHandler->registerFunction(
            'badges',
            'isBragging',
            function ($badge) {
                return $this->basicGetterFunction($badge, "isBragging");
            },
            'boolean',
            "Returns a boolean regarding whether the badge provides no reward."
        );
        //%badge.renderPicture(number) return expression for the image of the badge in the specified level
        $viewHandler->registerFunction('badges', 'renderPicture', function ($badge, $level) {
            //$level num or object
            if (is_array($level))
                $levelNum = $level["number"];
            else
                $levelNum = $level;
            $name = str_replace(' ', '', $badge["value"]["name"]);
            return new ValueNode("badges/" . $name . "-" . $levelNum . ".png");
        }, 'picture', 'Return a picture of a badge’s level.');
        //%badge.levels returns collection of level objects
        $viewHandler->registerFunction('badges', 'levels', function ($badge) {
            $this->checkArray($badge, "object", 'levels');
            return $this->getLevel(null, $badge);
        }, 'collection', 'Returns a collection of Level objects from that badge.');
        //%badge.getLevel(number) returns level object
        $viewHandler->registerFunction('badges', 'getLevel', function ($badge, $level) {
            $this->checkArray($badge, "object", 'getLevel');
            $this->checkArray($level, "object", 'getLevel');
            return $this->getLevel($level, $badge);
        }, 'object', 'Returns a Level object corresponding to Level number from that badge.');
        //%badge.currLevel(%user) returns object of the current level of user
        $viewHandler->registerFunction('badges', 'currLevel', function ($badge, $user) {
            $this->checkArray($badge, "object", 'currLevel');
            $levelNum = $this->getLevelNum($badge, $user);
            return $this->getLevel($levelNum, $badge);
        }, 'object', 'Returns a Level object corresponding to the current Level of a GameCourseUser identified by user from that badge.');
        //%badge.nextLevel(user) %level.nextLevel  returns level object
        $viewHandler->registerFunction('badges', 'nextLevel', function ($arg, $user = null) {
            $this->checkArray($arg, "object", 'nextLevel');
            if ($user === null) { //arg is a level
                $levelNum = $arg["value"]["number"];
            } else { //arg is badge
                $levelNum = $this->getLevelNum($arg, $user);
            }
            return $this->getLevel($levelNum + 1, $arg);
        }, 'object', 'Returns a Level object corresponding to the next Level of a GameCourseUser identified by user from that badge.');
        //%badge.previousLevel(user) %level.previousLevel  returns level object
        $viewHandler->registerFunction('badges', 'previousLevel', function ($arg, $user = null) {
            $this->checkArray($arg, "object", 'previousLevel');
            if ($user === null) { //arg is a level
                $levelNum = $arg["value"]["number"];
            } else { //arg is badge
                $levelNum = $this->getLevelNum($arg, $user);
            }
            return $this->getLevel($levelNum - 1, $arg);
        }, 'object', 'Returns a Level object corresponding to the previous Level of a GameCourseUser identified by user from that badge.');
        //%level.goal
        $viewHandler->registerFunction('badges', 'goal', function ($level) {
            return $this->basicGetterFunction($level, "goal");
        }, 'string', 'Returns a string with the goal of the Level.');
        //%level.reward
        $viewHandler->registerFunction('badges', 'reward', function ($level) {
            return $this->basicGetterFunction($level, "reward");
        }, 'string', 'Returns a string with the reward of the Level.');
        //%level.number
        $viewHandler->registerFunction('badges', 'number', function ($level) {
            return $this->basicGetterFunction($level, "number");
        }, 'string', 'Returns a string with the number of the Level.');



        /*$badgeCache = array();
        $viewHandler->registerFunction("badges",'userBadgesCache', function() use (&$badgeCache) {
            $course = $this->getParent();
            $courseId=$course->getId();
            
            //if updates become very regular maybe cacheId could just use de day of the update
            $updated = Core::$systemDB->select("course",["id"=>$courseId],"lastUpdate");
            $updated = strtotime($updated);
            $cacheId = "badges" . $courseId . '-' . $updated;
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);  
            if ($hasCache) {
                $badgeCache = $cacheValue;
                return new Modules\Views\Expression\ValueNode('');
            }

            $students = $course->getUsersWithRole('Student');
            $studentsBadges = array();
            $studentsUsernames = array();
            
            $studentsById= array_combine(array_column($students, "id"), $students);
            foreach ($students as $student) {
                $studentsUsernames[$student['id']] = $student['username'];
                $studentsNames[$student['id']] = $student['name'];
                $studentsCampus[$student['id']] = $student["campus"];
            }
            
            $badges = Core::$systemDB->selectMultiple("badge",["course"=>$courseId]);
            $badgeCache = array();
            $badgeCacheClean = array();
            foreach ($badges as $badge) {
                $badgeCache[$badge['name']] = array();
                $badgeCacheClean[$badge['name']] = array();
                $badgeProgressCount = array();
                $badgeLevel = array();
                $badgeStudents = Core::$systemDB->selectMultiple("user_badge",
                                                    ["course"=>$courseId,"name"=>$badge['name']]);
                for ($i = 0; $i < $badge['maxLvl']; ++$i) {
                    $badgeCache[$badge['name']][$i] = array();
                    $badgeCacheClean[$badge['name']][$i] = array();
                    foreach ($badgeStudents as $studentBadge) {
                        $id = $studentBadge['student'];
            
                        if (!array_key_exists($id, $badgeLevel)) // cache
                            $badgeLevel[$id] = $studentBadge['level'];
                        
                        if (!array_key_exists($id, $badgeProgressCount)) // cache
                            $badgeProgressCount[$id] = $studentBadge['progress'];

                        if ($badgeLevel[$id] > $i) {
                            $timestamp = strtotime(Core::$systemDB->select("badge_level_time",
                                    ["badgeName"=>$badge['name'], "student"=> $id, "course"=>$courseId, "badgeLevel"=>$i+1],"badgeLvlTime"));
                            $badgeCache[$badge['name']][$i][] = array(
                                'id' => $id,
                                'name' => $studentsNames[$id],
                                'campus' => $studentsCampus[$id],
                                'username' => $studentsUsernames[$id],
                                'progress' => $badgeProgressCount[$id],
                                'timestamp' => $timestamp,
                                'when' => date('d-M-Y', $timestamp)
                            );
                        }
                    }
                    usort($badgeCache[$badge['name']][$i], function ($v1, $v2) {
                        return $v1['timestamp'] - $v2['timestamp'];
                    });
                    $badgeCacheClean[$badge['name']][$i] = $badgeCache[$badge['name']][$i];
                }
            }
            CacheSystem::store($cacheId, $badgeCacheClean);
            return new Modules\Views\Expression\ValueNode('');
        });

        $viewHandler->registerFunction('userBadgesCacheGet', function($badgeName, $badgeLevel) use (&$badgeCache) {
            return new \Modules\Views\Expression\ValueNode($badgeCache[$badgeName][$badgeLevel]);
        });

        $viewHandler->registerFunction('userBadgesCacheDoesntHave', function($badgeName, $badgeLevel) use (&$badgeCache) {
            return new \Modules\Views\Expression\ValueNode(count($badgeCache[$badgeName][$badgeLevel]) == 0);
        });

        $viewHandler->registerFunction('indicator', function($indicator) {
            return new Modules\Views\Expression\ValueNode($indicator['indicatorText'] . ((!array_key_exists('quality', $indicator) || $indicator['quality'] == 0)? ' ' : ' (' . $indicator['quality'] . ')'));
        });
        */

    
    
        //add API request to list of requests
        //update list of badges for course, from the badges configuration page
        // API::registerFunction('settings', 'courseBadges', function() {
        //     API::requireCourseAdminPermission();
        //     $courseId=API::getValue('course');
        //     $folder = Course::getCourseLegacyFolder($courseId);// Course::getCourseLegacyFolder($courseId);
        //     $badges = Core::$systemDB->selectMultiple("badge",["course"=>$courseId],"*", "name");
            
        //     //set maxreward
        //     if (API::hasKey('maxReward')){
        //         $max=API::getValue('maxReward');
        //         Core::$systemDB->update("badges_config",["maxBonusReward"=>$max],["course"=>$courseId]);
        //         API::response(["updatedData"=>["Max Reward set to ".$max] ] );
        //         return;
        //     }
        //     //set badges
        //     if (API::hasKey('badgesList')) {
        //         $keys = ['name', 'description', 'desc1', 'desc2', 'desc3', 'xp1', 'xp2', 'xp3', 
        //             'countBased', 'postBased', 'pointBased','count1', 'count2', 'count3'];
        //         $achievements = preg_split('/[\r]?\n/', API::getValue('badgesList'), -1, PREG_SPLIT_NO_EMPTY);
                
        //         $badgesToDelete = array_column($badges,'name');
        //         $badgesInDB = array_combine($badgesToDelete,$badges);
        //         $totalLevels = 0;
        //         $updatedData=[];

        //         foreach($achievements as &$achievement) {
        //             $splitInfo =preg_split('/;/', $achievement);
        //             if (sizeOf($splitInfo) != sizeOf($keys)) {
        //                 echo "Badges information was incorrectly formatted";
        //                 return null;
        //             }
        //             $achievement = array_combine($keys, $splitInfo);
        //             $maxLevel= empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);
        //             //if badge doesn't exit, add it to DB
        //             $badgeData = ["maxLevel"=>$maxLevel,"name"=>$achievement['name'],
        //                         "course"=>$courseId,"description"=>$achievement['description'],
        //                         "isExtra"=> ($achievement['xp1'] < 0),
        //                         "isBragging"=>($achievement['xp1'] == 0),
        //                         "isCount"=>($achievement['countBased'] == 'True'),
        //                         "isPost"=>($achievement['postBased'] == 'True'),
        //                         "isPoint"=>($achievement['pointBased'] == 'True')];
        //             if (!array_key_exists($achievement['name'],$badgesInDB)){
        //             //if (empty(Core::$systemDB->select("badge",["name"=>$achievement['name'],"course"=>$courseId]))){
        //                 Core::$systemDB->insert("badge",$badgeData);
        //                 $badgeId=Core::$systemDB->getLastId();
        //                 for ($i=1;$i<=$maxLevel;$i++){
        //                     Core::$systemDB->insert("level",["number"=>$i,"course"=>$courseId,
        //                                             "description"=>$achievement['desc'.$i],
        //                                             "goal"=>$achievement['count'.$i]]);
        //                     $levelId=Core::$systemDB->getLastId();
        //                     Core::$systemDB->insert("badge_has_level",["badgeId"=>$badgeId,"levelId"=>$levelId,
        //                                             "reward"=>abs($achievement['xp'.$i])]);
        //                 }  
        //                 $updatedData[]= "New badge: ".$achievement["name"];
        //             }else{
        //                 Core::$systemDB->update("badge",$badgeData,["course"=>$courseId,"name"=>$achievement["name"]]);
        //                 $badge = $badgesInDB[$achievement['name']];
        //                 for ($i=1;$i<=$badge["maxLevel"];$i++){
        //                     $badgeLevel = Core::$systemDB->select("badge_has_level join level on id=levelId",
        //                             ["number"=>$i,"course"=>$courseId, "badgeId"=>$badge['id']]);
                            
        //                     Core::$systemDB->update("level",["description"=>$achievement['desc'.$i],
        //                                             "goal"=>$achievement['count'.$i]],["id"=>$badgeLevel['id']]);
                            
        //                     Core::$systemDB->update("badge_has_level",["reward"=>abs($achievement['xp'.$i])],
        //                             ["levelId"=>$badgeLevel['id'],"badgeId"=>$badge['id']]);
        //                 }
        //                 //ToDo: consider cases where maxLevel changes -> fixed on new version of code
        //                 unset($badgesToDelete[array_search($achievement['name'], $badgesToDelete)]);
        //             }
        //             $totalLevels += $maxLevel; 
        //         }
        //         foreach ($badgesToDelete as $badgeToDelete){
        //             $badge = $badgesInDB[$badgeToDelete];
        //             $badgeLevels = Core::$systemDB->selectMultiple("badge_has_level join level on id=levelId",
        //                             ["course"=>$courseId, "badgeId"=>$badge['id']],"id");
        //             foreach($badgeLevels as $level){
        //                 Core::$systemDB->delete("level",["id"=>$level['id']]);
        //             }
        //             Core::$systemDB->delete("badge",["id"=>$badge['id']]);
        //             $updatedData[]= "Deleted badge: ".$badgeToDelete;
        //         }
        //         //Core::$systemDB->update("course",["numBadges"=>$totalLevels],["id"=>$courseId]);
                
        //         file_put_contents($folder . '/achievements.txt',API::getValue('badgesList'));
        //         API::response(["updatedData"=>$updatedData ]);
        //         return;
        //     }
            
        //     foreach($badges as &$badge){
        //         //$levels = Core::$systemDB->selectMultiple("badge_level",["course"=>$courseId, "badgeName"=>$badge["name"]],"*","level");
        //         $levels = Core::$systemDB->selectMultiple("badge_has_level join level on id=levelId",
        //                             ["course"=>$courseId, "badgeId"=>$badge['id']]);

        //         foreach ($levels as $level){
        //             $badge["levels"][]=$level;
        //         }
        //     }
            
        //     $file = @file_get_contents($folder . '/achievements.txt');
        //     if ($file===FALSE){$file="";}
        //     API::response(array('badgesList' => $badges, "file"=>$file, "maxReward"=>Core::$systemDB->select("badges_config",["course"=>$courseId],"maxBonusReward")));
        // });
    
        if (!$viewsModule->templateExists(self::BADGES_TEMPLATE_NAME))
            $viewsModule->setTemplate(self::BADGES_TEMPLATE_NAME, file_get_contents(__DIR__ . '/badges.txt'),$this->getId());   
    }

    public function saveMaxReward($max, $courseId){
        Core::$systemDB->update("badges_config",["maxBonusReward"=>$max],["course"=>$courseId]);
    }
    public function getMaxReward( $courseId){
        return Core::$systemDB->select("badges_config",["course"=>$courseId], "maxBonusReward");
    }

    public function newBadge($achievement, $courseId){
        $maxLevel= empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);
        $badgeData = ["maxLevel"=>$maxLevel,"name"=>$achievement['name'],
                    "course"=>$courseId,"description"=>$achievement['description'],
                    "isExtra"=> ($achievement['xp1'] < 0) ? 1 : 0,
                    "isBragging"=>($achievement['xp1'] == 0) ? 1 : 0,
                    "isCount"=>($achievement['countBased']) ? 1 : 0,
                    "isPost"=>($achievement['postBased']) ? 1 : 0,
                    "isPoint"=>($achievement['pointBased']) ? 1 : 0];
        Core::$systemDB->insert("badge",$badgeData);
        $badgeId=Core::$systemDB->getLastId();
        for ($i=1;$i<=$maxLevel;$i++){
            Core::$systemDB->insert("level",["number"=>$i,"course"=>$courseId,
                                    "description"=>$achievement['desc'.$i],
                                    "goal"=>$achievement['count'.$i]]);
            $levelId=Core::$systemDB->getLastId();
            Core::$systemDB->insert("badge_has_level",["badgeId"=>$badgeId,"levelId"=>$levelId,
                                    "reward"=>abs($achievement['xp'.$i])]);
        } 
    }
    public function editBadge($achievement, $courseId){
        $originalBadge = Core::$systemDB->selectMultiple("badge",["course"=>$courseId, 'id'=>$achievement['id']],"*", "name")[0];

        $maxLevel= empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);
        $badgeData = ["maxLevel"=>$maxLevel,"name"=>$achievement['name'],
                    "course"=>$courseId,"description"=>$achievement['description'],
                    "isExtra"=> ($achievement['xp1'] < 0) ? 1 : 0,
                    "isBragging"=>($achievement['xp1'] == 0) ? 1 : 0,
                    "isCount"=>($achievement['countBased']) ? 1 : 0,
                    "isPost"=>($achievement['postBased']) ? 1 : 0,
                    "isPoint"=>($achievement['pointBased']) ? 1 : 0];
        Core::$systemDB->update("badge",$badgeData,["id"=>$achievement["id"]]);


        if($originalBadge["maxLevel"] <= $maxLevel){
            for ($i=1;$i<=$maxLevel;$i++){
                $badgeLevel = Core::$systemDB->select("badge_has_level join level on id=levelId",
                        ["number"=>$i,"course"=>$courseId, "badgeId"=>$achievement['id']]);
                if($i > $originalBadge["maxLevel"]){
                    //if they are new levels they need to be inserted and not updated
                    Core::$systemDB->insert("level",["number"=>$i,"course"=>$courseId,
                                    "description"=>$achievement['desc'.$i],
                                    "goal"=>$achievement['count'.$i]]);
                    $levelId=Core::$systemDB->getLastId();
                    Core::$systemDB->insert("badge_has_level",["badgeId"=>$achievement['id'],"levelId"=>$levelId,
                                            "reward"=>abs($achievement['xp'.$i])]);
                }
                else{
                    Core::$systemDB->update("level",["description"=>$achievement['desc'.$i],
                                        "goal"=>$achievement['count'.$i]],["id"=>$badgeLevel['id']]);
                    Core::$systemDB->update("badge_has_level",["reward"=>abs($achievement['xp'.$i])],
                            ["levelId"=>$badgeLevel['id'],"badgeId"=>$achievement['id']]);
                }
                
            }
        }
        else{
            //deletes original badge levels
            $originalbadgeLevels = Core::$systemDB->selectMultiple("badge_has_level join level on id=levelId",
                                    ["course"=>$courseId, "badgeId"=>$originalBadge['id']],"id");
            foreach($originalbadgeLevels as $level){
                Core::$systemDB->delete("level",["id"=>$level['id']]);
            }
            //creates new ones
            for ($i=1;$i<=$maxLevel;$i++){
                Core::$systemDB->insert("level",["number"=>$i,"course"=>$courseId,
                                        "description"=>$achievement['desc'.$i],
                                        "goal"=>$achievement['count'.$i]]);
                $levelId=Core::$systemDB->getLastId();
                Core::$systemDB->insert("badge_has_level",["badgeId"=>$achievement["id"],"levelId"=>$levelId,
                                        "reward"=>abs($achievement['xp'.$i])]);
            } 
        }        
    }
    public function deleteBadge($badge, $courseId){
        $badgeLevels = Core::$systemDB->selectMultiple("badge_has_level join level on id=levelId",
                                    ["course"=>$courseId, "badgeId"=>$badge['id']],"id");
        foreach($badgeLevels as $level){
            Core::$systemDB->delete("level",["id"=>$level['id']]);
        }
        Core::$systemDB->delete("badge",["id"=>$badge['id']]);
    }
    public function getBadges( $courseId){
        $badges = Core::$systemDB->selectMultiple("badge",["course"=>$courseId],"*", "name");
        foreach($badges as &$badge){
            //information to match needing fields
            $badge['countBased'] = $badge["isCount"];
            $badge['postBased'] = $badge["isPost"];
            $badge['pointBased'] = $badge["isPoint"];

            $levels = Core::$systemDB->selectMultiple("badge_has_level join level on id=levelId",
                                ["course"=>$courseId, "badgeId"=>$badge['id']]);
            foreach ($levels as $level){
                $badge['desc'.$level['number']] = $level['description']; //string
                $badge['count'.$level['number']] = intval($level['goal']); //int
                $badge['xp'.$level['number']] = intval($level['reward']); //int
            }
        }
        return $badges;
    }


    public function is_configurable(){
        return true;
    }

    
    public function has_general_inputs (){ return true; }
    public function get_general_inputs ($courseId){
        // $input1 = array('name' => "input 1", 'id'=> 'input1', 'type' => "text", 'options' => "", 'current_val' => "cenas");
        // $input2 = array('name' => "input 2", 'id'=> 'input2', 'type' => "date", 'options' => "", 'current_val' => "");
        // $input3 = array('name' => "input 3", 'id'=> 'input3', 'type' => "on_off button", 'options' => '', 'current_val' => true);
        // $input4 = array('name' => "input 4", 'id'=> 'input4', 'type' => "select", 'options' => ["OpA","OpB","OpC"], 'current_val' => "");
        // $input5 = array('name' => "input 5", 'id'=> 'input5', 'type' => "color", 'options' => "", 'current_val' => "#121212");
        // $input7 = array('name' => "input 7", 'id'=> 'input7', 'type' => "number", 'options' => "", 'current_val' => "");
        // $input8 = array('name' => "input 8", 'id'=> 'input8', 'type' => "paragraph", 'options' => "", 'current_val' => "my text here");
        // return [$input1, $input2, $input3, $input4, $input5, $input7, $input8];

        $input = array('name' => "Max Reward", 'id'=> 'maxReward', 'type' => "number", 'options' => "", 'current_val' => intval($this->getMaxReward($courseId)));
        return [$input];
        

    }
    public function save_general_inputs($generalInputs,$courseId){
        $maxVal = $generalInputs["maxReward"];
        $this->saveMaxReward($maxVal, $courseId);
    }



    public function has_listing_items (){ return  true; }
    public function get_listing_items ($courseId){
        //tenho de dar header
        $header = ['Name', 'Description', '# Levels', 'Level 1', 'XP Level 1', 'Is Count','Is Post', 'Is Point', 'Is Extra'] ;
        $displayAtributes = ['name', 'description', 'maxLevel', 'desc1','xp1',  'isCount', 'isPost', 'isPoint', 'isExtra'];
        // items (pela mesma ordem do header)
        $items = $this->getBadges($courseId);
        //argumentos para add/edit
        $allAtributes = [
            array('name' => "Name", 'id'=> 'name', 'type' => "text", 'options' => ""),
            array('name' => "Description", 'id'=> 'description', 'type' => "text", 'options' => ""),
            array('name' => "Level 1", 'id'=> 'desc1', 'type' => "text", 'options' => ""),
            array('name' => "XP1", 'id'=> 'xp1', 'type' => "number", 'options' => ""),
            array('name' => "Level 2", 'id'=> 'desc2', 'type' => "text", 'options' => ""),  
            array('name' => "XP2", 'id'=> 'xp2', 'type' => "number", 'options' => ""),
            array('name' => "Level 3", 'id'=> 'desc3', 'type' => "text", 'options' => ""), 
            array('name' => "XP3", 'id'=> 'xp3', 'type' => "number", 'options' => ""),             
            array('name' => "Is Count", 'id'=> 'countBased', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Post", 'id'=> 'postBased', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Point", 'id'=> 'pointBased', 'type' => "on_off button", 'options' => ""), 
            array('name' => "Count 1", 'id'=> 'count1', 'type' => "number", 'options' => ""),
            array('name' => "Count 2", 'id'=> 'count2', 'type' => "number", 'options' => ""),
            array('name' => "count 3", 'id'=> 'count3', 'type' => "number", 'options' => ""),
        ];
        return array( 'listName'=> 'Badges', 'itemName'=> 'Badge','header' => $header, 'displayAtributes'=> $displayAtributes, 'items'=> $items, 'allAtributes'=>$allAtributes);
    }
    public function save_listing_item ($actiontype, $listingItem, $courseId){
        if($actiontype == 'new'){
            $this->newBadge($listingItem, $courseId);
        }
        elseif ($actiontype == 'edit'){
            $this->editBadge($listingItem, $courseId);

        }elseif($actiontype == 'delete'){
            $this->deleteBadge($listingItem, $courseId);
        }
    }
}

ModuleLoader::registerModule(array(
    'id' => 'badges',
    'name' => 'Badges',
    'description' => 'Enables Badges with 3 levels and xp points that ca be atributed to a student in certain conditions.',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Badges();
    }
));
