angular.module('module.sideview', []);
angular.module('module.sideview').controller('SideView', function($scope, $smartboards, $compile, $sbviews) {
    $scope.refreshSideView = function() {
        if ($scope.course == undefined || $scope.course == $scope.thisCourse)
            return;
        $scope.thisCourse = $scope.course;

        $sbviews.request('sideview', {course: $scope.course}, function(view, err) {
            if (err) {
                console.log(err);
                return;
            }

            var container = $('#side-view');
            container.html('');
            container.append(view.element);

            // Side Links
            var linksImage = $sbviews.defaultPart('image');
            linksImage.info = 'images/leaderboard.svg';
            var linksTitle = $sbviews.defaultPart('value');
            linksTitle.info = 'Links';

            var sideLinksEl = $sbviews.buildStandalone({
                type: 'block',
                header: {
                    'image': linksImage,
                    'title': linksTitle
                },
                class: 'side-links',
                children: {
                }
            });
            sideLinksEl.children('.content').append('<a href="#Badges">Badges</a>');

            sideLinksEl.hide();

            var el = sideLinksEl.children('.content');
            function refreshLinks() {
                el.html('');
                var numLinks = 0;
                $('.block > .header > a').each(function() {
                    var img = $(this).parent().find('img').get(0).outerHTML;
                    var text = $(this).parent().children('.title').text();
                    if (text.indexOf('(') != -1)
                        text = text.substr(0, text.indexOf('('));
                    el.append('<a href="#' + $(this).attr('name') + '">' + img + text + '</a>');
                    numLinks++;
                });

                if (numLinks == 0) {
                    sideLinksEl.hide();
                } else if (sideLinksEl.is(':hidden')) {
                    sideLinksEl.show();
                }
            }

            var target = document.querySelector('#content-wrapper');

            var observer = new MutationObserver(function(mutations) {
                refreshLinks();
            });

            var config = { attributes: true, childList: true, characterData: true, subtree: true };

            refreshLinks();
            observer.observe(target, config);
            view.element.children('.content').append(sideLinksEl);

            // Sticky!
            function testSticky() {
                var windowTop = $(window).scrollTop();
                var divDop = $('#side-view-anchor').offset().top - 16;

                if (windowTop > divDop) {
                    $('#side-view').addClass('stick');
                } else {
                    $('#side-view').removeClass('stick');
                }
            }

            $(window).scroll(testSticky);
            testSticky();
        });
    }

    $scope.$on('$stateChangeSuccess', function() {
        $scope.refreshSideView();
    });
});

angular.module('module.sideview').run(function($rootScope, $compile, $state) {
    var sideView = $('<div>', {id: 'side-view', 'ng-controller': 'SideView'});
    $('#content-wrapper').before($compile(sideView)($rootScope));
    sideView.before($('<div>', {id: 'side-view-anchor'}));
    $rootScope.$on('$stateChangeSuccess', function(e, toState) {
        if (toState.name == 'course.profile' || toState.name == 'course.leaderboard')
            $('#wrapper').addClass('sv-visible');
        else
            $('#wrapper').removeClass('sv-visible');
    });
});
