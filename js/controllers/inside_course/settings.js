
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
            tabs.append($compile('<li><a ui-sref="course.settings.global">This Course</a></li>')($scope));
            tabs.append($compile('<li><a ui-sref="course.settings.roles">Roles</a></li>')($scope));
            tabs.append($compile('<li><a ui-sref="course.settings.modules">Modules</a></li>')($scope));
            for (var i = 0; i < data.length; ++i)
                tabs.append($compile(buildTabs(data[i], tabs, $smartboards, $scope))($scope));
            // tabs.append($compile('<li><a ui-sref="course.settings.about">About</a></li>')($scope));

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
    
    //falta imagem e descricao de cada modulo

    $scope.reduceList = function(){
        $scope.modules = $scope.allModules;
        filteredModules = [];
        text = $scope.search;
        if (validateSearch(text)){
            //match por name e short
            jQuery.each($scope.modules , function( index ){
                module_obj = $scope.modules[index];
                if (module_obj.name.toLowerCase().includes(text.toLowerCase())
                || module_obj.discription.toLowerCase().includes(text.toLowerCase())){
                    filteredModules.push(module_obj);
                }
            });
            if(filteredModules.length == 0){
                $("#courses-table").hide();
                $("#empty_table").append("No matches found");
            }
            $scope.modules = filteredModules;
        }
        
    }


    var tabContent = $($element);

    search = $("<div class='search'> <input type='text' id='seach_input' placeholder='Search..' name='search' ng-change='reduceList()' ng-model='search' ><button class='magnifying-glass' id='search-btn' ng-click='reduceList()'></button>  </div>")

    modules = $('<div id="modules"></div>');
    module_card = $('<div class="module_card" ng-repeat="(i, module) in modules"></div>')
    module_card.append($('<div class="icon"></div>'));
    module_card.append($('<div class="header">{{module.name}}</div>'));
    module_card.append($('<div class="text">{{module.description}}</div>'));
    module_card.append($('<div ng-if="module.enabled != true" class="status disable">Disabled <div class="background"></div></div>'));
    module_card.append($('<div ng-if="module.enabled == true" class="status enable">Enabled <div class="background"></div></div>'));
    modules.append(module_card);
    
    $compile(modules)($scope);
    $compile(search)($scope);
    tabContent.append(search);
    tabContent.append(modules);
    

    $smartboards.request('settings', 'courseModules', {course: $scope.course}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        console.log(data);
        $scope.modules = data;
        $scope.allModules = data.slice();




        var tabContent = $($element);
        $scope.data = data;
        
        // // Modules
        // var columns = ['c1', {field:'c2', constructor: function(content) {
        //     if(typeof content === 'string' || content instanceof String)
        //         return content;
        //     else {
        //         var state = $('<span>')
        //             .append($('<span>', {text: content.state ? 'Enabled ' : 'Disabled ' , 'class': content.state ? 'on' : 'off'}));
        //         var stateButton = $('<button>', {text: !content.state ? 'Enable' : 'Disable', 'class':'button small'});
        //         stateButton.click(function() {
        //             $(this).prop('disabled', true);
        //             $smartboards.request('settings', 'courseModules', {course: $scope.course, module: content.id, enabled: !content.state}, function(data, err) {
        //                 if (err) {
        //                     alert(err.description);
        //                     return;
        //                 }
        //                 location.reload();
        //             });
        //         });
        //         if (content.state || canEnable)
        //             state.append(stateButton);
        //         return state;

        //     }
        // }}];

        // var modulesSection = createSection(tabContent, 'Modules');
        // modulesSection.attr('id', 'modules');
        // var modules = $scope.data.modules;
        // for(var i in modules) {
        //     var module = modules[i];
        //     var dependencies = [];
        //     var canEnable = true;
        //     for (var d in module.dependencies) {
        //         var dependency = module.dependencies[d];
        //         var dependencyEnabled = modules[dependency.id].enabled;
        //         if (dependency.mode != 'optional') {
        //             if (!dependencyEnabled)
        //                 canEnable = false;
        //             dependencies.push('<span class="color: ' + (dependencyEnabled ? 'on' : 'off') + '">' + dependency.id + '</span>');
        //         }
        //     }
        //     dependencies = dependencies.join(', ');
        //     if (dependencies == '')
        //         dependencies = 'None';
        //     var table = Builder.buildTable([
        //         { c1:'Name:', c2: module.name},
        //         { c1:'Version:', c2: module.version},
        //         { c1:'Path:', c2: module.dir},
        //         { c1:'State:', c2: {state: module.enabled, id: module.id, canEnable: canEnable}},
        //         { c1:'Dependencies:', c2: dependencies}
        //     ], columns);
        //     modulesSection.append(table);
        // }
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
        $scope.data.newRoles = $scope.data.roles;
       
        
        // Roles
        var rolesSection = createSection(tabContent, 'Roles');
        var dd = $('<div>', {'class': 'dd', 'id': 'roles-config'});

        dd.append('<div class="header"><span class="role_sections">Roles</span><span class="page_sections">Landing Page</span></div>')


        // constroi linha na primeira vez
        function buildItem(roleName) {
            var item = $('<li>', {'class': 'dd-item', 'data-name': roleName});
            
            item.append($('<div>', {'class': 'dd-handle icon'}));
            item.append($('<div>', {'class': 'dd-content', text: roleName}));
            page_section = $('<select>', {'class':"dd-content", 'ng-model': roleName});
            page_section.append($('<option>', {text: 'Default Course Page', 'value':''}));
            page_options = $scope.data.pages
            jQuery.each(page_options, function( index ){
                page = page_options[index];
                page_section.append($('<option>', {text: page.name, 'value': page.name}));
            });
            item.append(page_section);
            $compile(item)($scope)
            
            //initialize scope value a select right option on dropdown
            role = $scope.data.roles_obj.find(x => x.name == roleName);
            $scope[roleName] = role.landingPage;
            return item;
        }

        // constroi tabela a primira vez
        function buildRoles(parent, roles) {
            var list = $('<ol>', {'class': 'dd-list first-layer'});
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
        
        buildRoles(dd, $scope.data.rolesHierarchy);
        
        // add button no fim da lista
        add_new_role = $('<div id="add_role_button_box"><button ng-click="addRole()" class="add_button icon"></button></div>')
        $compile(add_new_role)($scope)

        rolesSection.append(dd);
        rolesSection.append(add_new_role);

        options = {}
        options.dropdown = $scope.data.pages
        dd.nestable(options);

        dd.on('change', function() {
            var list = $(this);
            createChangeButtonIfNone('role-change', rolesSection, function (status) {
                var newRolesAllInfo = [];

                jQuery.each($scope.data.newRoles, function( index ){
                    role_name = $scope.data.newRoles[index];
                    role_page = $scope[role_name];
                    role_obj = $scope.data.roles_obj.find(x => x.name == role_name);
                    role_id = role_obj ? role_obj.id : null;
                    roleInfo = {name: role_name,
                                id: role_id,
                                landingPage: role_page};
                    newRolesAllInfo.push(roleInfo);
                });

                $smartboards.request('settings', 'roles', {course: $scope.course, updateRoleHierarchy:true, hierarchy: list.nestable('serialize'), roles: newRolesAllInfo}, function(data, err) {
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
            $scope.data.newRoles.push(ret[0]);
            $scope.data.rolesHierarchy = dd.nestable('serialize');
            $scope[ret[0]] = "";

        }).on('removeitem', function (event, data) {
            //delete de um role no nestable
            var roleName = data.name;
            deleteRoleAndChildren(dd.nestable('serialize'),roleName);
        });

        $scope.addRole = function() {
            var newRole = prompt('New role name: ');
            if (newRole === null)
                return;
            $scope.data.newRoles.push(newRole);
            $scope[newRole] = "";
            dd.nestable('createRootItem')(newRole, {name: newRole}); //passar lista de opcoes do select
            $scope.data.rolesHierarchy = dd.nestable('serialize');
            dd.trigger('change');
        };
    });

    // $smartboards.request('settings', 'landingPages', {course : $scope.course}, function(data, err) {
        
    //     var $landingPageSection = createSection($element, 'Landing pages');
    //     //fazer identificador por linha
    //     //colocar valor atual na caixa
    //     //nput vai passar a ser dropdown 
    //     for (role of data.roles){
    //         var title = "<b>" + role.name + "</b><br>";
    //         $landingPageSection.append(title);
    //         //ngModel =  'data.roles.landingPage' --- not working here, but is going to be changend anyway

    //         var input = createInputWithChange('landing-page-'+ role.id, 'Landing Page', '(ex: /myprofile)', $compile, $smartboards, $parse, $scope, 'data.roles.landingPage', 'settings', 'landingPages', 'landingPage', {course: $scope.course, id: role.id}, 'New landing page is set!');
    //         $landingPageSection.append(input);

    //     }
        
    // });
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