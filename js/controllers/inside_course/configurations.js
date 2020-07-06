//This file contains configuration options that are inside the settings
//(managing students and teachers, and configuring skill tree, badges and levels)

function alertUpdateAndReload(data, err) {
    if (err) {
        alert(err.description);
        return;
    }

    if (Object.keys(data.updatedData).length > 0) {
        var output = "";
        for (var i in data.updatedData) {
            output += data.updatedData[i] + '\n';
        }
        alert(output);
    }
    //location.reload();
}
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

app.controller('CourseSkillsSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $scope.replaceData = function (arg) {
        if (confirm("Are you sure you want to replace all the Skills with the ones on the input box?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, skillsList: arg }, alertUpdateAndReload);
    };
    $scope.replaceTier = function (arg) {
        if (confirm("Are you sure you want to replace all the Tiers with the ones on the input box?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, tiersList: arg }, alertUpdateAndReload);
    };
    $scope.replaceNumber = function (arg) {
        if (confirm("Are you sure you want to change the Maximum Tree XP?"))
            $smartboards.request('settings', 'courseSkills', { course: $scope.course, maxReward: arg }, alertUpdateAndReload);
    };
    $scope.addData = function (arg) {//Currently not being used
        $smartboards.request('settings', 'courseSkills', { course: $scope.course, newSkillsList: arg }, alertUpdateAndReload);
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
    $scope.replaceData = function (arg) {
        if (confirm("Are you sure you want to replace all the Badges with the ones on the input box?"))
            $smartboards.request('settings', 'courseBadges', { course: $scope.course, badgesList: arg }, alertUpdateAndReload);
    };
    $scope.clearData = function () {
        clearFillBox($scope);
    };
    $scope.replaceNumber = function (arg) {
        if (confirm("Are you sure you want to change the Maximum Bonus Badge Reward?"))
            $smartboards.request('settings', 'courseBadges', { course: $scope.course, maxReward: arg }, alertUpdateAndReload);
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
            $smartboards.request('settings', 'courseLevels', { course: $scope.course, levelList: arg }, alertUpdateAndReload);
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

        $smartboards.request('settings', 'coursePlugin', { fenix: lines, course: $scope.course }, alertUpdateAndReload);

    };
    $scope.saveMoodle = function () {
        console.log("save moodle");
        $smartboards.request('settings', 'coursePlugin', { moodle: $scope.moodleVars, course: $scope.course }, alertUpdateAndReload);
    };
    $scope.saveClassCheck = function () {
        console.log("save class check");
        $smartboards.request('settings', 'coursePlugin', { classCheck: $scope.classCheckVars, course: $scope.course }, alertUpdateAndReload);
    };
    $scope.saveGoogleSheets = function () {
        console.log("save google sheets");
        $smartboards.request('settings', 'coursePlugin', { googleSheets: $scope.googleSheetsVars, course: $scope.course }, alertUpdateAndReload);
    };


    $smartboards.request('settings', 'coursePlugin', { course: $scope.course }, function (data, err) {
        if (err) {
            console.log(err);
            return;
        }

        $scope.fenixVars = data.fenixVars;
        $scope.moodleVars = data.moodleVars;
        $scope.classCheckVars = data.classCheckVars;
        $scope.googleSheetsVars = data.googleSheetsVars;


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
        moodleconfigurationSection.append(moodleconfigSectionContent);

        var classCheckconfigurationSection = createSection(configurationSection, 'Class Check Variables');
        var classCheckconfigSectionContent = $('<div>', { 'class': 'row' });
        classCheckInputs = $('<div class="column" style=" width: 100%;"></div>');
        classCheckInputs.append('<span style="width: 15%; display: inline-block;">TSV Code: </span>');
        classCheckInputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="newList" ng-model="classCheckVars.tsvCode"><br>');
        classCheckconfigSectionContent.append(classCheckInputs);
        classCheckconfigSectionContent.append('<button class="button small" ng-click="saveClassCheck()">Save Class Check Vars</button><br>');
        classCheckconfigurationSection.append(classCheckconfigSectionContent);


        var googleSheetsconfigurationSection = createSection(configurationSection, 'Google Sheets Variables');
        var googleSheetsconfigSectionContent = $('<div>', { 'class': 'row' });
        googleSheetsVars = ["spreadsheetId", "sheetName", "range"];
        googleSheetsTitles = ["Spread Sheet Id: ", "Sheet Name: ", "Range: "];
        googleSheetsInputs = $('<div class="column" style=" width: 100%;"></div>');
        jQuery.each(googleSheetsVars, function (index) {
            model = googleSheetsVars[index];
            title = googleSheetsTitles[index];
            googleSheetsInputs.append('<span style="width: 15%; display: inline-block;">' + title + '</span>');
            googleSheetsInputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="newList" ng-model="googleSheetsVars.' + model + '"><br>');
        });
        googleSheetsconfigSectionContent.append(googleSheetsInputs);
        googleSheetsconfigSectionContent.append('<button class="button small" ng-click="saveGoogleSheets()">Save Google Sheets Vars</button><br>');
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


