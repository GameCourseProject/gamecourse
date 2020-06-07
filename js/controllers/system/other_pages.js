//Controllers for pages of the system, except for setting pages

app.controller('HomePage', function($element, $scope, $timeout) {
    $scope.setNavigation([], []);
    $timeout(function() {
        $scope.defaultNavigation();
        $timeout(function() {
            addActiveLinks('home');
        });
    });
    changeTitle('', 0, false);

    $element.append(Builder.createPageBlock({
        image: 'images/leaderboard.svg',
        text: 'Main Page'
    }, function(el, info) {
        el.append(Builder.buildBlock({
            image: 'images/awards.svg',
            title: 'Welcome'
        }, function(blockContent) {
            var divText = $('<div style="padding: 4px">');
            divText.append('<p>Welcome to the GameCourse system.</p>');
            divText.append('<p>Hope you enjoy!</p>');
            blockContent.append(divText);
        }));
    }));
});

//courses list
app.controller('Courses', function($element, $scope, $smartboards, $compile, $state) {
    $scope.courses = {};
    //changeTitle('Courses', 0);

    $scope.newCourse = function() {
        $state.go('courses.create');
    };

    $scope.deleteCourse = function(course) {
        console.log("continueDelete");
        $smartboards.request('settings', 'deleteCourse', {course: course}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }

            for (var i in $scope.courses){
                if ($scope.courses[i].id==course)
                    $("#course-" + course).remove();
                    delete $scope.courses[i];
                   
            }
            $scope.$emit('refreshTabs');
        });
    };

    $scope.visibleCouse = function(course_name, course_id){
        id = "#visible-" + course_name + ":checked"; 
        if($(id).length > 0){
            $visible = 0; //false
        }
        else{
            $visible = 1; //true
        }
        $smartboards.request('core', 'setCoursesvisibility', {course_id: course_id, visibility: $visible}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }

        });
    }

    $scope.activeCouse = function(course_name, course_id){
        id = "#active-" + course_name + ":checked"; 
        if($(id).length > 0){
            $active = 0; //false
        }
        else{
            $active = 1; //true
        }
        $smartboards.request('core', 'setCoursesActive', {course_id: course_id, active: $active}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }

        });
    }
    $scope.filtercourses = function(){
        $("#empty_table").empty();
        $("#courses-table").show();
        active = $("#filter-Active:checked").length >0
        inactive = $("#filter-Inactive:checked").length >0
        visible = $("#filter-Visible:checked").length >0
        invisible = $("#filter-Invisible:checked").length >0;

        //reset list of courses
        $scope.courses = $scope.allCourses;
        coursesList = $scope.courses;
        filteredCourses = [];
        error_msg = "";

        //cases of empty result
        if (!active && !inactive){
            //inserir aviso
            //filteredCourses is empty
            error_msg = "You must select at least one of the options: Active or Inactive"
        }
        else if(!visible & !invisible){
            //inserir aviso
            //filteredCourses is empty
            error_msg = "You must select at least one of the options: Visible or Invisible"
        }
        else if(active && inactive && visible & invisible){
            filteredCourses = coursesList;
        }
        else{
            jQuery.each(coursesList , function( index ) {
                course = coursesList[index];
                validA = false;
                validV = false;
                if (course.isActive == true && active){
                    validA = true;
                }
                else if ( course.isActive == false && inactive){
                    validA = true;
                }
                if (validA && course.isVisible == true && visible){
                    validV = true;
                }
                else if(validA && course.isVisible == false && invisible){
                    validV = true;
                }

                if (validA && validV){
                    filteredCourses.push(course);
                }
            });
        
            if (filteredCourses.length == 0){
                //nao ha nada para o filtro aplicado
                error_msg = "No matches found for your filter"
            }
        }
        if(error_msg != ""){
            $("#courses-table").hide();
            $("#empty_table").append(error_msg);
        }
        $scope.courses = filteredCourses;
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

    $scope.orderCourses = function(){
        order_by_id = $('input[type=radio]:checked', ".order-by")[0].id;
        order = getNameFromId(order_by_id);
        up = $("#triangle-up").hasClass("checked");

        if (up){ arrow = "up";}
        else{ arrow = "down";}

        console.log("Order by:" + order + " " + arrow);

        if ($scope.lastOrder =="none" || $scope.lastOrder != order){
            switch (order){
                //default sort made with arrow down
                case "Name":
                    $scope.courses.sort(orberByName);
                    break;
                case "Short":
                    $scope.courses.sort(orberByShort);
                    break;
                case "N Students":
                    $scope.courses.sort(orberByNStudents);
                    break;
                case "Year":
                    $scope.courses.sort(orberByYear);
                    break;
            }
            if (up){ 
                $scope.courses.reverse();
            }

        }else{
            if (arrow ==  $scope.lastArrow){
                //nothing changes
                return;
            }
            else{
                //only the ascendent/descent order changed
                $scope.courses.reverse();
            }

        }

        //save also in all courses - copy by value
        $scope.allCourses = $scope.courses.slice();

        //set values of the existing orderby
        $scope.lastOrder = order;
        $scope.lastArrow = arrow;
    }

    //o que esta a fazer mesmo????
    $scope.toggleCourse = function(course) {
        $smartboards.request('settings', 'setCourseState', {course: course.id, state: !course.active}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            course.isActive = !course.isActive;
            $scope.$emit('refreshTabs');
        });
    };

    var pageBlock;
    var pageBlock2;

    optionsFilter = ["Active", "Inactive", "Visible", "Invisible"];
    optionsOrder = ["Name", "Short","# Students", "Year"];
    //start checkboxs checked, tied by the ng-model in each input
    $scope.filterActive=true;
    $scope.filterInactive=true;
    $scope.filterVisible=true;
    $scope.filterInvisible=true;

    sidebar = createSidebar( optionsFilter, optionsOrder);
    $compile(sidebar)($scope)
    mainContent = $("<div id='mainContent'></div>");

    //each version of the page courses
    allCourses = $("<div id='allCourses' class='data-table'></div>").attr('ng-if', 'usingMyCourses ==false');
    myCourses = $("<div id='myCourses'></div>").attr('ng-if', 'usingMyCourses ==true');


    //versao aluno, teacher, etc
    myCourses.append('<ul style="list-style: none"><li ng-repeat="(i, course) in courses"><a ui-sref="course({courseName:course.nameUrl, course: course.id})">{{course.name}}{{course.isActive ? \'\' : \' - Inactive\'}}</a></li></ul>');
    


    //admin version of the page
    //table structure
    table = $('<table id="courses-table"></table>');
    rowHeader = $("<tr></tr>");
    header = [{class: "first-column", content: ""},
              {class: "name-column", content: "Name"},
              {class: "", content: "Short"},
              {class: "", content: "# Students"},
              {class: "", content: "Year"},
              {class: "check-column", content: "Visible"},
              {class: "check-column", content: "Active"},
              {class: "action-column", content: ""},
            ];
    jQuery.each(header, function(index){
        rowHeader.append( $("<th class="+ header[index].class + ">" + header[index].content + "</th>"));
    });

    rowContent = $("<tr ng-repeat='(i, course) in courses' id='course-{{course.id}}'> ></tr>");
    rowContent.append('<td class="first-column"><div class="profile-icon"></div></td>');
    rowContent.append('<td class="name-column" ui-sref="course({courseName:course.nameUrl, course: course.id})"><span>{{course.name}}</span></td>');
    rowContent.append('<td>{{course.short}}</td>');
    rowContent.append('<td>{{course.nstudents}}</td>');
    rowContent.append('<td>{{course.year}}</td>');
    rowContent.append('<td class="check-column"><label class="switch"><input ng-if="course.isVisible == true" id="visible-{{course.name}}" type="checkbox" checked><input ng-if="course.isVisible == false" id="visible-{{course.name}}" type="checkbox"><span ng-click= "visibleCouse(course.name, course.id)" class="slider round"></span></label></td>');
    rowContent.append('<td class="check-column"><label class="switch"><input ng-if="course.isActive == true" id="active-{{course.name}}" type="checkbox" checked><input ng-if="course.isActive == false" id="active-{{course.name}}" type="checkbox"><span ng-click= "activeCouse(course.name, course.id)" class="slider round"></span></label></td>');
    rowContent.append('<td class="action-column"><div class="icon duplicate_icon" ></div></td>');
    rowContent.append('<td class="action-column"><div class="icon edit_icon" ></div></td>');
    rowContent.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification-{{course.id}}" onclick="openModal(this)"></div></td>');

    //the verification modals
    modal = $("<div class='modal' id='delete-verification-{{course.id}}'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#delete-verification-{{course.id}}" onclick="closeModal(this)"></button>'));
    verification.append( $('<div class="warning">Are you sure you want to delete the Course?</div>'));
    verification.append( $('<div class="target">{{course.name}}</div>'));
    verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#delete-verification-{{course.id}}" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="deleteCourse(course.id)"> Delete</button></div>'))
    modal.append(verification);
    rowContent.append(modal);

    //append table
    table.append(rowHeader);
    table.append(rowContent);
    allCourses.append(table);

    //error section
    allCourses.append( $("<div class='error_box'><div id='empty_table' class='error_msg'></div></div>"));

    //action buttons
    action_buttons = $("<div class='action-buttons'></div>");
    action_buttons.append( $("<div class='icon add_icon' ng-click='newCourse()'></div>"));
    action_buttons.append( $("<div class='icon import_icon'></div>"));
    action_buttons.append( $("<div class='icon export_icon'></div>"));
    allCourses.append($compile(action_buttons)($scope));

    //new course modal
    // modal = $("<div class='modal' id='new-course'></div>");
    // newCourse = $("<div class='modal_content'></div>");
    // newCourse.append( $('<button class="close_btn icon" value="#new-course" onclick="closeModal(this)"></button>'));
    // newCourse.append( $('<div class="title">New Course: </div>'));
    // content = $('<div class="content">');
    // box = $('<div class= "box">');

    // content.append(box);
    // newCourse.append(content);

//   <button class="close_btn"></button>
//   <div class="title">New Course: </div>
//   <div class="content">
//     <div class= "box">
//     <div class="name full">
//       <input type="text" class="form__input " id="name" placeholder="Name" required="" /> <label for="name" class="form__label">Name</label>
      
//     </div>
//     <div class= "row_inputs">
//       <div class="short_name half">
//         <input type="text" class="form__input" id="short_name" placeholder="Short Name" required="" />
//           <label for="name" class="form__label">Short Name</label>
//         <input type="text" class="form__input" id="short_name" placeholder="Other name" required="" />
//           <label for="name" class="form__label">Other Name</label>
//       </div>
//       <div class="year half right">
//         <input type="text" class="form__input" id="year" placeholder="Year" required="" />
//           <label for="name" class="form__label">Year</label>
//         </div>
//     </div>
//        <div class= "row">
//          <div class= "on_off">
//            <span>Active </span>
//            <label class="switch">
//     <input type="checkbox">
//     <span class="slider round"></span>
//              </label></div>
//          <div class= "on_off">
//            <span>Visible </span>
//            <label class="switch">
//     <input type="checkbox">
//     <span class="slider round"></span>
//              </label></div>
//        </div>
//     </div>
//     <button class="save_btn"> Save </button>
//   </div>
// </div>


    //compile the page for scope values
    $compile(allCourses)($scope);
    $compile(myCourses)($scope);

    mainContent.append(allCourses);
    mainContent.append(myCourses);
    $element.append(sidebar);
    $element.append(mainContent);


    //request to get all courses info
    $smartboards.request('core', 'getCoursesList', {}, function(data, err) {
        if (err) {
            alert(err.description);
            return;
        }
        $scope.courses = data.courses;
        $scope.allCourses = data.courses.slice(); //using slice so it is a copy by value and not reference
        $scope.usingMyCourses = data.myCourses;//bool
        for (var i in $scope.courses) {
            var course = $scope.courses[i];
            course.nameUrl = course.name.replace(/\W+/g, '');
        }

        //set order by parameters
        $scope.lastOrder = "none";
        $scope.lastArrow = "none";
        $scope.orderCourses();
    });
});

app.controller('CourseCreate', function($scope, $state, $compile, $smartboards, $element) {
    //setSettingsTitle('Courses > Create');

    $scope.newCourse = {};
    $scope.isValid = function(v) { return v != undefined && v.trim().length > 0; };
    $scope.isValidCourse = function (course) { return course != undefined; };

    $scope.courses = undefined;
    $smartboards.request('core', 'getCoursesList', {}, function(data, err) {
        if (err) {
            alert(err.description);
            return;
        }

        $scope.courses = data.courses;
        $compile(createSectionWithTemplate($element, 'Course Create', 'partials/settings/course-create.html'))($scope);
    });

    $scope.createCourse = function() {
        var reqData = {
            courseName: $scope.newCourse.courseName,
            creationMode: $scope.newCourse.creationMode
        };

        if ($scope.newCourse.creationMode == 'similar')
            reqData.copyFrom = $scope.newCourse.course.id;

        $smartboards.request('settings', 'createCourse', reqData, function(data, err) {
            if (err) {
                console.log(err.description);
                return;
            }

            $scope.$emit('refreshTabs');
            $state.go('courses');
        });
    };
});

app.controller('Users', function($scope, $state, $compile, $smartboards, $element) {
    $smartboards.request('core', 'users', {}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }
        console.log(data)
        //$scope.pendingInvites = data.pendingInvites;

        $scope.usersAdmin = [];
        $scope.usersNonAdmin = [];

        $scope.selectedUsersAdmin = [];
        $scope.selectedUsersNonAdmin = [];
        $scope.saved=false;

        $scope.newAdmins = [];
        $scope.newUsers = [];
        for (var i in data.users) {
            var user = data.users[i];
            if (user.isAdmin==1)
                $scope.usersAdmin.push(user);
            else
                $scope.usersNonAdmin.push(user);
        }

        var userAdministration = createSection($($element), 'User Administration').attr('id', 'user-administration');
        var adminsWrapper = $('<div>');
        adminsWrapper.append('<div>Admins:</div>')
        var adminsSelect = $('<select>', {
            id: 'users',
            multiple:'',
            'ng-options': 'user.name + \' (\' + user.id + \', \' + user.username + \')\' for user in usersAdmin | orderBy:\'name\' track by user.id',
            'ng-model': 'selectedUsersAdmin'
        }).attr('size', 10);
        adminsWrapper.append(adminsSelect);

        var changeWrapper = $('<div>', {'class': 'buttons'});
        changeWrapper.append($('<button>', {'ng-if': 'selectedUsersNonAdmin.length > 0', 'ng-click': 'addAdmins()', text: '<-- Add Admin'}));
        changeWrapper.append($('<button>', {'ng-if': 'selectedUsersAdmin.length > 0', 'ng-click': 'removeAdmins()', text: 'Remove Admin -->'}));
        changeWrapper.append($('<button>', {'ng-if': 'newAdmins.length > 0 || newUsers.length > 0', 'ng-click': 'saveChanges()', text: 'Save'}));
        changeWrapper.append($('<span>', {'ng-if': 'saved && (selectedUsersAdmin.length == 0) && (selectedUsersNonAdmin.length==0)', text: 'Saved Admin List!'}));


        var nonAdminsWrapper = $('<div>');
        nonAdminsWrapper.append('<div>Users:</div>')
        var nonAdminsSelect = $('<select>', {
            id: 'users',
            multiple:'',
            'ng-options': 'user.name + \' (\' + user.id + \', \' + user.username + \')\' for user in usersNonAdmin | orderBy:\'name\' track by user.id',
            'ng-model': 'selectedUsersNonAdmin'
        }).attr('size', 10);
        nonAdminsWrapper.append(nonAdminsSelect);

        function removeArr(arr, toRemoveArr) {
            for (var i = 0; i < toRemoveArr.length; ++i) {
                var toRemove = toRemoveArr[i];
                for (var j = 0; j < arr.length; ++j) {
                    var obj = arr[j];
                    if (angular.equals(obj, toRemove)) {
                        arr.splice(j, 1);
                        break;
                    }
                }
            }
        }

        // removes from one toRemove but only adds in toAddArr  if it was not found
        function transferUser(arr, toRemoveArr, toAddArr) {
            for (var i = 0; i < toRemoveArr.length; ++i) {
                var toRemove = toRemoveArr[i];
                var found = false;
                for (var j = 0; j < arr.length; ++j) {
                    var obj = arr[j];
                    if (angular.equals(obj, toRemove)) {
                        arr.splice(j, 1);
                        found = true;
                        break;
                    }
                }
                if (!found)
                    toAddArr.push(toRemove);
            }
            $scope.saved=false;
        }

        $scope.addAdmins = function() {
            Array.prototype.push.apply($scope.usersAdmin, $scope.selectedUsersNonAdmin);
            removeArr($scope.usersNonAdmin, $scope.selectedUsersNonAdmin);
            transferUser($scope.newUsers, $scope.selectedUsersNonAdmin, $scope.newAdmins);
        };

        $scope.removeAdmins = function() {
            Array.prototype.push.apply($scope.usersNonAdmin, $scope.selectedUsersAdmin);
            removeArr($scope.usersAdmin, $scope.selectedUsersAdmin);
            transferUser($scope.newAdmins, $scope.selectedUsersAdmin, $scope.newUsers);
        };

        userAdministration.append(adminsWrapper);
        userAdministration.append(changeWrapper);
        userAdministration.append(nonAdminsWrapper);

        $scope.saveChanges = function() {
            var idsNewAdmin = $scope.newAdmins.map(function(user) { return user.id; });
            var idsNewUser = $scope.newUsers.map(function(user) { return user.id; });
            console.log(idsNewAdmin);
            console.log(idsNewUser);
            $smartboards.request('core', 'users', {setPermissions: {admins: idsNewAdmin, users: idsNewUser}}, function(data, err) {
                if (err) {
                    alert('Error. Refresh and try again.');
                    console.log(err.description);
                    return;
                }
                $scope.newAdmins = [];
                $scope.newUsers = [];
                $scope.saved=true;
            });
        };
        $compile(userAdministration)($scope);

        /*$scope.inviteInfo = {};
        $scope.createInvite = function() {
            $smartboards.request('settings', 'users', {createInvite: $scope.inviteInfo}, function(data, err) {
                if (err) {
                    alert(err.description);
                    return;
                }

                if (Array.isArray($scope.pendingInvites))
                    $scope.pendingInvites = {};
                $scope.pendingInvites[$scope.inviteInfo.username] = {id: $scope.inviteInfo.id, username: $scope.inviteInfo.username};
                console.log('ok!');
            });
        };
        $scope.deleteInvite = function(invite) {
            $smartboards.request('settings', 'users', {deleteInvite: invite.id}, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }

                delete $scope.pendingInvites[invite.username];
                console.log('ok!');
            });
        };*/
        $scope.isValidString = function(s) { return s != undefined && s.length > 0};

        /*var pendingInvites = createSection($($element), 'Pending Invites').attr('id', 'pending-invites');
        pendingInvites.append('<div>Pending: <ul><li ng-if="pendingInvites.length>0" ng-repeat="invite in pendingInvites">{{invite.id}}, {{invite.username}}<img src="images/trashcan.svg" ng-click="deleteInvite(invite)"></li></ul></div>');
        var addInviteDiv = $('<div>');
        addInviteDiv.append('<div>New invite: </div>')
        addInviteDiv.append('<div><label for="invite-id" class="label">IST Id:</label><input type="text" class="input-text" id="invite-id" ng-model="inviteInfo.id"></div>');
        addInviteDiv.append('<div><label for="invite-username" class="label">IST Username:</label><input type="text" class="input-text" id="invite-username" ng-model="inviteInfo.username"></div>');
        addInviteDiv.append('<div><button ng-disabled="!isValidString(inviteInfo.id) || !isValidString(inviteInfo.username)" ng-click="createInvite()">Create</button></div>');
        pendingInvites.append(addInviteDiv);
        $compile(pendingInvites)($scope);*/

        $scope.userUpdateInfo = {};
        $scope.updateUsername = function() {
            $smartboards.request('core', 'users', {updateUsername: $scope.userUpdateInfo}, function(data, err) {
                if (err) {
                    alert(err.description);
                    return;
                }

                console.log('ok!');
                $scope.userUpdateInfo = {};
            });
        };

        var updateUsernameSection = createSection($($element), 'Update usernames').attr('id', 'update-usernames');
        var setUsernameDiv = $('<div>');
        setUsernameDiv.append('<div>Update username: </div>')
        setUsernameDiv.append('<div><label for="update-id" class="label">IST Id:</label><input type="text" class="input-text" id="update-id" ng-model="userUpdateInfo.id"></div>');
        setUsernameDiv.append('<div><label for="update-username" class="label">IST Username:</label><input type="text" class="input-text" id="update-username" ng-model="userUpdateInfo.username"></div>');
        setUsernameDiv.append('<div><button ng-disabled="!isValidString(userUpdateInfo.id) || !isValidString(userUpdateInfo.username)" ng-click="updateUsername()">Update username</button></div>');
        updateUsernameSection.append(setUsernameDiv);
        $compile(updateUsernameSection)($scope);
    });
});