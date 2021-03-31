<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include('classes/ClassLoader.class.php');
include('api_functions/courses_list.php');
include('api_functions/users_list.php');
include('api_functions/course_users_list.php');
include('api_functions/course_settings_pages.php');
include('api_functions/course_related.php');
include('api_functions/system_related.php');

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;
use GameCourse\Module;
use GameCourse\Settings;
use GameCourse\User;
use GameCourse\CourseUser;

Core::denyCLI();
if (!Core::requireLogin(false)) {
    API::error("Not logged in!", 400);
}
if (!Core::requireSetup(false))
    API::error("GameCourse is not yet setup.", 400);
Core::init();
if (!Core::checkAccess(false))
    API::error("Access denied.", 400);

ModuleLoader::scanModules();
API::gatherRequestInfo();

//------------------- self page

//get logged user informaition on both system and course
API::registerFunction('core', 'getUserInfo', function() {
    $user = Core::getLoggedUser();
    $userInfo = $user->getData();
    $userInfo['username'] = $user->getUsername();
    $userInfo['authenticationService'] = User::getUserAuthenticationService($userInfo['username']);
    API::response(array('userInfo' => $userInfo));
});

//------------------- main page

//get list of active courses of the logged user
API::registerFunction('core', 'getUserActiveCourses', function() {
    $user = Core::getLoggedUser();

    $coursesId = $user->getCourses();
    $courses=[];
    foreach($coursesId as $cid){
        $course = Core::getCourse($cid);
        if ($course["isActive"]){
            $courses[]=$course;
        }
    }
    array_combine(array_column($courses,'id'), $courses);

    API::response(array('userActiveCourses' => $courses));
});

//-------------------this functions have to be passed into the xp and levels module--------

//NO LONGER USED - remove later
//update list of course levels, from the levels configuration page 
/*API::registerFunction('settings', 'courseLevels', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $levels = Core::$systemDB->selectMultiple("level",["course"=>$courseId],'number,description,goal,id',"number");
    //print_r($levels);
    $levelsByNum = array_combine(array_column($levels,"number") , $levels);
    $numOldLevels=sizeof($levels);
    $folder = Course::getCourseLegacyFolder($courseId);

    if (API::hasKey('levelList')) {
        $keys = array('title', 'goal');
        $levelInput=API::getValue('levelList');
        $levelList = preg_split('/[\r]?\n/', $levelInput, -1, PREG_SPLIT_NO_EMPTY);  
        $numNewLevels=sizeof($levelList);
        $updatedData=[];
        for($i=0;$i<$numNewLevels;$i++){
            //if level doesn't exit, add it to DB 
            $splitInfo =preg_split('/;/', $levelList[$i]);
            if (sizeof($splitInfo) != sizeOf($keys)) {
                echo "Level information was incorrectly formatted";
                return null;
            }
            $level = array_combine($keys, $splitInfo);
            if (!array_key_exists($i, $levelsByNum)){
                Core::$systemDB->insert("level",["number"=>$i,"goal"=>(int) $level['goal'],
                                                 "description"=>$level['title'],"course"=>$courseId]);  
                $updatedData[]= "New Level: " .$i;
            }else{
                Core::$systemDB->update("level",["goal"=>(int) $level['goal'],"description"=>$level['title']],
                                                ["id" => $levelsByNum[$i]['id']]);
            }
        }
        $lvlDiff=$numOldLevels-$numNewLevels;
        //Delete levels when given a smaller list of new levels
        if ($lvlDiff>0){
            for($i=$numNewLevels;$i<$numOldLevels;$i++){
                Core::$systemDB->delete("level",["id" => $levelsByNum[$i]['id']]);
                $updatedData[]= "Deleted Level: " .$i;
            }
        }
        file_put_contents($folder . '/levels.txt', $levelInput);
        API::response(["updatedData"=>$updatedData ]);
        return;
    }
    $file = @file_get_contents($folder . '/levels.txt');
    if ($file===FALSE){$file="";}
    API::response(array('levelList' => $levels, "file"=>$file ));
});*/

//returns array with all dependencies of a skill
function getSkillDependencies($skillId){
    $depArray=[];
    $dependencyIDs = Core::$systemDB->selectMultiple("dependency",["superSkillId"=>$skillId],"id");

    foreach ($dependencyIDs as $id){
        $individualDeps = Core::$systemDB->selectMultiple("skill_dependency",["dependencyId"=>$id["id"]]);
        foreach ($individualDeps as $dep){
            if($dep["isTier"]){
                $name = Core::$systemDB->select("skill_tier",["id" => $dep["normalSkillId"]], "tier");
            }
            else {
                $name = Core::$systemDB->select("skill",["id" => $dep["normalSkillId"]], "name");
            }
            array_push($depArray, $name);
        } 
    }   
    return $depArray;
}

function insertSkillDependencyElements($depElements,$depId,$skillsArray,$tree){
    
    foreach ($depElements as $depElement){
        if (array_key_exists($depElement, $skillsArray)){
            $requiredSkillId=$skillsArray[$depElement]['id'];
        }else{
            $requiredSkillId = Core::$systemDB->select("skill",["name"=>$depElement,"treeId"=>$tree],"id");
            if ($requiredSkillId==null){
                //echo "On skill '".$skill["name"]."' used dependecy of undefined skill";
                return null;
            }
        }
        Core::$systemDB->insert("skill_dependency",["dependencyId"=>$depId,"normalSkillId"=>$requiredSkillId]);
    }       
    return true;
}

function updateSkills($list,$tree,$replace, $folder){
    //for now names of skills are unique inside a course
    //if they start to be able to differ, this function needs to be updated
    $keys = array('tier', 'name', 'dependencies', 'color', 'xp');
    $skillTree = preg_split('/[\r]?\n/', $list, -1, PREG_SPLIT_NO_EMPTY);
    $skillsInDB= Core::$systemDB->selectMultiple("skill",["treeId"=>$tree],"id,name,tier,treeId");
    $skillsToDelete= array_column($skillsInDB,'name');
    $skilldInDBNames = array_combine($skillsToDelete,$skillsInDB);
    
    $updatedData=[];
    
    foreach($skillTree as &$skill) {
        $splitInfo =preg_split('/;/', $skill);
        if (sizeOf($splitInfo) != sizeOf($keys)) {
            echo "Skills information was incorrectly formatted";
            return null;
        }
        $skill = array_combine($keys, $splitInfo);
        if (strpos($skill['dependencies'], '|') !== FALSE) {//multiple dependency sets
            $skill['dependencies'] = preg_split('/[|]/', $skill['dependencies']);
            foreach($skill['dependencies'] as &$dependency) {
                $dependency = preg_split('/[+]/', $dependency);
            }
        } else {
            if (strpos($skill['dependencies'], '+') !== FALSE)
                $skill['dependencies'] = array(preg_split('/[+]/', $skill['dependencies']));
            elseif (strlen($skill['dependencies']) > 0) {
                $deps = [];
                $deps[] = [$skill['dependencies']];
                $skill['dependencies']=$deps;
            } 
            else
                $skill['dependencies'] = array();
        }
        
        unset($skill['xp']);//Not being used because xp is defined by tier (FIX?)

        $descriptionPage = @file_get_contents($folder . '/skills/' . str_replace(' ', '', $skill['name']) . '.html');

        if ($descriptionPage===FALSE){
            echo "Error: The skill ".$skill['name']." does not have a html file in the legacy data folder";
            return null;
        }
        $start = strpos($descriptionPage, '<td>') + 4;
        $end = stripos($descriptionPage, '</td>');
        $descriptionPage = substr($descriptionPage, $start, $end - $start);
        $skill['page'] = htmlspecialchars(utf8_encode($descriptionPage));

        if (!array_key_exists($skill["name"], $skilldInDBNames)){
            
            //new skill, insert in DB
            try{
                Core::$systemDB->insert("skill",["name"=>$skill["name"],"color"=>$skill['color'],
                                         "page"=>$skill['page'],"tier"=>$skill['tier'],"treeId"=>$tree]);
                $skillId = Core::$systemDB->getLastId();
                
                if (!empty($skill['dependencies'])){
                    for ($i=0; $i<sizeof($skill['dependencies']);$i++){
                        Core::$systemDB->insert("dependency",["superSkillId"=>$skillId]);
                        $dependencyId = Core::$systemDB->getLastId();
                        $deps=$skill['dependencies'][$i];
                        if (!insertSkillDependencyElements($deps,$dependencyId,$skilldInDBNames,$tree)){
                            echo "On skill '".$skill["name"]."' used dependecy of undefined skill";
                            return null;
                        }
                    }
                }
                $updatedData[]= "New skill: ".$skill["name"];
            } 
            catch (PDOException $e){
                echo "Error: Cannot add skills with tier=".$skill["tier"]." since it doesn't exist.";
                return;
            }
        }else{            
            //skill that exists in DB, update its info
            Core::$systemDB->update("skill",["color"=>$skill['color'],"page"=>$skill['page'],"tier"=>$skill['tier']],
                                                            ["name"=>$skill["name"],"treeId"=>$tree]);
            //update dependencies
            $skill['id'] = $skilldInDBNames[$skill["name"]]['id'];
            
            $dependenciesinDB = getSkillDependencies($skill['id']);
                    //array_column(Core::$systemDB->selectMultiple("skill_dependency",["skillName"=>$skill["name"],"course"=>$courseId],"dependencyNum"),"dependencyNum");
            
            $numOldDependencies=sizeof($dependenciesinDB);
            $numNewDependencies=sizeof($skill['dependencies']);
            foreach ($skill['dependencies'] as $depSet){
                $dependencyIndex=array_search($depSet, $dependenciesinDB);
                if ($dependencyIndex!==false){
                    unset($dependenciesinDB[$dependencyIndex]);
                }else{
                    Core::$systemDB->insert("dependency",["superSkillId"=>$skill['id']]);
                    $depSetId = Core::$systemDB->getLastId();
                    if (!insertSkillDependencyElements($depSet,$depSetId,$skilldInDBNames,$tree)){
                        echo "On skill '".$skill["name"]."' used dependecy of undefined skill";
                        return null;
                    }
                }
            }
            foreach ($dependenciesinDB as $depId => $dep){
                Core::$systemDB->delete("dependency",["id"=>$depId]);
            }
            unset($skillsToDelete[array_search($skill['name'], $skillsToDelete)]);
        }
    }
    //delete skills that weren't in the imported data
    if ($replace){
        foreach ($skillsToDelete as $skill){
            Core::$systemDB->delete("skill",["name"=>$skill,"treeId"=>$tree]);
            $updatedData[]= "Deleted skill: ".$skill;
        } 
        file_put_contents($folder . '/tree.txt', $list);
    }
    
    API::response(["updatedData"=>$updatedData ]);
    return;
}

/*register_shutdown_function(function() {
    echo '<pre>';
    print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
    echo '</pre>';
    //print_r(\GameCourse\Course::$coursesDb);
    echo 'before' . \GameCourse\Course::$coursesDb->numQueriesExecuted();
    echo 'after' . \GameCourse\Course::$coursesDb->numQueriesExecuted();
});*/

API::processRequest();
