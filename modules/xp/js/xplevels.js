//add config page to course
app.stateProvider.state('course.settings.levels', {
    url: '/levels',
    views : {
        'tabContent': {
            controller: 'CourseLevelsSettingsController'
        }
    }
});