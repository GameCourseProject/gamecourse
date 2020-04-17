// other pages inside a course, except for settings

app.controller('SpecificCourse', function($scope, $element, $stateParams, $compile) {
    $element.append($compile(Builder.createPageBlock({
        image: 'images/awards.svg',
        text: '{{courseName}}'
    }, function(el, info) {
    }))($scope));
});

app.controller('CourseUsersss', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    
    $smartboards.request('settings', 'courseUsers', {course : $scope.course, role: "allRoles"}, function(data,err){
        role = "allRoles"
        console.log(data);
        userTabContents=[];
        for (var st in data.userList){
            //fazer funcao para o tempo - 2 days, 30min, now, etc
            userTabContents.push({ID: data.userList[st].id,
                                Name:data.userList[st].name,
                                Username:data.userList[st].username,
                                Last_Login: data.userList[st].last_login});
                // "":{id: data.userList[st].id}});
        }
        var columns = ["ID","Name","Username","Last_Login"];
        $scope.newList=data.file;

        $scope.data = data;
        var tabContent = $($element);
        var configurationSection = createSection(tabContent, 'Users List');
    
        
        var configSectionContent = $('<div>',{'class': 'row'});
        
        var table = Builder.buildTable(userTabContents, columns,true);
            
        var tableArea = $('<div>',{'class': 'column', style: 'float: left; width: 40%;' });
        tableArea.append(table);
        tableArea.append('</div>');
        configSectionContent.append(tableArea);

        configurationSection.append(configSectionContent);
    });

});

//not used anymore
// app.controller('CourseTeacherSettingsController',function($scope, $stateParams, $element, $smartboards, $compile, $parse){
//     $scope.replaceData = function(arg) {
//         replaceUsers(arg,"Teacher", $smartboards, $scope);
//     };
//     $scope.addData = function(arg) {//Currently not being used
//         addUsers(arg,"Teacher", $smartboards, $scope);
//     };
//     $scope.deleteData = function(arg) {//currently not being used
//         deleteUser(arg, $smartboards, $scope);
//     };
//     $scope.clearData = function(){
//         clearFillBox($scope);
//     };
    
//     $smartboards.request('settings', 'courseUsers', {course : $scope.course, role: "Teacher"}, function(data,err){
//         userSettings("Teacher",data,err,$scope,$element,$compile);
//     });
// });

// app.controller('CourseStudentSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
//     $scope.replaceData = function(arg) {
//         replaceUsers(arg,"Student", $smartboards, $scope);
//     };
//     $scope.addData = function(arg) {//Currently not being used
//         addUsers(arg,"Student", $smartboards, $scope);
//     };
//     $scope.deleteData = function(arg) {//Currently not being used
//         deleteUser(arg, $smartboards, $scope);
//     };
//     $scope.clearData = function(){
//         clearFillBox($scope);
//     };
    
//     $smartboards.request('settings', 'courseUsers', {course : $scope.course, role: "Student"}, function(data,err){
//         console.log(data);
//         userSettings("Student",data,err,$scope,$element,$compile);
//     });
// });

//the following are not currently used
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

