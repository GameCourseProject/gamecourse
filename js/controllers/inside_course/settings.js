
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
        $("#empty_search").empty();
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
                $("#empty_search").append("No matches found");
            }
            $scope.modules = filteredModules;
        }
        
    }
    $scope.openModule = function(module){
        $scope.module_open = {};
        $scope.module_open.id = module.id;
        $scope.module_open.name = module.name;
        $scope.module_open.description = module.description;
        $scope.module_open.dir = module.dir;
        $scope.module_open.version = module.version;
        $scope.module_open.enabled = module.enabled;
        $scope.module_open.dependencies = module.dependencies;
        $scope.module_open.hasConfiguration = module.hasConfiguration;
        $scope.module_open.canBeEnabled = module.canBeEnabled;


        $scope.needsToBeSaved = function(){
            if ($scope.module_open.enabled != module.enabled){
                return true
            }else{
                return false
            }
        }

        $scope.saveModule = function(){

            $smartboards.request('settings', 'courseModules', {course: $scope.course, module: $scope.module_open.id, enabled: $scope.module_open.enabled}, function(data, err) {
                if (err) {
                    alert(err.description);
                    return;
                }
                location.reload();
            });
        }
    }


    var tabContent = $($element);

    search = $("<div class='search'> <input type='text' id='seach_input' placeholder='Search..' name='search' ng-change='reduceList()' ng-model='search' ><button class='magnifying-glass' id='search-btn' ng-click='reduceList()'></button>  </div>")
    

    modules = $('<div id="modules"></div>');
    module_card = $('<div class="module_card" ng-repeat="(i, module) in modules" value="#view-module" onclick="openModal(this)" ng-click="openModule(module)"></div> ')
    module_card.append($('<div class="icon" style="background-image: url(/gamecourse/modules/{{module.id}}/icon.svg)"></div>'));
    module_card.append($('<div class="header">{{module.name}}</div>'));
    module_card.append($('<div class="text">{{module.description}}</div>'));
    module_card.append($('<div ng-if="module.enabled != true" class="status disable">Disabled <div class="background"></div></div>'));
    module_card.append($('<div ng-if="module.enabled == true" class="status enable">Enabled <div class="background"></div></div>'));
    modules.append(module_card);
    //error section
    modules.append( $("<div class='error_box'><div id='empty_search' class='error_msg'></div></div>"));
    
    
    $compile(modules)($scope);
    $compile(search)($scope);
    tabContent.append(search);
    tabContent.append(modules);
    
    //modal for details of the module
    modal = $("<div class='modal' style='' id='view-module'></div>");
    viewModal = $("<div class='modal_content'></div>");
    viewModal.append( $('<button class="close_btn icon" value="#view-module" onclick="closeModal(this)"></button>'));
    header = $('<div class= "header"></div>');
    header.append($('<div class="icon" style="background-image: url(/gamecourse/modules/{{module_open.id}}/icon.svg)"></div>'));
    header.append( $('<div class="title">{{module_open.name}} </div>'));
    //se tiver dependencias pendentes tira-se o input e a class slider leva disabled
    header.append( $('<div class= "on_off" ng-if="module_open.canBeEnabled == true"><label class="switch"><input id="active" type="checkbox" ng-model="module_open.enabled"><span class="slider round"></span></label></div>'))
    header.append( $('<div class= "on_off" ng-if="module_open.canBeEnabled != true"><label class="switch disabled"><input id="active" type="checkbox" ng-model="module_open.enabled" disabled><span class="slider round"></span></label></div>'))

    
    
    viewModal.append(header);
    content = $('<div class="content">');
    box = $('<div class="inputs">');
    box.append( $('<div class="full" id="description">{{module_open.description}}</div>'))
    dependencies_row = $('<div class= "row"></div>');
    dependencies = $('<div ><span>Dependencies: </span></div>');
    dependencies.append($('<span class="details" ng-repeat="(i, dependency) in module_open.dependencies" ng-if="dependency.enabled == true" ><span style="color: green">{{dependency.id}}</span> | </span>'))
    dependencies.append($('<span class="details" ng-repeat="(i, dependency) in module_open.dependencies" ng-if="dependency.enabled != true" ><span style="color: red">{{dependency.id}}</span> | </span>'))

    dependencies_row.append(dependencies);
    box.append(dependencies_row);
    box.append( $('<div class= "row"><div ><span>Version: </span><span class="details">{{module_open.version}}</span></div></div>'))
    box.append( $('<div class= "row"><div ><span>Path: </span><span class="details">{{module_open.dir}}</span></div></div>'))
    content.append(box);
    content.append( $('<button class="save_btn" ng-click="saveModule()" ng-disabled="!needsToBeSaved()" > Save </button>'))
    content.append($('<button ng-if="module_open.hasConfiguration == true" class="config_btn" ui-sref="course.settings.{{module_open.id}}"> Configurate </button>'));
    viewModal.append(content);
    modal.append(viewModal);
    $compile(modal)($scope);
    $element.append(modal);



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
    
    function buildItem(roleName) {
        var item = $('<li>', {'class': 'dd-item', 'data-name': roleName});
        //default values can not be dragged or deletes
        if(roleName == "Teacher" || roleName=="Student" || roleName=="Watcher"){
            item.addClass("dd-nodrag");
        }
        item.append($('<div>', {'class': 'dd-handle icon'}));
        item.append($('<div>', {'class': 'dd-content', text: roleName}));
        page_section = $('<select>', {'class':"dd-content", 'ng-model': roleName, 'ng-change': "saveLandingPage('"+roleName+"')"});
        page_section.append($('<option>', {text: 'Default Course Page', 'value':''}));
        page_options = $scope.data.pages
        jQuery.each(page_options, function( index ){
            page = page_options[index];
            page_section.append($('<option>', {text: page.name, 'value': page.name}));
        });
        item.append(page_section);
        $compile(item)($scope); //esta a bloquear aqui
        
        //initialize scope value a select right option on dropdown
        role = $scope.data.roles_obj.find(x => x.name == roleName);
        $scope[roleName] = role.landingPage;
        return item;
    }
    // constroi tabela a primira vez
    function buildRoles(parent, roles) {
        var list = $('<ol>', {'class': 'dd-list first-layer'});
        parent.append(list);
        for (var n = 0; n < roles.length; n++) {
            var role = roles[n];
            var item = buildItem(role.name);
            if (role.children != undefined)
                buildRoles(item, role.children);
            list.append(item);
        }
    }
    
    //deletes the specified role and all its children
    function deleteRoleAndChildren(hierarchy, roleToDelete=null){
        for(var i=0; i<hierarchy.length; i++){
            if (!roleToDelete || hierarchy[i]['name'] === roleToDelete){
                if ("children" in hierarchy[i])
                    deleteRoleAndChildren(hierarchy[i]['children']);
                
                //remove from list of new roles
                $scope.data.newRoles.splice($.inArray(hierarchy[i]['name'], $scope.data.newRoles), 1);
                //remove from relation role - landingpage
                $scope.data.roles_obj = $.grep($scope.data.roles_obj, function(e){ 
                    return e.name != hierarchy[i]['name']; 
                });

            }else if ("children" in hierarchy[i])
                deleteRoleAndChildren(hierarchy[i]['children'],roleToDelete);  
        }
    }

    function createChangeButtonIfNone( anchor, action, list) {
        if (anchor.parent().find('#role-change-button').length == 0) {
            var pageStatus = anchor.parent().find('#role-change-status');
            if (pageStatus.length != 0)
                pageStatus.remove();
            var changePage = $('<button>', {id: 'role-change-button', text: "Save Changes", 'class': 'button', 'disabled': true});
            changePage.click(function() {
                updateChangeButton(true);
                action(list);
            });
            anchor.append(changePage);
        }
    }
    function updateChangeButton(disabled){
        if ($('#role-change-button').length != 0) {
            var button = $('#role-change-button');
            button.prop('disabled', disabled);
        }
    }

    function saveRoles ( list) {
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
        $("#action_completed").empty();
        $("#action_not_completed").empty();
        $smartboards.request('settings', 'roles', {course: $scope.course, updateRoleHierarchy:true, hierarchy: list.nestable('serialize'), roles: newRolesAllInfo}, function(data, err) {
            if (err) {
                $("#action_not_completed").append("Error, please try again!");
                $("#action_not_completed").show().delay(3000).fadeOut();
                return;
            }
            
            $scope.$emit('refreshTabs');
            $("#action_completed").append("Role hierarchy changed!");
            $("#action_completed").show().delay(3000).fadeOut();
        });
    }
    function addNewRole(){
        $("#new-role").show();
        $("#new-role input:text, #new-role textarea").first().focus();
        $scope.newRole = {};
        $scope.newRole.name = "";
        $("#role_name").val("") //for the add coming from nestable, it does not clean field with scope
        $scope.isReadyToSubmit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            //does role already exists?
            existing_role = $scope.data.newRoles.includes($scope.newRole.name);
            
            if (isValid($scope.newRole.name) && !existing_role){
                return true;
            }else{
                return false;
            }
        }
        function getUserInput(){
            return new Promise((resolve, reject) => {
                //enter no input
                $('#role_name').keydown(function(e) {
                  if (e.keyCode == 13 && $scope.isReadyToSubmit()) {
                    resolve($scope.newRole.name);
                  }
                });
                //click no button
                $("#submit_role").click(function(e){
                    resolve($scope.newRole.name);
                });
                //close modal
                $("#cancel_role").click(function(e){
                    reject();
                });
              });
        }

        return getUserInput();
    }
    function redoTable(state){
        //tenho de copiar por valor tambem
        //para nao alterar os valores/objetos que estao guardados no statemanager
        newHierarchy = Object.values(state['dd']);
        newRoles = state['roles'].slice();
        newRolesObjs = duplicateRolesObjs(state['obj']);
        //update scope
        $scope.data.rolesHierarchy = newHierarchy;
        $scope.data.newRoles = newRoles;
        $scope.data.roles_obj = newRolesObjs;

        dd = $("#roles-config");
        dd.children().last().remove();
        buildRoles(dd, newHierarchy);
        dd.nestable('init');
        updateChangeButton(false);
    }

    $scope.undo = function() {
        state = $scope.state_manager.undo();
        //now it is able to redo
        $("#redo_icon").removeClass("disabled");

        if(!$scope.state_manager.canUndo()){
            $("#undo_icon").addClass("disabled");
        }
        redoTable(state);
    }
    $scope.redo = function(){
        state = $scope.state_manager.redo();
        //now it is able to undo
        $("#undo_icon").removeClass("disabled");

        if(!$scope.state_manager.canRedo()){
            $("#redo_icon").addClass("disabled");
        }
        redoTable(state);
    }
    $scope.saveLandingPage = function(role_name){
        role = $scope.data.roles_obj.find(x => x.name == role_name);
        role.landingPage = $scope[role_name];
    }

    function duplicateRolesObjs(roles){
        duplicated_roles_obj = [];
        jQuery.each(roles, function( index ){
            role_obj = roles[index];
            new_obj = Object.assign({}, role_obj);
            duplicated_roles_obj.push(new_obj);
        });
        return duplicated_roles_obj;
    }

    function newAction(state){
        //preciso de "duplicar" objs do roles_obj
        duplicated_roles_obj = duplicateRolesObjs($scope.data.roles_obj);

        $scope.state_manager.newState({'dd': state, 'roles': $scope.data.newRoles.slice(), 'obj': duplicated_roles_obj});
        $("#undo_icon").removeClass("disabled");
        $("#redo_icon").addClass("disabled");
        updateChangeButton(false);
    }

    $smartboards.request('settings', 'roles', {course: $scope.course}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;
        $scope.data.newRoles = $scope.data.roles; 
        
        // Roles
        var rolesSection = $('<div>', {'class': 'content', 'id':'roles_page'});
        tabContent.append(rolesSection);

        //inicio dd
        var dd = $('<div>', {'class': 'dd', 'id': 'roles-config'});
        dd.append('<div class="header"><span class="role_sections">Roles</span><span class="page_sections">Landing Page</span></div>')
        
        // add button no fim da lista
        add_new_role = $('<div id="add_role_button_box"><button ng-click="addRole()" class="add_button icon"></button></div>')
        $compile(add_new_role)($scope)

        //action buttons
        action_buttons = $("<div class='action-buttons'></div>");
        action_buttons.append( $("<div id='undo_icon' class='icon undo_icon disabled' ng-click='undo()'></div>"));
        action_buttons.append( $("<div id='redo_icon' class='icon redo_icon disabled' ng-click='redo()'></div>"));
        $compile(action_buttons)($scope)
        createChangeButtonIfNone( action_buttons,  saveRoles, dd, false);

        rolesSection.append(dd);
        rolesSection.append(add_new_role);
        rolesSection.append(action_buttons);
        //error section
        rolesSection.append( $("<div class='error_box'><div id='action_not_completed' class='error_msg'></div></div>"));
        //success section
        rolesSection.append( $("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

        // add modal
        modal = $("<div class='modal' id='new-role'></div>");
        newRole = $("<div class='modal_content little_modal'></div>");
        newRole.append( $('<button class="close_btn icon" id="cancel_role" value="#new-role" onclick="closeModal(this)"></button>'));
        newRole.append( $('<div class="title">New Role: </div>'));
        content = $('<div class="content">');
        box = $('<div class= "inputs">');
        box.append( $('<div class="name full"><input type="text" class="form__input " id="role_name" placeholder="Name *" ng-model="newRole.name"/> <label for="name" class="form__label">Name</label></div>'));
        content.append(box);
        content.append( $('<button class="save_btn" id="submit_role" ng-disabled="!isReadyToSubmit()" > Continue </button>'))
        newRole.append(content);
        modal.append(newRole);
        $compile(modal)($scope)
        rolesSection.append(modal);
        
        //generate table
        buildRoles(dd, $scope.data.rolesHierarchy);
        options = {}
        options.dropdown = $scope.data.pages
        dd.nestable(options);

        dd.on('change', function() {
            $compile(dd)($scope); //new elements need to be compiled into scope
            $scope.data.rolesHierarchy = dd.nestable('serialize');
            newAction($scope.data.rolesHierarchy);
            var list = $(this);
            updateChangeButton( false);

        }).on('additem', function (event, ret) {
            ret[0] = addNewRole();
            ret[0].then((roleName) => {
                $scope.data.newRoles.push(roleName);
                $scope.data.roles_obj.push({
                    'id': null,
                    'name': roleName,
                    'landingPage': ''
                })
                $scope[roleName] = "";
                $("#new-role").hide();
            });            

        }).on('removeitem', function (event, data) {
            //delete de um role no nestable
            var roleName = data.name;
            deleteRoleAndChildren(dd.nestable('serialize'),roleName);
        });

        //duplicate by value array with role objs 
        duplicated_roles_obj = [];
        jQuery.each($scope.data.roles_obj, function( index ){
            role_obj = $scope.data.roles_obj[index];
            new_obj = Object.assign({}, role_obj);
            duplicated_roles_obj.push(new_obj);
        });
        //initialize state manager for undo/redo
        $scope.state_manager = new StateManager({'dd': dd.nestable('serialize'), 'roles': $scope.data.newRoles.slice(), 'obj': duplicated_roles_obj});


        // add role pelo add button
        $scope.addRole = function() {
            rolePromise = addNewRole();   
            rolePromise.then((roleName) => {
                $scope.data.newRoles.push(roleName);
                $scope.data.roles_obj.push({
                    'id': null,
                    'name': roleName,
                    'landingPage': ''
                })
                $scope[roleName] = "";
                dd.nestable('createRootItem')(roleName, {name: roleName});
                dd.trigger('change');
                $("#new-role").hide(); 
            });
                
        };
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