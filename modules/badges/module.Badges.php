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

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/badges.css');
    }

    private function setupData($courseId)
    {
        $folder = Course::getCourseDataFolder($courseId, Course::getCourse($courseId, false)->getName());
        if (!file_exists($folder . "/badges"))
            mkdir($folder . "/badges");
        if (!file_exists($folder . "/badges" . "/Extra"))
            mkdir($folder . "/badges" . "/Extra");
        if (!file_exists($folder . "/badges" . "/Bragging"))   
            mkdir($folder . "/badges" . "/Bragging");
        if (!file_exists($folder . "/badges" . "/Level2"))
            mkdir($folder . "/badges" . "/Level2");
        if (!file_exists($folder . "/badges" . "/Level3"))
            mkdir($folder . "/badges" . "/Level3");
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
            $table = "badge join badge_level on badge.id=badgeId";
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
    public function deleteLevels($courseId)
    {
        if(Core::$systemDB->tableExists("badge_level")){
            /*$levels = Core::$systemDB->selectMultiple(
                "badge_level left join badge on badgeId=badge.id",
                ["course" => $courseId],
                'badge_level.id'
            );
            foreach ($levels as $lvl) {
                Core::$systemDB->delete("badge_level", ["id" => $lvl["id"]]);
            }*/
            $badges = Core::$systemDB->selectMultiple("badge", ["course" => $courseId], 'id');
            foreach ($badges as $badge) {
                Core::$systemDB->delete("badge_level", ["badgeId" => $badge["id"]]);
            }
        }
    }
    public function dropTables($moduleName)
    {
        parent::dropTables($moduleName);
    }
    public function deleteDataRows($courseId)
    {
        $this->deleteLevels($courseId);
        Core::$systemDB->delete("badge", ["course" => $courseId]);
    }
    public function getBadgeCount($user = null)
    {
        $courseId = $this->getCourseId();
        if ($user === null) {
            $count = Core::$systemDB->select("badge", ["course" => $courseId, "isActive" => true], "sum(maxLevel)");
            if (is_null($count))
                return 0;
            else
                return $count;
        }
        $id = $this->getUserId($user);
        return  Core::$systemDB->select("award", ["course" => $courseId, "type" => "badge", "user" => $id], "count(*)");
    }
    public function getUsersWithBadge($badge, $level, $active=false) {
        $usersWithBadge = array();
        $courseId = $this->getCourseId();
        $course = new Course($courseId);
        $users = $course->getUsers($active);
        foreach($users as $user){
            $userId = $user["id"];
            $userObj = $this->createNode($course->getUser($userId)->getAllData(), 'users');
            //print_r($userObj);
            $userLevel = $this->getLevelNum($badge, $userId);
            $levelNum = $this->basicGetterFunction($level, "number")->getValue();
            if ($userLevel >= $levelNum) {
                array_push($usersWithBadge, $userObj->getValue()["value"]);
            }
        }

        return $this->createNode($usersWithBadge, 'users', "collection");
    }
    public function moduleConfigJson($courseId){
        $badgesConfigArray = array();
        $badgesArray = array();
        $badgesLevelArray = array();

        $badgesArr = array();
        if(Core::$systemDB->tableExists("badges_config")){
            $badgesConfigVarDB = Core::$systemDB->select("badges_config", ["course" => $courseId], "*");
            if($badgesConfigVarDB){
                unset($badgesConfigVarDB["course"]);
                array_push($badgesConfigArray, $badgesConfigVarDB);
            }
        }
        if (Core::$systemDB->tableExists("badge")) {
            $badgesVarDB = Core::$systemDB->selectMultiple("badge", ["course" => $courseId], "*");
            if ($badgesVarDB) {
                unset($badgesConfigVarDB["course"]);
                foreach ($badgesVarDB as $badge) {
                    array_push($badgesArray, $badge);

                    $badgesLevelVarDB_ = Core::$systemDB->selectMultiple("badge_level", ["badgeId" => $badge["id"]], "*");
                    foreach ($badgesLevelVarDB_ as $badgesLevelVarDB) {
                        array_push($badgesLevelArray, $badgesLevelVarDB);
                    }
                }
            }
        }

        $badgesArr["badges_config"] = $badgesConfigArray;
        $badgesArr["badge"] = $badgesArray;
        $badgesArr["badge_level"] = $badgesLevelArray;

        if ( $badgesConfigArray || $badgesArray || $badgesLevelArray) {
            return $badgesArr;
        } else {
            return false;
        }
    }

    public function readConfigJson($courseId, $tables, $update = false){
        $tableName = array_keys($tables);
        $i = 0;
        $badgeIds = array();
        $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
        foreach ($tables as $table) {
            foreach ($table as $entry) {
                if($tableName[$i] == "badges_config"){
                    if($update && $existingCourse){
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    }else{
                        $entry["course"] = $courseId;
                        Core::$systemDB->insert($tableName[$i], $entry);
                    }
                } else  if ($tableName[$i] == "badge") {
                    $importId = $entry["id"];
                    unset($entry["id"]);
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    } else {
                        $entry["course"] = $courseId;
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                    }
                    $badgeIds[$importId] = $newId;
                } else  if ($tableName[$i] == "badge_level") {
                    $oldBadgeId = $badgeIds[$entry["badgeId"]];
                    $entry["badgeId"] = $oldBadgeId;
                    unset($entry["id"]);
                    if ($update) {
                        Core::$systemDB->update($tableName[$i], $entry ,["badgeId" => $oldBadgeId, "number" => $entry["number"]]);
                    } else {
                         Core::$systemDB->insert($tableName[$i], $entry);
                    };
                }
            }
            $i++;
        }
        return $badgeIds;
    }

    public function init()
    {
        if ($this->addTables("badges", "badge") || empty(Core::$systemDB->select("badges_config", ["course" => $this->getCourseId()]))) {
            Core::$systemDB->insert("badges_config", ["maxBonusReward" => MAX_BONUS_BADGES, "course" => $this->getCourseId()]);
        }
        $courseId = $this->getParent()->getId();
        $this->setupData($courseId);
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();

        $viewHandler->registerLibrary("badges", "badges", "This library provides information regarding Badges and their levels. It is provided by the badges module.");

        //badges.getAllBadges(isExtra,isBragging,isActive)
        $viewHandler->registerFunction(
            'badges',
            'getAllBadges',
            function (bool $isExtra = null, bool $isBragging = null, bool $isActive = true) {
                $where = [];
                if ($isExtra !== null)
                    $where["isExtra"] = $isExtra;
                if ($isBragging !== null)
                    $where["isBragging"] = $isBragging;
                    $where["isActive"] = $isActive;
                return $this->getBadge($isActive, $where);
            },
            "Returns a collection with all the badges in the Course. The optional parameters can be used to find badges that specify a given combination of conditions:\nisExtra: Badge has a reward.\nisBragging: Badge has no reward.\nisActive: Badge is active.",
            'collection',
            'badge',
            'library',
            null

        );
        //badges.getBadge(name)
        $viewHandler->registerFunction(
            'badges',
            'getBadge',
            function (string $name = null) {
                return $this->getBadge(false, ["name" => $name]);
   
            },
            "Returns the badge object with the specific name.",
            'object',
            'badge',
            'library',
            null
        );
        //badges.getBadgesCount(user) returns num of badges of user (if specified) or of course 
        $viewHandler->registerFunction(
            'badges',
            'getBadgesCount',
            function ($user = null) {
                return new ValueNode($this->getBadgeCount($user));
            },
            "Returns an integer with the number of badges of the GameCourseUser identified by user. If no argument is provided, the function returns the number of badges of the course.",
            'integer',
            null,
            'library',
            null
        );
        //badges.doesntHaveBadge(%badge, %level, %active) returns True if there are no students with this badge, False otherwise
        $viewHandler->registerFunction(
            'badges',
            'doesntHaveBadge',
            function ($badge, $level, $active = true) {
                $users = $this->getUsersWithBadge($badge, $level, $active);
                return new ValueNode(empty($users->getValue()['value']));
            },
            "Returns an object with value True if there are no students with this badge, False otherwise.\nactive: if True only returns data regarding active students. When False, returns data reagrding all students. Defaults to True.",
            'object',
            'badge',
            'library',
            null
        );
        //users.getUsersWithBadge(%badge, %level, %active) returns an object with all users that earned that badge on that level
        $viewHandler->registerFunction(
            'users',
            'getUsersWithBadge',
            function ($badge, $level, $active = true) {
                $users = $this->getUsersWithBadge($badge, $level, $active);
                return $users;
            },
            "Returns an object with all users that earned that badge on that level.\nactive: If set to True, returns information regarding active students only. Otherwise, returns information regarding all students. Defaults to True.",
            'collection',
            'user',
            'library',
            null
        );
        //%badge.description
        $viewHandler->registerFunction(
            'badges',
            'description',
            function ($arg) {
                return $this->basicGetterFunction($arg, "description");
            },
            "Returns a string with information regarding the name of the badge, the goal to obtain it and the reward associated to it.",
            'string',
            null,
            'object',
            'badge'
        );
        //%badge.name
        $viewHandler->registerFunction(
            'badges',
            'name',
            function ($badge) {
                return $this->basicGetterFunction($badge, "name");
            },
            "Returns a string with the name of the badge.",
            'string',
            null,
            'object',
            'badge'
        );
        //%badge.maxLevel
        $viewHandler->registerFunction(
            'badges',
            'maxLevel',
            function ($badge) {
                return $this->basicGetterFunction($badge, "maxLevel");
            },
            "Returns a Level object corresponding to the maximum Level from that badge.",
            'object',
            'level',
            'object',
            'badge'
        );
        //%badge.isExtra
        $viewHandler->registerFunction(
            'badges',
            'isExtra',
            function ($badge) {
                return $this->basicGetterFunction($badge, "isExtra");
            },
            "Returns a boolean regarding whether the badge provides reward.",
            'boolean',
            null,
            'object',
            'badge'
        );
        //%badge.isCount
        $viewHandler->registerFunction(
            'badges',
            'isCount',
            function ($badge) {
                return $this->basicGetterFunction($badge, "isCount");
            },
            '',
            'boolean',
            null,
            'object',
            'badge'
        );
        //%badge.isPost
        $viewHandler->registerFunction(
            'badges',
            'isPost',
            function ($badge) {
                return $this->basicGetterFunction($badge, "isPost");
            },
            '',
            'boolean',
            null,
            'object',
            'badge'
        );
        //%badge.isBragging
        $viewHandler->registerFunction(
            'badges',
            'isBragging',
            function ($badge) {
                return $this->basicGetterFunction($badge, "isBragging");
            },
            "Returns a boolean regarding whether the badge provides no reward.",
            'boolean',
            null,
            'object',
            'badge'
        );
        //%badge.isActive
        $viewHandler->registerFunction(
            'badges',
            'isActive',
            function ($badge) {
                return $this->basicGetterFunction($badge, "isActive");
            },
            "Returns a boolean regarding whether the badge is active.",
            'boolean',
            null,
            'object',
            'badge'
        );
        //%badge.renderPicture(number) return expression for the image of the badge in the specified level
        $viewHandler->registerFunction(
            'badges',
            'renderPicture',
            function ($badge, $level) {
                //$level num or object
                if (is_array($level))
                    $levelNum = $level["number"];
                else
                    $levelNum = $level;
                $name = str_replace(' ', '', $badge["value"]["name"]);
                return new ValueNode("badges/" . $name . "-" . $levelNum . ".png");
            },
            'Return a picture of a badge’s level.',
            'picture',
            null,
            'object',
            'badge'
        );
        //%badge.levels returns collection of level objects
        $viewHandler->registerFunction(
            'badges',
            'levels',
            function ($badge) {
                $this->checkArray($badge, "object", 'levels');
                return $this->getLevel(null, $badge);
            },
            'Returns a collection of Level objects from that badge.',
            'collection',
            'level',
            'object',
            'badge'
        );
        //%badge.getLevel(number) returns level object
        $viewHandler->registerFunction(
            'badges',
            'getLevel',
            function ($badge, $level) {
                $this->checkArray($badge, "object", 'getLevel');
                $this->checkArray($level, "object", 'getLevel');
                return $this->getLevel($level, $badge);
            },
            'Returns a Level object corresponding to Level number from that badge.',
            'object',
            'level',
            'object',
            'badge'
        );
        //%badge.currLevel(%user) returns object of the current level of user
        $viewHandler->registerFunction(
            'badges',
            'currLevel',
            function ($badge, $user) {
                $this->checkArray($badge, "object", 'currLevel');
                $levelNum = $this->getLevelNum($badge, $user);
                return $this->getLevel($levelNum, $badge);
            },
            'Returns a Level object corresponding to the current Level of a GameCourseUser identified by user from that badge.',
            'object',
            'level',
            'object',
            'badge'
        );
        //%badge.nextLevel(user) %level.nextLevel  returns level object
        $viewHandler->registerFunction(
            'badges',
            'nextLevel',
            function ($arg, $user = null) {
                $this->checkArray($arg, "object", 'nextLevel');
                if ($user === null) { //arg is a level
                    $levelNum = $arg["value"]["number"];
                } else { //arg is badge
                    $levelNum = $this->getLevelNum($arg, $user);
                }
                return $this->getLevel($levelNum + 1, $arg);
            },
            'Returns a Level object corresponding to the next Level of a GameCourseUser identified by user from that badge.',
            'object',
            'level',
            'object',
            'badge'
        );
        //%badge.previousLevel(user) %level.previousLevel  returns level object
        $viewHandler->registerFunction(
            'badges',
            'previousLevel',
            function ($arg, $user = null) {
                $this->checkArray($arg, "object", 'previousLevel');
                if ($user === null) { //arg is a level
                    $levelNum = $arg["value"]["number"];
                } else { //arg is badge
                    $levelNum = $this->getLevelNum($arg, $user);
                }
                return $this->getLevel($levelNum - 1, $arg);
            },
            'Returns a Level object corresponding to the previous Level of a GameCourseUser identified by user from that badge.',
            'object',
            'level',
            'object',
            'badge'
        );

        //%badge.badgeProgression(user)
        $viewHandler->registerFunction(
            'badges',
            'badgeProgression',

            function ($badge, $user) {

                $badgeParticipation = $this->getBadgeProgression($badge, $user);
                return $this->createNode($badgeParticipation, 'badges', 'collection');

            },
            'Returns a collection object corresponding to the intermediate progress of a GameCourseUser identified by user for that badge.',
            'object',
            null,
            'object',
            'badge'
        );

        //%badgeProgression.post
        $viewHandler->registerFunction(
            'badges',
            'post',
            function ($badge) {
                return $this->basicGetterFunction($badge, "post");
            },
            'Returns a post from a collection of badge progression participations.',
            'string',
            null,
            'object',
            'badge'
        );

        //%badgeProgression.description
        $viewHandler->registerFunction(
            'badges',
            'description',
            function ($badge) {
                return $this->basicGetterFunction($badge, "description");
            },
            'Returns a post description from a collection of badge progression participations.',
            'string',
            null,
            'object',
            'badge'
        );	

        //%collection.countBadgesProgress  returns size of the collection or points obtained
        $viewHandler->registerFunction(
            'badges', 
            'countBadgesProgress', 
            function ($collection, $badge) {
                $count = $this->countBadgesProgress($collection, $badge);
                return new ValueNode($count);
        },   
        'Returns the number of elements (posts or points) in the collection.', 
        'integer', 
        null, 
        'collection'
    );


        //%level.goal
        $viewHandler->registerFunction(
            'badges',
            'goal',
            function ($level) {
                return $this->basicGetterFunction($level, "goal");
            },
            'Returns a string with the goal of the Level.',
            'string',
            null,
            'object',
            'level'
        );
        //%level.reward
        $viewHandler->registerFunction(
            'badges',
            'reward',
            function ($level) {
                return $this->basicGetterFunction($level, "reward");
            },
            'Returns a string with the reward of the Level.',
            'string',
            null,
            'object',
            'level'
        );
        //%level.number
        $viewHandler->registerFunction(
            'badges',
            'number',
            function ($level) {
                return $this->basicGetterFunction($level, "number");
            },
            'Returns a string with the number of the Level.',
            'string',
            null,
            'object',
            'level'
        );
        



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
        //     $folder = Course::getCourseDataFolder($courseId);// Course::getCourseDataFolder($courseId);
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

    public function saveGeneralImages($image, $value, $courseId){
        Core::$systemDB->update("badges_config",[$image=>$value],["course"=>$courseId]);
    }

    public function getGeneralImages($image, $courseId){
        $result = Core::$systemDB->select("badges_config",["course"=>$courseId], $image);
        if ($result == NULL)
            return "";
        return $result;
    }

    public function getBadgeProgression($badge, $user) {
        $badgePosts = Core::$systemDB->selectMultiple("badge_progression b left join badge on b.badgeId=badge.id left join participation on b.participationId=participation.id",["b.user" => $user, "badgeId" => $badge], "isPost, post, participation.description, participation.rating");

        
        for ($i = 0 ; $i < sizeof($badgePosts); $i++) {
            if ($badgePosts[$i]["isPost"]) {
                if (substr($badgePosts[$i]["post"],0,9) === "mod/quiz/") {
                    $badgePosts[$i]["post"] = "https://pcm.rnl.tecnico.ulisboa.pt/moodle/mod/resource/" . $badgePosts[$i]["post"];
                    $badgePosts[$i]["description"] = "(". strval($badgePosts[$i]["description"]) . ")";
                }
                else {
                    $badgePosts[$i]["post"] = "https://pcm.rnl.tecnico.ulisboa.pt/moodle/" . $badgePosts[$i]["post"];
                    $badgePosts[$i]["description"] = "(". strval($i + 1) . ")";
                }
            }
            else {
                if (substr($badgePosts[$i]["post"],0,8) === "view.php") {
                    $badgePosts[$i]["post"] = "https://pcm.rnl.tecnico.ulisboa.pt/moodle/mod/resource/" . $badgePosts[$i]["post"];
                    $badgePosts[$i]["description"] = "(". strval($badgePosts[$i]["description"]) . ")";
                }

                else if (empty($badgePosts[$i]["description"])) {
                    $badgePosts[$i]["description"] = "(". strval($i + 1) . ")";
                }

                else if (strlen($badgePosts[$i]["description"]) > 23) {
                    $badgePosts[$i]["post"] = $badgePosts[$i]["description"];
                    $badgePosts[$i]["description"] = "(". strval($i + 1) . ")";
                }

                else {
                    $desc = $badgePosts[$i]["description"];
                    $badgePosts[$i]["description"] = "(" . $desc . ")";
                }
            }
        }
        
        return $badgePosts;
    }

    public function countBadgesProgress($collection, $badge) {
        $badgeParams = Core::$systemDB->selectMultiple("badge",["id" => $badge], "isPost, isPoint, isCount");
        $count = 0;
        if (!empty($collection["value"])) {
            if ($badgeParams[0]["isPoint"]) {
                foreach ($collection["value"] as $line) {
                    $count += intval($line["rating"]); 
                }
            }
            else {
                $count = sizeof($collection["value"]);
            }
        }
        return $count;
    }

    
    public function newBadge($achievement, $courseId){
        $maxLevel= empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);
        $badgeData = ["maxLevel"=>$maxLevel,"name"=>$achievement['name'],
                    "course"=>$courseId,"description"=>$achievement['description'],
                    "isExtra"=> ($achievement['extra']) ? 1 : 0,
                    "isBragging"=>($achievement['xp1'] == 0) ? 1 : 0,
                    "isCount"=>($achievement['countBased']) ? 1 : 0,
                    "isPost"=>($achievement['postBased']) ? 1 : 0,
                    "isPoint"=>($achievement['pointBased']) ? 1 : 0];
        if (array_key_exists("image", $achievement)) {
            $badgeData["image"] = $achievement['image'];
        }
        Core::$systemDB->insert("badge",$badgeData);
        $badgeId=Core::$systemDB->getLastId();
        for ($i=1;$i<=$maxLevel;$i++){
            /*Core::$systemDB->insert("level",["number"=>$i,"course"=>$courseId,
                                    "description"=>$achievement['desc'.$i],
                                    "goal"=>$achievement['count'.$i]]);
            $levelId=Core::$systemDB->getLastId();
            Core::$systemDB->insert("badge_has_level",["badgeId"=>$badgeId,"levelId"=>$levelId,
                                    "reward"=>abs($achievement['xp'.$i])]);*/
            Core::$systemDB->insert("badge_level",[
                "badgeId"=>$badgeId,
                "number"=>$i,
                "goal"=>$achievement['count'.$i],
                "description"=>$achievement['desc'.$i],
                "reward"=>abs($achievement['xp'.$i])
            ]);
        } 
    }
    public function editBadge($achievement, $courseId){
        $originalBadge = Core::$systemDB->selectMultiple("badge",["course"=>$courseId, 'id'=>$achievement['id']],"*", "name")[0];

        $maxLevel= empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);
        $badgeData = ["maxLevel"=>$maxLevel,"name"=>$achievement['name'],
                    "course"=>$courseId,"description"=>$achievement['description'],
                    "image" => $achievement['image'],
                    "isExtra"=> ($achievement['extra']) ? 1 : 0,
                    "isBragging"=>($achievement['xp1'] == 0) ? 1 : 0,
                    "isCount"=>($achievement['countBased']) ? 1 : 0,
                    "isPost"=>($achievement['postBased']) ? 1 : 0,
                    "isPoint"=>($achievement['pointBased']) ? 1 : 0];
        Core::$systemDB->update("badge",$badgeData,["id"=>$achievement["id"]]);

        if($originalBadge["maxLevel"] <= $maxLevel){
            for ($i=1;$i<=$maxLevel;$i++){
                
                if($i > $originalBadge["maxLevel"]){
                    //if they are new levels they need to be inserted and not updated
                    /*Core::$systemDB->insert("level",["number"=>$i,"course"=>$courseId,
                                    "description"=>$achievement['desc'.$i],
                                    "goal"=>$achievement['count'.$i]]);
                    $levelId=Core::$systemDB->getLastId();
                    Core::$systemDB->insert("badge_has_level",["badgeId"=>$achievement['id'],"levelId"=>$levelId,
                                            "reward"=>abs($achievement['xp'.$i])]);*/
                    Core::$systemDB->insert("badge_level", [
                        "badgeId"=>$achievement['id'],
                        "number"=>$i,
                        "goal"=>$achievement['count'.$i],
                        "description"=>$achievement['desc'.$i],
                        "reward"=>abs($achievement['xp'.$i])
                    ]);
                }
                else{
                    Core::$systemDB->update("badge_level", [
                        "badgeId"=>$achievement['id'],
                        "number"=>$i,
                        "goal"=>$achievement['count'.$i],
                        "description"=>$achievement['desc'.$i],
                        "reward"=>abs($achievement['xp'.$i])
                    ], ["number"=>$i, "badgeId"=>$achievement['id']]);
                }
                
            }
        }
        else{
            //deletes original badge levels
            /*$originalbadgeLevels = Core::$systemDB->selectMultiple("badge_level join badge on badgeId=badge.id",
                                    ["course"=>$courseId, "badgeId"=>$originalBadge['id']], 'badge_level.id');
            foreach($originalbadgeLevels as $level){
                Core::$systemDB->delete("badge_level",["id"=>$level['id']]);}*/
            Core::$systemDB->delete("badge_level",["badgeId"=>$originalBadge['id']]);
            //creates new ones
            for ($i=1;$i<=$maxLevel;$i++){
                /*Core::$systemDB->insert("level",["number"=>$i,"course"=>$courseId,
                                        "description"=>$achievement['desc'.$i],
                                        "goal"=>$achievement['count'.$i]]);
                $levelId=Core::$systemDB->getLastId();
                Core::$systemDB->insert("badge_has_level",["badgeId"=>$achievement["id"],"levelId"=>$levelId,
                                        "reward"=>abs($achievement['xp'.$i])]);*/
                Core::$systemDB->insert("badge_level", [
                    "badgeId"=>$achievement['id'],
                    "number"=>$i,
                    "goal"=>$achievement['count'.$i],
                    "description"=>$achievement['desc'.$i],
                    "reward"=>abs($achievement['xp'.$i])
                    ]);
            } 
        }        
    }
    public function deleteBadge($badge, $courseId){
        /*$badgeLevels = Core::$systemDB->selectMultiple("badge_has_level join level on id=levelId",
                                    ["course"=>$courseId, "badgeId"=>$badge['id']],"id");
        foreach($badgeLevels as $level){
            Core::$systemDB->delete("level",["id"=>$level['id']]);
        }*/
        Core::$systemDB->delete("badge",["id"=>$badge['id']]);
    }
    public function getBadges($courseId){
        $badges = Core::$systemDB->selectMultiple("badge",["course"=>$courseId],"*", "name");
        foreach($badges as &$badge){
            //information to match needing fields
            $badge['countBased'] = boolval($badge["isCount"]);
            $badge['postBased'] = boolval($badge["isPost"]);
            $badge['pointBased'] = boolval($badge["isPoint"]);
            $badge['extra'] = boolval($badge["isExtra"]);
            $badge['isActive'] = boolval($badge["isActive"]);

            $levels = Core::$systemDB->selectMultiple("badge_level join badge on badge.id=badgeId",
                                ["course"=>$courseId, "badgeId"=>$badge['id']], 'badge_level.description , goal, reward, number' );
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

        $input = [
            array('name' => "Max Reward", 'id'=> 'maxReward', 'type' => "number", 'options' => "", 'current_val' => intval($this->getMaxReward($courseId))),
            array('name' => "Overlay for extra", 'id'=> 'extra', 'type' => "image", 'options' => "Extra", 'current_val' => $this->getGeneralImages('imageExtra', $courseId)),
            array('name' => "Overlay for bragging", 'id'=> 'bragging', 'type' => "image", 'options' => "Bragging", 'current_val' => $this->getGeneralImages('imageBragging', $courseId)),
            array('name' => "Overlay for level 2", 'id'=> 'level2', 'type' => "image", 'options' => "Level2", 'current_val' => $this->getGeneralImages('imageLevel2', $courseId)),
            array('name' => "Overlay for level 3", 'id'=> 'level3', 'type' => "image", 'options' => "Level3", 'current_val' => $this->getGeneralImages('imageLevel3', $courseId))
        ];
        return $input;
        

    }
    public function save_general_inputs($generalInputs,$courseId){
        $maxVal = $generalInputs["maxReward"];
        $this->saveMaxReward($maxVal, $courseId);

        $extraImg = $generalInputs["extraImg"];
        if ($extraImg != "") {
            $this->saveGeneralImages('imageExtra', $extraImg, $courseId);
        }
        $braggingImg = $generalInputs["braggingImg"];
        if ($braggingImg != "") {
            $this->saveGeneralImages('imageBragging', $braggingImg, $courseId);
        }
        $imageL2 = $generalInputs["imgL2"];
        if ($imageL2 != "") {
            $this->saveGeneralImages('imageLevel2', $imageL2, $courseId);
        }
        $imageL3 = $generalInputs["imgL3"];
        if ($imageL3 != "") {
            $this->saveGeneralImages('imageLevel3', $imageL3, $courseId);
        }
    }



    public function has_listing_items (){ return  true; }
    public function get_listing_items ($courseId){
        //tenho de dar header
        $header = ['Name', 'Description', '# Levels', 'Level 1', 'XP Level 1', 'Is Count','Is Post', 'Is Point', 'Is Extra', 'Image', 'Active'] ;
        $displayAtributes = ['name', 'description', 'maxLevel', 'desc1','xp1',  'isCount', 'isPost', 'isPoint', 'isExtra', 'image', 'isActive'];
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
            array('name' => "Is Extra", 'id'=> 'extra', 'type' => "on_off button", 'options' => ""),
            array('name' => "Count 1", 'id'=> 'count1', 'type' => "number", 'options' => ""),
            array('name' => "Count 2", 'id'=> 'count2', 'type' => "number", 'options' => ""),
            array('name' => "Count 3", 'id'=> 'count3', 'type' => "number", 'options' => ""),
            array('name' => "Badge images", 'id'=> 'image', 'type' => "image", 'options' => "")
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

    public function activeItem($itemId){
        $active = Core::$systemDB->select("badge", ["id" => $itemId], "isActive");
        Core::$systemDB->update("badge", ["isActive" => $active? 0 : 1], ["id" => $itemId]);
        //ToDo: ADD RULE MANIPULATION HERE
    }

    public function update_module($compatibleVersions)
    {
        //obter o ficheiro de configuração do module para depois o apagar
        $configFile = "modules/badges/config.json";
        $contents = array();
        if(file_exists($configFile)){
            $contents = json_decode(file_get_contents($configFile));
            unlink($configFile);
        }
        //verificar compatibilidade
        
    }

    public static function importItems($course, $fileData, $replace = true){
        $courseObject = Course::getCourse($course, false);
        $moduleObject = $courseObject->getModule("badges");

        $newItemNr = 0;
        $lines = explode("\n", $fileData);
        $has1stLine = false;
        $nameIndex = "";
        $descriptionIndex = "";
        $description = array();
        $reward = array();
        $isCountIndex = "";
        $isPostIndex = "";
        $isPointIndex = "";
        $count = array();
        $i = 0;
        if ($lines[0]) {
            $lines[0] = trim($lines[0]);
            $firstLine = explode(";", $lines[0]);
            $firstLine = array_map('trim', $firstLine);
            if (in_array("name", $firstLine)
                && in_array("description", $firstLine) && in_array("isCount", $firstLine)
                && in_array("isPost", $firstLine) && in_array("isPoint", $firstLine)
                && in_array("desc1", $firstLine) && in_array("xp1", $firstLine) && in_array("p1", $firstLine)
                && in_array("desc2", $firstLine) && in_array("xp2", $firstLine) && in_array("p2", $firstLine)
                && in_array("desc3", $firstLine) && in_array("xp3", $firstLine) && in_array("p3", $firstLine)
            ) {
                $has1stLine = true;
                $nameIndex = array_search("name", $firstLine);
                $descriptionIndex = array_search("description", $firstLine);
                $description[1] = array_search("desc1", $firstLine);
                $description[2] = array_search("desc2", $firstLine);
                $description[3] = array_search("desc3", $firstLine);
                $reward[1] = array_search("xp1", $firstLine);
                $reward[2] = array_search("xp2", $firstLine);
                $reward[3] = array_search("xp3", $firstLine);
                $isCountIndex = array_search("isCount", $firstLine);
                $isPostIndex = array_search("isPost", $firstLine);
                $isPointIndex = array_search("isPoint", $firstLine);
                $count[1] = array_search("p1", $firstLine);
                $count[2] = array_search("p2", $firstLine);
                $count[3] = array_search("p3", $firstLine);
            }
        }
        foreach ($lines as $line) {
            $line = trim($line);
            $item = explode(";", $line);
            $item = array_map('trim', $item);
            if (count($item) > 1){
                if (!$has1stLine){
                    $nameIndex = 0;
                    $descriptionIndex = 1;
                    $description[1] = 2;
                    $description[2] = 3;
                    $description[3] = 4;
                    $reward[1] = 5;
                    $reward[2] = 6;
                    $reward[3] = 7;
                    $isCountIndex = 8;
                    $isPostIndex = 9;
                    $isPointIndex = 10;
                    $count[1] = 11;
                    $count[2] = 12;
                    $count[3] = 13;
                }
                $maxLevel= empty($item[$description[2]]) ? 1 : (empty($item[$description[3]]) ? 2 : 3);
                if (!$has1stLine || ($i != 0 && $has1stLine)) {
                    $itemId = Core::$systemDB->select("badge", ["course"=> $course, "name"=> $item[$nameIndex]], "id");

                    $badgeData = [
                        "name"=>$item[$nameIndex],
                        "description"=>$item[$descriptionIndex],
                        "countBased"=>(!strcasecmp("true",$item[$isCountIndex]))? 1 : ($item[$isCountIndex] == 1)? 1 : 0,
                        "postBased"=>(!strcasecmp("true",$item[$isPostIndex]))? 1 : ($item[$isPostIndex] == 1)? 1 : 0,
                        "pointBased"=>(!strcasecmp("true",$item[$isPointIndex]))? 1 : ($item[$isPointIndex] == 1)? 1 : 0,
                        "extra"=>($item[$reward[1]] < 0)? 1 : 0,
                        "desc1"=>$item[$description[1]],
                        "desc2"=>$item[$description[2]],
                        "desc3"=>$item[$description[3]],
                        "xp1"=>$item[$reward[1]],
                        "xp2"=>$item[$reward[2]],
                        "xp3"=>$item[$reward[3]],
                        "count1"=>(empty($item[$count[1]]))? 0 : $item[$count[1]],
                        "count2"=>(empty($item[$count[2]]))? 0 : $item[$count[2]],
                        "count3"=>(empty($item[$count[3]]))? 0 : $item[$count[3]]
                        ];
                    if ($itemId){
                        if ($replace) {
                            $badgeData["id"] = $itemId;
                            $moduleObject->editBadge($badgeData, $course);
                        }
                    } else {
                        $moduleObject->newBadge($badgeData, $course);
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
        $listOfBadges = Core::$systemDB->selectMultiple("badge", ["course"=> $course], '*');
        $file = "";
        $i = 0;
        $len = count($listOfBadges);
        $file .= "name;description;isCount;isPost;isPoint;desc1;xp1;p1;desc2;xp2;p2;desc3;xp3;p3\n";
        foreach ($listOfBadges as $badge) {
            $maxLevel = $badge["maxLevel"];
            $isExtra = $badge["isExtra"];
            $isBragging = $badge["isBragging"];
            $isPoint = $badge["isPoint"];

            $file .= $badge["name"] . ";" . $badge["description"] . ";" . $badge["isCount"] . ";" .  $badge["isPost"] . ";" .  $badge["isPoint"] . ";";;
            for( $j = 1; $j <= 3 ; $j++){
                if ($j <= $maxLevel){
                    $level = Core::$systemDB->select("badge_level", ["badgeId"=> $badge["id"], "number"=> $j]);
                    $file .= $level["description"] . ";";
                    if ($isExtra){
                        $file .= "-". $level["reward"] . ";";
                    }
                    else if ($isBragging) {
                        $file .= "0" . ";";
                    }
                    else{
                        $file .= $level["reward"] . ";";
                    }
                    if ($isPoint){
                        $file .= $level["goal"] . ";";
                    }
                    else{
                        $file .= ";";
                    }
                }
                else{
                    $file .= ";;;";
                }
            }
            if ($i != $len - 1) {
                $file .= "\n";
            }
            $i++;
        }
        return ["Badges - " . $courseInfo["name"], $file];
    }
}

ModuleLoader::registerModule(array(
    'id' => 'badges',
    'name' => 'Badges',
    'description' => 'Enables Badges with 3 levels and xp points that can be atributed to a student in certain conditions.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Badges();
    }
));
