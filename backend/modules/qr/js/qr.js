//add config page to course
app.stateProvider.state('course.settings.qr', {
    url: '/qr',
    views : {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'qr'
    }
});

angular.module('module.qr', []);

function qrPersonalizedConfig($scope, $element, $smartboards, $compile){
    
    var tabContent = $($element);
    // QR Codes Generator
    var qrCodesGenerator = createSection(tabContent, 'QR Code Generator');

    var qrGenForm = $('<div>\
                    <label for="qr-quantity" class="label">How many QR codes? </label>');
    var qrQuantityInput = $('<input>', {type: 'number', id:'qr-quantity', min:'1',
            'class': 'input-text', placeholder:'', 'ng-model':'data.qrQuantity'});
    qrGenForm.append($compile(qrQuantityInput)($scope));
    
    qrGenForm.append($compile('<a style="text-decoration: none; font-size: 80%;" class="button" target="_blank" \\n\
    href="modules/qr/generator.php?quantos={{data.qrQuantity}}&course={{course}}">Generate</a>')($scope));
        
    qrCodesGenerator.append($compile(qrGenForm)($scope));
    
    //Button to show lists of participations
    var participationList = createSection(tabContent, 'Participation List');
    var participationButton = $('<button>',{text: 'Show List'}).click(function() {
        window.open("modules/qr/report.php?course=" + $scope.course,"_blank");
    });
        participationList.append(participationButton);

    var checkFailedAttempts = createSection(tabContent, 'Failled attemps of QR use');
    
    $smartboards.request('settings', 'qrError', {course: $scope.course}, function(data, err){
        if (err) {
            giveMessage(err.description);
            return;
        }
        $scope.allErrors = data.errors;

        var errors = $("<div id='errors'></div>")
        var errorSection = $('<div class="data-table" ></div>');
        var table = $('<table id="error-table">');
        rowHeader = $("<tr></tr>");
        rowHeader.append("<th> Date </th><th> Student Nº </th><th> Error </th><th> QR </th>");
        table.append(rowHeader);
        rowContent = $("<tr ng-repeat='item in allErrors'>");
        rowContent.append("<td>{{item.date}}</td>");
        rowContent.append("<td>{{item.studentNumber}}</td>");
        rowContent.append("<td>{{item.msg}}</td>");
        rowContent.append("<td>{{item.qrkey}}</td>");
        table.append(rowContent);
        table.append("</tr></table>");
        errorSection.append($compile(table)($scope));
        errors.append(errorSection);
        checkFailedAttempts.append(errors);
    });
}
