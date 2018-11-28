angular.module('module.qr', []);

angular.module('module.qr').controller('QRController', function ($element, $scope, $sbviews, $compile) {
    changeTitle('QR', 1);

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
//qrCodesGenerator.append(cena);
        var qrGenForm = $('<div>\
                        <label for="qr-quantity" class="label">How many QR codes? </label>');
        var qrQuantityInput = $('<input>', {type: 'number', id:'qr-quantity', 
               'class': 'input-text', placeholder:'', 'ng-model':'data.qrQuantity'});
        qrGenForm.append($compile(qrQuantityInput)($scope));
        

//working if generator.php and other files of qr are in the main smartboards folder, should be changed
//functionalities should be written in here
//redirecting to external qr page
        qrGenForm.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \
href="http://localhost/qr/generator.php?quantos={{data.qrQuantity}}&palavra=password">Generate</a>')($scope));
//generator.php?quantos=1&palavra=password
        qrCodesGenerator.append(qrGenForm);

        
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