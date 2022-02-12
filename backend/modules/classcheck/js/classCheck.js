//add config page to course
app.stateProvider.state('course.settings.classcheck', {
    url: '/classcheck',
    views: {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'classcheck'
    }
});


function classCheckPersonalizedConfig($scope, $element, $smartboards, $compile) {
    $scope.changeLimit = function (classcheck) {

        var periodicity1 = document.getElementById(classcheck + "Periodicidade1");
        var periodicity2 = document.getElementById(classcheck + "Periodicidade2");
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
            if (classcheck == "classCheck") {
                $scope.classCheckVarsPeriodicity.number = setValue;
            }
        }
        periodicity1.setAttribute("max", maxLimit);

    }

    $scope.enableClassCheck = function () {
        console.log($scope.classCheckVarsPeriodicity);
        $smartboards.request('settings', 'courseClassCheck', { classCheckPeriodicity: $scope.classCheckVarsPeriodicity, course: $scope.course }, alertUpdate);
    };
    $scope.disableClassCheck = function () {
        $smartboards.request('settings', 'courseClassCheck', { disableClassCheckPeriodicity: true, course: $scope.course }, alertUpdate);
    };
    $scope.saveClassCheck = function () {
        console.log("save class check");
        $smartboards.request('settings', 'courseClassCheck', { classCheck: $scope.classCheckVars, course: $scope.course }, alertUpdate);
    };

    $smartboards.request('settings', 'courseClassCheck', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        $scope.classCheckVarsPeriodicity = data.classCheckVarsPeriodicity;
        $scope.classCheckVars = data.classCheckVars;

        var configurationSection = $($element);

        if ($scope.classCheckVars.periodicityTime == "Minutes") {
            $scope.classCheckVars.periodicityTimeId = 1;
        } else if ($scope.classCheckVars.periodicityTime == "Hours") {
            $scope.classCheckVars.periodicityTimeId = 2;
        } else if ($scope.classCheckVars.periodicityTime == "Day") {
            $scope.classCheckVars.periodicityTimeId = 3;
        }

        $scope.classCheckVarsPeriodicity = {
            number: $scope.classCheckVars.periodicityNumber,
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
        classCheckconfigurationSection.attr("class", "column content");
        row = $("<div class='plugin_row'></div>");
        row.append('<span>TSV Code: </span>');
        row.append('<input class="config_input" type:"text" id="newList" ng-model="classCheckVars.tsvCode"><br>');
        classCheckconfigurationSection.append(row);
        row2 = $("<div class='plugin_row periodicity'></div>");
        row2.append('<span>Periodicity: </span>');
        row2.append('<input class="config_input" ng-init="classCheckVarsPeriodicity.number" ng-model="classCheckVarsPeriodicity.number" type="number" id="classCheckPeriodicidade1" min="0" max="59">');
        row2.append('<select class="form-control config_input" style="margin-left: 180px; margin-top: -30px" ng-model="classCheckVarsPeriodicity.time" id="classCheckPeriodicidade2" ng-options="option.name for option in classCheckVarsPeriodicity.availableOptions track by option.id" ng-change="changeLimit(classCheckVarsPeriodicity.plugin)" ></select >');
        //row2.append('<button style="margin-right:2px;background-color:green; margin-top: 30px" class="button small" ng-click="enableClassCheck()">Enable Class Check</button>');
       // row2.append('<button class="button small" style="background-color:red; margin-top: 30px" ng-click="disableClassCheck()">Disable Class Check</button><br>');
        row2.append('<button style="margin-right:2px;" class="button small" ng-click="enableClassCheck()">Enable Class Check</button>');
        row2.append('<button class="button small" ng-click="disableClassCheck()">Disable Class Check</button><br>');
        classCheckconfigurationSection.append(row2);

        action_buttons = $("<div class='config_save_button'></div>");
        action_buttons.append('<button class="button small" ng-click="saveClassCheck()">Save Class Check Vars</button><br>');
        classCheckconfigurationSection.append(action_buttons);

        $compile(configurationSection)($scope);


    });

}