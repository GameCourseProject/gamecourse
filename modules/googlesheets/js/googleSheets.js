//add config page to course
app.stateProvider.state('course.settings.googlesheets', {
    url: '/googlesheets',
    views: {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'googlesheets'
    }
});


function googleSheetsPersonalizedConfig($scope, $element, $smartboards, $compile) {
    $scope.changeLimit = function (googlesheets) {

        var periodicity1 = document.getElementById(googlesheets + "Periodicidade1");
        var periodicity2 = document.getElementById(googlesheets + "Periodicidade2");
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
            if (googlesheets == "googleSheets") {
                $scope.googleSheetsVarsPeriodicity.number = setValue;
            }
        }
        periodicity1.setAttribute("max", maxLimit);

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
        $smartboards.request('settings', 'courseGoogleSheets', { credentials: googleSheetsCredentials, course: $scope.course }, function (data, err) {
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

    $scope.enableGoogleSheets = function () {
        console.log($scope.googleSheetsVarsPeriodicity);
        $smartboards.request('settings', 'courseGoogleSheets', { googleSheetsPeriodicity: $scope.googleSheetsVarsPeriodicity, course: $scope.course }, alertUpdate);
    };
    $scope.disableGoogleSheets = function () {
        $smartboards.request('settings', 'courseGoogleSheets', { disableGoogleSheetsPeriodicity: true, course: $scope.course }, alertUpdate);
    };
    $scope.saveGoogleSheets = function () {
        console.log("save google sheets");
        i = 1;
        $scope.googleSheetsVars.sheetName = [];
        $scope.googleSheetsVars.ownerName = [];
        while (i <= $scope.numberGoogleSheets) {
            sheetId = "#sheetname" + i;
            ownerId = "#ownername" + i;
            sheetname = $(sheetId)[0].value;
            ownername = $(ownerId)[0].value;
            $scope.googleSheetsVars.sheetName.push(sheetname);
            $scope.googleSheetsVars.ownerName.push(ownername);
            i++;
        }
        $smartboards.request('settings', 'courseGoogleSheets', { googleSheets: $scope.googleSheetsVars, course: $scope.course }, alertUpdate);
    };
    $scope.addExtraField = function () {
        //inputs = $("#sheet_names");
        inputsButton = $(".input_with_button");
        $scope.numberGoogleSheets++;
        //inputsButton.prepend('<div style="width:100%;"><input class="config_input" type:"text" id="sheetname'+ $scope.numberGoogleSheets +'"><select class="config_input" ng-options="user.username as user.name for user in googleSheetsVars.professors track by user.username" ng-model="names" id="ownername'+ $scope.numberGoogleSheets +'"></div>');
        inputsButton.prepend('<div style="width:100%;"><input class="config_input" type:"text" id="sheetname' + $scope.numberGoogleSheets + '"><input class="config_input" type:"text" id="ownername' + $scope.numberGoogleSheets + '"></div>');
    }

    $smartboards.request('settings', 'courseGoogleSheets', {course: $scope.course}, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        $scope.googleSheetsVars = data.googleSheetsVars;
        $scope.googleSheetsAuthUrl = data.authUrl;
        $scope.googleSheetsVarsPeriodicity = data.googleSheetsVarsPeriodicity;

        var configurationSection = $($element);

        var googleSheetsconfigurationSection = createSection(configurationSection, 'Google Sheets Variables');
        googleSheetsconfigurationSection.attr("class", "column content");
        googleSheetsVars = ["credentials", "spreadsheetId", "sheetName"];
        googleSheetsTitles = ["Credentials:", "Spread Sheet Id: ", "Sheet Name: "];
        jQuery.each(googleSheetsVars, function (index) {
            model = googleSheetsVars[index];
            title = googleSheetsTitles[index];
            row = $("<div class='googlesheets_row'></div>");
            row.append('<span >' + title + '</span>');
            if (model == "credentials") {
                row.append('<div style="margin-left:80px; margin-top: -25px"><input class="config_input googlesheets" type="file" id="newList2" onchange="angular.element(this).scope().uploadCredentials()"></div>');
                row.append('<div style="margin-bottom: 20px"><button class="button small" ng-click="saveCredentials()">Upload and Authenticate</button><br></div>');
            } else if (model == "sheetName") {
                row.attr('id', 'sheet_names_row');
                $scope.numberGoogleSheets = 0;
                if ($scope.googleSheetsVars.sheetName.length != 0) {
                    inputsButton = $("<div class='input_with_button' ><div id='sheet_names'></div></div>");
                    jQuery.each($scope.googleSheetsVars.sheetName, function (index) {
                        sheetName = $scope.googleSheetsVars.sheetName[index];
                        ownerName = $scope.googleSheetsVars.ownerName[index];
                        $scope.numberGoogleSheets++;
                        //inputsButton.append('<div style="width:100%;"><input class="config_input" type:"text" value="'+ sheetName +'" id="sheetname'+ $scope.numberGoogleSheets +'"><select class="config_input" ng-options="user.username as user.name for user in googleSheetsVars.professors track by user.username" ng-model="names"  id="ownername'+ $scope.numberGoogleSheets +'"></select></div>');
                        inputsButton.append('<div style="width:100%;"><input class="config_input" type:"text" value="' + sheetName + '" id="sheetname' + $scope.numberGoogleSheets + '"><input class="config_input" value="' + ownerName + '" id="ownername' + $scope.numberGoogleSheets + '"></div>');
                    });
                }
                else {
                    inputsButton = $("<div class='input_with_button'></div>");
                    $scope.numberGoogleSheets++;
                    inputsButton.append('<div style="width:100%;"><input class="config_input" type:"text" id="sheetname' + $scope.numberGoogleSheets + '"><input class="config_input" type:"text" id="ownername' + $scope.numberGoogleSheets + '"></div>');
                }
                inputsButton.append('<button class="button small" style="margin-bottom:10px;" ng-click="addExtraField()">Add another sheet</button>');
                row.append(inputsButton);
            } else {
                row.append('<div style="margin-left: 120px; margin-top: -25px;"><input class="config_input" type:"text" id="newList" ng-model="googleSheetsVars.' + model + '"><br></div>');
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

        row2 = $("<div class='googlesheets_row periodicity'></div>");
        row2.append('<span>Periodicity: </span>');
        row2.append('<input class="config_input" style= "margin-top: -20px; margin-left: 140px;" ng-init="googleSheetsVarsPeriodicity.number" ng-model="googleSheetsVarsPeriodicity.number" type="number" id="googleSheetsPeriodicidade1" min="0" max="59">');
        row2.append('<select class="form-control config_input" style="margin-left: 300px; margin-top: -30px" ng-model="googleSheetsVarsPeriodicity.time" id="googleSheetsPeriodicidade2" ng-options="option.name for option in googleSheetsVarsPeriodicity.availableOptions track by option.id" ng-change="changeLimit(googleSheetsVarsPeriodicity.plugin)" ></select >');
        row2.append('<button style="margin-right:2px; margin-top: 30px" class="button small" ng-click="enableGoogleSheets()">Enable Google Sheets</button>');
        row2.append('<button class="button small" style= "margin-top: 30px" ng-click="disableGoogleSheets()">Disable Google Sheets</button><br>');
        googleSheetsconfigurationSection.append(row2);

        action_buttons = $("<div class='config_save_button'></div>");
        action_buttons.append('<button class="button small" ng-click="saveGoogleSheets()">Save Google Sheets Vars</button><br>');
        googleSheetsconfigurationSection.append(action_buttons);

        $compile(configurationSection)($scope);



    });

}