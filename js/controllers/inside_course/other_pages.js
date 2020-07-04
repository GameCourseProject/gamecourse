// other pages inside a course, except for settings

app.controller('SpecificCourse', function($scope, $element, $stateParams, $compile) {
    $element.append($compile(Builder.createPageBlock({
        image: 'images/awards.svg',
        text: '{{courseName}}'
    }, function(el, info) {
    }))($scope));
});

app.controller('CourseUsersss', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    
    $scope.reduceList = function(){
        $("#empty_table").empty();
        $("#users-table").show();
        $scope.users = $scope.allUsers.slice();
        $scope.searchList();
        $scope.filterList();
        
    }

    $scope.searchList = function(){
        filteredUsers = [];
        text = $scope.search;
        if (validateSearch(text)){
            //match por name e short
            jQuery.each($scope.users , function( index ){
                user = $scope.users[index];
                if (user.name && user.name.toLowerCase().includes(text.toLowerCase())
                || user.nickname && user.nickname.toLowerCase().includes(text.toLowerCase())
                || user.studentNumber && user.studentNumber.toLowerCase().includes(text.toLowerCase())){
                    filteredUsers.push(user);
                }
            });
            if(filteredUsers.length == 0){
                $("#users-table").hide();
                $("#empty_table").append("No matches found");
            }
            $scope.users = filteredUsers;
        }
        
    }

    $scope.filterList = function(){
        activeRoles= []
        jQuery.each($scope.courseRoles, function(index){
            role = $scope.courseRoles[index];
            filtername = "filter" + role;
            if ($scope[filtername] == true){
                activeRoles.push(role);
            }
        });
        
        usersList = $scope.users;
        filteredUsers = [];
        jQuery.each(usersList , function( index ){
            user = usersList[index];
            found = user.roles.some(r => activeRoles.includes(r))
            if(found){
                filteredUsers.push(user)
            }
        });
        if (filteredUsers.length == 0){
            error_msg = "No matches found for your filter";
            $("#users-table").hide();
            $("#empty_table").append(error_msg);
        }
        $scope.users = filteredUsers;
    }


    //functions to visually change the "order by" arrows
    $scope.sortUp = function(){
        document.getElementById("triangle-up").classList.add("checked");
        document.getElementById("triangle-down").classList.remove("checked");
    }
    $scope.sortDown = function() {
        document.getElementById("triangle-down").classList.add("checked");
        document.getElementById("triangle-up").classList.remove("checked");
    }

    $scope.orderList = function(){
        order_by_id = $('input[type=radio]:checked', ".order-by")[0].id;
        order = getNameFromId(order_by_id);
        up = $("#triangle-up").hasClass("checked");

        if (up){ arrow = "up";}
        else{ arrow = "down";}

        if ($scope.lastOrder =="none" || $scope.lastOrder != order){
            switch (order){
                //default sort made with arrow down
                case "Name":
                    $scope.users.sort(orberByName);
                    $scope.allUsers.sort(orberByName);
                    break;
                case "Nickname":
                    $scope.users.sort(orberByNickname);
                    $scope.allUsers.sort(orberByNickname);
                    break;
                case "Student Number":
                    $scope.users.sort(orberByStudentNumber);
                    $scope.allUsers.sort(orberByStudentNumber);
                    break;
                case "Last Login":
                    $scope.users.sort(orberByLastLgin);
                    $scope.allUsers.sort(orberByLastLgin);
                    break;
            }
            if (up){ 
                $scope.users.reverse();
                $scope.allUsers.reverse();
            }

        }else{
            if (arrow ==  $scope.lastArrow){
                //nothing changes
                return;
            }
            else{
                //only the ascendent/descent order changed
                $scope.users.reverse();
                $scope.allUsers.reverse();
            }

        }

        //set values of the existing orderby
        $scope.lastOrder = order;
        $scope.lastArrow = arrow;
    }


    mainContent = $("<div id='mainContent'></div>");
    //sidebar
    
    $smartboards.request('core', 'courseRoles', {course : $scope.course}, function(data,err){
        $scope.courseRoles = [];
        jQuery.each(data.courseRoles, function(index){
            role = data.courseRoles[index];
            $scope.courseRoles.push(role.name);
            filtername = "filter" + role.name;
            $scope[filtername] = true;
        });
        console.log("finished");
        optionsFilter = $scope.courseRoles;
        optionsOrder = ["Name", "Nickname","Student Number","Last Login"];
        //start checkboxs checked, tied by the ng-model in each input
        sidebarAll = createSidebar( optionsFilter, optionsOrder);
        $compile(sidebarAll)($scope)
    });

    

    allUsers=$("<div id='allUsers'></div>")
    allUsersSection = $('<div class="data-table" ></div>');
    table = $('<table id="users-table"></table>');
    rowHeader = $("<tr></tr>");
    header = [{class: "name-column", content: "Name"},
              {class: "", content: "Nickname"},
              {class: "", content: "Student nº"},
              {class: "", content: "Last Login"},
              {class: "action-column", content: ""},
              {class: "action-column", content: ""},
            ];
    jQuery.each(header, function(index){
        rowHeader.append( $("<th class="+ header[index].class + ">" + header[index].content + "</th>"));
    });
  
    rowContent = $("<tr ng-repeat='(i, user) in users' id='user-{{user.id}}'> ></tr>");
    //<td class="name-column"><span>Jill</span> <div class="role_tag">Admin</div> <div class="role_tag">Teacher</div></td>
    nameRoleColumn = $('<td class="name-column"><span>{{user.name}}</span></td>')
    nameRoleColumn.append('<div ng-repeat="(i, role) in user.roles" class="role_tag">{{role}}</div>');
    
    rowContent.append(nameRoleColumn);
    rowContent.append('<td>{{user.nickname}}</td>');
    rowContent.append('<td>{{user.studentNumber}}</td>');
    rowContent.append('<td>{{user.lastLogin}}</td>');
    rowContent.append('<td class="action-column"><div class="icon edit_icon" value="#edit-user" onclick="openModal(this)" ng-click="modifyUser(user)"></div></td>');
    rowContent.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification-{{user.id}}" onclick="openModal(this)"></div></td>');

    //the verification modals
    modal = $("<div class='modal' id='delete-verification-{{user.id}}'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#delete-verification-{{user.id}}" onclick="closeModal(this)"></button>'));
    verification.append( $('<div class="warning">Are you sure you want to delete this User?</div>'));
    verification.append( $('<div class="target">{{user.name}} - {{user.studentNumber}}</div>'));
    verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#delete-verification-{{user.id}}" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="deleteUser(user)"> Delete</button></div>'))
    modal.append(verification);
    rowContent.append(modal);

    //append table
    table.append(rowHeader);
    table.append(rowContent);
    allUsersSection.append(table);
    allUsers.append(allUsersSection);


    //new user modal
    modal = $("<div class='modal' id='new-user'></div>");
    newUser = $("<div class='modal_content'></div>");
    newUser.append( $('<button class="close_btn icon" value="#new-user" onclick="closeModal(this)"></button>'));
    newUser.append( $('<div class="title">New User: </div>'));
    content = $('<div class="content">');
    box = $('<div class= "inputs">');
    row_inputs = $('<div class= "row_inputs"></div>');
    //image input
    row_inputs.append($('<div class="image smaller"><div class="profile_image"><span>Select a profile image</span></div><input type="file" class="form__input" id="profile_image" required="" /></div>'))
    //text inputs
    details = $('<div class="details bigger right"></div>')
    details.append($('<input type="text" class="form__input" id="name" placeholder="Name *" ng-model="newUser.userName"/> <label for="name" class="form__label">Name</label>'))
    details.append($('<input type="email" class="form__input" id="email" placeholder="Email *" ng-model="newUser.userEmail"/><label for="email" class="form__label">Email</label>'))
    details.append($('<input type="text" class="form__input" id="studentNumber" placeholder="Student Number *" ng-model="newUser.userStudentNumber"/><label for="studentNumber" class="form__label">Student Number</label>'))
    details.append($('<input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="newUser.userNickname"/><label for="nickname" class="form__label">Nickname</label>'))
    row_inputs.append(details);
    box.append(row_inputs);
    //on/off inputs
    row = $('<div class= "row"></div>');
    row.append( $('<div class= "on_off"><span>Admin </span><label class="switch"><input id="admin" type="checkbox" ng-model="newUser.userIsAdmin"><span class="slider round"></span></label></div>'))
    row.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="newUser.userIsActive"><span class="slider round"></span></label></div>'))
    box.append(row);
    content.append(box);
    content.append( $('<button class="save_btn" ng-click="submitUser()" ng-disabled="!isReadyToSubmit()" > Save </button>'))
    newUser.append(content);
    modal.append(newUser);
    allUsers.append(modal);


    //edit user modal
    editmodal = $("<div class='modal' id='edit-user'></div>");
    editUser = $("<div class='modal_content'></div>");
    editUser.append( $('<button class="close_btn icon" value="#edit-user" onclick="closeModal(this)"></button>'));
    editUser.append( $('<div class="title">New User: </div>'));
    editcontent = $('<div class="content">');
    editbox = $('<div id="edit_box" class= "inputs">');
    editrow_inputs = $('<div class= "row_inputs"></div>');
    //image input
    editrow_inputs.append($('<div class="image smaller"><div class="profile_image"></div><input type="file" class="form__input" id="profile_image" required="" /></div>'))
    //text inputs
    editdetails = $('<div class="details bigger right"></div>')
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="name" placeholder="Name *" ng-model="editUser.userName"/> <label for="name" class="form__label">Name</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="email" placeholder="Email *" ng-model="editUser.userEmail"/><label for="email" class="form__label">Email</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="studentNumber" placeholder="Student Number *" ng-model="editUser.userStudentNumber"/><label for="studentNumber" class="form__label">Student Number</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="editUser.userNickname"/><label for="nickname" class="form__label">Nickname</label></div>'))
    editrow_inputs.append(editdetails);
    editbox.append(editrow_inputs);
    editcontent.append(editbox);
    editcontent.append( $('<button class="save_btn" ng-click="submitEditUser()" ng-disabled="!isReadyToEdit()" > Save </button>'))
    editUser.append(editcontent);
    editmodal.append(editUser);
    allUsers.append(editmodal);




    //error section
    allUsers.append( $("<div class='error_box'><div id='empty_table' class='error_msg'></div></div>"));
    //success section
    allUsers.append( $("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

    //action buttons
    action_buttons = $("<div class='action-buttons'></div>");
    action_buttons.append( $("<div class='icon add_icon' value='#new-user' onclick='openModal(this)' ng-click='createUser()'></div>"));
    action_buttons.append( $("<div class='icon import_icon'></div>"));
    action_buttons.append( $("<div class='icon export_icon'></div>"));
    allUsers.append($compile(action_buttons)($scope));


    
    mainContent.append(allUsers);
    $compile(mainContent)($scope);


    //filter vai fazer este pedido com diferentes role definidos
    $smartboards.request('core', 'courseUsers', {course : $scope.course, role: "allRoles"}, function(data,err){
        role = "allRoles"

        $scope.users = data.userList.slice();
        $scope.allUsers = data.userList.slice();

        // userTabContents=[];
        // for (var st in data.userList){
        //     //fazer funcao para o tempo - 2 days, 30min, now, etc
        //     userTabContents.push({ID: data.userList[st].id,
        //                         Name:data.userList[st].name,
        //                         Username:data.userList[st].username,
        //                         Last_Login: data.userList[st].last_login});
        //         // "":{id: data.userList[st].id}});
        // }
        // var columns = ["ID","Name","Username","Last_Login"];
        // $scope.newList=data.file;

        // $scope.data = data;
        // var tabContent = $(mainContent);
        // var configurationSection = createSection(tabContent, 'Users List');
    
        
        // var configSectionContent = $('<div>',{'class': 'row'});
        
        // var table = Builder.buildTable(userTabContents, columns,true);
            
        // var tableArea = $('<div>',{'class': 'column', style: 'float: left; width: 40%;' });
        // tableArea.append(table);
        // tableArea.append('</div>');
        // configSectionContent.append(tableArea);

        // configurationSection.append(configSectionContent);

        $element.append(sidebarAll);
        $element.append(mainContent);
        $scope.lastOrder = "none";
        $scope.lastArrow = "none";
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

