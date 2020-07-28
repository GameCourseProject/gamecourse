//add config page to course
app.stateProvider.state('course.settings.plugins', {
    url: '/plugins',
    views : {
        'tabContent': {
            controller: 'CoursePluginsSettingsController'
        }
    }
});