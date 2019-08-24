<?php
$starttime = microtime(true);
$first  = new DateTime();
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

function printTrace() {
    echo '<pre>';
    print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
    echo '</pre>';
}

include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;
use \SmartBoards\User;
use \SmartBoards\Course;
use \SmartBoards\CourseUser;

$isCLI = Core::isCLI();
Core::init();

if(!Core::requireSetup(false))
    die('Please perform setup first!');

//the course shoud de specified when running this script, else it will guess it's course 1
if ($isCLI) {
    $courseId = (array_key_exists(1, $argv) ? $argv[1] : 1);
} else {
    $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 1);
}

$course = Course::getCourse($courseId);

if (!$isCLI)
    echo '<pre>';

$legacyFolder = Course::getCourseLegacyFolder($courseId);

//get badges info that is needed when reading awards and for the user_badges
$DBbadges = Core::$systemDB->selectMultiple("badge",["course"=>$courseId]);
$sbBadges=[];
foreach($DBbadges as &$b){
    $sbBadges[$b["name"]]=$b;
    for ($i=1;$i<=$b["maxLevel"];$i++){
        $xp = Core::$systemDB->select("level join badge_has_level on id=levelId",
                ["course"=>$courseId,"badgeId"=>$b["id"],"number"=>$i],"reward");
        $sbBadges[$b["name"]]['xp'][]=$xp;
    }
}

// Read Indicators
$indicators = json_decode(file_get_contents($legacyFolder . '/indicators.json'), true);
$indicatorsByNum = array();
foreach ($indicators as &$indicatorsUser) {
    $indicatorsByNum[$indicatorsUser['num']] = $indicatorsUser['indicators'];   
}

//used for the awards
$badgesNames = array_keys($sbBadges);
$userIds=$course->getUsersIds();
// Read Awards
$keys = array('time', 'userid', 'what', 'field1', 'field2');
$awards = file_get_contents($legacyFolder . '/awards.txt');
$awards = preg_split('/[\r]?\n/', $awards, -1, PREG_SPLIT_NO_EMPTY);

$userBadge=[];
$userInfo=[];
foreach ($userIds as $userId) {
    $userBadge[$userId] = [];
}

foreach($awards as &$award) {
    $award = array_combine($keys, preg_split('/;/', $award, 5));
    
    if (in_array($award['userid'], $userIds)) {
        $data = ["course"=>$courseId,"user"=>$award['userid'],
                 "reward"=>(int) $award['field1'],"date"=>date("Y-m-d H:i:s", (double) $award['time'])];

        // Initial Bonus
        if ($award['what'] == 'Initial Bonus') {
            $name= $award['what'];
            if (empty(Core::$systemDB->select("award", ["description" => $name, "course" => $courseId, "user" => $award['userid']]))) {
                Core::$systemDB->insert("award", array_merge($data, ["description" => $name, "type" => 'bonus']));
                
            }
        }
        //Labs
        elseif ($award['what'] == 'Grade from Lab') {
            $name='Lab ' . $award['field2'];
            if (empty(Core::$systemDB->select("award", ["description" => $name, "course" => $courseId, "user" => $award['userid']]))) {
                Core::$systemDB->insert("award", array_merge($data, 
                        ["description" => $name, "type" => 'labs']));
            }
        }
        //Quizes
        elseif ($award['what'] == 'Grade from Quiz') {
            $name='Quiz ' . $award['field2'];
            $awardInDB=Core::$systemDB->select("award", ["description" => $name, "course" => $courseId, "user" => $award['userid']]);
            if (empty($awardInDB)) {
                Core::$systemDB->insert("award", array_merge($data, 
                        ["description" => $name, "type" => 'quiz']));
            }elseif($awardInDB["reward"]!=$data["reward"]){
                Core::$systemDB->update("award",["reward"=>$data["reward"]],["description" => $name, "course" => $courseId, "user" => $award['userid']]);        
            }
        }
        //Presentation
        elseif ($award['what'] == 'Grade from Presentation') {
            $name='Presentation';
            if (empty(Core::$systemDB->select("award",["description" => $name, "course" => $courseId, "user" => $award['userid']]))) {
                Core::$systemDB->insert("award", array_merge($data, 
                        ["description" => $name, "type" => 'presentation']));
            }
        }
        //Skill Tree
        elseif ($award['what'] == 'Skill Tree') {
            $name=$award['field2'];
            if (empty(Core::$systemDB->select("award", ["description" => $name, "course" => $courseId, "user" => $award['userid']]))) {
                $skillInstance = Core::$systemDB->select("skill s natural join skill_tier join skill_tree t on t.id=s.treeId",
                        ["name"=>$name,"course"=>$courseId],"s.id");
                $skillData = array_merge($data,["description" => $name, "type" => 'skill',"moduleInstance"=>$skillInstance]);
                Core::$systemDB->insert("award", $skillData);
                
                $awardId = Core::$systemDB->getLastId();
                unset($skillData["reward"]);
                
                $indicatorsForUser=$indicatorsByNum[$award['userid']];
                if (!array_key_exists($name, $indicatorsForUser)) {
                    echo "Did not receive indicator for skill ".$name. ", for user ".$award['userid']."\n";
                    continue;
                }
                $skillIndicator = $indicatorsForUser[$name];
                Core::$systemDB->insert("participation", array_merge($skillData,
                        //["award"=>$awardId,"post"=>$skillIndicator[1][0]['url']   ]));
                        ["post"=>$skillIndicator[1][0]['url']   ]));
               
            }
        }
        //Badges
        elseif (in_array($award['what'], $badgesNames)) {
            $level=$award['field1'];
            $badgeName = $award['what'];
            $name=$badgeName . " (level ".$level.")";
            $moduleInstance = Core::$systemDB->select("badge",["name"=>$badgeName, "course",$courseId],'id');
            $data['reward']=$sbBadges[$badgeName]['xp'][$level - 1];
            
            if (empty(Core::$systemDB->select("award",["description"=>$name,"course"=>$courseId,"user"=>$award['userid']]))){ 
                Core::$systemDB->insert ("award", array_merge ($data,
                                ["description" => $name, "type" => 'badge', "moduleInstance"=>$moduleInstance]));
            }
                
            $badgeLevel = $level;
            if (key_exists($name, $userBadge[$award['userid']]) && key_exists('level',$userBadge[$award['userid']][$badgeName]))      
                    $badgeLevel=max($userBadge[$award['userid']][$badgeName]["level"],$level);

            $userBadge[$award['userid']][$badgeName]["level"]= $badgeLevel;
                
            $badge_lvl_time=["badgeLevel"=>$level,"badgeLvlTime"=>$data['date'],
                                 "badgeName"=>$badgeName,"course"=>$courseId,"student"=>$award['userid']];
            $userBadge[$award['userid']][$badgeName]['level_time'][$level]=$badge_lvl_time;
        }
        else{
            echo '<pre>';
            echo '<p>Error processing award: </p>';
            print_r($award);
            echo '</pre>';
        }
    }
}
function insertParticipation($progressIndicator,$quality,$courseId){
    Core::$systemDB->insert("participation", $progressIndicator);
    $participation=Core::$systemDB->getLastId();
    if ($quality) {
        Core::$systemDB->insert("grade", ["participation" => $participation, "course" => $courseId, "grade" => $quality]);
    }
}
// Info for each student
foreach ($userIds as $userId){
    if (!$course->getUser($userId)->isStudent()) {
        continue;
    }

    //Badges of each student
    foreach($sbBadges as $badgeName => $badge){
        $badgeIndicators = $indicatorsByNum[$userId][$badgeName];
    
        $level = key_exists($badgeName,$userBadge[$userId]) ? $userBadge[$userId][$badgeName]['level'] : 0;

        if ($badge['isPost'] || $badge['isCount']){
            
            $quality =null;
            $post=null;
            $text=null;
            $link=null;
            
            if (is_array($badgeIndicators[1])) {
                
                $oldIndicators = Core::$systemDB->selectMultiple("participation",
                                ["user"=>$userId,"course"=>$courseId,"type"=>"badge","moduleInstance"=>$badge['id']]);
                $oldIndicatorsTextIndex=[];
                foreach($oldIndicators as $ind){
                    //print_r($ind);
                    $oldIndicatorsTextIndex[$ind["description"]][]=$ind["id"];
                }
                
                $postCount = 1;
                $indexes = [];//used for badges w multiple indicators w same text
                foreach($badgeIndicators[1] as $indicator) {
                    if ($badge['isPost']) {
                        if ($badge['isCount'] || ($indicator['action'] == 'graded post')) {
                            $quality = $indicator['xp'];
                            $post = $indicator['info']; 
                        }
                        $text = 'P' . $postCount++;
                        $link=$indicator['url'];
                    } else if ($badge['isCount'])
                        $text = $indicator['info'];
                    
                    if (key_exists($text, $indexes)){
                        $indexes[$text]++;
                    }else{
                        $indexes[$text]=0;
                    }
                    $index=$indexes[$text];
                    
                    $progressIndicator = ["post" => $link,"description" => $text,"type"=>"badge",
                        "date"=>date("Y-m-d H:i:s",$indicator["timestamp"]),"user" => $userId,
                        "moduleInstance" => $badge['id'],"course" => $courseId];
                    if (key_exists($text, $oldIndicatorsTextIndex)){
                        $found = array_search($index, $oldIndicatorsTextIndex[$text]);
                        if ($found !== false) {
                            unset($oldIndicatorsTextIndex[$text][$found]);
                        } else {
                            insertParticipation($progressIndicator,$quality,$courseId);
                        }
                    }else {
                        insertParticipation($progressIndicator,$quality,$courseId);

                    }
          
                }
                foreach ($oldIndicatorsTextIndex as $deleteText=>$deleteIndexes){
                    foreach ($deleteIndexes as $deleteIndex)
                        Core::$systemDB->delete("participation",["id"=>$deleteIndex]);
                }
            }
        }
    }
}
Core::$systemDB->update("course",["lastUpdate"=>date("Y-m-d H:i:s", time())],["id"=>$courseId]);
echo "Finished!". ($isCLI ? "\n" :  '<br>');
$last  = new DateTime();
$diff = $first->diff( $last );
echo "Time: ".$diff->format( '%H:%I:%S' ).($isCLI ? "\n" :  '<br>');
$endtime = microtime(true);
$total=$endtime-$starttime;
echo "seconds: ".$total . ($isCLI ? "\n" :  '<br>');
if (!$isCLI)
    echo '</pre>';
?>
