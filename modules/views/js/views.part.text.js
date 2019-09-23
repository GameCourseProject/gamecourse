angular.module('module.views').run(function($rootScope, $timeout, $sbviews, $compile) {
    $sbviews.registerPartType('text', {
        name: 'Text',
        defaultPart: function() {
            return {
                partType: 'text',
                //info: 'Text'
                parameters: {
                    value: 'Text',
                    loopData: '{}',
                    visibilityCondition: '{}',
                    visibilityType: "conditional"
                }
            };
        },
        changePids: function(part, change) {
        },
        build: function(scope, part, options) {
            var valuePartDef = this;
            $sbviews.setDefaultParamters(part);
            
            scope.placeholder = undefined;
            scope.placeholderValue = undefined;

            var element = this.createElement(scope, part, options);

            if (options.edit) {
                var optionsDivEl;
                function buildOptions(optionsScope, watch) {

                    var optionsDiv = $('<sb-menu sb-menu-title="Content" sb-menu-icon="images/gear.svg"><div ng-include="\'' + $rootScope.modulesDir + '/views/partials/value-settings.html\'"></div></sb-menu>');
                    $compile(optionsDiv)(optionsScope);

                    watch('part.valueType');
                    watch('part.parameters.value', function() {
                        valuePartDef.createElement(optionsScope, optionsScope.part, {edit: true, preview: true});
                    });

                    if (optionsDivEl === undefined)
                        return optionsDivEl = optionsDiv;
                    else {
                        optionsDivEl.replaceWith(optionsDiv);
                        optionsDivEl = optionsDiv;
                    }
                }

                function bindToolbar() {
                    $sbviews.bindToolbar(element, scope, part, options, { overlayOptions: {callbackFunc: function(el, execClose, optionsScope, watch) {
                        el.children('.partSpecific').after(buildOptions(optionsScope, watch));
                    }, closeFunc: function() {
                        var newEl = valuePartDef.createElement(scope, part, options);
                        element.replaceWith(newEl);
                        element = newEl;
                        bindToolbar();
                        optionsDivEl = undefined;
                    }}});
                }
                bindToolbar();
                element.css('padding-top', 18);
            }
            return element;
        },
        createElement: function(scope, part, options) {
            var element;
            if (part.parameters.link && !options.edit)
                element = $(document.createElement('a')).addClass('value').attr('href', part.parameters.link);
            else
                element = $(document.createElement('span')).addClass('value');
            
            if (part.parameters.value === '' || scope.placeholderValue === '') {
                element.text('(Empty Value)');
                element.addClass('red');
            } 
            else 
                element.html(part.parameters.value);
            element.data("scope",scope);
            return element;
        },
        destroy: function(element) {
        }
    });
});