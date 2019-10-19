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
                        alert(err.description);
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
                        alert(err.description);
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
                    alert(err.description);
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
                    alert(err.description);
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
                    alert(err.description);
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
                    alert(err.description);
                    return;
                }

                if (data != undefined)
                    alert(data);
                else {
                    alert('Saved!');
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
                    alert(err.description);
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
            alert(err.description);
            return;
        }
        var tableElement = '<td style="padding: 4px;">';
        var viewsArea = createSection($($element),"Pages");
        viewsArea.append($compile('<tr ng-repeat="(id, page) in pages"><td>{{page.name}} (page id: {{id}})</td>'+
                tableElement+'<button ng-click="editView(id,\'page\')">Edit</button></td> '+
                tableElement+'<button ng-click="deleteView(page,\'page\')">Delete</button> </td></tr>')($scope));
        viewsArea.append($compile('<button ng-click="createView(\'page\')">Create New Page</button>')($scope));
        
        var TemplateArea = createSection($($element),"View Templates");
        TemplateArea.append($compile('<tr  ng-repeat="template in templates"><td>{{template.name}}</td>'+
                tableElement+'<button ng-click="editView(template.id,\'template\')">Edit</button> </td>'+
                tableElement+'<button ng-if="template.isGlobal==false" ng-click="globalize(template)">Globalize</button> '+
                '<button ng-if="template.isGlobal==true" ng-click="globalize(template)">De-Globalize</button></td> '+
                tableElement+'<button ng-click="deleteView(template,\'template\')">Delete</button></td> '+
                tableElement+'<button ng-click="exportTemplate(template)">Export</button></td></tr>')($scope));
        TemplateArea.append($compile('<button ng-click="createView(\'template\')">Create New Template</button>')($scope));
        
        var globalTemplateArea = createSection($($element),"Global Templates");
        globalTemplateArea.append($compile('<div ng-repeat="template in globals">{{template.name}} '+
                '<button ng-if="template.course!=course" ng-click="useGlobal(template)">Add to course</button> </div>')($scope));//ToDo
                
        angular.extend($scope, data);
        
        $scope.createView = function(pageOrTemp){
            //show overlay to ask name of view and the if this is a template ask roleType
            $sbviews.openOverlay(function (el, execClose) {
                var optionsScope = $scope.$new();
                optionsScope.closeOverlay = function () {
                    execClose();
                };
                optionsScope.view={name: '',roleType: 'ROLE_SINGLE',pageOrTemp: pageOrTemp,course:$scope.course};

                optionsScope.saveView = function () {
                    $smartboards.request('views','createView',optionsScope.view,function(data,err){
                        if (err) {
                            alert(err.description);
                            return;
                        }
                        location.reload();
                    });
                };
                var wrapper = $('<div>');// ng-disabled=
                wrapper.append('<div class="title"><span>Create {{view.pageOrTemp}}</span><img src="images/close.svg" ng-click="closeOverlay()"></div>');
                var content = $('<div style="padding: 10px">');
                var inputName = $('<sb-input sb-input="view.name" sb-input-label="Name of {{view.pageOrTemp}}">');
                content.append(inputName);
                content.append($('<span> Role Type:</span>'+
                        '<select ng-options="type.id as type.name for type in types" ng-model="view.roleType"></select>'));
                content.append($('<br><button ng-click="saveView()">Save</button></sb-input>'));
                wrapper.append(content);
                $compile(wrapper)(optionsScope);
                el.append(wrapper);
            }, function () {});
        };
        $scope.editView = function(id,pageOrTemp){
            $state.go("course.settings.views.view",{pageOrTemp: pageOrTemp,view:id});
        };
        $scope.globalize = function(template){
            $smartboards.request('views','globalizeTemplate',{course: $scope.course, id: template.id,isGlobal: template.isGlobal},function(data,err){
                if (err) {
                    alert(err.description);
                    return;
                }
                location.reload();
            });
        };
        $scope.useGlobal = function(template){
            console.log(template);
            $smartboards.request('views','copyGlobalTemplate',{course: $scope.course, template: template},function(data,err){
                if (err) {
                    alert(err.description);
                    return;
                }
                location.reload();
            });
        };
        $scope.exportTemplate = function(template){
            $smartboards.request('views', 'exportTemplate', {course:$scope.course, id: template.id,name:template.name}, function(data, err) {
                if (err) {
                    alert(err.description);
                    return;
                }
                alert("File created: " + data.filename +  "\n"+ "To use it move to a module folder and edit the module file");
            });
        };
        $scope.deleteView = function(view,templateOrPage) {
            if (templateOrPage=="template"){
                if (!confirm("Are you sure you want to delete the "+templateOrPage+" '"+view.name+"'?\nAny template references that use it will also be deleted"))
                    return;
            }else{
                if (!confirm("Are you sure you want to delete the "+templateOrPage+" '"+view.name+"'?"))
                    return;
            }
            $smartboards.request('views', 'deleteView', {course: $scope.course, id: view.id,pageOrTemp:templateOrPage}, function(data, err) {
                if (err) {
                    alert(err.description);
                    return;
                }
                location.reload();
                //$scope.templates.splice($scope.templates.indexOf(view), 1);
            });
        };
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