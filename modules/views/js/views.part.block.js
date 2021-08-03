angular.module('module.views').run(function ($smartboards, $sbviews, $compile, $timeout) {
    $sbviews.registerPartType('block', {
        name: 'Block',
        defaultPart: function () {
            var part = {
                partType: 'block',
                header: {
                    title: undefined,
                    image: undefined
                },
                children: []
            };

            var titlePart = $sbviews.defaultPart('text');
            titlePart.value = 'Header Title';
            part.header.title = titlePart;


            var imagePart = $sbviews.defaultPart('image');
            imagePart.value = 'images/awards.svg';
            part.header.image = imagePart;
            return part;
        },
        build: function (scope, part, options) {
            function deleteIds(newPart) {
                delete newPart.id;
                for (var c in newPart.children) {
                    deleteIds(newPart.children[c]);
                }
                for (var r in newPart.rows) {
                    deleteIds(newPart.rows[r]);
                }
                for (var r in newPart.headerRows) {
                    deleteIds(newPart.headerRows[r]);
                }
                for (var c in newPart.values) {
                    deleteIds(newPart.values[c].value);
                }
            }

            function updateViewIds(newPart, newViewId) {
                newPart.viewId = newViewId;
                for (var r in newPart.headerRows) {
                    newViewId = (parseInt(newViewId) + 1).toString();
                    updateViewIds(newPart.headerRows[r], newViewId);
                }
                for (var r in newPart.rows) {
                    newViewId = (parseInt(newViewId) + 1).toString();
                    updateViewIds(newPart.rows[r], newViewId);
                }
                for (var c in newPart.values) {
                    newViewId = (parseInt(newViewId) + 1).toString();
                    updateViewIds(newPart.values[c].value, newViewId);
                }
                for (var c in newPart.children) {
                    newViewId = (parseInt(newViewId) + 1).toString();
                    updateViewIds(newPart.children[c], newViewId);
                }
            }
            $sbviews.setDefaultParamters(part);
            var block = $(document.createElement('div')).addClass('block');
            if (options.edit) {
                block.attr('data-role', parseRole(part.role)).attr('data-viewId', part.viewId);
                if (scope.role.includes('>')) {
                    if (scope.role.split('>')[1] != parseRole(part.role.split('>')[1])) {
                        block.addClass('aspect_hide');
                    }
                } else {
                    if (scope.role != parseRole(part.role))
                        block.addClass('aspect_hide');
                }
                if (part.class === null || part.class === undefined)
                    part.class = 'block';
                else if (!part.class.includes('block'))
                    part.class += '; block';
            }

            if (part.header) {
                part.header.image.role = part.role;
                part.header.title.role = part.role;
                var blockHeader = $(document.createElement('div')).addClass('header');
                if (part.header.anchor && !options.edit)
                    blockHeader.append('<a name="' + part.header.anchor + '"></a>');
                blockHeader.append($sbviews.build(scope, 'part.header.image', $sbviews.editOptions(options, { partType: 'image' })));
                blockHeader.append($sbviews.build(scope, 'part.header.title', $sbviews.editOptions(options, { partType: 'text' })).addClass('title'));
                block.append(blockHeader);
            }

            var blockContent = $(document.createElement('div')).addClass('content');

            if (part.children.length == 0 && options.edit) {
                blockContent.append($(document.createElement('div')).text('(No Children)').addClass('red no-children'));
            }

            if (part.isTemplateRef) {
                blockContent.attr("style", "background-color: #ddedeb; ");
            }

            var childOptions;
            if (options.edit) {
                function addOverlay(child) {
                    var replaceDiv = $('<div>');
                    var overlay = $('<div class="block-edit-overlay"></div>');
                    overlay.on('mouseover', function (event) {
                        event.stopPropagation();
                    });

                    var moving = false;

                    var oldIndex = 0;

                    function defineOverlayClick(overlay, child) {
                        overlay.on('mousedown', function (event) {
                            moving = true;
                            replaceDiv.css({ flexShrink: 0, width: child.innerWidth(), height: child.innerHeight() });
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
                            $timeout(function () {
                                var value = part.children[oldIndex];
                                child.detach();
                                if (newIdx < part.children.length - 1)
                                    $(block.children('.content').get(0).children[newIdx]).before(child);
                                else
                                    $(block.children('.content').get(0).children[newIdx - 1]).after(child);
                                part.children.splice(oldIndex, 1);
                                part.children.splice(newIdx, 0, value);
                                //$sbviews.notifyChanged(part, options);
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
                        canSaveTemplate: true,
                        canHaveAspects: true,
                    },
                    toolFunctions: {
                        remove: function (obj) {
                            // var idx = part.children.indexOf(obj);
                            // part.children.splice(idx, 1);
                            // $sbviews.destroy($(blockContent.children().get(idx)));
                            // if (blockContent.children().length == 0)
                            //     blockContent.append($(document.createElement('div')).text('(No Children)').addClass('red no-children'));
                            //$sbviews.notifyChanged(part, options);
                            //$sbviews.findViewToShow(obj.viewId);
                            $sbviews.manageViewAndSubviews(obj, scope, blockContent, childOptions, 'remove');
                        },
                        duplicate: function (obj) {
                            // var idx = part.children.indexOf(obj);
                            // var newPart = $.extend(true, {}, obj);
                            // const viewIdsArray = Array.from(document.querySelectorAll('[data-viewid]')).map(x => parseInt(x.getAttribute('data-viewid'))).sort();
                            // newPart.viewId = (viewIdsArray[viewIdsArray.length - 1] + 1).toString();
                            // //$sbviews.changePids(newPart);
                            // deleteIds(newPart);
                            // delete newPart.viewIndex;
                            // part.children.splice(idx, 0, newPart);
                            // var newPartEl = $sbviews.build(scope, 'part.children[' + idx + ']', childOptions);
                            // $(blockContent.children().get(idx)).before(newPartEl);
                            $sbviews.manageViewAndSubviews(obj, scope, blockContent, childOptions, 'duplicate');
                            //$sbviews.notifyChanged(part, options);
                        },
                        switch: function (obj, newPart) {
                            // var idx = part.children.indexOf(obj);
                            // part.children.splice(idx, 1, newPart);
                            // var newPartEl = $sbviews.build(scope, 'part.children[' + idx + ']', childOptions);
                            // var oldEl = $(blockContent.children().get(idx));
                            // oldEl.replaceWith(newPartEl);
                            // $sbviews.destroy(oldEl);
                            $sbviews.manageViewAndSubviews(obj, scope, blockContent, childOptions, 'switch', newPart);
                            //$sbviews.notifyChanged(part, options);
                        }
                    }
                });

                scope.$watch('part.header', function (n, o) {
                    if (o != n) {
                        var header = n;
                        if (header != undefined) {
                            header.image.role = part.role;
                            header.title.role = part.role;
                            blockHeader = $(document.createElement('div')).addClass('header');
                            blockHeader.append($sbviews.build(scope, 'part.header.image', $sbviews.editOptions(options, { partType: 'image' })));
                            blockHeader.append($sbviews.build(scope, 'part.header.title', $sbviews.editOptions(options, { partType: 'text' })).addClass('title'));
                            block.prepend(blockHeader);
                            //$sbviews.notifyChanged(part, options);
                        } else {
                            blockHeader.each(function () {
                                $sbviews.destroy($(this));
                            });
                            blockHeader.remove();
                            //$sbviews.notifyChanged(part, options);
                        }
                    }
                });
            }

            for (var cidx in part.children) {
                blockContent.append($sbviews.build(scope, 'part.children[' + cidx + ']', childOptions));
            }
            block.append(blockContent);

            if (options.edit) {
                var newOptions = $sbviews.editOptions(options, {
                    toolOptions: {
                        canSwitch: true,
                        canHaveAspects: true
                    },
                    toolFunctions: {
                        switch: function (obj, newPart) {
                            $sbviews.manageViewAndSubviews(obj, null, document.getElementById("viewEditor"), newOptions, 'switch', newPart);
                        }
                    }
                });
                $sbviews.bindToolbar(block, scope, part, $.extend(true, options, newOptions), {
                    layoutEditor: true,
                    overlayOptions: {
                        callbackFunc: function (el, execClose, optionsScope, watch) {
                            optionsScope.toggleHeader = function () {
                                if (optionsScope.part.header == undefined) {
                                    optionsScope.part.header = $sbviews.defaultPart('block').header;
                                } else
                                    delete optionsScope.part.header;
                            };

                            var partSpecificMenu = $('<sb-menu ng-if="part.noHeader != true" sb-menu-title="Content"></sb-menu>');

                            var header = $('<div class="sb-checkbox">');
                            header.append('<input id="block-header" type="checkbox" ng-checked="part.header != undefined" ng-click="toggleHeader()">');
                            header.append('<label for="block-header"> Enable Header</label>');
                            watch('part.header');

                            /*var pageAnchor = $('<sb-checkbox ng-if="part.header != undefined" sb-checkbox="part.header.anchor" sb-checkbox-label="Enable Page Anchor" sb-checkbox-default="" sb-checkbox-info="Adds a anchor reference to the block. This is used by links to jump directly to this element." sb-checkbox-link="./docs/#PartBlockHeaderPageAnchor"></sb-checkbox>');
                            pageAnchor.append('<sb-input sb-input="part.header.anchor" sb-input-label="Anchor"></sb-input>');
                            watch('part.header.anchor');
            
                            header.children('.content').append(pageAnchor);*/

                            partSpecificMenu.append(header);

                            el.children('.partSpecific').after($compile(partSpecificMenu)(optionsScope));
                        }
                    },
                    toolFunctions: {
                        layoutEditStart: function () {
                            $(block.children('.content').get(0)).children().each(function () {
                                var el = $(this);
                                if (el.hasClass('no-children'))
                                    return;
                                addOverlay(el);
                            });



                            var addDiv = $("<div class='add_new_part icon' value='#add_part' onclick='openModal(this)'></div>");

                            //select part done on a modal
                            var addPartModal = $("<div class='modal' id='add_part'></div>");
                            addPartModalContent = $("<div class='modal_content'></div>");
                            addPartModalContent.append($('<button class="close_btn icon" value="#add_part" onclick="resetModal(this)"></button>'));
                            //addPartModalContent.append($('<div class="title">Add New Part: </div>'));
                            parts_selection = $('<div id="parts_selection"></div>');
                            template_selection = $('<div id="template_selection"></div>');
                            warningTemplates = $('<span id="warningTemplates">There are no templates!</span>');
                            addPartContent = $("<div class='content'></div>");
                            addPartContent.append(parts_selection);
                            addPartContent.append(template_selection);
                            addPartContent.append(warningTemplates);
                            addPartModalContent.append(addPartContent);
                            addPartModal.append(addPartModalContent);

                            warningTemplates.hide();


                            var addPartsDiv = $(document.createElement('div')).addClass('add-parts');

                            //creates options for the different parts
                            var partsList = $(document.createElement('select')).attr('id', 'partList');
                            partsList.append($('<option value="" disabled selected >Part Type</option>'))
                            for (var type in $sbviews.registeredPartType) {
                                var partDef = $sbviews.registeredPartType[type];
                                if (partDef.name != undefined && partDef.defaultPart != undefined) {
                                    var option = $(document.createElement('option'));
                                    option.text(partDef.name);
                                    option.val('part:' + type);
                                    partsList.append(option);

                                    partIconName = "images/" + type + "_part_icon.svg";
                                    part_option = $('<div value="' + type + '" class="part_option"></div>')
                                    part_option.append($(document.createElement('img')).attr('src', partIconName));
                                    part_option.append($('<div class="part_label">' + type + '</div>'));
                                    parts_selection.append(part_option);
                                    part_option.click(function () {
                                        $(".part_option").removeClass("focus");
                                        $(this).addClass("focus");
                                        type = this.getAttribute('value');
                                        $("#partList").val("part:" + type);
                                        template_selection.hide();
                                        warningTemplates.hide();
                                        addButton.prop('disabled', false);
                                    });
                                }
                            }
                            //create only one option for templates on invisible select
                            var temp_option = $(document.createElement('option'));
                            temp_option.text('Template');
                            temp_option.val('temp:');
                            partsList.append(temp_option);
                            // add template icon option
                            part_option = $('<div value="' + type + '" class="part_option"></div>')
                            part_option.append($(document.createElement('img')).attr('src', "images/template_part_icon.svg"));
                            part_option.append($('<div class="part_label"> template</div>'));
                            parts_selection.append(part_option);
                            part_option.click(function () {
                                $(".part_option").removeClass("focus");
                                $(this).addClass("focus");
                                type = this.getAttribute('value');
                                $("#partList").val("temp:");
                                if (templateList[0].children.length != 1) {
                                    template_selection.show();
                                    addButton.prop('disabled', false);
                                } else {
                                    warningTemplates.show();
                                }
                            });
                            addPartsDiv.append(partsList);
                            partsList.hide();

                            var templateList = $(document.createElement('select')).attr('id', 'templateList').addClass("form__input");
                            templateList.append('<option disabled>Select a template</option>');
                            var templates = options.editData.templates;
                            for (var t in templates) {
                                var template = templates[t];
                                var option = $(document.createElement('option'));
                                option.text(template["name"] + " (" + template['id'] + ")");
                                option.val('temp:' + t);
                                templateList.append(option);
                            }
                            template_selection.append(templateList);
                            template_selection.append($('<div class= "on_off"><span>Use Template by reference </span><label class="switch"><input id="isRef" type="checkbox"><span class="slider round"></span></label></div>'))
                            template_selection.hide();
                            addPartsDiv.append(template_selection);

                            function addPart(newPart, $isTemplateRef = false) {
                                console.log("newPart", newPart);
                                console.log("template by ref?", $isTemplateRef);
                                if ($isTemplateRef === false)
                                    deleteIds(newPart);
                                //$sbviews.changePids(newPart);
                                blockContent.children('.no-children').remove();
                                part.children.push(newPart);
                                var newChild = $sbviews.buildElement(scope, newPart, childOptions);
                                //$sbviews.notifyChanged(part, options);
                                blockContent.append(newChild);
                                addOverlay(newChild);
                            }

                            var addButton = $(document.createElement('button')).text('Add Item');
                            addButton.prop('disabled', true);
                            addButton.addClass("save_btn");
                            addButton.click(function () {
                                var value = partsList.val();
                                var index = value.substr(5);
                                var newPart = [];
                                if (value.indexOf('part:') == 0) {
                                    newPart = $sbviews.registeredPartType[index].defaultPart();
                                    const viewer = $("#viewer_role").find(":selected")[0].text;
                                    var role = viewer;
                                    if (part.role.includes('>')) {
                                        const user = $("#user_role").find(":selected")[0].text;
                                        role = user + '>' + viewer;
                                    }
                                    newPart.role = unparseRole(role);
                                    newPart.parentId = part.id;
                                    // if (part.children.length == 0)
                                    //     newPart.viewId = (parseInt(part.viewId) + 1).toString();
                                    // else {
                                    const viewIdsArray = Array.from(document.querySelectorAll('[data-viewid]')).map(x => parseInt(x.getAttribute('data-viewid'))).sort();
                                    newPart.viewId = (viewIdsArray[viewIdsArray.length - 1] + 1).toString();
                                    // }
                                    if (newPart.partType == "table") {
                                        newPart.rows[0].values[0].value.viewId = (parseInt(newPart.viewId) + 1).toString();
                                        newPart.rows[0].values[0].value.role = part.role;
                                    }
                                    addPart(newPart);
                                }
                                else if (value.indexOf('temp:') == 0) {
                                    var isRef = $('#isRef').is(':checked');
                                    var value = templateList.val();
                                    var id = value.substr(5);

                                    //templates[id].role = scope.$root.role;
                                    if (isRef) {
                                        // with reference
                                        console.log("newTemplateRef", templates[id]);
                                        $smartboards.request('views', 'getTemplateContent', templates[id], function (data, err) {
                                            if (err) {
                                                giveMessage(err.description);
                                                return;
                                            }

                                            for (let aspect of data.template) {
                                                delete aspect.id;

                                                aspect.isTemplateRef = true;
                                                //delete aspect.aspectClass;
                                                aspect.parentId = part.id;
                                                aspect.variables = JSON.parse(aspect.variables);
                                                aspect.events = JSON.parse(aspect.events);
                                                if (aspect.partType != 'block')
                                                    delete aspect.children;
                                                //aspect.viewId = templateViewId;
                                                console.log("getTemplate by Reference", data);
                                                //updateViewIds(aspect, templateViewId);
                                                addPart(aspect, true);
                                                //data.template.parent = part.viewId;
                                                // if we use a template that was saved 'as normal'
                                                //if (data.template.parent == null) {
                                                //it give all the children, and not the
                                            }


                                            $sbviews.findViewToShow(templates[id].viewId);
                                            //}
                                            //if we use a template that was saved as ref
                                            //else {
                                            //addPart(data.template, true);
                                            //}

                                            //addPart(data.template, true);
                                        });
                                    }
                                    else {
                                        //without ref
                                        //console.log(templates[id]);
                                        $smartboards.request('views', 'getTemplateContent', templates[id], function (data, err) {
                                            if (err) {
                                                giveMessage(err.description);
                                                return;
                                            }
                                            //delete data.template.id;
                                            console.log("getTemp", data.template);
                                            //data.parent = part.viewId;
                                            var templateViewId;
                                            if (part.children.length == 0)
                                                templateViewId = (parseInt(part.viewId) + 1).toString();
                                            else {
                                                const viewIdsArray = Array.from(document.querySelectorAll('[data-viewid]')).map(x => parseInt(x.getAttribute('data-viewid'))).sort();
                                                templateViewId = (viewIdsArray[viewIdsArray.length - 1] + 1).toString();
                                            }
                                            for (let aspect of data.template) {
                                                delete aspect.id;

                                                aspect.parentId = part.id;
                                                aspect.variables = JSON.parse(aspect.variables);
                                                aspect.events = JSON.parse(aspect.events);
                                                // we want to use as a copy and not as ref
                                                if (aspect.isTemplateRef)
                                                    delete aspect.isTemplateRef;
                                                //aspect.viewId = templateViewId;

                                                const viewIdsArray = Array.from(document.querySelectorAll('[data-viewid]')).map(x => parseInt(x.getAttribute('data-viewid'))).sort();
                                                aspect.viewId = (viewIdsArray[viewIdsArray.length - 1] + 1).toString();

                                                updateViewIds(aspect, aspect.viewId);

                                                //for each aspect??
                                                addPart(aspect);
                                            }


                                            $sbviews.findViewToShow(templateViewId);
                                        });
                                    }

                                }
                                addPartModal.hide();
                            });


                            addPartContent.append(addPartsDiv);
                            addPartContent.append(addButton);
                            $(document.body).append(addPartModal);
                            block.append(addDiv);
                            block.click();
                        },
                        layoutEditEnd: function () {
                            block.children('.content').find('.block-edit-overlay').remove();
                            block.children('.add_new_part').remove();
                            $("#add_part").remove();

                        }
                    }
                });
                //block.css('padding-top', 10);
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