//This file contains configuration options that are inside the settings 
//(managing students and teachers, and configuring skill tree, badges and levels)

function alertUpdateAndReload(data,err){
    if (err) {
        alert(err.description);
        return;
    }
    if (data.updatedUsers!="")
        alert(data.updatedUsers);
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

//sets up the page for managing students or teachers
function userSettings(role,data, err, $scope,$element,$compile){
    if (err) {
            console.log(err);
            return;
    }
    $scope.data = data;
    var tabContent = $($element);
    var userSection = createSection(tabContent, 'Manage '+role+'s');
    $userTabContents=[];
    var userSectionContent = $('<div>',{'class': 'row'});
    for (var st in data.userList){
        $userTabContents.push({ID: data.userList[st].id,Name:data.userList[st].name,Username:data.userList[st].username,
                "":{id: data.userList[st].id, name:data.userList[st].name,username:data.userList[st].username }});
    }
        
    var columns = ["ID","Name","Username",
            //This is for the button in the row. ToDo decide if courseUsers should be editable/deletable from this page
        {field:'', constructor: function(content) {
                var button = $('<button>', {text: 'Delete', 'class':'button small'});
                button.click(function() {
                    $scope.deleteUser(content.id);
                });
                return button;
            }}];
    var table = Builder.buildTable($userTabContents, columns,true);
        
    var tableArea = $('<div>',{'class': 'column', style: 'float: left; width: 40%;' });
    tableArea.append(table);
    tableArea.append('</div>');
    userSectionContent.append(tableArea);
        
    //if replace user list
    $text = "Users must be inserted with the following format: username;id;name;email";
    if (role == "Student"){
        $text += ";campus";
    }
    var text = 'Users must be inserted with the following format: username;id;name;email;';
    if (role=='Student'){
        text+='campus';
    }
   
    var bigBox = $('<div>',{'class': 'column', style: 'float: left; width: 60%;', 
           text:text });
    bigBox.append('<textarea cols="80" rows="25" type="text" class="UsersInputBox" id="newUserList" ng-model="newUserList"></textarea>');
    bigBox.append('<button class="button small" ng-click="replaceUsers(newUserList)">Replace '+role+' List</button>');
    bigBox.append('<button class="button small" ng-click="addUsers(newUserList)">Add '+role+'s to List</button>');
        //ng-disabled="!isValidString(inviteInfo.id) "
    bigBox.append('</div>');//<button ng-disabled="!isValidString(inviteInfo.id) || !isValidString(inviteInfo.username)" ng-click="createInvite()">Create</button></div>');
    bigBox.append($compile(bigBox)($scope));
    
    userSectionContent.append(bigBox);
    userSection.append(userSectionContent);
}

app.controller('CourseTeacherSettingsController',function($scope, $stateParams, $element, $smartboards, $compile, $parse){
    $scope.replaceUsers = function(arg) {
        replaceUsers(arg,"Teacher", $smartboards, $scope);
    };
    $scope.addUsers = function(arg) {
        addUsers(arg,"Teacher", $smartboards, $scope);
    };
    $scope.deleteUser = function(arg) {
        deleteUser(arg, $smartboards, $scope);
    };
    
    $smartboards.request('settings', 'courseUsers', {course : $scope.course, role: "Teacher"}, function(data,err){
        userSettings("Teacher",data,err,$scope,$element,$compile);
    });
});

app.controller('CourseStudentSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $scope.replaceUsers = function(arg) {
        replaceUsers(arg,"Student", $smartboards, $scope);
    };
    $scope.addUsers = function(arg) {
        addUsers(arg,"Student", $smartboards, $scope);
    };
    $scope.deleteUser = function(arg) {
        deleteUser(arg, $smartboards, $scope);
    };
    
    $smartboards.request('settings', 'courseUsers', {course : $scope.course, role: "Student"}, function(data,err){
        userSettings("Student",data,err,$scope,$element,$compile);
    });
});

//ToDo delete if not necessary
/*
 * $smartboards.request('settings', 'courseUsers', {course : $scope.course}, function(data, err) {
        if (err) {
            console.log(err);
            return;
        }
        $scope.data = data;
        var tabContent = $($element);
        var studentSection = createSection(tabContent, 'Students List');
        
        //Pop Up for editing the course_user
        var modal = $('<div>',{'class': 'modal', style:'display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; overflow:auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);'});
        var modalContent = $('<div>',{'class': 'modal-content', style:'background-color: #fefefe; margin: 15% auto; padding: 10px; border: 1px solid #888; width: 80%;'});
        var span = $('<span class="close" style="float: right; font-size: 28px; font-weight: bold;"> &times;</span>');
        span.click(function() {
            console.log("clickspan");
            var modal =document.getElementsByClassName("modal")[0];
            modal.style.display = "none";
        });
        modalContent.append(span);
        modalContent.append('<br><div id="editPopUpContent"> ToDo</div>');
        modal.append(modalContent);
        
        studentSection.append(modal);
        //

        $studentTabContents=[];
        var studentSectionContent = $('<div>',{'class': 'row'});
        for (var st in data.studentList){
            $studentTabContents.push({ID: data.studentList[st].id,Name:data.studentList[st].name,Username:data.studentList[st].username,
                    "":{id: data.studentList[st].id, name:data.studentList[st].name,username:data.studentList[st].username }});
        }
        
        
        var columns = ["ID","Name","Username",
            //This is for the button in the row. ToDo decide if courseUsers should be editable/deletable from this page
            {field:'', constructor: function(content) {
                var button = $('<button>', {text: 'Delete', 'class':'button small'});
                button.click(function() {
//
                    /*var modal =document.getElementsByClassName("modal")[0];
                    modal.style.display = "block";
                    var modalContent = document.getElementById("editPopUpContent");
                    modalContent.innerHTML='<form>';
                    modalContent.innerHTML +='ID   : <input type="text" name="id" value="'+content.id+'"><br><br>';
                    modalContent.innerHTML +='</form>';
//
                    $scope.deleteStudent(content.id);
                    
                });
                return button;
            }}];
        var table = Builder.buildTable($studentTabContents, columns,true);
        
        var tableArea = $('<div>',{'class': 'column', style: 'float: left; width: 35%;' });
        tableArea.append(table);
        tableArea.append('</div>');
        studentSectionContent.append(tableArea);
        
        
        //if replace student list
        var bigBox = $('<div>',{'class': 'column', style: 'float: left; width: 65%;', 
            text:'Users must be inserted with the following format: username;id;name;email;campus' });
        bigBox.append('<textarea cols="100" rows="33" type="text" class="UsersInputBox" id="newStudentList" ng-model="newStudentList"></textarea>');
        bigBox.append('<button class="button small" ng-click="replaceStudents(newStudentList)">Replace Student List</button>');
        bigBox.append('<button class="button small" ng-click="addStudents(newStudentList)">Add Students to List</button>');
        //ng-disabled="!isValidString(inviteInfo.id) "
        bigBox.append('</div>');//<button ng-disabled="!isValidString(inviteInfo.id) || !isValidString(inviteInfo.username)" ng-click="createInvite()">Create</button></div>');
        bigBox.append($compile(bigBox)($scope));
//submit does part of what is done by loadLegacy
        studentSectionContent.append(bigBox);
        studentSection.append(studentSectionContent);
    });
  
 */