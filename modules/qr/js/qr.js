angular.module('module.qr', []);

angular.module('module.qr').controller('QRController', function ($element, $scope, $sbviews, $compile,$http) {
    changeTitle('QR', 1);
        
    $sbviews.request('qr', {course: $scope.course}, function(data, err) {
        if (err) {
            console.log(err);
            return;
        }
        $element.append(data.element);// contains what was defined in the view editor, can be empty

        var tabContent = $($element);
        $scope.data = data;

        // QR Codes Generator
        var qrCodesGenerator = createSection(tabContent, 'QR Codes Generator');

        var qrGenForm = $('<div>\
                        <label for="qr-quantity" class="label">How many QR codes? </label>');
        var qrQuantityInput = $('<input>', {type: 'number', id:'qr-quantity', min:'1',
               'class': 'input-text', placeholder:'', 'ng-model':'data.qrQuantity'});
        qrGenForm.append($compile(qrQuantityInput)($scope));
        
        qrGenForm.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \\n\
        href="modules/qr/generator.php?quantos={{data.qrQuantity}}&course={{course}}">Generate</a>')($scope));
        //generator.php?quantos={{data.qrQuantity}}&palavra=password&course={{course}}
         
        qrCodesGenerator.append($compile(qrGenForm)($scope));
        
        //Buttons to show lists of participations and failed attempts
        var participationList = createSection(tabContent, 'Check Participations List');
        var participationButton = $('<button>',{text: 'Show List'}).click(function() {
            window.open("modules/qr/report.php?course=" + $scope.course,"_blank");
        });
         participationList.append(participationButton);
        //participationList.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \
        //href="modules/qr/report.php?course={{course}}">List</a>')($scope));
        
        var checkFailedAttempts = createSection(tabContent, 'Check failled attemps of QR use');
        /*checkFailedAttempts.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \
        href="modules/qr/fails.php?course={{course}}">List</a>') ($scope));
        */
        data.showingAttempsList=false;
        var errorList = checkFailedAttempts.append($('<button>', {'ng-disabled': 'data.showingAttempsList==true','ng-click': 'showQRAttemptsList()', text: 'Show List'}));
        $scope.showQRAttemptsList = function() {
            $http.get('modules/qr/fails.php?course='+$scope.course).success(function(response) {
                errorList.append($('<br/>'));
                errorList.append($(response));
                data.showingAttempsList=true;
            }).error(function(response){
                console.log("Error with request to get QR: "+response);
            }); 
        };
        checkFailedAttempts.append($compile(errorList)($scope));
        /*$scope.enableQR = function () {
            var periodicity1 = document.getElementById("qrPeriodicidade1");
            var periodicity2 = document.getElementById("qrPeriodicidade2");
            var selectedNumber = periodicity1.value;
            var selectedOption = periodicity2.options[periodicity2.selectedIndex].text;
            $http.post("modules/qr/periodicity.php?course=" + $scope.course + "&number=" + selectedNumber + "&time=" + selectedOption).success(function (response) {
                console.log(response);
                if (response.trim() == "Please select a periodicity") {
                    giveMessage("Please select a periodicity");
                } else if (response.trim() != "") {
                    giveMessage(response);
                } else {
                    window.location.reload(); 
                }
            }).error(function (response) {
                console.log(response);
            });
        }
        $scope.disableQR = function () {
            $http.post("modules/qr/periodicity.php?course=" + $scope.course + "&disable=true").success(function (response) {
                if (response.trim() != "") {
                    giveMessage(response);
                } else {
                    window.location.reload(); 
                }
            }).error(function (response) {
                console.log(response);
            });
        }
        $scope.changeLimit = function () {

            var periodicity1 = document.getElementById("qrPeriodicidade1");
            var periodicity2 = document.getElementById("qrPeriodicidade2");
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
                $scope.qrVarsPeriodicity.number = setValue;
            }
            periodicity1.setAttribute("max", maxLimit);

        }
        $http.get('modules/qr/periodicity.php?course=' + $scope.course).success(function (response) {
            var periodicityCourseQR = response.trim().split(";");
            $scope.qrVarsPeriodicityNumber = parseInt(periodicityCourseQR[0]);
            $scope.qrVarsPeriodicityTime = periodicityCourseQR[2];
            $scope.qrVarsPeriodicityTimeId = 1;
            if ($scope.qrVarsPeriodicityTime == "Hours") {
                $scope.qrVarsPeriodicityTimeId = 2;
            } else if ($scope.qrVarsPeriodicityTime == "Day") {
                $scope.qrVarsPeriodicityTimeId = 3;
            }
            $scope.qrVarsPeriodicity = {
                number: $scope.qrVarsPeriodicityNumber,
                availableOptions: [
                    { id: '1', name: 'Minutes' },
                    { id: '2', name: 'Hours' },
                    { id: '3', name: 'Day' }
                ],
                time: { id: $scope.qrVarsPeriodicityTimeId, name: $scope.qrVarsPeriodicityTime },
            };
            var qrPeriodicity = createSection(tabContent, 'QR Codes Periodicity');
            var row2 = $("<div class='plugin_row periodicity'></div>");
            row2.append('<input class="config_input" ng-init="qrVarsPeriodicity.number" ng-model="qrVarsPeriodicity.number" type="number" id="qrPeriodicidade1" min="0" max="59">');
            row2.append('<select class="form-control config_input" style="margin-right:10px" ng-model="qrVarsPeriodicity.time" id="qrPeriodicidade2" ng-options="option.name for option in qrVarsPeriodicity.availableOptions track by option.id" ng-change="changeLimit(qrVarsPeriodicity.plugin)" ></select >');
            row2.append('<button style="margin-right:2px" class="button small" ng-click="enableQR()">Enable QR</button>');
            row2.append('<button class="button small" ng-click="disableQR()">Disable QR</button><br>');
            qrPeriodicity.append($compile(row2)($scope));
        }).error(function (response) {
            console.log("Error with request to get QR: " + response);
        });*/
    });

});
angular.module('module.qr').config(function ($stateProvider) {
    $stateProvider.state('course.qr', {
        url: '/qr',
        views: {
            'main-view@': {
                controller: 'QRController'
            }
        }
    });
});