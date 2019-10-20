angular.module('module.views').run(function($sbviews, $compile) {
    var valuePartDef = $sbviews.registeredPartType['text'];
    var imagePartDef = {
        name: 'Image',
        defaultPart: function() {
            return {
                partType: 'image',
                edit: true,
                value: 'images/awards.svg'
            };
        },
        //changePids: function(part, change) {},
        createElement: function(scope, part, options) {
            if (options.preview != undefined)
                return valuePartDef.createElement(scope, part, options);
            var img;

            var emptyValue = (part.value === '' || scope.placeholderValue === '');
            
            if (emptyValue) {
                img = $(document.createElement('div')).attr('class', 'placeholder red');
            } else if (part.edit) {
                img = $(document.createElement('div')).attr('class', 'placeholder');
            } else //if (part.valueType == 'text') {
                img = $(document.createElement('img')).attr('src', part.value);
            
            
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
    };
    imagePartDef.build = valuePartDef.build;

    $sbviews.registerPartType('image', imagePartDef);
});