//add config page to course
app.stateProvider.state('course.settings.xp', {
    url: '/xp',
    views : {
        'tabContent': {
            controller: 'CourseLevelsSettingsController'
        }
    },
    params: {
        'module': 'xp'
    }
});