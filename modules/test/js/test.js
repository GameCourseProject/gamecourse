angular.module('module.test', []);

angular.module('module.test').controller('TestController', function ($element, $scope, $sbviews) {
    changeTitle('Test', 1);

    $sbviews.request('test', {course: $scope.course}, function(view, err) {
        if (err) {
            console.log(err);
            return;
        }

        $element.append(view.element);
    });
});
angular.module('module.test').config(function ($stateProvider) {
    $stateProvider.state('course.test', {
        url: '/test',
        views: {
            'main-view@': {
                controller: 'TestController'
            }
        }
    });
});