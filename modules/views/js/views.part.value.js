angular.module('module.views').run(function($rootScope, $timeout, $sbviews, $compile) {
    $sbviews.registerPartType('value', {
        name: 'Value',
        defaultPart: function() {
            return {
                type: 'value',
                valueType: 'text',
                info: 'Text'
            };
        },
        changePids: function(part, change) {
        },
        build: function(scope, part, options) {
            function solveField(part) {
                var soFar = '';
                var contexts = {};
                var selectedField = part.info.field.split('.').reduce(function (p, v) {
                    if (soFar != '')
                        soFar += '.';
                    soFar += v;

                    if (p == undefined)
                        return undefined;
                    else if (p.type == undefined)
                        return p[v];
                    else if (p.type == 1 && p.value.field == v) {
                        contexts[soFar] = ['TODO: ASK FOR KEY']; // TODO: ASK FOR KEY
                        return p.value;
                    } else if (p.type == 2)
                        return p.fields[v];
                    else if (p.type == 3 && p.options.value.field == v) {
                        contexts[soFar] = p.options.keys;
                        return p.options.value;
                    } else
                        return undefined;
                }, options.editData.fieldsTree);

                scope.contexts = contexts;
                if (selectedField != undefined)
                    scope.placeholder = selectedField.example;
                else
                    scope.placeholder = undefined;
            }

            function applyFormatting() {
                var toFormat = 'value';
                if (scope.placeholder != undefined)
                    toFormat = scope.placeholder;
                if (part.info.format == undefined) {
                    scope.placeholderValue = toFormat;
                } else {
                    scope.placeholderValue = part.info.format.replace(/%v/g, toFormat);
                }
            }

            var valuePartDef = this;

            scope.placeholder = undefined;
            scope.placeholderValue = undefined;

            if (options.edit && part.valueType == 'field') {
                solveField(part);
                applyFormatting();
            }

            var element = this.createElement(scope, part, options);

            if (options.edit) {
                var optionsDivEl;
                function buildOptions(optionsScope, watch) {
                    var previewDiv;
                    function buildPreviewDiv() {
                        var newPreviewDiv = $(document.createElement('div')).addClass('preview');
                        newPreviewDiv.append($(document.createElement('strong')).text('Preview: '));
                        newPreviewDiv.append(valuePartDef.createElement(optionsScope, optionsScope.part, {edit: true, preview: true}));
                        if (previewDiv != undefined)
                            previewDiv.replaceWith(newPreviewDiv);
                        previewDiv = newPreviewDiv;
                        return previewDiv;
                    }

                    optionsScope.updateType = function () {
                        if (optionsScope.part.valueType == 'text') {
                            optionsScope.part.info = 'Text';
                            optionsScope.contexts = {};
                        } else if (optionsScope.part.valueType == 'field') {
                            optionsScope.part.info = {field: ''};
                            optionsScope.placeholder = undefined;
                        } else if (optionsScope.part.valueType == 'expression') {
                            optionsScope.part.info = 'ExpValue';
                            optionsScope.contexts = {};
                        }
                    };

                    optionsScope.$watch('part.info.field', function () {
                        if (optionsScope.part.valueType == 'field') {
                            solveField(optionsScope.part);
                            optionsScope.filteredResults = options.editData.fields;
                            var searches = optionsScope.part.info.field.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&").split(' ');
                            for (var i = 0; i < searches.length; ++i) {
                                var regex = new RegExp(searches[i], 'gi');
                                optionsScope.filteredResults = optionsScope.filteredResults.filter(function (val) {
                                    return (val.field.match(regex) != null || (val.desc != null && val.desc.match(regex) != null)) && val.leaf;
                                });
                            }
                            applyFormatting();
                        }
                    });

                    optionsScope.$watch('part.info.format', function () {
                        if (optionsScope.part.valueType == 'field')
                            applyFormatting();
                    });

                    optionsScope.toggleFormatting = function () {
                        if (optionsScope.part.info.format != undefined)
                            delete optionsScope.part.info.format;
                        else
                            optionsScope.part.info.format = '%v';
                    }

                    var optionsDiv = $('<sb-menu sb-menu-title="Part Specific" sb-menu-icon="images/gear.svg"><div ng-include="\'' + $rootScope.modulesDir + '/views/partials/value-settings.html\'"></div></sb-menu>');
                    $compile(optionsDiv)(optionsScope);
                    $timeout(function() {
                        optionsDiv.find('.preview').replaceWith(buildPreviewDiv());
                    }, 50);
                    watch('part.valueType');
                    watch('part.info', function() {
                        buildPreviewDiv();
                    });

                    if (optionsDivEl == undefined)
                        return optionsDivEl = optionsDiv;
                    else {
                        optionsDivEl.replaceWith(optionsDiv);
                        optionsDivEl = optionsDiv;
                    }
                }

                function bindToolbar() {
                    $sbviews.bindToolbar(element, scope, part, options, { overlayOptions: {callbackFunc: function(el, execClose, optionsScope, watch) {
                        el.children('.title').after(buildOptions(optionsScope, watch));
                    }, closeFunc: function() {
                        var newEl = valuePartDef.createElement(scope, part, options);
                        element.replaceWith(newEl);
                        element = newEl;
                        bindToolbar();
                        optionsDivEl = undefined;
                    }}});
                }
                bindToolbar();
            }

            return element;
        },
        createElement: function(scope, part, options) {
            var element;
            if (part.link && !options.edit)
                element = $(document.createElement('a')).addClass('value').attr('href', part.link);
            else
                element = $(document.createElement('span')).addClass('value');

            var valueType = part.valueType;
            if (part.info === '' || scope.placeholderValue === '') {
                element.text('(Empty Value)');
                element.addClass('red');
            } else if (valueType == 'field') {
                if (scope.placeholder !== undefined)
                    element.html(scope.placeholderValue);
                else {
                    element.text('(Unknown Field)');
                    element.addClass('red');
                }
            } else if (valueType == 'text')
                element.html(part.info);
            else if (valueType == 'expression')
                element.html('ExpValue');

            return element;
        },
        destroy: function(element) {
        }
    });
});