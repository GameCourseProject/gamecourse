angular.module('module.notifications', []);

angular.module('module.notifications').directive('notificationDirective', function($state, $smartboards) {
    return {
        link: function($scope, $element) {
            $element.find('.close').click(function() {
                $smartboards.request('notifications', 'removeNotification', {course: $scope.part.data.course.value, notification: $scope.part.data.notificationId.value}, function(data, err) {
                    if (err) {
                        giveMessage('Failed to remove notification!');
                        console.log(err.description);
                        return;
                    }

                    $($element).remove();
                });
            });
        }
    }
});
