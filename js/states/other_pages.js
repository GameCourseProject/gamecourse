//states
app.config(function ($locationProvider, $compileProvider, $stateProvider) {
    $locationProvider.html5Mode(true);
    $compileProvider.debugInfoEnabled(true);

    $stateProvider.state('home', {
        url: '/',
        views: {
            'main-view': {
                controller: 'HomePage'
            }
        }
    }).state('myInfo', {
        url: '/myInfo',
        views : {
            'main-view': {
                controller: 'MyInfo'
            }
        }
    }).state('users', {
        url: '/users',
        views : {
            'main-view': {
                controller: 'Users'
            }
        }
    }).state('courses', {
        url: '/courses',
        views: {
            'main-view': {
                controller: 'Courses'
            }
        }
    }).state('course', {
        url: '/courses/{courseName:[A-Za-z0-9]+}-{course:[0-9]+}',
        views: {
            'main-view': {
                controller: 'SpecificCourse'
            }
        }
    }).state('course.users', {
        url: '/users',
        views: {
            'main-view@': {
                controller: 'CourseUsersss'
            }
        }
    }).state('course.myInfo', { 
        //double self page so it can be accessed both inside and outside course
        url: '/myInfo',
        views : {
            'main-view@': {
                controller: 'MyInfo'
            }
        }
    });
});