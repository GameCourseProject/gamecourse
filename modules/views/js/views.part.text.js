angular.module('module.views').run(function($rootScope, $timeout, $sbviews, $compile) {
    $sbviews.registerPartType('text', {
        name: 'Text',
        defaultPart: function() {
            return {
                partType: 'text',
                info: 'Text'
            };
        },
        changePids: function(part, change) {
        },
        build: function(scope, part, options) {
            var valuePartDef = this;
            
            scope.placeholder = undefined;
            scope.placeholderValue = undefined;

            var element = this.createElement(scope, part, options);

            if (options.edit) {
                var optionsDivEl;
                function buildOptions(optionsScope, watch) {

                    var optionsDiv = $('<sb-menu sb-menu-title="Part Specific" sb-menu-icon="images/gear.svg"><div ng-include="\'' + $rootScope.modulesDir + '/views/partials/value-settings.html\'"></div></sb-menu>');
                    $compile(optionsDiv)(optionsScope);
                    $timeout(function() {
                        optionsDiv.find('.preview').replaceWith(buildPreviewDiv());
                    }, 50);
                    watch('part.valueType');
                    watch('part.info', function() {
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
            
            if ((part.info==undefined || part.info=="") && (part.parameters != undefined && part.parameters.value!=undefined)){
                part.info=part.parameters.value;
            }
            if (part.info === '' || scope.placeholderValue === '') {
                element.text('(Empty Value)');
                element.addClass('red');
            } 
            else 
                element.html(part.info);
            return element;
        },
        destroy: function(element) {
        }
    });
});