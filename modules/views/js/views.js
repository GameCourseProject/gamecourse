angular.module('module.views', []);
//not used
angular.module('module.views').controller('ViewSettings', function ($state, $stateParams, $smartboards, $element, $compile, $scope) {
    $element.html('Loading...');
    $smartboards.request('views', 'getInfo', { view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }
        function subtractSpecializations(one, two) {
            var cpy = one.slice(0);
            for (var i = 0; i < cpy.length; ++i) {
                for (var j = 0; j < two.length; ++j) {
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

        $scope.name = $stateParams.name;
        $scope.viewType = $scope.viewSettings.roleType;
        $scope.pageOrTemp = data.pageOrTemp;
        $scope.selection = {};
        $scope.selectedStyle = {
            'background-color': 'rgba(0, 0, 0, 0.07)'
        };

        $scope.selectOne = function (specializationOne) {
            $scope.oneSelected = specializationOne;
            if ($scope.viewType == "ROLE_SINGLE") {
                $state.go('course.settings.views.view.edit-role-single', { role: $scope.oneSelected.id });
            } else if ($scope.viewType == "ROLE_INTERACTION") {
                $scope.specializationsTwo = specializationOne.viewedBy;
                $scope.missingTwo = subtractSpecializations($scope.allIds, $scope.specializationsTwo);
                if ($scope.missingTwo.length > 0)
                    $scope.selection.missingTwoToAdd = $scope.missingTwo[0];
            }
        };

        $scope.selectTwo = function (specializationTwo) {
            $state.go('course.settings.views.view.edit-role-interaction', { roleOne: $scope.oneSelected.id, roleTwo: specializationTwo.id });
        };

        $scope.createViewOne = function () {
            if ($scope.viewType == "ROLE_SINGLE") {
                $smartboards.request('views', 'createAspectView', { view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: { roleOne: $scope.selection.missingOneToAdd.id } }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }

                    $scope.viewSpecializations.push($scope.selection.missingOneToAdd);

                    $scope.missingOne.splice($scope.missingOne.indexOf($scope.selection.missingOneToAdd), 1);
                    if ($scope.missingOne.length > 0)
                        $scope.selection.missingOneToAdd = $scope.missingOne[0];
                });
            } else if ($scope.viewType == "ROLE_INTERACTION") {
                $smartboards.request('views', 'createAspectView', { view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: { roleOne: $scope.selection.missingOneToAdd.id, roleTwo: 'role.Default' } }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }

                    var newSpec = $.extend({ viewedBy: [{ id: 'role.Default', name: 'Default' }] }, $scope.selection.missingOneToAdd);
                    $scope.viewSpecializations.push(newSpec);

                    $scope.missingOne.splice($scope.missingOne.indexOf($scope.selection.missingOneToAdd), 1);
                    if ($scope.missingOne.length > 0)
                        $scope.selection.missingOneToAdd = $scope.missingOne[0];
                });
            }
        };

        $scope.createViewTwo = function () {
            $smartboards.request('views', 'createAspectView', { view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: { roleOne: $scope.oneSelected.id, roleTwo: $scope.selection.missingTwoToAdd.id } }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }

                $scope.oneSelected.viewedBy.push($scope.selection.missingTwoToAdd);
                $scope.missingTwo.splice($scope.missingTwo.indexOf($scope.selection.missingTwoToAdd), 1);
                if ($scope.missingTwo.length > 0)
                    $scope.selection.missingTwoToAdd = $scope.missingTwo[0];
            });
        };

        $scope.deleteViewOne = function (what, $event) {
            $event.stopPropagation();
            if ($stateParams.pageOrTemp == "template") {
                if (!confirm("Are you sure you want to delete the " + what.name + " aspect?\nAny template references that use it will also be deleted"))
                    return;
            } else {
                if (!confirm("Are you sure you want to delete the " + what.name + " aspect?"))
                    return;
            }

            $smartboards.request('views', 'deleteAspectView', { view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: { roleOne: what.id } }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
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

        $scope.deleteViewTwo = function (what, $event) {
            $event.stopPropagation();
            if ($stateParams.pageOrTemp == "template") {
                if (!confirm("Are you sure you want to delete the " + what.name + " aspect?\nAny template references that use it will also be deleted"))
                    return;
            } else {
                if (!confirm("Are you sure you want to delete the " + what.name + " aspect?"))
                    return;
            }

            $smartboards.request('views', 'deleteAspectView', { view: $stateParams.view, pageOrTemp: $stateParams.pageOrTemp, course: $scope.course, info: { roleOne: $scope.oneSelected.id, roleTwo: what.id } }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }

                var item = what;
                $scope.specializationsTwo.splice($scope.specializationsTwo.indexOf(item), 1);
                $scope.missingTwo.push(item);
                if ($scope.missingTwo.length > 0)
                    $scope.selection.missingTwoToAdd = $scope.missingTwo[0];
            });
        };
        $scope.gotoEdit = function () {
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
        var el = $('<div ng-include="\'' + $scope.modulesDir + '/views/partials/view-settings.html\'">');
        $element.html(el);
        $compile(el)($scope);

    });

});

angular.module('module.views').controller('ViewEditController', function ($rootScope, $state, $stateParams, $smartboards, $sbviews, $element, $compile, $scope) {
    var loadedView;
    var initialViewContent;

    var viewEditorWindow = $('<div id="viewEditor"></div>')
    $element.append(viewEditorWindow);

    var breadcrum = $("<div id='page_history'></div>");
    breadcrum.append($("<span class='clickable' ui-sref='course.settings.views'> Views </span>"));
    breadcrum.append($("<div class='go_back icon' ui-sref='course.settings.views'></div>"));
    breadcrum.append($("<span class='clickable' ui-sref='course.settings.views'>Templates</span>"));
    breadcrum.append($("<div class='go_back icon'></div>"));
    breadcrum.append($("<span>" + $stateParams.name + "</span>"));

    var helper = $('<div class="side_helper">');
    var open_helper = $('<div id="open_helper">');
    open_helper.append($('<span class="help icon"></span><span id="arrow" class="open icon"></span>'));
    var helper_content = $('<div id="helper_content">');
    helper_content.append($('<span><a target="_blank" href="./docs">About Views</a></span>'));
    helper_content.append($('<span><a target="_blank" href="./docs/functions">Available Functions</a></span>'));

    helper.append(open_helper);
    helper.append(helper_content);
    open_helper.click(function () {
        arrow = $("#arrow");
        if (helper_content.hasClass("visible")) {
            helper_content.removeClass("visible");
            helper_content.addClass("invisible");
            arrow.removeClass("closed");
        }
        else {
            helper_content.removeClass("invisible");
            helper_content.addClass("visible");
            arrow.addClass("closed");
        }
    });

    breadcrum.append($("<span id='warning_ref'>The selected view is a reference for a template!</span>"));


    var reqData = { course: $scope.course };
    if ($state.current.name == 'course.settings.views.edit-role-single') {
        reqData.roles = { viewerRole: "Default" };
        //breadcrum.append($("<span class='role_type'>" + $stateParams.role + "</span>"));
    }
    if ($state.current.name == 'course.settings.views.edit-role-interaction') {
        reqData.roles = { viewerRole: "Default", userRole: "Default" };
        //breadcrum.append($("<span class='role_type'>" + $stateParams.roleOne + " - " + $stateParams.roleTwo + "</span>"));
    }

    $sbviews.requestEdit($stateParams.view, "template", reqData, function (view, err) {
        if (err) {
            viewEditorWindow.html(err);
            console.log(err);
            console.log(err.description);
            return;
        }
        loadedView = view;
        initialViewContent = angular.copy(view.get());
        $scope.courseRoles = view.courseRoles;
        //$scope.viewRoles = view.viewRoles;
        // $scope.selectedVRole = $scope.viewRoles[0].id;
        $scope.roleType = view.roleType;

        if (view.roleType == 'ROLE_SINGLE') {
            $scope.viewerRoles = view.viewRoles;
            $scope.selectedVRole = $scope.viewRoles[0].id;
        } else {
            $scope.userRoles = view.viewRoles[0];
            $scope.viewerRoles = view.viewRoles[1];
            $scope.selectedURole = $scope.viewRoles[0][0].id;
            $scope.selectedVRole = $scope.viewRoles[1][0].id;
        }

        selectViews = function () {
            const views = $("#viewEditor")[0];
            const targetRole = $("#viewer_role").find(":selected")[0].text;
            $sbviews.findViewsForRole(views, targetRole);
            // if ($state.current.name == 'course.settings.views.edit-role-single') {
            //     reqData.roles = { viewerRole: $("#viewer_role").find(":selected")[0].text };
            // }
            // if ($state.current.name == 'course.settings.views.edit-role-interaction') {
            //     reqData.roles = { viewerRole: $("#viewer_role").find(":selected")[0].text, userRole: $("#user_role").find(":selected")[0].text };
            // }
            // // so funciona para quando as views estao na Bd
            // $sbviews.requestEdit($stateParams.view, "template", reqData, function (view, err) {
            //     if (err) {
            //         viewEditorWindow.html(err);
            //         console.log(err);
            //         console.log(err.description);
            //         return;
            //     }
            //     console.log("editView:", view);

            //     viewEditorWindow[0].lastChild.remove();
            //     viewEditorWindow.append(view.element);
            // });
        };

        var controlsDiv = $('<div class="action-buttons" id="view_editor_actions">');

        var dropdownRoles = $('<div class="editor-roles">');
        if ($scope.roleType == 'ROLE_INTERACTION') {
            dropdownRoles.append($('<div style="margin-right:5px;">User: </div><select id="user_role" onchange="selectViews()" ng-options="role.id as role.name for role in userRoles" ng-model="selectedURole" ng-selected="role.id==selectedURole"></select></div>'));
        }
        dropdownRoles.append($('<div style="margin-right:5px;">View as: </div><select id="viewer_role" onchange="selectViews()" ng-options="role.id as role.name for role in viewerRoles" ng-model="selectedVRole" ng-selected="role.id==selectedVRole"></select></div>'));
        $compile(dropdownRoles)($scope);
        controlsDiv.append(dropdownRoles);

        $scope.canUndo = view.canUndo;
        $scope.undo = view.undo;
        var btnUndoActive = $('<div ng-if="canUndo()" id="undo_icon" class="icon undo_icon" title="Undo" ng-click="undo()"></div>');
        var btnUndoDisabled = $('<div ng-if="!canUndo()" id="undo_icon" class="icon undo_icon disabled" title="Redo"></div>');
        $compile(btnUndoActive)($scope);
        $compile(btnUndoDisabled)($scope);
        controlsDiv.append(btnUndoActive);
        controlsDiv.append(btnUndoDisabled);

        $scope.canRedo = view.canRedo;
        $scope.redo = view.redo;
        var btnRedoActive = $('<div ng-if="canRedo()" id="redo_icon" class="icon redo_icon" title="Redo" ng-click="redo()"></div>');
        var btnRedoDisabled = $('<div ng-if="!canRedo()" id="redo_icon" class="icon redo_icon disabled" title="Undo" ></div>');
        $compile(btnRedoActive)($scope);
        $compile(btnRedoDisabled)($scope);
        controlsDiv.append(btnRedoActive);
        controlsDiv.append(btnRedoDisabled);

        //meter so save quando e preciso
        var btnSave = $('<button>Save Changes</button>');
        btnSave.click(function () {
            btnSave.prop('disabled', true);
            if ($state.current.name == 'course.settings.views.edit-role-single') {
                reqData.roles = { viewerRole: $("#viewer_role").find(":selected")[0].text };
                //breadcrum.append($("<span class='role_type'>" + $stateParams.role + "</span>"));
            }
            if ($state.current.name == 'course.settings.views.edit-role-interaction') {
                reqData.roles = { viewerRole: $("#viewer_role").find(":selected")[0].text, userRole: $("#user_role").find(":selected")[0].text };
                //breadcrum.append($("<span class='role_type'>" + $stateParams.roleOne + " - " + $stateParams.roleTwo + "</span>"));
            }
            var saveData = $.extend({}, reqData);
            saveData.view = $stateParams.view;
            saveData.pageOrTemp = $stateParams.pageOrTemp;
            saveData.content = $rootScope.partsHierarchy;
            console.log("saveEdit", saveData.content);
            html2canvas($("#viewEditor .view.editing"), {
                onrendered: function (canvas) {
                    var img = canvas.toDataURL();
                    saveData.sreenshoot = img;
                    $smartboards.request('views', 'saveEdit', saveData, function (data, err) {
                        btnSave.prop('disabled', false);
                        if (err) {
                            giveMessage(err.description);
                            return;
                        }

                        if (data != undefined)
                            giveMessage(data);
                        else {
                            giveMessage('Saved!');
                        }
                        initialViewContent = angular.copy(saveData.content);
                        //location.reload();//reloading to prevent bug that kept adding new parts over again
                    });
                }
            });

        });
        controlsDiv.append(btnSave);
        var btnPreview = $('<button>Preview</button>');
        btnPreview.click(function () {
            btnPreview.prop('disabled', true);
            var editData = $.extend({}, reqData);
            editData.view = $stateParams.view;
            editData.pageOrTemp = $stateParams.pageOrTemp;
            editData.content = view.get();
            $smartboards.request('views', 'previewEdit', editData, function (data, err) {
                btnPreview.prop('disabled', false);
                if (err) {
                    giveMessage(err.description);
                    return;
                }

                var viewScope = $scope.$new(true);
                viewScope.view = data.view;

                viewScope.viewBlock = {
                    partType: 'block',
                    noHeader: false,
                    children: viewScope.view.children,
                };
                if (viewScope.view.header) {
                    viewScope.viewBlock.header = viewScope.view.header;
                }

                var viewBlock = $sbviews.build(viewScope, 'viewBlock');
                viewBlock.removeClass('block');
                viewBlock.addClass('view');
                $compile(viewBlock)(viewScope);

                view.element.hide();
                controlsDiv.hide();

                acion_buttons = $('<div class="action-buttons" >');
                acion_buttons.css("width", "130px");
                var btnClosePreview = $('<button>Close Preview</button>');
                btnClosePreview.click(function () {
                    viewBlock.remove();
                    acion_buttons.remove();
                    view.element.show();
                    controlsDiv.show();
                });
                acion_buttons.append(btnClosePreview);
                viewEditorWindow.append(viewBlock);
                viewEditorWindow.prepend(acion_buttons);
            });
        });
        controlsDiv.append(btnPreview);


        viewEditorWindow.html(view.element);
        viewEditorWindow.prepend(controlsDiv);
        $compile(breadcrum)($scope);

        $scope.roleType == 'ROLE_INTERACTION' ? document.getElementById('view_editor_actions').style.width = '580px' : null;
        viewEditorWindow.prepend(breadcrum);
        viewEditorWindow.prepend(helper);
    });

    var watcherDestroy = $rootScope.$on('$stateChangeStart', function ($event, toState, toParams) {
        if (initialViewContent == undefined || loadedView == undefined) {
            watcherDestroy();
            return;
        }
        console.log(JSON.stringify(initialViewContent));
        console.log(JSON.stringify(loadedView.get()));
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
angular.module('module.views').controller('ViewsList', function ($smartboards, $element, $compile, $scope, $state, $sbviews) {
    $smartboards.request('views', 'listViews', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        //associate information to scope -> accessible on search
        $scope.pages = Object.values(data.pages);
        $scope.templates = Object.values(data.templates);
        $scope.globals = Object.values(data.globals);

        console.log(data.templates);

        //all the information is saved so we can filter it
        $scope.allPages = Object.values(data.pages);
        $scope.allTemplates = Object.values(data.templates);
        $scope.allGlobals = Object.values(data.globals);

        search = $("<div class='search'> <input type='text' id='seach_input' placeholder='Search..' name='search' ng-change='reduceList()' ng-model='search' ><button class='magnifying-glass' id='search-btn' ng-click='reduceList()'></button>  </div>")
        $compile(search)($scope);
        $element.append(search);

        var time = new Date().getTime(); //for image reload purpose

        //pages section
        var viewsArea = createSection($($element), "Pages");
        viewsArea.attr("id", "pages");
        box = $('<div class="card"  ng-repeat="(id, page) in pages" ></div>');
        box.append($('<div class="color_box"><div class="box" ></div> <div  class="frame frame-page" style="background-image: url(/gamecourse/screenshoots/page/{{id}}.png?' + time + ');">'));
        //+ <span class="edit_icon" title="Edit" ng-click="editView(id,\'page\',page.name)"></span></div></div>'));
        //box.append($('<div class="footer"><div class="page_info"><span>{{page.name}}</span> <span>(id: {{id}})</span></div><div class="page_actions"><span class="delete_icon icon" title="Remove" ng-click="deleteView(page,\'page\')"></span></div></div>'))

        //for the configure/edit info of the page
        // for the enable/disable feature of pages

        box.append($('<div class="footer with_status"><div class="page_info"><span>{{page.name}}</span> <span>(id: {{id}})</span></div><div class="page_actions">' +
            '<span class="config_icon icon" title="Edit" value="#edit-view" onclick="openModal(this)" ng-click="configureView(page,\'page\')"></span>' +
            '<span class="delete_icon icon" ng-click="deleteView(page,\'page\')"></span></div></div>'));
        box.append($('<div ng-if="page.isEnabled != true" class="status disable">Disabled <div class="background"></div></div>'));
        box.append($('<div ng-if="page.isEnabled == true" class="status enable">Enabled <div class="background"></div></div>'));
        //box.append( $('<div class="status enable">Enabled<div class="background"></div></div>'))
        $compile(box)($scope);
        viewsArea.append(box);
        action_button = $("<div class='action-buttons' style='width:30px;'></div>");
        action_button.append($('<div class="icon add_icon" title="New" ng-click="createView(\'page\')"></div>'));
        viewsArea.append($compile(action_button)($scope));
        //viewsArea.append($compile('<div class="add_button icon" title="New" ng-click="createView(\'page\')"></div>')($scope));
        //error section
        viewsArea.append($("<div class='error_box'><div id='empty_pages' class='error_msg'></div></div>"));

        //templates section
        var TemplateArea = createSection($($element), "View Templates");
        TemplateArea.attr("id", "templates");
        box = $('<div class="card"  ng-repeat="template in templates" ></div>');
        box.append($('<div class="color_box"><div class="box" ></div> <div  class="frame frame-page" style="background-image: url(/gamecourse/screenshoots/template/{{template.id}}.png?' + time + ');"><span class="edit_icon" title="Edit" ng-click="editView(template.id,template.roleType,template.name)"></span></div></div>'));
        box.append($('<div class="footer"><div class="page_name">{{template.name}}</div><div class="template_actions">' +
            //for the configure/edit info of the template
            '<span class="config_icon icon" title="Edit" value="#edit-view" onclick="openModal(this)" ng-click="configureView(template, \'template\')"></span>' +
            '<span class="globalize_icon icon" ng-if="template.isGlobal==false" title="Globalize" ng-click="globalize(template)"></span>' +
            '<span class="de_globalize_icon icon" ng-if="template.isGlobal==true" title="Deglobalize" ng-click="globalize(template)"></span>' +
            '<span class="export_icon_no_outline icon" title="Export" ng-click="exportTemplate(template)">' +
            '</span><span class="delete_icon icon" title="Remove" ng-click="deleteView(template,\'template\')"></span></div></div>'))
        //box.append( $('<div class="status enable">Enabled<div class="background"></div></div>'))
        $compile(box)($scope);
        TemplateArea.append(box);
        action_button_template = $("<div class='action-buttons' style='width:30px;'></div>");
        action_button_template.append($('<div class="icon add_icon" title="New" ng-click="createView(\'template\')"></div>'));
        TemplateArea.append($compile(action_button_template)($scope));
        //error section
        TemplateArea.append($("<div class='error_box'><div id='empty_templates' class='error_msg'></div></div>"));

        // global templates section
        var globalTemplateArea = createSection($($element), "Global Templates");
        globalTemplateArea.attr("id", "templates");
        box = $('<div class="card"  ng-repeat="template in globals"></div>');
        box.append($('<div class="color_box"><div class="box" ></div> <div  class="frame frame-page" style="background-image: url(/gamecourse/screenshoots/page/{{id}}.png?' + time + ');"><span class="add_icon_no_outline" ng-if="template.course!=course" ng-click="useGlobal(template)"></span></div></div>'));
        box.append($('<div class="footer"><div class="page_name">{{template.name}}</div></div>'))
        //box.append( $('<div class="status enable">Enabled<div class="background"></div></div>'))
        $compile(box)($scope);
        globalTemplateArea.append(box);
        //error section
        globalTemplateArea.append($("<div class='error_box'><div id='empty_globals' class='error_msg'></div></div>"));



        //new view modal
        modal = $("<div class='modal' style='' id='new-view'></div>");
        newView = $("<div class='modal_content'></div>");
        newView.append($('<button class="close_btn icon" value="#new-view" onclick="closeModal(this);resetSelectTextColor(\'new_view_role\');resetSelectTextColor(\'new_view_template\');"></button>'));
        newView.append($('<div class="title">New {{newView.pageOrTemp}}: </div>'));
        content = $('<div class="content">');
        box = $('<div class="inputs" id="inputs_view_box">');
        box.append($('<div class="name full"><input type="text" class="form__input" id="name" placeholder="Name *" ng-model="newView.name"/> <label for="name" class="form__label">Name</label></div>'))
        roleType = ($('<select class="form__input pages_info" id="new_view_role" ng-options="type.id as type.name for type in types" ng-model="newView.roleType" onchange="changeSelectTextColor(this);"></select>'));
        roleType.append($('<option value="" disabled selected>Select a role type *</option>'));
        box.append(roleType);
        // which view this page will show
        viewTemplate = ($('<select class="form__input pages_info" id="new_view_template" ng-options="template.viewId as template.name for template in templates" ng-model="newView.viewId" onchange="changeSelectTextColor(this);"></select>'));
        viewTemplate.append($('<option value="" disabled selected>Select a view template *</option>'));
        box.append(viewTemplate);
        // for the enable/disable feature of pages
        row = $('<div id="active_page" class= "row"></div>');
        row.append($('<div class= "on_off"><span>Enable Page</span><label class="switch"><input id="active" type="checkbox" ng-model="newView.isEnabled"><span class="slider round"></span></label></div>'))
        //box.append(row);
        content.append(box);
        // added row to content intead of box to align with the save button
        content.append(row);
        content.append($('<button class="cancel" value="#new-view" onclick="closeModal(this);resetSelectTextColor(\'new_view_role\');resetSelectTextColor(\'new_view_template\');" > Cancel </button>'))
        content.append($('<button class="save_btn" ng-click="saveView()" ng-disabled="!isReadyToSubmit(newView.pageOrTemp)" > Save </button>'))
        newView.append(content);
        modal.append(newView);
        $compile(modal)($scope);
        $element.append(modal);


        // delete verification modal
        delete_modal = $("<div class='modal' id='delete-view'></div>");
        verification = $("<div class='verification modal_content'></div>");
        verification.append($('<button class="close_btn icon" value="#delete-view" onclick="closeModal(this)"></button>'));
        verification.append($('<div class="warning">Are you sure you want to delete this View?</div>'));
        verification.append($('<div id="for_template_warning" class="warning">Any template references that use it will also be deleted</div>'));
        verification.append($('<div class="target">{{view.pageOrTemp}}: {{view.name}} (id: {{view.id}})</div>'));
        verification.append($('<div class="confirmation_btns"><button class="cancel" value="#delete-view" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="submitDelete()"> Delete</button></div>'))
        delete_modal.append(verification);
        $compile(delete_modal)($scope);
        $element.append(delete_modal);

        // alert modal
        delete_modal = $("<div class='modal' id='alert-view'></div>");
        verification = $("<div class='verification modal_content'></div>");
        verification.append($('<button class="close_btn icon" value="#alert-view" onclick="closeModal(this)"></button>'));
        verification.append($('<div class="warning">File created. To use it move to a module folder and edit the module file.</div>'));
        verification.append($('<div class="target">{{response}}</div>'));
        verification.append($('<div class="confirmation_btns"><button class="cancel" value="#alert-view" onclick="closeModal(this)">Continue</button></div>'))

        delete_modal.append(verification);
        $compile(delete_modal)($scope);
        $element.append(delete_modal);

        //edit page modal
        editmodal = $("<div class='modal' id='edit-view'></div>");
        editpage = $("<div class='modal_content'></div>");
        editpage.append($('<button class="close_btn icon" value="#edit-view" onclick="closeModal(this)"></button>'));
        editpage.append($('<div class="title">Edit {{editView.pageOrTemp}}: </div>'));
        editcontent = $('<div class="content">');
        editbox = $('<div id="edit_view_box" class= "inputs">');
        //text inputs

        editbox.append($('<div class="container" ><input type="text" class="form__input" id="edit_name" placeholder="Name *" ng-model="editView.name"/> <label for="name" class="form__label">Name</label></div>'))


        //editrow = $('<div id="#active_visible_inputs" class= "row"></div>');
        //editrow.append( $('<div class= "on_off"><span>Enable Page</span><label class="switch"><input id="active" type="checkbox" ng-model="editView.viewIsEnabled"><span class="slider round"></span></label></div>'))
        // authentication information - service and username
        editcontent.append(editbox);
        //editcontent.append(editrow);
        editcontent.append($('<button class="cancel" value="#edit-view" onclick="closeModal(this)" > Cancel </button>'))
        editcontent.append($('<button class="save_btn" ng-click="submitEditView()" ng-disabled="!isReadyToEdit(editView.pageOrTemp)" > Save </button>'))
        editpage.append(editcontent);
        editmodal.append(editpage);

        $compile(editmodal)($scope);
        $element.append(editmodal);


        angular.extend($scope, data);

        $scope.createView = function (pageOrTemp) {
            $scope.newView = { name: '', roleType: '', pageOrTemp: pageOrTemp, course: $scope.course, isEnabled: 0, viewId: '' };

            $scope.saveView = function () {
                $smartboards.request('views', 'createView', $scope.newView, alertUpdate);
            };
            //criar funcao de verificacao
            $scope.isReadyToSubmit = function (pageOrTemp) {
                isValid = function (text) {
                    return (text != "" && text != undefined && text != null)
                }

                if (pageOrTemp == "page") {
                    if (isValid($scope.newView.name) &&
                        //isValid($scope.newView.roleType) &&
                        isValid($scope.newView.viewId)) {
                        return true;
                    }
                    else {
                        return false;
                    }
                } else {
                    if (isValid($scope.newView.name) &&
                        isValid($scope.newView.roleType)) {
                        return true;
                    } else {
                        return false;
                    }
                }
                //validate inputs

            }

            if (pageOrTemp == "page") {
                $("#active_page").show();
                $("#new_view_template").show();
                $("#new_view_role").hide();
                $("#inputs_view_box").attr("style", "padding-bottom: 26px");
            }
            else {
                $("#active_page").hide();
                $("#new_view_template").hide();
                $("#new_view_role").show();
                $("#inputs_view_box").attr("style", "padding-bottom: 26px");
            }

            $("#new-view").show();
        };
        $scope.editView = function (id, roleType, name) {
            console.log(roleType);
            if (roleType == "ROLE_SINGLE")
                $state.go("course.settings.views.edit-role-single", { pageOrTemp: "template", view: id, name: removeSpacefromName(name) });
            else
                $state.go("course.settings.views.edit-role-interaction", { pageOrTemp: "template", view: id, name: removeSpacefromName(name) });
        };
        $scope.globalize = function (template) {
            $smartboards.request('views', 'globalizeTemplate', { course: $scope.course, id: template.id, isGlobal: template.isGlobal }, alertUpdate);
        };
        $scope.useGlobal = function (template) {
            console.log(template);
            const targetRole = $("#viewer_role").find(":selected")[0].text;
            const roles = $sbviews.buildRolesHierarchyForOneRole(targetRole).map((e) => e.name);
            $smartboards.request('views', 'copyGlobalTemplate', { course: $scope.course, template: template, roles: roles }, alertUpdate);
        };
        $scope.exportTemplate = function (template) {
            $smartboards.request('views', 'exportTemplate', { course: $scope.course, id: template.id, name: template.name }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $scope.response = data.filename;
                $("#alert-view").show();
            });
        };
        $scope.deleteView = function (view, templateOrPage) {
            $scope.view = { name: view.name, pageOrTemp: templateOrPage, course: $scope.course, id: view.id }
            $("#delete-view").show();

            if (templateOrPage == "template") {
                $("#for_template_warning").show();
            } else {
                $("#for_template_warning").hide();
            }
            $scope.submitDelete = function () {
                $smartboards.request('views', 'deleteView', $scope.view, alertUpdate);
            }

        };
        $scope.reduceList = function () {
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
            if (validateSearch(text)) {
                //match por name e short
                jQuery.each($scope.pages, function (index) {
                    view_obj = $scope.pages[index];
                    if (view_obj.name.toLowerCase().includes(text.toLowerCase())) {
                        filteredPages.push(view_obj);
                    }
                });
                jQuery.each($scope.templates, function (index) {
                    view_obj = $scope.templates[index];
                    if (view_obj.name.toLowerCase().includes(text.toLowerCase())) {
                        filteredTemplates.push(view_obj);
                    }
                });
                jQuery.each($scope.globals, function (index) {
                    view_obj = $scope.globals[index];
                    if (view_obj.name.toLowerCase().includes(text.toLowerCase())) {
                        filteredGlobals.push(view_obj);
                    }
                });

                if (filteredPages.length == 0) {
                    $("#empty_pages").append("No matches found");
                }
                if (filteredTemplates.length == 0) {
                    $("#empty_templates").append("No matches found");
                }
                if (filteredGlobals.length == 0) {
                    $("#empty_globals").append("No matches found");
                }
                $scope.pages = filteredPages;
                $scope.templates = filteredTemplates;
                $scope.globals = filteredGlobals;
            }

        }
        $scope.configureView = function (view, pageOrTemp) {
            $("#view_template_input").remove();
            $("#active_visible_inputs").remove();
            $scope.editView = {};
            $scope.editView.id = view.id;
            $scope.editView.course = view.course;
            $scope.editView.roleType = view.roleType;
            $scope.editView.name = view.name;
            $scope.editView.theme = null;
            $scope.editView.pageOrTemp = pageOrTemp;
            $scope.editView.viewId = view.viewId;

            if (pageOrTemp == "page") {
                $("#active_visible_inputs").show();
                //$("#inputs_view_box").attr("style", "padding-bottom: 26px");


                // view that this page will show
                editbox = $("#edit_view_box");

                editviewTemplate = ($('<select class="form__input" id="view_template_input" ng-options="template.viewId as template.name for template in templates" ng-model="editView.viewId"></select>'));
                editviewTemplate.append($('<option value="" disabled selected>Select a view template *</option>'));
                editbox.append(editviewTemplate);

                editrow = $('<div class= "row" id="active_visible_inputs"></div>');
                if (view.isEnabled == true) {
                    editrow.append($('<div class= "on_off"><span>Enable Page </span><label class="switch"><input id="active" type="checkbox" ng-model="editView.pageIsEnabled" checked><span class="slider round"></span></label></div>'));
                    $scope.editView.pageIsEnabled = true;
                }
                else {
                    editrow.append($('<div class= "on_off"><span>Enable Page </span><label class="switch"><input id="active" type="checkbox" ng-model="editView.pageIsEnabled"><span class="slider round"></span></label></div>'));
                    $scope.editView.pageIsEnabled = false;
                }
                editbox.append(editrow);

            } else {
                editbox = $("#edit_view_box");
                editbox.attr("style", "padding-bottom: 26px");

                editroleType = ($('<select class="form__input" ng-options="type.id as type.name for type in types" ng-model="editView.roleType"></select>'));
                editroleType.append($('<option value="" disabled selected>Select a role type *</option>'));
                editbox.append(editroleType);
            }

            $compile(editbox)($scope);
            $("#edit-view").show();

            $scope.isReadyToEdit = function (pageOrTemp) {
                isValid = function (text) {
                    return (text != "" && text != undefined && text != null)
                }
                //validate inputs
                if (pageOrTemp == "page") {
                    if (isValid($scope.editView.name)) {
                        return true;
                    }
                    else {
                        return false;
                    }
                } else {
                    if (isValid($scope.editView.name) &&
                        isValid($scope.editView.roleType)) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }

            $scope.submitEditView = function () {
                isEnabled = $scope.editView.pageIsEnabled ? 1 : 0;
                var editData = {
                    course: $scope.editView.course,
                    name: $scope.editView.name,
                    id: $scope.editView.id,
                    isEnabled: isEnabled,
                    roleType: $scope.editView.roleType || '',
                    viewId: $scope.editView.viewId,
                    theme: $scope.editView.theme,
                    pageOrTemp: $scope.editView.pageOrTemp
                };

                $smartboards.request('views', 'editView', editData, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    $("#edit-view").hide();
                    // reload the window to update the nav bar

                    window.location.reload();
                    //$("#action_completed").append($scope.editView.pageOrTemp + ": "+ $scope.editView.name + " edited");
                    //$("#action_completed").show().delay(3000).fadeOut();
                });
            };
        }


    });
});

//controller for pages that are created in the views page
angular.module('module.views').controller('CustomUserPage', function ($state, $stateParams, $element, $scope, $sbviews) {
    changeTitle($stateParams.name, 1);
    $sbviews.request($stateParams.id, { course: $scope.course, user: $stateParams.userID }, function (view, err) {
        if (err) {
            console.log(err);
            return;
        }
        $element.append(view.element);
        addActiveLinks($state.current.name);
    });
});
angular.module('module.views').controller('CustomPage', function ($state, $stateParams, $rootScope, $element, $scope, $sbviews, $compile, $state) {
    changeTitle($stateParams.name, 1);
    $sbviews.request($stateParams.id, { course: $scope.course }, function (view, err) {
        if (err) {
            console.log(err);
            console.log(err.description);
            return;
        }
        $element.append(view.element);
        addActiveLinks($state.current.name);
    });
});
angular.module('module.views').config(function ($stateProvider) {
    $stateProvider.state('course.customUserPage', {
        url: '/{name:[A-z0-9]+}-{id:[0-9]+}/{userID:[0-9]{1,5}}',
        views: {
            'main-view@': {
                controller: 'CustomUserPage'
            }
        }
    }).state('course.customPage', {
        url: '/{name:[A-z0-9]+}-{id:[0-9]+}',
        views: {
            'main-view@': {
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
        url: '/{pageOrTemp:(?:template|page)}/{view:[A-z0-9]+}-{name:[A-z0-9]+}',
        views: {
            'tabContent@course.settings': {
                template: 'abc',
                controller: 'ViewSettings'
            }
        }
    })
        // .state('course.settings.views.view.edit-single', {
        //     url: '/edit',
        //     views: {
        //         'main-view@': {
        //             template: '',
        //             controller: 'ViewEditController'
        //         }
        //     }
        // })
        .state('course.settings.views.edit-role-single', {
            url: '/{pageOrTemp:[A-z]+}/{view:[A-z0-9]+}-{name:[A-z0-9]+}/edit-single',
            views: {
                'main-view@': {
                    template: '',
                    controller: 'ViewEditController'
                }
            }
        }).state('course.settings.views.edit-role-interaction', {
            url: '/{pageOrTemp:[A-z]+}/{view:[A-z0-9]+}-{name:[A-z0-9]+}/edit-interaction',
            views: {
                'main-view@': {
                    template: '',
                    controller: 'ViewEditController'
                }
            }
        });
});