//states
app.config(function($locationProvider, $compileProvider, $stateProvider){
    $locationProvider.html5Mode(true);
    $compileProvider.debugInfoEnabled(false);

    $stateProvider.state('home', {
        url: '/',
        views: {
            'main-view': {
                controller: 'HomePage'
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
    }).state('courses.create', {
        url: '/create',
        views : {
            'main-view@': {
                controller: 'CourseCreate'
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
    });
});