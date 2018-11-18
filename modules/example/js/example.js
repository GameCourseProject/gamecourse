angular.module('module.example', []);

angular.module('module.example').controller('ExampleController', function ($element, $scope, $sbviews) {
    changeTitle('Example', 1);

    $sbviews.request('example', {course: $scope.course}, function(view, err) {
        if (err) {
            console.log(err);
            return;
        }

        $element.append(view.element);
    });
});

angular.module('module.example').config(function ($stateProvider) {
    $stateProvider.state('course.example', {
        url: '/example',
        views: {
            'main-view@': {
                controller: 'ExampleController'
            }
        }
    });
});