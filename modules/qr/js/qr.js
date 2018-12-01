angular.module('module.qr', []);


angular.module('module.qr').controller('QRController', function ($element, $scope, $sbviews, $compile,$http, $filter) {
    changeTitle('QR', 1);
    
    //TO be deleted, now is refreshing everytime so it uses the latest code
    $scope.$emit('refreshTabs');
     
    
    
    $sbviews.request('qr', {course: $scope.course}, function(data, err) {
        if (err) {
            console.log(err);
            return;
        }
        //$element.append(view.element);

        var tabContent = $($element);
        $scope.data = data;

        // QR Codes Generator
        var qrCodesGenerator = createSection(tabContent, 'QR Codes Generator');

        var qrGenForm = $('<div>\
                        <label for="qr-quantity" class="label">How many QR codes? </label>');
        var qrQuantityInput = $('<input>', {type: 'number', id:'qr-quantity', 
               'class': 'input-text', placeholder:'', 'ng-model':'data.qrQuantity'});
        qrGenForm.append($compile(qrQuantityInput)($scope));
        
        qrGenForm.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \
        href="modules/qr/generator.php?quantos={{data.qrQuantity}}&palavra=password">Generate</a>')($scope));
        
        /*qrGenForm.append($('<button>', {'ng-show': 'data.qrQuantity>0','ng-click': 'generateQR()', text: 'Generate'}));
        
        //Create QRs and tiny URLs
        $scope.generateQR = function() {
            $http.get('modules/qr/generator.php?quantos='+data.qrQuantity+'&palavra=password').success(function(response) {
                qrGenForm.append($(response));
            }).error(function(response){
                console.log("Error with request to get QR: "+response);
            }); 
        };//the database used and the link that the QR is pointing to are still external
        */
        qrCodesGenerator.append($compile(qrGenForm)($scope));
        
        var participationList = createSection(tabContent, 'Check Participations List');
        participationList.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \
        href="modules/qr/report.php">List</a>')($scope));
        
        var checkFailedAttempts = createSection(tabContent, 'Check failled attemps of QR use');
        checkFailedAttempts.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \
        href="modules/qr/fails.php">List</a>') ($scope));
        
        data.showingAttempsList=false;
        var errorList = checkFailedAttempts.append($('<button>', {'ng-disabled': 'data.showingAttempsList==true','ng-click': 'showQRAttemptsList()', text: 'Show List'}));
        $scope.showQRAttemptsList = function() {
            $http.get('modules/qr/fails.php').success(function(response) {
                errorList.append($('<br/>'));
                errorList.append($(response));
                data.showingAttempsList=true;
            }).error(function(response){
                console.log("Error with request to get QR: "+response);
            }); 
        };//the database used is still external
        checkFailedAttempts.append($compile(errorList)($scope));
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