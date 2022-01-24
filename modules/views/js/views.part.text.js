angular.module('module.views').run(function ($rootScope, $timeout, $sbviews, $compile) {
    $sbviews.registerPartType('text', {
        name: 'Text',
        defaultPart: function () {
            return {
                partType: 'text',
                value: 'Text',
                loopData: '{}',
                visibilityCondition: '{}',
                visibilityType: "conditional"
            };
        },
        build: function (scope, part, options) {
            var valuePartDef = this;
            $sbviews.setDefaultParamters(part);

            scope.placeholder = undefined;
            scope.placeholderValue = undefined;

            var element = this.createElement(scope, part, options);

            if (options.edit) {
                var optionsDivEl;

                if (part.class === null || part.class === undefined)
                    part.class = 'value';
                else if (!part.class.includes('value'))
                    part.class += '; value';

                function buildOptions(optionsScope, watch) {

                    var optionsDiv = $('<sb-menu sb-menu-title="Content"><div ng-include="\'' + $rootScope.modulesDir + '/views/partials/value-settings.html\'"></div></sb-menu>');
                    $compile(optionsDiv)(optionsScope);

                    watch('part.valueType');
                    watch('part.value', function () {
                        valuePartDef.createElement(optionsScope, optionsScope.part, { edit: true, preview: true });
                    });

                    if (optionsDivEl === undefined)
                        return optionsDivEl = optionsDiv;
                    else {
                        optionsDivEl.replaceWith(optionsDiv);
                        optionsDivEl = optionsDiv;
                    }
                }

                function bindToolbar() {
                    $sbviews.bindToolbar(element, scope, part, options, {
                        overlayOptions: {
                            callbackFunc: function (el, execClose, optionsScope, watch) {
                                el.children('.partSpecific').after(buildOptions(optionsScope, watch));
                            }, closeFunc: function () {
                                var newEl = valuePartDef.createElement(scope, part, options);
                                var data = element.data();
                                if (element.hasClass('diff_aspect')) {
                                    newEl.removeClass('aspect_hide');
                                    newEl.addClass('diff_aspect');
                                    //$sbviews.findViewToShow(part.viewId);
                                }
                                if (element.hasClass('view')) {
                                    newEl.addClass('view editing');
                                    //$sbviews.findViewToShow(part.viewId);
                                }
                                element.replaceWith(newEl);
                                element = newEl;
                                element.data(data);
                                //element.css('padding-top', 18);
                                bindToolbar();
                                optionsDivEl = undefined;
                            }
                        }
                    });
                }
                bindToolbar();
                //element.css('padding-top', 18);
            }
            return element;
        },
        createElement: function (scope, part, options) {
            var element;
            if (part.link && !options.edit) {
                part.link = part.link.replace(/\s/g, '');
                element = $(document.createElement('a')).addClass('value').attr('href', part.link);
            } else {
                element = $(document.createElement('span')).addClass('value');
            }

            if (part.value === '' || scope.placeholderValue === '') {
                element.text('(Empty Value)');
                element.addClass('red');
            }
            else
                element.html(part.value);
            if (options.edit) {
                element.attr('data-role', parseRole(part.role)).attr('data-viewId', part.viewId);
                if (scope.role.includes('>')) {
                    if (scope.role.split('>')[1] != parseRole(part.role.split('>')[1]) || (scope.role.split('>')[0] != parseRole(part.role.split('>')[0]))) {
                        if (part.parentId != null && !("header" in scope.$parent.part && part in scope.$parent.part.header) || part.parentId === null)
                            element.addClass('aspect_hide');
                    }
                } else {
                    if (scope.role != parseRole(part.role)) {

                        if (part.parentId != null && !("header" in scope.$parent.part && part in scope.$parent.part.header) || part.parentId === null)
                            element.addClass('aspect_hide');
                    }
                }

                if (part.isTemplateRef) {
                    element.attr("style", "background-color: #ddedeb; ");
                }
            }

            element.data("scope", scope);
            return element;
        },
        destroy: function (element) {
        }
    });
});