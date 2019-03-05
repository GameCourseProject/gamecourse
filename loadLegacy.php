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

define('XP_PER_LEVEL', 1000);
define('MAX_BONUS_BADGES', 1000);
define('MAX_TREE_XP', 5000);

include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;
use \SmartBoards\User;
use \SmartBoards\Course;
use \SmartBoards\CourseUser;

$isCLI = Core::isCLI();
Core::init();

if(!Core::requireSetup(false))
    die('Please perform setup first!');

if ($isCLI) {
    $courseId = (array_key_exists(1, $argv) ? $argv[1] : 1);
} else {
    $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 1);
}

$course = Course::getCourse($courseId);

echo '<pre>';

// Read Teachers
$keys = array('id', 'name', 'email');
$teachers = file_get_contents(LEGACY_DATA_FOLDER . '/teachers.txt');
$teachers = preg_split('/[\r]?\n/', $teachers, -1, PREG_SPLIT_NO_EMPTY);

foreach($teachers as &$teacher) {
    $teacher = array_combine($keys, preg_split('/;/', $teacher));
    $user = User::getUser($teacher['id']);
    if (!$user->exists()) {
        $user->create($teacher['name']);
        $user->setEmail($teacher['email']);
    } else {
        $user->initialize($teacher['name'], $teacher['email']);  
    }
    $user->setAdmin(true);
    
    $courseUser= new CourseUser($teacher['id'],$course);
    if (!$courseUser->exists()) {
        $courseUser->create("Teacher");
        echo 'New teacher ' . $teacher['id'] . "\n";
    }elseif (!$courseUser->isTeacher()){
        $courseUser->setRoles("Teacher");
    }
}

// Read Students
$keys = array('id', 'name', 'email', 'campus');
$students = utf8_encode(file_get_contents(LEGACY_DATA_FOLDER . '/students.txt'));
$students = preg_split('/[\r]?\n/', $students, -1, PREG_SPLIT_NO_EMPTY);
foreach($students as &$student) {
    $student = array_combine($keys, preg_split('/;/', $student));
    $user = User::getUser($student['id']);
    if (!$user->exists()) {
        $user->create($student['name']);
        $user->setEmail($student['email']);
    } else {
        $user->initialize($student['name'], $student['email']);  
    }
    
    $courseUser= new CourseUser($student['id'],$course);
    if (!$courseUser->exists()) {
        $courseUser->create("Student",$student['campus']);
        echo 'New student ' . $student['id'] . "\n";
    }else
        $courseUser->addRole("Student");
}

if (file_exists(LEGACY_DATA_FOLDER . '/gave_up.txt')) {
    $keys = array('id', 'name', 'email', 'campus');
    $studentsGaveUp = utf8_encode(file_get_contents(LEGACY_DATA_FOLDER . '/gave_up.txt'));
    $studentsGaveUp = preg_split('/[\r]?\n/', $studentsGaveUp, -1, PREG_SPLIT_NO_EMPTY);
    foreach($studentsGaveUp as &$student) {
        $student = array_combine($keys, preg_split('/;/', $student));
        $courseUser= new CourseUser($student['id'],$course);
        if ($courseUser->exists()){
            $courseUser->delete();
            echo 'Student ' . $student['id'] . " gave up\n";
        }
    }
}

// Read Tree
$keys = array('tier', 'name', 'dependencies', 'color', 'xp');
$skillTree = file_get_contents(LEGACY_DATA_FOLDER . '/tree.txt');
$skillTree = preg_split('/[\r]?\n/', $skillTree, -1, PREG_SPLIT_NO_EMPTY);

foreach($skillTree as &$skill) {
    $skill = array_combine($keys, preg_split('/;/', $skill));
    if (strpos($skill['dependencies'], '|') !== FALSE) {//2 possible dependencies
        $skill['dependencies'] = preg_split('/[|]/', $skill['dependencies']);
        foreach($skill['dependencies'] as &$dependency) {
            $dependency = preg_split('/[+]/', $dependency);
        }
    } else {
        if (strpos($skill['dependencies'], '+') !== FALSE)
            $skill['dependencies'] = array(preg_split('/[+]/', $skill['dependencies']));
        else
            $skill['dependencies'] = array();
    }

    unset($skill['xp']);
    $descriptionPage = file_get_contents(LEGACY_DATA_FOLDER . '/tree/' . str_replace(' ', '', $skill['name']) . '.html');

    $start = strpos($descriptionPage, '<td>') + 4;
    $end = stripos($descriptionPage, '</td>');
    $descriptionPage = substr($descriptionPage, $start, $end - $start);
    $skill['page'] = htmlspecialchars(utf8_encode($descriptionPage));
    //if skill doesn't exit, add it to DB (ToDo consider cases where skill atribute changes)
    if (empty(Core::$sistemDB->select("skill","*",["skillName"=>$skill["name"],"course"=>$courseId]))){
        Core::$sistemDB->insert("skill",["skillName"=>$skill["name"],"color"=>$skill['color'],
                                         "page"=>$skill['page'],"tier"=>$skill['tier'],"course"=>$courseId]);
        if (!empty($skill['dependencies'])){
            for ($i=0; $i<sizeof($skill['dependencies']);$i++){
                $dep=$skill['dependencies'][$i];
                Core::$sistemDB->insert("skill_dependency",["dependencyNum"=>$i,"skillName"=>$skill["name"],"course"=>$courseId,
                                                            "dependencyA"=>$dep[0],"dependencyB"=>$dep[1]]);
            }
        }
    }
}

// Read Levels
$keys = array('title', 'minxp');
$levels = file_get_contents(LEGACY_DATA_FOLDER . '/levels.txt');
$levels = preg_split('/[\r]?\n/', $levels, -1, PREG_SPLIT_NO_EMPTY);

for($i=0;$i<sizeof($levels);$i++){
    //if level doesn't exit, add it to DB (ToDo consider cases where level atribute changes)
    if (empty(Core::$sistemDB->select("level","*",["lvlNum"=>$i,"course"=>$courseId]))){
        $level = array_combine($keys, preg_split('/;/', $levels[$i]));
        Core::$sistemDB->insert("level",["lvlNum"=>$i,"minXP"=>(int) $level['minxp'],
                                         "title"=>$level['title'],"course"=>$courseId]);  
    }
}

// Read Achievements/Badges
$keys = array(
    'name', 'description', 'desc1', 'desc2', 'desc3', 'xp1', 'xp2', 'xp3', 'countBased', 'postBased', 'pointBased',
    'count1', 'count2', 'count3'
);
$achievements = file_get_contents(LEGACY_DATA_FOLDER . '/achievements.txt');
$achievements = preg_split('/[\r]?\n/', $achievements, -1, PREG_SPLIT_NO_EMPTY);
$sbBadges = array();
$totalLevels = 0;

foreach($achievements as &$achievement) {
    $achievement = array_combine($keys, preg_split('/;/', $achievement));
    $maxLevel= empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);
    //if badge doesn't exit, add it to DB
    $badgeData = ["maxLvl"=>$maxLevel,
                  "isExtra"=> ($achievement['xp1'] < 0),
                  "isBragging"=>($achievement['xp1'] == 0),
                  "isCount"=>($achievement['countBased'] == 'True'),
                  "isPost"=>($achievement['postBased'] == 'True'),
                  "isPoint"=>($achievement['pointBased'] == 'True')];
    if (empty(Core::$sistemDB->select("badge","*",["badgeName"=>$achievement['name'],"course"=>$courseId]))){
        Core::$sistemDB->insert("badge", array_merge($badgeData,
                                        ["course"=>$courseId,"badgeName"=>$achievement['name'],
                                        "badgeDescription"=>$achievement['description']]));
        for ($i=1;$i<=$maxLevel;$i++){
            Core::$sistemDB->insert("badge_level",["level"=>$i,"course"=>$courseId,
                                            "xp"=>abs($achievement['xp'.$i]),
                                            "description"=>$achievement['desc'.$i],
                                            "progressNeeded"=>$achievement['count'.$i],
                                            "badgeName"=>$achievement['name']]);
        }
        
    }
    //this is here because we need xp for the awards 
    for ($i=1;$i<=$maxLevel;$i++)
            $sbBadges[$achievement['name']]['xp'][]=abs($achievement['xp'.$i]);
    $sbBadges[$achievement['name']]=array_merge($sbBadges[$achievement['name']],$badgeData);
    $totalLevels += $maxLevel; 
}
Core::$sistemDB->update("course",["numBadges"=>$totalLevels],["id"=>$courseId]);

// Read Indicators
$indicators = json_decode(file_get_contents(LEGACY_DATA_FOLDER . '/indicators.json'), true);
$indicatorsByNum = array();
foreach ($indicators as &$indicatorsUser) {
    $indicatorsByNum[$indicatorsUser['num']] = $indicatorsUser['indicators'];   
}

//used for the awards
$badgesNames = array_keys($sbBadges);
$userIds=$course->getUsersIds();
// Read Awards
$keys = array('time', 'userid', 'what', 'field1', 'field2');
$awards = file_get_contents(LEGACY_DATA_FOLDER . '/awards.txt');
$awards = preg_split('/[\r]?\n/', $awards, -1, PREG_SPLIT_NO_EMPTY);

$userBadge=[];
$userInfo=[];
foreach ($userIds as $userId) {
    $userBadge[$userId] = [];
    $userInfo[$userId]['totalTreeXP']=0;
    $userInfo[$userId]['normalBadgeXP']=0;
    $userInfo[$userId]['extraBadgeXP']=0;
    $userInfo[$userId]['quizXP']=0;
    $userInfo[$userId]['labsXP']=0;
    $userInfo[$userId]['presentationXP']=0;
    $userInfo[$userId]['bonusXP']=0;
}

foreach($awards as &$award) {
    $award = array_combine($keys, preg_split('/;/', $award, 5));
    
    if (in_array($award['userid'], $userIds)) {
        $data = ["course"=>$courseId,"student"=>$award['userid'],
                 "reward"=>(int) $award['field1'],"awardDate"=>date("Y-m-d H:i:s", (double) $award['time'])];
        // Initial Bonus
        if ($award['what'] == 'Initial Bonus') {
            $name=$award['what'];
            if (empty(Core::$sistemDB->select("award", "*", ["awardName" => $name, "course" => $courseId, "student" => $award['userid']]))) {
                Core::$sistemDB->insert("award", array_merge($data, ["awardName" => $name, "type" => 'bonus']));
                $userInfo[$award['userid']]['bonusXP']+=$data['reward'];
            }
        }
        //Labs
        elseif ($award['what'] == 'Grade from Lab') {
            $name='Lab ' . $award['field2'];
            if (empty(Core::$sistemDB->select("award", "*", ["awardName" => $name, "course" => $courseId, "student" => $award['userid']]))) {
                Core::$sistemDB->insert("award", array_merge($data, 
                        ["awardName" => $name, "type" => 'grade',
                         'subtype' => 'lab', 'num' => $award['field2']]));
                $userInfo[$award['userid']]['labsXP']+=$data['reward'];
            }
        }
        //Quizes
        elseif ($award['what'] == 'Grade from Quiz') {
            $name='Quiz ' . $award['field2'];
            if (empty(Core::$sistemDB->select("award", "*", ["awardName" => $name, "course" => $courseId, "student" => $award['userid']]))) {
                Core::$sistemDB->insert("award", array_merge($data, 
                        ["awardName" => $name, "type" => 'grade',
                         'subtype' => 'quiz', 'num' => $award['field2']]));
                $userInfo[$award['userid']]['quizXP']+=$data['reward'];
            }
        }
        //Presentation
        elseif ($award['what'] == 'Grade from Presentation') {
            $name='Presentation';
            if (empty(Core::$sistemDB->select("award", "*", ["awardName" => $name, "course" => $courseId, "student" => $award['userid']]))) {
                Core::$sistemDB->insert("award", array_merge($data, 
                        ["awardName" => $name, "type" => 'grade',
                         'subtype' => 'presentation']));
                $userInfo[$award['userid']]['presentationXP']+=$data['reward'];
            }
        }
        //Skill Tree
        elseif ($award['what'] == 'Skill Tree') {
            $name=$award['field2'];
            if (empty(Core::$sistemDB->select("award", "*", ["awardName" => $name, "course" => $courseId, "student" => $award['userid']]))) {
                Core::$sistemDB->insert("award", array_merge($data, ["awardName" => $name, "type" => 'skill']));
                
                $indicatorsForUser=$indicatorsByNum[$award['userid']];
                if (!array_key_exists($name, $indicatorsForUser)) {
                    echo "Did not receive indicator for skill ".$skill. ", for user ".$award['userid']."\n";
                    continue;
                }
                
                Core::$sistemDB->updateAdd("course_user",["numSkills"=>1,"totalTreeXP"=>$data['reward']],
                                                ["id"=>$award['userid'],"course"=>$courseId]);
                $userInfo[$award['userid']]['totalTreeXP']+=$data['reward'];
               
                $skillIndicator = $indicatorsForUser[$name];
                Core::$sistemDB->insert("user_skill",
                        ["course"=>$courseId,"student"=>$award['userid'],
                         "skillName"=>$name,"skillTime"=>$data["awardDate"],
                         "post"=>$skillIndicator[1][0]['url'],"quality"=>(int) $skillIndicator[1][0]['xp']]);
            }
        }
        //Badges
        elseif (in_array($award['what'], $badgesNames)) {
            $name=$award['what'];
            $level=$award['field1'];
            if (empty(Core::$sistemDB->select("award","*",["awardName"=>$name,"course"=>$courseId,"student"=>$award['userid'],"level"=>$level]))){
                $data['reward']=$sbBadges[$award['what']]['xp'][$level - 1];
                
                Core::$sistemDB->insert ("award", array_merge ($data,
                                ["awardName" => $name, "type" => 'badge','level' => $level]));
                
                if ($sbBadges[$name]['isExtra']){
                    $normal=0;
                    $extra=$data['reward'];
                }else{
                    $normal=$data['reward'];
                    $extra=0;
                }
                Core::$sistemDB->updateAdd("course_user",["normalBadgeXP"=>$normal,
                                                          "extraBadgeXP"=> $extra,
                                                          "totalBadgeXP"=>$data['reward'],
                                                          "numBadgeLvls"=>1],
                                                ["id"=>$award['userid'],"course"=>$courseId]); 
                $userInfo[$award['userid']]['normalBadgeXP']+=$normal;
                $userInfo[$award['userid']]['extraBadgeXP']+=$extra;
                
            }
            $badgeLevel = $level;
            if (key_exists($name, $userBadge[$award['userid']]) && key_exists('level',$userBadge[$award['userid']][$name]))      
                    $badgeLevel=max($userBadge[$award['userid']][$name]["level"],$level);

            $userBadge[$award['userid']][$name]["level"]= $badgeLevel;
                
            $badge_lvl_time=["badgeLevel"=>$level,"badgeLvlTime"=>$data['awardDate'],
                                 "badgeName"=>$name,"course"=>$courseId,"student"=>$award['userid']];
            $userBadge[$award['userid']][$name]['level_time'][$level]=$badge_lvl_time;
        }
       else{
            echo '<pre>';
            echo '<p>Error processing award: </p>';
            print_r($award);
            echo '</pre>';
        }
    }
}

// Info for each student
foreach ($userIds as $userId){
    if (!$course->getUser($userId)->isStudent())
        continue;
    
    $countedTreeXP=min($userInfo[$userId]['totalTreeXP'],MAX_TREE_XP);
    $countedBadgeXP=$userInfo[$userId]['normalBadgeXP'] + min($userInfo[$userId]['extraBadgeXP'],MAX_BONUS_BADGES);
    $totalXP=$countedTreeXP+$countedBadgeXP+$userInfo[$userId]['bonusXP']+
             $userInfo[$userId]['quizXP']+$userInfo[$userId]['labsXP']+
             $userInfo[$userId]['presentationXP'];
    if ($totalXP>0){
        Core::$sistemDB->updateAdd("course_user",
            ["countedBadgeXP"=>$countedBadgeXP,"countedTreeXP"=>$countedTreeXP,"XP"=>$totalXP],
            ["course"=>$courseId,"id"=>$userId]);
        
        $realXP=(int)Core::$sistemDB->select("course_user","XP",["course"=>$courseId,"id"=>$userId]);
        
        Core::$sistemDB->update("course_user",
            ["level"=>floor($realXP/XP_PER_LEVEL)],
            ["course"=>$courseId,"id"=>$userId]);
    }

    //Badges of each student
    foreach($sbBadges as $badgeName => $badge){
        $badgeIndicators = $indicatorsByNum[$userId][$badgeName];

        if (empty(Core::$sistemDB->select("user_badge", "*", ["badgeName" => $badgeName, "course" => $courseId, "student" => $userId])))    
            Core::$sistemDB->insert("user_badge",["badgeName"=>$badgeName,
                                            "progress"=>(int)($badgeIndicators[0] == 'False' ? -1 : $badgeIndicators[0]),
                                            "course"=>$courseId,"student"=>$userId]);
        if (key_exists($badgeName,$userBadge[$userId])){
            Core::$sistemDB->update("user_badge",
                ["level"=>$userBadge[$userId][$badgeName]['level']],
                ["badgeName"=>$badgeName,"course"=>$courseId,"student"=>$userId]);
            for($i=1; $i<=$userBadge[$userId][$badgeName]['level'];$i++){
                if (empty(Core::$sistemDB->select("badge_level_time", "*", ["badgeLevel"=>$i,"badgeName" => $badgeName, "course" => $courseId, "student" => $userId]))) {
                    Core::$sistemDB->insert("badge_level_time", $userBadge[$userId][$badgeName]['level_time'][$i]);
                }
            }        
        }
        if ($badge['isPost']) {
            if ($badge['isCount'] || (is_array($badgeIndicators[1]) && !empty($badgeIndicators[1]) && $badgeIndicators[1][0]['action'] == 'graded post')) {
                $postCount = 1;
                foreach($badgeIndicators[1] as $indicator) {
                    $text='P' . $postCount++;
                    if (empty(Core::$sistemDB->select("progress_indicator", "*", ["indicatorText"=>$text,"badgeName" => $badgeName, "course" => $courseId, "student" => $userId])))    
                        Core::$sistemDB->insert("progress_indicator",
                                    ["quality"=>$indicator['xp'],
                                     "link"=>$indicator['url'],
                                     'post' => $indicator['info'],
                                     "indicatorText"=>$text,
                                     "badgeName"=>$badgeName,
                                     "course"=>$courseId,"student"=>$userId]);
                }
            } else {
                $postCount = 1;
                foreach($badgeIndicators[1] as $indicator) {
                    $text='P' . $postCount++;
                    //should i just use the code above, giving "post" and "quality" null vals?
                    if (empty(Core::$sistemDB->select("progress_indicator", "*", ["indicatorText"=>$text,"badgeName" => $badgeName, "course" => $courseId, "student" => $userId]) ))    
                        Core::$sistemDB->insert("progress_indicator",
                                ["link"=>$indicator['url'],
                                 "indicatorText"=>$text,
                                 "badgeName"=>$badgeName,
                                 "course"=>$courseId,"student"=>$userId]);
                }
            }
        } else if ($badge['isCount']) {
            if (is_array($badgeIndicators[1])) {
                foreach($badgeIndicators[1] as $indicator) {
                    $text = $indicator['info'];     
                    if (empty(Core::$sistemDB->select("progress_indicator", "*", ["indicatorText"=>$text,"badgeName" => $badgeName, "course" => $courseId, "student" => $userId])))     
                        Core::$sistemDB->insert("progress_indicator",
                                ["indicatorText"=>$text,
                                 "badgeName"=>$badgeName,
                                 "course"=>$courseId,"student"=>$userId]);
                }
            }
        } 
    }
}
echo "Finished!\n";
$last  = new DateTime();
$diff = $first->diff( $last );
echo "Time: ".$diff->format( '%H:%I:%S' ).'<br>';
$endtime = microtime(true);
$total=$endtime-$starttime;
echo "seconds: ".$total;
echo '</pre>';
?>
