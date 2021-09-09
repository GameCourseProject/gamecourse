angular.module('module.overview', []);

angular.module('module.overview').controller('OverviewController', function ($element, $scope, $sbviews) {
    changeTitle('Overview', 1);
    $sbviews.request('Overview', {course: $scope.course, needPermission:true}, function(view, err) {
        if (err) {
            console.log(err);
            return;
        }
        $element.append(view.element);
    });
});

angular.module('module.overview').config(function ($stateProvider) {
    $stateProvider.state('course.overview', {
        url: '/overview',
        views: {
            'main-view@': {
                controller: 'OverviewController'
            }
        }
    });
});