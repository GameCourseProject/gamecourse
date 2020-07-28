angular.module('module.badges', []);

angular.module('module.badges').directive('badgeDirective', function($state) {
    return {
        scope: false,
        link: function($scope, $element, $attrs) {
            $element.find('.badge-extra').hide();
            $element.click(function() {
                console.log("click");
                var isVisible = $(this).find('.badge-extra').is(':visible');
                $('.badge-extra:visible').hide();
                if (!isVisible) {
                    var badgeExtra = $(this).find('.badge-extra');
                    badgeExtra.show();
                    badgeExtra.off('click').click(function(e) {
                        e.stopPropagation();
                    });
                }
            });
        }
    };
});

angular.module('module.badges').directive('badgeStudentImage', function($state) {
    return {
        scope: false,
        link: function($scope, $element, $attrs) {
            $scope.gotoProfile = function(part) {
                $element.trigger('mouseout');
                $state.go('course.profile', {'userID': part.data.info.value.id});
            };
            $scope.tooltipBound = false;
            $scope.showBadgeTooltip = function(part) {
                
                console.log(part);
                if ($scope.tooltipBound)
                    return;
                
                var user = part.data.info.value;
                
                var tooltipContent = $('<div>', {'class': 'content'});
                tooltipContent.append($('<img>', {'class': 'student-image', src: 'photos/' +  user.username + '.png'}));
                var tooltipUserInfo = $('<div>', {'class': 'userinfo'});
                tooltipUserInfo.append($('<div>', {'class': 'name', text: user.name + ' [' + user.campus + ']'}));
                tooltipUserInfo.append($('<div>', {text: 'Date: ' + user.when}));
                if (user.progress != -1)
                    tooltipUserInfo.append($('<div>', {text: 'Progress: ' + user.progress}));
                tooltipContent.append(tooltipUserInfo);

                $element.tooltip({offset: [-150, -65], html: tooltipContent});
                $scope.tooltipBound = true;
                $element.trigger('mouseover');
            }
        }
    }
});

//add config page to course
app.stateProvider.state('course.settings.badges', {
    url: '/badges',
    views : {
        'tabContent': {
            controller: 'CourseBadgesSettingsController'
        }
    }
});
