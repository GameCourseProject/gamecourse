angular.module('module.views').service('$sbviews', function($smartboards, $rootScope, $compile, $parse, $timeout) {
    var $sbviews = this;
    this.request = function (view, params, func) {
        $smartboards.request('views', 'view', $.extend({view: view}, params), function(data, err) {  
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

    this.requestEdit = function(view,pageOrTemp, params, func) {
        $smartboards.request('views', 'getEdit', $.extend({view: view, pageOrTemp:pageOrTemp}, params), function(data, err) {
            if (err) {
                func(undefined, err);
                return;
            }
            console.log("getEdit",data);
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
                pid: viewScope.view.id,
                origin: viewScope.view.origin,
                role: viewScope.view.role
            };

            function build() {
                var element = $sbviews.build(viewScope, 'viewBlock', { edit: true, editData: { fields: allFields, fieldsTree: data.fields, templates: data.templates }, view: viewScope.view });
                element.removeClass('block');
                element.css('padding-top', 18);
                element.addClass('view editing');
                return element;
            }

            var viewBlock = build();

            viewScope.$watch('viewBlock', function(newValue, oldValue) {
                if ((newValue != oldValue) && !changing) {
                    undoStack.push(oldValue);
                    redoStack.length = 0;
                }
                changing = false;
            }, true);

            var view = {
                scope: viewScope,
                element: viewBlock,
                get: function() {
                    viewScope.view.origin = viewScope.viewBlock.origin;
                    viewScope.view.pid = viewScope.viewBlock.pid;
                    //viewScope.view.content = viewScope.viewBlock.children;
                    return viewScope.view;
                },
                undo: function() {
                    if (undoStack.length > 0) {
                        changing = true;
                        redoStack.push(angular.copy(viewScope.viewBlock));
                        viewScope.viewBlock = undoStack.pop();

                        var newView = build();
                        viewBlock.replaceWith(newView);
                        view.element = viewBlock = newView;
                    }
                },
                redo: function() {
                    if (redoStack.length > 0) {
                        changing = true;
                        undoStack.push(angular.copy(viewScope.viewBlock));
                        viewScope.viewBlock = redoStack.pop();

                        var newView = build();
                        viewBlock.replaceWith(newView);
                        view.element = viewBlock = newView;
                    }
                },
                canUndo: function() {
                    return undoStack.length > 0;
                },
                canRedo: function() {
                    return redoStack.length > 0;
                }
            };
            func(view, undefined);
        });
    };

    this.registeredPartType = [];
    this.registerPartType = function(type, options) {
        this.registeredPartType[type] = options;
    };

    this.build = function(scope, what, options) {
        //console.log(what);
        //console.log(scope);
        var part = $parse(what)(scope);
        //console.log(part);
        return this.buildElement(scope, part, options);
    };

    this.buildElement = function(parentScope, part, options) {
        var options = $.extend({}, options);
        if (options.edit && part.partType=="image")
            part.edit=true;
        if (options.type != undefined)
            part.partType = options.partType;

        if (this.registeredPartType[part.partType] == undefined) {
            console.error('Unknown part type: ' + part.partType);
            return;
        }
        
        var partScope = parentScope.$new(true);
        partScope.part = part;
        if (part.partType!="templateRef")
            var element = this.registeredPartType[part.partType].build(partScope, part, options);
        //ToDo: build instance/templateref
        
        this.applyCommonFeatures(partScope, part, element, options);
        element.data('scope', partScope);
        return element;
    };

    this.buildStandalone = function(part, options) {
        return this.buildElement($rootScope, part, options);
    };

    this.destroy = function(element) {
        var scope = element.data('scope');
        
        if (scope == null || scope.part == null)
            return;
        this.registeredPartType[scope.part.partType].destroy(element, scope.part);
        scope.$destroy();
        element.remove();
    };

    this.applyCommonFeatures = function(scope, part, element, options) {
        if (!options.edit && part.events && !options.disableEvents) {
            var keys = Object.keys(part.events);
            for (var i = 0; i < keys.length; ++i) {
                var key = keys[i];
                var fn = $parse(part.events[key]);
                (function(key, fn) {
                    element.on(key, function(e) {
                        if(e.stopPropagation)
                            e.stopPropagation();
                        fn(scope);
                    });
                })(key, fn);
            }
        }

        if (options.edit)
            return;
        if (part.class != undefined)
            element.addClass(part.class);
        if (part.style != undefined)
            element.attr('style', part.style);

        if (part.directive != undefined) {
            element.attr(part.directive, '');
            $compile(element)(scope);
            element.removeAttr(part.directive);
        }
        
    };

    this.openOverlay = function(callbackFunc, closeFunc) {
        
        var overlay = $('<div class="settings-overlay">');
        var overlayCenter = $('<div class="settings-overlay-center"></div>');
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

        destroyListener = $rootScope.$on('$stateChangeStart', function() {
            execClose(true);
        });

        overlay.click(function(event) {
            if (event.target == this)
                execClose();
        });

        $('#wrapper').hide();
        if (callbackFunc != undefined)
            callbackFunc(overlayCenter, execClose);
        $(document.body).append(overlay);
    };

    this.createTool = function(title, img, click) {
        return $(document.createElement('img')).addClass('btn').attr('title', title).attr('src', img).on('click', function() {
            var thisArg = this;
            var args = arguments;
            $timeout(function() { click.apply(thisArg, args); });
        });
    };

    this.createToolbar = function(scope, part, toolbarOptions) {
        var toolbar = $(document.createElement('div')).addClass('edit-toolbar');
        
        if (!toolbarOptions.tools.noSettings){
            toolbar.append($sbviews.createTool('Edit Settings', 'images/gear.svg', function() {
                var optionsScope = scope.$new();
                optionsScope.editData = toolbarOptions.editData;
                optionsScope.part = angular.copy(part);
                optionsScope.toggleProperty = function(part, path, defaultValue) {
                    if (defaultValue == undefined)
                        defaultValue = '';

                    var obj = part;
                    var key = path;

                    var pathKeys = path.split('.');
                    if (pathKeys.length > 1) {
                        key = pathKeys.pop();
                        obj = pathKeys.reduce(function (obj,i) { return obj[i]; }, part);
                    }

                    if (obj[key] == undefined)
                        obj[key] = defaultValue;
                    else
                        delete obj[key];
                };

                optionsScope.delete = function (obj, k) {
                    delete obj[k];
                };

                optionsScope.toggleVisCondition = function(){
                    var sbExp = $(($(document.getElementById('visCondition'))).children().get(1));
                    
                    if (optionsScope.part.parameters.visibilityType==="visible" || optionsScope.part.parameters.visibilityType==="invisible"){
                        //disable condition input if visibility is visible or invisible
                        sbExp.prop('disabled', true);
                    }
                    else{//enable condition input if visibility is by condition
                        sbExp.prop('disabled', false);
                    }
                };
                
                var options = toolbarOptions.overlayOptions;
                optionsScope.options = options;

                $timeout(function() { // this is needed because the scope was created in the same digest..
                    function watch(path, fn) {
                        optionsScope.$watch(path, function(n, o) {
                            if (n != o) {
                                var notifiedPart = part;
                                if (toolbarOptions.notifiedPart != null)
                                    notifiedPart = toolbarOptions.notifiedPart;
                                $sbviews.notifyChanged(notifiedPart, {view: toolbarOptions.view});
                                if (notifiedPart == part) {
                                    optionsScope.part.pid = part.pid;
                                    optionsScope.part.origin = part.origin;
                                }
                                if (fn != undefined)
                                    fn(n, o);
                            }
                        }, true);
                    }
                    $sbviews.openOverlay(function(el, execClose) {
                        optionsScope.closeOverlay = function() {
                            execClose();
                        };

                        var container = $('<div ng-include="\'' + $rootScope.modulesDir + '/views/partials/settings-overlay.html\'">');
                        $compile(container)(optionsScope);
                        el.append(container);
                        watch('part.parameters.class');
                        watch('part.directive');
                        watch('part.parameters.style');
                        watch('part.parameters.loopData');
                        watch('part.if');
                        watch('part.events');
                        watch('part.data');
                        
                        el.on('mouseenter', function() {
                            //this ensures that when visibility is not conditional, the field will be disabled
                            optionsScope.toggleVisCondition();
                        });
                        // Events
                        var events = ['click', 'dblclick', 'mousedown', 'mouseup', 'mouseover', 'mouseout', 'mousemove', 'mouseenter', 'mouseleave', 'keydown', 'keyup', 'keypress', 'submit', 'focus', 'blur', 'copy', 'cut', 'paste'];
                        var missingEvents = [];
                        if (optionsScope.part.events != undefined) {
                            for (var i in events) {
                                var event = events[i];
                                if (optionsScope.part.events[event] == undefined)
                                    missingEvents.push(event);
                            }
                        } else {
                            missingEvents = events;
                        }
                        optionsScope.missingEvents = missingEvents;
                        optionsScope.events = {
                            eventToAdd: undefined
                        };
                        optionsScope.addEvent = function() {
                            var eventType = optionsScope.events.eventToAdd;
                            optionsScope.missingEvents.splice(optionsScope.missingEvents.indexOf(eventType), 1);
                            optionsScope.part.events[eventType] = '';
                        };

                        optionsScope.addEventToMissing = function(type) {
                            optionsScope.missingEvents.push(type);
                        };


                        // Variables
                        optionsScope.variables = {
                            dataKey: undefined
                        };

                        optionsScope.addVariable = function() {
                            optionsScope.part.variables[optionsScope.variables.dataKey] = {value: ''};
                            optionsScope.variables.dataKey = '';
                        };

                        $timeout(function() {
                            if (options.callbackFunc != undefined)
                                options.callbackFunc(container.next(), execClose, optionsScope, watch);
                        }, 50);
                        
                    }, function(cancel) {
                        console.log("close settings",optionsScope.part);
         

                        if (JSON.stringify(optionsScope.part) !== JSON.stringify(part)) {
                            $timeout(function() {
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
            var tool = $sbviews.createTool('Edit Layout', 'images/layout-edit.svg', function() {
                if (toolbarOptions.toolFunctions.layoutEdit)
                    toolbarOptions.toolFunctions.layoutEdit(tool);
            });
            toolbar.append(tool);
        }

        if (toolbarOptions.tools.canDelete) {
            toolbar.append($sbviews.createTool('Remove', 'images/trashcan.svg', function() {
                
                toolbarOptions.toolFunctions.remove(part);
            }));
        }

        if (toolbarOptions.tools.canSwitch) {
            toolbar.append($sbviews.createTool('Switch part', 'images/switch.svg', function () {
                var optionsScope = scope.$new();
                $sbviews.openOverlay(function(el, execClose) {
                    optionsScope.closeOverlay = function() {
                        execClose();
                    };
                    var wrapper = $('<div>');

                    wrapper.append('<div class="title"><span>Switch part</span><img src="images/close.svg" ng-click="closeOverlay()"></div>');
                    $compile(wrapper)(optionsScope);

                    var addPartsDiv = $(document.createElement('div')).addClass('add-parts');
                    addPartsDiv.attr('style', 'display: block; margin: 0 auto; padding: 6px; width: 230px');
                    var partsList = $(document.createElement('select')).attr('id', 'partList');
                    partsList.append('<option disabled>-- Part --</option>');
                    for(var type in $sbviews.registeredPartType) {
                        var partDef = $sbviews.registeredPartType[type];
                        if (partDef.name != undefined && partDef.defaultPart != undefined) {
                            var option = $(document.createElement('option'));
                            option.text(partDef.name);
                            option.val('part:' + type);
                            partsList.append(option);
                        }
                    }

                    partsList.append('<option disabled>-- Template --</option>');
                    
                    var templates = toolbarOptions.editData.templates;
                    for (var t in templates) {
                        var template = templates[t];
                        var option = $(document.createElement('option'));
                        option.text(template["name"]+" ("+template['id']+")");
                        option.val('temp:' + template['id']);
                        partsList.append(option);
                    }

                    var turnButton = $(document.createElement('button')).text('Turn');
                    turnButton.click(function() {
                        var value = partsList.val();
                        var id = value.substr(5);
                        var newPart;
                        if (value.indexOf('part:') == 0)
                            newPart = $sbviews.registeredPartType[id].defaultPart();
                        else if (value.indexOf('temp:') == 0)
                            newPart = angular.copy(templates[id]);

                        $sbviews.changePids(newPart);
                        toolbarOptions.toolFunctions.switch(part, newPart);
                        execClose();
                    });

                    wrapper.append('<label for="partList">Turn Part into:</label>')
                    wrapper.append(partsList);
                    wrapper.append(turnButton);
                    el.append(wrapper);
                }, function() {
                    optionsScope.$destroy();
                });
            }));
        }

        if (toolbarOptions.tools.canDuplicate) {
            toolbar.append($sbviews.createTool('Duplicate', 'images/duplicate.svg', function () {
                toolbarOptions.toolFunctions.duplicate(part);
            }));
        }

        if (toolbarOptions.tools.canSaveTemplate) {
            toolbar.append($sbviews.createTool('Save template', 'images/save.svg', function () {
                var optionsScope = scope.$new();
                optionsScope.editData = toolbarOptions.editData;
                optionsScope.part = part;
                $timeout(function () {
                    $sbviews.openOverlay(function (el, execClose) {
                        optionsScope.closeOverlay = function () {
                            execClose();
                        };

                        var templatePart = angular.copy(part);
                        $sbviews.changePids(templatePart);
                        optionsScope.template = {
                            name: '',
                            part: templatePart,
                            course: $rootScope.course
                        };

                        optionsScope.saveTemplate = function () {
                            $smartboards.request('views', 'saveTemplate', optionsScope.template, function (data, err) {
                                if (err) {
                                    alert(err.description);
                                    return;
                                }
                                execClose();
                                alert('Template saved!');
                                optionsScope.editData.templates[optionsScope.template.name] = templatePart;
                            });
                        };

                        var wrapper = $('<div>')
                        wrapper.append('<div class="title"><span>Save Template</span><img src="images/close.svg" ng-click="closeOverlay()"></div>');
                        var input = $('<sb-input sb-input="template.name" sb-input-label="Template Name"><button ng-click="saveTemplate()">Save</button></sb-input>');
                        wrapper.append(input);
                        $compile(wrapper)(optionsScope)
                        el.append(wrapper);
                    }, function () {
                    });
                });
            }));
        }

        var nTools = toolbar.children().length;
        toolbar.css('min-width', nTools * 15 + 1);

        return toolbar;
    };

    this.editOptions = function(options, extend) {
        return $.extend({
            edit: options.edit,
            editData: options.editData,
            view: options.view
        }, extend);
    };

    this.bindToolbar = function(element, scope, part, partOptions, options) {
        var myToolbar = undefined;
        var toolbarOptions;
        var layoutEditing = false;

        var toolOptions = partOptions.toolOptions != undefined ? partOptions.toolOptions : {};
        var toolFunctions = partOptions.toolFunctions != undefined ? $.extend({}, partOptions.toolFunctions) : {};
        var overlayOptions = partOptions.overlayOptions != undefined ? partOptions.overlayOptions : {};
        var editData = partOptions.editData != undefined ? partOptions.editData : {};

        toolFunctions.layoutEdit = function(tool) {
            layoutEditing  = !layoutEditing;
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

        element.on('mouseenter', function() {
            if (myToolbar)
                return;
                                   
            var trueMargin = element.data('true-margin');
            if (trueMargin == undefined) {
                trueMargin = element.innerHeight() - element.height();
                element.data('true-margin', trueMargin)
            }

            var defaultOptions = {
                layoutEditor: false,
                overlayOptions: {
                    allowClass: true,
                    allowStyle: true
                    //allowDirective: true
                },
                tools: {
                    noSettings: false,
                    canSwitch: false,
                    canDelete: false,
                    canDuplicate: false,
                    canSaveTemplate: false
                },
                view: partOptions.view
            };
            
            if (element.parent().hasClass("header")){
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
            toolbarOptions = $.extend(true, defaultOptions, {tools: toolOptions, toolFunctions: toolFunctions, overlayOptions: overlayOptions}, options);
            toolbarOptions.editData = editData;

            myToolbar = $sbviews.createToolbar(scope, part, toolbarOptions);
            //element.css('padding-top', trueMargin + 18);
  
            myToolbar.css({
                position: 'absolute',
                top: 0,
                right: 0
            });
            if (element.parent().prop("tagName")!="TD")//not highlighting table element because it moves things arround
                element.addClass('highlight');
            
            element.append(myToolbar);
            //console.log("mouseenter",part);
        });

        element.on('mouseleave', function(e) {
            if (layoutEditing)
                return;
            element.css('padding-top', element.data('true-margin'));
            element.removeClass('highlight');
            if (myToolbar != undefined)
                myToolbar.remove();
            myToolbar = undefined;
        });
    };

    this.defaultPart = function(partType) {
        var part = $sbviews.registeredPartType[partType].defaultPart();
        $sbviews.generatePid(part);
        return part;
    };

    this.generatePid = function(part) {
        var validChars = '0123456789abcdef';
        var pid = '';
        for (var i = 0; i < 32; ++i)
            pid += validChars[Math.floor(Math.random() * 16)];
        if (part != undefined) {
            part.pid = pid;
            part.origin = 'this';
        }
        return pid;
    };

    this.changePids = function(part) {
        $sbviews.generatePid(part);
        $sbviews.registeredPartType[part.partType].changePids(part, $sbviews.changePids);
    };

    this.notifyChanged = function(part, options) {
        if (part.origin == 'this')
            return;
        var oldpartid = part.pid;
        var newpartid = $sbviews.generatePid(part);
        var view = options.view;
        if (view.replacements !== 'object' || Array.isArray(view.replacements))
            view.replacements = {};
        view.replacements[oldpartid] = newpartid;
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
        var newKeys = copyKeys.filter(function(val) {
            return originalKeys.indexOf(val) == -1;
        });
        var commonKeys = copyKeys.filter(function(val) {
            return originalKeys.indexOf(val) > -1;
        });
        var oldKeys = originalKeys.filter(function(val) {
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
    this.setDefaultParamters = function(part) {
        //sets some fields contents to '{}' and ensures paramters is an object
        if (Array.isArray(part.parameters)){
            //when a block is saved with empty parameters it becomes an array instead of object
            //this changes them back to object(to prevent problems where params wheren't being saved)
            part.parameters={};
        }
        if (part.parameters!==undefined ){
            if (part.parameters.loopData===undefined)
                part.parameters.loopData="{}";
            if (part.parameters.visibilityCondition===undefined)
                part.parameters.visibilityCondition="{}";
            if (part.parameters.visibilityType==undefined)
                part.parameters.visibilityType="conditional";
        }
    };
    
});
