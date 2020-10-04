//add config page to course
app.stateProvider.state('course.settings.plugin', {
    url: '/plugin',
    views : {
        'tabContent': {
            controller: 'CoursePluginsSettingsController'
        }
    },
    params: {
        'module': 'plugin'
    }
});