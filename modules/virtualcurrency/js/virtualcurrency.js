angular.module('module.virtualcurrency', []);

app.stateProvider.state('course.settings.virtualcurrency', {
    url: '/virtualcurrency',
    views: {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'virtualcurrency'
    }
});
