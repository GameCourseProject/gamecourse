//add config page to course
app.stateProvider.state('course.settings.fenix', {
    url: '/fenix',
    views: {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'fenix'
    }
});

function fenixPersonalizedConfig($scope, $element, $smartboards, $compile) {

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
        $smartboards.request('settings', 'courseFenix', { fenix: lines, course: $scope.course }, alertUpdate);
    }

    $smartboards.request('settings', 'courseFenix', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        $scope.fenixVars = data.fenixVars;

        var configurationSection = $($element);

        //Fenix
        var fenixconfigurationSection = createSection(configurationSection, 'Fenix Variables');
        fenixconfigurationSection.attr("class", "multiple_inputs content");
        fenixInputs = $('<div class="row" ></div>');
        fenixInputs.append('<span">Fenix Course Id: </span>');
        fenixInputs.append('<input class="config_input fenix" type="file" accept=".csv, .txt" id="newList1" onchange="angular.element(this).scope().upload()"><br>');
        fenixconfigurationSection.append(fenixInputs);

        action_buttons = $("<div class='config_save_button'></div>");
        action_buttons.append('<button class="button small" ng-click="saveFenix()">Save Fenix Vars</button><br>');
        fenixconfigurationSection.append(action_buttons);


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