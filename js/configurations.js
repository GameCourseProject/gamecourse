//This file contains configuration options that are inside the settings 
//(managing students and teachers, and configuring skill tree, badges and levels)

function alertUpdateAndReload(data,err){
    if (err) {
        alert(err.description);
        return;
    }
    
    if (Object.keys(data.updatedData).length>0){
        var output="";
        for (var i in data.updatedData){
            output+=data.updatedData[i]+'\n';
        }
        alert(output);
    }
    location.reload();
}

function replaceUsers(list, role, $smartboards, $scope) {
    if (confirm("Are you sure you want to replace all the "+ role+ "s with the ones on the input box?"))
        $smartboards.request('settings', 'courseUsers', {course : $scope.course, fullUserList:list, role:role}, alertUpdateAndReload);
}
function addUsers(list, role, $smartboards, $scope) {
    $smartboards.request('settings', 'courseUsers', {course : $scope.course, newUsers:list, role:role}, alertUpdateAndReload);
}
function deleteUser(user, $smartboards, $scope) {
    if (confirm("Are you sure you want to DELETE the user with id: "+user+'?'))
        $smartboards.request('settings', 'courseUsers', {course : $scope.course, deleteCourseUser:user}, alertUpdateAndReload);
}
function clearFillBox($scope){
    if ($scope.newList!=="")
        $scope.newList="";
    else if ("file" in $scope.data)
        $scope.newList=$scope.data.file;
}
function constructTextArea($compile,$scope,name,text,width=60,data="newList",){
    var bigBox = $('<div>',{'class': 'column', style: 'float: right; width: '+width+'%;', 
           text:text });
    
    var funName = (name=="Tier") ? name : "Data";
    bigBox.append('<textarea cols="80" rows="25" type="text" style="width: 100%" class="ConfigInputBox" id="newList" ng-model="'+data+'"></textarea>');
    bigBox.append('<button class="button small" ng-click="replace'+funName+'('+data+')">Replace '+name+' List</button>');
    //ToDo: add this button (and delete) back after the system stops using TXTs from legacy_folder
    //If TXT files are used it's difficult to keep them synced w DB and have the Add/Edit functionality
//if (name!=="Level")
    //    bigBox.append('<button class="button small" ng-click="addData(newList)">Add/Edit '+name+'s </button>');
        //ng-disabled="!isValidString(inviteInfo.id) "
    bigBox.append('<button class="button small" ng-click="clear'+funName+'()" style="float: right;">Clear/Fill Box</button>');
    //bigBox.append('</div>');//<button ng-disabled="!isValidString(inviteInfo.id) || !isValidString(inviteInfo.username)" ng-click="createInvite()">Create</button></div>');
    bigBox.append($compile(bigBox)($scope));
    return bigBox;
}
function constructConfigPage(data, err, $scope,$element,$compile,name,text,tabContents,columns){
    if (err) {
        console.log(err);
        return;
    }
    $scope.data = data;
    var tabContent = $($element);
    var configurationSection = createSection(tabContent, 'Manage '+name+'s');
   
    
    var configSectionContent = $('<div>',{'class': 'row'});
    
    var table = Builder.buildTable(tabContents, columns,true);
        
    var tableArea = $('<div>',{'class': 'column', style: 'float: left; width: 40%;' });
    tableArea.append(table);
    tableArea.append('</div>');
    configSectionContent.append(tableArea);

    var bigBox = constructTextArea($compile,$scope,name,text);
    
    configSectionContent.append(bigBox);
    configurationSection.append(configSectionContent);
}
//sets up the page for managing students or teachers
function userSettings(role,data, err, $scope,$element,$compile){
    console.log(data);
    userTabContents=[];
    for (var st in data.userList){
        userTabContents.push({ID: data.userList[st].id,Name:data.userList[st].name,Username:data.userList[st].username});
               // "":{id: data.userList[st].id}});
    }
    var columns = ["ID","Name","Username"];
            //This is for the button in the row. ToDo decide if courseUsers should be editable/deletable from this page
        /*{field:'', constructor: function(content) {
            var button = $('<button>', {text: 'Delete', 'class':'button small'});
            button.click(function() {
                $scope.deleteData(content.id);
            });
            return button;
        
    }}];*/
    var text = "Users must be inserted with the following format: username;id;name;email";
    if (role == "Student"){
        text += ";campus";
    }
    $scope.newList=data.file;
    constructConfigPage(data, err, $scope,$element,$compile,role,text,userTabContents,columns);
}

app.controller('CourseTeacherSettingsController',function($scope, $stateParams, $element, $smartboards, $compile, $parse){
    $scope.replaceData = function(arg) {
        replaceUsers(arg,"Teacher", $smartboards, $scope);
    };
    $scope.addData = function(arg) {//Currently not being used
        addUsers(arg,"Teacher", $smartboards, $scope);
    };
    $scope.deleteData = function(arg) {//currently not being used
        deleteUser(arg, $smartboards, $scope);
    };
    $scope.clearData = function(){
        clearFillBox($scope);
    };
    
    $smartboards.request('settings', 'courseUsers', {course : $scope.course, role: "Teacher"}, function(data,err){
        userSettings("Teacher",data,err,$scope,$element,$compile);
    });
});

app.controller('CourseStudentSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $scope.replaceData = function(arg) {
        replaceUsers(arg,"Student", $smartboards, $scope);
    };
    $scope.addData = function(arg) {//Currently not being used
        addUsers(arg,"Student", $smartboards, $scope);
    };
    $scope.deleteData = function(arg) {//Currently not being used
        deleteUser(arg, $smartboards, $scope);
    };
    $scope.clearData = function(){
        clearFillBox($scope);
    };
    
    $smartboards.request('settings', 'courseUsers', {course : $scope.course, role: "Student"}, function(data,err){
        console.log(data);
        userSettings("Student",data,err,$scope,$element,$compile);
    });
});

app.controller('CourseSkillsSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $scope.replaceData = function(arg) {
        if (confirm("Are you sure you want to replace all the Skills with the ones on the input box?"))
            $smartboards.request('settings', 'courseSkills', {course : $scope.course, skillsList:arg}, alertUpdateAndReload);
    };
    $scope.replaceTier = function(arg) {
        if (confirm("Are you sure you want to replace all the Tiers with the ones on the input box?"))
            $smartboards.request('settings', 'courseSkills', {course : $scope.course, tiersList:arg}, alertUpdateAndReload);
    };
    $scope.addData = function(arg) {//Currently not being used
            $smartboards.request('settings', 'courseSkills', {course : $scope.course, newSkillsList:arg}, alertUpdateAndReload);
    };
    $scope.clearData = function(){
        clearFillBox($scope);
    };
    $scope.clearTier = function(){//clear textarea of the tiers
        if ($scope.tierList!=="")
            $scope.tierList="";
        else if ("file2" in $scope.data)
            $scope.tierList=$scope.data.file2;
    };
    
    $smartboards.request('settings', 'courseSkills', {course : $scope.course}, function(data,err){
        if (err) {
            console.log(err);
            return;
        }
        var text = "Skills must be in the following format: tier;name;dep1A+dep1B|dep2A+dep2B;color;XP";
        
        $scope.data = data;
        var tabContent = $($element);
        var configurationSection = createSection(tabContent, 'Manage Skills');

        var configSectionContent = $('<div>',{'class': 'row'});
        
        //Display skill Tree info (similar to how it appears on the profile)
        var dataArea = $('<div>',{'class': 'column row', style: 'float: left; width: 55%;' });
        
        var numTiers = Object.keys(data.skillsList).length;
        for (t in data.skillsList ){
            
            var tier = data.skillsList[t];
            var width = 100 / numTiers -2;
            
            var tierArea = $('<div>',{class:"block tier column",text:"Tier "+ t +":\t"+tier.reward+" XP", style: 'float: left; width: '+width+'%;'});
            
            for (var i =0; i<tier.skills.length; i++){
                var skill = tier.skills[i];
                
                var skillBlock = $('<div>',{class: "block skill", style:"background-color: "+skill.color+"; color: #ffffff; width: 60px; height:60px"});
                skillBlock.append('<span style="font-size: 80%">'+skill.name+'</span>');
                tierArea.append(skillBlock);
                
                if ('dependencies' in skill){
                    for (var d in skill.dependencies){
                        tierArea.append('<span style="font-size: 70%">'+skill.dependencies[d][0]+' + '+skill.dependencies[d][1]+'</span><br>');
                    }
                }
            }
            dataArea.append(tierArea);
        }
        configSectionContent.append(dataArea);
        
    
        $scope.newList=data.file;
        var bigBox = constructTextArea($compile,$scope,"Skill",text,45);
        
        $scope.tierList=data.file2;
        var bigBox2 = constructTextArea($compile,$scope,"Tier","Tier must be in the following formart: tier;XP",
                                                        100,"tierList");
        bigBox.append(bigBox2);                                                
        configSectionContent.append(bigBox);
        configurationSection.append(configSectionContent);
    });
});
app.controller('CourseBadgesSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $scope.replaceData = function(arg) {
        if (confirm("Are you sure you want to replace all the Badges with the ones on the input box?"))
            $smartboards.request('settings', 'courseBadges', {course : $scope.course, badgesList:arg}, alertUpdateAndReload);
    };
    $scope.clearData = function(){
        clearFillBox($scope);
    };
    
    $smartboards.request('settings', 'courseBadges', {course : $scope.course}, function(data,err){
        if (err) {
            console.log(err);
            return;
        }
        
        var text = "Badges must in ascending order with the following format: name;description; desc1;desc2;desc3; xp1;xp2;xp3; count?;post?;point?; count1;count2;count3";
          
        $scope.data = data;
        var tabContent = $($element);
        var configurationSection = createSection(tabContent, 'Manage Badges');

        var configSectionContent = $('<div>',{'class': 'row'});
        
        //Display skill Badge info (similar to how it appears on the profile but simpler)
        var dataArea = $('<div>',{'class': 'column badges-page', style: 'padding-right: 10px; float: left; width: 43%;' });
        for (t in data.badgesList ){
            var badge = data.badgesList[t];
            var badgeArea = $('<div>',{'class': 'badge'});
            badgeArea.append('<strong style="font-size: 110%;">'+badge.name+':&nbsp&nbsp</strong>');
            badgeArea.append('<span style="font-size: 105%; ">'+badge.description+'</span><br><br>');
            
            var imageLevel = $('<div>',{ style:'height: 90px'});
            imageLevel.append('<img style="float: left" src="badges/'+badge.name.replace(/\s+/g, '')+'-1.png" class="img">');
            
            for (var i = 0;i<badge.maxLvl;i++){
                imageLevel.append('<span>Level '+(i+1)+':&nbsp</span>');
                imageLevel.append('<span>'+badge.levels[i].description+'&nbsp</span>');
                imageLevel.append('<span style="float: right">&nbsp'+badge.levels[i].xp+' XP</span>');
                //if (badge.isCount==true)
                //    badgeArea.append('<span style="font-size: 95%; ">&nbsp (count='+badge.levels[i].progressNeeded+')</span>');
                imageLevel.append('<br>');
            }
            
            var count = badge.isCount==true ? "Yes" : "No";
            imageLevel.append('<span style="font-size: 80%; ">Count Based: '+count+', </span>');
            var point = badge.isPoint==true ? "Yes" : "No";
            imageLevel.append('<span style="font-size: 80%; ">Point Based: '+point+', </span>');
            var post = badge.isPost==true ? "Yes" : "No";
            imageLevel.append('<span style="font-size: 80%; ">Post Based: '+post+', </span>');
            var extra = badge.isExtra==true ? "Yes" : "No";  
            imageLevel.append('<span style="font-size: 80%; ">Extra Credit: '+extra+'.</span><br>');
            
            badgeArea.append(imageLevel);
            dataArea.append(badgeArea);
        }
        configSectionContent.append(dataArea);
 
        $scope.newList=data.file;
        var bigBox = constructTextArea($compile,$scope,"Badge",text,55);

        configSectionContent.append(bigBox);
        configurationSection.append(configSectionContent);
    });
});
app.controller('CourseLevelsSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $scope.replaceData = function(arg) {
        if (confirm("Are you sure you want to replace all the Levels with the ones on the input box?"))
            $smartboards.request('settings', 'courseLevels', {course : $scope.course, levelList:arg}, alertUpdateAndReload);
    };
    $scope.clearData = function(){
        clearFillBox($scope);
    };
    
    $smartboards.request('settings', 'courseLevels', {course : $scope.course}, function(data,err){
        if (err) {
            console.log(err);
            return;
        }
        
        var text = "Levels must in ascending order with the following format: title;minimunXP";
        tabContents=[];
        for (var st in data.levelList){
            tabContents.push({Level: data.levelList[st].lvlNum,Title:data.levelList[st].title,"Minimum XP":data.levelList[st].minXP,
                    "":{level: data.levelList[st].id}});
        }
        var columns = ["Level","Title","Minimum XP"];
        $scope.newList=data.file;
        constructConfigPage(data, err, $scope,$element,$compile,"Level",text,tabContents,columns);
    });
});