<?php
use GameCourse\Core;
use Modules\Views\Expression\ValueNode;
use GameCourse\Module;
use GameCourse\ModuleLoader;

class Badges extends Module {
    const BADGES_TEMPLATE_NAME = 'Badges block - by badges';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/badges.css');
    }
    public function getBadge($selectMultiple,$where){
        $where["course"]=$this->getCourseId();
        if ($selectMultiple){
            $badgeArray = Core::$systemDB->selectMultiple("badge",$where); 
            $type = "collection";
        }else{
            $badgeArray = Core::$systemDB->select("badge",$where); 
            if (empty($badgeArray))
                throw new \Exception("In function badges.getBadge(name): couldn't find badge with name '".$where["name"]."'.");
            $type = "object";
        }
        return $this->createNode($badgeArray, 'badges',$type);
    }
    
    public function getLevel($levelNum,$badge){
        $type="object";
        $parent=null;
        if ($levelNum===0){
            $level = ["number"=>0,"description"=>null];
        }else if ($levelNum>$badge["value"]["maxLevel"] || $levelNum<0){
            $level = ["number"=>null,"description"=>null];
        }else{
            $table = "level join badge_has_level on id=levelId";
            $badgeId = $badge["value"]["id"];
            if ($levelNum==null){
                $level = Core::$systemDB->selectMultiple($table,["badgeId"=>$badgeId]);
                $type="collection";
                $parent=$badge;
            }else{
                $level = Core::$systemDB->select($table,["badgeId"=>$badgeId,"number"=>$levelNum]);
            }
        }
        unset($badge["value"]["description"]);
        unset($level["id"]);
        
        if ($type == "collection") {
            foreach ($level as &$l) {
                $l["libraryOfVariable"] = "badges";
                $l=array_merge($badge["value"],$l);
            }
        } else {
            $level["libraryOfVariable"] = "badges";
            $level=array_merge($badge["value"],$level);
        }
        return $this->createNode($level, 'badges',$type,$parent);
    }
    
    public function getLevelNum($badge,$user) {
        $id=$this->getUserId($user);
        $badgeId=$badge["value"]["id"];
        $levelNum=Core::$systemDB->select("award",["user"=>$id,"type"=>"badge","moduleInstance"=>$badgeId],"count(*)");
        return (int)$levelNum;
    }
    
    public function dropTables($moduleName){
        $levels = Core::$systemDB->selectMultiple("level left join badge_has_level on levelId=id",
                    ["course"=>$this->getCourseId()],'id',null,[["levelId",null]]);
        foreach($levels as $lvl){
            Core::$systemDB->delete("level",["id"=>$lvl["id"]]);
        }
        parent::dropTables($moduleName);
    }
    public function getBadgeCount($user=null){
        $courseId=$this->getCourseId();
        if ($user===null){
            return Core::$systemDB->select("badge",["course"=>$courseId],"sum(maxLevel)");
        }
        $id=$this->getUserId($user);
        return  Core::$systemDB->select("award", ["course"=>$courseId,"type"=>"badge","user"=>$id],"count(*)") ;
    }
    public function init() {
        if ($this->addTables("badges", "badge")) {
            Core::$systemDB->insert("badges_config", ["maxBonusReward" => MAX_BONUS_BADGES, "course" => $this->getCourseId()]);
        }
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        
        //%badges.getAllBadges(isExtra,IsBragging)
        $viewHandler->registerFunction('badges','getAllBadges', function($isExtra=null, $isBragging=null) { 
            $where=[];
            if ($isExtra!==null)
                $where["isExtra"]=$isExtra;
            if ($isBragging!==null)
                $where["isBragging"]=$isBragging;
            return $this->getBadge(true,$where);
        });
        //%badges.getBadge(name)
        $viewHandler->registerFunction('badges','getBadge', function($name=null) { 
            return $this->getBadge(false,["name"=>$name]);
        });
        //%badges.getCountBadges(user) returns num of badges of user (if specified) or of course 
        $viewHandler->registerFunction('badges','getBadgesCount', function($user=null) {  
            return new ValueNode( $this->getBadgeCount($user) );
        });
        //%badge.description
        $viewHandler->registerFunction('badges','description', function($arg) {
            return $this->basicGetterFunction($arg,"description");
        });
        //%badge.name
        $viewHandler->registerFunction('badges','name', function($badge) {
            return $this->basicGetterFunction($badge,"name");
        });
        //%badge.maxLevel
        $viewHandler->registerFunction('badges','maxLevel', function($badge) {
            return $this->basicGetterFunction($badge,"maxLevel");
        });
        //%badge.isExtra
        $viewHandler->registerFunction('badges','isExtra', function($badge) {
            return $this->basicGetterFunction($badge,"isExtra");
        });
        //%badge.isBragging
        $viewHandler->registerFunction('badges','isBragging', function($badge) {
            return $this->basicGetterFunction($badge,"isBragging");
        });
        //%badge.renderPicture(number) return expression for the image of the badge in the specified level
        $viewHandler->registerFunction('badges','renderPicture', function($badge,$level) {   
            //$level num or object
            if (is_array($level))
                $levelNum=$level["number"];
            else
                $levelNum=$level;
            $name=str_replace(' ', '', $badge["value"]["name"]);
            return new ValueNode("badges/".$name."-".$levelNum.".png");
        });
        //%badge.levels returns collection of level objects
        $viewHandler->registerFunction('badges','levels', function($badge) {
            $this->checkArray($badge, "object", 'levels');
            return $this->getLevel(null,$badge);
        });
        //%badge.getLevel(number) returns level object
        $viewHandler->registerFunction('badges','getLevel', function($badge,$level) {
            $this->checkArray($badge, "object", 'getLevel');
            $this->checkArray($level, "object", 'getLevel');
            return $this->getLevel($level,$badge);
        });
        //%badge.currLevel(%user) returns object of the current level of user
        $viewHandler->registerFunction('badges','currLevel', function($badge,$user) {
            $this->checkArray($badge, "object", 'currLevel');
            $levelNum=$this->getLevelNum($badge,$user);
            return $this->getLevel($levelNum,$badge);
        });
        //%badge.nextLevel(user) %level.nextLevel  returns level object
        $viewHandler->registerFunction('badges','nextLevel', function($arg,$user=null) {
            $this->checkArray($arg, "object", 'nextLevel');
            if ($user === null) { //arg is a level
                $levelNum = $arg["value"]["number"];
            } else {//arg is badge
                $levelNum = $this->getLevelNum($arg, $user);
            }
            return $this->getLevel($levelNum+1,$arg);
        });
        //%badge.previousLevel(user) %level.previousLevel  returns level object
        $viewHandler->registerFunction('badges','previousLevel', function($arg,$user=null) {  
            $this->checkArray($arg, "object", 'previousLevel');
            if ($user === null) {//arg is a level
                $levelNum = $arg["value"]["number"];
            } else {//arg is badge
                $levelNum = $this->getLevelNum($arg, $user);
            }
            return $this->getLevel($levelNum-1,$arg);
        });
        //%level.goal
        $viewHandler->registerFunction('badges','goal', function($level) {
            return $this->basicGetterFunction($level,"goal");
        });
        //%level.reward
        $viewHandler->registerFunction('badges','reward', function($level) {
            return $this->basicGetterFunction($level,"reward");
        });
        //%level.number
        $viewHandler->registerFunction('badges','number', function($level) {
            return $this->basicGetterFunction($level,"number");
        });
        
        
        
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
        //if (!$viewsModule->templateExists(self::BADGES_TEMPLATE_NAME))
         //   $viewsModule->setTemplate(self::BADGES_TEMPLATE_NAME, file_get_contents(__DIR__ . '/badges.txt'),$this->getId());   
    }
}

ModuleLoader::registerModule(array(
    'id' => 'badges',
    'name' => 'Badges',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Badges();
    }
));
?>
