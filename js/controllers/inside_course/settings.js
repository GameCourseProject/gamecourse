
//definie o que esta nas settings de um curso
app.controller('CourseSettings', function($scope, $state, $compile, $smartboards) {
    changeTitle('Course Settings', 1); //muda no breadcrumb

    var refreshTabsBind = $scope.$on('refreshTabs', function() {
        $smartboards.request('settings', 'courseTabs', {course: $scope.course}, function(data, err) {
            if (err) {
                console.log(err);
                return;
            }

            var tabs = $('#settings > .tabs > .tabs-container');
            tabs.html('');
            tabs.append($compile('<li><a ui-sref="course.settings.global">Global</a></li>')($scope));
            for (var i = 0; i < data.length; ++i)
                tabs.append($compile(buildTabs(data[i], tabs, $smartboards, $scope))($scope));
            tabs.append($compile('<li><a ui-sref="course.settings.modules">Modules</a></li>')($scope));
            tabs.append($compile('<li><a ui-sref="course.settings.about">About</a></li>')($scope));

            addActiveLinks($state.current.name);
            updateTabTitle($state.current.name, $state.params);
        });
    });
    $scope.$emit('refreshTabs');
    $scope.$on('$destroy', refreshTabsBind);
});


app.controller('CourseSettingsGlobal', function($scope, $element, $smartboards, $compile) {
    $smartboards.request('settings', 'courseGlobal', {course: $scope.course}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;
        
        var courseInfo = createSection(tabContent, 'Info');
        var infoDiv = $('<div>');
        infoDiv.append($compile('<p>Course Name: '+$scope.data.name+'</p>') ($scope));
        infoDiv.append($compile('<p>Course ID: '+$scope.course+'</p>') ($scope));
        courseInfo.append(infoDiv);

        var loadDataSection = createSection(tabContent, 'Load Data');
        var loadLegacy = $('<div><br>');
        loadLegacy.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" href="loadLegacy.php?course={{course}}">Load Legacy</a>')($scope));
        loadDataSection.append(loadLegacy);

        var downloadPhotosSettings = $('<br><div>');
        downloadPhotosSettings.append('<label for="jsessionid" class="label">JSESSIONID</label>');
        var jsessionidInput = $('<input>', {type: 'text', id:'jsessionid', 'class': 'input-text', placeholder:'', 'ng-model':'data.jsessionid'});
        downloadPhotosSettings.append($compile(jsessionidInput)($scope));
        downloadPhotosSettings.append('<label for="backendid" class="label">BACKENDID</label>');
        var backendidInput = $('<input>', {type: 'text', id:'backendid', 'class': 'input-text', placeholder:'', 'ng-model':'data.backendid'});
        downloadPhotosSettings.append($compile(backendidInput)($scope));
        loadDataSection.append(downloadPhotosSettings);
        var updateDownloadButtons = $('<div>');
        updateDownloadButtons.append($compile('<br><a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" href="downloadPhotos.php?course={{course}}&jsessionid={{data.jsessionid}}&backendid={{data.backendid}}">Download Photos</a>')($scope));

        loadDataSection.append(updateDownloadButtons);
    });
});

app.controller('CourseSettingsModules', function($scope, $element, $smartboards, $compile) {
    $smartboards.request('settings', 'courseModules', {course: $scope.course}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;
        
        // Modules
        var columns = ['c1', {field:'c2', constructor: function(content) {
            if(typeof content === 'string' || content instanceof String)
                return content;
            else {
                var state = $('<span>')
                    .append($('<span>', {text: content.state ? 'Enabled ' : 'Disabled ' , 'class': content.state ? 'on' : 'off'}));
                var stateButton = $('<button>', {text: !content.state ? 'Enable' : 'Disable', 'class':'button small'});
                stateButton.click(function() {
                    $(this).prop('disabled', true);
                    $smartboards.request('settings', 'courseModules', {course: $scope.course, module: content.id, enabled: !content.state}, function(data, err) {
                        if (err) {
                            alert(err.description);
                            return;
                        }
                        location.reload();
                    });
                });
                if (content.state || canEnable)
                    state.append(stateButton);
                return state;

            }
        }}];

        var modulesSection = createSection(tabContent, 'Modules');
        modulesSection.attr('id', 'modules');
        var modules = $scope.data.modules;
        for(var i in modules) {
            var module = modules[i];
            var dependencies = [];
            var canEnable = true;
            for (var d in module.dependencies) {
                var dependency = module.dependencies[d];
                var dependencyEnabled = modules[dependency.id].enabled;
                if (dependency.mode != 'optional') {
                    if (!dependencyEnabled)
                        canEnable = false;
                    dependencies.push('<span class="color: ' + (dependencyEnabled ? 'on' : 'off') + '">' + dependency.id + '</span>');
                }
            }
            dependencies = dependencies.join(', ');
            if (dependencies == '')
                dependencies = 'None';
            var table = Builder.buildTable([
                { c1:'Name:', c2: module.name},
                { c1:'Version:', c2: module.version},
                { c1:'Path:', c2: module.dir},
                { c1:'State:', c2: {state: module.enabled, id: module.id, canEnable: canEnable}},
                { c1:'Dependencies:', c2: dependencies}
            ], columns);
            modulesSection.append(table);
        }
    });
});

//pagina dos roles
app.controller('CourseRolesSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $smartboards.request('settings', 'roles', {course: $scope.course}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;
        $scope.data.usersSorted = $.map($scope.data.users, function(value, key) { return value; }).sort(function(a, b) { return a.name.localeCompare(b.name); });

        // Users
        var usersSection = createSection(tabContent, 'Users');
        var usersSelect = $($compile($('<select>', {
            id: 'users',
            multiple:'',
            'ng-options': 'user.name for user in data.usersSorted track by user.id',
            'ng-model': 'data.selectedUsers'
        }).attr('size', 10))($scope));

        usersSection.append(usersSelect);
        var userRoles = $('<div>', {id: 'currentRoles'});
        var selectedUsersLabel = $('<div>', {text: 'Current roles for: '});
        var selectedUsernames = $('<span>', {'class': 'user-names', text: 'None'});
        selectedUsersLabel.append(selectedUsernames);
        userRoles.append(selectedUsersLabel);
        var selectedRoles = $('<div>', {class: 'roles'});
        userRoles.append(selectedRoles);
        var selectedRolesModify = $('<div>', {class: 'roles-modify'}).css({'display': 'none'});
        selectedRolesModify.append('<div>Add role: </div>');
        var modifyContainer = $('<div>')
        modifyContainer.append($compile($('<select>', {
            'class': 'select',
            'ng-options': 'user for user in data.possibleNewRoles',
            'ng-model': 'data.newRoleSelected'
        }))($scope));
        var addButton = $($compile($('<button>', {'class': 'button small', 'text': 'Add'}))($scope));
        addButton.click(function() {
            if ($scope.data.newRoleSelected != undefined) {
                addRoleToSelected($scope, $scope.data.newRoleSelected);
                updateSelectedUsersRolesView();
                // force update of select list, because angular..
                $scope.$apply(function() { $scope.data.possibleNewRoles = $scope.data.possibleNewRoles; });
            }
        });
        modifyContainer.append(addButton);

        selectedRolesModify.append(modifyContainer);
        userRoles.append(selectedRolesModify);

        usersSection.append(userRoles);
        var changeBox = $('<div>', {'class': 'change-box'});
        usersSection.append(changeBox);

        function createChangeButtonForUsers($scope) {
            createChangeButtonIfNone('user-change', changeBox, function (status) {
                $smartboards.request('settings', 'roles', {course: $scope.course, usersRoleChanges: $scope.data.usersRoleChanges}, function(data, err) {
                    if (err) {
                        status.text('Error, please try again!');
                        return;
                    }
                    status.text('Users\' roles changed!');
                });
            });
        }

        function addRoleToSelected($scope, newRole) {
            $scope.data.selectedUsers.forEach(function(user) {
                var index = $.inArray(newRole, user.roles);
                if (index == -1) {
                    user.roles.push(newRole);
                    $scope.data.users[user.id].roles.push(newRole);
                    if ($scope.data.usersRoleChanges == undefined)
                        $scope.data.usersRoleChanges = {};
                    $scope.data.usersRoleChanges[user.id] = $scope.data.users[user.id].roles;
                }
            });
            createChangeButtonForUsers($scope);
        }

        function removeRoleFromSelected($scope, newRole) {
            $scope.data.selectedUsers.forEach(function(user) {
                var index = $.inArray(newRole, user.roles);
                if (index != -1) {
                    user.roles.splice(index, 1);
                    $scope.data.users[user.id].roles.splice(index, 1);
                    if ($scope.data.usersRoleChanges == undefined)
                        $scope.data.usersRoleChanges = {};
                    $scope.data.usersRoleChanges[user.id] = $scope.data.users[user.id].roles;
                }
            });
            $scope.$apply(function() {
                $scope.data.possibleNewRoles.push(newRole);
                if ($scope.data.possibleNewRoles.length == 1)
                    $scope.data.newRoleSelected = newRole;
            });
            createChangeButtonForUsers($scope);
        }

        function updateSelectedUsersRolesView() {
            var selectedUsers = $scope.data.selectedUsers;
            var usersRoles = {};
            selectedUsers.forEach(function(user) {
                var userRoles = user.roles;
                userRoles.forEach(function (role) {
                    if (this[role] != undefined)
                        this[role] += 1;
                    else
                        this[role] = 1;
                }, this);
            }, usersRoles);

            $scope.data.possibleNewRoles = $scope.data.roles.filter(function(role) {
                return usersRoles[role] == undefined;
            });

            if ($scope.data.possibleNewRoles != undefined)
                $scope.data.newRoleSelected = $scope.data.possibleNewRoles[0];

            selectedRoles.html('');

            $.each(usersRoles, function(role, users) {
                var assignedRole = $('<div>', {text: role});
                assignedRole.append($('<div>', {'class': 'selector ' + (users == selectedUsers.length ? 'all' : 'some')}));
                selectedRoles.append(assignedRole);
            });

            selectedRoles.find('div.selector').parent().click(function() {
                var selector = $(this).children('.selector');
                var assignedRole = $(this);
                if (selector.hasClass('some')) {
                    selector.toggleClass('some all');
                    addRoleToSelected($scope, assignedRole.text());
                } else {
                    removeRoleFromSelected($scope, assignedRole.text());
                    assignedRole.remove();
                }
            });
        }

        $scope.$watch('data.selectedUsers', function(selectedUsers) {
            if (selectedUsers == undefined || selectedUsers.length == 0) {
                selectedUsernames.text('None');
                selectedRoles.html('');
                selectedRolesModify.css('display', 'none');
                return;
            }

            var usernames = selectedUsers.slice(0, 5).map(function(user) { return user.name; }).join(', ');
            usernames += (selectedUsers.length > 5 ? ' and ' + (selectedUsers.length - 5) + ' more.' : '.');
            selectedUsernames.text(usernames);

            updateSelectedUsersRolesView();
        });

        $scope.$watch('data.possibleNewRoles', function(roles) {
            if (roles == undefined || roles.length == 0)
                selectedRolesModify.css('display', 'none');
            else
                selectedRolesModify.css('display', 'block');
        }, true);

        // Roles
        var rolesSection = createSection(tabContent, 'Roles');
        var dd = $('<div>', {'class': 'dd'});

        function buildItem(roleName) {
            var item = $('<li>', {'class': 'dd-item', 'data-name': roleName});
            item.append($('<div>', {'class': 'dd-handle'}));
            item.append($('<div>', {'class': 'dd-content', text: roleName}));
            return item;
        }

        function buildRoles(parent, roles) {
            var list = $('<ol>', {'class': 'dd-list'});
            for (var n = 0; n < roles.length; n++) {
                var role = roles[n];
                var item = buildItem(role.name);
                if (role.children != undefined)
                    buildRoles(item, role.children);
                list.append(item);
            }
            parent.append(list);
        }
        
        //deletes the specified role and all its children
        function deleteRoleAndChildren(hierarchy, roleToDelete=null){
            for(var i=0; i<hierarchy.length; i++){
                if (!roleToDelete || hierarchy[i]['name'] === roleToDelete){
                    if ("children" in hierarchy[i])
                        deleteRoleAndChildren(hierarchy[i]['children']);
                    
                    $scope.data.newRoles.splice($.inArray(hierarchy[i]['name'], $scope.data.newRoles), 1);
                }else if ("children" in hierarchy[i])
                    deleteRoleAndChildren(hierarchy[i]['children'],roleToDelete);  
            }
        }
        
        if ($scope.data.newRoles == undefined)
            $scope.data.newRoles = $scope.data.roles;
        buildRoles(dd, $scope.data.rolesHierarchy);
        rolesSection.append(dd);
        rolesSection.append($compile($('<button ng-click="addRole()" class="button">Add Role</button>'))($scope));
        dd.nestable().on('change', function() {
            var list = $(this);
            createChangeButtonIfNone('role-change', rolesSection, function (status) {
                $smartboards.request('settings', 'roles', {course: $scope.course, updateRoleHierarchy: {hierarchy: list.nestable('serialize'), roles: $scope.data.newRoles}}, function(data, err) {
                    if (err) {
                        status.text('Error, please try again!');
                        return;
                    }

                    $scope.$emit('refreshTabs');
                    status.text('Role hierarchy changed!');
                });
            });
        }).on('additem', function (event, ret) {
            ret[0] = ret[1] = prompt('New role name: ');
            if ($scope.data.newRoles == undefined)
                $scope.data.newRoles = $scope.data.roles;
            $scope.data.newRoles.push(ret[0]);
            $scope.data.rolesHierarchy = dd.nestable('serialize');
        }).on('removeitem', function (event, data) {
            var roleName = data.name;
            if ($scope.data.newRoles == undefined)
                $scope.data.newRoles = $scope.data.roles;
            deleteRoleAndChildren(dd.nestable('serialize'),roleName);
        });

        $scope.addRole = function() {
            var newRole = prompt('New role name: ');
            if (newRole === null)
                return;
            if ($scope.data.newRoles == undefined)
                $scope.data.newRoles = $scope.data.roles;
            $scope.data.newRoles.push(newRole);
            dd.nestable('createRootItem')(newRole, {name: newRole});
            $scope.data.rolesHierarchy = dd.nestable('serialize');
            //$(dd.children().get(0)).append(buildItem(newRole));
            dd.trigger('change');
        };
    });

    $smartboards.request('settings', 'landingPages', {course : $scope.course}, function(data, err) {
        
        var $landingPageSection = createSection($element, 'Landing pages');
        //fazer identificador por linha
        //colocar valor atual na caixa
        //nput vai passar a ser dropdown 
        for (role of data.roles){
            var title = "<b>" + role.name + "</b><br>";
            $landingPageSection.append(title);
            //ngModel =  'data.roles.landingPage' --- not working here, but is going to be changend anyway

            var input = createInputWithChange('landing-page-'+ role.id, 'Landing Page', '(ex: /myprofile)', $compile, $smartboards, $parse, $scope, 'data.roles.landingPage', 'settings', 'landingPages', 'landingPage', {course: $scope.course, id: role.id}, 'New landing page is set!');
            $landingPageSection.append(input);

        }
        
    });
});

app.controller('CourseRoleSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    //shortName: $stateParams.role,
    $smartboards.request('settings', 'roleInfo', {course : $scope.course,  id: $stateParams.id}, function(data, err) {
        if (err) {
            console.log(err);
            return;
        }
        $scope.data = data;

        var input = createInputWithChange('landing-page', 'Landing Page', '(ex: /myprofile)', $compile, $smartboards, $parse, $scope, 'data.landingPage', 'settings', 'roleInfo', 'landingPage', {course: $scope.course, id: $stateParams.id}, 'New landing page is set!');
        $element.append(input);
    });
});