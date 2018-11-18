angular.module('module.views').run(function($sbviews, $compile) {
    var valuePartDef = $sbviews.registeredPartType['value'];
    var imagePartDef = {
        name: 'Image',
        defaultPart: function() {
            return {
                type: 'image',
                valueType: 'text',
                info: 'images/awards.svg'
            };
        },
        changePids: function(part, change) {
        },
        createElement: function(scope, part, options) {
            if (options.preview != undefined)
                return valuePartDef.createElement(scope, part, options);
            var img;

            var emptyValue = (part.info === '' || scope.placeholderValue === '');
            var fieldError = (part.valueType == 'field' && (scope.placeholder === undefined || scope.placeholderValue === ''));

            if (emptyValue || fieldError) {
                img = $(document.createElement('div')).attr('class', 'placeholder red');
            } else if (part.valueType == 'expression') {
                img = $(document.createElement('div')).attr('class', 'placeholder');
            } else if (part.valueType == 'text') {
                img = $(document.createElement('img')).attr('src', part.info);
            } else if (part.valueType == 'field') {
                img = $(document.createElement('img')).attr('src', scope.placeholderValue);
            }

            var root;
            if (part.link != undefined && !options.edit) {
                root = $(document.createElement('a')).attr({href: part.link});
            } else {
                root = $(document.createElement('span'));
            }
            root.append(img);
            root.addClass('image');
            img.addClass('img');
            return root;
        },
        destroy: function(element) {
        }
    }
    imagePartDef.build = valuePartDef.build;

    $sbviews.registerPartType('image', imagePartDef);
});