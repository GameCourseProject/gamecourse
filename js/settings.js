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

app.controller('CourseSettings', function($scope, $state, $compile, $smartboards) {
    changeTitle('Settings', 1);

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
            tabs.append($compile('<li><a ui-sref="course.settings.api">API</a></li>')($scope));
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

        // General configuration
        var generalConfiguration = createSection(tabContent, 'General Configuration');

        var headerLinkSettings = $('<div>');
        headerLinkSettings.append('<label for="header-link" class="label">Header Link</label>');
        var headerLinkInput = $('<input>', {type: 'text', id:'header-link', 'class': 'input-text', placeholder:'', 'ng-model':'data.headerLink'});
        headerLinkSettings.append($compile(headerLinkInput)($scope));
        generalConfiguration.append(headerLinkSettings);

        $('#header-link').bind('change paste keyup', function() {
            var input = $(this);
            createChangeButtonIfNone('header-link', headerLinkInput, function (status) {
                $smartboards.request('settings', 'courseGlobal', {course: $scope.course, headerLink: $scope.data.headerLink}, function(data, err) {
                    if (err) {
                        status.text('Error, please try again!');
                        input.prop('disabled', false);
                        return;
                    }

                    status.text('New header link is set!');
                    input.prop('disabled', false);
                });
            }, {
                createMode: 'after',
                disableFunc: function () {
                    input.prop('disabled', true);
                }
            });
        });

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
                    $smartboards.request('settings', 'courseGlobal', {course: $scope.course, module: content.id, enabled: !content.state}, function(data, err) {
                        console.log("enable", data);
                        if (err) {
                            alert(err.description);
                            return;
                        }

                        window.location = window.location;
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
                { c1:'Dependencies:', c2: dependencies},
            ], columns);
            modulesSection.append(table);
        }

        var loadDataSection = createSection(tabContent, 'Load Data');
        var loadLegacy = $('<div><br>');
        loadLegacy.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" href="loadLegacy.php?course={{course}}">Load Legacy</a>')($scope));
        loadDataSection.append(loadLegacy);
        var fenixLinkSettings = $('<div>');
        fenixLinkSettings.append('<br><label for="fenix-link" class="label">Fenix Grades Link</label>');
        var fenixLinkInput = $('<input>', {type: 'text', id:'fenix-link', 'class': 'input-text', placeholder:'', 'ng-model':'data.courseFenixLink'});
        fenixLinkSettings.append($compile(fenixLinkInput)($scope));
        loadDataSection.append(fenixLinkSettings);

        $('#fenix-link').bind('change paste keyup', function() {
            var input = $(this);
            createChangeButtonIfNone('fenix-link', fenixLinkInput, function (status) {
                $smartboards.request('settings', 'courseGlobal', {course: $scope.course, courseFenixLink: $scope.data.courseFenixLink}, function(data, err) {
                    if (err) {
                        status.text('Error, please try again!');
                        input.prop('disabled', false);
                        return;
                    }

                    status.text('New Fenix Grades Link is set!');
                    input.prop('disabled', false);
                });
            }, {
                createMode: 'after',
                disableFunc: function () {
                    input.prop('disabled', true);
                }
            });
        });

        var downloadPhotosSettings = $('<div>');
        downloadPhotosSettings.append('<label for="jsessionid" class="label">JSESSIONID</label>');
        var jsessionidInput = $('<input>', {type: 'text', id:'jsessionid', 'class': 'input-text', placeholder:'', 'ng-model':'data.jsessionid'});
        downloadPhotosSettings.append($compile(jsessionidInput)($scope));
        downloadPhotosSettings.append('<label for="backendid" class="label">BACKENDID</label>');
        var backendidInput = $('<input>', {type: 'text', id:'backendid', 'class': 'input-text', placeholder:'', 'ng-model':'data.backendid'});
        downloadPhotosSettings.append($compile(backendidInput)($scope));
        loadDataSection.append(downloadPhotosSettings);
        var updateDownloadButtons = $('<div>');
        updateDownloadButtons.append($compile('<br><a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" href="updateUsernames.php?course={{course}}&courseurl={{data.courseFenixLink}}&jsessionid={{data.jsessionid}}&backendid={{data.backendid}}">Update Usernames</a>')($scope));
        updateDownloadButtons.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" href="downloadPhotos.php?course={{course}}&jsessionid={{data.jsessionid}}&backendid={{data.backendid}}">Download Photos</a>')($scope));

        loadDataSection.append(updateDownloadButtons);
    });
});

app.controller('CourseSettingsAPI', function($scope, $element, $smartboards, $compile) {
    $scope.key = 'Loading';
    $element.append($compile('<div>API Key: <strong>{{key}}</strong></div>')($scope));
    $element.append($compile('<button ng-click="generateNewKey()">Generate new key</button>')($scope));

    $scope.generateNewKey = function() {
        $smartboards.request('settings', 'courseApiKeyGen', {course: $scope.course},  function(data, err) {
            if (err) {
                $($element).text('Error: ' + err.description);
                return;
            }

            $scope.key = data.key;
        });
    };

    $smartboards.request('settings', 'courseApiKey', {course: $scope.course},  function(data, err) {
        if (err) {
            $($element).text('Error: ' + err.description);
            return;
        }

        if (data.key == false)
            $scope.key = 'Not set';
        else
            $scope.key = data.key;
    });
});

app.controller('SettingsAPI', function($scope, $element, $smartboards, $compile) {
    $scope.key = 'Loading';
    $element.append($compile('<div>API Key: <strong>{{key}}</strong></div>')($scope));
    $element.append($compile('<button ng-click="generateNewKey()">Generate new key</button>')($scope));

    $scope.generateNewKey = function() {
        $smartboards.request('settings', 'apiKeyGen', {course: $scope.course},  function(data, err) {
            if (err) {
                $($element).text('Error: ' + err.description);
                return;
            }

            $scope.key = data.key;
        });
    };

    $smartboards.request('settings', 'apiKey', {course: $scope.course},  function(data, err) {
        if (err) {
            $($element).text('Error: ' + err.description);
            return;
        }

        if (data.key == false || data.key == null)
            $scope.key = 'Not set';
        else
            $scope.key = data.key;
    });
});

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
});

app.controller('CourseRoleSettingsController', function($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $smartboards.request('settings', 'roleInfo', {course : $scope.course, role: $stateParams.role}, function(data, err) {
        if (err) {
            console.log(err);
            return;
        }
        $scope.data = data;

        var input = createInputWithChange('landing-page', 'Landing Page', '(ex: /myprofile)', $compile, $smartboards, $parse, $scope, 'data.landingPage', 'settings', 'roleInfo', 'landingPage', {course: $scope.course, role: $stateParams.role}, 'New landing page is set!');
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
        $smartboards.request('settings', 'tabs', {}, function(data, err) {
            if (err) {
                console.log(err);
                return;
            }

            var tabs = $('#settings > .tabs > .tabs-container');
            tabs.html('');
            tabs.append($compile('<li><a ui-sref="settings.global">Global</a></li>')($scope));
            tabs.append($compile('<li><a ui-sref="settings.users">Users</a></li>')($scope));
            for (var i = 0; i < data.length; ++i)
                tabs.append($compile(buildTabs(data[i], tabs, $smartboards, $scope))($scope));
            tabs.append($compile('<li><a ui-sref="settings.api">API</a></li>')($scope));
            tabs.append($compile('<li><a ui-sref="settings.about">About</a></li>')($scope));

            addActiveLinks($state.current.name);
            updateTabTitle($state.current.name, $state.params);
        });
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
app.controller('SettingsCourses', function($scope, $state, $compile, $smartboards, $element) {
    $scope.newCourse = function() {
        $state.go('settings.courses.create');
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

    $scope.toggleCourse = function(course) {
        $smartboards.request('settings', 'setCourseState', {course: course.id, state: !course.active}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            course.active = !course.active;
            $scope.$emit('refreshTabs');
        });
    };

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
        $($element).append($compile($('<ul style="list-style: none"><li ng-repeat="course in courses">{{course.name}}{{course.active ? \'\' : \' - Inactive\'}} <button ng-click="toggleCourse(course)">{{course.active ? \'Deactivate\' : \'Activate\'}}</button><img src="images/trashcan.svg" ng-click="deleteCourse(course.id)"></li></ul>'))($scope));
        $($element).append($compile($('<button>', {'ng-click': 'newCourse()', text: 'Create new'}))($scope));
    });
});

app.controller('SettingsCourse', function($scope, $state, $compile, $smartboards, $element) {
});

app.controller('SettingsCourseCreate', function($scope, $state, $compile, $smartboards, $element) {
    setSettingsTitle('Courses > Create');

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
            $state.go('settings.courses');
        });
    };
});

app.controller('SettingsUsers', function($scope, $state, $compile, $smartboards, $element) {
    $smartboards.request('settings', 'users', {}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        $scope.pendingInvites = data.pendingInvites;

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
            $smartboards.request('settings', 'users', {setPermissions: {admins: idsNewAdmin, users: idsNewUser}}, function(data, err) {
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

        $scope.inviteInfo = {};
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
        };
        $scope.isValidString = function(s) { return s != undefined && s.length > 0};

        var pendingInvites = createSection($($element), 'Pending Invites').attr('id', 'pending-invites');
        pendingInvites.append('<div>Pending: <ul><li ng-if="pendingInvites.length>0" ng-repeat="invite in pendingInvites">{{invite.id}}, {{invite.username}}<img src="images/trashcan.svg" ng-click="deleteInvite(invite)"></li></ul></div>');
        var addInviteDiv = $('<div>');
        addInviteDiv.append('<div>New invite: </div>')
        addInviteDiv.append('<div><label for="invite-id" class="label">IST Id:</label><input type="text" class="input-text" id="invite-id" ng-model="inviteInfo.id"></div>');
        addInviteDiv.append('<div><label for="invite-username" class="label">IST Username:</label><input type="text" class="input-text" id="invite-username" ng-model="inviteInfo.username"></div>');
        addInviteDiv.append('<div><button ng-disabled="!isValidString(inviteInfo.id) || !isValidString(inviteInfo.username)" ng-click="createInvite()">Create</button></div>');
        pendingInvites.append(addInviteDiv);
        $compile(pendingInvites)($scope);

        $scope.userUpdateInfo = {};
        $scope.updateUsername = function() {
            $smartboards.request('settings', 'users', {updateUsername: $scope.userUpdateInfo}, function(data, err) {
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
    }).state('settings.global', {
        url: '/global',
        views : {
            'tabContent': {
                template: '',
                controller: 'SettingsGlobal'
            }
        }
    }).state('settings.courses', {
        url: '/courses',
        views : {
            'tabContent@settings': {
                controller: 'SettingsCourses'
            }
        }
    }).state('settings.courses.course', {
        url: '/{course:[0-9]+}',
        views : {
            'tabContent@settings': {
                controller: 'SettingsCourse'
            }
        }
    }).state('settings.courses.create', {
        url: '/create',
        views : {
            'tabContent@settings': {
                controller: 'SettingsCourseCreate'
            }
        }
    }).state('settings.users', {
        url: '/users',
        views : {
            'tabContent': {
                controller: 'SettingsUsers'
            }
        }
    }).state('settings.api', {
        url: '/api',
        views : {
            'tabContent': {
                template: '',
                controller: 'SettingsAPI'
            }
        }
    }).state('settings.about', {
        url: '/about',
        views : {
            'tabContent': {
                templateUrl: 'partials/settings/about.html'
            }
        }
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
    }).state('course.settings.global', {
        url: '/global',
        views : {
            'tabContent': {
                template: '',
                controller: 'CourseSettingsGlobal'
            }
        }
    }).state('course.settings.about', {
        url: '/about',
        views : {
            'tabContent': {
                templateUrl: 'partials/settings/about.html'
            }
        }
    }).state('course.settings.api', {
        url: '/api',
        views : {
            'tabContent': {
                template: '',
                controller: 'CourseSettingsAPI'
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
        url: '/{role:[A-Za-z.]+}',
        views : {
            'tabContent@course.settings': {
                controller: 'CourseRoleSettingsController'
            }
        }
    });
});