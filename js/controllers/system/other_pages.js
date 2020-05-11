//Controllers for pages of the system, except for setting pages

app.controller('HomePage', function($element, $scope, $timeout) {
    $scope.setNavigation([]);
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
    changeTitle('Courses', 0);

    $scope.newCourse = function() {
        $state.go('courses.create');
    };

    $scope.deleteCourse = function(course) {
        //if (prompt('Are you sure you want to delete? Type \'DELETE ' + $scope.courses[course].name + '\' to confirm the action') != ('DELETE ' + $scope.courses[course].name))
        //    return;
        if (prompt('Are you sure you want to delete? Type \'DELETE\' to confirm the action') != ('DELETE')){
            return;
        }   
        $smartboards.request('settings', 'deleteCourse', {course: course}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }

            for (var i in $scope.courses){
                if ($scope.courses[i].id==course)
                    delete $scope.courses[i];
            }
            $scope.$emit('refreshTabs');
        });
    };

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
    $element.append(pageBlock = Builder.createPageBlock({
        image: 'images/leaderboard.svg',
        text: 'Courses'
    }, function(el, info) {
        el.append(Builder.buildBlock({
            image: 'images/awards.svg',
            title: 'My courses'
        }, function(blockContent) {
            blockContent.append('<ul style="list-style: none"><li ng-repeat="(i, course) in courses"><a ui-sref="course({courseName:course.nameUrl, course: course.id})">{{course.name}}{{course.isActive ? \'\' : \' - Inactive\'}}</a></li></ul>');
        }).attr('ng-if', 'usingMyCourses ==true'));//'myCourses != undefined && myCourses.length != 0'));
        el.append(Builder.buildBlock({
            image: 'images/awards.svg',
            title: 'All Courses'
        }, function(blockContent) {
            //blockContent.append('<ul style="list-style: none"><li ng-repeat="(i, course) in courses"><a ui-sref="course({courseName:course.nameUrl, course: course.id})">{{course.name}}{{course.isActive ? \'\' : \' - Inactive\'}}</a></li></ul>');
            blockContent.append('<ul style="list-style: none"><li ng-repeat="(i, course) in courses"><a ui-sref="course({courseName:course.nameUrl, course: course.id})">{{course.name}}{{course.isActive ? \'\' : \' - Inactive\'}}</a> <button ng-click="toggleCourse(course)">{{course.isActive ? \'Deactivate\' : \'Activate\'}}</button><img src="images/trashcan.svg" ng-click="deleteCourse(course.id)"></li></ul>');
            blockContent.append($compile($('<button>', {'ng-click': 'newCourse()', text: 'Create new'}))($scope));
    
        }).attr('ng-if', 'usingMyCourses ==false'));
    }));
    $compile(pageBlock)($scope);

    $smartboards.request('core', 'getCoursesList', {}, function(data, err) {
        if (err) {
            alert(err.description);
            return;
        }
        $scope.courses = data.courses;
        $scope.usingMyCourses = data.myCourses;//bool
        for (var i in $scope.courses) {
            var course = $scope.courses[i];
            course.nameUrl = course.name.replace(/\W+/g, '');
        }
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