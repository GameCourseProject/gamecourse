angular.module('module.views', []);
angular.module('module.views').controller('ViewSettings', function($state, $stateParams, $smartboards, $element, $compile, $scope) {
    $element.html('Loading...');
    $smartboards.request('views', 'getInfo', {view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp,course: $scope.course}, function(data, err) {
        if (err) {
            $element.text(err.description);
            return;
        }
        function subtractSpecializations(one, two) {
            var cpy = one.slice(0);
            for(var i = 0; i < cpy.length; ++i) {
                for(var j = 0; j < two.length; ++j) {
                    if (cpy[i].id == two[j].id) {
                        cpy.splice(i, 1);
                        --i;
                        break;
                    }
                }
            }
            if (cpy.length == 0)
                cpy = undefined;
            return cpy;
        }
        angular.extend($scope, data);

        $scope.viewType = $scope.viewSettings.roleType;
        $scope.pageOrTemp = data.pageOrTemp;
        $scope.selection = {};
        $scope.selectedStyle = {
            'background-color': 'rgba(0, 0, 0, 0.07)'
        };

        $scope.selectOne = function(specializationOne) {
            $scope.oneSelected = specializationOne;
            if ($scope.viewType == "ROLE_SINGLE") {
                $state.go('course.settings.views.view.edit-role-single', {role: $scope.oneSelected.id});
            } else if ($scope.viewType == "ROLE_INTERACTION") {
                $scope.specializationsTwo = specializationOne.viewedBy;
                $scope.missingTwo = subtractSpecializations($scope.allIds, $scope.specializationsTwo);
                if ($scope.missingTwo.length > 0)
                    $scope.selection.missingTwoToAdd = $scope.missingTwo[0];
            }
        };

        $scope.selectTwo = function(specializationTwo) {
            $state.go('course.settings.views.view.edit-role-interaction', {roleOne: $scope.oneSelected.id, roleTwo: specializationTwo.id});
        };

        $scope.createViewOne = function() {
            if ($scope.viewType == "ROLE_SINGLE") {
                $smartboards.request('views', 'createAspectView', {view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: {roleOne: $scope.selection.missingOneToAdd.id}}, function(data, err) {
                    if (err) {
                        giveError(err.description);
                        return;
                    }

                    $scope.viewSpecializations.push($scope.selection.missingOneToAdd);

                    $scope.missingOne.splice($scope.missingOne.indexOf($scope.selection.missingOneToAdd), 1);
                    if ($scope.missingOne.length > 0)
                        $scope.selection.missingOneToAdd = $scope.missingOne[0];
                });
            } else if ($scope.viewType == "ROLE_INTERACTION") {
                $smartboards.request('views', 'createAspectView', {view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: {roleOne: $scope.selection.missingOneToAdd.id, roleTwo: 'role.Default'}}, function(data, err) {
                    if (err) {
                        giveError(err.description);
                        return;
                    }

                    var newSpec = $.extend({viewedBy: [{id: 'role.Default', name: 'Default'}]}, $scope.selection.missingOneToAdd);
                    $scope.viewSpecializations.push(newSpec);

                    $scope.missingOne.splice($scope.missingOne.indexOf($scope.selection.missingOneToAdd), 1);
                    if ($scope.missingOne.length > 0)
                        $scope.selection.missingOneToAdd = $scope.missingOne[0];
                });
            }
        };

        $scope.createViewTwo = function() {
            $smartboards.request('views', 'createAspectView', {view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: {roleOne: $scope.oneSelected.id, roleTwo: $scope.selection.missingTwoToAdd.id}}, function(data, err) {
                if (err) {
                    giveError(err.description);
                    return;
                }

                $scope.oneSelected.viewedBy.push($scope.selection.missingTwoToAdd);
                $scope.missingTwo.splice($scope.missingTwo.indexOf($scope.selection.missingTwoToAdd), 1);
                if ($scope.missingTwo.length > 0)
                    $scope.selection.missingTwoToAdd = $scope.missingTwo[0];
            });
        };

        $scope.deleteViewOne = function(what, $event) {
            $event.stopPropagation();
            if ($stateParams.pageOrTemp=="template"){
                if (!confirm("Are you sure you want to delete the "+what.name+" aspect?\nAny template references that use it will also be deleted"))
                    return;
            }else{
                if (!confirm("Are you sure you want to delete the "+what.name+" aspect?"))
                    return;
            }
            
            $smartboards.request('views', 'deleteAspectView', {view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: {roleOne: what.id}}, function(data, err) {
                if (err) {
                    giveError(err.description);
                    return;
                }

                var item = what;
                $scope.viewSpecializations.splice($scope.viewSpecializations.indexOf(item), 1);
                if (item.viewedBy)
                    delete item.viewedBy;
                $scope.missingOne.push(item);
                if ($scope.missingOne.length > 0)
                    $scope.selection.missingOneToAdd = $scope.missingOne[0];

                if ($scope.oneSelected != undefined && $scope.oneSelected.id == item.id)
                    $scope.oneSelected = undefined;
            });
        };

        $scope.deleteViewTwo = function(what, $event) {
            $event.stopPropagation();
            if ($stateParams.pageOrTemp=="template"){
                if (!confirm("Are you sure you want to delete the "+what.name+" aspect?\nAny template references that use it will also be deleted"))
                    return;
            }else{
                if (!confirm("Are you sure you want to delete the "+what.name+" aspect?"))
                    return;
            }

            $smartboards.request('views', 'deleteAspectView', {view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: {roleOne: $scope.oneSelected.id, roleTwo: what.id}}, function(data, err) {
                if (err) {
                    giveError(err.description);
                    return;
                }

                var item = what;
                $scope.specializationsTwo.splice($scope.specializationsTwo.indexOf(item), 1);
                $scope.missingTwo.push(item);
                if ($scope.missingTwo.length > 0)
                    $scope.selection.missingTwoToAdd = $scope.missingTwo[0];
            });
        };
        $scope.gotoEdit = function() {
            $state.go('course.settings.views.view.edit-single');
        };

        if ($scope.viewType == "ROLE_SINGLE" || $scope.viewType == "ROLE_INTERACTION") {
            $scope.missingOne = subtractSpecializations($scope.allIds, $scope.viewSpecializations);
            for (var i = 0; i < $scope.missingOne.length; ++i) {
                if ($scope.missingOne[i].id.startsWith('special.')) {
                    $scope.missingOne.splice(i, 1);
                    --i;
                }
            }
            if ($scope.missingOne.length > 0)
                $scope.selection.missingOneToAdd = $scope.missingOne[0];
        }
        
        var el = $compile($('<div ng-include="\'' + $scope.modulesDir + '/views/partials/view-settings.html\'">'))($scope);
        $element.html(el);
    });
});

angular.module('module.views').controller('ViewEditController', function($rootScope, $state, $stateParams, $smartboards, $sbviews, $element, $compile, $scope) {
    var loadedView;
    var initialViewContent;

    var reqData = {course: $scope.course};
    if ($state.current.name == 'course.settings.views.view.edit-role-single')
        reqData.info = {role: $stateParams.role};
    if ($state.current.name == 'course.settings.views.view.edit-role-interaction')
        reqData.info = {roleOne: $stateParams.roleOne, roleTwo: $stateParams.roleTwo};

    $sbviews.requestEdit($stateParams.view, $stateParams.pageOrTemp, reqData, function(view, err) {
        if (err) {
            $element.html(err);
            console.log(err);
            return;
        }
        loadedView = view;
        initialViewContent = angular.copy(view.get());

        var controlsDiv = $('<div>');
        var btnSave = $('<button>Save</button>');
        btnSave.click(function() {
            btnSave.prop('disabled', true);
            var saveData = $.extend({}, reqData);
            saveData.view = $stateParams.view;
            saveData.pageOrTemp = $stateParams.pageOrTemp;
            saveData.content = view.get();
            console.log("saveEdit",saveData.content);
            $smartboards.request('views', 'saveEdit', saveData, function(data, err) {
                btnSave.prop('disabled', false);
                if (err) {
                    giveError(err.description);
                    return;
                }

                if (data != undefined)
                    giveError(data);
                else {
                    giveError('Saved!');
                }
                initialViewContent = angular.copy(saveData.content);
                location.reload();//reloading to prevent bug that kept adding new parts over again
            });
        });
        controlsDiv.append(btnSave);
        var btnPreview = $('<button>Preview</button>');
        btnPreview.click(function() {
            btnPreview.prop('disabled', true);
            var editData = $.extend({}, reqData);
            editData.view = $stateParams.view;
            editData.pageOrTemp = $stateParams.pageOrTemp;
            editData.content = view.get();
            $smartboards.request('views', 'previewEdit', editData, function(data, err) {
                btnPreview.prop('disabled', false);
                if (err) {
                    giveError(err.description);
                    return;
                }

                var viewScope = $scope.$new(true);
                viewScope.view = data.view;

                viewScope.viewBlock = {
                    partType: 'block',
                    noHeader: true,
                    children: viewScope.view.children
                };

                var viewBlock = $sbviews.build(viewScope, 'viewBlock');
                viewBlock.removeClass('block');
                viewBlock.addClass('view');
                $compile(viewBlock)(viewScope);

                view.element.hide();
                controlsDiv.hide();

                var btnClosePreview = $('<button>Close Preview</button>');
                btnClosePreview.click(function() {
                    viewBlock.remove();
                    btnClosePreview.remove();
                    view.element.show();
                    controlsDiv.show();
                });
                $element.append(viewBlock);
                $element.prepend(btnClosePreview);
            });
        });
        controlsDiv.append(btnPreview);

        $scope.canUndo = view.canUndo;
        $scope.undo = view.undo;
        var btnUndo = $('<button ng-if="canUndo()" ng-click="undo()">Undo</button>');
        $compile(btnUndo)($scope);
        controlsDiv.append(btnUndo);

        $scope.canRedo = view.canRedo;
        $scope.redo = view.redo;
        var btnRedo = $('<button ng-if="canRedo()" ng-click="redo()">Redo</button>');
        $compile(btnRedo)($scope);
        controlsDiv.append(btnRedo);

        $element.html(view.element);
        $element.prepend(controlsDiv);
    });

    var watcherDestroy = $rootScope.$on('$stateChangeStart', function($event, toState, toParams) {
        if (initialViewContent == undefined || loadedView == undefined) {
            watcherDestroy();
            return;
        }
        if (JSON.stringify(initialViewContent) !== JSON.stringify(loadedView.get())) {
            if (confirm("There are unsaved changes. Leave page without saving?")) {
                watcherDestroy();
                return;
            }
            $event.preventDefault();
        } else
            watcherDestroy();
    });
});

angular.module('module.views').controller('ViewsList', function($smartboards, $element, $compile, $scope,$state,$sbviews) {
    $smartboards.request('views', 'listViews', {course: $scope.course}, function(data, err) {
        if (err) {
            giveError(err.description);
            return;
        }

        //associate information to scope -> accessible on search
        $scope.pages = Object.values(data.pages);
        $scope.templates = Object.values(data.templates);
        $scope.globals = Object.values(data.globals);
        
        //all the information is saved so we can filter it
        $scope.allPages = Object.values(data.pages);
        $scope.allTemplates = Object.values(data.templates);
        $scope.allGlobals = Object.values(data.globals);

        search = $("<div class='search'> <input type='text' id='seach_input' placeholder='Search..' name='search' ng-change='reduceList()' ng-model='search' ><button class='magnifying-glass' id='search-btn' ng-click='reduceList()'></button>  </div>")
        $compile(search)($scope);
        $element.append(search);

        //pages section
        var viewsArea = createSection($($element),"Pages");
        viewsArea.attr("id","pages");
        box = $('<div class="card"  ng-repeat="(id, page) in pages" ></div>');
        box.append( $('<div class="color_box"><div class="box" ></div> <div  class="frame frame-page" ><span class="edit_icon" ng-click="editView(id,\'page\')"></span></div></div>'));
        box.append( $('<div class="footer with_status"><div class="page_info"><span>{{page.name}}</span> <span>(id: {{id}})</span></div><div class="page_actions"><span class="config_icon icon" ng-click="editView(id,\'page\')"></span><span class="delete_icon icon" ng-click="deleteView(page,\'page\')"></span></div></div>'))
        box.append( $('<div class="status enable">Enabled<div class="background"></div></div>'))
        $compile(box)($scope);
        viewsArea.append(box);
        viewsArea.append($compile('<div class="add_button icon" ng-click="createView(\'page\')"></div>')($scope));
        //error section
        viewsArea.append( $("<div class='error_box'><div id='empty_pages' class='error_msg'></div></div>"));
    
        //templates section
        var TemplateArea = createSection($($element),"View Templates");
        TemplateArea.attr("id", "templates");
        box = $('<div class="card"  ng-repeat="template in templates" ></div>');
        box.append( $('<div class="color_box"><div class="box" ></div> <div  class="frame frame-page" ><span class="edit_icon" ng-click="editView(template.id,\'template\')"></span></div></div>'));
        box.append( $('<div class="footer"><div class="page_name">{{template.name}}</div><div class="template_actions">'+
                '<span class="config_icon icon" ng-click="editView(template.id,\'template\')"></span>'+
                '<span class="globalize_icon icon" ng-if="template.isGlobal==false" ng-click="globalize(template)"></span>'+
                '<span class="de_globalize_icon icon" ng-if="template.isGlobal==true" ng-click="globalize(template)"></span>'+
                '<span class="export_icon_no_outline icon" ng-click="exportTemplate(template)">'+
                '</span><span class="delete_icon icon" ng-click="deleteView(template,\'template\')"></span></div></div>'))
        //box.append( $('<div class="status enable">Enabled<div class="background"></div></div>'))
        $compile(box)($scope);
        TemplateArea.append(box);
        TemplateArea.append($compile('<div class="add_button icon" ng-click="createView(\'template\')"></div>')($scope));
        //error section
        TemplateArea.append( $("<div class='error_box'><div id='empty_templates' class='error_msg'></div></div>"));
    
        // global templates section
        var globalTemplateArea = createSection($($element),"Global Templates");
        globalTemplateArea.attr("id", "templates");
        box = $('<div class="card"  ng-repeat="template in globals"></div>');
        box.append( $('<div class="color_box"><div class="box" ></div> <div  class="frame frame-page" ><span class="add_icon_no_outline" ng-if="template.course!=course" ng-click="useGlobal(template)"></span></div></div>'));
        box.append( $('<div class="footer"><div class="page_name">{{template.name}}</div></div>'))
        //box.append( $('<div class="status enable">Enabled<div class="background"></div></div>'))
        $compile(box)($scope);
        globalTemplateArea.append(box);
        //error section
        globalTemplateArea.append( $("<div class='error_box'><div id='empty_globals' class='error_msg'></div></div>"));
    
     

        //new view modal
        modal = $("<div class='modal' style='' id='new-view'></div>");
        newView = $("<div class='modal_content'></div>");
        newView.append( $('<button class="close_btn icon" value="#new-view" onclick="closeModal(this)"></button>'));
        newView.append( $('<div class="title">New {{newView.pageOrTemp}}: </div>'));
        content = $('<div class="content">');
        box = $('<div class="inputs" id="inputs_view_box">');
        box.append( $('<div class="name full"><input type="text" class="form__input " id="name" placeholder="Name *" ng-model="newView.name"/> <label for="name" class="form__label">Name</label></div>'))
        roleType = ( $('<select class="form__input" ng-options="type.id as type.name for type in types" ng-model="newView.roleType"></select>'));
        roleType.append($('<option value="" disabled selected>Select a role type *</option>'));
        box.append(roleType);
        row = $('<div id="active_page" class= "row"></div>');
        row.append( $('<div class= "on_off"><span>Enable </span><label class="switch"><input id="active" type="checkbox" ng-model="newView.IsActive"><span class="slider round"></span></label></div>'))
        box.append(row);
        content.append(box);
        content.append( $('<button class="save_btn" ng-click="saveView()" ng-disabled="!isReadyToSubmit()" > Save </button>'))
        newView.append(content);
        modal.append(newView);
        $compile(modal)($scope);
        $element.append(modal);


        // delete verification modal
        delete_modal = $("<div class='modal' id='delete-view'></div>");
        verification = $("<div class='verification modal_content'></div>");
        verification.append( $('<button class="close_btn icon" value="#delete-view" onclick="closeModal(this)"></button>'));
        verification.append( $('<div class="warning">Are you sure you want to delete this View?</div>'));
        verification.append( $('<div id="for_template_warning" class="warning">Any template references that use it will also be deleted</div>'));
        verification.append( $('<div class="target">{{view.pageOrTemp}}: {{view.name}} (id: {{view.id}})</div>'));
        verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#delete-view" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="submitDelete()"> Delete</button></div>'))
        delete_modal.append(verification);
        $compile(delete_modal)($scope);
        $element.append(delete_modal);

        // alert modal
        delete_modal = $("<div class='modal' id='alert-view'></div>");
        verification = $("<div class='verification modal_content'></div>");
        verification.append( $('<button class="close_btn icon" value="#alert-view" onclick="closeModal(this)"></button>'));
        verification.append( $('<div class="warning">File created. To use it move to a module folder and edit the module file.</div>'));
        verification.append( $('<div class="target">{{response}}</div>'));
        verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#alert-view" onclick="closeModal(this)">Continue</button></div>'))

        delete_modal.append(verification);
        $compile(delete_modal)($scope);
        $element.append(delete_modal);


        angular.extend($scope, data);
        
        $scope.createView = function(pageOrTemp){
            $scope.newView = {name: '', roleType: '', pageOrTemp: pageOrTemp, course: $scope.course, IsActive: false};

            $scope.saveView = function () {
                $smartboards.request('views','createView',$scope.newView,function(data,err){
                    if (err) {
                        giveError(err.description);
                        return;
                    }
                    location.reload();
                });
            };
            //criar funcao de verificacao
            $scope.isReadyToSubmit = function() {
                isValid = function(text){
                    return  (text != "" && text != undefined && text != null)
                }
                //validate inputs
                if (isValid($scope.newView.name) &&
                isValid($scope.newView.roleType) ){
                    return true;
                }
                else{
                    return false;
                }
            }

            if (pageOrTemp == "page"){
                $("#active_page").show();
                $("#inputs_view_box").attr("style", "padding-bottom: 0px");
            }
            else{
                $("#active_page").hide();
                $("#inputs_view_box").attr("style", "padding-bottom: 26px");
            }

            $("#new-view").show();
        };
        $scope.editView = function(id,pageOrTemp){
            $state.go("course.settings.views.view",{pageOrTemp: pageOrTemp,view:id});
        };
        $scope.globalize = function(template){
            $smartboards.request('views','globalizeTemplate',{course: $scope.course, id: template.id,isGlobal: template.isGlobal},function(data,err){
                if (err) {
                    giveError(err.description);
                    return;
                }
                location.reload();
            });
        };
        $scope.useGlobal = function(template){
            console.log(template);
            $smartboards.request('views','copyGlobalTemplate',{course: $scope.course, template: template},function(data,err){
                if (err) {
                    giveError(err.description);
                    return;
                }
                location.reload();
            });
        };
        $scope.exportTemplate = function(template){
            $smartboards.request('views', 'exportTemplate', {course:$scope.course, id: template.id,name:template.name}, function(data, err) {
                if (err) {
                    giveError(err.description);
                    return;
                }
                $scope.response = data.filename;
                $("#alert-view").show();
            });
        };
        $scope.deleteView = function(view,templateOrPage) {
            $scope.view = {name: view.name, pageOrTemp: templateOrPage, course: $scope.course, id: view.id}
            $("#delete-view").show();

            if (templateOrPage=="template"){
                $("#for_template_warning").show();
            }else{
                $("#for_template_warning").hide();
            }
            $scope.submitDelete = function (){
                $smartboards.request('views', 'deleteView', $scope.view, function(data, err) {
                    if (err) {
                        giveError(err.description);
                        return;
                    }
                    location.reload();
                });
            }
            
        };
        $scope.reduceList = function(){
            $("#empty_pages").empty();
            $("#empty_templates").empty();
            $("#empty_globals").empty();
            $scope.pages = $scope.allPages;
            $scope.templates = $scope.allTemplates;
            $scope.globals = $scope.allGlobals;

            filteredPages = [];
            filteredTemplates = [];
            filteredGlobals = [];
            text = $scope.search;
            if (validateSearch(text)){
                //match por name e short
                jQuery.each($scope.pages , function( index ){
                    view_obj = $scope.pages[index];
                    if (view_obj.name.toLowerCase().includes(text.toLowerCase())){
                        filteredPages.push(view_obj);
                    }
                });
                jQuery.each($scope.templates , function( index ){
                    view_obj = $scope.templates[index];
                    if (view_obj.name.toLowerCase().includes(text.toLowerCase())){
                        filteredTemplates.push(view_obj);
                    }
                });
                jQuery.each($scope.globals , function( index ){
                    view_obj = $scope.globals[index];
                    if (view_obj.name.toLowerCase().includes(text.toLowerCase())){
                        filteredGlobals.push(view_obj);
                    }
                });
                
                if(filteredPages.length == 0){
                    $("#empty_pages").append("No matches found");
                }
                if(filteredTemplates.length == 0){
                    $("#empty_templates").append("No matches found");
                }
                if(filteredGlobals.length == 0){
                    $("#empty_globals").append("No matches found");
                }
                $scope.pages = filteredPages;
                $scope.templates = filteredTemplates;
                $scope.globals = filteredGlobals;
            }
            
        }
    });
});

//controller for pages that are created in the views page
angular.module('module.views').controller('CustomUserPage', function ($stateParams, $element, $scope, $sbviews) {
    changeTitle($stateParams.name, 1);
    $sbviews.request($stateParams.id, {course: $scope.course, user: $stateParams.userID}, function(view, err) {
        if (err) {
            console.log(err);
            return;
        }
        $element.append(view.element);
    });
});
angular.module('module.views').controller('CustomPage', function ($stateParams,$rootScope, $element, $scope, $sbviews, $compile, $state) {
    changeTitle($stateParams.name, 1);
    $sbviews.request($stateParams.id, {course: $scope.course}, function(view, err) {
        if (err) {
            console.log(err);
            return;
        }
        $element.append(view.element);
    });
});
angular.module('module.views').config(function($stateProvider) {
    $stateProvider.state('course.customUserPage', {
        url: '/{name:[A-z0-9]+}-{id:[0-9]+}/{userID:[0-9]{1,5}}',
        views: {
            'main-view@':{
                controller: 'CustomUserPage'
            }
        }
    }).state('course.customPage', {
        url: '/{name:[A-z0-9]+}-{id:[0-9]+}',
        views: {
            'main-view@':{
                controller: 'CustomPage'
            }
        }
    }).state('course.settings.views', {
        url: '/views',
        views: {
            'tabContent@course.settings': {
                //template: '<div ng-repeat="(id, view) in views">{{view.name}} (view id: {{id}}, module:{{view.module}})</div><ul class="templates-list"><strong>Templates</strong><li ng-repeat="template in templates">{{template}} <button ng-click="deleteTemplate(template)">Delete</button></li></ul>',
                controller: 'ViewsList'
            }
        }
    }).state('course.settings.views.view', {
        url: '/{pageOrTemp:(?:template|page)}/{view:[A-z0-9]+}',
        views: {
            'tabContent@course.settings': {
                template: 'abc',
                controller: 'ViewSettings'
            }
        }
    }).state('course.settings.views.view.edit-single', {
        url: '/edit',
        views: {
            'tabContent@course.settings': {
                template: '',
                controller: 'ViewEditController'
            }
        }
    }).state('course.settings.views.view.edit-role-single', {
        url: '/edit/{role:[A-Za-z0-9.]+}',
        views: {
            'tabContent@course.settings': {
                template: '',
                controller: 'ViewEditController'
            }
        }
    }).state('course.settings.views.view.edit-role-interaction', {
        url: '/edit/{roleOne:[A-Za-z0-9.]+}/{roleTwo:[A-Za-z0-9.]+}',
        views: {
            'tabContent@course.settings': {
                template: '',
                controller: 'ViewEditController'
            }
        }
    });
});