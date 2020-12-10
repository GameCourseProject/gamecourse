
app.controller('Users', function($scope, $state, $compile, $smartboards, $element) {

    $scope.deleteUser = function(user) {
        $("#action_completed").empty();
        $smartboards.request('core', 'deleteUser', {user_id: user.id}, function(data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            getUsers();
            $("#action_completed").append("User: " + user.name +"-" + user.studentNumber + " deleted");
            $("#action_completed").show().delay(3000).fadeOut();
        });
    };

    $scope.adminUser = function( user_id){
        id = "#admin-" + user_id + ":checked";
        if($(id).length > 0){
            $admin = 0; //false
        }
        else{
            $admin = 1; //true
        }
        $smartboards.request('core', 'setUserAdmin', {user_id: user_id, isAdmin: $admin}, function(data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            $scope.users.find(x => x.id === user_id).isAdmin = $admin;
            $scope.allUsers.find(x => x.id === user_id).isAdmin = $admin;
        });
    }

    $scope.activeUser = function( user_id){
        id = "#active-" + user_id + ":checked"; 
        if($(id).length > 0){
            $active = 0; //false
        }
        else{
            $active = 1; //true
        }
        $smartboards.request('core', 'setUserActive', {user_id: user_id, isActive: $active}, function(data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            $scope.users.find(x => x.id === user_id).isActive = $active;
            $scope.allUsers.find(x => x.id === user_id).isActive = $active;
        });
    }
    $scope.createUser = function(){
        $("#action_completed").empty();
        $scope.newUser = {};
        //inputs start not checked
        $scope.newUser.userIsActive = false;
        $scope.newUser.userIsAdmin = false;
        $scope.newUser.userImage = null;
        $scope.newUser.userHasImage = "false";


        var imageInput = document.getElementById('profile_image');
        var imageDisplayArea = document.getElementById('display_profile_image'); //ver este limpar tem de ter o span
        imageDisplayArea.innerHTML = "";
        $('#display_profile_image').append($('<span>Select a profile image</span>'));

		imageInput.addEventListener('change', function(e) {
			var file = imageInput.files[0];
			var imageType = /image.*/;

			if (file.type.match(imageType)) {
				var reader = new FileReader();

				reader.onload = function(e) {
					imageDisplayArea.innerHTML = "";

					var img = new Image();
                    img.src = reader.result;
                    $scope.newUser.userImage = reader.result;
                    $scope.newUser.userHasImage = "true";
                    imageDisplayArea.appendChild(img);
				}

                reader.readAsDataURL(file);	
                
			} else {
                $('#display_profile_image').empty();
                $('#display_profile_image').append($("<span>Please choose a valid type of file (hint: image)</span>"));
                $scope.newUser.userImage = null;
                $scope.newUser.userHasImage = "false";
            }
		});


        $scope.isReadyToSubmit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            //validate inputs
            if (isValid($scope.newUser.userName) &&
            isValid($scope.newUser.userStudentNumber) &&
            isValid($scope.newUser.userEmail) &&
            isValid($scope.newUser.userAuthService) &&
            isValid($scope.newUser.userUsername)){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitUser = function() {
            isActive = $scope.newUser.userIsActive ? 1 : 0; //to transform from true-false
            isAdmin = $scope.newUser.userIsAdmin ? 1 : 0; //same
            var reqData = {
                userName: $scope.newUser.userName,
                userStudentNumber: $scope.newUser.userStudentNumber,
                userNickname: $scope.newUser.userNickname,
                userUsername: $scope.newUser.userUsername,
                userEmail: $scope.newUser.userEmail,
                userIsActive: isActive,
                userIsAdmin: isAdmin,
                userAuthService: $scope.newUser.userAuthService,
                userImage: $scope.newUser.userImage,
                userHasImage: $scope.newUser.userHasImage
            };
            $smartboards.request('core', 'createUser', reqData, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $("#new-user").hide();
                //set profile image to initial state
                $('#display_profile_image').empty();
                $('#display_profile_image').append($("<span>Select a profile image</span>"));
                
                getUsers();
                $("#action_completed").append("New User created");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        };
    }
    $scope.modifyUser = function(user){
        $("#action_completed").empty();
        $("#active_visible_inputs").remove();
        $("#courses_list").remove();
        $scope.editUser = {};
        $scope.editUser.userId = user.id;
        $scope.editUser.userName = user.name;
        $scope.editUser.userEmail = user.email;
        $scope.editUser.userStudentNumber = user.studentNumber;
        $scope.editUser.userNickname = user.nickname;
        $scope.editUser.userUsername = user.username;
        $scope.editUser.userAuthService = user.authenticationService;
        $scope.editUser.userImage = null;
        $scope.editUser.userHasImage = "false";
        
                
        editbox = $("#edit_box");
        //list of courses
        courses_row = $('<div id="courses_list"><span style="margin-right: 10px;">Courses: </span></div>')
        jQuery.each(user.courses , function( index ) {
            course = user.courses[index];
            courses_row.append($('<div class="course_tag">'+course.name+'</div>'));
        });
        editbox.append(courses_row);
        //on/off inputs
        editrow = $('<div class= "row" id="active_visible_inputs"></div>');
        if (user.isAdmin == true){
            editrow.append( $('<div class= "on_off"><span>Admin </span><label class="switch"><input id="admin" type="checkbox" ng-model="editUser.userIsAdmin" checked><span class="slider round"></span></label></div>'))
            $scope.editUser.userIsAdmin = true;
            console.log("is admin");
        }
        else{
            editrow.append( $('<div class= "on_off"><span>Admin </span><label class="switch"><input id="admin" type="checkbox" ng-model="editUser.userIsAdmin" ><span class="slider round"></span></label></div>'))
            $scope.editUser.userIsAdmin = false;
        }
        if (user.isActive == true){
            editrow.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="editUser.userIsActive" checked><span class="slider round"></span></label></div>'));
            $scope.editUser.userIsActive = true;
            console.log("is active");
        }
        else{
            editrow.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="editUser.userIsActive"><span class="slider round"></span></label></div>'));
            $scope.editUser.userIsActive = false;
        }
        editbox.append(editrow);
        $compile(editbox)($scope);
        

        var imageInput = document.getElementById('edit_profile_image');
        var imageDisplayArea = document.getElementById('edit_display_profile_image');
        imageDisplayArea.innerHTML = "";

        //set initial image
        var profile_image = new Image();
        profile_image.onload = function() {
            imageDisplayArea.appendChild(profile_image);
        }
        profile_image.onerror = function() {
            $('#edit_display_profile_image').append($('<span>Select a profile image</span>'));
        }
        profile_image.src = 'photos/'+ user.id +'.png?'+ new Date().getTime();
                
        //set listener for input change
        imageInput.addEventListener('change', function(e) {
            var file = imageInput.files[0];
            var imageType = /image.*/;

            if (file.type.match(imageType)) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    imageDisplayArea.innerHTML = "";

                    var img = new Image();
                    img.src = reader.result;
                    $scope.editUser.userImage = reader.result;
                    $scope.editUser.userHasImage = "true";
                    imageDisplayArea.appendChild(img);
                }

                reader.readAsDataURL(file);	
                
            } else {
                $('#display_profile_image').empty();
                $('#display_profile_image').append($("<span>Please choose a valid type of file (hint: image)</span>"));
                $scope.editUser.userImage = null;
                $scope.editUser.userHasImage = "false";
            }
        });


        $scope.isReadyToEdit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            //validate inputs
            if (isValid($scope.editUser.userName) &&
            isValid($scope.editUser.userEmail) &&
            isValid($scope.editUser.userStudentNumber) &&
            isValid($scope.editUser.userUsername) &&
            isValid($scope.editUser.userAuthService)){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitEditUser = function() {
            isActive = $scope.editUser.userIsActive ? 1 : 0;
            isAdmin = $scope.editUser.userIsAdmin ? 1 : 0;
            var reqData = {
                userName: $scope.editUser.userName,
                userId: $scope.editUser.userId,
                userStudentNumber: $scope.editUser.userStudentNumber,
                userNickname: $scope.editUser.userNickname,
                userUsername:  $scope.editUser.userUsername,
                userEmail: $scope.editUser.userEmail,
                userIsActive: isActive,
                userIsAdmin: isAdmin,
                userAuthService: $scope.editUser.userAuthService,
                userImage: $scope.editUser.userImage,
                userHasImage: $scope.editUser.userHasImage
            };
            $smartboards.request('core', 'editUser', reqData, function(data, err) {
                if (err) {
                    giveMessage(err.description);
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
        active = $scope.filterActive;
        inactive = $scope.filterInactive;
        admin = $scope.filterAdmin;
        nonAdmin = $scope.filterNonAdmin;

        //reset list of courses
        usersList = $scope.users;
        filteredUsers = [];
        error_msg = "";

        //cases of empty result
        if (!active && !inactive){
            error_msg = "You must select at least one of the options: Active or Inactive"
        }
        else if(!admin & !nonAdmin){
            error_msg = "You must select at least one of the options: Admin or NonAdmin"
        }
        else if(active && inactive && admin & nonAdmin){
            filteredUsers = usersList;
        }
        else{
            jQuery.each(usersList , function( index ) {
                user = usersList[index];
                validA = false;
                validV = false;
                if (user.isActive == true && active){
                    validA = true;
                }
                else if ( user.isActive == false && inactive){
                    validA = true;
                }
                if (validA && user.isAdmin == true && admin){
                    validV = true;
                }
                else if(validA && user.isAdmin == false && nonAdmin){
                    validV = true;
                }

                if (validA && validV){
                    filteredUsers.push(user);
                }
            });
        
            if (filteredUsers.length == 0){
                error_msg = "No matches found for your filter"
            }
        }
        if(error_msg != ""){
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
                case "N Courses":
                    $scope.users.sort(orberByNCourses);
                    $scope.allUsers.sort(orberByNCourses);
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
    $scope.importUsers = function(replace){
        $scope.importedUsers = null;
        $scope.replaceUsers = replace;
        console.log($scope.replaceUsers);
        var fileInput = document.getElementById('import_user');
        var file = fileInput.files[0];

        var reader = new FileReader();

        reader.onload = function(e) {
            $scope.importedUsers = reader.result;
            $smartboards.request('core', 'importUser', { file: $scope.importedUsers, replace: $scope.replaceUsers }, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                nUsers = data.nUsers;
                $("#import-user").hide();
                getUsers();
                fileInput.value = "";
                $("#action_completed").empty();
                $("#action_completed").append(nUsers + " Users Imported");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        }
        reader.readAsDataURL(file);	
        
    }
    $scope.exportUsers = function(){
        $smartboards.request('core', 'exportUsers', { }, function(data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            download("users.csv", data.users);
        });
        
    }

    mainContent = $("<div id='mainContent'></div>");

    //sidebar
    optionsFilter = ["Admin", "NonAdmin", "Active", "Inactive"];
    optionsOrder = ["Name", "Nickname","Student Number", "# Courses","Last Login"];
    //start checkboxs checked, tied by the ng-model in each input
    $scope.filterAdmin=true;
    $scope.filterNonAdmin=true;
    $scope.filterActive=true;
    $scope.filterInactive=true;
    sidebarAll = createSidebar( optionsFilter, optionsOrder);
    $compile(sidebarAll)($scope)

    //table structure

    allUsers=$("<div id='allUsers'></div>")
    allUsersSection = $('<div class="data-table" ></div>');
    table = $('<table id="users-table"></table>');
    rowHeader = $("<tr></tr>");
    header = [{class: "name-column", content: "Name"},
              {class: "", content: "Nickname"},
              {class: "", content: "Student nº"},
              {class: "", content: "# Courses"},
              {class: "", content: "Last Login"},
              {class: "check-column", content: "Admin"},
              {class: "check-column", content: "Active"},
              {class: "action-column", content: ""},
              {class: "action-column", content: ""},
            ];
    jQuery.each(header, function(index){
        rowHeader.append( $("<th class="+ header[index].class + ">" + header[index].content + "</th>"));
    });
    
    rowContent = $("<tr ng-repeat='(i, user) in users' id='user-{{user.id}}'> ></tr>");
    rowContent.append('<td class="name-column"><span>{{user.name}}</span></td>');
    rowContent.append('<td>{{user.nickname}}</td>');
    rowContent.append('<td>{{user.studentNumber}}</td>');
    rowContent.append('<td>{{user.ncourses}}</td>');
    rowContent.append('<td>{{user.lastLogin}}</td>');
    rowContent.append('<td class="check-column"><label class="switch"><input ng-if="user.isAdmin == true" id="admin-{{user.id}}" type="checkbox" checked><input ng-if="user.isAdmin == false" id="admin-{{user.id}}" type="checkbox"><span ng-click= "adminUser(user.id)" class="slider round"></span></label></td>');
    rowContent.append('<td class="check-column"><label class="switch"><input ng-if="user.isActive == true" id="active-{{user.id}}" type="checkbox" checked><input ng-if="user.isActive == false" id="active-{{user.id}}" type="checkbox"><span ng-click= "activeUser(user.id)" class="slider round"></span></label></td>');
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
    row_inputs.append($('<div class="image smaller"><div class="profile_image"><div id="display_profile_image"><span>Select a profile image</span></div></div><input type="file" class="form__input" id="profile_image" required="" accept=".png, .jpeg, .jpg"/></div>'))
    //text inputs
    details = $('<div class="details bigger right"></div>')
    details.append($('<div class="container"><input type="text" class="form__input" id="name" placeholder="Name *" ng-model="newUser.userName"/> <label for="name" class="form__label">Name</label></div>'))
    details.append($('<div class="container"><input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="newUser.userNickname"/><label for="nickname" class="form__label">Nickname</label></div>'))
    details.append($('<div class="container"><input type="email" class="form__input" id="email" placeholder="Email *" ng-model="newUser.userEmail"/><label for="email" class="form__label">Email</label></div>'))
    details.append($('<div class="container"><input type="text" class="form__input" id="studentNumber" placeholder="Student Number *" ng-model="newUser.userStudentNumber"/><label for="studentNumber" class="form__label">Student Number</label></div>'))
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
    editUser.append( $('<div class="title">Edit User: </div>'));
    editcontent = $('<div class="content">');
    editbox = $('<div id="edit_box" class= "inputs">');
    editrow_inputs = $('<div class= "row_inputs"></div>');
    //image input
    editrow_inputs.append($('<div class="image smaller"><div class="profile_image"><div id="edit_display_profile_image"></div></div><input type="file" class="form__input" id="edit_profile_image" required="" accept=".png, .jpeg, .jpg"/></div>'))
    //text inputs
    editdetails = $('<div class="details bigger right"></div>')
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="name" placeholder="Name *" ng-model="editUser.userName"/> <label for="name" class="form__label">Name</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="editUser.userNickname"/><label for="nickname" class="form__label">Nickname</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="email" placeholder="Email *" ng-model="editUser.userEmail"/><label for="email" class="form__label">Email</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="studentNumber" placeholder="Student Number *" ng-model="editUser.userStudentNumber"/><label for="studentNumber" class="form__label">Student Number</label></div>'))
    editrow_inputs.append(editdetails);
    editbox.append(editrow_inputs);
    // authentication information - service and username
    editrow_auth = $('<div class= "row_inputs"></div>');
    editSelectAuth = $('<div class="smaller">');
    editSelect = $('<select id="authService" class="form__input" name="authService" ng-model="editUser.userAuthService"></select>');
    editSelect.append($('<option value="" disabled selected>Auth Service</option>'));
    optionsAuth = ["fenix", "google", "facebook", "linkedin"];
    jQuery.each(optionsAuth, function( index ){
        option = optionsAuth[index];
        editSelect.append($('<option value="'+option+'">'+option+'</option>'))
    });
    editSelectAuth.append(editSelect);
    editrow_auth.append(editSelectAuth);
    editrow_auth.append($('<div class="details bigger right"><div class="container"><input type="text" class="form__input" id="username" placeholder="Username *" ng-model="editUser.userUsername"/> <label for="username" class="form__label">Username</label></div></div>'))
    editbox.append(editrow_auth);
    editcontent.append(editbox);
    editcontent.append( $('<button class="save_btn" ng-click="submitEditUser()" ng-disabled="!isReadyToEdit()" > Save </button>'))
    editUser.append(editcontent);
    editmodal.append(editUser);
    allUsers.append(editmodal);




    //error section
    allUsers.append( $("<div class='error_box'><div id='empty_table' class='error_msg'></div></div>"));
    //success section
    mainContent.append( $("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

    //action buttons
    action_buttons = $("<div class='action-buttons'></div>");
    action_buttons.append( $("<div class='icon add_icon' value='#new-user' onclick='openModal(this)' ng-click='createUser()'></div>"));
    action_buttons.append( $("<div class='icon import_icon' value='#import-user' onclick='openModal(this)'></div>"));
    action_buttons.append( $("<div class='icon export_icon' ng-click='exportUsers()'></div>"));
    mainContent.append(action_buttons);


    //the import modal
    importModal = $("<div class='modal' id='import-user'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#import-user" onclick="closeModal(this)"></button>'));
    verification.append( $('<div class="warning">Please select a .csv or .txt file to be imported</div>'));
    verification.append( $('<div class="target">The seperator must be comma</div>'));
    verification.append( $('<input class="config_input" type="file" id="import_user" accept=".csv, .txt">')); //input file
    verification.append( $('<div class="confirmation_btns"><button ng-click="importUsers(true)">Import Users (Replace duplicates)</button><button ng-click="importUsers(false)">Import Users (Ignore duplicates)</button></div>'))
    importModal.append(verification);
    mainContent.append(importModal);
    
    mainContent.append(allUsers);
    $compile(mainContent)($scope);

    getUsers = function() {
        $smartboards.request('core', 'users', {}, function(data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            console.log(data)

            $scope.users = data.users.slice();
            $scope.allUsers = data.users.slice();
            
            //no fim do request
            $scope.lastOrder = "none";
            $scope.lastArrow = "none";
            $element.append(sidebarAll);
            $element.append(mainContent);
            $scope.orderList();
            $scope.reduceList();
            
        });  
    }          
    getUsers();
    
    
});