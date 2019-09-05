angular.module('module.views').run(function($smartboards,$sbviews, $compile, $timeout) {
    $sbviews.registerPartType('block', {
        name: 'Block',
        defaultPart: function() {
            var part = {
                partType: 'block',
                header: {
                    title: undefined,
                    image: undefined
                },
                children: [],
                parameters: {}
            };

            var titlePart = $sbviews.defaultPart('text');
            titlePart.parameters.value = 'Header Title';
            part.header.title = titlePart;

            var imagePart = $sbviews.defaultPart('image');
            imagePart.parameters.value = 'images/awards.svg';
            part.header.image = imagePart;
            return part;
        },
        changePids: function(part, change) {
            if (part.header) {
                if (part.header.title)
                    change(part.header.title);
                if (part.header.image)
                    change(part.header.image);
            }

            for (var i in part.children)
                change(part.children[i]);
        },
        build: function (scope, part, options) {
            function deleteIds(newPart){
                delete newPart.id;
                for (var c in newPart.children){
                    deleteIds(newPart.children[c]);
                }
                for (var r in newPart.rows){
                    deleteIds(newPart.rows[r]);
                }
                for (var r in newPart.headerRows){
                    deleteIds(newPart.headerRows[r]);
                }
                for (var c in newPart.values){
                    deleteIds(newPart.values[c].value);
                }
            }
            if (Array.isArray(part.parameters)){
                //when a block is saved with empty parameters it becomes an array instead of object
                //this changes them back (so it doesnt cause problems)
                part.parameters={};
            }
                
            var block = $(document.createElement('div')).addClass('block');
            if (part.header) {
                var blockHeader = $(document.createElement('div')).addClass('header');
                if (part.header.anchor && !options.edit)
                    blockHeader.append('<a name="' + part.header.anchor + '"></a>')
                blockHeader.append($sbviews.build(scope, 'part.header.image', $sbviews.editOptions(options, {partType: 'image'})));
                blockHeader.append($sbviews.build(scope, 'part.header.title', $sbviews.editOptions(options, {partType: 'text'})).addClass('title'));
                block.append(blockHeader);
            }

            var blockContent = $(document.createElement('div')).addClass('content');

            if (part.children.length == 0 && options.edit) {
                blockContent.append($(document.createElement('div')).text('(No Children)').addClass('red no-children'));
            }

            var childOptions;
            if (options.edit) {
                function addOverlay(child) {
                    var replaceDiv = $('<div>');
                    var overlay = $('<div class="block-edit-overlay" style="width: 100%; height: 100%; position: absolute; top: 0px; left: 0px; background-color: rgba(255, 0, 0, 0.1); z-index: 10000; cursor: move"></div>');
                    overlay.on('mouseover', function (event) {
                        event.stopPropagation();
                    });

                    var moving = false;

                    var oldIndex = 0;

                    function defineOverlayClick(overlay, child) {
                        overlay.on('mousedown', function (event) {
                            moving = true;
                            replaceDiv.css({flexShrink: 0, width: child.innerWidth(), height: child.innerHeight()});
                            oldIndex = Array.prototype.indexOf.call(block.children('.content').get(0).children, child.get(0));
                            child.before(replaceDiv);
                            child.css({
                                position: 'fixed',
                                left: event.pageX - document.body.scrollLeft,
                                top: event.pageY - (window.pageYOffset || document.documentElement.scrollTop)
                            });
                            event.preventDefault();
                            event.stopPropagation();
                        });
                    }

                    defineOverlayClick(overlay, child);
                    $(window).on('mousemove', function (event) {
                        if (!moving)
                            return;
                        child.css({
                            left: event.pageX - document.body.scrollLeft,
                            top: event.pageY - (window.pageYOffset || document.documentElement.scrollTop),
                            zIndex: 10000
                        });
                    });

                    $(window).on('mouseup', function (event) {
                        if (!moving)
                            return;
                        moving = false;

                        var newIdx = -1;
                        var pointEl = $(document.elementFromPoint(event.pageX - document.body.scrollLeft, event.pageY - (window.pageYOffset || document.documentElement.scrollTop)));
                        var el = pointEl;
                        if (el.length != 0) {
                            if (el.hasClass('block-edit-overlay')) {
                                el = el.parent();
                                if (el.parent().get(0) == child.parent().get(0)) {
                                    var before = event.pageX < (el.offset().left + el.width() / 2);
                                    if (before) {
                                        newIdx = Array.prototype.indexOf.call(block.children('.content').get(0).children, el.get(0));
                                    } else
                                        newIdx = Array.prototype.indexOf.call(block.children('.content').get(0).children, el.get(0)) + 1;
                                    if (oldIndex < newIdx)
                                        newIdx -= 2;
                                }
                            }
                        }
                        replaceDiv.replaceWith(child);
                        if (newIdx != -1 && newIdx != oldIndex) {
                            $timeout(function() {
                                var value = part.children[oldIndex];
                                child.detach();
                                if (newIdx < part.children.length - 1)
                                    $(block.children('.content').get(0).children[newIdx]).before(child);
                                else
                                    $(block.children('.content').get(0).children[newIdx - 1]).after(child);
                                part.children.splice(oldIndex, 1);
                                part.children.splice(newIdx, 0, value);
                                $sbviews.notifyChanged(part, options);
                            });
                        }

                        var cssProp = child.prop('style');
                        cssProp.removeProperty('position');
                        cssProp.removeProperty('left');
                        cssProp.removeProperty('top');
                        cssProp.removeProperty('z-index');
                        event.preventDefault();
                        event.stopPropagation();
                    });
                    child.append(overlay);
                }
                
                childOptions = $sbviews.editOptions(options, {
                    toolOptions: {
                        canDelete: true,
                        canSwitch: true,
                        canDuplicate: true,
                        canSaveTemplate: true
                    },
                    toolFunctions: {
                        remove: function (obj) {
                            var idx = part.children.indexOf(obj);
                            part.children.splice(idx, 1);
                            $sbviews.destroy($(blockContent.children().get(idx)));
                            if (blockContent.children().length == 0)
                                blockContent.append($(document.createElement('div')).text('(No Children)').addClass('red no-children'));
                            $sbviews.notifyChanged(part, options);
                        },
                        duplicate: function(obj) {
                            var idx = part.children.indexOf(obj);
                            var newPart = $.extend(true, {}, obj);
                            //$sbviews.changePids(newPart);
                            deleteIds(newPart);
                            delete newPart.viewIndex;
                            part.children.splice(idx, 0, newPart);
                            var newPartEl = $sbviews.build(scope, 'part.children[' + idx + ']', childOptions);
                            $(blockContent.children().get(idx)).before(newPartEl);
                            $sbviews.notifyChanged(part, options);
                        },
                        switch: function(obj, newPart) {
                            var idx = part.children.indexOf(obj);
                            part.children.splice(idx, 1, newPart);
                            var newPartEl = $sbviews.build(scope, 'part.children[' + idx + ']', childOptions);
                            var oldEl = $(blockContent.children().get(idx));
                            oldEl.replaceWith(newPartEl);
                            $sbviews.destroy(oldEl);
                            $sbviews.notifyChanged(part, options);
                        }
                    }
                });

                scope.$watch('part.header', function (n, o) {
                    if (o != n) {
                        var header = n;
                        if (header != undefined) {
                            blockHeader = $(document.createElement('div')).addClass('header');
                            blockHeader.append($sbviews.build(scope, 'part.header.image', $sbviews.editOptions(options, {partType: 'image'})));
                            blockHeader.append($sbviews.build(scope, 'part.header.title', $sbviews.editOptions(options, {partType: 'text'})).addClass('title'));
                            block.prepend(blockHeader);
                            $sbviews.notifyChanged(part, options);
                        } else {
                            blockHeader.each(function () {
                                $sbviews.destroy($(this));
                            })
                            blockHeader.remove();
                            $sbviews.notifyChanged(part, options);
                        }
                    }
                });
            }

            for (var cidx in part.children) {
                blockContent.append($sbviews.build(scope, 'part.children[' + cidx + ']', childOptions));
            }
            block.append(blockContent);

            if (options.edit) {
                $sbviews.bindToolbar(block, scope, part, options, {
                    layoutEditor: true,
                    overlayOptions: {
                        callbackFunc: function (el, execClose, optionsScope, watch) {
                            optionsScope.toggleHeader = function() {
                                if (optionsScope.part.header == undefined) {
                                    optionsScope.part.header = $sbviews.defaultPart('block').header;
                                } else
                                    delete optionsScope.part.header;
                            };

                            var partSpecificMenu = $('<sb-menu ng-if="part.noHeader != true" sb-menu-title="Part Specific" sb-menu-icon="images/gear.svg"></sb-menu>');

                            var header = $('<div class="sb-checkbox">');
                            header.append('<input id="block-header" type="checkbox" ng-checked="part.header != undefined" ng-click="toggleHeader()">');
                            header.append('<label for="block-header">Enable Header</label>');
                            header.append('<a href="./docs/#PartBlockHeader" target="_blank"><img title="Enables the block header, containing the image and title of the block" class="info" src="images/info.svg"></a>');
                            header.append('<div class="content"></div>');
                            watch('part.header');

                            var pageAnchor = $('<sb-checkbox ng-if="part.header != undefined" sb-checkbox="part.header.anchor" sb-checkbox-label="Enable Page Anchor" sb-checkbox-default="" sb-checkbox-info="Adds a anchor reference to the block. This is used by links to jump directly to this element." sb-checkbox-link="./docs/#PartBlockHeaderPageAnchor"></sb-checkbox>');
                            pageAnchor.append('<sb-input sb-input="part.header.anchor" sb-input-label="Anchor"></sb-input>');
                            watch('part.header.anchor');

                            header.children('.content').append(pageAnchor);

                            partSpecificMenu.append(header);

                            el.children('.partSpecific').after($compile(partSpecificMenu)(optionsScope));
                        }
                    },
                    toolFunctions: {
                        layoutEditStart: function() {
                            $(block.children('.content').get(0)).children().each(function() {
                                var el = $(this);
                                if (el.hasClass('no-children'))
                                    return;
                                addOverlay(el);
                            });
                            
                            
                            function addTemplatelist(listElement){
                                listElement.append('<option disabled>-- Template --</option>');
                                var templates = options.editData.templates;
                                for (var t in templates) {
                                    var template = templates[t];
                                    var option = $(document.createElement('option'));
                                    option.text(template["name"]+" ("+template['id']+")");
                                    option.val('temp:' + t);
                                    listElement.append(option);
                                }
                            }
                            
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
                            var templates = options.editData.templates;
                            addTemplatelist(partsList);
                            
                            function addPart(newPart){
                                console.log("newPart", newPart);
                                deleteIds(newPart);
                                //$sbviews.changePids(newPart);
                                blockContent.children('.no-children').remove();
                                part.children.push(newPart);
                                var newChild = $sbviews.buildElement(scope, newPart, childOptions);
                                $sbviews.notifyChanged(part, options);
                                blockContent.append(newChild);
                                addOverlay(newChild);
                            }
                            
                            var addButton = $(document.createElement('button')).text('Add');
                            addButton.click(function() {                                
                                var value = partsList.val();
                                var index = value.substr(5);
                                var newPart = [];
                                if (value.indexOf('part:') == 0){
                                    newPart = $sbviews.registeredPartType[index].defaultPart();
                                    addPart(newPart);
                                }
                                else if (value.indexOf('temp:') == 0){     
                                    templates[index].role=part.role;
                                    $smartboards.request('views', 'getTemplateContent', templates[index], function (data, err) {
                                        if (err) {
                                            alert(err.description);
                                            return;
                                        }
                                        console.log("getTemp", data.template);
                                        addPart(data.template);
                                    });                                    
                                }                                
                            });

                            addPartsDiv.append('<label for="partList">Add New Part:</label><br>')
                            addPartsDiv.append(partsList);
                            addPartsDiv.append(addButton);
                            
                            var addTemplateRef = $(document.createElement('div')).addClass('add-parts');
                            addTemplateRef.attr('style', 'display: block; margin: 0 auto; padding: 6px; width: 230px');
                            var templateList = $(document.createElement('select')).attr('id', 'partList');
                            addTemplatelist(templateList);
                            var addTemplateButton = $(document.createElement('button')).text('Add');
                            addTemplateButton.click(function() {
                                var value = templateList.val();
                                var id = value.substr(5);
                                
                                    //newPart = angular.copy(templates[id]['content']);
                                    console.log("newTemplateRef",templates[id]);
                                    //get template contents
                                
                            });
                            addTemplateRef.append('<label for="partList">Add Template Reference:</label><br>')
                            addTemplateRef.append(templateList);
                            addTemplateRef.append(addTemplateButton);

                            block.append(addPartsDiv);
                            block.append(addTemplateRef);                            
                            block.append('');
                        },
                        layoutEditEnd: function() {
                            block.children('.content').find('.block-edit-overlay').remove();
                            block.children('.add-parts').remove();
                        }
                    }
                });
            }

            return block;
        },
        destroy: function (element) {
            element.children('.content').children().each(function () {
                $sbviews.destroy($(this));
            });
        }
    });
});