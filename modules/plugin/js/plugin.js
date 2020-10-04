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
            alertUpdateAndReload(data, err);
            authUrl = data.authUrl;
        });
    }
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
        i=1;
        $scope.googleSheetsVars.sheetName=[];
        while (i <= $scope.numberGoogleSheets){
            id = "#sheetname" + i;
            sheetname = $(id)[0].value;
            $scope.googleSheetsVars.sheetName.push(sheetname);
            i++;
        }
        $smartboards.request('settings', 'coursePlugin', { googleSheets: $scope.googleSheetsVars, course: $scope.course }, alertUpdateAndReload);
    };
    $scope.addExtraField = function(){
        inputs = $("#sheet_names");
        $scope.numberGoogleSheets++;
        inputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="sheetname'+ $scope.numberGoogleSheets +'">');
    }


    $smartboards.request('settings', 'coursePlugin', { course: $scope.course }, function (data, err) {
        if (err) {
            console.log(err);
            return;
        }

        $scope.fenixVars = data.fenixVars;
        $scope.moodleVars = data.moodleVars;
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
            } else if (model == "sheetName"){
                $scope.numberGoogleSheets = 0;
                if($scope.googleSheetsVars.sheetName.length != 0){
                    jQuery.each($scope.googleSheetsVars.sheetName, function (index){
                        sheetName = $scope.googleSheetsVars.sheetName[index];
                        $scope.numberGoogleSheets++;
                        googleSheetsInputs.append('<span id="sheet_names"><input type:"text" style="width: 25%;margin: 5px;" value="'+ sheetName +'" id="sheetname'+ $scope.numberGoogleSheets +'"></span>');
                    });
                }
                else{
                    $scope.numberGoogleSheets++;
                    googleSheetsInputs.append('<span id="sheet_names"><input type:"text" style="width: 25%;margin: 5px;" id="sheetname'+ $scope.numberGoogleSheets +'"></span>');
                }
                googleSheetsInputs.append('<button class="button small" ng-click="addExtraField()">Add another sheet</button><br><br>');

            } else {
                googleSheetsInputs.append('<input type:"text" style="width: 25%;margin: 5px;" id="newList" ng-model="googleSheetsVars.' + model + '"><br>');
            }
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
}