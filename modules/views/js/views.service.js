angular.module('module.views').service('$sbviews', function ($smartboards, $rootScope, $compile, $parse, $timeout) {
    var $sbviews = this;
    this.request = function (view, params, func, pageOrTemp = "page") {
        $smartboards.request('views', 'view', $.extend({ view: view, pageOrTemp: pageOrTemp }, params), function (data, err) {
            if (err) {
                func(undefined, err);
                return;
            }
            var viewScope = $rootScope.$new(true);
            viewScope.view = data.view;
            viewScope.viewBlock = {
                partType: 'block',
                noHeader: true,
                children: viewScope.view.children,
                role: viewScope.view.role
            };

            var viewBlock = $sbviews.build(viewScope, 'viewBlock');
            viewBlock.removeClass('block');
            viewBlock.addClass('view');

            var view = {
                scope: viewScope,
                element: $compile(viewBlock)(viewScope)
            };

            func(view, undefined);
            //$rootScope.loaded=true;
        });
    };

    this.requestEdit = function (view, pageOrTemp, params, func) {
        $smartboards.request('views', 'getEdit', $.extend({ view: view, pageOrTemp: pageOrTemp }, params), function (data, err) {
            if (err) {
                func(undefined, err);
                return;
            }
            console.log("getEdit", data);
            var viewScope = $rootScope.$new(true);
            viewScope.view = data.view;
            viewScope.fields = data.fields;

            var undoStack = [];
            var redoStack = [];
            var changing = false;

            var allFields = [];
            /*function computeExpandedFields(obj, parent, parentExp, isParentContainer) {
                function handle(val, parent) {
                    var fieldNameForExp = (isParentContainer ? '[]' : val.field);
                    var fieldObj = {field: parent + val.field, fieldExp: parentExp + fieldNameForExp, desc: val.desc, example: val.example, leaf: false};
                    allFields.push(fieldObj);
                    switch (val.partType) {
                        case 0:
                            fieldObj.leaf = true;
                            break;
                        case 1:
                            computeExpandedFields(val.value, parent + val.field + '.', parentExp + fieldNameForExp, true);
                            break;
                        case 2:
                            computeExpandedFields(val.fields, parent + val.field + '.', parentExp + fieldNameForExp + '.', false);
                            break;
                        case 3:
                            //computeExpandedFields(val.options.key, parent + val.field + '.');
                            computeExpandedFields(val.options.value, parent + val.field + '.', parentExp + fieldNameForExp, true);
                            break;
                    }
                }

                if (obj.field != undefined && obj.type != undefined ) {
                    handle(obj, parent, parentExp, isParentContainer);
                } else {
                    $.each(obj, function(key, val) {
                        computeExpandedFields(val, parent, parentExp, isParentContainer);
                    });
                }
            }
            //console.log('getEdit-allFields',allFields);
            computeExpandedFields(viewScope.fields, '', '');
*/
            viewScope.viewBlock = {
                partType: 'block',
                noHeader: true,
                children: viewScope.view.children,
                role: viewScope.view.role,
                viewId: viewScope.view.viewId
            };

            $rootScope.partsHierarchy = viewScope.viewBlock;
            $rootScope.role = viewScope.view.role;
            $rootScope.roleType = $rootScope.role.includes(">") ? "ROLE_INTERACTION" : "ROLE_SINGLE";
            $rootScope.courseRoles = data.courseRoles;
            $rootScope.viewRoles = JSON.parse(angular.toJson(data.viewRoles));
            $rootScope.courseId = data.courseId;
            if ($rootScope.roleType == "ROLE_SINGLE") {
                $rootScope.current_viewer_role = viewScope.view.role.split(".")[1];
            } else {
                //TODO - confirmar e adicionar user role
                $rootScope.current_viewer_role = viewScope.view.role.split(">")[0].split(".")[1];
            }


            function build() {
                var element = $sbviews.build(viewScope, 'viewBlock', { edit: true, editData: { fields: allFields, fieldsTree: data.fields, templates: data.templates }, view: viewScope.view });
                element.removeClass('block');
                //element.css('padding-top', 18);
                element.addClass('view editing');
                return element;
            }

            var viewBlock = build();

            viewScope.$watch('viewBlock', function (newValue, oldValue) {
                if ((newValue != oldValue) && !changing) {
                    undoStack.push(oldValue);
                    redoStack.length = 0;
                }
                changing = false;
            }, true);

            var view = {
                scope: viewScope,
                element: viewBlock,
                courseRoles: $rootScope.courseRoles,
                viewRoles: $rootScope.viewRoles,
                get: function () {
                    return viewScope.view;
                },
                undo: function () {
                    if (undoStack.length > 0) {
                        changing = true;
                        redoStack.push(angular.copy(viewScope.viewBlock));
                        viewScope.viewBlock = undoStack.pop();

                        var newView = build();
                        viewBlock.replaceWith(newView);
                        view.element = viewBlock = newView;
                    }
                },
                redo: function () {
                    if (redoStack.length > 0) {
                        changing = true;
                        undoStack.push(angular.copy(viewScope.viewBlock));
                        viewScope.viewBlock = redoStack.pop();

                        var newView = build();
                        viewBlock.replaceWith(newView);
                        view.element = viewBlock = newView;
                    }
                },
                canUndo: function () {
                    return undoStack.length > 0;
                },
                canRedo: function () {
                    return redoStack.length > 0;
                }
            };
            func(view, undefined);
        });
    };

    this.registeredPartType = [];
    this.registerPartType = function (type, options) {
        this.registeredPartType[type] = options;
    };

    this.build = function (scope, what, options) {
        var part = $parse(what)(scope);
        return this.buildElement(scope, part, options);
    };

    this.buildElement = function (parentScope, part, options) {
        var options = $.extend({}, options);
        if (options.edit && part.partType == "image")
            part.edit = true;
        if (options.type != undefined)
            part.partType = options.partType;

        var partScope = parentScope.$new(true);
        partScope.part = part;
        if (part.partType == "templateRef") {//adding background color, border and a warning message if its a template reference
            //ToDo: improve the look of the template references
            tempRefOptions = angular.copy(options);
            tempRefOptions.toolOptions.canSwitch = false;
            tempRefOptions.toolOptions.noSettings = true;
            tempRefOptions.toolOptions.canDuplicate = false;
            tempRefOptions.toolOptions.canSaveTemplate = false;
            tempRefOptions.toolOptions.canHaveAspects = false;
            var element = this.registeredPartType["block"].build(partScope, part, tempRefOptions);
            element.prepend($('<span style="color: red; display: table; margin: 5px auto 15px;">Warning: Any changes made to this block will affect the original template</span>'));
            element.attr("style", "padding: 10px; background-color: #ddedeb; ");//#881111
        }
        else {
            if (this.registeredPartType[part.partType] == undefined) {
                console.error('Unknown part type: ' + part.partType);
                return;
            }
            var element = this.registeredPartType[part.partType].build(partScope, part, options);
        }

        this.applyCommonFeatures(partScope, part, element, options);

        element.data('scope', partScope);
        return element;
    };

    this.buildStandalone = function (part, options) {
        return this.buildElement($rootScope, part, options);
    };

    this.destroy = function (element) {
        var scope = element.data('scope');
        if (scope == null || scope.part == null)
            return;
        if (scope.part.partType == "templateRef")
            scope.part.partType = "block";
        this.registeredPartType[scope.part.partType].destroy(element, scope.part);
        scope.$destroy();
        element.remove();
    };

    this.applyCommonFeatures = function (scope, part, element, options) {
        if (!options.edit && part.events && !options.disableEvents) {
            var keys = Object.keys(part.events);
            for (var i = 0; i < keys.length; ++i) {
                var key = keys[i];

                part.events[key] = part.events[key].replace(/\\n/g, '');

                var fn = $parse(part.events[key]);
                (function (key, fn) {
                    element.on(key, function (e) {
                        scope.event = key;
                        if (e.stopPropagation)
                            e.stopPropagation();
                        fn(scope);
                    });
                })(key, fn);
            }
        }

        if (options.edit)
            return;
        if (part.class !== undefined)
            element.addClass(part.class);
        if (part.style !== undefined)
            element.attr('style', part.style);
        if (part.label !== undefined)
            element.attr('label-for-events', part.label);

        //add events attribute so they can acess functions of events directive
        element.attr("events", '');
        $compile(element)(scope);
        element.removeAttr("events");
    };

    this.openOverlay = function (callbackFunc, closeFunc) {

        var overlay = $("<div class='modal'></div>");
        var overlayCenter = $("<div class='modal_content'></div>");
        overlay.append(overlayCenter);

        var scroll = $(window).scrollTop();

        var destroyListener;
        function execClose(force) {
            var destroy = true;

            function cancel() {
                destroy = false;
            }

            if (closeFunc != undefined)
                closeFunc(force ? undefined : cancel);

            if (destroy) {
                destroyListener();
                overlay.remove();
                $('#wrapper').show();
                $(window).scrollTop(scroll);
            }
        }

        destroyListener = $rootScope.$on('$stateChangeStart', function () {
            execClose(true);
        });

        overlay.mousedown(function (event) {
            if (event.target == this)
                execClose();
        });

        //$('#wrapper').hide();
        if (callbackFunc != undefined)
            callbackFunc(overlayCenter, execClose);
        $(document.body).append(overlay);
    };

    this.createTool = function (title, img, click) {
        div = $("<div class='tool'></div>").addClass('btn').attr('title', title).on('click', function (e) {
            e.stopPropagation();
            var thisArg = this;
            var args = arguments;
            $timeout(function () { click.apply(thisArg, args); });
        });
        div.append($(document.createElement('img')).attr('src', img));
        return div;
    };

    this.createToolbar = function (scope, part, toolbarOptions, isTableTool) {
        var toolbar = $(document.createElement('div')).addClass('edit-toolbar');
        if (isTableTool) {
            toolbar.addClass('table-toolbar');
        }

        if (!toolbarOptions.tools.noSettings) {
            toolbar.append($sbviews.createTool('Edit Part Settings', 'images/edit_icon.svg', function () {
                var optionsScope = scope.$new();
                optionsScope.editData = toolbarOptions.editData;
                optionsScope.part = angular.copy(part);
                optionsScope.toggleProperty = function (part, path, defaultValue) {
                    if (defaultValue == undefined)
                        defaultValue = '';

                    var obj = part;
                    var key = path;

                    var pathKeys = path.split('.');
                    if (pathKeys.length > 1) {
                        key = pathKeys.pop();
                        obj = pathKeys.reduce(function (obj, i) { return obj[i]; }, part);
                    }

                    if (obj[key] == undefined)
                        obj[key] = defaultValue;
                    else
                        delete obj[key];
                };

                optionsScope.delete = function (obj, k) {
                    console.log(k);
                    delete obj[k];
                };

                optionsScope.toggleVisCondition = function () {
                    var sbExp = $(($(document.getElementById('visCondition'))).children().get(1));

                    if (optionsScope.part.visibilityType === "visible" || optionsScope.part.visibilityType === "invisible") {
                        //disable condition input if visibility is visible or invisible
                        sbExp.prop('disabled', true);
                        $("#visCondition").hide();
                    }
                    else {//enable condition input if visibility is by condition
                        sbExp.prop('disabled', false);
                        $("#visCondition").show();
                    }
                };

                var options = toolbarOptions.overlayOptions;
                optionsScope.options = options;

                $timeout(function () { // this is needed because the scope was created in the same digest..
                    function watch(path, fn) {
                        optionsScope.$watch(path, function (n, o) {
                            if (n != o) {
                                /*var notifiedPart = part;
                                if (toolbarOptions.notifiedPart != null)
                                    notifiedPart = toolbarOptions.notifiedPart;
                                $sbviews.notifyChanged(notifiedPart, {view: toolbarOptions.view});
                                if (notifiedPart == part) {
                                    optionsScope.part.pid = part.pid;
                                    optionsScope.part.origin = part.origin;
                                }*/
                                if (fn != undefined)
                                    fn(n, o);
                            }
                        }, true);
                    }
                    $sbviews.openOverlay(function (modal, execClose) {
                        optionsScope.closeOverlay = function () {
                            execClose();
                        };
                        modal.parent().attr("id", "edit_part");
                        var scopeToConfig = optionsScope;
                        $smartboards.request('views', 'getDictionary', { course: $rootScope.course }, function (data, err) {
                            scopeToConfig.dictionary = data;
                        });
                        var container = $('<div ng-include="\'' + $rootScope.modulesDir + '/views/partials/settings-overlay.html\'">');
                        $compile(container)(optionsScope);
                        modal.append(container);
                        modal.on('mouseenter', function () {
                            //this ensures that when visibility is not conditional, the field will be disabled
                            optionsScope.toggleVisCondition();
                        });
                        // Events
                        var events = ['click', 'dblclick', 'mouseover', 'mouseout', 'mouseup', 'wheel', 'drag'];
                        //ToDo: drop,keydown,keypress,keyup (these weren't working)
                        var missingEvents = [];
                        if (optionsScope.part.events !== undefined) {
                            for (var i in events) {
                                var event = events[i];
                                if (optionsScope.part.events[event] === undefined)
                                    missingEvents.push(event);
                            }
                        } else {
                            missingEvents = events;
                            optionsScope.part.events = {};
                        }
                        optionsScope.missingEvents = missingEvents;
                        optionsScope.events = {
                            eventToAdd: undefined
                        };
                        optionsScope.addEvent = function () {
                            var eventType = optionsScope.events.eventToAdd;
                            optionsScope.missingEvents.splice(optionsScope.missingEvents.indexOf(eventType), 1);
                            optionsScope.part.events[eventType] = '';
                        };

                        optionsScope.addEventToMissing = function (type) {
                            optionsScope.missingEvents.push(type);
                        };


                        // Variables
                        optionsScope.variables = {
                            dataKey: undefined
                        };

                        optionsScope.addVariable = function () {
                            optionsScope.part.variables[optionsScope.variables.dataKey] = { value: '' };
                            optionsScope.variables.dataKey = '';
                        };
                        $timeout(function () {
                            if (options.callbackFunc != undefined)
                                options.callbackFunc(container.next(), execClose, optionsScope, watch);
                        }, 50);

                    }, function (cancel) {
                        console.log("close settings", optionsScope.part);
                        if (JSON.stringify(optionsScope.part) !== JSON.stringify(part)) {
                            $timeout(function () {
                                objSync(part, optionsScope.part);

                                optionsScope.$destroy();
                                if (options.closeFunc != undefined)
                                    options.closeFunc();
                            });
                        } else {
                            optionsScope.$destroy();
                            if (options.closeFunc != undefined)
                                options.closeFunc();
                        }
                    });
                });
            }));
        }

        if (toolbarOptions.layoutEditor) {
            var tool = $sbviews.createTool('Edit Layout', 'images/layout_editor_icon.svg', function () {
                if (toolbarOptions.toolFunctions.layoutEdit)
                    toolbarOptions.toolFunctions.layoutEdit(tool);
            });
            toolbar.append(tool);
        }

        if (toolbarOptions.tools.canDelete) {
            toolbar.append($sbviews.createTool('Remove', 'images/delete_icon.svg', function () {

                toolbarOptions.toolFunctions.remove(part);
            }));
        }

        if (toolbarOptions.tools.canSwitch) {
            toolbar.append($sbviews.createTool('Switch part', 'images/switch_part_icon.svg', function () {
                var optionsScope = scope.$new();
                $sbviews.openOverlay(function (modal, execClose) {
                    optionsScope.closeOverlay = function () {
                        execClose();
                    };
                    modal.parent().attr("id", "switch_part");
                    modal.append($('<button class="close_btn icon" value="#switch_part" ng-click="closeOverlay()"</button>'));
                    modal.append($('<div class="title">Switch Part Into: </div>'));
                    modalContent = $("<div class='content'></div>");
                    parts_selection = $('<div id="parts_selection"></div>');
                    template_selection = $('<div id="template_selection"></div>');
                    modalContent.append(parts_selection);
                    modalContent.append(template_selection);
                    modal.append(modalContent);

                    $compile(modal)(optionsScope);

                    var addPartsDiv = $(document.createElement('div')).addClass('add-parts');

                    var partsList = $(document.createElement('select')).attr('id', 'partList');
                    partsList.append($('<option value="" disabled selected >Part Type</option>'))
                    for (var type in $sbviews.registeredPartType) {
                        var partDef = $sbviews.registeredPartType[type];
                        if (partDef.name != undefined && partDef.defaultPart != undefined) {
                            var option = $(document.createElement('option'));
                            option.text(partDef.name);
                            option.val('part:' + type);
                            partsList.append(option);

                            partIconName = "images/" + type + "_part_icon.svg";
                            part_option = $('<div value="' + type + '" class="part_option"></div>')
                            part_option.append($(document.createElement('img')).attr('src', partIconName));
                            part_option.append($('<div class="part_label">' + type + '</div>'));
                            parts_selection.append(part_option);
                            part_option.click(function () {
                                $(".part_option").removeClass("focus");
                                $(this).addClass("focus");
                                type = this.getAttribute('value');
                                $("#partList").val("part:" + type);
                                template_selection.hide();
                                turnButton.prop('disabled', false);
                            });
                            if (type == part.partType) {
                                part_option.append($('<div class="current_label">(current)</div>'));
                                part_option.addClass("current");
                            }
                        }
                    }
                    //create only one option for templates on invisible select
                    var temp_option = $(document.createElement('option'));
                    temp_option.text('Template');
                    temp_option.val('temp:');
                    partsList.append(temp_option);
                    // add template icon option
                    part_option = $('<div value="' + type + '" class="part_option"></div>')
                    part_option.append($(document.createElement('img')).attr('src', "images/template_part_icon.svg"));
                    part_option.append($('<div class="part_label"> template</div>'));
                    parts_selection.append(part_option);
                    part_option.click(function () {
                        $(".part_option").removeClass("focus");
                        $(this).addClass("focus");
                        type = this.getAttribute('value');
                        $("#partList").val("temp:");
                        template_selection.show();
                        turnButton.prop('disabled', false);
                    });
                    addPartsDiv.append(partsList);
                    partsList.hide();


                    var templateList = $(document.createElement('select')).attr('id', 'templateList').addClass("form__input");
                    templateList.append('<option disabled>Select a template</option>');
                    var templates = toolbarOptions.editData.templates;
                    for (var t in templates) {
                        var template = templates[t];
                        var option = $(document.createElement('option'));
                        option.text(template["name"] + " (" + template['id'] + ")");
                        option.val('temp:' + t);
                        templateList.append(option);
                    }
                    template_selection.append(templateList);
                    template_selection.hide();
                    addPartsDiv.append(template_selection);

                    var turnButton = $(document.createElement('button')).text('Switch Part');
                    turnButton.prop('disabled', true);
                    turnButton.addClass("save_btn");
                    turnButton.click(function () {
                        var value = partsList.val();
                        var id = value.substr(5);
                        var newPart;
                        if (value.indexOf('part:') == 0) {
                            newPart = $sbviews.registeredPartType[id].defaultPart();
                            toolbarOptions.toolFunctions.switch(part, newPart);
                            execClose();
                        }
                        else if (value.indexOf('temp:') === 0) {
                            var value = templateList.val();
                            var id = value.substr(5);
                            templates[id].role = $rootScope.role;
                            $smartboards.request('views', 'getTemplateContent', templates[id], function (data, err) {
                                if (err) {
                                    giveMessage(err.description);
                                    return;
                                }
                                newPart = data.template;
                                toolbarOptions.toolFunctions.switch(part, newPart);
                                execClose();
                            });
                        }
                    });

                    modalContent.append(addPartsDiv);
                    modalContent.append(turnButton);
                }, function () {
                    optionsScope.$destroy();
                });
            }));
        }

        if (toolbarOptions.tools.canDuplicate) {
            toolbar.append($sbviews.createTool('Duplicate', 'images/duplicate_icon.svg', function () {
                toolbarOptions.toolFunctions.duplicate(part);
            }));
        }

        if (toolbarOptions.tools.canSaveTemplate) {
            toolbar.append($sbviews.createTool('Save template', 'images/save_icon.svg', function () {
                var optionsScope = scope.$new();
                optionsScope.editData = toolbarOptions.editData;
                optionsScope.part = part;
                $timeout(function () {
                    $sbviews.openOverlay(function (modal, execClose) {
                        optionsScope.closeOverlay = function () {
                            execClose();
                        };

                        var templatePart = angular.copy(part);
                        //$sbviews.changePids(templatePart);
                        optionsScope.template = {
                            name: '',
                            part: templatePart,
                            course: $rootScope.course
                        };

                        optionsScope.saveTemplate = function () {
                            optionsScope.template.part.role = $rootScope.role;
                            var isbyRef = $('#isRef').is(':checked');
                            if (isbyRef) {
                                var templateIndex = optionsScope.editData.templates.length;
                                optionsScope.template.roleType = "ROLE_SINGLE";
                                optionsScope.template.role = "Role.Default";
                                $smartboards.request('views', 'saveTemplate', optionsScope.template, function (data, err) {
                                    if (err) {
                                        giveMessage(err.description);
                                        return;
                                    }
                                    execClose();
                                    giveMessage('Template saved as reference!');
                                    optionsScope.template.id = data.templateId;
                                    optionsScope.editData.templates[templateIndex] = optionsScope.template;
                                    $smartboards.request('views', 'getTemplateReference', optionsScope.editData.templates[templateIndex], function (data, err) {
                                        if (err) {
                                            giveMessage(err.description);
                                            return;
                                        }
                                        newPart = data.template;
                                        delete data.template.id;
                                        console.log(newPart);
                                        toolbarOptions.toolFunctions.switch(part, newPart);
                                        execClose();
                                    });
                                });
                            }
                            else {
                                $smartboards.request('views', 'saveTemplate', optionsScope.template, function (data, err) {
                                    if (err) {
                                        giveMessage(err.description);
                                        return;
                                    }
                                    execClose();
                                    giveMessage('Template saved!');
                                    optionsScope.editData.templates[optionsScope.template.name] = templatePart;
                                });
                            }

                        };

                        modal.parent().attr("id", "save_as_template");
                        modal.append($('<button class="close_btn icon" value="#save_as_template" ng-click="closeOverlay()"</button>'));
                        modal.append($('<div class="title">Save Part as Template: </div>'));
                        modalContent = $("<div class='content'></div>");
                        modalContent.append($('<div class="full"><input type="text" class="form__input" id="templateName" placeholder="Template Name *" ng-model="template.name"/> <label for="templateName" class="form__label">Template Name</label></div>'))
                        modalContent.append($('<div class= "on_off"><span>Save as reference </span><label class="switch"><input id="isRef" type="checkbox"><span class="slider round"></span></label></div>'))
                        modalContent.append($('<button class="save_btn" ng-click="saveTemplate()">Save</button>'));
                        modal.append(modalContent);
                        $compile(modal)(optionsScope)
                    }, function () {
                    });
                });
            }));
        }

        if (toolbarOptions.tools.canHaveAspects) {
            toolbar.append($sbviews.createTool('Manage Aspects', 'images/aspects_icon.svg', function () {
                var optionsScope = scope.$new();
                optionsScope.part = angular.copy(part);

                getAvailableRoles = function () {
                    return $rootScope.courseRoles.filter(elem => !$rootScope.viewRoles.some(role => role.name == elem.name));
                }

                var rolesWithoutAspect = getAvailableRoles();

                optionsScope.isReadyToSubmit = function () {
                    isValid = function (text) {
                        return (text != "" && text != undefined && text != null)
                    }

                    if (isValid($("#viewer").val()) && isValid($("#aspect_selection").val())) {
                        if ($rootScope.roleType == "ROLE_SINGLE")
                            return true;
                        //is ROLE_INTERACTION
                        else if (isValid(("#user").val()))
                            return true;
                        return false;
                    } else
                        return false;
                }

                function deleteIds(newPart) {
                    delete newPart.id;
                    for (var c in newPart.children) {
                        deleteIds(newPart.children[c]);
                    }
                    for (var r in newPart.rows) {
                        deleteIds(newPart.rows[r]);
                    }
                    for (var r in newPart.headerRows) {
                        deleteIds(newPart.headerRows[r]);
                    }
                    for (var c in newPart.values) {
                        deleteIds(newPart.values[c].value);
                    }
                }

                function addPart() {
                    var new_role_viewer = $("#viewer").find(":selected")[0];
                    var new_aspect = $rootScope.create_aspect;

                    // add this role to toolbar 
                    $rootScope.current_viewer_role = new_role_viewer.text;
                    document.getElementById("editing_role").innerHTML = "View Aspect: " + $rootScope.current_viewer_role;


                    if (new_aspect == "new_aspect") {
                        if ($rootScope.roleType == "ROLE_SINGLE") {
                            var el = $('.highlight');
                            var parentContent = $(el[0].parentElement);
                            var newAspect = [];

                            newAspect = $sbviews.defaultPart([part.partType]);
                            newAspect.viewId = part.viewId;
                            newAspect.role = new_aspect;
                            var partParent = $sbviews.findParent(part.parent, $rootScope.partsHierarchy);
                            partParent.children.push(newAspect);

                            var childOptions = $sbviews.editOptions(toolbarOptions, {
                                edit: true, toolOptions: {
                                    canDelete: true,
                                    canSwitch: true,
                                    canDuplicate: true,
                                    canSaveTemplate: true,
                                    canHaveAspects: true,
                                },
                                toolFunctions: {
                                    remove: function (obj) {
                                        var idx = partParent.children.indexOf(obj);
                                        partParent.children.splice(idx, 1);
                                        $sbviews.destroy($(parentContent.children().get(idx)));
                                        if (parentContent.children().length == 0)
                                            parentContent.append($(document.createElement('div')).text('(No Children)').addClass('red no-children'));
                                        //$sbviews.notifyChanged(part, options);
                                    },
                                    duplicate: function (obj) {
                                        var idx = partParent.children.indexOf(obj);
                                        var newPart = $.extend(true, {}, obj);
                                        //$sbviews.changePids(newPart);
                                        deleteIds(newPart);
                                        delete newPart.viewIndex;
                                        partParent.children.splice(idx, 0, newPart);
                                        var newPartEl = $sbviews.build(scope, 'part.children[' + idx + ']', childOptions);
                                        $(parentContent.children().get(idx)).before(newPartEl);
                                        //$sbviews.notifyChanged(part, options);
                                    },
                                    switch: function (obj, newPart) {
                                        var idx = partParent.children.indexOf(obj);
                                        partParent.children.splice(idx, 1, newPart);
                                        var newPartEl = $sbviews.build(scope, 'part.children[' + idx + ']', childOptions);
                                        var oldEl = $(parentContent.children().get(idx));
                                        oldEl.replaceWith(newPartEl);
                                        $sbviews.destroy(oldEl);
                                        //$sbviews.notifyChanged(part, options);
                                    }
                                }
                            });

                            var newChild = $sbviews.buildElement(scope, newAspect, childOptions);
                            newChild.addClass("diff_aspect");
                            //remove highlight
                            //el.click();
                            el.addClass("aspect_hide");
                            //$sbviews.notifyChanged(part, options);
                            parentContent.append(newChild);
                            newChild.click();

                        } else if ($rootScope.roleType == "ROLE_INTERACTION") {

                        }
                    }
                    //copy
                    else {
                        if ($rootScope.roleType == "ROLE_SINGLE") {

                        }
                    }
                }



                updateAddAspectSection = function (viewerRoles, userRoles = null) {
                    $("#add_asp").remove();
                    aspect_box = $("#aspects_modal");

                    add_asp = $('<div id="add_asp"></div>');
                    //add_asp.append($('<span style="margin-right: 8px;">New Dependency:</span>'));

                    add_viewer = $('<select id="viewer" class="form__input roles_aspect" name="viewer" onchange="changeSelectTextColor(this);">');
                    add_viewer.append($('<option value="" disabled selected>Select Viewer Role *</option>'));
                    jQuery.each(viewerRoles, function (index, item) {
                        add_viewer.append($('<option value="' + item.id + '">' + item.name + "</option>"));
                    });
                    add_asp.append(add_viewer);

                    if (userRoles != null) {
                        add_user = $('<select id="user" class="form__input roles_aspect" name="user" onchange="changeSelectTextColor(this);">');
                        add_user.append($('<option value="" disabled selected>Select User Role *</option>'));
                        jQuery.each(userRoles, function (index, item) {
                            add_user.append($('<option value="' + item.id + '">' + item.name + "</option>"));
                        });

                        add_asp.append(add_user);
                    }

                    aspect_selection = $('<select class="form__input roles_aspect" name="aspect_selection" id="aspect_selection" onchange="changeSelectTextColor(this);"></select>');
                    aspect_selection.append($('<option value="" disabled selected>How ?</option>'));
                    aspect_selection.append($('<option value="new_aspect">Create from scratch</option>'));
                    aspect_selection.append($('<option value="copy_aspect">Use default as basis</option>'));

                    add_asp.append(aspect_selection);

                    add_asp.append($('<button ng-click="addAspect()">Create</button>'));
                    add_asp.append($('<div class="delete_icon icon" ng-click="removeAddForm()"></div>'));

                    add_asp.insertBefore("#aspect");

                    $compile(aspect_box)(optionsScope);
                }

                optionsScope.removeAddForm = function () {
                    $("#add_asp").remove();
                };

                optionsScope.addAspect = function () {
                    var new_viewer = $("#viewer").find(":selected")[0];
                    $rootScope.create_aspect = $("#aspect_selection").find(":selected")[0].value;

                    if ($rootScope.roleType == "ROLE_SINGLE" && new_viewer.value != "") {
                        //update select elements
                        $rootScope.viewRoles.push({ "id": new_viewer.value, "name": new_viewer.text });
                        //select ???
                        //$("#edit_viewer_select").append($('<option selected value="' + new_viewer.value + '">' + new_viewer.text + '</option>'));
                        document.getElementById("viewer_role").append($('<option value="' + new_viewer.value + '">' + new_viewer.text + '</option>'));

                        rolesWithoutAspect = getAvailableRoles();

                        //add part
                        addPart();

                        //remove form
                        optionsScope.closeOverlay();
                    }
                    //TODO role_interaction
                    else {
                        var new_viewer = $("#user").find(":selected")[0];
                    }

                };

                optionsScope.isAddAspectEnabled = function () {
                    if (rolesWithoutAspect.length == 0)
                        return false;
                    else
                        return true;

                };

                optionsScope.showAspectSection = function () {
                    //viewerRolesWithoutAspect
                    //userRolesWithoutAspect
                    if ($rootScope.roleType == "ROLE_SINGLE")
                        updateAddAspectSection(rolesWithoutAspect);
                    else
                        updateAddAspectSection(rolesWithoutAspect);
                };

                $sbviews.openOverlay(function (modal, execClose) {
                    optionsScope.closeOverlay = function () {
                        execClose();
                    };
                    modal.parent().attr("id", "aspects_modal");
                    modal.append($('<button class="close_btn icon" value="#aspects_modal" ng-click="closeOverlay()"</button>'));
                    modal.append($('<div class="title">Edit aspect for this view: </div>'));
                    modalContent = $("<div class='content'></div>");


                    //TODO : add user for role interaction templates
                    edit_viewer_selection = $('<div id="edit_viewer_selection"></div>');
                    edit_viewer_selection.append($('<div class="select_label">Viewer: </div>'));
                    edit_viewer_selector = $('<select class="form__input roles_aspect" id="edit_viewer_select" class="form__input"></select>');
                    $.each($rootScope.viewRoles, function (i, item) {
                        if ($("#viewer_role").find(":selected")[0].text == item.name || $rootScope.current_viewer_role == item.name) {
                            edit_viewer_selector.append($('<option selected value="' + item.id + '">' + item.name + '</option>'));
                        } else {
                            edit_viewer_selector.append($('<option value="' + item.id + '">' + item.name + '</option>'));
                        }
                    });
                    //edit_viewer_selector.change(isReadyToSubmit);
                    edit_viewer_selection.append(edit_viewer_selector);
                    modalContent.append(edit_viewer_selection);

                    //add aspect button
                    modalContent.append($('<div class="half" id="aspect"><button class="btn" ng-click="showAspectSection()" ng-disabled="!isAddAspectEnabled()"><img class="icon" src="./images/add_icon.svg"/><span>Add Aspect</span></button></div>'));


                    //TODO : add user for role interaction templates
                    new_viewer_selection = $('<div id="new_viewer_selection"></div>');
                    new_viewer_selection.append($('<div class="select_label">Viewer: </div>'));
                    new_viewer_selector = $('<select id="viewer_select" class="form__input"></select>');
                    new_viewer_selector.append($('<option value="" disabled selected>Select a role *</option>'));
                    $.each(rolesWithoutAspect, function (i, item) {
                        new_viewer_selector.append($('<option value="' + item.name + '">' + item.name + '</option>'));
                    });
                    //new_viewer_selector.change(isReadyToSubmit);
                    new_viewer_selection.append(new_viewer_selector);

                    //aspect_selection.change(isReadyToSubmit);

                    saveButton = $(document.createElement('button')).text('Save');
                    //saveButton.prop('disabled', true);
                    saveButton.addClass("save_btn");
                    saveButton.click(function () {


                        // ISTO DEVE SER FEITO APENAS QUANDO GUARDAMOS AS ALTERACOES 

                        // if (new_aspect == "new_aspect") {
                        //     if ($rootScope.roleType == "ROLE_SINGLE") {
                        //         $smartboards.request('views', 'createAspectView', { view: part.id, pageOrTemp: "template", course: $rootScope.courseId, info: { roleOne: "role." + new_role_viewer }, copyOrNew: "new" }, function (data, err) {
                        //             if (err) {
                        //                 giveMessage(err.description);
                        //                 return;
                        //             }
                        //         })
                        //     } else if ($rootScope.roleType == "ROLE_INTERACTION") {

                        //     }
                        // }
                        // //copy
                        // else {
                        //     if ($rootScope.roleType == "ROLE_SINGLE") {
                        //         $smartboards.request('views', 'createAspectView', { view: part.id, pageOrTemp: "template", course: $rootScope.courseId, info: { roleOne: "role." + new_role_viewer }, copyOrNew: "copy" }, function (data, err) {
                        //             if (err) {
                        //                 giveMessage(err.description);
                        //                 return;
                        //             }
                        //         })
                        //     }
                        // }
                    });
                    modalContent.append(saveButton);
                    modal.append(modalContent);
                    $compile(modal)(optionsScope);


                }, function () {
                    optionsScope.$destroy();
                });
            }));
            //TODO
            toolbar.append('<span id="editing_role">View Aspect: ' + $rootScope.current_viewer_role + '</span>');
        }

        var nTools = toolbar.children().length;
        toolbar.css('min-width', nTools * 15 + 1);

        return toolbar;
    };

    this.findParent = function (parentId, view) {
        if (view.viewId == parentId)
            return view;
        if (view.children.length != 0) {
            for (viewChild in view.children) {
                this.findParent(parentId, viewChild);
            }
        }
    };

    this.checkViews = function (mainPart, current_role, mainContent) {

        if (mainPart.children.length != 0) {
            var i = 0;
            for (viewChild in mainPart.children) {
                viewContent = mainContent.children[i];
                i++;
                this.checkViews(viewChild, current_role, viewContent);
            }
        }
        if (mainPart.role != current_role) { //&& main.role != role imediatamente acima na hierarquia (ver course roles)
            mainContent.addClass("aspect_hide");
        }
    };

    this.editOptions = function (options, extend) {
        return $.extend({
            edit: options.edit,
            editData: options.editData,
            view: options.view
        }, extend);
    };

    this.bindToolbar = function (element, scope, part, partOptions, options) {
        var myToolbar = undefined;
        var toolbarOptions;
        var layoutEditing = false;
        var firstclickdone = false;

        var toolOptions = partOptions.toolOptions != undefined ? partOptions.toolOptions : {};
        var toolFunctions = partOptions.toolFunctions != undefined ? $.extend({}, partOptions.toolFunctions) : {};
        var overlayOptions = partOptions.overlayOptions != undefined ? partOptions.overlayOptions : {};
        var editData = partOptions.editData != undefined ? partOptions.editData : {};

        toolFunctions.layoutEdit = function (tool) {
            layoutEditing = !layoutEditing;
            if (layoutEditing) {
                if (toolbarOptions.toolFunctions.layoutEditStart)
                    toolbarOptions.toolFunctions.layoutEditStart();
                tool.addClass('red');
            } else {
                if (toolbarOptions.toolFunctions.layoutEditEnd)
                    toolbarOptions.toolFunctions.layoutEditEnd();
                tool.removeClass('red');
            }
        };

        console.log(element);

        element.on('click', function (e) {

            //click only works on the closest div.
            e.stopPropagation();

            //second click closes the toolbar
            if (firstclickdone) {
                if (layoutEditing) {
                    return;
                }
                element.removeClass('highlight');
                if (element.hasClass('diff_aspect') && $rootScope.current_viewer_role != $("#viewer_role").find(":selected")[0]) {
                    element.removeClass('diff_aspect');
                    var mainContent = $('.view.editing')[0].parentElement;
                    this.checkViews(part, $("#viewer_role").find(":selected")[0], mainContent);
                }
                if (myToolbar != undefined)
                    myToolbar.remove();
                myToolbar = undefined;
                firstclickdone = false;
            }

            //first click opens the toolbar
            else {
                shouldBuild = true
                //remove highlight and toolbar from preiously selected elements
                previousHighlighted = $(".highlight");
                jQuery.each(previousHighlighted, function (index) {
                    item = previousHighlighted[index];
                    item.click();
                    if ($(item)[0].classList.contains('highlight'))
                        shouldBuild = false;
                });

                if (myToolbar)
                    return;

                if (!shouldBuild)
                    return;

                var defaultOptions = {
                    layoutEditor: false,
                    overlayOptions: {
                        allowClass: true,
                        allowStyle: true,
                        allowDirective: true
                    },
                    tools: {
                        noSettings: false,
                        canSwitch: false,
                        canDelete: false,
                        canDuplicate: false,
                        canSaveTemplate: false,
                        canHaveAspects: false,
                    },
                    view: partOptions.view
                };

                if (element.parent().hasClass("header")) {
                    defaultOptions.overlayOptions.allowEvents = true;
                    defaultOptions.overlayOptions.allowStyle = true;
                    defaultOptions.overlayOptions.allowClass = true;
                }
                else if (element.parent().parent().hasClass('block') || element.parent().parent().hasClass('view')) { // children of ui-block
                    defaultOptions.overlayOptions.allowDataLoop = true;
                    defaultOptions.overlayOptions.allowIf = true;
                    defaultOptions.overlayOptions.allowEvents = true;
                    defaultOptions.overlayOptions.allowVariables = true;
                    defaultOptions.overlayOptions.allowStyle = true;
                    defaultOptions.overlayOptions.allowClass = true;
                }
                if (element.hasClass('view')) {
                    toolOptions.noSettings = true;
                }
                toolbarOptions = $.extend(true, defaultOptions, { tools: toolOptions, toolFunctions: toolFunctions, overlayOptions: overlayOptions }, options);
                toolbarOptions.editData = editData;

                myToolbar = $sbviews.createToolbar(scope, part, toolbarOptions, false);

                // myToolbar.css({
                //     position: 'absolute',
                //     top: 0,
                //     right: 0
                // });
                //if (element.parent().prop("tagName")!="TD")//not highlighting table element because it moves things arround
                element.addClass('highlight');

                element.append(myToolbar);
                firstclickdone = true;
                //console.log("mouseenter",element.data());
            }

        });
    };

    this.defaultPart = function (partType) {
        var part = $sbviews.registeredPartType[partType].defaultPart();
        return part;
    };

    this.createCheckboxAndInput = function (scope, watch, elId, text, path, defaultVal, fn) {
        var root = $('<div>');
        root.append('<label for="' + elId + '">' + text + ':</label><input id="' + elId + '" partType="checkbox" ng-checked="part.' + path + ' != undefined" ng-click="toggleProperty(part, \'' + path + '\', \'' + defaultVal + '\')">');
        root.append('<input ng-if="part.' + path + ' != undefined" ng-model="part.' + path + '"><br>');
        watch('part.' + path, fn);
        return $compile(root)(scope);
    };

    // updates the original to have the same keys has copy
    function objSync(original, copy) {
        var originalKeys = Object.keys(original);
        var copyKeys = Object.keys(copy);
        var newKeys = copyKeys.filter(function (val) {
            return originalKeys.indexOf(val) == -1;
        });
        var commonKeys = copyKeys.filter(function (val) {
            return originalKeys.indexOf(val) > -1;
        });
        var oldKeys = originalKeys.filter(function (val) {
            return copyKeys.indexOf(val) == -1;
        });

        for (var i = 0; i < newKeys.length; ++i) {
            original[newKeys[i]] = copy[newKeys[i]];
        }

        for (var i = 0; i < commonKeys.length; ++i) {
            var objOrig = original[commonKeys[i]];
            var objCopy = copy[commonKeys[i]];
            if (JSON.stringify(objOrig) != JSON.stringify(objCopy)) {
                if ((typeof objOrig == 'object') && (typeof objCopy == 'object'))
                    objSync(objOrig, objCopy);
                else
                    original[commonKeys[i]] = objCopy;
            }
        }

        for (var i = 0; i < oldKeys.length; ++i) {
            delete original[oldKeys[i]];
        }
    };
    this.setDefaultParamters = function (part) {
        //sets some fields contents to '{}' 
        if (part.variables === undefined || Array.isArray(part.variables))
            part.variables = {};
        if (part.events === undefined || Array.isArray(part.events))
            part.events = {};
        if (part.loopData === undefined)
            part.loopData = "{}";
        if (part.visibilityCondition === undefined)
            part.visibilityCondition = "{}";
        if (part.visibilityType === undefined)
            part.visibilityType = "conditional";
    };

});
