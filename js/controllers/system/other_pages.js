//Controllers for pages of the system, except for setting pages

app.controller('MyInfo', function ($element, $scope, $smartboards, $compile, $state) {


    $smartboards.request('core', 'getUserInfo', {}, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }
        $scope.myInfo = data.userInfo;

        myInfo = $("<div id='myInfo'></div>");
        title = $("<div class='title'>My Information</div>");
        subtitle = $("<div class='warning'>If some of the following information is not right please contact your Teacher to fix it.</div>");
        informationbox = $("<div class='information_box'></div>");
        image = $('<div class="profile_image"></div>');

        var profile_image = new Image();
        profile_image.onload = function () {
            image.append(profile_image);
        }
        profile_image.onerror = function () {
            image.append($('<span>No profile image was selected</span>'));
        }
        profile_image.src = 'photos/' + $scope.myInfo.id + '.png?' + new Date().getTime();



        atributes = ['Name', 'Nickname', 'Student Number', 'Email', 'Authentication', 'Username']
        values = ['name', 'nickname', 'studentNumber', 'email', 'authenticationService', 'username']
        atributes_column = $("<div class='info'></div>");
        values_column = $("<div class='info'></div>");
        jQuery.each(atributes, function (index) {
            atributes_column.append($('<span class="label">' + atributes[index] + '</span>'));
            if ($scope.myInfo[values[index]] == null) {
                values_column.append($('<span class="not_set">Not set</span>'));
            } else {
                values_column.append($('<span>' + $scope.myInfo[values[index]] + '</span>'));
            }

        });
        informationbox.append(image);
        informationbox.append(atributes_column);
        informationbox.append(values_column);

        myInfo.append(title);
        myInfo.append(informationbox);
        myInfo.append(subtitle);
        $element.append(myInfo);
    });

});
