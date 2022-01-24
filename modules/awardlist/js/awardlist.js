angular.module('module.awardlist', []);

angular.module('module.awardlist').controller('AwardListController', function ($element, $scope, $sbviews) {
    changeTitle('Award List', 1);

    $sbviews.request('awardlist', {course: $scope.course}, function(view, err) {
        if (err) {
            console.log(err);
            return;
        }

        $element.append(view.element);
    });
});

angular.module('module.awardlist').config(function ($stateProvider) {
    $stateProvider.state('course.awardlist', {
        url: '/awardlist',
        views: {
            'main-view@': {
                controller: 'AwardListController'
            }
        }
    });
});