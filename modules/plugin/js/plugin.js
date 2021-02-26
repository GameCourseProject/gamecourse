//add config page to course
app.stateProvider.state('course.settings.plugin', {
    url: '/plugin',
    views : {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'plugin'
    }
});

function pluginPersonalizedConfig($scope, $element, $smartboards, $compile){
    $scope.changeLimit = function (plugin) {
        
        var periodicity1 = document.getElementById(plugin + "Periodicidade1");
        var periodicity2 = document.getElementById(plugin + "Periodicidade2");
        var selectedOption = periodicity2.options[periodicity2.selectedIndex].text;
        var maxLimit;
        var setValue = null;
        if (selectedOption == "Minutes") {
            maxLimit = 59;
        } else if (selectedOption == "Hours") {
            maxLimit = 23;
        } else if (selectedOption == "Day") {
            maxLimit = 1;
        }

        if (periodicity1.value > maxLimit) {
            setValue = maxLimit;
        }

        if (setValue) {
            if (plugin == "moodle") {
                $scope.moodleVarsPeriodicity.number = setValue;
            } else if (plugin == "classCheck" ) {
                $scope.classCheckVarsPeriodicity.number = setValue;
            } else if (plugin == "googleSheets") {
                $scope.googleSheetsVarsPeriodicity.number = setValue;
            }
        }
        periodicity1.setAttribute("max", maxLimit);
        
    }
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
            // alertUpdate(data, err);  
            if (err) {
                giveMessage(err.description);
            } else {
                w = 550;
                h = 650;
                var left = (screen.width - w) / 2;
                var top = (screen.height - h) / 4;
                window.open(data.authUrl, 'Authenticate', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
            }
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
    $scope.disableMoodle = function () {
        $smartboards.request('settings', 'coursePlugin', { disableMoodlePeriodicity: true, course: $scope.course }, alertUpdate);
    };
    $scope.disableClassCheck = function () {
        $smartboards.request('settings', 'coursePlugin', { disableClassCheckPeriodicity: true, course: $scope.course }, alertUpdate);
    };
    $scope.disableGoogleSheets = function () {
        $smartboards.request('settings', 'coursePlugin', { disableGoogleSheetsPeriodicity: true, course: $scope.course }, alertUpdate);
    };
    $scope.saveClassCheck = function () {
        console.log("save class check");
        $smartboards.request('settings', 'coursePlugin', { classCheck: $scope.classCheckVars, course: $scope.course }, alertUpdate);
    };
    $scope.saveGoogleSheets = function () {
        console.log("save google sheets");
        i=1;
        $scope.googleSheetsVars.sheetName=[];
        $scope.googleSheetsVars.ownerName=[];
        while (i <= $scope.numberGoogleSheets){
            sheetId = "#sheetname" + i;
            ownerId = "#ownername" + i;
            sheetname = $(sheetId)[0].value;
            ownername = $(ownerId)[0].value;
            $scope.googleSheetsVars.sheetName.push(sheetname);
            $scope.googleSheetsVars.ownerName.push(ownername);
            i++;
        }
        $smartboards.request('settings', 'coursePlugin', { googleSheets: $scope.googleSheetsVars, course: $scope.course }, alertUpdate);
    };
    $scope.addExtraField = function(){
        //inputs = $("#sheet_names");
        inputsButton = $(".input_with_button");
        $scope.numberGoogleSheets++;
        //inputsButton.prepend('<div style="width:100%;"><input class="config_input" type:"text" id="sheetname'+ $scope.numberGoogleSheets +'"><select class="config_input" ng-options="user.username as user.name for user in googleSheetsVars.professors track by user.username" ng-model="names" id="ownername'+ $scope.numberGoogleSheets +'"></div>');
        inputsButton.prepend('<div style="width:100%;"><input class="config_input" type:"text" id="sheetname'+ $scope.numberGoogleSheets +'"><input class="config_input" type:"text" id="ownername'+ $scope.numberGoogleSheets +'"></div>');
    }


    $smartboards.request('settings', 'coursePlugin', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
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


        var configurationSection = $($element);

        //Fenix
        var fenixconfigurationSection = createSection(configurationSection, 'Fenix Variables');
        fenixconfigurationSection.attr("class","multiple_inputs content");
        fenixInputs = $('<div class="row" ></div>');
        fenixInputs.append('<span">Fenix Course Id: </span>');
        fenixInputs.append('<input class="config_input" type="file" accept=".csv, .txt" id="newList1" onchange="angular.element(this).scope().upload()"><br>');
        fenixconfigurationSection.append(fenixInputs);

        action_buttons = $("<div class='config_save_button'></div>");
        action_buttons.append('<button class="button small" ng-click="saveFenix()">Save Fenix Vars</button><br>');
        fenixconfigurationSection.append(action_buttons);


        //moodle
        var moodleconfigurationSection = createSection(configurationSection, 'Moodle Variables');
        moodleconfigSectionInputs = $('<div class="multiple_inputs" ></div>');
        moodleVars = ["dbserver", "dbuser", "dbpass", "db", "dbport", "prefix", "time", "course", "user"];
        moodleTitles = ["DB Server:", "DB User:", "DB Pass:", "DB:", "DB Port:", "Prefix:", "Time:", "Course:", "User:"];
        jQuery.each(moodleVars, function (index) {
            model = moodleVars[index];
            title = moodleTitles[index];
            row = $("<div class='row'></div>");
            row.append('<span >' + title + '</span>');
            row.append('<input class="config_input" type:"text"  id="newList" ng-model="moodleVars.' + model + '"><br>');
            moodleconfigSectionInputs.append(row);
        });
        moodleconfigurationSection.append(moodleconfigSectionInputs);

        if ($scope.moodleVars.periodicityTime == "Minutes") {
            $scope.moodleVars.periodicityTimeId = 1;
        } else if ($scope.moodleVars.periodicityTime == "Hours") {
            $scope.moodleVars.periodicityTimeId = 2;
        } else if ($scope.moodleVars.periodicityTime == "Day") {
            $scope.moodleVars.periodicityTimeId = 3;
        }

        $scope.moodleVarsPeriodicity = {
            number: $scope.moodleVars.periodicityNumber,
            availableOptions: [
                { id: '1', name: 'Minutes' },
                { id: '2', name: 'Hours' },
                { id: '3', name: 'Day' }
            ],
            time: { id: $scope.moodleVars.periodicityTimeId, name: $scope.moodleVars.periodicityTime },
            plugin: "moodle"

        };
        console.log($scope.moodleVars);
        moodleconfigSectionPeriodicity = $('<div class="column" ></div>');
        row2 = $("<div class='plugin_row periodicity'></div>");
        row2.append('<span>Periodicity: </span>');
        row2.append('<input class="config_input" ng-init="moodleVarsPeriodicity.number" ng-model="moodleVarsPeriodicity.number" type="number" id="moodlePeriodicidade1"  min="0" max="59">');
        row2.append('<select class="form-control config_input" ng-model="moodleVarsPeriodicity.time" id="moodlePeriodicidade2" ng-options="option.name for option in moodleVarsPeriodicity.availableOptions track by option.id" ng-change="changeLimit(moodleVarsPeriodicity.plugin)" ></select >');
        row2.append('<button style="margin-right:2px" class="button small" ng-click="enableMoodle()">Enable Moodle</button>');
        row2.append('<button class="button small" ng-click="disableMoodle()">Disable Moodle</button><br>');
        moodleconfigSectionPeriodicity.append(row2);
        moodleconfigurationSection.append(moodleconfigSectionPeriodicity);
        
        action_buttons = $("<div class='config_save_button'></div>");
        action_buttons.append('<button class="button small" ng-click="saveMoodle()">Save Moodle Vars</button><br>');
        moodleconfigurationSection.append(action_buttons);

        if ($scope.classCheckVars.periodicityTime == "Minutes") {
            $scope.classCheckVars.periodicityTimeId = 1;
        } else if ($scope.classCheckVars.periodicityTime == "Hours") {
            $scope.classCheckVars.periodicityTimeId = 2;
        } else if ($scope.classCheckVars.periodicityTime == "Day") {
            $scope.classCheckVars.periodicityTimeId = 3;
        }

        $scope.classCheckVarsPeriodicity = {
            number: $scope.googleSheetsVars.periodicityNumber,
            availableOptions: [
                { id: '1', name: 'Minutes' },
                { id: '2', name: 'Hours' },
                { id: '3', name: 'Day' }
            ],
            time: { id: $scope.classCheckVars.periodicityTimeId, name: $scope.classCheckVars.periodicityTime },
            plugin: "classCheck"

        };
        //class check
        var classCheckconfigurationSection = createSection(configurationSection, 'Class Check Variables');
        classCheckconfigurationSection.attr("class","column content");
        row = $("<div class='plugin_row'></div>");      
        row.append('<span>TSV Code: </span>');
        row.append('<input class="config_input" type:"text" id="newList" ng-model="classCheckVars.tsvCode"><br>');
        classCheckconfigurationSection.append(row);
        row2 = $("<div class='plugin_row periodicity'></div>");
        row2.append('<span>Periodicity: </span>');
        row2.append('<input class="config_input" ng-init="classCheckVarsPeriodicity.number" ng-model="classCheckVarsPeriodicity.number" type="number" id="classCheckPeriodicidade1" min="0" max="59">');
        row2.append('<select class="form-control config_input" ng-model="classCheckVarsPeriodicity.time" id="classCheckPeriodicidade2" ng-options="option.name for option in classCheckVarsPeriodicity.availableOptions track by option.id" ng-change="changeLimit(classCheckVarsPeriodicity.plugin)" ></select >');
        row2.append('<button style="margin-right:2px" class="button small" ng-click="enableClassCheck()">Enable Class Check</button>');
        row2.append('<button class="button small" ng-click="disableClassCheck()">Disable Class Check</button><br>');
        classCheckconfigurationSection.append(row2);

        action_buttons = $("<div class='config_save_button'></div>");
        action_buttons.append('<button class="button small" ng-click="saveClassCheck()">Save Class Check Vars</button><br>');
        classCheckconfigurationSection.append(action_buttons);

        //google sheets
        var googleSheetsconfigurationSection = createSection(configurationSection, 'Google Sheets Variables');
        googleSheetsconfigurationSection.attr("class","column content");
        googleSheetsVars = ["credentials", "spreadsheetId", "sheetName"];
        googleSheetsTitles = ["Credentials:", "Spread Sheet Id: ", "Sheet Name: "];
        jQuery.each(googleSheetsVars, function (index) {
            model = googleSheetsVars[index];
            title = googleSheetsTitles[index];
            row = $("<div class='plugin_row'></div>");
            row.append('<span >' + title + '</span>');
            if (model == "credentials") {
                row.append('<input class="config_input" type="file" id="newList2" onchange="angular.element(this).scope().uploadCredentials()">');
                row.append('<button class="button small" ng-click="saveCredentials()">Upload and Authenticate</button><br>');
            } else if (model == "sheetName"){
                row.attr('id','sheet_names_row');
                $scope.numberGoogleSheets = 0;
                if($scope.googleSheetsVars.sheetName.length != 0){
                    inputsButton = $("<div class='input_with_button' ><div id='sheet_names'></div></div>");
                    jQuery.each($scope.googleSheetsVars.sheetName, function (index){
                        sheetName = $scope.googleSheetsVars.sheetName[index];
                        ownerName = $scope.googleSheetsVars.ownerName[index];
                        $scope.numberGoogleSheets++;
                        //inputsButton.append('<div style="width:100%;"><input class="config_input" type:"text" value="'+ sheetName +'" id="sheetname'+ $scope.numberGoogleSheets +'"><select class="config_input" ng-options="user.username as user.name for user in googleSheetsVars.professors track by user.username" ng-model="names"  id="ownername'+ $scope.numberGoogleSheets +'"></select></div>');
                        inputsButton.append('<div style="width:100%;"><input class="config_input" type:"text" value="'+ sheetName +'" id="sheetname'+ $scope.numberGoogleSheets +'"><input class="config_input" value="'+ ownerName +'" id="ownername'+ $scope.numberGoogleSheets +'"></div>');
                    });
                }
                else{
                    inputsButton = $("<div class='input_with_button'></div>");
                    $scope.numberGoogleSheets++;
                    inputsButton.append('<div style="width:100%;"><input class="config_input" type:"text" id="sheetname'+ $scope.numberGoogleSheets +'"><input class="config_input" type:"text" id="ownername'+ $scope.numberGoogleSheets +'"></div>');
                }
                inputsButton.append('<button class="button small" ng-click="addExtraField()">Add another sheet</button>');
                row.append(inputsButton);
            } else {
                row.append('<input class="config_input" type:"text" id="newList" ng-model="googleSheetsVars.' + model + '"><br>');
            }
            googleSheetsconfigurationSection.append(row);
        });
        if ($scope.googleSheetsVars.periodicityTime == "Minutes") {
            $scope.googleSheetsVars.periodicityTimeId = 1;
        } else if ($scope.googleSheetsVars.periodicityTime == "Hours") {
            $scope.googleSheetsVars.periodicityTimeId = 2;
        } else if ($scope.googleSheetsVars.periodicityTime == "Day") {
            $scope.googleSheetsVars.periodicityTimeId = 3;
        }
        $scope.googleSheetsVarsPeriodicity = {
            number: $scope.googleSheetsVars.periodicityNumber,
            availableOptions: [
                { id: '1', name: 'Minutes' },
                { id: '2', name: 'Hours' },
                { id: '3', name: 'Day' }
            ],
            time: { id: $scope.googleSheetsVars.periodicityTimeId, name: $scope.googleSheetsVars.periodicityTime },
            plugin: "googleSheets"

        };

        row2 = $("<div class='plugin_row periodicity'></div>");
        row2.append('<span>Periodicity: </span>');
        row2.append('<input class="config_input" ng-init="googleSheetsVarsPeriodicity.number" ng-model="googleSheetsVarsPeriodicity.number" type="number" id="googleSheetsPeriodicidade1" min="0" max="59">');
        row2.append('<select class="form-control config_input" ng-model="googleSheetsVarsPeriodicity.time" id="googleSheetsPeriodicidade2" ng-options="option.name for option in googleSheetsVarsPeriodicity.availableOptions track by option.id" ng-change="changeLimit(googleSheetsVarsPeriodicity.plugin)" ></select >');
        row2.append('<button style="margin-right:2px" class="button small" ng-click="enableGoogleSheets()">Enable Google Sheets</button>');
        row2.append('<button class="button small" ng-click="disableGoogleSheets()">Disable Google Sheets</button><br>');
        googleSheetsconfigurationSection.append(row2);

        action_buttons = $("<div class='config_save_button'></div>");
        action_buttons.append('<button class="button small" ng-click="saveGoogleSheets()">Save Google Sheets Vars</button><br>');
        googleSheetsconfigurationSection.append(action_buttons);

        $compile(configurationSection)($scope);


        //for my future self
        // Fénix:
        // combobox com o curso que está a leccionar

        // ClassCheck:
        // tsvCode (é uma sequencia de caracteres que aparece no final de um url,
        // por isso podes meter "https://classcheck.tk/tsv/course?s=" e depois deixar um campo
        // pro user preencher com o código)

    });
}