// other pages inside a course, except for settings

app.controller('SpecificCourse', function($scope, $element, $stateParams, $compile) {
    $element.append($compile(Builder.createPageBlock({
        image: 'images/awards.svg',
        text: '{{courseName}}'
    }, function(el, info) {
    }))($scope));
});

app.controller('CourseUsersss', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    
    $scope.deleteUser = function(user) {
        $("#action_completed").empty();
        $smartboards.request('course', 'removeUser', {user_id: user.id, course: $scope.course }, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            getUsers();
            $("#action_completed").append("User: " + user.name +"-" + user.studentNumber + " removed from this course");
            $("#action_completed").show().delay(3000).fadeOut();
        });
    };

    //place initial form of modal to choose how to add a user
    resetAddUserModal = function(){
        modal = $("#add-user");
        modal.empty();
        modal_content = $("<div class='modal_content'></div>");
        modal_content.append( $('<button class="close_btn icon" value="#add-user" onclick="closeModal(this)"></button>'));
        modal_content.append( $('<div class="title centered" >How do you want to add a new user? </div>'));
        content = $('<div class="content options">');
        opt1 = $('<div class="option" ng-click="createUser()"></div>');
        opt1.append($('<div class="icon" id="choose_create_new_user"> </div> '));
        opt1.append($('<div class="description">Create a new user</div>'));
        opt2 = $('<div class="option" ng-click="selectUsers()"></div>');
        opt2.append($('<div class="icon" id="choose_select_user"> </div> '));
        opt2.append($('<div class="description">Select existing user</div>'));
        content.append(opt1);
        content.append(opt2);
        modal_content.append(content);
        modal.append(modal_content);
        $compile(modal)($scope);
    }

    //redo sctions for adding existing user
    updateSelectUsersSection = function($scope, selectedUser){
        $scope.selectedUser.find="";
        $("#selected_users").empty();

        selected = $("#selected_users");
        jQuery.each(selectedUser , function( index ) {
            user = selectedUser[index];
            selected.append($('<div  ng-click="removeUser('+user.id+')" class="user_tag">'+user.name+'</div>'));
        });
        selected.append($('<input id="select_system_user" type="text" placeholder="Search.." ng-model="selectedUser.find" ng-change="searchUser()"></input>'))
        
        $compile(selected)($scope);
    }
    updateRemainingUsersSection = function($scope, remianingUsers){
        $("#system_users").empty();

        systemUsers= $("#system_users");
        jQuery.each(remianingUsers , function( index ) {
            user = remianingUsers[index];
            systemUsers.append($('<div class="line"><div>' + user.name + '-' + user.studentNumber + '</div><div class="add_icon_no_outline icon" ng-click="addUser('+user.id+')"></div></div>'));
        });
        
        $compile(systemUsers)($scope);
    }

    //add an existing user
    $scope.selectUsers = function(){
        $scope.selectedUser = {};
        $scope.selectedUser.users=[];
        $scope.selectedUser.role="";
        $scope.selectedUser.find="";

        var reqData = { course: $scope.course,};

        $scope.searchUser = function(){
            filteredUsers = [];
            text = $scope.selectedUser.find;

            if (validateSearch(text)){
                //match por name e short
                jQuery.each($scope.remianingUsers , function( index ){
                    user = $scope.remianingUsers[index];
                    if (user.name && user.name.toLowerCase().includes(text.toLowerCase())
                    || user.studentNumber && user.studentNumber.toLowerCase().includes(text.toLowerCase())){
                        filteredUsers.push(user);
                    }
                });
                updateRemainingUsersSection($scope, filteredUsers);
            }
            else{
                updateRemainingUsersSection($scope, $scope.remianingUsers);
            }
            
        }
        $scope.addUser = function(user_id){
            user = $scope.remianingUsers.find(el => el.id == user_id);
            $scope.selectedUser.users.push(user);

            array = $scope.remianingUsers;
            index = $scope.remianingUsers.indexOf(user);
            array.splice(index, 1);

            updateSelectUsersSection($scope, $scope.selectedUser.users);
            updateRemainingUsersSection($scope, $scope.remianingUsers);
        }
        $scope.removeUser = function(user_id){
            user = $scope.selectedUser.users.find(el => el.id == user_id);
            $scope.remianingUsers.push(user);

            array = $scope.selectedUser.users;
            index = $scope.selectedUser.users.indexOf(user);
            array.splice(index, 1);

            updateSelectUsersSection($scope, $scope.selectedUser.users);
            updateRemainingUsersSection($scope, $scope.remianingUsers);
        }
        $scope.isReadyToSubmit = function() {
            
            //validate inputs
            if ($scope.selectedUser.users.length !=0 &&
                $scope.selectedUser.role != ''){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitUsers = function() {
            var reqData = {
                course: $scope.course,
                role: $scope.selectedUser.role,
                users: $scope.selectedUser.users
            };
            $smartboards.request('course', 'addUser', reqData, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }
                $("#add-user").hide();
                getUsers();
                $("#action_completed").append("New User(s) added");
                $("#action_completed").show().delay(3000).fadeOut();
                resetAddUserModal();
            });
        };

        
        modal = $("#add-user");
        modal.empty();
        Users = $("<div class='modal_content'></div>");
        Users.append( $('<button class="close_btn icon" value="#add-user" onclick="closeModal(this); resetAddUserModal();"></button>'));
        Users.append( $('<div class="title">Select Users: </div>'));
        content = $('<div class="content">');
        box = $('<div id="new_box" class= "inputs">');
        
        selected = $('<div class="search_box" id="selected_users"></div>')
        systemUsers= $('<div class="select_box" id="system_users"></div>')
        

        add_row = $('<div id="add_roles" style="margin-bottom: 0px;"></div>')
        add_row.append($('<span style="margin-right: 10px;"> Role: </span>'))
        add_role = $('<select id="roles" class="form__input" name="roles" ng-model="selectedUser.role">');
        add_role.append($('<option value="" disabled selected>Select a role</option>'));
        jQuery.each($scope.courseRoles , function( index ) {
            role = $scope.courseRoles[index];
            add_role.append($('<option value="'+ role +'">'+ role +'</option>'));
        });
        add_row.append(add_role);
        
        box.append(selected);
        box.append(systemUsers);
        box.append(add_row);
        
        content.append(box);
        content.append( $('<button class="save_btn" ng-click="submitUsers()" ng-disabled="!isReadyToSubmit()" > Save </button>'))
        Users.append(content);
        modal.append(Users);
        $compile(modal)($scope);

        $smartboards.request('course', 'notCourseUsers', reqData, function(data, err) {
            if (err) {
                console.log(err.description);
                return;
            }
    
            $scope.remianingUsers = Object.values(data.notCourseUsers);
            updateSelectUsersSection($scope, $scope.selectedUser.users);
            updateRemainingUsersSection($scope, $scope.remianingUsers);
        });

       

    }

    //create a new user
    $scope.createUser = function(){
        
        //replace modal content
        modal = $("#add-user");
        modal.empty();
        newUser = $("<div class='modal_content'></div>");
        newUser.append( $('<button class="close_btn icon" value="#add-user" onclick="closeModal(this); resetAddUserModal();"></button>'));
        newUser.append( $('<div class="title">New User: </div>'));
        content = $('<div class="content">');
        box = $('<div id="new_box" class= "inputs">');
        row_inputs = $('<div class= "row_inputs"></div>');
        //image input
        row_inputs.append($('<div class="image smaller"><div class="profile_image"><span>Select a profile image</span></div><input type="file" class="form__input" id="profile_image" required="" /></div>'))
        //text inputs
        details = $('<div class="details bigger right"></div>')
        details.append($('<div class="container" ><input type="text" class="form__input" id="name" placeholder="Name *" ng-model="newUser.userName"/> <label for="name" class="form__label">Name</label></div>'))
        details.append($('<div class="container" ><input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="newUser.userNickname"/><label for="nickname" class="form__label">Nickname</label></div>'))
        details.append($('<div class="container" ><input type="text" class="form__input" id="email" placeholder="Email *" ng-model="newUser.userEmail"/><label for="email" class="form__label">Email</label></div>'))
        doubledetails = $('<div class="container" >')
        doubledetails.append( $('<div class="details bigger"><div class="container" ><input type="text" class="form__input" id="studentNumber" placeholder="Student Number *" ng-model="newUser.userStudentNumber"/><label for="studentNumber" class="form__label">Student Number</label></div></div>'))
        doubledetails.append( $('<div class="details smaller right"><div class="container" ><input type="text" class="form__input" id="campus" placeholder="Campus *" ng-model="newUser.userCampus"/><label for="campus" class="form__label">Campus</label></div></div>'))
        details.append(doubledetails);
        row_inputs.append(details);
        box.append(row_inputs);
        // authentication information - service and username
        row_auth = $('<div class= "row_inputs"></div>');
        selectAuth = $('<div class="smaller">');
        select = $('<select id="authService" class="form__input" name="authService" ng-model="newUser.userAuthService"></select>');
        select.append($('<option value="" disabled selected>Auth Service</option>'));
        optionsAuth = ["fenix", "google", "facebook", "linkedin"];
        jQuery.each(optionsAuth, function( index ){
            option = optionsAuth[index];
            select.append($('<option value="'+option+'">'+option+'</option>'))
        });
        selectAuth.append(select);
        row_auth.append(selectAuth);
        row_auth.append($('<div class="details bigger right"><div class="container"><input type="text" class="form__input" id="username" placeholder="Username *" ng-model="newUser.userUsername"/> <label for="username" class="form__label">Username</label></div></div>'))
        box.append(row_auth);
        content.append(box);
        content.append( $('<button class="save_btn" ng-click="submitUser()" ng-disabled="!isReadyToSubmit()" > Save </button>'))
        newUser.append(content);
        modal.append(newUser);
        $compile(modal)($scope);


        $("#action_completed").empty();
        $scope.newUser = {};
        $scope.newUser.userRoles = [];

        updateRolesAddSection($scope, "#new_box", $scope.courseRoles, $scope.newUser.userRoles);

        $scope.addRole = function(){
            selector = $("#roles")[0];
            role = selector.options[selector.selectedIndex].value;
            $scope.newUser.userRoles.push(role);
            updateRolesAddSection($scope, "#new_box", $scope.courseRoles, $scope.newUser.userRoles);
        }
        $scope.removeRole = function(role){
            array = $scope.newUser.userRoles;
            index = $scope.newUser.userRoles.indexOf(role);
            array.splice(index, 1);
            //scope array keeps info
            updateRolesAddSection($scope, "#new_box", $scope.courseRoles, $scope.newUser.userRoles);
        }
        

        $scope.isReadyToSubmit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            
            //validate inputs
            if (isValid($scope.newUser.userName) &&
            isValid($scope.newUser.userStudentNumber) &&
            isValid($scope.newUser.userEmail) &&  
            isValid($scope.newUser.userUsername) &&             
            isValid($scope.newUser.userAuthService) &&
            isValid($scope.newUser.userCampus) && 
            $scope.newUser.userRoles.length != 0){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitUser = function() {
            var reqData = {
                course: $scope.course,
                userName: $scope.newUser.userName,
                userStudentNumber: $scope.newUser.userStudentNumber,
                userNickname: $scope.newUser.userNickname,
                userEmail: $scope.newUser.userEmail,
                userRoles: $scope.newUser.userRoles,
                userCampus: $scope.newUser.userCampus,
                userUsername: $scope.newUser.userUsername,
                userAuthService: $scope.newUser.userAuthService
            };
            $smartboards.request('course', 'createUser', reqData, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }
                $("#add-user").hide();
                getUsers();
                $("#action_completed").append("New User created");
                $("#action_completed").show().delay(3000).fadeOut();
                resetAddUserModal();
            });
        };
    }
    
    updateRolesAddSection = function($scope, box, allRoles, userRoles){
        $("#roles_list").remove();
        $("#add_roles").remove();
        editbox = $(box);

        remianingRoles = allRoles.filter( ( el ) => !userRoles.includes( el ) );

        add_row = $('<div id="add_roles"></div>')
        add_row.append($('<span style="margin-right: 10px;">Add Role: </span>'))
        if(remianingRoles.length == 0){
            add_row.append($('<span style="color: lightgray;"> All roles selected </span>'))
        }
        else{
            add_role = $('<select id="roles" class="form__input" name="roles">')
            jQuery.each(remianingRoles , function( index ) {
                role = remianingRoles[index];
                add_role.append($('<option value="'+ role +'">'+ role +'</option>'));
            });
            add_row.append(add_role);
            add_row.append($('<button ng-click="addRole()">Add</button>'));
        }
        
        editbox.append(add_row);

        roles_row = $('<div id="roles_list"><span style="margin-right: 10px;">Roles: </span></div>')
        jQuery.each(userRoles , function( index ) {
            role = userRoles[index];
            roles_row.append($('<div  ng-click="removeRole(\''+role+'\')" class="role_tag">'+role+'</div>'));
        });
        editbox.append(roles_row);
        
        $compile(editbox)($scope);
    }

    $scope.modifyUser = function(user){
        $("#action_completed").empty();
        $("#active_visible_inputs").remove();
        $scope.editUser = {};
        $scope.editUser.userId = user.id;
        $scope.editUser.userName = user.name;
        $scope.editUser.userEmail = user.email;
        $scope.editUser.userStudentNumber = user.studentNumber;
        $scope.editUser.userNickname = user.nickname;
        $scope.editUser.userRoles = user.roles.slice();
        $scope.editUser.userCampus = user.campus;
        $scope.editUser.userUsername = user.username;
        $scope.editUser.userAuthService = user.authenticationService;
            
        updateRolesAddSection($scope,"#edit_box", $scope.courseRoles, user.roles);

        $scope.addRole = function(){
            selector = $("#roles")[0];
            role = selector.options[selector.selectedIndex].value;
            $scope.editUser.userRoles.push(role);
            updateRolesAddSection($scope,"#edit_box", $scope.courseRoles, $scope.editUser.userRoles);
        }
        $scope.removeRole = function(role){
            array = $scope.editUser.userRoles;
            index = $scope.editUser.userRoles.indexOf(role);
            array.splice(index, 1);
            //scope array keeps info
            updateRolesAddSection($scope,"#edit_box", $scope.courseRoles, $scope.editUser.userRoles);
        }

        $scope.isReadyToEdit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            //validate inputs
            if (isValid($scope.editUser.userName) &&
            isValid($scope.editUser.userEmail) &&
            isValid($scope.editUser.userStudentNumber) &&
            isValid($scope.editUser.userCampus) &&
            isValid($scope.editUser.userUsername) &&
            isValid($scope.editUser.userAuthService) &&
            $scope.editUser.userRoles.length != 0){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitEditUser = function() {
            var reqData = {
                course: $scope.course,
                userName: $scope.editUser.userName,
                userId: $scope.editUser.userId,
                userStudentNumber: $scope.editUser.userStudentNumber,
                userNickname: $scope.editUser.userNickname,
                userEmail: $scope.editUser.userEmail,
                userCampus: $scope.editUser.userCampus,
                userRoles: $scope.editUser.userRoles,
                userUsername: $scope.editUser.userUsername,
                userAuthService: $scope.editUser.userAuthService
            };
            $smartboards.request('course', 'editUser', reqData, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }
                $("#edit-user").hide();
                getUsers();
                $("#action_completed").append("User: "+ $scope.editUser.userName+"-"+ $scope.editUser.userStudentNumber + " edited");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        };
    }

    $scope.reduceList = function(){
        $("#empty_table").empty();
        $("#users-table").show();
        $scope.users = $scope.allUsers.slice();
        $scope.error_msg = '';
        $scope.searchList();
        $scope.filterList();
        if($scope.error_msg != ''){
            $("#users-table").hide();
            $("#empty_table").append($scope.error_msg);
        }
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
            $scope.users = filteredUsers;
            if(filteredUsers.length == 0){
                $scope.error_msg = "No matches found";
            }
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
        
        $scope.users = filteredUsers;
        if (filteredUsers.length == 0){
            $scope.error_msg = "No matches found for your filter";
        }
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
    $smartboards.request('course', 'courseRoles', {course : $scope.course}, function(data,err){
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
        sidebarAll = createSidebar( optionsFilter, optionsOrder);
        $compile(sidebarAll)($scope)
    });

    allUsers=$("<div id='allUsers'></div>")
    allUsersSection = $('<div class="data-table" ></div>');
    table = $('<table id="users-table"></table>');
    rowHeader = $("<tr></tr>");
    header = [{class: "name-column", content: "Name"},
              {class: "", content: "Nickname"},
              {class: "", content: "Campus"},
              {class: "", content: "Student nº"},
              {class: "", content: "Last Login"},
              {class: "action-column", content: ""},
              {class: "action-column", content: ""},
            ];
    jQuery.each(header, function(index){
        rowHeader.append( $("<th class="+ header[index].class + ">" + header[index].content + "</th>"));
    });
  
    rowContent = $("<tr ng-repeat='(i, user) in users' id='user-{{user.id}}'> ></tr>");
    nameRoleColumn = $('<td class="name-column"><span>{{user.name}}</span></td>')
    nameRoleColumn.append('<div ng-repeat="(i, role) in user.roles" class="role_tag">{{role}}</div>');

    rowContent.append(nameRoleColumn);
    rowContent.append('<td>{{user.nickname}}</td>');
    rowContent.append('<td>{{user.campus}}</td>');
    rowContent.append('<td>{{user.studentNumber}}</td>');
    rowContent.append('<td>{{user.lastLogin}}</td>');
    rowContent.append('<td class="action-column"><div class="icon edit_icon" value="#edit-user" onclick="openModal(this)" ng-click="modifyUser(user)"></div></td>');
    rowContent.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification-{{user.id}}" onclick="openModal(this)"></div></td>');

    //the verification modals
    modal = $("<div class='modal' id='delete-verification-{{user.id}}'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#delete-verification-{{user.id}}" onclick="closeModal(this)"></button>'));
    verification.append( $('<div class="warning">Are you sure you want to remove this User from this course?</div>'));
    verification.append( $('<div class="target">{{user.name}} - {{user.studentNumber}}</div>'));
    verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#delete-verification-{{user.id}}" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="deleteUser(user)"> Remove</button></div>'))
    modal.append(verification);
    rowContent.append(modal);

    //append table
    table.append(rowHeader);
    table.append(rowContent);
    allUsersSection.append(table);
    allUsers.append(allUsersSection);


    //add user modal
    modal = $("<div class='modal' id='add-user'></div>");
    modal_content = $("<div class='modal_content'></div>");
    modal_content.append( $('<button class="close_btn icon" value="#add-user" onclick="closeModal(this)"></button>'));
    modal_content.append( $('<div class="title centered" >How do you want to add a new user? </div>'));
    content = $('<div class="content options">');
    opt1 = $('<div class="option" ng-click="createUser()"></div>');
    opt1.append($('<div class="icon" id="choose_create_new_user"> </div> '));
    opt1.append($('<div class="description">Create a new user</div>'));
    opt2 = $('<div class="option" ng-click="selectUsers()"></div>');
    opt2.append($('<div class="icon" id="choose_select_user"> </div> '));
    opt2.append($('<div class="description">Select existing user</div>'));
    content.append(opt1);
    content.append(opt2);
    modal_content.append(content);
    modal.append(modal_content);
    allUsers.append(modal);

    //edit user modal
    editmodal = $("<div class='modal' id='edit-user'></div>");
    editUser = $("<div class='modal_content'></div>");
    editUser.append( $('<button class="close_btn icon" value="#edit-user" onclick="closeModal(this)"></button>'));
    editUser.append( $('<div class="title">Edit User: </div>'));
    editcontent = $('<div class="content">');
    editbox = $('<div id="edit_box" class= "inputs">');
    editrow_inputs = $('<div class= "row_inputs"></div>');
    //image input
    editrow_inputs.append($('<div class="image smaller"><div class="profile_image"></div><input type="file" class="form__input" id="profile_image" required="" /></div>'))
    //text inputs
    editdetails = $('<div class="details bigger right"></div>')
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="name" placeholder="Name *" ng-model="editUser.userName"/> <label for="name" class="form__label">Name</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="editUser.userNickname"/><label for="nickname" class="form__label">Nickname</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="email" placeholder="Email *" ng-model="editUser.userEmail"/><label for="email" class="form__label">Email</label></div>'))
    editdoubledetails = $('<div class="container" >')
    editdoubledetails.append( $('<div class="details bigger"><div class="container" ><input type="text" class="form__input" id="studentNumber" placeholder="Student Number *" ng-model="editUser.userStudentNumber"/><label for="studentNumber" class="form__label">Student Number</label></div></div>'))
    editdoubledetails.append( $('<div class="details smaller right"><div class="container" ><input type="text" class="form__input" id="campus" placeholder="Campus *" ng-model="editUser.userCampus"/><label for="campus" class="form__label">Campus</label></div></div>'))
    editdetails.append(editdoubledetails)
    editrow_inputs.append(editdetails);
    editbox.append(editrow_inputs);
    // authentication information - service and username
    row_auth = $('<div class= "row_inputs"></div>');
    selectAuth = $('<div class="smaller">');
    select = $('<select id="authService" class="form__input" name="authService" ng-model="editUser.userAuthService"></select>');
    select.append($('<option value="" disabled selected>Auth Service</option>'));
    optionsAuth = ["fenix", "google", "facebook", "linkedin"];
    jQuery.each(optionsAuth, function( index ){
        option = optionsAuth[index];
        select.append($('<option value="'+option+'">'+option+'</option>'))
    });
    selectAuth.append(select);
    row_auth.append(selectAuth);
    row_auth.append($('<div class="details bigger right"><div class="container"><input type="text" class="form__input" id="username" placeholder="Username *" ng-model="editUser.userUsername"/> <label for="username" class="form__label">Username</label></div></div>'))
    editbox.append(row_auth);
    editcontent.append(editbox);
    editcontent.append( $('<button class="save_btn" ng-click="submitEditUser()" ng-disabled="!isReadyToEdit()" > Save </button>'))
    editUser.append(editcontent);
    editmodal.append(editUser);
    allUsers.append(editmodal);

    //choose how to add user modal
    


    //error section
    allUsers.append( $("<div class='error_box'><div id='empty_table' class='error_msg'></div></div>"));
    //success section
    mainContent.append( $("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

    //action buttons
    action_buttons = $("<div class='action-buttons'></div>");
    action_buttons.append( $("<div class='icon add_icon' value='#add-user' onclick='openModal(this)'></div>"));
    action_buttons.append( $("<div class='icon import_icon'></div>"));
    action_buttons.append( $("<div class='icon export_icon'></div>"));
    mainContent.append($compile(action_buttons)($scope));


    mainContent.append(allUsers);
    $compile(mainContent)($scope);


    //filter vai fazer este pedido com diferentes role definidos
    getUsers = function() {
            $smartboards.request('course', 'courseUsers', {course : $scope.course, role: "allRoles"}, function(data,err){
            role = "allRoles"

            $scope.users = data.userList.slice();
            $scope.allUsers = data.userList.slice();

            $element.append(sidebarAll);
            $element.append(mainContent);
            $scope.lastOrder = "none";
            $scope.lastArrow = "none";
            $scope.orderList();
            $scope.reduceList();
        });
    };
    getUsers();

});
