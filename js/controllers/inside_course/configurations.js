//This file contains configuration options that are inside the settings
//(managing students and teachers, and configuring skill tree, badges and levels)

function clearFillBox($scope) {
    if ($scope.newList !== "")
        $scope.newList = "";
    else if ("file" in $scope.data)
        $scope.newList = $scope.data.file;
}

function constructNumberInput($compile, $scope, text, dataModel, button) {
    var numInput = $('<div>', { 'class': 'column', style: 'float: right; width: 100%;', text: text + ": " });
    numInput.append('<br><input type:"number" style="width: 25%" id="newList" ng-model="' + dataModel + '">');
    numInput.append('<button class="button small" ng-click="replaceNumber(' + dataModel + ')">Save ' + button + '</button>');
    return $compile(numInput)($scope);
}

function constructTextArea($compile, $scope, name, text, width = 60, data = "newList", rows = 25) {
    var bigBox = $('<div>', {
        'class': 'column',
        style: 'float: right; width: ' + width + '%;',
        text: text
    });

    var funName = (name == "Tier") ? name : "Data";
    bigBox.append('<textarea cols="80" rows="' + rows + '" type="text" style="width: 100%" class="ConfigInputBox" id="newList" ng-model="' + data + '"></textarea>');
    bigBox.append('<button class="button small" ng-click="replace' + funName + '(' + data + ')">Replace ' + name + ' List</button>');
    //ToDo: add this button (and delete) back after the system stops using TXTs from legacy_folder
    //If TXT files are used it's difficult to keep them synced w DB and have the Add/Edit functionality
    //if (name!=="Level")
    //    bigBox.append('<button class="button small" ng-click="addData(newList)">Add/Edit '+name+'s </button>');
    //ng-disabled="!isValidString(inviteInfo.id) "
    bigBox.append('<button class="button small" ng-click="clear' + funName + '()" style="float: right;">Clear/Fill Box</button>');
    //bigBox.append('</div>');//<button ng-disabled="!isValidString(inviteInfo.id) || !isValidString(inviteInfo.username)" ng-click="createInvite()">Create</button></div>');
    return ($compile(bigBox)($scope));
}

function constructConfigPage(data, err, $scope, $element, $compile, name, text, tabContents, columns) {
    if (err) {
        giveMessage(err.description);
        return;
    }
    $scope.data = data;
    var tabContent = $($element);
    var configurationSection = createSection(tabContent, 'Manage ' + name + 's');


    var configSectionContent = $('<div>', { 'class': 'row' });

    var table = Builder.buildTable(tabContents, columns, true);

    var tableArea = $('<div>', { 'class': 'column', style: 'float: left; width: 40%;' });
    tableArea.append(table);
    tableArea.append('</div>');
    configSectionContent.append(tableArea);

    var bigBox = constructTextArea($compile, $scope, name, text);

    configSectionContent.append(bigBox);
    configurationSection.append(configSectionContent);
}

app.controller('ConfigurationController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {

    $scope.saveDataGeneralInputs = function () {
        $smartboards.request('settings', 'saveModuleConfigInfo', { course: $scope.course, module: $stateParams.module, generalInputs: $scope.inputs }, alertUpdate);
    }
    $scope.generalInputsChanged = function () {
        changed = false;
        jQuery.each($scope.generalInputs, function (index) {
            inputName = $scope.generalInputs[index].id;
            initialInput = $scope.initialInputs[inputName];
            currentInput = $scope.inputs[inputName];
            if (initialInput != currentInput) {
                changed = true;
            }
        })
        return changed;
    }

    updateDependenciesAddSection = function ($scope, box, allSkills, addForm) {
        $("#dep_list").remove();
        $("#add_dep").remove();
        editbox = $(box);

        if ($scope.openItem.dependenciesList.length > 0) {
            dep_list = $('<div id="dep_list"></div>');
            for (var i = 0; i < $scope.openItem.dependenciesList.length; i++) {

                dep_row = $('<div id="dep_row"><span style="margin-right: 8px;">Dependency ' + String(i + 1) + ': </span></div>');
                jQuery.each($scope.openItem.dependenciesList[i], function (index) {
                    skill = $scope.openItem.dependenciesList[i][index];
                    dep_row.append($('<div class="skill_tag">' + skill + "</div>"));
                });
                dep_row.append($('<div class="delete_icon icon" ng-click="removeDependency(' + i + ')"></div>'));
                dep_list.append(dep_row);

            }
            dep_list.insertBefore("#dependency");
        }

        if (addForm) {
            add_dep = $('<div id="add_dep"></div>');
            add_dep.append($('<span style="margin-right: 8px;">New Dependency:</span>'));

            add_skill1 = $('<select id="dependency1" class="form__input skills" name="dependency1" onchange="changeSelectTextColor(this);">');
            add_skill2 = $('<select id="dependency2" class="form__input skills" name="dependency2" onchange="changeSelectTextColor(this);">');
            add_skill1.append($('<option value="" disabled selected>Select Skill 1</option>'));
            add_skill2.append($('<option value="" disabled selected>Select Skill 2</option>'));
            jQuery.each(allSkills, function (index) {
                role = allSkills[index];
                add_skill1.append($('<option value="' + role + '">' + role + "</option>"));
                add_skill2.append($('<option value="' + role + '">' + role + "</option>"));
            });
            add_dep.append(add_skill1);
            add_dep.append(add_skill2);
            add_dep.append($('<button ng-click="addDependency()">Add</button>'));
            add_dep.append($('<div class="delete_icon icon" ng-click="removeAddForm()"></div>'));

            add_dep.insertBefore("#dependency");
        }

        $compile(editbox)($scope);
    };

    $scope.openPickerModal = function (itemId = "", allowedExtensions = []) {
        $scope.selectedInput = itemId;
        $scope.allowedExtensions = allowedExtensions;
        if ($scope.module.name == "Skills")
            $scope.allowedExtensions = [".png", ".jpg", ".jpeg", ".gif"];
        openImagePicker($scope, $smartboards);
    }

    // $scope.chooseFileFromPC = function () {
    //     const input = document.getElementById("upload-picker");
    //     const file = document.getElementById(input.id).files[0];

    //     filename = $('#' + input.id).val().split('\\')[2];
    //     $(".config_input #text-" + input.id).text(filename);
    //     var reader = new FileReader();
    //     reader.onload = function (e) {
    //         $scope.uploadFile = reader.result;
    //         $smartboards.request('settings', 'upload', { course: $scope.course, newFile: $scope.uploadFile, fileName: file["name"], module: $scope.module.name, subfolder: $scope.openItem.name }, function (data, err) {
    //             if (err) {
    //                 giveMessage(err.description);
    //                 return;
    //             }
    //             if (data.url != 0) {
    //                 document.getElementById("img-upload-picker").src = data.url;
    //                 hideIfNeed("img-upload-picker");
    //                 //insertToEditor(data.url);// Display image element
    //             } else {
    //                 alert('file not uploaded');
    //             }
    //         }
    //         );
    //     }
    //     reader.readAsDataURL(file);
    // };

    $scope.saveChosenImage = function () {
        const imageUploaded = document.getElementById("img-upload-picker");

        if (imageUploaded.src != "" && imageUploaded.style.borderColor == "rgb(0, 112, 249)") {
            if ($scope.module.name == "Skills") {
                $scope.insertToEditor(imageUploaded.src);
            } else {
                document.getElementById("img-" + $scope.selectedInput).src = imageUploaded.src;
                hideIfNeed($scope.selectedInput);
                filename = $(".config_input #text-upload-picker").innerHTML;
                $(".config_input #text-" + $scope.selectedInput).text(filename);
            }
            //resetUploadImage("img-upload-picker");
        } else {
            document.getElementsByClassName("square-image").forEach(element => {
                if ($(element).css("borderColor") == "rgb(0, 112, 249)") {
                    // get the name of the file
                    var filename = element.children[1].innerHTML;
                    if ($scope.module.name == "Skills") {
                        $scope.insertToEditor(imageUploaded.src);
                    } else {
                        document.getElementById("img-" + $scope.selectedInput).src = imageUploaded.src;
                        hideIfNeed($scope.selectedInput);
                        $(".config_input #text-" + $scope.selectedInput).text(filename);
                    }

                    if ($scope.selectedInput == "badge") {
                        $scope.buildMergeImages($scope.uploadFile, filename.split(".")[0]);
                    }
                }
            });
        }
    }


    $scope.insertToEditor = function (url) {
        // push image url to rich editor.
        const range = quill.getSelection();
        quill.insertEmbed(range.index, 'image', `${url}`);
    }

    $scope.getSelectedInput = function (inputId) {
        $scope.selectedInput = $scope.generalInputs.filter(input => input.id == inputId)[0];
        document.getElementById(inputId).onchange = function () {
            $scope.uploadGeneralImages();
        }
    }

    $scope.uploadGeneralImages = function () {

        const input = $scope.selectedInput;
        const subfolder = input.options;
        const file = document.getElementById(input.id).files[0];

        filename = $('#' + input.id).val().split('\\')[2];
        $(".config_input #text_" + input.id).text(filename);

        $scope.inputs[input.id] = filename;

        var reader = new FileReader();
        reader.onload = function (e) {
            $scope.uploadFile = reader.result;

            //upload base image
            $smartboards.request('settings', 'upload', { course: $scope.course, newFile: $scope.uploadFile, fileName: filename, module: $scope.module.name, subfolder: subfolder }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                if (data.url != 0) {
                    document.getElementById("badge_" + input.id).src = data.url;
                    hideIfNeed("badge_" + input.id);
                }
            });


        }
        reader.readAsDataURL(file);
    }

    $scope.uploadBadgeImage = function () {

        const file = document.getElementById("imageFile").files[0];

        filename = $('#imageFile').val().split('\\')[2];
        $(".config_input #textBadge").text(filename);
        $scope.openItem[atribute.id] = filename;

        var reader = new FileReader();
        reader.onload = function (e) {
            $scope.uploadFile = reader.result;

            //upload base image
            $smartboards.request('settings', 'upload', { course: $scope.course, newFile: $scope.uploadFile, fileName: filename, module: $scope.module.name, subfolder: $scope.openItem.name }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                if (data.url != 0) {
                    document.getElementById("badge_img").src = data.url;
                    hideIfNeed('badge_img');
                    // take off the .png / .jpeg from filename
                    $scope.buildMergeImages($scope.uploadFile, filename.split(".")[0]);
                }
            });


        }
        reader.readAsDataURL(file);
    }

    $scope.buildMergeImages = function (file, filename) {

        const isExtra = $scope.openItem.extra;
        const isBragging = $scope.openItem.xp1 == 0;
        // initialInputs to get the values that are saved in the DB
        const extraImg = $scope.initialInputs["extraImg"];
        const braggingImg = $scope.initialInputs["extraImg"];

        var fileExtra = $scope.courseFolder + '/badges/Extra/' + extraImg;
        var fileBragging = $scope.courseFolder + '/badges/Bragging/' + braggingImg;
        // upload image for level 1
        if (isExtra) {
            layersL1 = [file, fileExtra];
            imageLevel1 = mergeImages(layersL1)
                .then(b64 => $smartboards.request('settings', 'upload', { course: $scope.course, newFile: b64, fileName: filename + '-1.png', module: $scope.module.name, subfolder: $scope.openItem.name }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                }
                ));

        } else if (isBragging) {
            layersL1 = [file, fileBragging];
            imageLevel1 = mergeImages(layersL1)
                .then(b64 => $smartboards.request('settings', 'upload', { course: $scope.course, newFile: b64, fileName: filename + '-1.png', module: $scope.module.name, subfolder: $scope.openItem.name }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                }
                ));
        }
        else {
            $smartboards.request('settings', 'upload', { course: $scope.course, newFile: $scope.uploadFile, fileName: filename + '-1.png', module: $scope.module.name, subfolder: $scope.openItem.name }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
            });
        }
        document.getElementById("img-badge-l1").src = $scope.courseFolder + "/badges/" + removeSpacefromName($scope.openItem.name) + "/" + filename + '-1.png';
        hideIfNeed('img-badge-l1');

        if ($scope.openItem.desc2 != "") {
            const imgL2 = $scope.initialInputs["imgL2"];
            var fileLevel2 = $scope.courseFolder + '/badges/Level2/' + imgL2;

            // create and upload image for level 2
            layersL2 = isExtra ? [file, fileExtra, fileLevel2] : isBragging ? [file, fileBragging, fileLevel2] : [file, fileLevel2];

            imageLevel2 = mergeImages(layersL2)
                .then(b64 => $smartboards.request('settings', 'upload', { course: $scope.course, newFile: b64, fileName: filename + '-2.png', module: $scope.module.name, subfolder: $scope.openItem.name }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                }
                ));
            document.getElementById("img-badge-l2").src = $scope.courseFolder + "/badges/" + removeSpacefromName($scope.openItem.name) + "/" + filename + '-2.png';
            hideIfNeed('img-badge-l2');
        }

        if ($scope.openItem.desc3 != "") {
            const imgL3 = $scope.initialInputs["imgL3"];
            var fileLevel3 = $scope.courseFolder + '/badges/Level3/' + imgL3;
            // create and upload image for level 3 
            layersL3 = isExtra ? [file, fileExtra, fileLevel3] : isBragging ? [file, fileBragging, fileLevel3] : [file, fileLevel3];

            imageLevel3 = mergeImages(layersL3)
                .then(b64 => $smartboards.request('settings', 'upload', { course: $scope.course, newFile: b64, fileName: filename + '-3.png', module: $scope.module.name, subfolder: $scope.openItem.name }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                }
                ));
            document.getElementById("img-badge-l3").src = $scope.courseFolder + "/badges/" + removeSpacefromName($scope.openItem.name) + "/" + filename + '-3.png';
            hideIfNeed('img-badge-l3');
        }
    }

    $scope.addItem = function () {

        if ($('#open_item_action').is(':visible')) {
            $("#add_dep").remove();
            $("#dep_list").remove();
            $scope.openItem = {};
            if ($scope.module.name == "Skills") {
                $scope.openItem.dependenciesList = [];
                document.getElementById('close').onclick = function () {
                    closeModal(this);
                    resetSelectTextColor('select-item');
                }
            }
            //reset atributes fields
            jQuery.each($scope.listingItems.allAtributes, function (index) {
                atribute = $scope.listingItems.allAtributes[index];
                switch (atribute.type) {
                    case 'text':
                        $scope.openItem[atribute.id] = "";
                        break;
                    case 'select':
                        $scope.openItem[atribute.id] = "";
                        document.getElementById("select-item").style.color = "rgb(106,106,106)";
                        document.getElementById("select-item").onchange = function () {
                            changeSelectTextColor(this);
                            if ($('#add_dep').is(':visible')) {
                                $scope.showDepSection();
                            }
                        };
                        break;
                    case 'on_off button':
                        $scope.openItem[atribute.id] = 0;
                        $("#" + atribute.id)[0].checked = false;
                        break;
                    case 'date':
                        $scope.openItem[atribute.id] = "";
                        break;
                    case 'number':
                        $scope.openItem[atribute.id] = 0;
                        break;
                    case 'color':
                        const attr = atribute;
                        $scope.openItem[atribute.id] = "#000000";
                        const inputElement_colorPicker = $('#' + attr.id)[0];
                        const pickr = new Pickr({
                            el: inputElement_colorPicker,
                            useAsButton: true,
                            default: $scope.openItem[attr.id],
                            theme: 'monolith',
                            components: {
                                hue: true,
                                interaction: { input: true, save: true }
                            }
                        }).on('init', pickr => {
                            inputElement_colorPicker.value = pickr.getSelectedColor().toHEXA().toString(0);
                        }).on('save', color => {
                            inputElement_colorPicker.value = color.toHEXA().toString(0);
                            pickr.hide();
                        }).on('change', color => {
                            inputElement_colorPicker.value = color.toHEXA().toString(0);
                            color_sample = $('#' + attr.id + '-color-sample');
                            color_sample[0].children[0].style.backgroundColor = color.toHEXA().toString(0);
                            color_sample[0].children[1].style.borderColor = color.toHEXA().toString(0);
                            $scope.openItem[attr.id] = inputElement_colorPicker.value;
                        });
                        break;
                    case 'editor':
                        quill.setContents([{ insert: '\n' }]);
                        break;
                    case 'image':
                        $scope.openItem[atribute.id] = "";
                        // module badges
                        $(".config_input #text-badge").text("No file chosen");
                        $("#img-badge").hide();
                        $("#img-badge-l1").hide();
                        $("#img-badge-l2").hide();
                        $("#img-badge-l3").hide();
                        document.getElementById("badge").onchange = function () {
                            //$scope.uploadBadgeImage();
                        };
                        break;

                }
            })
            $("#open_item_action").text('New ' + $scope.listingItems.itemName + ': ');

        } else {
            $scope.openTier = {};
            //reset atributes fields
            jQuery.each($scope.tiers.allAtributes, function (index) {
                atribute = $scope.tiers.allAtributes[index];
                switch (atribute.type) {
                    case 'text':
                        $scope.openTier[atribute.id] = "";
                        break;
                    case 'number':
                        $scope.openTier[atribute.id] = 0;
                        break;
                }
            })
            $("#open_tier_action").text('New ' + $scope.tiers.itemName + ': ');
        }

        // $scope.isReadyToSubmit = function () {
        //     isValid = function (text) {
        //         return (text != "" && text != undefined && text != null)
        //     }

        //     //validate inputs
        //     if ($scope.openTier) {
        //         if (isValid($scope.openTier.tier) &&
        //             $scope.openTier.reward > 0) {
        //             return true;
        //         } else {
        //             return false;
        //         }
        //     } else {
        //         if (isValid($scope.openItem.tier) &&
        //             isValid($scope.openItem.name) &&
        //             isValid($scope.openItem.color)) {
        //             return true;
        //         } else {
        //             return false;
        //         }
        //     }
        // }



        $scope.submitItem = function () {
            if ($scope.openItem) {
                if ($scope.module.name == "Skills") {
                    $scope.openItem.dependencies = ""
                    $scope.openItem.dependenciesList.forEach(element => {
                        $scope.openItem.dependencies += element[0] + " + " + element[1] + " | ";
                    });
                    if ($scope.openItem.dependencies != "")
                        $scope.openItem.dependencies = $scope.openItem.dependencies.slice(0, -3);

                    $scope.openItem.description = quill.root.innerHTML;
                }

            }
            $smartboards.request('settings', 'saveModuleConfigInfo', { course: $scope.course, module: $stateParams.module, listingItems: $scope.openItem ? $scope.openItem : $scope.openTier, action_type: 'new' }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $smartboards.request('settings', 'getModuleConfigInfo', { course: $scope.course, module: $stateParams.module }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    $scope.listingItems.items = data.listingItems.items;
                });
            });

        }
    }

    $scope.editItem = function (item) {

        if ($('#open_item_action').is(':visible')) {
            $scope.openItem = {};
            $scope.openItem.id = item.id;


            $("#open_item_action").text('Edit ' + $scope.listingItems.itemName + ': ');

            jQuery.each($scope.listingItems.allAtributes, function (index) {
                atribute = $scope.listingItems.allAtributes[index];
                $scope.openItem[atribute.id] = item[atribute.id];
                //click on on/off inputs to visually change to on
                if (atribute["type"] == "on_off button") {
                    if (item[atribute.id] == "1" || item[atribute.id] == 'true' ||
                        item[atribute.id] == true || item[atribute.id] == 1) {
                        $("#" + atribute.id)[0].checked = true;
                    } else {
                        $("#" + atribute.id)[0].checked = false;
                    }
                } else if (atribute["type"] == "select") {
                    document.getElementById("select-item").style.color = "#333";
                    document.getElementById("select-item").onchange = function () {
                        if ($('#add_dep').is(':visible')) {
                            $scope.showDepSection();
                        }
                    };

                } else if (atribute["type"] == "editor") {
                    quill.setContents([{ insert: '\n' }]);
                    quill.clipboard.dangerouslyPasteHTML(item[atribute.id]);

                } else if (atribute["type"] == "image") {
                    //module badges
                    if ($scope.openItem[atribute.id] != "") {
                        document.getElementById("img-badge").src = $scope.courseFolder + "/badges/" + removeSpacefromName($scope.openItem.name) + "/" + $scope.openItem[atribute.id];
                        $("#img-badge").show();

                        filename = $scope.openItem[atribute.id].split(".")[0];

                        document.getElementById("img-badge-l1").src = $scope.courseFolder + "/badges/" + removeSpacefromName($scope.openItem.name) + "/" + filename + "-1.png";
                        $("#img-badge-l1").show();

                        if ($scope.openItem["desc2"] != "") {
                            document.getElementById("img-badge-l2").src = $scope.courseFolder + "/badges/" + removeSpacefromName($scope.openItem.name) + "/" + filename + "-2.png";
                            $("#img-badge-l2").show();
                        }
                        if ($scope.openItem["desc3"] != "") {
                            document.getElementById("img-badge-l3").src = $scope.courseFolder + "/badges/" + removeSpacefromName($scope.openItem.name) + "/" + filename + "-3.png";
                            $("#img-badge-l3").show();
                        }
                        $(".config_input #text-badge").text($scope.openItem[atribute.id]);
                    } else {
                        $(".config_input #text-badge").text("No file chosen");
                        hideIfNeed('img-badge');
                        hideIfNeed('img-badge-l1');
                        hideIfNeed('img-badge-l2');
                        hideIfNeed('img-badge-l3');
                    }
                    document.getElementById("badge").onchange = function () {
                        //$scope.uploadBadgeImage();
                        hideIfNeed('img-badge');
                        hideIfNeed('img-badge-l1');
                        hideIfNeed('img-badge-l2');
                        hideIfNeed('img-badge-l3');
                    };

                } else if (atribute["type"] == "color") {
                    const attr = atribute;
                    const inputElement_colorPicker = $('#' + attr.id)[0];
                    const pickr = new Pickr({
                        el: inputElement_colorPicker,
                        useAsButton: true,
                        default: item[attr.id],
                        theme: 'monolith',
                        components: {
                            hue: true,
                            interaction: { input: true, save: true }
                        }
                    }).on('init', pickr => {
                        inputElement_colorPicker.value = pickr.getSelectedColor().toHEXA().toString(0);
                    }).on('save', color => {
                        inputElement_colorPicker.value = color.toHEXA().toString(0);
                        pickr.hide();
                    }).on('change', color => {
                        inputElement_colorPicker.value = color.toHEXA().toString(0);
                        color_sample = $('#' + attr.id + '-color-sample');
                        color_sample[0].children[0].style.backgroundColor = color.toHEXA().toString(0);
                        color_sample[0].children[1].style.borderColor = color.toHEXA().toString(0);
                        $scope.openItem[attr.id] = inputElement_colorPicker.value;
                    });
                }

            });
            if ($scope.module.name == "Skills")
                $scope.showDepSection(false);
        } else {
            $scope.openTier = {};
            $scope.openTier.id = item.tier;
            jQuery.each($scope.tiers.allAtributes, function (index) {
                atribute = $scope.tiers.allAtributes[index];
                $scope.openTier[atribute.id] = item[atribute.id];
                if (atribute["type"] == "number")
                    $scope.openTier[atribute.id] = parseInt(item[atribute.id]);
            });
            $("#open_tier_action").text('Edit ' + $scope.tiers.itemName + ': ');
        }

        // $scope.isReadyToSubmit = function () {
        //     isValid = function (text) {
        //         return (text != "" && text != undefined && text != null)
        //     }

        //     //validate inputs
        //     if ($scope.openTier) {
        //         if (isValid($scope.openTier.tier) &&
        //             $scope.openTier.reward > 0) {
        //             return true;
        //         } else {
        //             return false;
        //         }
        //     } else {
        //         if (isValid($scope.openItem.tier) &&
        //             isValid($scope.openItem.name) &&
        //             isValid($scope.openItem.color)) {
        //             return true;
        //         } else {
        //             return false;
        //         }
        //     }


        // }


        $scope.submitItem = function () {
            if ($scope.openItem) {
                if ($scope.module.name == "Skills") {
                    $scope.openItem.description = quill.root.innerHTML;

                    $scope.openItem.dependencies = ""
                    $scope.openItem.dependenciesList.forEach(element => {
                        $scope.openItem.dependencies += element[0] + " + " + element[1] + " | ";
                    });
                    if ($scope.openItem.dependencies != "")
                        $scope.openItem.dependencies = $scope.openItem.dependencies.slice(0, -3);
                }
                if ($scope.module.name == "Badges") {
                    baseImage = $scope.courseFolder + "/badges/" + removeSpacefromName($scope.openItem.name) + "/" + $scope.openItem.image;
                    $scope.buildMergeImages(baseImage);
                }


            }
            $smartboards.request('settings', 'saveModuleConfigInfo', { course: $scope.course, module: $stateParams.module, listingItems: $scope.openItem ? $scope.openItem : $scope.openTier, action_type: 'edit' }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $smartboards.request('settings', 'getModuleConfigInfo', { course: $scope.course, module: $stateParams.module }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    $scope.listingItems.items = data.listingItems.items;
                });
            });

        }
    };

    $scope.isAddDepEnabled = function () {
        isValid = function (text) {
            return (text != "" && text != undefined && text != null)
        }
        if ($scope.openItem) {
            var firstTier = $scope.tiers.items.filter(function (el) {
                return el.seqId == 1;
            })[0];
            if (isValid($scope.openItem.tier) && $scope.openItem.tier != firstTier.tier && !($("#add_dep").is(':visible'))) {
                return true;
            } else {
                return false;
            }
        }
    };

    $scope.addDependency = function () {
        firstSelector = $("#dependency1")[0];
        secondSelector = $("#dependency2")[0];
        firstSkill = firstSelector.options[firstSelector.selectedIndex].value;
        secondSkill = secondSelector.options[secondSelector.selectedIndex].value;
        if (firstSkill == secondSkill) {
            giveMessage("You have to choose 2 different skills.");
            return;
        }

        $scope.openItem.dependenciesList.push([firstSkill, secondSkill]);
        $scope.showDepSection(false);
    };
    $scope.removeDependency = function (dependencyIdx) {
        array = $scope.openItem.dependenciesList;

        array.splice(dependencyIdx, 1);
        //scope array keeps info
        $scope.showDepSection(false);
    };

    $scope.removeAddForm = function () {
        $("#add_dep").remove();
    };

    $scope.showDepSection = function (addForm = true) {
        var currentTier = $scope.tiers.items.filter(function (el) {
            return el.tier == $scope.openItem.tier;
        })[0];

        var tierToSeqId = Object.assign({}, ...$scope.tiers.items.map((x) => ({
            [x.tier]: x.seqId
        })));

        var skillsForDep = $scope.listingItems.items.filter(function (el) {
            // get the seqid of each skill
            return parseInt(tierToSeqId[el.tier]) == parseInt(currentTier.seqId) - 1;
        }).map(el => el.name);

        if (parseInt(currentTier.seqId) != 1) {
            var previousTier = $scope.tiers.items.filter(function (el) {
                return parseInt(el.seqId) == parseInt(currentTier.seqId) - 1;
            })[0];
            var isThereWildcardTier = $scope.tiers.items.filter(function (el) {
                return parseInt(el.reward) == parseInt(previousTier.reward) && el.tier != previousTier.tier;
            });
            if (isThereWildcardTier.length != 0) {
                wildcardTier = isThereWildcardTier[0].tier;
                allDependencies = skillsForDep.concat(wildcardTier);
            } else {
                allDependencies = skillsForDep;
            }
            updateDependenciesAddSection($scope, "#new_box", allDependencies, addForm);
        } else {
            updateDependenciesAddSection($scope, "#new_box", skillsForDep, addForm);
        }



    };

    $scope.deleteItem = function (item) {

        if ($('#delete_action_info').is(':visible')) {
            $scope.openItem = {};
            $scope.openItem.id = item.id;
            $('#delete_action_info').text($scope.listingItems.itemName + ': ' + $scope.openItem.id);
        } else {
            $scope.openTier = {};
            $scope.openTier.id = item.tier;
            $scope.openTier.reward = parseInt(item["reward"]);
            $('#delete_tier_info').text($scope.tiers.itemName + ': ' + $scope.openTier.id);
        }

        $scope.confirmDelete = function () {
            $smartboards.request('settings', 'saveModuleConfigInfo', { course: $scope.course, module: $stateParams.module, listingItems: $scope.openItem ? $scope.openItem : $scope.openTier, action_type: 'delete' }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $smartboards.request('settings', 'getModuleConfigInfo', { course: $scope.course, module: $stateParams.module }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    $scope.listingItems.items = data.listingItems.items;
                });
            });

        }
    }

    $scope.showPreview = function () {
        $('#preview_content').empty();
        $('#preview_content').append($compile(Builder.createPageBlock({
            'image': 'images/skills.svg',
            'text': 'Skill - {{openItem.name}}'
        }, function (el) {
            el.attr('class', 'content');
            el.append($('<div>', { 'class': 'text-content', 'bind-trusted-html': 'openItem.description' }));
        }))($scope));
    };


    $scope.importItems = function (replace) {
        $scope.importedItems = null;
        $scope.replaceItems = replace;
        var fileInput = document.getElementById('import_item');
        var file = fileInput.files[0];
        var reader = new FileReader();

        reader.onload = function (e) {
            $scope.importedItems = reader.result;
            $smartboards.request('settings', 'importItem', { course: $scope.course, module: $stateParams.module, file: $scope.importedItems, replace: $scope.replaceItems }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                nItems = data.nItems;
                $("#import-item").hide();
                fileInput.value = "";
                location.reload();
                $("#action_completed").empty();
                if (nItems > 1)
                    $("#action_completed").append(nItems + " Items Imported");
                else
                    $("#action_completed").append(nItems + " Item Imported");
                $("#action_completed").show().delay(3000).fadeOut();
            });

        }
        reader.readAsDataURL(file);
    }
    $scope.exportItem = function () {
        $smartboards.request('settings', 'exportItem', { course: $scope.course, module: $stateParams.module }, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            download(data.fileName + ".txt", data.courseItems);
        });

    }

    $scope.moveUp = function (row) {
        var item = row.item ? row.item : row.tier;
        var index = parseInt(row.$index);
        var newIdx = index - 1;
        var changed = false;

        if (item.reward != undefined) {
            if (newIdx >= 0 && newIdx <= $scope.tiers.items.length - 1) {
                if (index - 1 == 0) {
                    if (confirm("You are moving this tier to the first position. If you continue, all the dependencies of its skills will be deleted. Are you sure you want to continue?")) {
                        // changed = moveRow("tier-table", index, index - 1);
                        $scope.tiers.items.splice(newIdx, 2, item, $scope.tiers.items[newIdx]);
                        changed = true;
                    }

                } else {
                    $scope.tiers.items.splice(newIdx, 2, item, $scope.tiers.items[newIdx]);
                    changed = true;
                }
            }

        } else {
            var myTier = item.tier;
            if (newIdx >= 0 && newIdx <= $scope.listingItems.items.length - 1 && $scope.listingItems.items[newIdx].tier == myTier) {
                $scope.listingItems.items.splice(newIdx, 2, item, $scope.listingItems.items[newIdx]);
                changed = true;
            }

        }

        if (changed) {
            $smartboards.request('settings', 'saveNewSequence', { course: $scope.course, module: $stateParams.module, itemId: item.reward ? item.tier : item.id, oldSeq: index, nextSeq: index - 1, table: item.reward ? "tier" : "skill" }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
            });

        }

    }

    $scope.moveDown = function (row) {
        var item = row.item ? row.item : row.tier;
        var index = parseInt(row.$index);
        var newIdx = index + 1;
        var changed = false;


        if (item.reward != undefined) {

            if (newIdx >= 0 && newIdx <= $scope.tiers.items.length - 1) {
                $scope.tiers.items.splice(index, 2, $scope.tiers.items[newIdx], item);
                changed = true;
            }
        } else {
            // var idx = parseInt(row.$index);
            // var newIdx = indxx + 1;
            var myTier = item.tier;

            if (newIdx >= 0 && newIdx <= $scope.listingItems.items.length - 1 && $scope.listingItems.items[newIdx].tier == myTier) {
                $scope.listingItems.items.splice(index, 2, $scope.listingItems.items[newIdx], item);
                changed = true;
            }
        }
        if (changed) {
            $smartboards.request('settings', 'saveNewSequence', { course: $scope.course, module: $stateParams.module, itemId: item.reward ? item.tier : item.id, oldSeq: index, nextSeq: index + 1, table: item.reward ? "tier" : "skill" }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
            });
        }

    }

    $smartboards.request('settings', 'getModuleConfigInfo', { course: $scope.course, module: $stateParams.module }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        $scope.courseFolder = data.courseFolder;
        //page title
        $scope.module = data.module;
        configPage = $("<div id='configPage'></div>");
        $element.append(configPage);

        header = $("<div class='header'></div>");
        header.append($('<div id="name">{{module.name}} Configuration</div><div id="description">{{module.description}}</div>'))
        $compile(header)($scope);
        configPage.append(header);



        //general inputs section
        $scope.generalInputs = data.generalInputs;
        if ($scope.generalInputs.length != 0) {
            generalInputsDiv = createSection(configPage, 'General Attributes');
            generalInputsDiv.attr("class", "multiple_inputs content");

            $scope.inputs = {};
            $scope.initialInputs = {};
            jQuery.each($scope.generalInputs, function (index) {
                input = $scope.generalInputs[index];
                row = $("<div class='row'></div>");
                row.append('<span >' + input.name + '</span>');
                switch (input.type) {
                    case 'text':
                        row.append('<input class="config_input" type="text"  ng-model="inputs.' + input.id + '"><br>');
                        break;
                    case 'select':
                        select = $('<select class="config_input" ng-model="inputs.' + input.id + '"></select>');
                        jQuery.each(input.options, function (index) {
                            option = input.options[index];
                            select.append($('<option value="' + option + '">' + option + '</option>'))
                        });
                        row.append(select);
                        break;
                    case 'on_off button':
                        row.append($('<div class="on_off"><label class="switch"><input type="checkbox" ng-model="inputs.' + input.id + '"><span class="slider round"></span></label></div>'))
                        break;
                    case 'date':
                        row.append('<input class="config_input" type="date"  ng-model="inputs.' + input.id + '"><br>');
                        break;
                    case 'number':
                        row.append('<input class="config_input" type="number"  ng-model="inputs.' + input.id + '"><br>');
                        break;
                    case 'paragraph':
                        row.append('<textarea class="config_input"  ng-model="inputs.' + input.id + '"><br>');
                        break;
                    case 'color':
                        color_picker_section = $('<div class="color_picker"></div>');
                        color_picker_section.append($('<input type="text" class="config_input pickr" id="' + input.id + '" placeholder="Color *" ng-model="inputs.' + input.id + '"/>'));
                        color_picker_section.append($('<div id="' + input.id + '-color-sample" class="color-sample"><div class="box" style="background-color: ' + input.current_val + ';"></div><div  class="frame" style="border: 1px solid ' + input.current_val + '"></div>'));
                        row.append(color_picker_section);
                        generalInputsDiv.append(row);

                        const inputElement_colorPicker = $('#' + input.id)[0];
                        const pickr = new Pickr({
                            el: inputElement_colorPicker,
                            useAsButton: true,
                            default: input.current_val,
                            theme: 'monolith',
                            components: {
                                hue: true,
                                interaction: { input: true, save: true }
                            }
                        }).on('init', pickr => {
                            inputElement_colorPicker.value = pickr.getSelectedColor().toHEXA().toString(0);
                        }).on('save', color => {
                            inputElement_colorPicker.value = color.toHEXA().toString(0);
                            pickr.hide();
                        }).on('change', color => {
                            inputElement_colorPicker.value = color.toHEXA().toString(0);
                            color_sample = $('#' + input.id + '-color-sample');
                            color_sample[0].children[0].style.backgroundColor = color.toHEXA().toString(0);
                            color_sample[0].children[1].style.borderColor = color.toHEXA().toString(0);
                            $scope.inputs[input.id] = input.current_val;
                        })
                        break;
                    case 'image':
                        row.append($('<div class="full" ><div class="badge_image">' +
                            '<div class="config_input" style="flex: none;width: 230px;"><input style="display: none;" id="' + input.id + '" type="file" class="form__input" /> ' +
                            '<input type="button" value="Choose File" ng-click="openPickerModal(\'' + input.id + '\', [\'.png\', \'.jpg\', \'.jpeg\']);" />' +
                            '<span id="text-' + input.id + '" > </span></div> <img title="' + input.name + '" class="icon" id="img-' + input.id + '"/></div></div>'));

                        break;
                }


                generalInputsDiv.append(row);
                $scope.inputs[input.id] = input.current_val;
                $scope.initialInputs[input.id] = input.current_val; //duplicated value to keep track of changes


            });

            $compile(generalInputsDiv)($scope);

            jQuery.each($scope.generalInputs, function (index) {
                input = $scope.generalInputs[index];
                if (input.type == "image") {
                    if (input.current_val != "") {
                        $(".config_input #text-" + input.id).text(input.current_val);
                        document.getElementById("img-" + input.id).src = $scope.courseFolder + "/badges/" + input.options + "/" + input.current_val;
                    }
                    else {
                        $(".config_input #text-" + input.id).text("No file chosen");
                    }
                    hideIfNeed("img-" + input.id);
                }
            });




            //save button for generel inputs
            action_buttons = $("<div class='config_save_button'></div>");
            action_buttons.append($('<button>', { id: 'general-inputs-save-button', text: "Save Changes", 'class': 'button', 'disabled': true, 'ng-disabled': '!generalInputsChanged()', 'ng-click': 'saveDataGeneralInputs()' }))
            $compile(action_buttons)($scope);
            generalInputsDiv.append(action_buttons);
        }


        if (data.module.name == "Skills") {
            $scope.tiers = data.tiers;

            allTiers = createSection(configPage, $scope.tiers.listName);
            allTiers.attr('id', 'allTiers')
            allTiersSection = $('<div class="data-table"></div>');
            tableTiers = $('<table id="tier-table"></table>');
            rowHeaderTiers = $("<tr></tr>");
            jQuery.each($scope.tiers.header, function (index) {
                header = $scope.tiers.header[index];
                rowHeaderTiers.append($("<th>" + header + "</th>"));
            });
            rowHeaderTiers.append($("<th class='action-column'></th>")); // edit
            rowHeaderTiers.append($("<th class='action-column'></th>")); // delete
            rowHeaderTiers.append($("<th class='action-column'></th>")); // move up
            rowHeaderTiers.append($("<th class='action-column'></th>")); // move down

            rowContentTiers = $("<tr ng-repeat='(i, tier) in tiers.items' id='tier-{{tier.tier}}'> ></tr>");
            jQuery.each($scope.tiers.displayAtributes, function (index) {
                atribute = $scope.tiers.displayAtributes[index];
                stg = "tier." + atribute;
                rowContentTiers.append($('<td>{{' + stg + '}}</td>'));
            });
            rowContentTiers.append('<td class="action-column"><div class="icon edit_icon" value="#open-tier" onclick="openModal(this)" ng-click="editItem(item)"></div></td>');
            rowContentTiers.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification-tier" onclick="openModal(this)" ng-click="deleteItem(tier)"></div></td>');
            rowContentTiers.append('<td class="action-column"><div class="icon up_icon" title="Move up" ng-click="moveUp(this)"></div></td>');
            rowContentTiers.append('<td class="action-column"><div class="icon down_icon" title="Move down" ng-click="moveDown(this)"></div></td>');

            //append table
            tableTiers.append(rowHeaderTiers);
            tableTiers.append(rowContentTiers);
            allTiersSection.append(tableTiers);
            allTiers.append(allTiersSection);
            $compile(allTiers)($scope);

            //add and edit tier modal
            modalTiers = $("<div class='modal' id='open-tier'></div>");
            open_itemTiers = $("<div class='modal_content'></div>");
            open_itemTiers.append($('<button class="close_btn icon" value="#open-tier" onclick="closeModal(this)"></button>'));
            open_itemTiers.append($('<div class="title" id="open_tier_action"></div>'));
            contentTiers = $('<div class="content">');
            boxTiers = $('<div id="new_box_tier" class= "inputs">');
            row_inputsTiers = $('<div class= "row_inputs"></div>');

            detailsTiers = $('<div class="details full config_item"></div>');
            jQuery.each($scope.tiers.allAtributes, function (index) {
                atribute = $scope.tiers.allAtributes[index];
                switch (atribute.type) {
                    case 'text':
                        detailsTiers.append($('<div class="half"><div class="container"><input type="text" class="form__input" placeholder="' + atribute.name + '" ng-model="openTier.' + atribute.id + '"/> <label for="' + atribute.id + '" class="form__label">' + atribute.name + '</label></div></div>'))
                        break;
                    case 'number':
                        detailsTiers.append($('<div class="half"><div class="container"><input type="number" class="form__input"  ng-model="openTier.' + atribute.id + '"/> <label for="' + atribute.id + '" class="form__label number_label">' + atribute.name + '</label></div></div>'))
                        break;
                }
            });
            row_inputsTiers.append(detailsTiers);
            boxTiers.append(row_inputsTiers);
            contentTiers.append(boxTiers);
            contentTiers.append($('<button class="cancel" value="#open-tier" onclick="closeModal(this)" > Cancel </button>'))
            contentTiers.append($('<button class="save_btn" value="#open-tier" onclick="closeModal(this)" ng-click="submitItem()"> Save </button>'))
            open_itemTiers.append(contentTiers);
            modalTiers.append(open_itemTiers);
            $compile(modalTiers)($scope);
            allTiers.append(modalTiers);


            //delete verification modal
            deletemodal = $("<div class='modal' id='delete-verification-tier'></div>");
            verification = $("<div class='verification modal_content'></div>");
            verification.append($('<button class="close_btn icon" value="#delete-verification-tier" onclick="closeModal(this)"></button>'));
            verification.append($('<div class="warning">Are you sure you want to delete?</div>'));
            verification.append($('<div class="target" id="delete_tier_info"></div>'));
            verification.append($('<div class="confirmation_btns"><button class="cancel" value="#delete-verification-tier" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="confirmDelete()"> Delete</button></div>'))
            deletemodal.append(verification);
            rowContentTiers.append(deletemodal);


            //success section
            allTiers.append($("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

            action_buttonsTier = $("<div class='action-buttons' style='width:30px;'></div>");
            action_buttonsTier.append($("<div class='icon add_icon' value='#open-tier' onclick='openModal(this)' ng-click='addItem()'></div>"));
            allTiers.append($compile(action_buttonsTier)($scope));

        }


        //personalized configuration Section
        if (data.personalizedConfig.length != 0) {
            functionName = data.personalizedConfig;
            window[functionName]($scope, configPage, $smartboards, $compile);
        }

        //listing items section
        if (data.listingItems.length != 0) {
            $scope.listingItems = data.listingItems;

            allItems = createSection(configPage, $scope.listingItems.listName);
            allItems.attr('id', 'allItems')
            allItemsSection = $('<div class="data-table"></div>');
            table = $('<table id="listing-table"></table>');
            rowHeader = $("<tr></tr>");
            jQuery.each($scope.listingItems.header, function (index) {
                header = $scope.listingItems.header[index];
                rowHeader.append($("<th>" + header + "</th>"));
            });
            rowHeader.append($("<th class='action-column'></th>"));
            rowHeader.append($("<th class='action-column'></th>"));
            if (data.module.name == "Skills") {
                rowHeader.append($("<th class='action-column'></th>")); // move up
                rowHeader.append($("<th class='action-column'></th>")); // move down
            }

            rowContent = $("<tr ng-repeat='(i, item) in listingItems.items' id='item-{{item.id}}'> ></tr>");
            jQuery.each($scope.listingItems.displayAtributes, function (index) {
                atribute = $scope.listingItems.displayAtributes[index];
                stg = "item." + atribute;
                if (atribute == "color") {
                    rowContent.append($('<td><div style="display:flex;justify-content:flex-start;"><div class="color-sample" style="margin-right:8px"><div class="box" style="border:none;background-color:{{ ' + stg + '}};width:20px;height:20px;opacity:1;"></div></div><div>{{' + stg + '}}</div></td>'));
                } else if (atribute == "image") {
                    path = $scope.courseFolder + "/badges/";
                    rowContent.append($('<td ng-if="!item.image"><img src="images/no-image.png" class="badges"/></td>'));
                    rowContent.append($('<td ng-if="item.image"><img src="' + path + '{{' + "item.name" + '}}' + "/" + '{{' + stg + '}}" title="{{ ' + stg + '}}" class="badges"/></td>'));
                } else {
                    rowContent.append($('<td>{{' + stg + '}}</td>'));
                }
            });
            rowContent.append('<td class="action-column"><div class="icon edit_icon" value="#open-item" onclick="openModal(this)" ng-click="editItem(item)"></div></td>');
            rowContent.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification" onclick="openModal(this)" ng-click="deleteItem(item)"></div></td>');
            if (data.module.name == "Skills") {
                rowContent.append('<td class="action-column"><div class="icon up_icon" title="Move up" ng-click="moveUp(this)"></div></td>');
                rowContent.append('<td class="action-column"><div class="icon down_icon" title="Move down" ng-click="moveDown(this)"></div></td>');
            }
            //append table
            table.append(rowHeader);
            table.append(rowContent);
            allItemsSection.append(table);
            allItems.append(allItemsSection);
            $compile(allItems)($scope);

            //add and edit item modal
            modal = $("<div class='modal' id='open-item'></div>");
            open_item = $("<div class='modal_content'></div>");
            if (data.module.name == "Skills")
                open_item.css("width", "650px");
            open_item.append($('<button class="close_btn icon" id="close" value="#open-item" onclick="closeModal(this);"></button>'));
            open_item.append($('<div class="title" id="open_item_action"></div>'));
            content = $('<div class="content">');
            box = $('<div id="new_box" class= "inputs">');
            row_inputs = $('<div class= "row_inputs"></div>');

            $scope.listingItems.allAtributes
            details = $('<div class="details full config_item"></div>');
            jQuery.each($scope.listingItems.allAtributes, function (index) {
                atribute = $scope.listingItems.allAtributes[index];
                switch (atribute.type) {
                    case 'text':
                        details.append($('<div class="half"><div class="container"><input type="text" class="form__input" placeholder="' + atribute.name + '" ng-model="openItem.' + atribute.id + '"/><label for="' + atribute.id + '" class="form__label">' + atribute.name + '</label></div></div>'))
                        break;
                    case 'select':
                        select_box = $('<div class="options_box half"></div>');
                        select_box.append($('<span >' + atribute.name + ': </span>'))
                        select = $('<select class="form__input" id="select-item" ng-model="openItem.' + atribute.id + '"></select>');
                        select.append($('<option value="" disabled>Select a ' + atribute.name + '</option>'));
                        jQuery.each(atribute.options, function (index) {
                            option = atribute.options[index];
                            select.append($('<option value="' + option + '">' + option + '</option>'))
                        });
                        select_box.append(select);
                        details.append(select_box);
                        break;
                    case 'on_off button':
                        details.append($('<div class= "on_off"><span>' + atribute.name + ' </span><label class="switch"><input id="' + atribute.id + '" type="checkbox" ng-model="openItem.' + atribute.id + '"><span class="slider round"></span></label></div>'))
                        break;
                    // case 'button':
                    //     details.append($('<div class="half"><button class="btn" ng-click="addDepRow()" ng-disabled="!isTierSet()"><img class="icon" src="./images/add_icon.svg"/><span style="color:white;padding:5px;font-weight:600;">Add ' + atribute.name + ' </span></button></div>'));
                    //     break;
                    case 'date':
                        details.append('<div class="half"><div class="container"><input type="date" class="form__input"  ng-model="openItem.' + atribute.id + '"><label for="' + atribute.id + '" class="form__label date_label">' + atribute.name + '</label></div></div>');
                        break;
                    case 'number':
                        details.append($('<div class="half"><div class="container"><input type="number" class="form__input"  ng-model="openItem.' + atribute.id + '"/> <label for="' + atribute.id + '" class="form__label number_label">' + atribute.name + '</label></div></div>'))
                        break;
                    case 'color':
                        color_picker_section = $('<div class="color_picker half"></div>');
                        color_picker_section.append($('<input type="text" class="config_input pickr" id="' + atribute.id + '" placeholder="Color *" ng-model="openItem.' + atribute.id + '"/><label for="color" class="form__label">Color</label>'));
                        color_picker_section.append($('<div id="' + atribute.id + '-color-sample" class="color-sample"><div class="box" style="background-color: {{openItem.' + atribute.id + '}}";"></div><div  class="frame" style="border: 1px solid {{openItem.' + atribute.id + '}}"></div>'));
                        details.append(color_picker_section);
                        break;
                    case 'editor':
                        editor_container = $('<div class="editor_container"></div>');
                        editor = $('<div id="editor"></div>');
                        editor_container.append(editor);
                        break;
                    case 'image':
                        details.append($('<div class="full" ><div class="badge_image"><span>' + atribute.name + ' </span> ' +
                            '<div class="config_input" style="flex: none;width: 230px;"><input style="display: none;" id="badge" type="file" class="form__input"/> ' +
                            '<input  type="button" value="Choose File" ng-click="openPickerModal(\'badge\', [\'.png\', \'.jpg\', \'.jpeg\']);" />' +
                            '<span id="text-badge"> </span></div> <img title="Base image" class="icon" id="img-badge" /><img title="Level 1" class="icon" id="img-badge-l1" /><img title="Level 2" class="icon" id="img-badge-l2" /><img title="Level 3" class="icon" id="img-badge-l3" /></div ></div > '));
                        break;


                }
            });
            row_inputs.append(details);
            box.append(row_inputs);

            if (data.module.name == "Skills" || data.module.name == "Badges") {
                $smartboards.request('course', 'getDataFolders', { course: $scope.course }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    $scope.folders = data.folders;
                    $scope.path = $scope.courseFolder;
                    modal_picker = buildImagePicker($scope, $compile);


                    allItems.append(modal_picker);
                    //allItems.append(deletemodal);

                });

            }

            if (data.module.name == "Skills") {

                //add dependency button
                box.append($('<div class="half" id="dependency"><button class="btn" ng-click="showDepSection()" ng-disabled="!isAddDepEnabled()"><img class="icon" src="./images/add_icon.svg"/><span style="color:white;padding:5px;font-weight:600;">Add Dependency</span></button></div>'));
                //add editor
                box.append(editor_container);
                Quill.register("modules/htmlEditButton", htmlEditButton);
                quill = new Quill(editor[0], {
                    modules: {
                        toolbar: [
                            [{ 'font': [] }, { header: [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline'],
                            [{ 'script': 'sub' }, { 'script': 'super' }],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'align': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }, { 'indent': '-1' }, { 'indent': '+1' }],
                            ['link', 'image', 'video', 'code-block'],
                            // dropdown with defaults from theme

                        ],
                        imageResize: {},
                        htmlEditButton: {}
                    },
                    scrollingContainer: '#editor',
                    placeholder: 'Add the skill description here...',
                    theme: 'snow'
                });

                quill.getModule("toolbar").addHandler("image", $scope.openPickerModal);
                //modal_picker = buildImagePicker($scope, $compile);

            }


            content.append(box);
            if (data.module.name == "Skills")
                content.append($('<button class="preview" value="#open-preview" ng-click="showPreview()" onclick="openModal(this)"> Preview </button>'))
            content.append($('<button class="cancel" value="#open-item" onclick="closeModal(this)" > Cancel </button>'))
            content.append($('<button class="save_btn" value="#open-item" onclick="closeModal(this)" ng-click="submitItem()"> Save </button>'))
            open_item.append(content);
            modal.append(open_item);
            $compile(modal)($scope);
            allItems.append(modal);
            //allItems.append(modal_picker);

            // preview modal
            previewModal = $("<div class='modal' id='open-preview'></div>");
            open_preview = $("<div class='modal_content' style='width:90%;'></div>");
            open_preview.append($('<button class="close_btn icon" id="close" value="#open-preview" onclick="closeModal(this);"></button>'));
            open_preview.append($('<div class="title"> Preview for Skill: {{openItem.name}}</div>'));
            previewContent = $('<div class="content" id="preview_content">');
            open_preview.append(previewContent);
            previewModal.append(open_preview);
            $compile(previewModal)($scope);
            allItems.append(previewModal);

            //import items modal
            importModal = $("<div class='modal' id='import-item'></div>");
            importVerification = $("<div class='verification modal_content'></div>");
            importVerification.append($('<button class="close_btn icon" value="#import-item" onclick="closeModal(this)"></button>'));
            importVerification.append($('<div class="warning">Please select a .csv or .txt file to be imported</div>'));
            importVerification.append($('<div class="target">The seperator must be comma</div>'));
            importVerification.append($('<input class="config_input" type="file" id="import_item" accept=".csv, .txt">')); //input file
            importVerification.append($('<div class="confirmation_btns"><button ng-click="importItems(true)">Replace duplicates</button><button ng-click="importItems(false)">Ignore duplicates</button></div>'))
            importModal.append(importVerification);
            $compile(importModal)($scope);
            allItems.append(importModal);


            //delete verification modal
            deletemodal = $("<div class='modal' id='delete-verification'></div>");
            verification = $("<div class='verification modal_content'></div>");
            verification.append($('<button class="close_btn icon" value="#delete-verification" onclick="closeModal(this)"></button>'));
            verification.append($('<div class="warning">Are you sure you want to delete</div>'));
            verification.append($('<div class="target" id="delete_action_info"></div>'));
            verification.append($('<div class="confirmation_btns"><button class="cancel" value="#delete-verification" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="confirmDelete()"> Delete</button></div>'))
            deletemodal.append(verification);
            rowContent.append(deletemodal);


            //success section
            allItems.append($("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

            action_buttons = $("<div class='action-buttons'></div>");
            action_buttons.append($("<div class='icon add_icon' value='#open-item' onclick='openModal(this)' ng-click='addItem()'></div>"));
            action_buttons.append($("<div class='icon import_icon' value='#import-item' onclick='openModal(this)'></div>"));
            action_buttons.append($("<div class='icon export_icon' value='#export-item' ng-click='exportItem()'></div>"));
            allItems.append($compile(action_buttons)($scope));
        }

        if (data.module.name == "Skills") {
            var configurationSection = createSection(configPage, 'Skill Tree');

            var configSectionContent = $('<div>', { 'class': 'row' });

            //Display skill Tree info (similar to how it appears on the profile)
            var dataArea = $('<div>', { 'class': 'row', style: 'float: left; width: 100%;' });

            var numTiers = data.tiers.items.length;
            for (t in data.tiers.items) {
                var tier = data.tiers.items[t];

                var skills = data.listingItems.items.filter(function (el) {
                    return el.tier == tier.tier;
                });
                skills.sort(function (a, b) {
                    return a.seqId - b.seqId;
                })

                var width = 100 / numTiers;

                var tierArea = $('<div>', { class: "block tier column", text: "Tier " + tier.tier + ":\t" + tier.reward + " XP", style: 'float: left; width: ' + width + '%;' });

                for (var i = 0; i < skills.length; i++) {
                    var skill = skills[i];

                    var skillBlock = $('<div>', { class: "block skill", style: "background-color: " + skill.color + "; color: #ffffff; width: 60px; height:60px" });
                    skillBlock.append('<span style="font-size: 80%;overflow-wrap:anywhere;">' + skill.name + '</span>');
                    tierArea.append(skillBlock);

                    if ('dependencies' in skill) {
                        for (var d in skill.dependenciesList) {
                            var deps = '<span style="font-size: 70%">';
                            for (var dElement in skill.dependenciesList[d]) {
                                deps += skill.dependenciesList[d][dElement] + ' + ';
                            }
                            deps = deps.slice(0, -2);
                            deps += '</span><br>';
                            tierArea.append(deps);
                        }

                    }
                }
                dataArea.append(tierArea);
            }
            configSectionContent.append(dataArea);
            configurationSection.append(configSectionContent);
            $compile(configurationSection)($scope);
        }
    });



});


// old version, not used anymore
/*app.controller('CourseSkillsSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {

    $scope.replaceData = function (arg) {
        if (confirm("Are you sure you want to replace all the Skills with the ones on the input box?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, skillsList: arg }, alertUpdate);
    };
    $scope.replaceTier = function (arg) {
        if (confirm("Are you sure you want to replace all the Tiers with the ones on the input box?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, tiersList: arg }, alertUpdate);
    };
    $scope.replaceNumber = function (arg) {
        if (confirm("Are you sure you want to change the Maximum Tree XP?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, maxReward: arg }, alertUpdate);
    };
    $scope.addData = function (arg) { //Currently not being used
        $smartboards.request('settings', 'courseSkills', { course: $scope.course, newSkillsList: arg }, alertUpdate);
    };
    $scope.clearData = function () {
        clearFillBox($scope);
    };
    $scope.clearTier = function () { //clear textarea of the tiers
        if ($scope.tierList !== "")
            $scope.tierList = "";
        else if ("file2" in $scope.data)
            $scope.tierList = $scope.data.file2;
    };

    $smartboards.request('settings', 'courseSkills', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }
        var text = "Skills must be in the following format: tier;name;dep1A+dep1B|dep2A+dep2B;color;XP";

        $scope.data = data;
        var tabContent = $($element);
        var configurationSection = createSection(tabContent, 'Manage Skills');

        var configSectionContent = $('<div>', { 'class': 'row' });

        //Display skill Tree info (similar to how it appears on the profile)
        var dataArea = $('<div>', { 'class': 'column row', style: 'float: left; width: 55%;' });

        var numTiers = Object.keys(data.skillsList).length;
        for (t in data.skillsList) {

            var tier = data.skillsList[t];
            var width = 100 / numTiers - 2;

            var tierArea = $('<div>', { class: "block tier column", text: "Tier " + t + ":\t" + tier.reward + " XP", style: 'float: left; width: ' + width + '%;' });

            for (var i = 0; i < tier.skills.length; i++) {
                var skill = tier.skills[i];

                var skillBlock = $('<div>', { class: "block skill", style: "background-color: " + skill.color + "; color: #ffffff; width: 60px; height:60px" });
                skillBlock.append('<span style="font-size: 80%;">' + skill.name + '</span>');
                tierArea.append(skillBlock);

                if ('dependencies' in skill) {
                    for (var d in skill.dependencies) {
                        var deps = '<span style="font-size: 70%">';
                        for (var dElement in skill.dependencies[d]) {
                            deps += skill.dependencies[d][dElement] + ' + ';
                        }
                        deps = deps.slice(0, -2);
                        deps += '</span><br>';
                        tierArea.append(deps);
                    }
                }
            }
            dataArea.append(tierArea);
        }
        configSectionContent.append(dataArea);


        $scope.newList = data.file;
        var bigBox = constructTextArea($compile, $scope, "Skill", text, 45);

        $scope.tierList = data.file2;
        var bigBox2 = constructTextArea($compile, $scope, "Tier", "Tier must be in the following formart: tier;XP",
            100, "tierList", 5);
        $scope.maxReward = data.maxReward;
        var numInput = constructNumberInput($compile, $scope, "Maximum Skill Tree Reward", "maxReward", "Max Reward");
        //        $('<div>',{'class': 'column', style: 'float: right; width: 100%;',text:"Maximum Skill Tree Reward: "});
        //numInput.append('<br><input type:"number" style="width: 25%" id="newList" ng-model="maxReward">');
        //numInput.append('<button class="button small" ng-click="replaceMax(maxReward)">Save Max Reward</button>');
        //ToDo: add this button (and delete) back after the system stops using TXTs from legacy_folder
        //If TXT files are used it's difficult to keep them synced w DB and have the Add/Edit functionality
        //if (name!=="Level")
        //    bigBox.append('<button class="button small" ng-click="addData(newList)">Add/Edit '+name+'s </button>');
        //ng-disabled="!isValidString(inviteInfo.id) "
        // numInput.append('<button class="button small" ng-click="clear'+funName+'()" style="float: right;">Clear/Fill Box</button>');
        //bigBox.append('</div>');//<button ng-disabled="!isValidString(inviteInfo.id) || !isValidString(inviteInfo.username)" ng-click="createInvite()">Create</button></div>');

        bigBox.append(bigBox2);
        bigBox.append(numInput);
        configSectionContent.append(bigBox);
        configurationSection.append(configSectionContent);
    });
});*/
app.controller('CourseBadgesSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    //old version, not used, only here for verification

    $scope.replaceData = function (arg) {
        if (confirm("Are you sure you want to replace all the Badges with the ones on the input box?"))
            $smartboards.request('settings', 'courseBadges', { course: $scope.course, badgesList: arg }, alertUpdate);
    };
    $scope.clearData = function () {
        clearFillBox($scope);
    };
    $scope.replaceNumber = function (arg) {
        if (confirm("Are you sure you want to change the Maximum Bonus Badge Reward?"))
            $smartboards.request('settings', 'courseBadges', { course: $scope.course, maxReward: arg }, alertUpdate);
    };
    $smartboards.request('settings', 'courseBadges', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        var text = "Badges must in ascending order with the following format: name;description; desc1;desc2;desc3; xp1;xp2;xp3; count?;post?;point?; count1;count2;count3";

        $scope.data = data;
        var tabContent = $($element);
        var configurationSection = createSection(tabContent, 'Manage Badges');

        var configSectionContent = $('<div>', { 'class': 'row' });

        //Display skill Badge info (similar to how it appears on the profile but simpler)
        var dataArea = $('<div>', { 'class': 'column badges-page', style: 'padding-right: 10px; float: left; width: 43%;' });
        for (t in data.badgesList) {
            var badge = data.badgesList[t];
            var badgeArea = $('<div>', { 'class': 'badge' });
            badgeArea.append('<strong style="font-size: 110%;">' + badge.name + ':&nbsp&nbsp</strong>');
            badgeArea.append('<span style="font-size: 105%; ">' + badge.description + '</span><br><br>');

            var imageLevel = $('<div>', { style: 'height: 90px' });
            imageLevel.append('<img style="float: left" src="badges/' + badge.name.replace(/\s+/g, '') + '-1.png" class="img">');

            for (var i = 0; i < badge.maxLevel; i++) {
                imageLevel.append('<span>Level ' + (i + 1) + ':&nbsp</span>');
                imageLevel.append('<span>' + badge.levels[i].description + '&nbsp</span>');
                imageLevel.append('<span style="float: right">&nbsp' + badge.levels[i].reward + ' XP</span>');
                //if (badge.isCount==true)
                //    badgeArea.append('<span style="font-size: 95%; ">&nbsp (count='+badge.levels[i].progressNeeded+')</span>');
                imageLevel.append('<br>');
            }

            var count = badge.isCount == true ? "Yes" : "No";
            imageLevel.append('<span style="font-size: 80%; ">Count Based: ' + count + ', </span>');
            var point = badge.isPoint == true ? "Yes" : "No";
            imageLevel.append('<span style="font-size: 80%; ">Point Based: ' + point + ', </span>');
            var post = badge.isPost == true ? "Yes" : "No";
            imageLevel.append('<span style="font-size: 80%; ">Post Based: ' + post + ', </span>');
            var extra = badge.isExtra == true ? "Yes" : "No";
            imageLevel.append('<span style="font-size: 80%; ">Extra Credit: ' + extra + '.</span><br>');

            badgeArea.append(imageLevel);
            dataArea.append(badgeArea);
        }
        configSectionContent.append(dataArea);

        $scope.newList = data.file;
        var bigBox = constructTextArea($compile, $scope, "Badge", text, 55);

        $scope.maxReward = data.maxReward;
        var numInput = constructNumberInput($compile, $scope, "Maximum Bonus Badges Reward", "maxReward", "Max Reward");
        bigBox.append(numInput);
        configSectionContent.append(bigBox);
        configurationSection.append(configSectionContent);
    });
});
/*app.controller('CourseLevelsSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    //old version, not used
    $scope.replaceData = function (arg) {
        if (confirm("Are you sure you want to replace all the Levels with the ones on the input box?"))
            $smartboards.request('settings', 'courseLevels', { course: $scope.course, levelList: arg }, alertUpdate);
    };
    $scope.clearData = function () {
        clearFillBox($scope);
    };

    $smartboards.request('settings', 'courseLevels', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        var text = "Levels must in ascending order with the following format: title;minimunXP";
        tabContents = [];
        console.log(data.levelList);
        for (var st in data.levelList) {
            tabContents.push({
                Level: data.levelList[st].number,
                Title: data.levelList[st].description,
                "Minimum XP": data.levelList[st].goal,
                "": { level: data.levelList[st].id }
            });
        }
        var columns = ["Level", "Title", "Minimum XP"];
        $scope.newList = data.file;
        constructConfigPage(data, err, $scope, $element, $compile, "Level", text, tabContents, columns);
    });
});*/