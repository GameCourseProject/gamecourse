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
    $folder = Course::getCourseDataFolder($courseId);

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
    $folder = Course::getCourseDataFolder($courseId);
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

//////////////////////////////////////////////////////////////////////////////
///     Removed from configurations.js
//////////////////////////////////////////////////////////////////////////////

/*app.controller('CourseSkillsSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    // old version, not used anymore
    $scope.replaceData = function (arg) {
        if (confirm("Are you sure you want to replace all the Skills with the ones on the input box?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, skillsList: arg }, alertUpdate);
    };
    $scope.replaceTier = function (arg) {
        if (confirm("Are you sure you want to replace all the Tiers with the ones on the input box?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, tiersList: arg }, alertUpdate);
    };
    $scope.replaceNumber = function (arg) {
        if (confirm("Are you sure you want to change the Maximum Tree XP?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, maxReward: arg }, alertUpdate);
    };
    $scope.addData = function (arg) { //Currently not being used
        $smartboards.request('settings', 'courseSkills', { course: $scope.course, newSkillsList: arg }, alertUpdate);
    };
    $scope.clearData = function () {
        clearFillBox($scope);
    };
    $scope.clearTier = function () { //clear textarea of the tiers
        if ($scope.tierList !== "")
            $scope.tierList = "";
        else if ("file2" in $scope.data)
            $scope.tierList = $scope.data.file2;
    };

    $smartboards.request('settings', 'courseSkills', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }
        var text = "Skills must be in the following format: tier;name;dep1A+dep1B|dep2A+dep2B;color;XP";

        $scope.data = data;
        var tabContent = $($element);
        var configurationSection = createSection(tabContent, 'Manage Skills');

        var configSectionContent = $('<div>', { 'class': 'row' });

        //Display skill Tree info (similar to how it appears on the profile)
        var dataArea = $('<div>', { 'class': 'column row', style: 'float: left; width: 55%;' });

        var numTiers = Object.keys(data.skillsList).length;
        for (t in data.skillsList) {

            var tier = data.skillsList[t];
            var width = 100 / numTiers - 2;

            var tierArea = $('<div>', { class: "block tier column", text: "Tier " + t + ":\t" + tier.reward + " XP", style: 'float: left; width: ' + width + '%;' });

            for (var i = 0; i < tier.skills.length; i++) {
                var skill = tier.skills[i];

                var skillBlock = $('<div>', { class: "block skill", style: "background-color: " + skill.color + "; color: #ffffff; width: 60px; height:60px" });
                skillBlock.append('<span style="font-size: 80%;">' + skill.name + '</span>');
                tierArea.append(skillBlock);

                if ('dependencies' in skill) {
                    for (var d in skill.dependencies) {
                        var deps = '<span style="font-size: 70%">';
                        for (var dElement in skill.dependencies[d]) {
                            deps += skill.dependencies[d][dElement] + ' + ';
                        }
                        deps = deps.slice(0, -2);
                        deps += '</span><br>';
                        tierArea.append(deps);
                    }
                }
            }
            dataArea.append(tierArea);
        }
        configSectionContent.append(dataArea);


        $scope.newList = data.file;
        var bigBox = constructTextArea($compile, $scope, "Skill", text, 45);

        $scope.tierList = data.file2;
        var bigBox2 = constructTextArea($compile, $scope, "Tier", "Tier must be in the following formart: tier;XP",
            100, "tierList", 5);
        $scope.maxReward = data.maxReward;
        var numInput = constructNumberInput($compile, $scope, "Maximum Skill Tree Reward", "maxReward", "Max Reward");
        //        $('<div>',{'class': 'column', style: 'float: right; width: 100%;',text:"Maximum Skill Tree Reward: "});
        //numInput.append('<br><input type:"number" style="width: 25%" id="newList" ng-model="maxReward">');
        //numInput.append('<button class="button small" ng-click="replaceMax(maxReward)">Save Max Reward</button>');
        //ToDo: add this button (and delete) back after the system stops using TXTs from legacy_folder
        //If TXT files are used it's difficult to keep them synced w DB and have the Add/Edit functionality
        //if (name!=="Level")
        //    bigBox.append('<button class="button small" ng-click="addData(newList)">Add/Edit '+name+'s </button>');
        //ng-disabled="!isValidString(inviteInfo.id) "
        // numInput.append('<button class="button small" ng-click="clear'+funName+'()" style="float: right;">Clear/Fill Box</button>');
        //bigBox.append('</div>');//<button ng-disabled="!isValidString(inviteInfo.id) || !isValidString(inviteInfo.username)" ng-click="createInvite()">Create</button></div>');

        bigBox.append(bigBox2);
        bigBox.append(numInput);
        configSectionContent.append(bigBox);
        configurationSection.append(configSectionContent);
    });
});*/

/*app.controller('CourseLevelsSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    //old version, not used
    $scope.replaceData = function (arg) {
        if (confirm("Are you sure you want to replace all the Levels with the ones on the input box?"))
            $smartboards.request('settings', 'courseLevels', { course: $scope.course, levelList: arg }, alertUpdate);
    };
    $scope.clearData = function () {
        clearFillBox($scope);
    };

    $smartboards.request('settings', 'courseLevels', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        var text = "Levels must in ascending order with the following format: title;minimunXP";
        tabContents = [];
        console.log(data.levelList);
        for (var st in data.levelList) {
            tabContents.push({
                Level: data.levelList[st].number,
                Title: data.levelList[st].description,
                "Minimum XP": data.levelList[st].goal,
                "": { level: data.levelList[st].id }
            });
        }
        var columns = ["Level", "Title", "Minimum XP"];
        $scope.newList = data.file;
        constructConfigPage(data, err, $scope, $element, $compile, "Level", text, tabContents, columns);
    });
});*/

//////////////////////////////////////////////////////////////////////////////
///     Removed from inside_course > settings.js
//////////////////////////////////////////////////////////////////////////////

        // in controller 'CourseSettingsModules'
        // var columns = ['c1', {field:'c2', constructor: function(content) {
        //     if(typeof content === 'string' || content instanceof String)
        //         return content;
        //     else {
        //         var state = $('<span>')
        //             .append($('<span>', {text: content.state ? 'Enabled ' : 'Disabled ' , 'class': content.state ? 'on' : 'off'}));
        //         var stateButton = $('<button>', {text: !content.state ? 'Enable' : 'Disable', 'class':'button small'});
        //         stateButton.click(function() {
        //             $(this).prop('disabled', true);
        //             $smartboards.request('settings', 'courseModules', {course: $scope.course, module: content.id, enabled: !content.state}, function(data, err) {
        //                 if (err) {
        //                     alert(err.description);
        //                     return;
        //                 }
        //                 location.reload();
        //             });
        //         });
        //         if (content.state || canEnable)
        //             state.append(stateButton);
        //         return state;

        //     }
        // }}];

        // var modulesSection = createSection(tabContent, 'Modules');
        // modulesSection.attr('id', 'modules');
        // var modules = $scope.data.modules;
        // for(var i in modules) {
        //     var module = modules[i];
        //     var dependencies = [];
        //     var canEnable = true;
        //     for (var d in module.dependencies) {
        //         var dependency = module.dependencies[d];
        //         var dependencyEnabled = modules[dependency.id].enabled;
        //         if (dependency.mode != 'optional') {
        //             if (!dependencyEnabled)
        //                 canEnable = false;
        //             dependencies.push('<span class="color: ' + (dependencyEnabled ? 'on' : 'off') + '">' + dependency.id + '</span>');
        //         }
        //     }
        //     dependencies = dependencies.join(', ');
        //     if (dependencies == '')
        //         dependencies = 'None';
        //     var table = Builder.buildTable([
        //         { c1:'Name:', c2: module.name},
        //         { c1:'Version:', c2: module.version},
        //         { c1:'Path:', c2: module.dir},
        //         { c1:'State:', c2: {state: module.enabled, id: module.id, canEnable: canEnable}},
        //         { c1:'Dependencies:', c2: dependencies}
        //     ], columns);
        //     modulesSection.append(table);
        // }

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

//////////////////////////////////////////////////////////////////////////////
///     Removed from module.badges inside init()
//////////////////////////////////////////////////////////////////////////////

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