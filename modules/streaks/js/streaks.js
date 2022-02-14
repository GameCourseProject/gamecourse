angular.module('module.streaks', []);



//add config page to course
app.stateProvider.state('course.settings.streaks', {
    url: '/streaks',
    views : {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'streaks'
    }
});
