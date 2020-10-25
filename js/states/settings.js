
app.run(['$rootScope', '$state', function ($rootScope, $state) {
    $rootScope.$on('$stateChangeSuccess', function (e, toState, toParams, fromState, fromParams) {
        if (toState.name.indexOf('settings') == 0 || toState.name.indexOf('course.settings') == 0) {
            updateTabTitle(toState.name, toParams);
        }

        if (toState.name == 'settings') {
            e.preventDefault();
            $state.go('settings.global');
        } else if (toState.name == 'course.settings') {
            e.preventDefault();
            $state.go('course.settings.global');
        }
    });
}]);

app.config(function($stateProvider){
    //saving the state provider so we can latter and config pages
    //so they are only available when module is activated
    app.stateProvider = $stateProvider;
    $stateProvider.state('settings', {
        url: '/settings',
        views: {
            'side-view@': {
                template: ''
            },
            'main-view@': {
                templateUrl: 'partials/settings.html',
                controller: 'Settings'
            }
        }
    
    //Settings of the system
    }).state('settings.global', {
        url: '/global',
        views : {
            'tabContent': {
                template: '',
                controller: 'SettingsGlobal'
            }
        }
    }).state('settings.modules', {
        url: '/modules',
        views : {
            'tabContent': {
                controller: 'SettingsModules'
            }
        }
    }).state('settings.about', {
        url: '/about',
        views : {
            'tabContent': {
                templateUrl: 'partials/settings/about.html'
            }
        }

    //settings of the course
    }).state('course.settings', {
        url: '/settings',
        views: {
            'side-view@': {
                template: ''
            },
            'main-view@': {
                templateUrl: 'partials/settings.html',
                controller: 'CourseSettings'
            }
        }
    }).state('course.settings.global', {
        url: '/global',
        views : {
            'tabContent': {
                template: '',
                controller: 'CourseSettingsGlobal'
            }
        }
    }).state('course.settings.modules', {
        url: '/modules',
        views : {
            'tabContent': {
                template: '',
                controller: 'CourseSettingsModules'
            }
        }
    }).state('course.settings.roles', {
        url: '/roles',
        views : {
            'tabContent': {
                controller: 'CourseRolesSettingsController'
            }
        }
    });
});