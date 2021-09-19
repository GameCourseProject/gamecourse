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
                noHeader: false,
                children: viewScope.view.children,
                role: viewScope.view.role,
                parentId: null,
                id: viewScope.view.id,
                class: viewScope.view.class,
                cssId: viewScope.view.cssId
            };
            if (viewScope.view.header) {
                viewScope.viewBlock.header = viewScope.view.header;
            }

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
        $rootScope.templateId = view;
        //console.log(view);
        $smartboards.request('views', 'getEdit', $.extend({ view: view, pageOrTemp: pageOrTemp }, params), function (data, err) {
            if (err) {
                func(undefined, err);
                return;
            }
            console.log("getEdit", data);
            var viewScope = $rootScope.$new(true);
            viewScope.views = data.view;
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

            $rootScope.partsHierarchy = [];
            var roleToBlock = {};

            for (let i = 0; i < viewScope.views.length; i++) {
                //to change role interaction
                const block = 'viewBlock' + i;
                roleToBlock[parseRole(viewScope.views[i].role)] = block;
                // viewScope[block] = {
                //     partType: viewScope.views[i].partType,
                //     role: viewScope.views[i].role,
                //     viewId: viewScope.views[i].viewId,
                //     parentId: null,
                //     id: viewScope.views[i].id,
                // };
                // if (viewScope.views[i].header) {
                //     viewScope[block].header = viewScope.views[i].header;
                // }
                // if (viewScope.views[i].partType == 'block') {
                //     viewScope[block].noHeader = false;
                //     viewScope[block].children = viewScope.views[i].children;
                // } else if (['text', 'image'].includes(viewScope.views[i].partType)) {
                //     viewScope[block].value = viewScope.views[i].value;
                // }
                viewScope[block] = viewScope.views[i];
                $rootScope.partsHierarchy.push(viewScope[block]);
            }

            //$rootScope.partsHierarchy = [viewScope.viewBlock];
            $rootScope.role = viewScope.views[0].role;
            $rootScope.roleType = viewScope.views[0].role.includes(">") ? "ROLE_INTERACTION" : "ROLE_SINGLE";
            $rootScope.courseRoles = data.courseRoles;
            $rootScope.viewRoles = JSON.parse(angular.toJson(data.viewRoles)); //roles for which there is at least one subview
            $rootScope.rolesHierarchy = data.rolesHierarchy;
            $rootScope.courseId = data.courseId;

            function build() {
                var viewBlock = [];
                for (let i = 0; i < viewScope.views.length; i++) {
                    const block = 'viewBlock' + i;
                    //to change role interaction
                    var element = $sbviews.build(viewScope, block, { edit: true, editData: { fields: allFields, fieldsTree: data.fields, templates: data.templates }, view: viewScope.views[i] });
                    //element.removeClass('block');
                    //element.removeAttr('data-role');
                    //element.removeAttr('data-viewId');
                    //element.css('padding-top', 18);
                    element.addClass('view editing');
                    // if (viewScope.views[i].isTemplateRef) {
                    //     //element.prepend($('<span style="color: red; display: table; margin: 5px auto 15px;">Warning: Any changes made to this block will affect the templates that use this one</span>'));
                    //     element.attr("style", "background-color: #ddedeb; ");
                    // }
                    viewBlock.push(element);
                    viewScope.$watch(block, function (newValue, oldValue) {
                        if ((newValue != oldValue) && !changing) {
                            undoStack.push(oldValue);
                            redoStack.length = 0;
                        }
                        changing = false;
                    }, true);
                }
                viewBlock[0].click();
                return viewBlock;
            }

            var viewBlock = build();

            function buildOneBlock(block) {
                const index = block.slice(-1);
                var element = $sbviews.build(viewScope, block, { edit: true, editData: { fields: allFields, fieldsTree: data.fields, templates: data.templates }, view: viewScope.views[index] });
                //element.removeClass('block');
                //element.removeAttr('data-role');
                //element.removeAttr('data-viewId');
                //element.css('padding-top', 18);
                element.addClass('view editing');
                // if (viewScope.views[index].isTemplateRef) {
                //     //element.prepend($('<span style="color: red; display: table; margin: 5px auto 15px;">Warning: Any changes made to this block will affect the templates that use this one</span>'));
                //     element.attr("style", "background-color: #ddedeb; ");
                // }
                return element;
            }

            var view = {
                scope: viewScope,
                element: viewBlock,
                roleType: $rootScope.roleType,
                courseRoles: $rootScope.courseRoles,
                viewRoles: $rootScope.viewRoles,
                viewId: viewScope.views[0].viewId,
                get: function () {
                    return viewScope.views;
                },
                undo: function () {
                    if (undoStack.length > 0) {
                        changing = true;
                        const role = $(".view.editing").not(".aspect_hide")[0].getAttribute('data-role');
                        const block = roleToBlock[role];
                        redoStack.push(angular.copy(viewScope[block]));
                        viewScope[block] = undoStack.pop();
                        var newView = buildOneBlock(block);

                        viewBlock[block.slice(-1)].replaceWith(newView);
                        view.element = viewBlock;
                        viewBlock[block.slice(-1)] = newView;
                    }
                },
                redo: function () {
                    if (redoStack.length > 0) {
                        changing = true;
                        const role = $(".view.editing").not(".aspect_hide")[0].getAttribute('data-role');
                        const block = roleToBlock[role];
                        undoStack.push(angular.copy(viewScope[block]));
                        viewScope[block] = redoStack.pop();

                        var newView = buildOneBlock(block);
                        viewBlock[block.slice(-1)].replaceWith(newView);
                        view.element = viewBlock;
                        viewBlock[block.slice(-1)] = newView;
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
        // console.log(options);
        if (part.isTemplateRef) {//adding background color, border and a warning message if its a template reference
            //ToDo: improve the look of the template references
            tempRefOptions = angular.copy(options);
            tempRefOptions.toolOptions = {};
            tempRefOptions.toolOptions.canSwitch = true;
            //tempRefOptions.toolOptions.noSettings = true;
            //tempRefOptions.toolOptions.canDuplicate = part.parentId == null ? false : true;
            tempRefOptions.toolOptions.canSaveTemplate = false;
            tempRefOptions.toolOptions.canDelete = true;
            tempRefOptions.toolOptions.canHaveAspects = true;
            partScope.role = parseRole(part.role);
            var element = this.registeredPartType[part.partType].build(partScope, part, tempRefOptions);
            //element.prepend($('<span style="color: red; display: table; margin: 5px auto 15px;">Warning: Any changes made to this block will affect the original template</span>'));
            //element.attr("style", "background-color: #ddedeb; ");//#881111
        }
        else {
            if (this.registeredPartType[part.partType] == undefined) {
                console.error('Unknown part type: ' + part.partType);
                return;
            }
            const viewer = $("#viewer_role").find(":selected")[0] ? $("#viewer_role").find(":selected")[0].text : "Default";
            partScope.role = viewer;
            if ($rootScope.roleType == 'ROLE_INTERACTION') {
                const user = $("#user_role").find(":selected")[0] ? $("#user_role").find(":selected")[0].text : "Default";
                partScope.role = user + '>' + viewer;
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
        // if (scope.part.partType == "templateRef")
        //     scope.part.partType = "block";
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

        if (part.class != undefined) {
            if (part.class.includes(';')) {
                $.each(part.class.split(';'), function (i, item) {
                    element.addClass(item);
                });
            } else
                element.addClass(part.class);
        }
        if (part.cssId != undefined) {
            element.attr('id', part.cssId);
        }
        if (part.style != undefined)
            element.attr('style', part.style);
        if (part.label != undefined)
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

        // overlay.mousedown(function (event) {
        //     if (event.target == this)
        //         execClose();
        // });

        //$('#wrapper').hide();
        if (callbackFunc != undefined)
            callbackFunc(overlayCenter, execClose);
        $(document.body).append(overlay);
    };

    this.createTool = function (title, img, click) {
        div = $("<div class='tool'></div>").addClass('btn').attr('title', title).on('click', function (e) {
            if (title == 'Edit Layout') {
                document.getElementsByClassName("tool").forEach(btn => {
                    if (btn.getAttribute("title") != 'Edit Layout') {
                        !$(btn).hasClass('disabled') ? $(btn).addClass('disabled') : $(btn).removeClass('disabled');
                    }
                });
            }
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
                    //console.log(k);
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
                            execClose(true);
                        };
                        modal.parent().attr("id", "edit_part");
                        var scopeToConfig = optionsScope;
                        $smartboards.request('views', 'getDictionary', { course: $rootScope.course }, function (data, err) {
                            //scopeToConfig.dictionary = data;
                            scopeToConfig.libraries = data.libraries;
                            scopeToConfig.variables = data.variables;
                            scopeToConfig.functions = data.functions;

                            scopeToConfig.curr_libraries = data.libraries;
                            scopeToConfig.curr_functions = {};
                            scopeToConfig.curr_variables = {};
                            scopeToConfig.hierarchyLoops = [];
                            scopeToConfig.preview_function = {};
                            //console.log(data);
                        });

                        var container = $('<div id="edit-container">');
                        var edit_container = $('<div class="settings-overlay" ng-include="\'' + $rootScope.modulesDir + '/views/partials/settings-overlay.html\'">');
                        $compile(edit_container)(optionsScope);

                        // --------- TOP SECTION ---------
                        rightbox = $('<div class="help-right-box">');
                        topbox = $('<div id="help-box">');

                        // --------- EXPRESSION SECTION ---------
                        expression = $('<div style="margin-bottom:0.5rem;"><span style="font-size: 16px;font-weight: 500;">Helper</span></div>');
                        expressionInput = $('<div id="expression-functions"></div>');
                        typing_help = $('<div id="typing-help"\
                        <span><strong>Tips</strong> </span>\
                        <div class="icon editor-icon collapse_icon" style="float: right;margin-top: -4px;" title="Toggle" ng-click="toggleTipsSection($event)"></div>\
                        <div id="preview-tips">\
                        <p>Type any of the following modules/variables for Expression Language (EL) suggestions.</p>\
                        <p>To use Expression Language, use {} around the expression.</p>\
                        <p>For variables, use % behind its name.</p>\
                        <p style="color: tomato;">For Loop Data, Visibility and Events you must write only inside {}.</p>\
                        <p>For visibility, you must write a condition, for example, using boolean functions (<object>.isActive) or comparing 2 expressions (%item.number > %badgeLevel.number).</p>\
                        <p>To preview expressions, click the button below. If your expression has content besides EL, please select <strong>only</strong> the part that is EL, including {}.</p>\
                        <p>More information about the GameCourse Expression Language dictionary is located <a href="docs" class="hlink">here</a>.</p></div></div>');
                        expressionList = $('<ul></ul>');
                        expressionLibs = $('<li ng-repeat="lib in curr_libraries" ng-if="lib.moduleId != null" class="lib-list-item">{{lib.name}}</li>');
                        expressionFuncs = $('<li ng-repeat="func in curr_functions" class="func-list-item">{{func.keyword}}</li>');
                        expressionVars = $('<li ng-repeat="var in curr_variables" class="var-list-item">{{var.name}}</li>');
                        functionDesc = $('<div id="func-info" style="display:none;">\
                            <div ng-if="!isEmptyObject(active_func.args)" class="arguments-div">Arguments:\
                            <ul id="func-info-args"><li ng-repeat="arg in active_func.args" class="arg-list-item"><strong>{{arg.name}}</strong>\
                            <span ng-if="arg.type != \'\'"><strong>:</strong> {{arg.type}}</span>\
                            <span ng-if="arg.optional != \'1\'" class="optional">*</span></li></ul></div>\
                            <div ng-if="active_func.returnType != \'\'" class="return-div">Return Type:\
                            <span>{{active_func.returnType}}</span></div>\
                            <div ng-if="active_func.keyword == \'parent\'" class="return-div">Return Object:\
                            <span>{{active_func.returnName}}</span></div>\
                            <div class="function-description" ng>{{active_func.description}}</div></div>');
                        varDesc = $('<div id="var-info" style="display:none;">\
                            <div ng-if="active_var.returnType != \'\'" class="return-div">Return Type:\
                            <span>{{active_var.returnType}}</span></div>\
                            <div ng-if="active_var.name == \'%item\'" class="return-div">Return Object:\
                            <span>{{active_var.returnName}}</span></div>\
                            <div ng-if="active_var.returnValue" class="return-div">Return Value:\
                            <span>{{active_var.returnValue}}</span></div>\
                            <div ng-if="active_var.library == null" class="var-warning">\
                            Be careful using this variable. It was defined in a loop data of a outer view.\nAlso, check if it has any attributes or functions by typing a ".".</div></div>');

                        expressionList.append(expressionLibs, expressionFuncs, expressionVars);

                        expressionInput.append(typing_help, expressionList, functionDesc, varDesc);
                        topbox.append(expression, expressionInput);
                        rightbox.append(topbox);


                        // --------- BOTTOM SECTION ---------
                        bottombox = $('<div class="preview-box">');

                        // --------- PREVIEW SECTION ---------

                        //preview = $('<div class="preview-title"><span>Preview Section</span></div>');

                        previewbutton = $('<button id="preview-exp-button" ng-click="previewExpression()">Preview Expression</button>');


                        bottombox.append(previewbutton);
                        rightbox.append(bottombox);
                        $compile(rightbox)(optionsScope);

                        previewModal = $("<div class='modal' id='open-preview' value='#open-preview'></div>");
                        open_preview = $("<div class='modal_content'></div>");
                        open_preview.append($('<button class="close_btn icon" id="close" value="#open-preview" onclick="closeModal(this);"></button>'));
                        open_preview.append($('<div class="title">Preview Expression</div>'));
                        content = $('<div class="content">');
                        previewbox = $('<div id="preview-expressions"></div>');
                        previewboxlist = $('<ul></ul>');
                        previewboxlist.append('<li class="request-li">> {{preview_function.expr}}</li>\
                        <span ng-if="preview_function.response_array != null">\
                        <li class="response-index-li">Preview finished with no errors. {{preview_function.response_array.length}} line(s) retrieved.</li>\
                        <span ng-if="preview_function.response_array" ng-repeat="line in preview_function.response_array">\
                        <li class="response-index-li">[{{$index}}]:</li><li class="response-item-li">\
                        <span ng-repeat="(key,value) in line">{{key}} : {{value}}<span ng-show="!$last">, </span></span>\
                        </li></span></span>\
                        <span ng-if="preview_function.response"><li class="response-single-li">{{preview_function.response}}</li>');

                        previewbox.append(previewboxlist);
                        content.append(previewbox);
                        open_preview.append(content);
                        previewModal.append(open_preview);
                        $compile(previewModal)(optionsScope);


                        var confirmation_btns = $('<div class="confirmation_btns"><button class="cancel" value="#edit_part" ng-click="closeOverlay()"> Cancel </button><button value="#edit_part" ng-click="saveEdit();"> Save </button></div>');
                        $compile(confirmation_btns)(optionsScope);

                        container.append(edit_container, rightbox);
                        modal.append(container);
                        modal.append(previewModal);
                        modal.append(confirmation_btns);
                        modal.on('mouseenter', function () {
                            //this ensures that when visibility is not conditional, the field will be disabled
                            optionsScope.toggleVisCondition();
                        });

                        optionsScope.isEmptyObject = function (obj) {
                            return angular.equals({}, obj) || angular.equals(null, obj);
                        };

                        optionsScope.toggleTipsSection = function ($event) {

                            target = $event.target;
                            selector = "#preview-tips";
                            var state = $(selector).css("display");

                            if (state == 'block') {
                                $(selector).css("display", "none");
                                target.classList.remove("collapse_icon");
                                target.classList.add("expand_icon");
                            }
                            else if (state == 'none') {
                                $(selector).css("display", "block");
                                target.classList.remove("expand_icon");
                                target.classList.add("collapse_icon");
                            }

                        }

                        optionsScope.previewExpression = function () {

                            if (optionsScope.focused) {
                                const cm = optionsScope.focused;
                                const textArea = cm.getTextArea().getAttribute("id");
                                var value = cm.getValue();
                                selection = { start: cm.getCursor(true), end: cm.getCursor(false) };
                                if (selection.start.ch != selection.end.ch) {
                                    value = cm.getSelection();
                                    optionsScope.active_func = {};
                                }
                                //expressions = value.match(/{[^{}]*}/g);


                                // for (expr of expressions) {
                                //     valueWithoutArgs = expr.replace(/\([0-9A-Za-z,"'_%\. ]*\)/g, '').split(".");
                                //     console.log(valueWithoutArgs);

                                //     if (valueWithoutArgs.startsWith("%")) {

                                //     }
                                if (value.includes("%")) {
                                    //WHEN IT HAS VARS, WE HAVE TO CALCULATE THEIR VALUES, maybe not worth it
                                    giveMessage('Expressions with variables cannot be tested, you have to preview the whole view');
                                } else {
                                    openModal(document.getElementById('open-preview'));
                                    if (!optionsScope.isEmptyObject(optionsScope.active_func)) {
                                        func = optionsScope.active_func["keyword"];
                                        funclib = optionsScope.active_func["name"];
                                        args = optionsScope.curr_args;
                                        return_type = optionsScope.active_func["returnType"];
                                    } else {
                                        funclib = value.split(".")[0].replace(/[{}]/g, '');
                                        func = value.split(".")[1].replace(/[{}]/g, '');;
                                        funcName = func.replace(/\([0-9A-Za-z,"'_%\. ]*\)/g, '');
                                        args_part = func.split("(");
                                        args = args_part[args_part.length - 1].split(")");
                                        args = args[0].split(",");
                                        return_type = optionsScope.functions.filter(el => el["keyword"] == funcName && el["moduleId"] == funclib)[0]["returnType"];
                                    }

                                    request = { "expr": value.replace(/[{}]/g, ''), "func": func, "funclib": funclib, "args": args, "returnType": return_type };

                                    $smartboards.request('views', 'testExpression', { course: $rootScope.course, expression: value }, function (data, err) {
                                        if (err) {
                                            giveMessage('Something is wrong with your expression... :( Try again!');
                                            return;
                                        }

                                        if (textArea == "loopData") {
                                            request["response_array"] = JSON.parse(data);
                                            request["response"] = null;
                                            //request["preview_type"] = "function";
                                        }
                                        else {
                                            request["response_array"] = null;
                                            request["response"] = data;
                                            //request["preview_type"] = "function";
                                        }

                                        optionsScope.preview_function = request;
                                        // openModal(document.getElementById("open-preview"));
                                    });
                                    // }
                                }

                            } else {
                                giveMessage('Select one of the to see its preview!');
                            }


                        }

                        optionsScope.getAvailableVariables = function (thisPart, allVars = []) {

                            if (thisPart.parentId != null) {
                                partParent = $sbviews.findPart(thisPart.parentId, getViewerFromRole(thisPart.role), $rootScope.partsHierarchy, true);
                                newVariables = optionsScope.getAvailableVariables(partParent, allVars);
                                if (Object.keys(newVariables).length != 0) {
                                    for (var key in newVariables) {
                                        allVars.push([key, newVariables[key]]);
                                    }
                                }
                            }
                            vars = thisPart.variables;
                            if (Object.keys(vars).length != 0) {
                                for (var key in vars) {
                                    allVars.push([key, vars[key]]);
                                }
                            }

                            if (thisPart.loopData != "{}") {
                                var itemReturnName;
                                if (!thisPart.loopData.startsWith("{%")) {
                                    itemReturnName = optionsScope.functions.filter(el => el["keyword"] == thisPart.loopData.replace(/[{}]/g, '').replace(/\([0-9A-Za-z,"'_%\. ]*\)/g, '').split(".")[1])[0]["returnName"];
                                } else {
                                    itemReturnName = allVars.filter(el => {
                                        if (!Array.isArray(el) && thisPart.loopData.replace(/[{}]/g, '').includes(el["name"]))
                                            return el;
                                    })[0]["returnName"];
                                }
                                optionsScope.$apply(optionsScope.itemReturnName = itemReturnName);
                                optionsScope.$apply(optionsScope.hierarchyLoops.push({ "viewId": thisPart.viewId, "returnName": itemReturnName }));
                            }


                            allVars.forEach((el) => {
                                if (Array.isArray(el) && el[1]["value"]) {

                                    const value = el[1]["value"];
                                    var returnName = null;
                                    //even if there are more than 1 function with the same name, it will return the same type
                                    const func = optionsScope.functions.filter(el => el["keyword"] == value.replace(/[{}]/g, '').replace(/\([0-9A-Za-z,"'_%\. ]*\)/g, '').split(".")[1])[0];
                                    if (value.replace(/[{}]/g, '').replace(/\([0-9A-Za-z,"'_%\. ]*\)/g, '').includes(".")) {
                                        returnName = func["returnName"];
                                    } else if (value.replace(/[{}]/g, '').replace(/\([0-9A-Za-z,"'_%\. ]*\)/g, '').includes("%")) {
                                        returnName = optionsScope.itemReturnName;
                                    }
                                    const newEl = { 'library': null, 'name': "%" + el[0], 'returnType': func ? func["returnType"] : value == "%item" ? "object" : '', 'returnName': returnName, 'returnValue': value.replace(/[{}]/g, '') };
                                    if (!Object.values(allVars).some(element => element['name'] == "%" + el[0]))
                                        allVars.push(newEl);
                                }
                            });;
                            return allVars.filter((el, i) => allVars.indexOf(el) === i && !Array.isArray(el));
                        }

                        optionsScope.getOptions = function (el, list) {
                            const options = {
                                hint: function () {
                                    var cur = el.getCursor();
                                    var curLine = el.getLine(cur.line);
                                    var start = cur.ch;
                                    var end = start;
                                    while (end < curLine.length && /[\w$]/.test(curLine.charAt(end))) ++end;
                                    while (start && /[%\w$]/.test(curLine.charAt(start - 1))) --start;
                                    var curWord = start !== end && curLine.slice(start, end);
                                    var regex = new RegExp('^' + curWord, 'i');
                                    var completion = {
                                        list: (!curWord ? [] : list.filter(function (item) {
                                            return item.match(regex);
                                        })).sort(),
                                        from: CodeMirror.Pos(cur.line, start),
                                        to: CodeMirror.Pos(cur.line, end)
                                    };
                                    if (completion) {
                                        CodeMirror.on(completion, 'pick', function (selectedItem) {
                                            var cursor = el.doc.getCursor();
                                            var replaceChar = '';
                                            if (!optionsScope.isEmptyObject(optionsScope.curr_functions)) {
                                                var hasArgs = !optionsScope.isEmptyObject(optionsScope.curr_functions.filter(el => el['keyword'] == selectedItem)[0].args);
                                                if (hasArgs)
                                                    replaceChar = "(";
                                            } else if (!optionsScope.isEmptyObject(optionsScope.curr_libraries)) {
                                                replaceChar = ".";
                                            }

                                            el.doc.replaceRange(replaceChar, cursor);
                                            // optionsScope.checkSuggestions(el, true);
                                        })
                                    }
                                    return completion;
                                }
                                , completeSingle: false,

                            };
                            return options;
                        }

                        optionsScope.available = function (id, module = '', variable = "") {

                            if (id == 'loopData') {
                                return {
                                    'lib': optionsScope.libraries.filter(el => {
                                        return el["moduleId"] != null && el["name"] != 'actions' && el["name"] != 'system';
                                    }),
                                    'func': optionsScope.functions.filter(el => {
                                        return (module != '' ? (module == "!=" ? el["moduleId"] != null : el["moduleId"] == null) : 1) && el["name"] != "system" && el["returnType"] == 'collection' && el["refersToType"] == optionsScope.currentReturnType;
                                    })
                                }
                            }
                            else if (id == 'value') {
                                return {
                                    'lib': optionsScope.libraries.filter(el => {
                                        return el["moduleId"] != null && el["name"] != 'actions';
                                    }),
                                    'func': optionsScope.functions.filter(el => {
                                        return (module != '' ? (module == "!=" ? el["moduleId"] != null : el["moduleId"] == null) : 1) &&
                                            (variable != "" && el["refersToName"] ? el["refersToName"] == variable : 1) &&
                                            el["refersToType"] == optionsScope.currentReturnType;
                                    })
                                }
                            } else if (id == 'link') {
                                return {
                                    'lib': optionsScope.libraries.filter(el => {
                                        return el["name"] == 'actions';
                                    }),
                                    'func': optionsScope.functions.filter(el => {
                                        return (module != '' ? (module == "!=" ? el["moduleId"] != null : el["moduleId"] == null) : 1) &&
                                            (variable != "" && el["refersToName"] ? el["refersToName"] == variable : 1) &&
                                            el["refersToType"] == optionsScope.currentReturnType;
                                    })
                                }

                            } else if (id.includes('events')) {
                                return {
                                    'lib': optionsScope.libraries.filter(el => {
                                        return el["name"] == 'actions';
                                    }),
                                    'func': optionsScope.functions.filter(el => {
                                        return el["name"] == 'actions';
                                    })
                                }
                            } else if (id.includes('variables')) {
                                return {
                                    'lib': optionsScope.libraries.filter(el => {
                                        return el["moduleId"] != null && el["name"] != 'actions';
                                    }),
                                    'func': optionsScope.functions.filter(el => {
                                        return (module != '' ? (module == "!=" ? el["moduleId"] != null : el["moduleId"] == null) : 1) &&
                                            (variable != "" && el["refersToName"] ? el["refersToName"] == variable : 1) &&
                                            el["refersToType"] == optionsScope.currentReturnType;
                                    })
                                }
                            } else if (id == "visibilityCondition") {
                                return {
                                    'lib': optionsScope.libraries.filter(el => {
                                        return el["moduleId"] != null && el["name"] != 'actions';
                                    }),
                                    'func': optionsScope.functions.filter(el => {
                                        return (module != '' ? (module == "!=" ? el["moduleId"] != null : el["moduleId"] == null) : 1) &&
                                            (variable != "" && el["refersToName"] ? el["refersToName"] == variable : 1) &&
                                            el["refersToType"] == optionsScope.currentReturnType;
                                    })
                                }
                            }

                        };

                        optionsScope.getCondition = function (function_typed) {
                            const hypothesis = ["==", ">=", "<=", "!=", "<", ">"];
                            for (cond of hypothesis) {
                                if (function_typed.includes(cond))
                                    return cond;
                            }
                            return false;
                        }

                        optionsScope.buildCodeMirrorBox = function (id) {


                            var boxCodeMirror = CodeMirror.fromTextArea(document.getElementById(id), {
                                lineNumbers: false, styleActiveLine: true, autohint: true, lineWrapping: true,
                                theme: "mdn-like", value: $("#" + id).val(), placeholder: "Expression"
                            });


                            boxCodeMirror.on("focus", function (cm, event) {
                                if (id.includes('events'))
                                    optionsScope.$apply(optionsScope.curr_variables = {});
                                //$('#preview-exp-button').attr("disabled", false);
                                optionsScope.checkSuggestions(cm, true);
                            });

                            boxCodeMirror.on("blur", function (cm, event) {
                                optionsScope.$apply(optionsScope.focused = cm);
                            });

                            // eventCodeMirror.on("cursorActivity", function (cm, event) {
                            //     optionsScope.checkSuggestions(cm, true);
                            // });

                            boxCodeMirror.on("keyup", function (cm, event) {
                                //$("#typing-help").hide();

                                if (event.keyCode === 13) { //enter 
                                    optionsScope.checkSuggestions(cm, true);
                                    //$(".CodeMirror-hint-active").click();
                                }

                                else if (!(event.keyCode == 37 || event.keyCode == 38 || event.keyCode == 39 || event.keyCode == 40)) {
                                    // checks if arrow keys were not pressed - causes errors
                                    // this simple check solves errors with selection on autocomplete
                                    optionsScope.checkSuggestions(cm);
                                }
                            });

                        }

                        optionsScope.buildCodeMirrorBoxes = function () {

                            optionsScope.$apply(optionsScope.currentReturnType = null);

                            if (optionsScope.part.partType != 'text') {
                                // --------- LOOP DATA EDITOR ---------
                                optionsScope.buildCodeMirrorBox('loopData');
                            }
                            if (optionsScope.part.partType != 'table' && optionsScope.part.partType != 'block') {
                                // --------- CONTENT EDITOR ---------

                                optionsScope.buildCodeMirrorBox('value');
                            }
                            // --------- EVENTS EDITOR(S) ---------
                            if (!optionsScope.isEmptyObject(optionsScope.part.events)) {
                                for (event in optionsScope.part.events) {
                                    optionsScope.buildCodeMirrorBox("events." + event);
                                }
                            }
                            // --------- VARIABLES EDITOR(S) ---------
                            if (!optionsScope.isEmptyObject(optionsScope.part.variables)) {
                                for (variable in optionsScope.part.variables) {
                                    optionsScope.buildCodeMirrorBox("variables." + variable);
                                }
                            }
                            // --------- VISIBILITY EDITOR(S) ---------
                            if (optionsScope.part.visibilityType == 'conditional') {
                                optionsScope.buildCodeMirrorBox('visibilityCondition');
                            }
                            // --------- LINK EDITOR(S) ---------
                            if (optionsScope.part.link != null) {
                                optionsScope.buildCodeMirrorBox('link');
                            }

                        }

                        optionsScope.checkSuggestions = function (cm, fromClick = false) {
                            optionsScope.$apply(optionsScope.hierarchyLoops = []);
                            let textArea = cm.getTextArea().getAttribute("id");
                            var hierarchyVars = optionsScope.getAvailableVariables(part);
                            var varlist = angular.copy(optionsScope.variables);
                            varlist.forEach(el => {
                                if (el["name"] == "%item")
                                    el["returnName"] = optionsScope.itemReturnName;
                            });

                            var line = cm.doc.getCursor().line;
                            ch = cm.doc.getCursor().ch;
                            textBefore = cm.doc.getLine(line).substr(0, ch);

                            //variables
                            if (textBefore.match(/{%[A-Za-z0-9]*$/)) {
                                $(".func-list-item").css("font-weight", "normal");
                                $(".func-list-item").css("color", "#333");
                                $("#func-info").hide();
                                $("#var-info").hide();
                                $(".var-list-item").css("font-weight", "normal");
                                $(".var-list-item").css("color", "#333");
                                optionsScope.$apply(optionsScope.active_func = {});
                                optionsScope.$apply(optionsScope.curr_functions = {});
                                optionsScope.$apply(optionsScope.curr_variables = varlist.concat(hierarchyVars));
                                optionsScope.$apply(optionsScope.curr_libraries = {});
                                gccomp = textBefore.split("{");
                                var_typed = gccomp[gccomp.length - 1];

                                if (var_typed != "") {
                                    optionsScope.$apply(optionsScope.curr_variables = optionsScope.curr_variables.filter(el => el["name"].startsWith(var_typed)));
                                    if (optionsScope.curr_variables.length == 1 && optionsScope.curr_variables[0]["name"] == var_typed) {
                                        optionsScope.$apply(optionsScope.active_var = optionsScope.curr_variables[0]);
                                        $("#var-info").show();
                                        $(".var-list-item").css("font-weight", "bold");
                                        $(".var-list-item").css("color", "#905");
                                    }

                                }

                                // var options = optionsScope.getOptions(cm, varlist.concat(hierarchyVars).map(el => el["name"]));
                                // cm.showHint(options);
                                if (fromClick) {
                                    //give no options to remove the hints since the user has selected an options
                                    // needed because it kept the option(s) in the suggestions
                                    var options = optionsScope.getOptions(cm, []);
                                    cm.showHint(options);
                                } else {
                                    var options = optionsScope.getOptions(cm, varlist.concat(hierarchyVars).map(el => el["name"]));
                                    cm.showHint(options);
                                }

                            }
                            //libraries
                            if (textBefore.match(/{[A-Za-z]*$/)) {
                                $(".func-list-item").css("font-weight", "normal");
                                $(".func-list-item").css("color", "#333");
                                $("#func-info").hide();
                                $("#var-info").hide();
                                $(".var-list-item").css("font-weight", "normal");
                                $(".var-list-item").css("color", "#333");
                                optionsScope.$apply(optionsScope.active_func = {});
                                optionsScope.$apply(optionsScope.curr_functions = {});
                                optionsScope.$apply(optionsScope.curr_variables = {});
                                optionsScope.$apply(optionsScope.curr_libraries = optionsScope.available(textArea).lib);
                                gccomp = textBefore.split("{");
                                module_typed = gccomp[gccomp.length - 1];

                                if (module_typed != "") {
                                    optionsScope.$apply(optionsScope.curr_libraries = optionsScope.curr_libraries.filter(el => el["name"].startsWith(module_typed)));
                                } else {
                                    //$("#typing-help").show();
                                    if (!textArea.includes('events'))
                                        optionsScope.$apply(optionsScope.curr_variables = varlist.concat(hierarchyVars));
                                }


                                var options = optionsScope.getOptions(cm, optionsScope.available(textArea).lib.map(value => value["name"]));
                                cm.showHint(options);

                            }
                            //functions for libraries or over other functions
                            if (textBefore.match(/{[A-Za-z]+\.([0-9A-Za-z,"'_ %\.]*\(?\)?\.?)+$/g)) {
                                $(".func-list-item").css("font-weight", "normal");
                                $(".func-list-item").css("color", "#333");
                                $("#func-info").hide();
                                $("#var-info").hide();
                                $(".var-list-item").css("font-weight", "normal");
                                $(".var-list-item").css("color", "#333");
                                optionsScope.$apply(optionsScope.curr_libraries = {});
                                optionsScope.$apply(optionsScope.curr_variables = {});
                                gcfunc = textBefore.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, '').split("."); // does not include args
                                function_typed = gcfunc[gcfunc.length - 1];
                                functionWithArgs = textBefore.match(new RegExp("" + gcfunc[gcfunc.length - 1] + "\\([0-9A-Za-z,\"'_% \\.]*\\)?", "g"));
                                if (functionWithArgs)
                                    function_typed = functionWithArgs[0];
                                func_module = gcfunc[0].split("{")[1];
                                lastfunction = gcfunc[gcfunc.length - 2];//.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, '');

                                if ((/*!function_typed.endsWith(")") &&*/ gcfunc.length == 2)) // if we only have written the module/library
                                    optionsScope.$apply(optionsScope.currentReturnType = 'library');
                                else if (gcfunc.length > 2) {
                                    let object = optionsScope.functions.filter(el => (el["name"] == func_module || el["name"] == null) && el["keyword"] == lastfunction)[0];
                                    optionsScope.$apply(optionsScope.currentReturnType = object["returnType"]);
                                }

                                optionsScope.$apply(optionsScope.curr_functions = optionsScope.available(textArea).func);

                                if (function_typed != "") {
                                    var func = optionsScope.curr_functions.filter(el => (el["name"] == func_module || el["name"] == null) && el["keyword"] == function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, ''));
                                    if (function_typed.endsWith("(")) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => (el["name"] == func_module || el["name"] == null) && el["keyword"].startsWith(function_typed.replace(/[\(]/g, ''))));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed.replace(/[\(]/g, '')) {
                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                        }
                                    } else if (func.length > 0 && optionsScope.isEmptyObject(func[0].args)) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => (el["name"] == func_module || el["name"] == null) && el["keyword"].startsWith(function_typed)));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed) {
                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            optionsScope.$apply(optionsScope.currentReturnType = optionsScope.active_func["returnType"]);
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                        }
                                    } else if (function_typed.includes("(") && !function_typed.includes(")") || function_typed.endsWith(")")) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => (el["name"] == func_module || el["name"] == null) && el["keyword"].startsWith(function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, ''))));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, '')) {
                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                            if (function_typed.endsWith(")")) {
                                                args_part = function_typed.split("(");
                                                args = args_part[args_part.length - 1].split(")");
                                                func_args = args[0].split(",");

                                                new_args = [];

                                                func_args.forEach(function (el) {
                                                    arg = el.replace(/^\"+|\"+$/g, '');
                                                    argn = arg.replace(/^\'+|\'+$/g, '');
                                                    new_args.push(argn);
                                                });
                                                //console.log(new_args);
                                                optionsScope.$apply(optionsScope.curr_args = new_args);
                                            }
                                        }
                                    }
                                    else {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => (el["name"] == func_module || el["name"] == null) && el["keyword"].startsWith(function_typed)));
                                    }
                                }
                                else {
                                    optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["name"] == func_module || el["name"] == null));
                                }

                                // funclist = funclist.filter(el => el["moduleId"] != null && el["name"] == func_module && el["refersToType"] == optionsScope.currentReturnType);
                                // funclist = funclist.map(value => value["keyword"]);

                                // let replaceChar = '';
                                // if (optionsScope.active_func == optionsScope.curr_functions[0] && optionsScope.curr_functions.length == 1) {
                                //     var hasArgs = !optionsScope.isEmptyObject(optionsScope.active_func.args);
                                //     if (hasArgs)
                                //         replaceChar = "(";
                                // }
                                if (fromClick) {
                                    //give no options to remove the hints since the user has selected an options
                                    // needed because it kept the option(s) in the suggestions
                                    var options = optionsScope.getOptions(cm, []);
                                    cm.showHint(options);
                                } else {
                                    var options = optionsScope.getOptions(cm, optionsScope.available(textArea).func.filter(el => el["name"] == func_module || el["name"] == null).map(value => value["keyword"]));
                                    cm.showHint(options);
                                }



                            }
                            //props and functions of vars
                            if (textBefore.match(/{%[A-Za-z0-9]*\.([A-Za-z]*\(?[0-9A-Za-z,"'_% \.]*\)?\.?)+$/)) {
                                $(".func-list-item").css("font-weight", "normal");
                                $(".func-list-item").css("color", "#333");
                                $("#func-info").hide();
                                $("#var-info").hide();
                                $(".var-list-item").css("font-weight", "normal");
                                $(".var-list-item").css("color", "#333");
                                optionsScope.$apply(optionsScope.curr_libraries = {});
                                optionsScope.$apply(optionsScope.curr_variables = {});
                                gcvar = textBefore.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, '').split("."); // does not include args
                                function_typed = gcvar[gcvar.length - 1];
                                functionWithArgs = textBefore.match(new RegExp("" + gcvar[gcvar.length - 1] + "\\([0-9A-Za-z,\"'_% \\.]*\\)?", "g"));
                                if (functionWithArgs)
                                    function_typed = functionWithArgs[0];
                                var_typed = gcvar[0].split("{")[1];
                                lastobject = gcvar[gcvar.length - 2];//.replace(/\([0-9A-Za-z,"'_ %]*\)?/g, '');

                                let variable = varlist.concat(hierarchyVars).filter(el => el["name"] == var_typed)[0]; // %{var} 
                                let object = variable; // %{var}.(...) ; variable over which the function will be applied
                                if (gcvar.length > 2) { // when user writes like %item.parent. (...)
                                    object = optionsScope.functions.filter(el => (el["name"] == variable["returnName"] || el["name"] == null) && el["keyword"] == lastobject)[0];
                                }


                                if (object["keyword"] == "parent") {
                                    //if item, length = 2 and we will want the last on the list, if parent, we want the second but last
                                    // for item -> optionsScope.hierarchyLoops.length; parent -> optionsScope.hierarchyLoops.length - 1....
                                    bias = gcvar.length - 1;
                                    object["returnName"] = optionsScope.hierarchyLoops[optionsScope.hierarchyLoops.length - bias]["returnName"];
                                }
                                optionsScope.$apply(optionsScope.currentReturnType = object["returnType"]);
                                optionsScope.$apply(optionsScope.curr_functions = optionsScope.available(textArea, "", object["returnName"] ? object["returnName"] : "").func);

                                if (function_typed != "") {
                                    var func = optionsScope.curr_functions.filter(el => el["keyword"] == function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, ''));
                                    if (function_typed.endsWith("(")) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"].startsWith(function_typed.replace(/[\(]/g, ''))));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed.replace(/[\(]/g, '')) {
                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                        }
                                    } else if (func.length > 0 && optionsScope.isEmptyObject(func[0].args)) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"].startsWith(function_typed)));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed) {

                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            if (function_typed == "parent") {
                                                //here parent is the function and not the object
                                                bias = gcvar.length;
                                                optionsScope.$apply(optionsScope.active_func["returnName"] = optionsScope.hierarchyLoops[optionsScope.hierarchyLoops.length - bias]["returnName"]);
                                            }
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                        }
                                    } else if (function_typed.includes("(") && !function_typed.includes(")") || function_typed.endsWith(")")) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"].startsWith(function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, ''))));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, '')) {
                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                            if (function_typed.endsWith(")")) {
                                                args_part = function_typed.split("(");
                                                args = args_part[args_part.length - 1].split(")");
                                                func_args = args[0].split(",");

                                                new_args = [];

                                                func_args.forEach(function (el) {
                                                    arg = el.replace(/^\"+|\"+$/g, '');
                                                    argn = arg.replace(/^\'+|\'+$/g, '');
                                                    new_args.push(argn);
                                                });
                                                //console.log(new_args);
                                                optionsScope.$apply(optionsScope.curr_args = new_args);
                                            }
                                        }
                                    }
                                    else {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"].startsWith(function_typed)));
                                    }
                                }
                                else {
                                    optionsScope.$apply(optionsScope.curr_functions = optionsScope.available(textArea, "", object["returnName"] ? object["returnName"] : "").func);
                                }
                                //removes parent when there is no more hierarchy
                                if (gcvar.length > optionsScope.hierarchyLoops.length) {
                                    optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"] != "parent"));
                                }
                                // funclist = angular.copy(optionsScope.functions);
                                // funclist = funclist.filter(el => el["refersToType"] == optionsScope.currentReturnType && (variable["returnName"] != "" ? el["refersToName"] == variable["returnName"] : 1));
                                // funclist = funclist.map(value => value["keyword"]);
                                //let replaceChar = '';
                                //if (optionsScope.active_func == optionsScope.curr_functions[0] && optionsScope.curr_functions.length == 1) {

                                // console.log(hasArgs);

                                //}
                                if (fromClick) {
                                    //give no options to remove the hints since the user has selected an options
                                    // needed because it kept the option(s) in the suggestions
                                    var options = optionsScope.getOptions(cm, []);
                                    cm.showHint(options);
                                } else {
                                    var options = optionsScope.getOptions(cm, optionsScope.available(textArea, "", object["returnName"] ? object["returnName"] : "").func.map(value => value["keyword"]));
                                    cm.showHint(options);
                                }

                            }

                            //visibility condition
                            if (textBefore.match(/{%[A-Za-z0-9]*\.([A-Za-z]*\(?[0-9A-Za-z,"'_% \.]*\)?\.?)+ ?[<=>!]{1,2} ?%?[A-Za-z0-9]*(\.?[A-Za-z]*\(?[0-9A-Za-z,"'_% \.]*\)?\.?)+$/)) {
                                $(".func-list-item").css("font-weight", "normal");
                                $(".func-list-item").css("color", "#333");
                                $("#func-info").hide();
                                $("#var-info").hide();
                                $(".var-list-item").css("font-weight", "normal");
                                $(".var-list-item").css("color", "#333");
                                optionsScope.$apply(optionsScope.curr_libraries = {});
                                optionsScope.$apply(optionsScope.curr_variables = {});

                                var gccond = optionsScope.getCondition(textBefore.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, ''));

                                optionsScope.$apply(optionsScope.curr_variables = varlist.concat(hierarchyVars));
                                optionsScope.$apply(optionsScope.curr_functions = {});
                                //get last var to give suggestions
                                gcvar = textBefore.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, '').split(gccond)[1].split("."); // does not include args
                                function_typed = "";
                                if (gcvar.length > 1) {
                                    function_typed = gcvar[gcvar.length - 1];
                                    functionWithArgs = textBefore.match(new RegExp("" + gcvar[gcvar.length - 1] + "\\([0-9A-Za-z,\"'_% \\.]*\\)?", "g"));
                                    if (functionWithArgs)
                                        function_typed = functionWithArgs[0];
                                }

                                var_typed = gcvar[0];

                                lastobject = gcvar[gcvar.length - 2];//.replace(/\([0-9A-Za-z,"'_ %]*\)?/g, '');

                                if (gcvar.length > 1) {
                                    let variable = varlist.concat(hierarchyVars).filter(el => el["name"] == var_typed)[0]; // %{var} 
                                    var object = variable; // %{var}.(...) ; variable over which the function will be applied
                                    if (gcvar.length > 2) { // when user writes like %item.parent. (...)
                                        object = optionsScope.functions.filter(el => (el["name"] == variable["returnName"] || el["name"] == null) && el["keyword"] == lastobject)[0];
                                    }


                                    if (object["keyword"] == "parent") {
                                        //if item, length = 2 and we will want the last on the list, if parent, we want the second but last
                                        // for item -> optionsScope.hierarchyLoops.length; parent -> optionsScope.hierarchyLoops.length - 1....
                                        bias = gcvar.length - 1;
                                        object["returnName"] = optionsScope.hierarchyLoops[optionsScope.hierarchyLoops.length - bias]["returnName"];
                                    }
                                    optionsScope.$apply(optionsScope.currentReturnType = object["returnType"]);
                                    optionsScope.$apply(optionsScope.curr_functions = optionsScope.available(textArea, "", object["returnName"] ? object["returnName"] : "").func);
                                    optionsScope.$apply(optionsScope.curr_variables = {});
                                }
                                if (function_typed != "") {
                                    var func = optionsScope.curr_functions.filter(el => el["keyword"] == function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, ''));
                                    if (function_typed.endsWith("(")) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"].startsWith(function_typed.replace(/[\(]/g, ''))));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed.replace(/[\(]/g, '')) {
                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                        }
                                    } else if (func.length > 0 && optionsScope.isEmptyObject(func[0].args)) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"].startsWith(function_typed)));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed) {

                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            if (function_typed == "parent") {
                                                //here parent is the function and not the object
                                                bias = gcvar.length;
                                                optionsScope.$apply(optionsScope.active_func["returnName"] = optionsScope.hierarchyLoops[optionsScope.hierarchyLoops.length - bias]["returnName"]);
                                            }
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                        }
                                    } else if (function_typed.includes("(") && !function_typed.includes(")") || function_typed.endsWith(")")) {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"].startsWith(function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, ''))));
                                        if (optionsScope.curr_functions[0]["keyword"] == function_typed.replace(/\([0-9A-Za-z,"'_ %\.]*\)?/g, '')) {
                                            optionsScope.$apply(optionsScope.curr_functions = [optionsScope.curr_functions[0]]);
                                            optionsScope.$apply(optionsScope.active_func = optionsScope.curr_functions[0]);
                                            $("#func-info").show();
                                            $(".func-list-item").css("font-weight", "bold");
                                            $(".func-list-item").css("color", "#905");
                                        }
                                    }
                                    else {
                                        optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"].startsWith(function_typed)));
                                    }
                                }
                                //function == ""
                                else if (gcvar.length > 1) {
                                    optionsScope.$apply(optionsScope.curr_functions = optionsScope.available(textArea, "", object["returnName"] ? object["returnName"] : "").func);
                                }
                                //variable
                                else {
                                    optionsScope.$apply(optionsScope.curr_variables = optionsScope.curr_variables.filter(el => el["name"].startsWith(var_typed)));
                                    if (optionsScope.curr_variables.length == 1 && optionsScope.curr_variables[0]["name"] == var_typed) {
                                        optionsScope.$apply(optionsScope.active_var = optionsScope.curr_variables[0]);
                                        $("#var-info").show();
                                        $(".var-list-item").css("font-weight", "bold");
                                        $(".var-list-item").css("color", "#905");
                                    }
                                }
                                //removes parent when there is no more hierarchy
                                if (gcvar.length > optionsScope.hierarchyLoops.length) {
                                    optionsScope.$apply(optionsScope.curr_functions = optionsScope.curr_functions.filter(el => el["keyword"] != "parent"));
                                }

                                if (fromClick) {
                                    //give no options to remove the hints since the user has selected an options
                                    // needed because it kept the option(s) in the suggestions
                                    var options = optionsScope.getOptions(cm, []);
                                    cm.showHint(options);
                                } else {
                                    if (gcvar.length > 1) {
                                        var options = optionsScope.getOptions(cm, optionsScope.available(textArea, "", object["returnName"] ? object["returnName"] : "").func.map(value => value["keyword"]));
                                        cm.showHint(options);
                                    } else {
                                        var options = optionsScope.getOptions(cm, varlist.concat(hierarchyVars).map(el => el["name"]));
                                        cm.showHint(options);
                                    }
                                }

                            }

                            if (!textBefore.match(/{[A-Za-z]*\.*[A-Za-z]*\(*[A-Za-z,]*\)*.*}*$/)) {
                                //$("#typing-help").show();
                                optionsScope.$apply(optionsScope.curr_functions = {});
                                optionsScope.$apply(optionsScope.curr_variables = {});
                                // optionsScope.$apply(optionsScope.currentReturnType = null);
                                $("#func-info").hide();
                                $(".func-list-item").css("font-weight", "normal");
                                $(".func-list-item").css("color", "#333");
                                $("#var-info").hide();
                                $(".var-list-item").css("font-weight", "normal");
                                $(".var-list-item").css("color", "#333");
                            }

                        }




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
                            optionsScope.part.events[eventType] = '{}';
                            $timeout(function () {
                                optionsScope.buildCodeMirrorBox("events." + eventType);
                            }, 50);

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
                            $timeout(function () {
                                optionsScope.buildCodeMirrorBox("variables." + dataKey);
                            }, 50);
                        };

                        optionsScope.saveEdit = function () {
                            var errorMsg = "Loop data, Visibility and Events must be between {} !";
                            var shouldSave = true;

                            let codeMirrors = document.getElementsByClassName('CodeMirror');
                            for (code of codeMirrors) {
                                const cm = $(code)[0].CodeMirror;
                                const value = cm.getValue();
                                textArea = cm.getTextArea().getAttribute("id");
                                if (textArea == 'loopData') {
                                    if (!(value.startsWith("{") && value.endsWith("}"))) {
                                        giveMessage(errorMsg);
                                        shouldSave = false;
                                    } else
                                        optionsScope.loopData = value;
                                }
                                else if (textArea == 'value')
                                    optionsScope.value = value;
                                else if (textArea.includes('events')) {
                                    let eventType = textArea.split(".")[1];
                                    if (!(value.startsWith("{") && value.endsWith("}"))) {
                                        giveMessage(errorMsg);
                                        shouldSave = false;
                                    } else
                                        optionsScope.part.events[eventType] = value;

                                } else if (textArea.includes('variables')) {
                                    let variable = textArea.split(".")[1];
                                    optionsScope.part.variables[variable] = value;
                                } else if (textArea == 'link') {
                                    optionsScope.link = value;
                                } else if (textArea == 'visibilityCondition') {
                                    if (!(value.startsWith("{") && value.endsWith("}"))) {
                                        giveMessage(errorMsg);
                                        shouldSave = false;
                                    } else
                                        optionsScope.visibilityCondition = value;
                                }
                            }
                            if (shouldSave) {
                                if (JSON.stringify(optionsScope.part) !== JSON.stringify(part)) {
                                    $timeout(function () {
                                        objSync(part, optionsScope.part);
                                        optionsScope.closeOverlay();
                                        if (options.closeFunc != undefined)
                                            options.closeFunc();
                                        giveMessage('Saved!');
                                    });
                                } else {
                                    optionsScope.closeOverlay();
                                    if (options.closeFunc != undefined)
                                        options.closeFunc();
                                    giveMessage('You have made no changes!');
                                }

                            }

                        };

                        $timeout(function () {
                            if (options.callbackFunc != undefined)
                                options.callbackFunc(edit_container.next(), execClose, optionsScope, watch);

                        }, 50);

                        $timeout(function () {
                            optionsScope.buildCodeMirrorBoxes();
                        }, 250);

                        $timeout(function () {
                            if (optionsScope.part.partType == "image" || optionsScope.part.partType == "text") {
                                $("#cb-link").on("change", function () {
                                    if ($("#cb-link").is(":checked")) {
                                        optionsScope.buildCodeMirrorBox("link");
                                    }
                                });
                            }
                        }, 150);

                    }
                        //     , function (cancel) {
                        //         console.log("close settings", optionsScope.part);
                        //         // if (JSON.stringify(optionsScope.part) !== JSON.stringify(part)) {
                        //         //     $timeout(function () {
                        //         //         objSync(part, optionsScope.part);

                        //         //         optionsScope.$destroy();
                        //         //         if (options.closeFunc != undefined)
                        //         //             options.closeFunc();
                        //         //     });
                        //         // } else {
                        //             optionsScope.$destroy();
                        //             if (options.closeFunc != undefined)
                        //                 options.closeFunc();
                        //         }


                        //     }
                    );
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
                var optionsScope = scope.$new();

                optionsScope.submitDelete = function () {
                    toolbarOptions.toolFunctions.remove(part);
                    optionsScope.closeOverlay();
                }
                $sbviews.openOverlay(function (modal, execClose) {
                    optionsScope.closeOverlay = function () {
                        execClose();
                    };
                    modal.parent().attr("id", "submit_delete_view");
                    // delete verification modal
                    modal.attr("class", "verification modal_content");
                    modal.append($('<button class="close_btn icon" value="#submit_delete_view" ng-click="closeOverlay()"</button>'));
                    modal.append($('<div class="warning">Are you sure you want to delete this view and all its aspects?</div>'));
                    modal.append($('<div class="confirmation_btns"><button class="cancel" value="#submit_delete_view" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="submitDelete()"> Delete</button></div>'));

                    $compile(modal)(optionsScope);

                }, function () {
                    optionsScope.$destroy();
                });
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
                            newPart.role = part.role;
                            newPart.parentId = part.parentId;
                            newPart.viewId = part.viewId;
                            if (part.isTemplateRef)
                                newPart.isTemplateRef = true;
                            if (part.id)
                                newPart.id = part.id;
                            // console.log(newPart);
                            toolbarOptions.toolFunctions.switch(part, newPart);
                            execClose();
                        }
                        else if (value.indexOf('temp:') === 0) {
                            var value = templateList.val();
                            var id = value.substr(5);
                            //TODO verify this later (role)
                            //templates[id].role = $rootScope.role;
                            $smartboards.request('views', 'getTemplateContent', { id: id }, function (data, err) {
                                if (err) {
                                    giveMessage(err.description);
                                    return;
                                }

                                for (let aspect of data.template) {
                                    newPart = aspect;
                                    newPart.id = aspect.id;
                                    toolbarOptions.toolFunctions.switch(part, newPart);
                                }

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
                            parts: [templatePart],
                            course: $rootScope.course
                        };



                        optionsScope.saveTemplate = function () {
                            //optionsScope.template.part.role = $rootScope.role;
                            var isbyRef = $('#isRef').is(':checked');
                            var templateIndex = optionsScope.editData.templates.length;
                            if (isbyRef) {
                                var templateIndex = optionsScope.editData.templates.length;
                                optionsScope.template.roleType = $rootScope.roleType; // "ROLE_SINGLE";
                                optionsScope.template.role = part.role; //"Role.Default";
                                optionsScope.template.isRef = true;
                                optionsScope.template.viewId = part.viewId;
                                $smartboards.request('views', 'saveTemplate', optionsScope.template, function (data, err) {
                                    if (err) {
                                        giveMessage(err.description);
                                        return;
                                    }
                                    execClose();
                                    giveMessage('Template saved as reference!');
                                    optionsScope.template.id = data.templateId;
                                    optionsScope.template.viewId = data.idView;
                                    optionsScope.editData.templates[templateIndex] = optionsScope.template;

                                    $smartboards.request('views', 'getTemplateContent', optionsScope.editData.templates[templateIndex], function (data, err) {
                                        if (err) {
                                            giveMessage(err.description);
                                            return;
                                        }
                                        for (let aspect of data.template) {
                                            newPart = aspect;
                                            delete newPart.id;
                                            newPart.isTemplateRef = true;
                                            //newPart.id = optionsScope.template.id;
                                            // console.log(newPart);
                                            toolbarOptions.toolFunctions.switch(part, newPart);
                                        }

                                        execClose();
                                    });
                                });
                            }
                            else {
                                var templatePartAsp = $sbviews.getAllPartAspects(part.viewId);
                                optionsScope.template.parts = templatePartAsp;
                                optionsScope.template.isRef = false;
                                $smartboards.request('views', 'saveTemplate', optionsScope.template, function (data, err) {
                                    if (err) {
                                        giveMessage(err.description);
                                        return;
                                    }
                                    execClose();
                                    giveMessage('Template saved!');
                                    optionsScope.template.id = data.templateId;
                                    optionsScope.template.viewId = data.idView;
                                    optionsScope.editData.templates[templateIndex] = optionsScope.template;
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
                    if ($rootScope.roleType == "ROLE_SINGLE")
                        return $rootScope.courseRoles.filter(elem => !viewRoles.some(role => role.name == elem.name));
                    else
                        return [$rootScope.courseRoles, $rootScope.courseRoles];
                }
                //SINGLE - VIEWER_ROLES[] ; INTERACTION - [USER_ROLES, VIEWER_ROLES]
                var viewRoles = $sbviews.findRolesOfView(part.viewId);
                var rolesWithoutAspect = getAvailableRoles();


                optionsScope.isReadyToSubmit = function () {
                    isValid = function (text) {
                        return (text != "" && text != undefined && text != null)
                    }

                    if (isValid($("#viewer").val()) && isValid($("#aspect_selection").val())) {
                        if ($rootScope.roleType == "ROLE_SINGLE")
                            return true;
                        //if ROLE_INTERACTION
                        else if (isValid($("#user").val()))
                            return true;
                        else
                            return false;
                    } else
                        return false;
                }

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
                            const el = $('.highlight');
                            const role = unparseRole(el[0].getAttribute('data-role'));
                            const part = $sbviews.findPart(el[0].getAttribute('data-viewId'), getViewerFromRole(role), $rootScope.partsHierarchy);
                            // const viewId = el[0].getAttribute('data-viewId');
                            // const role = el[0].getAttribute('data-role');
                            // const parentContent = el[0].parentElement;
                            // //el.parent => parentContent.viewId
                            // const partParent = $sbviews.findPart(el.parent, "role." + role, $rootScope.partsHierarchy);
                            // var idx = partParent.children.indexOf(el);
                            // partParent.children.splice(idx, 1);
                            // $sbviews.destroy(el);
                            // if ($(parentContent).children().length == 0)
                            //     parentContent.append($(document.createElement('div')).text('(No Children)').addClass('red no-children'));
                            //$sbviews.notifyChanged(part, options);
                            //$sbviews.findViewToShow(viewId);
                            $sbviews.manageViewAndSubviews(part, scope.$parent, $(el[0].parentElement), childOptions, 'remove');
                        },
                        duplicate: function (obj) {
                            const el = $('.highlight');
                            const role = unparseRole(el[0].getAttribute('data-role'));
                            const part = $sbviews.findPart(el[0].getAttribute('data-viewId'), getViewerFromRole(role), $rootScope.partsHierarchy);
                            // const role = el[0].getAttribute('data-role');
                            // const partParent = $sbviews.findPart(el.parent, role, $rootScope.partsHierarchy);
                            // var idx = partParent.children.indexOf(el);
                            // var newPart = $.extend(true, {}, el);
                            // const viewIdsArray = Array.from(document.querySelectorAll('[data-viewid]')).map(x => parseInt(x.getAttribute('data-viewid'))).sort();
                            // newPart.viewId = (viewIdsArray[viewIdsArray.length - 1] + 1).toString();
                            // //$sbviews.changePids(newPart);
                            // deleteIds(newPart);
                            // delete newPart.viewIndex;
                            // partParent.children.splice(idx, 0, newPart);
                            // var newPartEl = $sbviews.build(scope, 'partParent.children[' + idx + ']', childOptions);
                            // $(el)[0].before(newPartEl);
                            $sbviews.manageViewAndSubviews(part, scope.$parent, $(el[0].parentElement), childOptions, 'duplicate');
                            //$sbviews.notifyChanged(part, options);
                        },
                        switch: function (obj, newPart) {
                            const el = $('.highlight');
                            const role = unparseRole(el[0].getAttribute('data-role'));
                            const part = $sbviews.findPart(el[0].getAttribute('data-viewId'), getViewerFromRole(role), $rootScope.partsHierarchy);
                            // const role = el[0].getAttribute('data-role');
                            // const partParent = $sbviews.findPart(el.parent, "role." + role, $rootScope.partsHierarchy);
                            // var idx = partParent.children.indexOf(el);
                            // partParent.children.splice(idx, 1, newPart);
                            // var newPartEl = $sbviews.build(scope, 'partParent.children[' + idx + ']', childOptions);
                            // var oldEl = el;
                            // oldEl.replaceWith(newPartEl);
                            // $sbviews.destroy(oldEl);
                            $sbviews.manageViewAndSubviews(part, scope.$parent, $(el[0].parentElement), childOptions, 'switch', newPart);
                            //$sbviews.notifyChanged(part, options);
                        }
                    }
                });

                function addPart() {
                    var new_role_viewer = $("#viewer").find(":selected")[0].text;

                    if ($rootScope.roleType == "ROLE_INTERACTION") {
                        var new_role_user = $("#user").find(":selected")[0].text;
                        var new_role = new_role_user + '>' + new_role_viewer;
                    }

                    var new_aspect = $rootScope.create_aspect;

                    var el = $('.highlight');
                    const role = unparseRole(el[0].getAttribute('data-role'));
                    var parentContent = el[0].parentElement;
                    var partParent = $sbviews.findPart(part.parentId, getViewerFromRole(role), $rootScope.partsHierarchy, true);
                    // console.log(partParent);

                    // default part
                    if (new_aspect == "new_aspect") {
                        var newAspect = [];

                        newAspect = $sbviews.defaultPart([part.partType]);
                        newAspect.viewId = part.viewId;
                        newAspect.role = $rootScope.roleType == "ROLE_INTERACTION" ? unparseRole(new_role) : unparseRole(new_role_viewer);
                        newAspect.parentId = part.parentId;
                        //newAspect.aspectClass = part.aspectClass;
                        var newChild;
                        //root view
                        if ($rootScope.partsHierarchy.some(asp => asp.viewId == part.viewId)) {
                            var newOptions = $sbviews.editOptions(childOptions, {
                                toolOptions: {
                                    canSwitch: true,
                                    canHaveAspects: true
                                },
                                toolFunctions: {
                                    switch: function (obj, newPart) {
                                        $sbviews.manageViewAndSubviews(obj, null, document.getElementById("viewEditor"), newOptions, 'switch', newPart);
                                    }
                                }
                            });
                            var viewScope = $rootScope.$new(true);
                            viewScope.view = newAspect;
                            $rootScope.partsHierarchy.push(newAspect);
                            newChild = $sbviews.buildElement(viewScope, newAspect, newOptions);
                            newChild.addClass('view editing');
                        } else {
                            partParent.children.splice(partParent.children.indexOf(part) + 1, 0, newAspect);
                            var newChild = $sbviews.buildElement(scope.$parent, newAspect, childOptions);
                        }



                        newChild.addClass("diff_aspect");
                        if (newChild.hasClass('aspect_hide'))
                            newChild.removeClass('aspect_hide');

                        el.addClass("aspect_hide");
                        //$sbviews.notifyChanged(part, options);
                        //insert after
                        parentContent.insertBefore(newChild.get(0), el[0].nextSibling);
                        newChild.click();

                        // } else if ($rootScope.roleType == "ROLE_INTERACTION") {

                        // }
                    }
                    //copy
                    else {
                        // if ($rootScope.roleType == "ROLE_SINGLE") {

                        var newAspect = Object.assign({}, part);
                        delete newAspect.id;
                        newAspect.role = $rootScope.roleType == "ROLE_INTERACTION" ? unparseRole(new_role) : unparseRole(new_role_viewer);
                        //newAspect.aspectClass = part.aspectClass;

                        if ($rootScope.partsHierarchy.some(asp => asp.viewId == part.viewId)) {
                            var newOptions = $sbviews.editOptions(childOptions, {
                                toolOptions: {
                                    canSwitch: true,
                                    canHaveAspects: true
                                },
                                toolFunctions: {
                                    switch: function (obj, newPart) {
                                        $sbviews.manageViewAndSubviews(obj, null, document.getElementById("viewEditor"), newOptions, 'switch', newPart);
                                    }
                                }
                            });
                            newAspect.parentId = null;
                            var viewScope = $rootScope.$new(true);
                            viewScope.view = newAspect;

                            $rootScope.partsHierarchy.push(newAspect);
                            newChild = $sbviews.buildElement(viewScope, newAspect, newOptions);
                            newChild.addClass('view editing');
                        } else {
                            partParent.children.splice(partParent.children.indexOf(part) + 1, 0, newAspect);
                            var newChild = $sbviews.buildElement(scope.$parent, newAspect, childOptions);
                        }
                        newChild.addClass("diff_aspect");
                        if (newChild.hasClass('aspect_hide'))
                            newChild.removeClass('aspect_hide');

                        el.addClass("aspect_hide");
                        //$sbviews.notifyChanged(part, options);
                        parentContent.insertBefore(newChild.get(0), el[0].nextSibling);
                        newChild.click();
                        // }
                    }
                }

                optionsScope.updateAddAspectSection = function (viewerRoles, userRoles = null) {

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
                    aspect_selection.append($('<option value="copy_aspect">Use this aspect as basis</option>'));

                    add_asp.append(aspect_selection);

                    add_asp.append($('<button ng-click="addAspect()" disabled id="create">Create</button>'));
                    add_asp.append($('<div class="delete_icon icon" ng-click="removeAddForm()"></div>'));

                    add_asp.insertBefore("#aspect");

                    $compile(aspect_box)(optionsScope);
                }

                optionsScope.changeCreateButton = function () {
                    $("#create").attr("disabled", !optionsScope.isReadyToSubmit());
                }

                optionsScope.removeAddForm = function () {
                    $("#add_asp").remove();
                };

                optionsScope.addAspect = function () {
                    var new_viewer = $("#viewer").find(":selected")[0];
                    $rootScope.create_aspect = $("#aspect_selection").find(":selected")[0].value;

                    if ($rootScope.roleType == "ROLE_SINGLE") {
                        //update select elements
                        viewRoles.push({ "id": new_viewer.value, "name": new_viewer.text });
                        //select ???
                        //$("#edit_viewer_select").append($('<option selected value="' + new_viewer.value + '">' + new_viewer.text + '</option>'));
                        if ($("#viewer_role option").filter((idx, option) => {
                            return option.text == new_viewer.text
                        }).length == 0) {
                            // document.getElementById("viewer_role").append(new Option(new_viewer.text, new_viewer.value));
                            $rootScope.viewRoles.push({ "id": new_viewer.value, "name": new_viewer.text });
                        }
                    } else {
                        var new_user = $("#user").find(":selected")[0];

                        const viewId = $('.highlight')[0].getAttribute('data-viewid');
                        const existingAspects = $sbviews.findAspectCombinationsOfView(viewId)[new_user.text];
                        if (existingAspects.includes(new_viewer.text)) {
                            $("#warning-roles").show();
                            return;
                        }


                        viewRoles[0].push({ "id": new_user.value, "name": new_user.text });
                        viewRoles[1].push({ "id": new_viewer.value, "name": new_viewer.text });

                        if ($("#user_role option").filter((idx, option) => {
                            return option.text == new_user.text
                        }).length == 0) {
                            $rootScope.viewRoles[0].push({ "id": new_user.value, "name": new_user.text });
                            document.getElementById("user_role").append($('<option value="' + new_user.value + '">' + new_user.text + '</option>'));
                        }

                        if ($("#viewer_role option").filter((idx, option) => {
                            return option.text == new_viewer.text
                        }).length == 0) {
                            $rootScope.viewRoles[1].push({ "id": new_viewer.value, "name": new_viewer.text });
                            //   document.getElementById("viewer_role").append($('<option value="' + new_viewer.value + '">' + new_viewer.text + '</option>'));
                        }
                    }
                    rolesWithoutAspect = getAvailableRoles();

                    //add part
                    addPart();

                    //remove form
                    optionsScope.closeOverlay();

                };

                optionsScope.isAddAspectEnabled = function () {
                    if ($rootScope.roleType == "ROLE_SINGLE" && rolesWithoutAspect.length == 0)
                        return false;
                    else if ($rootScope.roleType == "ROLE_INTERACTION" && rolesWithoutAspect[0].length == 0 && rolesWithoutAspect[1].length == 0)
                        return false;
                    else if ($("#add_asp").is(':visible'))
                        return false;
                    else
                        return true;

                };

                optionsScope.showAspectSection = function () {
                    if ($rootScope.roleType == "ROLE_SINGLE")
                        optionsScope.updateAddAspectSection(rolesWithoutAspect);
                    else
                        optionsScope.updateAddAspectSection(rolesWithoutAspect[1], rolesWithoutAspect[0]);
                    $('#viewer, #user, #aspect_selection').on('change', optionsScope.changeCreateButton);
                };

                optionsScope.deleteAspect = function () {
                    var viewer = $("#edit_viewer_select").find(":selected")[0].text;
                    var user = null;
                    if ($rootScope.roleType == "ROLE_INTERACTION")
                        user = $("#edit_user_selection").find(":selected")[0].text;
                    optionsScope.aspect = { viewer: viewer, user: user ? user : false }
                    $("#delete-aspect").show();

                    optionsScope.submitDelete = function () {
                        optionsScope.removeAspect();
                    }
                };

                optionsScope.removeAspect = function () {
                    const el = $('.highlight');
                    const viewId = el[0].getAttribute('data-viewId');
                    const roleViewer = $("#edit_viewer_select").find(":selected")[0];
                    var role = roleViewer.text;

                    const parentContent = el[0].parentElement;

                    const part = $sbviews.findPart(viewId, unparseRole(role), $rootScope.partsHierarchy);
                    if ($rootScope.partsHierarchy.indexOf(part) == -1) { // if it is not one 'main view'
                        const partParent = $sbviews.findPart(part.parentId, unparseRole(role), $rootScope.partsHierarchy, true);
                        var idx = partParent.children.indexOf(part);
                        partParent.children.splice(idx, 1);
                    } else {
                        var idx = $rootScope.partsHierarchy.indexOf(part);
                        $rootScope.partsHierarchy.splice(idx, 1);
                    }


                    const elToRemove = $($("[data-viewid=" + viewId + "][data-role=" + role + "]").toArray()[0]);
                    $sbviews.destroy(elToRemove);
                    if ($(parentContent).children().length == 0)
                        parentContent.append($(document.createElement('div')).text('(No Children)').addClass('red no-children'));

                    if ($rootScope.roleType == 'ROLE_INTERACTION') {
                        [userRoles, viewerRoles] = $sbviews.findRolesOfHierarchy($rootScope.roleType, $rootScope.partsHierarchy);
                    } else {
                        viewerRoles = $sbviews.findRolesOfHierarchy($rootScope.roleType, $rootScope.partsHierarchy);
                    }



                    if ($rootScope.roleType == "ROLE_INTERACTION") {
                        if (!viewerRoles.map(el => el.name).includes(roleViewer.text)) {
                            viewRoles[1] = viewRoles[1].filter(role => role.name != roleViewer.text);
                            const requiredIndexV = $rootScope.viewRoles[1].findIndex(el => {
                                return el.name === roleViewer.text;
                            });
                            $rootScope.viewRoles[1].splice(requiredIndexV, 1);
                            $("#edit_viewer_select option:contains(" + roleViewer.text + ")").remove();
                        }
                        const roleUser = $("#edit_user_selection").find(":selected")[0];
                        role = roleUser.text + '>' + roleViewer.text;

                        if (!userRoles.map(el => el.name).includes(roleUser.text)) {
                            //$("#edit_user_select option:contains(" + roleUser.text + ")").remove();
                            viewRoles[0] = viewRoles[0].filter(role => role.name != roleUser.text);

                            const requiredIndexU = $rootScope.viewRoles[0].findIndex(el => {
                                return el.name === roleUser.text;
                            });
                            $rootScope.viewRoles[0].splice(requiredIndexU, 1);

                            //not sure 
                            $("#edit_user_select option:contains(" + roleUser.text + ")").remove();
                        }
                        // set to Default ??
                        $("#user_role option[value='0']").attr('selected', 'selected');

                    } else {
                        if (!viewerRoles.map(el => el.name).includes(roleViewer.text)) {
                            viewRoles = viewRoles.filter(role => role.name != roleViewer.text);
                            const requiredIndex = $rootScope.viewRoles.findIndex(el => {
                                return el.name === roleViewer.text;
                            });
                            $rootScope.viewRoles.splice(requiredIndex, 1);
                            $("#edit_viewer_select option:contains(" + roleViewer.text + ")").remove();
                        }
                    }


                    const roles = $sbviews.buildRolesHierarchyForOneRole(roleViewer.text);
                    const newRole = roles[1];
                    $("#viewer_role option[value=" + newRole.id + "]").attr('selected', 'selected');
                    $("#viewer_role").trigger("onchange");


                    $("#delete-aspect").hide();
                    optionsScope.closeOverlay();
                };

                optionsScope.isRemoveDisabled = function () {
                    return $rootScope.roleType == 'ROLE_SINGLE' ? $('#edit_viewer_select option').length == 1 : $('#edit_viewer_select option').length == 1 && $('#edit_user_select option').length == 1;
                };

                $sbviews.openOverlay(function (modal, execClose) {
                    optionsScope.closeOverlay = function () {
                        execClose();
                    };

                    const viewRoles = $sbviews.findRolesOfView(part.viewId);
                    modal.parent().attr("id", "aspects_modal");
                    modal.append($('<button class="close_btn icon" value="#aspects_modal" ng-click="closeOverlay()"</button>'));
                    modal.append($('<div class="title">Edit aspect for this view: </div>'));
                    modalContent = $("<div class='content'></div>");

                    edit_roles_selection = $('<div id="edit_roles_selection"></div>');
                    edit_roles_selection.append($('<div class="select_label">Viewer: </div>'));
                    edit_viewer_selector = $('<select class="form__input roles_aspect" id="edit_viewer_select" class="form__input"></select>');
                    if ($rootScope.roleType == 'ROLE_SINGLE') {
                        $.each(viewRoles, function (i, item) {
                            if (parseRole(part.role) == item.name) {
                                edit_viewer_selector.append($('<option selected value="' + item.id + '">' + item.name + '</option>'));

                            } else {
                                edit_viewer_selector.append($('<option value="' + item.id + '">' + item.name + '</option>'));
                            }
                        });
                        edit_roles_selection.append(edit_viewer_selector);
                    } else {
                        $.each(viewRoles[1], function (i, item) {
                            if (parseRole(part.role.split('>')[0]) == item.name) {
                                edit_viewer_selector.append($('<option selected value="' + item.id + '">' + item.name + '</option>'));

                            } else {
                                edit_viewer_selector.append($('<option value="' + item.id + '">' + item.name + '</option>'));
                            }
                        });
                        edit_roles_selection.append(edit_viewer_selector);
                        //edit_user_selection = $('<div id="edit_viewer_selection"></div>');
                        edit_roles_selection.append($('<div class="select_label">User: </div>'));
                        edit_user_selector = $('<select class="form__input roles_aspect" id="edit_user_select" class="form__input"></select>');
                        $.each(viewRoles[0], function (i, item) {
                            if (parseRole(part.role.split('>')[1]) == item.name) {
                                edit_user_selector.append($('<option selected value="' + item.id + '">' + item.name + '</option>'));

                            } else {
                                edit_user_selector.append($('<option value="' + item.id + '">' + item.name + '</option>'));
                            }
                        });
                        edit_roles_selection.append(edit_user_selector);
                    }
                    edit_roles_selection.append($('<button class="delete_icon icon" title="Delete Aspect" ng-disabled="isRemoveDisabled()" ng-click="deleteAspect()"></button>'));


                    modalContent.append(edit_roles_selection);

                    //add aspect button
                    modalContent.append($('<div class="half" id="aspect"><button class="btn" ng-click="showAspectSection()" ng-disabled="!isAddAspectEnabled()"><img class="icon" src="./images/add_icon.svg"/><span>Add Aspect</span></button></div>'));

                    // delete verification modal
                    delete_modal = $("<div class='modal' id='delete-aspect'></div>");
                    verification = $("<div class='verification modal_content'></div>");
                    verification.append($('<button class="close_btn icon" value="#delete-aspect" onclick="closeModal(this)"></button>'));
                    verification.append($('<div class="warning">Are you sure you want to delete this Aspect?</div>'));
                    verification.append($('<div ng-if="aspect.user" class="target">Aspect for viewer: {{aspect.viewer}} and user: {{aspect.user}}</div>'));
                    verification.append($('<div ng-if="!aspect.user" class="target">Aspect for viewer: {{aspect.viewer}}</div>'));
                    verification.append($('<div class="confirmation_btns"><button class="cancel" value="#delete-aspect" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="submitDelete()"> Delete</button></div>'))
                    delete_modal.append(verification);
                    modalContent.append(delete_modal);

                    // warning wrong combination of roles modal
                    warning_modal = $("<div class='modal' id='warning-roles'></div>");
                    w_verification = $("<div class='verification modal_content'></div>");
                    w_verification.append($('<button class="close_btn icon" value="#warning-roles" onclick="closeModal(this)"></button>'));
                    w_verification.append($('<div class="warning">This view already has an aspect for this combination of roles.</div>'));
                    w_verification.append($('<div class="confirmation_btns"><button class="btn" value="#warning-roles" onclick="closeModal(this)">OK</button></div>'))
                    warning_modal.append(w_verification);
                    modalContent.append(warning_modal);


                    saveButton = $(document.createElement('button')).text('Save');
                    //saveButton.prop('disabled', true);
                    saveButton.addClass("save_btn");
                    saveButton.click(function () {
                        const viewer = $("#edit_viewer_select").find(":selected")[0].text;
                        const globalViewer = $("#viewer_role").find(":selected")[0].text;
                        var role = viewer;
                        var globalRole = globalViewer;
                        if ($rootScope.roleType == 'ROLE_INTERACTION') {
                            const user = $("#edit_user_select").find(":selected")[0].text;
                            const globalUser = $("#user_role").find(":selected")[0].text;
                            role = user + '>' + viewer;
                            globalRole = globalUser + '>' + globalViewer;
                        }
                        //if (viewer != globalViewer) {
                        var highlighted = $(".highlight")[0];
                        $sbviews.findViewToChange(highlighted, role, globalRole);
                        //}
                        execClose();
                    });
                    modalContent.append(saveButton);
                    modal.append(modalContent);
                    $compile(modal)(optionsScope);


                }, function () {
                    optionsScope.$destroy();
                });
            }));
            toolbar.append('<span id="editing_role">View Aspect: ' + parseRole(part.role) + '</span>');

        }

        var nTools = toolbar.children().length;
        toolbar.css('min-width', nTools * 15 + 1);

        return toolbar;
    };

    this.getAllPartAspects = function (viewId) {
        var aspects = $("[data-viewid=" + viewId + "]").toArray();
        var aspectsParts = [];
        for (let asp of aspects) {
            let part = this.findPart(viewId, getViewerFromRole(asp.getAttribute('data-role')), $rootScope.partsHierarchy);
            part.parentId = null;
            //delete part.viewId;
            aspectsParts.push(part);
        }
        return aspectsParts;
    }

    //role argument is the viewer role; viewId = id if parent, viewId = viewId if we looking for that part
    this.findPart = function (viewId, role, viewAspects, isParent = false) {
        if (Array.isArray(viewAspects) && viewAspects.length == 1) {
            //if (viewAspects[0].parentId == null) {
            if (isParent && viewAspects[0].id == viewId) {
                return viewAspects[0];
            }
            else if (!isParent && viewAspects[0].viewId == viewId) {
                return viewAspects[0];
            }
            //only one aspect with children
            if (viewAspects[0].children && viewAspects[0].children.length != 0) {
                const viewIdsArray = [...new Set(viewAspects[0].children.map(x => x.viewId))];
                //console.log(viewIdsArray);
                //console.log(aspect.children);
                const aspectChildren = viewIdsArray.map((key) => {
                    return viewAspects[0].children.reduce(function (result, el) {
                        if (el.viewId == key)
                            result.push(el);
                        return result;
                    }, []);
                });
                //console.log(aspectChildren);

                for (let aspectsOfChild of aspectChildren) {
                    // if (aspectsOfChild[0].viewId == viewId)
                    aspect = this.findPart(viewId, role, aspectsOfChild, isParent);
                    if (aspect !== false) {
                        return aspect;
                    }
                };

            }
        } else if (Array.isArray(viewAspects) && viewAspects.length != 1) {
            for (let aspect of viewAspects) {
                if ((isParent && aspect.id == viewId) || (!isParent && aspect.viewId == viewId)) {
                    if ($rootScope.roleType == 'ROLE_SINGLE') {
                        //main view
                        if (aspect.role == role) {
                            return aspect;
                        }
                        // else if (!isParent && aspect.viewId == viewId && aspect.role == role) {
                        //     console.log("entra aqui");
                        //     return aspect;
                        // }
                    } else {
                        const viewer = aspect.role.split('>')[1];
                        const user = aspect.role.split('>')[0];
                        const globalUser = $("#user_role").find(":selected")[0].text;
                        if (viewer == role && globalUser == user) {
                            return aspect;
                        }
                        // else if (!isParent && aspect.viewId == viewId && viewer == role && globalUser == user) {
                        //     return aspect;
                        // }
                    }
                }

                else if (aspect.children && aspect.children.length != 0) {
                    const viewIdsArray = [...new Set(aspect.children.map(x => x.viewId))];
                    // console.log(viewIdsArray);
                    // console.log(aspect.children);
                    const aspectChildren = viewIdsArray.map((key) => {
                        return aspect.children.reduce(function (result, el) {
                            if (el.viewId == key)
                                result.push(el);
                            return result;
                        }, []);
                    });
                    //console.log(aspectChildren);
                    let found = false;
                    for (let aspectsOfChild of aspectChildren) {
                        // if (aspectsOfChild[0].viewId == viewId)
                        aspect = this.findPart(viewId, role, aspectsOfChild, isParent);
                        if (aspect !== false) {
                            found = true;
                            break;
                        }
                    };
                    if (found)
                        return aspect;
                } else {
                    //console.log(aspect);
                    const rolesForTargetRole = this.buildRolesHierarchyForOneRole(parseRole(role));
                    for (let roleObj of rolesForTargetRole) {
                        //console.log(view[0]);
                        //const view = this.findPart(viewId, role, viewChild);

                        if ($rootScope.roleType == 'ROLE_SINGLE') {
                            //main view
                            if (aspect.role == unparseRole(roleObj.name))
                                return aspect;
                        } else {
                            const viewer = aspect.role.split('>')[1];
                            const user = aspect.role.split('>')[0];
                            const globalUser = $("#user_role").find(":selected")[0].text;
                            if (viewer == unparseRole(roleObj.name) && globalUser == user) {
                                return aspect;
                            }
                        }
                    }
                }
            }
        } else {
            // console.log("aqui");
            if (isParent && viewAspects.id == viewId) {
                return viewAspects;
            }
            else if (!isParent && viewAspects.viewId == viewId) {
                return viewAspects;
            }

        }
        return false;


    };

    //finds the child that we should show
    this.findViewToShow = function (childViewId, roleViewer) {
        var currentGlobalViewerRole = $("#viewer_role").find(":selected")[0].text;
        var otherViews = $("[data-viewid=" + childViewId + "]").toArray();
        //find the right view to show for the globally selected role
        this.findViewsForRole(otherViews, currentGlobalViewerRole);
        const rolesToCheck = roleViewer != currentGlobalViewerRole ? [roleViewer, currentGlobalViewerRole] : [roleViewer];

        rolesToCheck.forEach(role => {
            if (($("[data-role=" + role + "]").toArray().length == 0 || $("[data-role*='>" + role + "']").toArray().length == 0) && role != "Default") {
                const roles = this.buildRolesHierarchyForOneRole(role);
                //console.log(roles);
                const newRole = roles[1]; // if it was 0, it would be the role itself. we want the next one
                const newOption = $("#viewer_role option").filter((idx, option) => {
                    return option.label == newRole.name
                });
                document.getElementById('viewer_role').value = newOption[0].value;
                // $("#viewer_role option:contains(" + role + ")").remove();
            }
        })

    };

    //finds the child that we want to show with "diff_aspect"
    this.findViewToChange = function (elementToHide, roleToEdit, globalRole) {
        $(elementToHide).addClass('aspect_hide');
        $(elementToHide).removeClass('diff_aspect');
        $(elementToHide).click();
        const views = $("[data-viewid=" + elementToHide.getAttribute('data-viewid') + "]").toArray();
        const elementToShow = views.filter((el) => {
            return el.getAttribute('data-role') == roleToEdit;
        });
        if (roleToEdit != globalRole)
            $(elementToShow).addClass('diff_aspect');
        $(elementToShow).removeClass('aspect_hide');

    };

    //returns the roles that a (sub)view has
    this.findRolesOfView = function (viewId) {
        const views = $("[data-viewid=" + viewId + "]").toArray();
        if ($rootScope.roleType == 'ROLE_SINGLE') {
            const roles = views.map(x => x.getAttribute('data-role'));
            // console.log(roles);
            // console.log($rootScope.viewRoles);
            const result = $rootScope.viewRoles.filter((el) => {
                return roles.includes(el.name);
            });
            return result;
        } else {
            const roles = views.map(x => x.getAttribute('data-role'));
            const userRoles = roles.map(x => x.split(">")[0]);
            const viewerRoles = roles.map(x => x.split(">")[1]);
            const resultUser = $rootScope.viewRoles[0].filter((el) => {
                return userRoles.includes(el.name);
            });
            const resultViewer = $rootScope.viewRoles[1].filter((el) => {
                return viewerRoles.includes(el.name);
            });
            return [resultUser, resultViewer];
        }

    };

    //returns the roles that a hierarchy has
    this.findRolesOfHierarchy = function (roleType, viewAspects, viewerRoles = [], userRoles = []) {
        if (roleType == 'ROLE_SINGLE') {
            for (v of viewAspects) {
                if (v.children && v.children.length != 0) {
                    newRoles = this.findRolesOfHierarchy(roleType, v.children, viewerRoles);
                    viewerRoles = viewerRoles.concat(newRoles);
                }
                roles = this.findRolesOfView(v.viewId);
                viewerRoles = viewerRoles.concat(roles);

                //return viewerRoles;
            }
            return viewerRoles.filter((el, i) => viewerRoles.indexOf(el) === i);
        } else {

            for (v of viewAspects) {
                if (v.children && v.children.length != 0) {
                    [newURoles, newVRoles] = this.findRolesOfHierarchy(roleType, v.children, viewerRoles, userRoles);
                    viewerRoles = viewerRoles.concat(newVRoles);
                    userRoles = userRoles.concat(newURoles);
                }
                [uRoles, vRoles] = this.findRolesOfView(v.viewId);
                viewerRoles = viewerRoles.concat(vRoles);
                userRoles = userRoles.concat(uRoles);

            }
            return [userRoles.filter((el, i) => userRoles.indexOf(el) === i), viewerRoles.filter((el, i) => viewerRoles.indexOf(el) === i)];
        }
    };

    //finds the right views to show for the targetRole
    this.findViewsForRole = function (viewAspects, targetRole, targetUser = null) {
        $(".highlight").click(); // removes the highligh and the toolbar that can be seen as a child
        //if (roletype == ROLE_SINGLE)
        //console.log(viewAspects);

        if (Array.from(viewAspects).some(el => $(el).hasClass('content')) || viewAspects.length != 1) {
            const components = Array.from(viewAspects);
            for (component of components) {
                const children = Array.from(component.children);
                const viewIdsArray = [...new Set(children.map(x => x.getAttribute('data-viewid')))];
                //console.log(viewIdsArray);
                const aspectChildren = viewIdsArray.map((key) => {
                    return children.reduce(function (result, el) {
                        if (el.getAttribute('data-viewid') == key)
                            result.push(el);
                        return result;
                    }, []);
                });

                // console.log(aspectChildren);
                aspectChildren.forEach(aspects => {
                    this.findViewsForRole(aspects, targetRole, targetUser);
                });
            }

        }

        //if there is only one aspect available
        else if (viewAspects.length == 1 && !$(viewAspects[0]).hasClass('header')) {
            if (($rootScope.roleType == 'ROLE_SINGLE' && viewAspects[0].getAttribute('data-role') == targetRole) ||
                ($rootScope.roleType == 'ROLE_INTERACTION' && getViewerFromRole(viewAspects[0].getAttribute('data-role'), true) == targetRole && getUserFromRole(viewAspects[0].getAttribute('data-role'), true) == targetUser)) {
                $(viewAspects[0]).removeClass('aspect_hide');
                if ($(viewAspects[0]).hasClass('diff_aspect')) {
                    $(viewAspects[0]).removeClass('diff_aspect');
                }
                // if ($(view).hasClass('highlight'))
                //     $(view).click();
            }
        }

        if (viewAspects.length == 1 && viewAspects[0].children && Array.from(viewAspects[0].children).length != 0 && !$(viewAspects[0]).hasClass('image')) {
            const children = Array.from(viewAspects[0].children);
            //in case is the content of a block (with or w/o header)
            if (children.some(el => $(el).hasClass('header'))) {
                $.each(children, (idx, el) => {
                    this.findViewsForRole([el], targetRole, targetUser);
                })
            } else if (children.some(el => $(el).hasClass('content'))) {
                this.findViewsForRole(viewAspects[0].children, targetRole, targetUser);
            } else {
                // console.log(children);
                const viewIdsArray = [...new Set(children.map(x => x.getAttribute('data-viewid')))];
                //console.log(viewIdsArray);
                const aspectChildren = viewIdsArray.map((key) => {
                    return children.reduce(function (result, el) {
                        if (el.getAttribute('data-viewid') == key)
                            result.push(el);
                        return result;
                    }, []);
                });

                // console.log(aspectChildren);
                aspectChildren.forEach(aspects => {
                    this.findViewsForRole(aspects, targetRole, targetUser);
                });
            }


        }
        else if (viewAspects.length == 1 && viewAspects[0].getAttribute('data-role') == "Default") {
            $(viewAspects[0]).removeClass('aspect_hide');
            if ($(viewAspects[0]).hasClass('diff_aspect')) {
                $(viewAspects[0]).removeClass('diff_aspect');
            }
        }

        else {
            const rolesForTargetRole = this.buildRolesHierarchyForOneRole(targetRole);
            // console.log(rolesForTargetRole);
            //search from the most specific role to the least one
            for (let role of rolesForTargetRole) {
                let otherViews;
                let view;
                if ($rootScope.roleType == 'ROLE_SINGLE') {
                    otherViews = viewAspects.filter(function (el) {
                        return el.getAttribute('data-role') != role.name;
                    });
                    view = viewAspects.filter(function (el) {
                        return el.getAttribute('data-role') == role.name;
                    });
                } else {
                    otherViews = viewAspects.filter(function (el) {
                        return getViewerFromRole(el.getAttribute('data-role'), true) != role.name || getUserFromRole(el.getAttribute('data-role'), true) != targetUser;
                    });
                    view = viewAspects.filter(function (el) {
                        return getViewerFromRole(el.getAttribute('data-role'), true) == role.name && getUserFromRole(el.getAttribute('data-role'), true) == targetUser;
                    });
                }

                // console.log(otherViews);
                // console.log(view);
                // if (otherViews.length == 1 && view.length == 0) {
                //     //when there is a view for other role (and not to default), but not this one
                //     //example: we are looking for a view for role=student, but we only find a view for teacher (??)
                //     $(otherViews).addClass('aspect_hide');
                // }
                if (otherViews.length == viewAspects.length) {
                    //when there is no view for this role, we have to look for the next specific role
                    continue
                }

                for (let v of otherViews) {
                    $(v).addClass('aspect_hide');
                }
                $(view).removeClass('aspect_hide');
                if ($(view).hasClass('diff_aspect')) {
                    $(view).removeClass('diff_aspect');
                }
                if ($(view).hasClass('highlight'))
                    $(view).click();
                break
            }
        }
    }

    this.findAspectCombinationsOfView = function (viewId) {
        var viewAspects = $("[data-viewid=" + viewId + "]").toArray();
        var aspects = {};
        viewAspects.forEach(aspect => {
            const userRole = aspect.getAttribute('data-role').split('>')[0];
            const viewerRole = aspect.getAttribute('data-role').split('>')[1];
            userRole in aspects ? aspects[userRole].push(viewerRole) : aspects[userRole] = [viewerRole];
        })
        return aspects;
    }

    this.getRolesOfView = function () {
        return $rootScope.viewRoles;
    }

    this.deleteIds = function (newPart) {
        delete newPart.id;
        for (var c in newPart.children) {
            this.deleteIds(newPart.children[c]);
        }
        for (var r in newPart.rows) {
            this.deleteIds(newPart.rows[r]);
        }
        for (var r in newPart.headerRows) {
            this.deleteIds(newPart.headerRows[r]);
        }
        for (var c in newPart.values) {
            this.deleteIds(newPart.values[c].value);
        }
    }

    this.manageViewAndSubviews = function (part, scope, blockContent, childOptions, operation, newPartSwitch = null) {

        //when we want to switch the root
        if ($rootScope.partsHierarchy.some(asp => asp.viewId == part.viewId) && operation == 'switch') {
            //var viewAspects = $("[data-viewid=" + part.viewId + "]").toArray();
            var viewAspect = $('.highlight');
            //for (let viewChild of viewAspects) {
            var aspectPart = this.findPart(part.viewId, getViewerFromRole(viewAspect[0].getAttribute('data-role')), $rootScope.partsHierarchy);
            var idx = $rootScope.partsHierarchy.indexOf(aspectPart);
            newPartSwitch.role = unparseRole(viewAspect[0].getAttribute('data-role'));
            newPartSwitch.parentId = null;
            var viewScope = $rootScope.$new(true);
            viewScope.view = newPartSwitch;

            $rootScope.partsHierarchy[idx] = newPartSwitch;
            //parentPart.children.splice(idx, 1, newPartSwitch);
            var newPartEl = this.build(viewScope, 'view', childOptions);
            newPartEl.addClass('view editing');
            var oldEl = viewAspect; //$('[data-role=' + viewAspect.getAttribute('data-role') + '][data-viewid=' + part.viewId + ']');
            oldEl.replaceWith(newPartEl);
            this.destroy(oldEl);
            //}

            return;
        }

        const viewIdsArray = Array.from(document.querySelectorAll('[data-viewid]')).map(x => parseInt(x.getAttribute('data-viewid'))).sort();
        const nextViewId = viewIdsArray[viewIdsArray.length - 1] + 1;

        var viewAspects = $("[data-viewid=" + part.viewId + "]").toArray();
        const parentPart = this.findPart(part.parentId, getViewerFromRole(part.role), $rootScope.partsHierarchy, true);
        // console.log(parentPart);
        // console.log(viewAspects);

        if (operation == 'switch') {
            //console.log(newPartSwitch);
            var viewAspect = $('.highlight')[0];

            if (newPartSwitch.isTemplateRef) {
                viewAspect = $("[data-viewid=" + part.viewId + "][data-role=" + part.role + "]");
                //return;
            }

            //for (let viewChild of viewAspects) {
            var aspectPart = this.findPart(part.viewId, getViewerFromRole(viewAspect.getAttribute('data-role')), $rootScope.partsHierarchy);
            var idx = parentPart.children.indexOf(aspectPart);
            newPartSwitch.role = aspectPart.role;
            newPartSwitch.parentId = aspectPart.parentId;
            parentPart.children.splice(idx, 1, newPartSwitch);
            var newPartEl = this.build(scope, 'part.children[' + idx + ']', childOptions);
            var oldEl = $(blockContent.children().get(idx));
            oldEl.replaceWith(newPartEl);
            this.destroy(oldEl);
            return;
        }

        for (let viewChild of viewAspects) {
            var aspectPart = this.findPart(part.viewId, getViewerFromRole(viewChild.getAttribute('data-role')), $rootScope.partsHierarchy);
            var idx = parentPart.children.indexOf(aspectPart);

            if (operation == 'duplicate') {
                var newPart = $.extend(true, {}, aspectPart);
                newPart.viewId = nextViewId;
                if (newPart.isTemplateRef)
                    delete newPart.isTemplateRef;
                this.deleteIds(newPart);
                delete newPart.viewIndex;
                for (let i = 0; i < newPart.children.length; i++) {
                    newPart.children[i].viewId = (parseInt(newPart.viewId) + i + 1).toString();
                    if (newPart.children[i].isTemplateRef)
                        delete newPart.children[i].isTemplateRef;
                }
                parentPart.children.splice(idx, 0, newPart);
                var newPartEl = this.build(scope, 'part.children[' + idx + ']', childOptions);
                $(blockContent.children().get(idx)).before(newPartEl);
            }
            else if (operation == 'remove') {
                parentPart.children.splice(idx, 1);
                this.destroy($(blockContent.children().get(idx)));
                if (blockContent.children().length == 0)
                    blockContent.append($(document.createElement('div')).text('(No Children)').addClass('red no-children'));
            }


        }

    }

    this.buildRolesHierarchyForOneRole = function (targetRole) {
        var hierarchyRoles = [];
        for (let role of $rootScope.rolesHierarchy) {
            this.setHierarchy(role, targetRole, hierarchyRoles)
        }
        if (!hierarchyRoles.includes($rootScope.rolesHierarchy[0]))
            // add Default
            hierarchyRoles.push($rootScope.rolesHierarchy[0]);
        return hierarchyRoles;
    };
    this.setHierarchy = function (role, targetRole, hierarchyRoles) {
        if (role.children) {
            for (let child of role.children) {
                this.setHierarchy(child, targetRole, hierarchyRoles);
                if (hierarchyRoles.includes(child))
                    hierarchyRoles.push(role);
            }
        }
        if (role.name == targetRole)
            hierarchyRoles.push(role);
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



        element.on('click', function (e) {

            //click only works on the closest div.
            e.stopPropagation();

            //second click closes the toolbar
            if (firstclickdone) {
                if (layoutEditing) {
                    return;
                }
                element.removeClass('highlight');
                $('#warning_ref').hide();
                // if (element.hasClass('diff_aspect')) {
                //     element.addClass('aspect_hide');
                //     $sbviews.findViewToShow(part.viewId);
                //     element.removeClass('diff_aspect');
                // }
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
                        allowId: true,
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
                    defaultOptions.overlayOptions.allowId = true;
                }
                else if (element.parent().parent().hasClass('block') || element.parent().parent().hasClass('view') || element.hasClass('block')) { // children of ui-block
                    defaultOptions.overlayOptions.allowDataLoop = true;
                    defaultOptions.overlayOptions.allowIf = true;
                    defaultOptions.overlayOptions.allowEvents = true;
                    defaultOptions.overlayOptions.allowVariables = true;
                    defaultOptions.overlayOptions.allowStyle = true;
                    defaultOptions.overlayOptions.allowClass = true;
                    defaultOptions.overlayOptions.allowId = true;
                }
                // if (element.hasClass('view')) {
                //     toolOptions.noSettings = true;
                // }

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
                if (part.isTemplateRef)
                    $('#warning_ref').show();

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
        if (part.variables === undefined || Array.isArray(part.variables) || part.variables === "[]" || part.variables === null)
            part.variables = {};
        if (part.events === undefined || Array.isArray(part.events) || part.events === "[]" || part.events === null)
            part.events = {};
        if (part.loopData === undefined || part.loopData === null)
            part.loopData = "{}";
        if (part.visibilityCondition === undefined || part.visibilityCondition === null)
            part.visibilityCondition = "{}";
        if (part.visibilityType === undefined || part.visibilityType === null)
            part.visibilityType = "conditional";
    };

});
