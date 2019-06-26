angular.module('module.skills', []);
angular.module('module.skills').controller('SkillPage', function($scope, $smartboards, $stateParams, $compile) {
    $scope.skillName = $stateParams.skillName;
    $smartboards.request('skills', 'page', {course: $scope.course, skillName: $scope.skillName}, function(data, err) {
        if (err) {
            if (err.status == '404')
                $('#skill-description-container').text(err.description);
            else
                window.location = document.baseURI;
            return;
        }

        $scope.skill = data;
        changeTitle('Skill - ' + data.name, 1);
        $('#skill-description-container').append($compile(Builder.createPageBlock({
            'image': 'images/skills.svg',
            'text': 'Skill - {{skill.name}}'
        }, function(el) {
            el.attr('class', 'content');
            el.append($('<div>', { 'class': 'text-content', 'bind-trusted-html': 'skill.description'}));
        }))($scope));
    });
});

angular.module('module.skills').directive('skillBlock', function($state) {
    return {
        link: function($scope, $element) {
            $scope.gotoSkillPage = function(skill) {
                $state.go('course.skill', {'skillName': skill.data.skillName.value});
            };

            // disable propagation of Post links
            $element.find('a').on('click', function(e) { e.stopPropagation(); });
        }
    };
});

angular.module('module.skills').config(function($stateProvider) {
    $stateProvider.state('course.skill', {
        url: '/skill/{skillName:[A-z0-9]+}',
        views: {
            'main-view@': {
                template: '<div id="skill-description-container"></div>',
                controller: 'SkillPage'
            }
        }
    });
});

angular.module('module.skills').directive('skillStudentImage', function($state) {
    return {
        scope: false,
        link: function($scope, $element, $attrs) {
            $scope.gotoProfile = function(part) {
                $element.trigger('mouseout');
                $state.go('course.profile', {'userID': part.data.student.value.id});
            };
            $scope.tooltipBound = false;
            $scope.showSkillTooltip = function(part) {
                if ($scope.tooltipBound)
                    return;
                var user = part.data.student.value;

                var tooltipContent = $('<div>', {'class': 'content'});
                tooltipContent.append($('<img>', {'class': 'student-image', src: 'photos/' +  user.username + '.png'}));
                var tooltipUserInfo = $('<div>', {'class': 'userinfo'});
                tooltipUserInfo.append($('<div>', {'class': 'name', text: user.name + ' [' + user.campus + ']'}));
                tooltipUserInfo.append($('<div>', {text: 'Date: ' + user.when}));
                tooltipContent.append(tooltipUserInfo);

                $element.tooltip({offset: [-150, -65], html: tooltipContent});
                $scope.tooltipBound = true;
                $element.trigger('mouseover');
            };
        }
    };
});


Builder.onPageBlock('skills-overview', function(el, info) {
    el.addClass('skills-overview');
    for (var i = 0; i < info.content.length; ++i) {
        var tier = info.content[i];
        var tierContainer = $('<div>', {'class': 'tier-container'});
        for (var j = 0; j < tier.skills.length; ++j) {
            var skill = tier.skills[j];
            var container = $('<div>');
            var skillSquare = $('<div>', {'class': 'skill-square', text: skill.name + ' - ' + skill.users.length});
            skillSquare.css({backgroundColor: skill.color});
            container.append(skillSquare);
            var photosContainer = $('<span>', {'class': 'photos-container'});
            container.append(photosContainer);

            for (var u = 0; u < skill.users.length; ++u) {
                var user = skill.users[u];
                var userImage = $('<img>', {'class': 'student-image', src: user.photo});

                (function(userid) {
                    userImage.click(function() {
                        $(this).closest('#overview-container').data('state').go('course.profile', {userID: userid});
                    });
                })(user.id);

                photosContainer.append(userImage);
            }

            tierContainer.append(container);
        }
        el.append(tierContainer);
    }
});
