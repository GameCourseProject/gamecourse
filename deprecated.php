<?php
/*---------------------------------------------------------------------------

Functions that are no longer being used.
Need to be removed if they are no longer needed.

----------------------------------------------------------------------------*/

//////////////////////////////////////////////////////////////////////////////
///     Removed from info.php
//////////////////////////////////////////////////////////////////////////////

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

/*function insertSkillDependencyElements($depElements,$depId,$skillsArray,$tree){
    
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
}*/

/*function updateSkills($list,$tree,$replace, $folder){
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
}*/

/*register_shutdown_function(function() {
    echo '<pre>';
    print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
    echo '</pre>';
    //print_r(\GameCourse\Course::$coursesDb);
    echo 'before' . \GameCourse\Course::$coursesDb->numQueriesExecuted();
    echo 'after' . \GameCourse\Course::$coursesDb->numQueriesExecuted();
});*/

//////////////////////////////////////////////////////////////////////////////
///     Removed from module.XPLevels.php inside init()
//////////////////////////////////////////////////////////////////////////////

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
/*API::registerFunction('settings', 'courseSkills', function() {
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
    else if (API::hasKey('newSkillsList')) {
        updateSkills(API::getValue('newSkillsList'), $courseId, false, $folder);
        return;
    }
    
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
});*/