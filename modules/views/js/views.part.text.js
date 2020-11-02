angular.module('module.views').run(function($rootScope, $timeout, $sbviews, $compile) {
    $sbviews.registerPartType('text', {
        name: 'Text',
        defaultPart: function() {
            return {
                partType: 'text',
                value: 'Text',
                loopData: '{}',
                visibilityCondition: '{}',
                visibilityType: "conditional"
            };
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

                    var optionsDiv = $('<sb-menu sb-menu-title="Content"><div ng-include="\'' + $rootScope.modulesDir + '/views/partials/value-settings.html\'"></div></sb-menu>');
                    $compile(optionsDiv)(optionsScope);

                    watch('part.valueType');
                    watch('part.value', function() {
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
                        var data = element.data();
                        element.replaceWith(newEl);
                        element = newEl;
                        element.data(data);
                        //element.css('padding-top', 18);
                        bindToolbar();
                        optionsDivEl = undefined;
                    }}});
                }
                bindToolbar();
                //element.css('padding-top', 18);
            }
            return element;
        },
        createElement: function(scope, part, options) {
            var element;
            if (part.link && !options.edit)
                element = $(document.createElement('a')).addClass('value').attr('href', part.link);
            else
                element = $(document.createElement('span')).addClass('value');
            
            if (part.value === '' || scope.placeholderValue === '') {
                element.text('(Empty Value)');
                element.addClass('red');
            } 
            else 
                element.html(part.value);
            element.data("scope",scope);
            return element;
        },
        destroy: function(element) {
        }
    });
});