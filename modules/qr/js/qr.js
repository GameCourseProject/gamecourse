angular.module('module.qr', []);


angular.module('module.qr').controller('QRController', function ($element, $scope, $sbviews, $compile, $filter) {
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

        
        //the link inside the QR is to the external page, and it's not putting anything in DB yet
        //option 1: redirecting to page w qr page
        qrGenForm.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \
        href="modules/qr/generator.php?quantos={{data.qrQuantity}}&palavra=password">Generate</a>')($scope));
        //option 2: show qr in the same page
        qrGenForm.append($('<button>', {'ng-show': 'data.qrQuantity>0','ng-click': 'generateQR()', text: 'Generate'}));

         //replacing generator.php
        $scope.generateQR = function() {
            //check password, may not be necessary
            //connect to database
            //var datagen=date('YmdHis');
            var datagen = $filter('date')(new Date(),'yyyyMMddHHmmss');
            console.log(datagen);
            var max = data.qrQuantity;
            console.log(max);
            for (i = 1; i <= max; i++) {
                //uid=uniqid();  nao ha funcao disto para js
                var uid = i; //FIX ME
                var separator =';';
                var key = datagen+separator+uid;//tinha password
                console.log(key);
                var url = "http://localhost/qr/index.php?key="+key;
                //var tinyurl = getTinyURL(url);  ajax ou traduzir o php de getTinyURL
                
                //sql
                //query
                //<div id="tinyQR"><img src="qrcode.php?url=<?=$url?>" alt="<?=$url?>" /><br/><?=substr($tinyurl,7);?></div>
        
            }
        };
        
        qrCodesGenerator.append($compile(qrGenForm)($scope));
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