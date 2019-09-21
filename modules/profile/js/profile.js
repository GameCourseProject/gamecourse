angular.module('module.profile', []);

angular.module('module.profile').controller('Profile', function($element, $scope, $smartboards, $stateParams, $compile, $sbviews) {
    $scope.userID = $stateParams.userID;
    $sbviews.request('Profile', {course: $scope.course, user: $scope.userID}, function(view, err) {
        if (err) {
            console.log(err);
            return;
        }

        $element.append(view.element);
        changeTitle('Profile', 1); //TODO: fix
    });
});

angular.module('module.profile').run(['$rootScope', '$state', function($rootScope, $state) {
    $rootScope.$on('$stateChangeSuccess', function(e, toState, toParams, fromState, fromParams) {
        if (toState.name == 'profile' && toParams.userID == $rootScope.user.id) {
            $(document).find('a[ui-sref="course.myprofile"]').addClass('active');
        } else if (fromState.name == 'profile' && fromParams.userID == $rootScope.user.id) {
            $(document).find('a[ui-sref="course.myprofile"]').removeClass('active');
        }
    });
}]);

angular.module('module.profile').config(function($stateProvider) {
    $stateProvider.state('course.myprofile', {
        url: '/myprofile',
        views: {
            'main-view@': {
                controller: ['$state', '$rootScope', function ($state, $rootScope) {
                    $state.go('course.profile', {userID: $rootScope.user.id}, {location: 'replace'});
                }]
            }
        }
    }).state('course.profile', {
        url: '/profile/{userID:[0-9]{1,5}}',
        views: {
            'main-view@': {
                controller: 'Profile'
            }
        }
    });
});