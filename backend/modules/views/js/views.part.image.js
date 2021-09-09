angular.module('module.views').run(function ($sbviews, $compile) {
    var valuePartDef = $sbviews.registeredPartType['text'];
    var imagePartDef = {
        name: 'Image',
        defaultPart: function () {
            return {
                partType: 'image',
                edit: true,
                value: 'images/awards.svg'
            };
        },
        //changePids: function(part, change) {},
        createElement: function (scope, part, options) {
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
                part.link = part.link.replace(/\s/g, '');
                root = $(document.createElement('a')).attr({ href: part.link });
            } else {
                root = $(document.createElement('span'));
            }
            root.append(img);
            root.addClass('image');
            if (options.edit) {
                root.attr('data-role', parseRole(part.role)).attr('data-viewId', part.viewId);
                if (scope.role.includes('>')) {
                    if (scope.role.split('>')[1] != parseRole(part.role.split('>')[1])) {
                        if (part.parentId != null && !("header" in scope.$parent.part) || part.parentId === null)
                            element.addClass('aspect_hide');
                    }
                } else {
                    if (scope.role != parseRole(part.role)) {
                        if (part.parentId != null && !("header" in scope.$parent.part) || part.parentId === null)
                            element.addClass('aspect_hide');
                    }
                }
                if (part.class === null || part.class === undefined)
                    part.class = 'image';
                else if (!part.class.includes('image'))
                    part.class += '; image';
            }
            if (part.isTemplateRef) {
                root.attr("style", "border-color: #e34309; ");
            }

            img.addClass('img');
            return root;
        },
        destroy: function (element) {
        }
    };
    imagePartDef.build = valuePartDef.build;

    $sbviews.registerPartType('image', imagePartDef);
});