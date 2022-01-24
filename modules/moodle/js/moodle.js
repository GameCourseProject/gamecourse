//add config page to course
app.stateProvider.state('course.settings.moodle', {
    url: '/moodle',
    views: {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'moodle'
    }
});


function moodlePersonalizedConfig($scope, $element, $smartboards, $compile) {
    $scope.changeLimit = function (moodle) {

        var periodicity1 = document.getElementById(moodle + "Periodicidade1");
        var periodicity2 = document.getElementById(moodle + "Periodicidade2");
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
            if (moodle == "moodle") {
                $scope.moodleVarsPeriodicity.number = setValue;
            }
        }
        periodicity1.setAttribute("max", maxLimit);

    }

    $scope.saveMoodle = function () {
        console.log("save moodle");
        $smartboards.request('settings', 'courseMoodle', { moodle: $scope.moodleVars, course: $scope.course }, alertUpdate);
    };
    $scope.enableMoodle = function () {
        console.log($scope.moodleVarsPeriodicity);
        $smartboards.request('settings', 'courseMoodle', { moodlePeriodicity: $scope.moodleVarsPeriodicity, course: $scope.course }, alertUpdate);
    };
    $scope.disableMoodle = function () {
        $smartboards.request('settings', 'courseMoodle', { disableMoodlePeriodicity: true, course: $scope.course }, alertUpdate);
    };

    $smartboards.request('settings', 'courseMoodle', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        $scope.moodleVars = data.moodleVars;
        $scope.moodleVarsPeriodicity = data.moodleVarsPeriodicity;

        var configurationSection = $($element);

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
        row2.append('<select class="form-control config_input" style="margin-left: 180px; margin-top: -30px" ng-model="moodleVarsPeriodicity.time" id="moodlePeriodicidade2" ng-options="option.name for option in moodleVarsPeriodicity.availableOptions track by option.id" ng-change="changeLimit(moodleVarsPeriodicity.plugin)" ></select >');
        row2.append('<button style="margin-right:2px; margin-top: 30px" class="button small" ng-click="enableMoodle()">Enable Moodle</button>');
        row2.append('<button class="button small" style= "margin-top: 30px" ng-click="disableMoodle()">Disable Moodle</button><br>');
        moodleconfigSectionPeriodicity.append(row2);
        moodleconfigurationSection.append(moodleconfigSectionPeriodicity);

        action_buttons = $("<div class='config_save_button'></div>");
        action_buttons.append('<button class="button small" ng-click="saveMoodle()">Save Moodle Vars</button><br>');
        moodleconfigurationSection.append(action_buttons);

        $compile(configurationSection)($scope);

    });

}