function buildTabs(info, parent, $smartboards, $scope) {
    var el = $('<li>');
    var link = $('<a>', {'ui-sref': info.sref});
    link.append($('<span>', {text: info.text}));
    el.append(link);
    if (info.subItems != null) {
        var subList = $('<ul>');
        for (var i = 0; i < info.subItems.length; ++i) {
            subList.append(buildTabs(info.subItems[i], subList, $smartboards, $scope));
        }
        el.append(subList);
    }
    return el;
}

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

function createSection(parent, title) {
    var sec = $('<div>', {'class':'section'});
    sec.append($('<div>', {'class':'title', text: title}));
    var content = $('<div>', {'class':'content'});
    sec.append(content);
    parent.append(sec);
    return content;
}

function createSectionWithTemplate(parent, title, templateUrl) {
    var sec = $('<div>', {'class':'section'});
    sec.append($('<div>', {'class':'title', text: title}));
    var content = $('<div>', {'class':'content', 'ng-include': '\'' + templateUrl + '\''});
    sec.append(content);
    parent.append(sec);
    return content;
}

function createChangeButtonIfNone(name, anchor, action, config) {
    var defaults = {
        'buttonText' : 'Save',
        'statusTextUpdating': 'Updating',
        'enableFunc': undefined,
        'disableFunc': undefined,
        'createMode': 'append',
        'onCreate': undefined
    };
    config = $.extend({}, defaults, config);

    if (anchor.parent().find('#' + name + '-button').length == 0) {
        var pageStatus = anchor.parent().find('#' + name + '-status');
        if (pageStatus.length != 0)
            pageStatus.remove();
        var changePage = $('<button>', {id: name + '-button', text: config.buttonText, 'class': 'button'});
        changePage.click(function() {
            var status = $('<span>', {id: name + '-status', text: config.statusTextUpdating});
            if (config.disableFunc != undefined)
                config.disableFunc();
            $(this).replaceWith(status);
            action(status);
            if (config.enableFunc != undefined)
                config.enableFunc();
        });
        anchor[config.createMode](changePage);
        if (config.onCreate != undefined)
            config.onCreate();
    }
}

function createInputWithChange(id, text, placeholder, $compile, $smartboards, $parse, scope, ngModel, module, request, field, additionalData, successMsg) {
    //ngModel =  'data.roles.landingPage'
    var wrapperDiv = $('<div>');
    wrapperDiv.append('<label for="' + id + '" class="label">' + text + '</label>');
    var textInput = $('<input>', {type: 'text', id:'' + id + '', 'class': 'input-text', placeholder: placeholder, 'ng-model': ngModel});
    wrapperDiv.append($compile(textInput)(scope));

    textInput.bind('change paste keyup', function() {
        var input = $(this);
        createChangeButtonIfNone(id, textInput, function (status) {
            var data = {};
            data[field] = $parse(ngModel)(scope);
            $.extend(data, additionalData);
            $smartboards.request(module, request, data, function(data, err) {
                if (err) {
                    status.text('Error, please try again!');
                    input.prop('disabled', false);
                    return;
                }

                status.text(successMsg);
                input.prop('disabled', false);
            });
        }, {
            createMode: 'after',
            disableFunc: function () {
                input.prop('disabled', true);
            }
        });
    });

    return wrapperDiv;
}

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


function updateTabTitle(stateName, stateParams) {
    var title = '';

    var possibleLinks = $(document).find('#settings > .tabs > .tabs-container a[ui-sref^="' + stateName + '"]');
    if (possibleLinks.length == 0)
        return;

    var matchedParams = 0;

    var final = undefined;
    for (var i = 0; i < possibleLinks.length; ++i) {
        var link = $(possibleLinks.get(i));
        var sref = link.attr('ui-sref');

        if (sref == stateName) {
            final = link;
            break;
        } else if (sref.substr(stateName.length, 1) == '(') {
            var paramsStart = stateName.length + 2;
            var params = sref.substr(paramsStart, sref.length - paramsStart - 2).split(',');
            for(var j = 0; j < params.length; ++j) {
                var param = params[j].split(':');
                var key = param[0];
                var value = param[1].substr(1, param[1].length - 2);
                if (stateParams[key] == value) {
                    if (j + 1 > matchedParams) {
                        final = link;
                        matchedParams = j + 1;
                    }
                } else {
                    break;
                }
            }
        }
    }

    if (final == undefined)
        return;

    title = final.text();
    var parent = final.parent().parent();
    while(!parent.hasClass('tabs-container')) {
        final = parent.prev();
        parent = final.parent().parent();

        title = final.text() + ' > ' + title;
    }

    setSettingsTitle(title);
}

function setSettingsTitle(title) {
    $('#settings > .tabs > .tab-content-wrapper .tab-title').text(title);
}

app.controller('Settings', function($scope, $state, $compile, $smartboards) {
    changeTitle('Settings', 0);
    var refreshTabsBind = $scope.$on('refreshTabs', function() {

        var tabs = $('#settings > .tabs > .tabs-container');
        tabs.html('');
        //side bar nas settings globais
        tabs.append($compile('<li><a ui-sref="settings.global">Global</a></li>')($scope));
        tabs.append($compile('<li><a ui-sref="settings.modules">Installed Modules</a></li>')($scope));
        tabs.append($compile('<li><a ui-sref="settings.about">About</a></li>')($scope));

        addActiveLinks($state.current.name);
        updateTabTitle($state.current.name, $state.params);
    });
    $scope.$emit('refreshTabs');
    $scope.$on('$destroy', refreshTabsBind);
});

app.controller('SettingsGlobal', function($scope, $element, $smartboards, $compile) {
    $smartboards.request('settings', 'global', {}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;

        // Themes
        var themes = createSection(tabContent, 'Themes');
        themes.attr('id', 'themes');
        for(var i = 0; i < $scope.data.themes.length; i++) {
            var theme = $scope.data.themes[i];
            var themeWrapper = $('<div>', {'class': 'theme'});
            themeWrapper.append($('<div>').text(theme.name));
            themeWrapper.append($('<img>', {'class': 'preview', src: theme.preview ? ('themes/' + theme.name + '/preview.png') : 'images/no-preview.png'}));
            (function(theme) {
                if (theme.name == data.theme)
                    themeWrapper.addClass('current').attr('title', 'Current theme');
                else {
                    themeWrapper.click(function() {
                        $smartboards.request('settings', 'global', {setTheme: theme.name}, function(data, err) {
                            window.location = window.location;
                        });
                    }).attr('title', 'Set theme').addClass('pointer');
                }
            })(theme);
            themes.append(themeWrapper);
        }
    });
});

app.controller('SettingsModules', function($scope, $element, $smartboards, $compile) {
    $smartboards.request('settings', 'modules', {}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;
        
        var columns = ['c1', {field:'c2', constructor: function(content) {
            return content;
        }}];

        var modulesSection = createSection(tabContent, 'Modules');
        modulesSection.attr('id', 'modules');
        var modules = $scope.data;
        for(var i in modules) {
            var module = modules[i];
            var dependencies = [];
            var canEnable = true;
            for (var d in module.dependencies) {
                var dependency = module.dependencies[d];
                dependencies.push(dependency.id);
            }
            dependencies = dependencies.join(', ');
            if (dependencies == '')
                dependencies = 'None';
            var table = Builder.buildTable([
                { c1:'Name:', c2: module.name},
                { c1:'Version:', c2: module.version},
                { c1:'Path:', c2: module.dir},
                { c1:'Dependencies:', c2: dependencies}
            ], columns);
            modulesSection.append(table);
        }
    });
});

//retirar no futuro
app.controller('SettingsCourses', function($scope, $state, $compile, $smartboards, $element) {

    //talvez precise do if abaixo
    $smartboards.request('core', 'getCoursesList', {}, function(data, err) {
        // make sure courses is an object
        var courses = data.courses;
        if (Array.isArray(courses)) {
            var newCourses = {};
            for (var i in courses){
                courses[i]['active'] = courses[i]['active']==='1' ? true : false;
                newCourses[i] = courses[i];
            }
            courses = newCourses;
        }
        $scope.courses = courses;
        $($element).append($compile($('<ul style="list-style: none"><li ng-repeat="course in courses">{{course.name}}{{course.isActive ? \'\' : \' - Inactive\'}} <button ng-click="toggleCourse(course)">{{course.isActive ? \'Deactivate\' : \'Activate\'}}</button><img src="images/trashcan.svg" ng-click="deleteCourse(course.id)"></li></ul>'))($scope));
        $($element).append($compile($('<button>', {'ng-click': 'newCourse()', text: 'Create new'}))($scope));
    });
});



app.run(['$rootScope', '$state', function ($rootScope, $state) {
    $rootScope.$on('$stateChangeSuccess', function (e, toState, toParams, fromState, fromParams) {
        if (toState.name.indexOf('settings') == 0 || toState.name.indexOf('course.settings') == 0) {
            updateTabTitle(toState.name, toParams);
        }

        if (toState.name == 'settings') {
            e.preventDefault();
            $state.go('settings.global');
        } else if (toState.name == 'course.settings') {
            e.preventDefault();
            $state.go('course.settings.global');
        }
    });
}]);

app.config(function($stateProvider){
    $stateProvider.state('settings', {
        url: '/settings',
        views: {
            'side-view@': {
                template: ''
            },
            'main-view@': {
                templateUrl: 'partials/settings.html',
                controller: 'Settings'
            }
        }
    
    //Settings of the system
    }).state('settings.global', {
        url: '/global',
        views : {
            'tabContent': {
                template: '',
                controller: 'SettingsGlobal'
            }
        }
    }).state('settings.modules', {
        url: '/modules',
        views : {
            'tabContent': {
                controller: 'SettingsModules'
            }
        }
    }).state('settings.about', {
        url: '/about',
        views : {
            'tabContent': {
                templateUrl: 'partials/settings/about.html'
            }
        }

    //settings do curso
    }).state('course.settings', {
        url: '/settings',
        views: {
            'side-view@': {
                template: ''
            },
            'main-view@': {
                templateUrl: 'partials/settings.html',
                controller: 'CourseSettings'
            }
        }
    // }).state('course.users', {
    //     url: '/users',
    //     views: {
    //         'main-view@': {
    //             controller: 'CourseUsersss'
    //         }
    //     }
    }).state('course.settings.global', {
        url: '/global',
        views : {
            'tabContent': {
                template: '',
                controller: 'CourseSettingsGlobal'
            }
        }
    }).state('course.settings.modules', {
        url: '/modules',
        views : {
            'tabContent': {
                template: '',
                controller: 'CourseSettingsModules'
            }
        }
    }).state('course.settings.about', {
        url: '/about',
        views : {
            'tabContent': {
                templateUrl: 'partials/settings/about.html'
            }
        }
    }).state('course.settings.students', {
        url: '/students',
        views : {
            'tabContent': {
                controller: 'CourseStudentSettingsController'
            }
        }
    }).state('course.settings.teachers', {
        url: '/teachers',
        views : {
            'tabContent': {
                controller: 'CourseTeacherSettingsController'
            }
        }
    }).state('course.settings.users', {
        url: '/users',
        views : {
            'tabContent': {
                controller: 'CourseUsersss'
            }
        }
    }).state('course.settings.skills', {
        url: '/skills',
        views : {
            'tabContent': {
                controller: 'CourseSkillsSettingsController'
            }
        }
    }).state('course.settings.badges', {
        url: '/badges',
        views : {
            'tabContent': {
                controller: 'CourseBadgesSettingsController'
            }
        }
    }).state('course.settings.levels', {
        url: '/levels',
        views : {
            'tabContent': {
                controller: 'CourseLevelsSettingsController'
            }
        }
    }).state('course.settings.roles', {
        url: '/roles',
        views : {
            'tabContent': {
                controller: 'CourseRolesSettingsController'
            }
        }
    }).state('course.settings.roles.role', {
        url: '/{role:[A-Za-z.]+}-{id:[0-9]+}',
        views : {
            'tabContent@course.settings': {
                controller: 'CourseRoleSettingsController'
            }
        }
    });
});