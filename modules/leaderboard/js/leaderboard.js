angular.module('module.leaderboard', [], function ($stateProvider) {
    $stateProvider.state('course.leaderboard', {
        url: '/leaderboard',
        views: {
            'main-view@': {
                controller: 'Leaderboard'
            }
        }
    });
});

angular.module('module.leaderboard').controller('Leaderboard', function ($rootScope, $element, $scope, $sbviews, $compile, $state) {
    changeTitle('Leaderboard', 1);
    $sbviews.request('Leaderboard View', {course: $scope.course}, function(view, err) {
        if (err) {
            console.log(err);
            return;
        }

        $element.append(view.element);
    });
});

angular.module('module.leaderboard').directive('leaderboardTable', function($state) {
    return {
        link: function($scope) {
            $scope.gotoProfile = function(row) {
                $state.go('course.profile', {'userID': row.data.student.value.id});
            };
        }
    };
});