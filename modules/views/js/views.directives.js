angular.module('module.views').directive('sbMenu', function() {
    return {
        replace: true,
        restrict: 'E',
        transclude: true,
        scope: true,
        link: function ($scope, element, attrs) {
            $scope.title = attrs.sbMenuTitle;
            $scope.icon = attrs.sbMenuIcon;
        },
        template: '<div class="sb-menu"><div class="header"><img class="icon" ng-src="{{icon}}"><span>{{title}}</span></div><div class="content" ng-transclude></div></div>'
    };
}).directive('sbCheckbox', function($parse) {
    var uid = 0;
    return {
        replace: true,
        restrict: 'E',
        scope: true,
        transclude: true,
        link: function ($scope, element, attrs) {
            $scope.what = $parse(attrs.sbCheckbox);
            $scope.default = $parse(attrs.sbCheckboxDefault)($scope);
            if ($scope.default === undefined)
                $scope.default = '';
            $scope.label = attrs.sbCheckboxLabel;
            $scope.isChecked = function() { return $scope.what($scope) != undefined; };
            $scope.toggle = function() {
                if ($scope.what($scope) == undefined)
                    $scope.what.assign($scope, angular.copy($scope.default));
                else {
                    var path = attrs.sbCheckbox.split('.');
                    var key = path.pop();
                    var parentPath = path.join('.');
                    delete $parse(parentPath)($scope)[key];
                }
            };

            $scope.info = attrs.sbCheckboxInfo;
            $scope.link = attrs.sbCheckboxLink;

            $scope.elid = 'cb-' + (++uid);
        },
        template: '<div class="sb-checkbox"><input id="{{elid}}" type="checkbox" ng-checked="isChecked()" ng-click="toggle()"><label for="{{elid}}">{{label}}</label><a ng-href="{{link}}" target="_blank"><img ng-if="parameters.value != undefined" title="{{parameters.value}}" class="info" src="images/info.svg"></a><div class="content" ng-if="isChecked()" ng-transclude></div></div>'
    };
}).directive('sbInput', function($parse) {
    var uid = 0;
    return {
        replace: true,
        restrict: 'E',
        scope: true,
        transclude: true,
        link: function ($scope, element, attrs) {
            var parsedValue = $parse(attrs.sbInput);
            $scope.value = function() { return function() {
                return arguments.length > 0 ? parsedValue.assign($scope, arguments[0]) : parsedValue($scope);
            }; };
            $scope.label = attrs.sbInputLabel;
            $scope.elid = 'ip-' + (++uid);
        },
        template: '<div class="sb-input"><label for="{{elid}}">{{label}}</label><input id="{{elid}}" type="text" ng-model="value()" ng-model-options="{ getterSetter: true }"><div class="content" ng-transclude></div></div>'
    };
}).directive('sbExpression', function($parse, $timeout) {
    CodeAssistant = {};
    CodeAssistant.fields = {};
    CodeAssistant.path = {
        path: '',
        totalPath: '',
        objectPath: null
    };
    CodeAssistant.suggestions = [];
    CodeAssistant.suggestionSelected = 0;

    CodeAssistant.reset = function() {
        CodeAssistant.path = {
            path: '',
            totalPath: '',
            objectPath: CodeAssistant.fields
        };
        CodeAssistant.suggestions = [];
        CodeAssistant.suggestionSelected = 0;
    };

    CodeAssistant.setPath = function(path) {
        CodeAssistant.path.path = path;
    };

    CodeAssistant.pushPath = function() {
        CodeAssistant.followPathField();
        CodeAssistant.path.totalPath += (CodeAssistant.path.totalPath != '' ? '.' : '') + CodeAssistant.path.path;
        CodeAssistant.path.path = '';
    };

    CodeAssistant.pathFollowKey = function() {
        CodeAssistant.pushPath();
        CodeAssistant.setPath(CodeAssistant.getFields()[0].field);

    };

    function objectValues(obj) {
        var values = [];
        for (var i in obj)
            values.push(obj[i]);
        return values;
    }

    CodeAssistant.getFields = function () {
        var obj = CodeAssistant.path.objectPath;
        if (obj == undefined)
            return [];
        else if (obj.type == undefined)
            return objectValues(obj);
        else if (obj.type == 1) {
            return [obj.value];
        } else if (obj.type == 2)
            return objectValues(obj.fields);
        else if (obj.type == 3) {
            return [obj.options.value];
        } else
            return [];
    };

    CodeAssistant.followPathField = function() {
        var path = CodeAssistant.path.path;
        var obj = CodeAssistant.path.objectPath;
        if (obj == undefined)
            obj = undefined;
        else if (obj.type == undefined)
            obj = obj[path];
        else if (obj.type == 1)
            obj = obj.value;
        else if (obj.type == 2)
            obj = obj.fields[path];
        else if (obj.type == 3)
            obj = obj.options.value;
        else
            obj = undefined;
        CodeAssistant.path.objectPath = obj;
    };

    CodeAssistant.getCurrentType = function() {
        var obj = CodeAssistant.path.objectPath;
        if (obj == undefined)
            return undefined;
        return obj.type;
    };

    CodeAssistant.processError = function(err) {
        if (typeof err === 'object' && err.hash) {
            if (CodeAssistant.path.path != '' || (err.hash.expected.length == 1 && err.hash.expected[0] == '\'PATH\'')) {
                CodeAssistant.suggestions = [];
                var allFields = CodeAssistant.getFields();
                var possibleFields = allFields.filter(function(v) { return v.field.startsWith(CodeAssistant.path.path); });
                if ((possibleFields.length == 1 && possibleFields[0].field != CodeAssistant.path.path) || possibleFields.length > 1) {
                    CodeAssistant.suggestions = possibleFields.sort(function(a, b) { return a.field.localeCompare(b.field); });
                    CodeAssistant.suggestionSelected = 0;
                }
            }
        }
    };

    var uid = 0;
    return {
        replace: true,
        restrict: 'E',
        scope: true,
        transclude: true,
        link: function ($scope, element, attrs) {
            $scope.elid = 'ex-' + (++uid);

            CodeAssistant.fields = $scope.editData.fieldsTree;
            $scope.ca = CodeAssistant;
            var parsedValue = $parse(attrs.sbExpression);
            $scope.value = function() { return function() {
                return arguments.length > 0 ? parsedValue.assign($scope, arguments[0]) : parsedValue($scope);
            }; };
            $scope.label = attrs.sbExpressionLabel;
            $scope.tryAutoComplete = function($event) {
                if ($event.keyCode == 9 || $event.keyCode == 13) {
                    if (CodeAssistant.suggestions.length > 0) {
                        $event.preventDefault();

                        $scope.performAutoComplete(CodeAssistant.suggestionSelected);
                    }
                } else if ($event.keyCode == 38 && CodeAssistant.suggestions.length > 0) {
                    CodeAssistant.suggestionSelected = (CodeAssistant.suggestionSelected - 1) % CodeAssistant.suggestions.length;
                } else if ($event.keyCode == 40 && CodeAssistant.suggestions.length > 0) {
                    CodeAssistant.suggestionSelected = (CodeAssistant.suggestionSelected + 1) % CodeAssistant.suggestions.length;
                }
                $scope.applyResize($event);
            };

            $scope.performAutoComplete = function(id) {
                var autoComplete = CodeAssistant.suggestions[id].field.substr(CodeAssistant.path.path.length);
                var currentType = CodeAssistant.suggestions[id].type;
                var line = $scope.error.hash.loc.first_line;
                var value = parsedValue($scope);
                var currLine = 1;
                var currChar = 0;
                for (; currChar < value.length && currLine < line; ++currChar) {
                    if (value[currChar] == '\n')
                        currLine++;
                }
                currChar += $scope.error.hash.loc.last_column;

                if (currentType == 1 || currentType == 3)
                    autoComplete += '[';

                parsedValue.assign($scope, value.slice(0, currChar) + autoComplete + value.slice(currChar));
                //parsedValue.assign($scope, parsedValue($scope) + autoComplete);
            };

            $scope.applyResize = function() {
                var target = element.children('.expression');
                var content = target.val();
                var sizerDiv = $(document.createElement('div'));
                sizerDiv.html(content.replace(/\n/g, '<br>') + '<br>');
                sizerDiv.css({
                    width: target.width(),
                    'word-wrap': 'break-word',
                    'white-space': 'pre-wrap',
                    'line-height': '20px',
                    'font-size': '13px',
                    'font-family': 'monospace'
                });
                $(document.body).append(sizerDiv);
                target.css('height', sizerDiv.height());
                sizerDiv.remove();
            };

            setTimeout(function() {
                $scope.applyResize();
            }, 20);

            $scope.typeName = function(type) {
                switch (type) {
                    case 0: return 'Value';
                    case 1: return 'Array';
                    case 2: return 'Object';
                    case 3: return 'Map';
                    default: return null;
                }
            };

            $scope.testExpression = function(newValue) {
                try {
                    CodeAssistant.reset();
                    if (newValue != undefined)
                        SmartboardsExpression.parse(newValue);
                    $scope.error = 'OK';
                    element.children('.expression').removeClass('err');
                } catch (err) {
                    $scope.error = err;
                    element.children('.expression').addClass('err');
                    CodeAssistant.processError(err);
                }
                $scope.updateSuggestionStyle();
            };

            $scope.selectedStyle = {backgroundColor: 'rgba(0, 0, 0, 0.1)'};
            $scope.updateSuggestionStyle = function(hide) {
                if ((typeof $scope.error == 'string' && $scope.error == 'OK') || hide) {
                    $timeout(function() {
                        $scope.suggestionsStyle = {
                            display: 'none'
                        };
                    }, 100);
                } else if (CodeAssistant.suggestions.length > 0) {
                    var textArea = element.children('#' + $scope.elid);
                    var position = textArea.position();
                    var height = textArea.outerHeight();
                    $scope.suggestionsStyle = {
                        position: 'absolute',
                        top: position.top + height,
                        left: position.left
                    };
                }
            };

            $scope.needVariable = {display: 'none'};
            $scope.updateVisibility = function(visible) {
                $scope.updateSuggestionStyle(!visible);

                if (visible)
                    $scope.needField = {display: 'inline'};
                else
                    $timeout(function() { $scope.needField = {display: 'none'}; }, 200);
            };

            $scope.needField = {display: 'none'};
            $scope.searchFieldContext = {searchVariable: '', showFieldSearch: false};
            $scope.$watch('searchFieldContext.searchVariable', function(newValue, oldValue) {
                if (newValue == oldValue)
                    return;

                $scope.filteredResults = $scope.editData.fields;
                var searches = $scope.searchFieldContext.searchVariable.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&").split(' ');
                for (var i = 0; i < searches.length; ++i) {
                    var regex = new RegExp(searches[i], 'gi');
                    $scope.filteredResults = $scope.filteredResults.filter(function (val) {
                        return val.field.match(regex) != null || val.fieldExp.match(regex) != null || (val.desc != null && val.desc.match(regex) != null);
                    });
                }
            });

            $scope.selectField = function(field) {
                parsedValue.assign($scope, parsedValue($scope) + field.fieldExp);
                $scope.searchFieldContext.showFieldSearch = false;
                $timeout(function() { $scope.applyResize(); });
            };

            $scope.testExpression(parsedValue($scope));

            $scope.$watch(attrs.sbExpression, function(newValue, oldValue) {
                if (newValue == oldValue)
                    return;
                $scope.testExpression(newValue);
            });
        },
        template: '<div class="sb-expression">' +
        '<label for="{{elid}}">{{label}}</label>' +
        '<textarea id="{{elid}}" ng-blur="updateVisibility(false)" ng-focus="updateVisibility(true)" ng-model="value()" ng-model-options="{ getterSetter: true }" class="expression" placeholder="Expression" ng-keydown="tryAutoComplete($event)" ng-keyup="applyResize()"></textarea>' +
        //'<a ng-style="needField" ng-mousedown="searchFieldContext.showFieldSearch = true">Need a field?</a>' +
        //'<div class="expression-field-search" ng-if="searchFieldContext.showFieldSearch == true">' +
        //'<div><label for="label-search-{{elid}}">Search Field</label><input id="label-search-{{elid}}" type="text" ng-model="searchFieldContext.searchVariable">' +
        //'<img src="images/close.svg" ng-click="searchFieldContext.showFieldSearch = false"></div>' +
        //'<div class="value-filtered-fields">' +
        //'<div ng-repeat="result in filteredResults" ng-click="selectField(result)">' +
        //'<div class="field">{{result.fieldExp}}</div><div class="description">{{result.desc}}</div><div class="example">{{result.example}}</div>' +
        //'</div>' +
        //'</div>' +
        //'</div>' +
        //'<div class="suggestions" ng-style="suggestionsStyle" style="display: none"><div ng-repeat="suggestion in ca.suggestions" ng-style="ca.suggestionSelected == $index ? selectedStyle : undefined" ng-click="performAutoComplete($index)"><div class="field">{{suggestion.field}} - {{typeName(suggestion.type)}}</div><div class="description">{{suggestion.desc}}</div><div class="example">{{suggestion.example}}</div></div></div>' +
        '<div class="content" ng-transclude></div>' +
        '</div>'
    };
}).directive('events', function($state,$compile,$rootScope,$sbviews) {
    return {
        link: function($scope, $element) {
            $scope.goToPage = function(page,user=null) {
                console.log("goToPAge",page);
                var pageState = "course."+page.toLowerCase(); 
                if (user!==null){
                    $state.go(pageState, {'userID': user});
                }else{
                    $state.go(pageState);
                }
                    
            };
            $scope.hideView = function(label) {
                console.log("hide view",label);
                $compile($("#"+label).hide())($scope);
            };
            $scope.showView = function(label) {
                console.log("show view",label);
                $compile($("#"+label).show())($scope);
            };
            $scope.toggleView = function(label) {
                console.log("togles hiden",label);
                $compile($("#"+label).toggle())($scope);
            };
            $scope.tooltipBound = false;
            $scope.showToolTip = function(template) {
                console.log("showToolTip");
                if ($scope.tooltipBound){
                    console.log("bound");
                    return;
                }
                var view =JSON.parse(template);
                console.log(view);
                var viewScope = $rootScope.$new(true);
                viewScope.view = view;
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

                var tooltipContent = $('<div>', {'class': 'block'});    
                tooltipContent.append(view.element);

                $element.tooltip({offset: [50, -50], html: tooltipContent});
                $scope.tooltipBound = true; 
                $element.trigger('mouseover');
            };
        }
    };
});
