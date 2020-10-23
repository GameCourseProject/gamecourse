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
        'class': 'column', style: 'float: right; width: ' + width + '%;',
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
        console.log(err);
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

app.controller('ConfigurationController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse){

    $scope.saveDataGeneralInputs = function(){
        $smartboards.request('settings', 'saveModuleConfigInfo', { course: $scope.course, module: $stateParams.module, generalInputs: $scope.inputs }, alertUpdate);
    }
    $scope.generalInputsChanged = function(){
        changed = false;
        jQuery.each($scope.generalInputs, function( index ){
            inputName = $scope.generalInputs[index].id
            initialInput = $scope.initialInputs[inputName];
            currentInput = $scope.inputs[inputName];
            if (initialInput != currentInput){
                changed =  true;
            }
        })
        return changed;
    }   
    $scope.addItem = function(){
        $scope.openItem = {};
        //reset atributes fields
        jQuery.each($scope.listingItems.allAtributes, function(index){
            atribute = $scope.listingItems.allAtributes[index];
            switch (atribute.type) {
                case 'text':
                    $scope.openItem[atribute.id] = "";
                    break;
                case 'select':
                    $scope.openItem[atribute.id] = "";
                    break;
                case 'on_off button':
                    $scope.openItem[atribute.id] = 0;
                    $("#"+atribute.id)[0].checked = false;
                    break;
                case 'date':
                    $scope.openItem[atribute.id] = "";
                    break;
                case 'number':
                    $scope.openItem[atribute.id] = 0;
                    break;
            }
        })
        $("#open_item_action").text('New '+$scope.listingItems.itemName+': ');

        $scope.submitItem = function (){
            $smartboards.request('settings', 'saveModuleConfigInfo', { course: $scope.course, module: $stateParams.module, listingItems: $scope.openItem, action_type: 'new'}, alertUpdate);
        }
    }
    $scope.editItem = function(item){
        $scope.openItem = {};
        $scope.openItem.id = item.id;
        $("#open_item_action").text('Edit '+$scope.listingItems.itemName+': ');

        jQuery.each($scope.listingItems.allAtributes, function(index){
            atribute = $scope.listingItems.allAtributes[index];
            $scope.openItem[atribute.id] = item[atribute.id];
            
            //click on on/off inputs to visually change to on
            if(atribute["type"] == "on_off button" && 
            (item[atribute.id] == "1" || item[atribute.id] == 'true' 
            || item[atribute.id] == true || item[atribute.id] == 1)){
                $("#"+atribute.id)[0].checked = true;
            }
            
        });
        
        $scope.submitItem = function (){
            $smartboards.request('settings', 'saveModuleConfigInfo', { course: $scope.course, module: $stateParams.module, listingItems: $scope.openItem, action_type: 'edit'}, alertUpdate);
        }
    }
    $scope.deleteItem = function(item){
        $scope.openItem = {};
        $scope.openItem.id = item.id;
        $('#delete_action_info').text( $scope.listingItems.itemName + ': ' + $scope.openItem.id);

        $scope.confirmDelete = function (){
            $smartboards.request('settings', 'saveModuleConfigInfo', { course: $scope.course, module: $stateParams.module, listingItems: $scope.openItem, action_type: 'delete'}, alertUpdate);
        }
    }
    $smartboards.request('settings', 'getModuleConfigInfo', { course: $scope.course, module: $stateParams.module }, function (data, err) {
        if (err) {
            console.log(err);
            return;
        }
        
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
        if ($scope.generalInputs.length != 0){
            generalInputsDiv = createSection(configPage, 'Variables');
            generalInputsDiv.attr("class","multiple_inputs content"); 

            $scope.inputs = {};
            $scope.initialInputs = {};
            jQuery.each($scope.generalInputs, function( index ){
                input = $scope.generalInputs[index];
                row = $("<div class='row'></div>");
                row.append('<span >' + input.name + '</span>');
                switch (input.type) {
                    case 'text':
                        row.append('<input class="config_input" type="text"  ng-model="inputs.' + input.id + '"><br>');
                        break;
                    case 'select':
                        select = $('<select class="config_input" ng-model="inputs.' + input.id + '"></select>');
                        jQuery.each(input.options, function( index ){
                            option = input.options[index];
                            select.append($('<option value="'+option+'">'+option+'</option>'))
                        });
                        row.append(select);
                        break;
                    case 'on_off button':
                        row.append( $('<div class="on_off"><label class="switch"><input type="checkbox" ng-model="inputs.' + input.id + '"><span class="slider round"></span></label></div>'))
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
                        color_picker_section.append( $('<input type="text" class="config_input pickr" id="'+input.id+'" placeholder="Color *" ng-model="inputs.' + input.id + '"/>'));
                        color_picker_section.append( $('<div id="'+input.id+'-color-sample" class="color-sample"><div class="box" style="background-color: '+input.current_val+';"></div><div  class="frame" style="border: 1px solid '+input.current_val+'"></div>'));
                        row.append(color_picker_section);
                        generalInputsDiv.append(row);

                        const inputElement_colorPicker = $('#'+input.id)[0];
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
                            color_sample = $('#'+input.id+'-color-sample');
                            color_sample[0].children[0].style.backgroundColor = color.toHEXA().toString(0);
                            color_sample[0].children[1].style.borderColor = color.toHEXA().toString(0);
                            $scope.inputs[input.id] = input.current_val;
                        })
                        break;
                }
                
            
                generalInputsDiv.append(row);
                $scope.inputs[input.id] = input.current_val;
                $scope.initialInputs[input.id] = input.current_val; //duplicated value to keep track of changes
            });

            $compile(generalInputsDiv)($scope);

            //save button for generel inputs
            action_buttons = $("<div class='config_save_button'></div>");
            action_buttons.append($('<button>', {id: 'general-inputs-save-button', text: "Save Changes", 'class': 'button', 'disabled': true, 'ng-disabled': '!generalInputsChanged()', 'ng-click': 'saveDataGeneralInputs()'})) 
            $compile(action_buttons)($scope);
            generalInputsDiv.append(action_buttons);
        }
        
        
        //personalized configuration Section
        if (data.personalizedConfig.length != 0){
            functionName = data.personalizedConfig;
            window[functionName]($scope, configPage, $smartboards, $compile);
        }      
                
        //listing items section
        if (data.listingItems.length != 0){
            $scope.listingItems = data.listingItems;

            allItems = createSection(configPage, $scope.listingItems.listName);
            allItems.attr('id', 'allItems')
            allItemsSection = $('<div class="data-table"></div>');
            table = $('<table></table>');
            rowHeader = $("<tr></tr>");
            jQuery.each($scope.listingItems.header, function(index){
                header = $scope.listingItems.header[index];
                rowHeader.append( $("<th>" + header + "</th>"));
            });
            rowHeader.append( $("<th class='action-column'></th>"));
            rowHeader.append( $("<th class='action-column'></th>"));
            
            rowContent = $("<tr ng-repeat='(i, item) in listingItems.items' id='item-{{item.id}}'> ></tr>");
            jQuery.each($scope.listingItems.displayAtributes, function(index){
                atribute = $scope.listingItems.displayAtributes[index];
                stg = "item." + atribute;
                rowContent.append($('<td>{{'+stg+'}}</td>'));
            });
            rowContent.append('<td class="action-column"><div class="icon edit_icon" value="#open-item" onclick="openModal(this)" ng-click="editItem(item)"></div></td>');
            rowContent.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification" onclick="openModal(this)" ng-click="deleteItem(item)"></div></td>');
        
            //append table
            table.append(rowHeader);
            table.append(rowContent);
            allItemsSection.append(table);
            allItems.append(allItemsSection);
            $compile(allItems)($scope);

            //add and edit item modal
            modal = $("<div class='modal' id='open-item'></div>");
            open_item = $("<div class='modal_content'></div>");
            open_item.append( $('<button class="close_btn icon" value="#open-item" onclick="closeModal(this)"></button>'));
            open_item.append( $('<div class="title" id="open_item_action"></div>'));
            content = $('<div class="content">');
            box = $('<div id="new_box" class= "inputs">');
            row_inputs = $('<div class= "row_inputs"></div>');

            $scope.listingItems.allAtributes
            details = $('<div class="details full config_item"></div>');
            jQuery.each($scope.listingItems.allAtributes, function(index){
                atribute = $scope.listingItems.allAtributes[index];
                switch (atribute.type) {
                    case 'text':
                        details.append($('<div class="half"><div class="container"><input type="text" class="form__input" placeholder="'+atribute.name+'" ng-model="openItem.'+atribute.id+'"/> <label for="name" class="form__label">'+atribute.name+'</label></div></div>'))
                        break;
                    case 'select':
                        select_box = $('<div class="options_box half"></div>');
                        select_box.append($('<span >'+atribute.name+': </span>'))
                        select = $('<select class="form__input" ng-model="openItem.'+atribute.id+'"></select>');
                        jQuery.each(atribute.options, function( index ){
                            option = atribute.options[index];
                            select.append($('<option value="'+option+'">'+option+'</option>'))
                        });
                        select_box.append(select);
                        details.append(select_box);
                        break;
                    case 'on_off button':
                        details.append( $('<div class= "on_off"><span>'+atribute.name+' </span><label class="switch"><input id="'+atribute.id+'" type="checkbox" ng-model="openItem.'+atribute.id+'"><span class="slider round"></span></label></div>'))
                        break;
                    case 'date':
                        details.append('<div class="half"><div class="container"><input type="date" class="form__input"  ng-model="openItem.' + atribute.id + '"><label for="name" class="form__label date_label">'+atribute.name+'</label></div></div>');
                        break;
                    case 'number':
                        details.append($('<div class="half"><div class="container"><input type="number" class="form__input"  ng-model="openItem.'+atribute.id+'"/> <label for="name" class="form__label number_label">'+atribute.name+'</label></div></div>'))
                        break;
                }
            });            
            row_inputs.append(details);
            box.append(row_inputs);
            content.append(box);
            content.append( $('<button class="save_btn" ng-click="submitItem()" > Save </button>'))
            open_item.append(content);
            modal.append(open_item);
            $compile(modal)($scope);
            allItems.append(modal);


            //delete verification modal
            deletemodal = $("<div class='modal' id='delete-verification'></div>");
            verification = $("<div class='verification modal_content'></div>");
            verification.append( $('<button class="close_btn icon" value="#delete-verification" onclick="closeModal(this)"></button>'));
            verification.append( $('<div class="warning">Are you sure you want to delete</div>'));
            verification.append( $('<div class="target" id="delete_action_info"></div>'));
            verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#delete-verification" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="confirmDelete()"> Delete</button></div>'))
            deletemodal.append(verification);
            rowContent.append(deletemodal);

            action_buttons = $("<div class='config_save_button'></div>");
            action_buttons.append( $("<div class='icon add_icon' value='#open-item' onclick='openModal(this)' ng-click='addItem()'></div>"));
            allItems.append($compile(action_buttons)($scope));
        }
    });
    
});


app.controller('CourseSkillsSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {
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
    $scope.addData = function (arg) {//Currently not being used
        $smartboards.request('settings', 'courseSkills', { course: $scope.course, newSkillsList: arg }, alertUpdate);
    };
    $scope.clearData = function () {
        clearFillBox($scope);
    };
    $scope.clearTier = function () {//clear textarea of the tiers
        if ($scope.tierList !== "")
            $scope.tierList = "";
        else if ("file2" in $scope.data)
            $scope.tierList = $scope.data.file2;
    };

    $smartboards.request('settings', 'courseSkills', { course: $scope.course }, function (data, err) {
        if (err) {
            console.log(err);
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
                skillBlock.append('<span style="font-size: 80%">' + skill.name + '</span>');
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
});
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
            console.log(err);
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
app.controller('CourseLevelsSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $scope.replaceData = function (arg) {
        if (confirm("Are you sure you want to replace all the Levels with the ones on the input box?"))
            $smartboards.request('settings', 'courseLevels', { course: $scope.course, levelList: arg }, alertUpdate);
    };
    $scope.clearData = function () {
        clearFillBox($scope);
    };

    $smartboards.request('settings', 'courseLevels', { course: $scope.course }, function (data, err) {
        if (err) {
            console.log(err);
            return;
        }

        var text = "Levels must in ascending order with the following format: title;minimunXP";
        tabContents = [];
        console.log(data.levelList);
        for (var st in data.levelList) {
            tabContents.push({
                Level: data.levelList[st].number, Title: data.levelList[st].description, "Minimum XP": data.levelList[st].goal,
                "": { level: data.levelList[st].id }
            });
        }
        var columns = ["Level", "Title", "Minimum XP"];
        $scope.newList = data.file;
        constructConfigPage(data, err, $scope, $element, $compile, "Level", text, tabContents, columns);
    });
});

app.controller('CoursePluginsSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    //uma funcao de submit para cada

    var fileFenixUploaded;
    var lines = [];
    $scope.upload = function () {
        const inputElement = document.getElementById("newList1");
        fileFenixUploaded = inputElement.files[0];

        var reader = new FileReader();
        reader.onload = (function (reader) {
            return function () {
                var contents = reader.result;
                lines.push(contents.split('\n'));
            }
        })(reader);

        reader.readAsText(fileFenixUploaded);
        console.log(lines);
    }
    $scope.saveFenix = function () {
        $smartboards.request('settings', 'coursePlugin', { fenix: lines, course: $scope.course }, alertUpdate);


    }

    $scope.getAuthCode = function () {
        var win = window.open(authUrl, '_blank');
        win.focus();
    }

    var fileCredentialsUploaded;
    var googleSheetsCredentials = [];
    $scope.uploadCredentials = function () {
        const inputElement = document.getElementById("newList2");
        fileCredentialsUploaded = inputElement.files[0];

        var reader = new FileReader();
        reader.onload = (function (reader) {
            return function () {
                var contents = reader.result;
                googleSheetsCredentials.push(JSON.parse(contents));
                console.log(googleSheetsCredentials);
            }
        })(reader);

        reader.readAsText(fileCredentialsUploaded);
    }
    var authUrl;
    $scope.saveCredentials = function () {
        $smartboards.request('settings', 'coursePlugin', { credentials: googleSheetsCredentials, course: $scope.course }, function (data, err) {
            alertUpdate(data, err);
            authUrl = data.authUrl;
        });
    }
    $scope.saveMoodle = function () {
        console.log("save moodle");
        $smartboards.request('settings', 'coursePlugin', { moodle: $scope.moodleVars, course: $scope.course }, alertUpdate);
    };
    $scope.enableMoodle = function () {
        console.log($scope.moodleVarsPeriodicity);
        $smartboards.request('settings', 'coursePlugin', { moodlePeriodicity: $scope.moodleVarsPeriodicity, course: $scope.course }, alertUpdate);
    };
    $scope.enableClassCheck = function () {
        console.log($scope.classCheckVarsPeriodicity);
        $smartboards.request('settings', 'coursePlugin', { classCheckPeriodicity: $scope.classCheckVarsPeriodicity, course: $scope.course }, alertUpdate);
    };
    $scope.enableGoogleSheets = function () {
        console.log($scope.googleSheetsVarsPeriodicity);
        $smartboards.request('settings', 'coursePlugin', { googleSheetsPeriodicity: $scope.googleSheetsVarsPeriodicity, course: $scope.course }, alertUpdate);
    };
    $scope.saveClassCheck = function () {
        console.log("save class check");
        $smartboards.request('settings', 'coursePlugin', { classCheck: $scope.classCheckVars, course: $scope.course }, alertUpdate);
    };
    $scope.saveGoogleSheets = function () {
        console.log("save google sheets");
        i = 1;
        $scope.googleSheetsVars.sheetName = [];
        while (i <= $scope.numberGoogleSheets) {
            id = "#sheetname" + i;
            sheetname = $(id)[0].value;
            $scope.googleSheetsVars.sheetName.push(sheetname);
            i++;
        }
        $smartboards.request('settings', 'coursePlugin', { googleSheets: $scope.googleSheetsVars, course: $scope.course }, alertUpdate);
    };
    $scope.addExtraField = function () {
        inputs = $("#sheet_names");
        $scope.numberGoogleSheets++;
        inputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="sheetname' + $scope.numberGoogleSheets + '">');
    }


    $smartboards.request('settings', 'coursePlugin', { course: $scope.course }, function (data, err) {
        if (err) {
            console.log(err);
            return;
        }

        $scope.fenixVars = data.fenixVars;
        $scope.moodleVars = data.moodleVars;
        $scope.moodleVarsPeriodicity = data.moodleVarsPeriodicity;
        $scope.classCheckVarsPeriodicity = data.classCheckVarsPeriodicity;
        $scope.googleSheetsVarsPeriodicity = data.googleSheetsVarsPeriodicity;
        $scope.classCheckVars = data.classCheckVars;
        $scope.googleSheetsVars = data.googleSheetsVars;
        $scope.googleSheetsAuthUrl = data.authUrl;


        var tabContent = $($element);
        var configurationSection = createSection(tabContent, 'Manage Plugins');

        var fenixconfigurationSection = createSection(configurationSection, 'Fenix Variables');
        var fenixconfigSectionContent = $('<div>', { 'class': 'row' });
        fenixInputs = $('<div class="column" style=" width: 100%;"></div>');
        fenixInputs.append('<span style="width: 15%; display: inline-block;">Fenix Course Id: </span>');
        fenixInputs.append('<input type="file" style="width: 25%;margin: 5px;" id="newList1" onchange="angular.element(this).scope().upload()"><br>');
        fenixconfigSectionContent.append(fenixInputs);
        fenixconfigSectionContent.append('<button class="button small" ng-click="saveFenix()">Save Fenix Vars</button><br>');
        fenixconfigurationSection.append(fenixconfigSectionContent);

        var moodleconfigurationSection = createSection(configurationSection, 'Moodle Variables');
        var moodleconfigSectionContent = $('<div>', { 'class': 'row' });
        moodleVars = ["dbserver", "dbuser", "dbpass", "db", "dbport", "prefix", "time", "course", "user"];
        moodleTitles = ["DB Server:", "DB User:", "DB Pass:", "DB:", "DB Port:", "Prefix:", "Time:", "Course:", "User:"];
        moodleInputs = $('<div class="column" style=" width: 100%;"></div>');
        jQuery.each(moodleVars, function (index) {
            model = moodleVars[index];
            title = moodleTitles[index];
            moodleInputs.append('<span style="width: 15%; display: inline-block;">' + title + '</span>');
            moodleInputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="newList" ng-model="moodleVars.' + model + '"><br>');
        });
        moodleconfigSectionContent.append(moodleInputs);
        moodleconfigSectionContent.append('<button class="button small" ng-click="saveMoodle()">Save Moodle Vars</button><br>');

        moodleconfigSectionContent.append('<input ng-init="moodleVarsPeriodicity.number=5" ng-model="moodleVarsPeriodicity.number" type="number" id="periodicidade1" name="periodicidade1" min="1" max="59">');
        moodleconfigSectionContent.append('<select class="form-control" ng-value="minutes" ng-model="moodleVarsPeriodicity.time" ng-init="moodleVarsPeriodicity.time = data[0]" name="periodicidade2" id="periodicidade2"> <option disabled hidden style="display: none" value=""></option><option  ng-value ="minutos">Minutos</option><option ng-value="horas">Horas</option><option ng-value="meses">Meses</option></select> ');
        moodleconfigSectionContent.append('<button class="button small" ng-click="enableMoodle()">Enable Moodle</button><br>');

        moodleconfigurationSection.append(moodleconfigSectionContent);

        var classCheckconfigurationSection = createSection(configurationSection, 'Class Check Variables');
        var classCheckconfigSectionContent = $('<div>', { 'class': 'row' });
        classCheckInputs = $('<div class="column" style=" width: 100%;"></div>');
        classCheckInputs.append('<span style="width: 15%; display: inline-block;">TSV Code: </span>');
        classCheckInputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="newList" ng-model="classCheckVars.tsvCode"><br>');
        classCheckconfigSectionContent.append(classCheckInputs);
        classCheckconfigSectionContent.append('<button class="button small" ng-click="saveClassCheck()">Save Class Check Vars</button><br>');

        classCheckconfigSectionContent.append('<input ng-init="classCheckVarsPeriodicity.number=5" ng-model="classCheckVarsPeriodicity.number" type="number" id="periodicidade1" name="periodicidade1" min="1" max="59">');
        classCheckconfigSectionContent.append('<select class="form-control" ng-value="minutes" ng-model="classCheckVarsPeriodicity.time" ng-init="classCheckVarsPeriodicity.time = data[0]" name="periodicidade2" id="periodicidade2"> <option disabled hidden style="display: none" value=""></option><option  ng-value ="minutos">Minutos</option><option ng-value="horas">Horas</option><option ng-value="meses">Meses</option></select> ');
        classCheckconfigSectionContent.append('<button class="button small" ng-click="enableClassCheck()">Enable Class Check</button><br>');

        classCheckconfigurationSection.append(classCheckconfigSectionContent);


        var googleSheetsconfigurationSection = createSection(configurationSection, 'Google Sheets Variables');
        var googleSheetsconfigSectionContent = $('<div>', { 'class': 'row' });
        googleSheetsVars = ["credentials", "authCode", "spreadsheetId", "sheetName"];
        googleSheetsTitles = ["Credentials:", "Auth Code: ", "Spread Sheet Id: ", "Sheet Name: "];
        googleSheetsInputs = $('<div class="column" style=" width: 100%;"></div>');
        jQuery.each(googleSheetsVars, function (index) {
            model = googleSheetsVars[index];
            title = googleSheetsTitles[index];
            googleSheetsInputs.append('<span style="width: 15%; display: inline-block;">' + title + '</span>');
            if (model == "authCode") {
                googleSheetsInputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="newList" ng-model="googleSheetsVars.' + model + '">');
                googleSheetsInputs.append('<button class="button small" ng-click="getAuthCode()">Get AuthCode</button><br>');
            } else if (model == "credentials") {
                googleSheetsInputs.append('<input type="file" style="width: 25%;margin: 5px;" id="newList2" onchange="angular.element(this).scope().uploadCredentials()">');
                googleSheetsInputs.append('<button class="button small" ng-click="saveCredentials()">Upload</button><br>');
            } else if (model == "sheetName") {
                $scope.numberGoogleSheets = 0;
                if ($scope.googleSheetsVars.sheetName.length != 0) {
                    jQuery.each($scope.googleSheetsVars.sheetName, function (index) {
                        sheetName = $scope.googleSheetsVars.sheetName[index];
                        $scope.numberGoogleSheets++;
                        googleSheetsInputs.append('<span id="sheet_names"><input type:"text" style="width: 25%;margin: 5px;" value="' + sheetName + '" id="sheetname' + $scope.numberGoogleSheets + '"></span>');
                    });
                }
                else {
                    $scope.numberGoogleSheets++;
                    googleSheetsInputs.append('<span id="sheet_names"><input type:"text" style="width: 25%;margin: 5px;" id="sheetname' + $scope.numberGoogleSheets + '"></span>');
                }
                googleSheetsInputs.append('<button class="button small" ng-click="addExtraField()">Add another sheet</button><br><br>');

            } else {
                googleSheetsInputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="newList" ng-model="googleSheetsVars.' + model + '"><br>');
            }
        });
        googleSheetsconfigSectionContent.append(googleSheetsInputs);
        googleSheetsconfigSectionContent.append('<button class="button small" ng-click="saveGoogleSheets()">Save Google Sheets Vars</button><br>');

        googleSheetsconfigSectionContent.append('<input ng-init="googleSheetsVarsPeriodicity.number=5" ng-model="googleSheetsVarsPeriodicity.number" type="number" id="periodicidade1" name="periodicidade1" min="1" max="59">');
        googleSheetsconfigSectionContent.append('<select class="form-control" ng-value="minutes" ng-model="googleSheetsVarsPeriodicity.time" ng-init="googleSheetsVarsPeriodicity.time = data[0]" name="periodicidade2" id="periodicidade2"> <option disabled hidden style="display: none" value=""></option><option  ng-value ="minutos">Minutos</option><option ng-value="horas">Horas</option><option ng-value="meses">Meses</option></select> ');
        googleSheetsconfigSectionContent.append('<button class="button small" ng-click="enableGoogleSheets()">Enable Google Sheets</button><br>');

        googleSheetsconfigurationSection.append(googleSheetsconfigSectionContent);

        $compile(configurationSection)($scope);


        //for my future self
        // Fénix:
        // combobox com o curso que está a leccionar

        // ClassCheck:
        // tsvCode (é uma sequencia de caracteres que aparece no final de um url,
        // por isso podes meter "https://classcheck.tk/tsv/course?s=" e depois deixar um campo
        // pro user preencher com o código)

        // Moodle:
        // Servidor da BD
        // User da BD
        // Pass da BD
        // Port da BD (este campo não é de preenchimento obrigatório)
        // Prefixo das tabelas (podes por preenchido já com "mdl_", porque também aparece assim na configuração do moodle, se a pessoa quiser, depois altera)

    });
});


