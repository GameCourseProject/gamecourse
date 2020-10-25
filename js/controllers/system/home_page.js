app.controller('HomePage', function($element, $scope, $timeout, $smartboards, $compile) {
    $scope.setNavigation([], []);
    $timeout(function() {
        $scope.defaultNavigation();
        $timeout(function() {
            addActiveLinks('home');
            beginNavbarResize();
        });
    });
    changeTitle('', 0, false);

    $smartboards.request('core', 'getUserActiveCourses', {}, function(data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }
        $scope.userActiveCourses = data.userActiveCourses;

        //$("#user_icon").addClass("bold");
        mainPage = $("<div id='mainPage'></div>");
        
        logo = $('<div class="logo"></div>')
        title = $("<div class='title'>Welcome to the GameCourse system</div>");
        informationbox = $("<div id='active_courses_list'></div>");
        informationbox.append($('<span class="label">Your active courses</span>'))
        informationbox.append('<span ng-repeat="(i, course) in userActiveCourses"  ui-sref="course({courseName:course.nameUrl, course: course.id})">{{course.name}}</span>');
        
        mainPage.append(logo);
        mainPage.append(title);
        mainPage.append(informationbox);
        $element.append(mainPage);
        $compile(mainPage)($scope);
    });
});