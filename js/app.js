var app = angular.module('smartBoard', ['ui.router', 'oc.lazyLoad']);

app.run(['$rootScope', '$state', function($rootScope, $state) {
    $rootScope.$on('$stateChangeSuccess', function(e, toState, toParams, fromState, fromParams) {
        removeActiveLinks();
        addActiveLinks(toState.name);
    });
}]);

function removeActiveLinks() {
    var elementsFrom = $(document).find('a.active[ui-sref]');
    if (elementsFrom.length > 0)
        elementsFrom.removeClass('active');
}

function addActiveLinks(state) {
    var sliced = state.split('.');
    for(var i = 1; i <= sliced.length; i++) {
        var elementsTo = $(document).find('a[ui-sref="' + sliced.slice(0, i).join('.') + '"]');
        if (elementsTo.length > 0)
            elementsTo.addClass('active');
    }
}

app.config(function($locationProvider, $compileProvider, $stateProvider){
    $locationProvider.html5Mode(true);
    if (location.hostname != 'localhost')
        $compileProvider.debugInfoEnabled(false);

    $stateProvider.state('home', {
        url: '/',
        views: {
            'main-view': {
                controller: function($element, $scope, $timeout) {
                    $scope.setNavigation([]);
                    $timeout(function() {
                        $scope.defaultNavigation();
                        $timeout(function() {
                            addActiveLinks('home');
                        });
                    });
                    changeTitle('', 0, false);

                    $element.append(Builder.createPageBlock({
                        image: 'images/leaderboard.svg',
                        text: 'Main Page'
                    }, function(el, info) {
                        el.append(Builder.buildBlock({
                            image: 'images/awards.svg',
                            title: 'Welcome'
                        }, function(blockContent) {
                            var divText = $('<div style="padding: 4px">');
                            divText.append('<p>Welcome to the SmartBoards system.</p>');
                            divText.append('<p>Hope you enjoy!</p>');
                            blockContent.append(divText);
                        }));
                    }));
                }
            }
        }
    }).state('courses', {
        url: '/courses',
        views: {
            'main-view': {
                controller: function($element, $scope, $smartboards, $compile) {
                    $scope.courses = {};
                    changeTitle('Courses', 0);


                    var pageBlock;
                    $element.append(pageBlock = Builder.createPageBlock({
                        image: 'images/leaderboard.svg',
                        text: 'Courses'
                    }, function(el, info) {
                        el.append(Builder.buildBlock({
                            image: 'images/awards.svg',
                            title: 'My courses'
                        }, function(blockContent) {
                            blockContent.append('<ul style="list-style: none"><li ng-repeat="(cid, course) in myCourses"><a ui-sref="course({courseName:course.nameUrl, course: cid})">{{course.name}}{{course.active ? \'\' : \' - Inactive\'}}</a></li></ul>');
                        }).attr('ng-if', 'myCourses != undefined && myCourses.length != 0'));
                        el.append(Builder.buildBlock({
                            image: 'images/awards.svg',
                            title: 'All Courses'
                        }, function(blockContent) {
                            blockContent.append('<ul style="list-style: none"><li ng-repeat="(cid, course) in courses"><a ui-sref="course({courseName:course.nameUrl, course: cid})">{{course.name}}{{course.active ? \'\' : \' - Inactive\'}}</a></li></ul>');
                        }));
                    }));
                    $compile(pageBlock)($scope);

                    $smartboards.request('core', 'getCoursesList', {}, function(data, err) {
                        if (err) {
                            alert(err.description);
                            return;
                        }

                        $scope.courses = data.courses;
                        for (var i in $scope.courses) {
                            var course = $scope.courses[i];
                            course.nameUrl = course.name.replace(/\W+/g, '');
                        }
                    });
                }
            }
        }
    }).state('course', {
        url: '/courses/{courseName:[A-Za-z0-9]+}-{course:[0-9]+}',
        views: {
            'main-view': {
                controller: function($scope, $element, $stateParams, $compile) {
                    $element.append($compile(Builder.createPageBlock({
                        image: 'images/awards.svg',
                        text: '{{courseName}}'
                    }, function(el, info) {
                    }))($scope));
                }
            }
        }
    });
});

function changeTitle(newTitle, depth, keepOthers, url) {
    var title = $('title');
    var baseTitle = title.attr('data-base');

    var titles = title.data('titles');
    if (titles == undefined)
        title.data('titles', titles = []);

    var titlesWithLinks = title.data('titles-with-links');
    if (titlesWithLinks == undefined)
        title.data('titles-with-links', titlesWithLinks = []);

    var newTitleWithLink = (url != undefined ? '<a href="' + url + '">' + newTitle + '</a>' : newTitle);

    if (depth < titles.length && !keepOthers) {
        titles.splice(depth, titles.length - depth, newTitle);
        titlesWithLinks.splice(depth, titlesWithLinks.length - depth, newTitleWithLink);
    } else {
        titles.splice(depth, 1, newTitle);
        titlesWithLinks.splice(depth, 1, newTitleWithLink);
    }

    var finalTitle = titles.join(' - ');
    var finalTitleWithLinks = titlesWithLinks.join(' - ');

    title.text(baseTitle + (finalTitle.length != 0 ? ' - ' : '') + finalTitle);
    $('#page-title').html((finalTitleWithLinks.length != 0 ? ' - ' : '') + finalTitleWithLinks);
}

app.service('$smartboards', function($http, $q, $ocLazyLoad) {
    var $smartboards = this;

    this.sendFile = function (module, request, fileBuffer, callback) {
        $http.post('info.php?uploadFile&module=' + module + '&request=' + request, fileBuffer, {
            transformRequest: [],
            headers: {'Content-Type': 'application/octet-stream'}
        }).then(function (response) {
            if (callback)
                callback(response.data.data, undefined);
        }, function (response) {
            if (callback)
                callback(undefined, {status: response.status, description: response.data.error});
        });
    };

    this.request = function (module, request, data, callback) {
        $http.post('info.php?module=' + module + '&request=' + request, data).then(function(response) {
            if (callback) {
                if (response.data.data != undefined)
                    callback(response.data.data, undefined);
                else if (response.data != '')
                    callback(undefined, {status: response.status, description: response.data});
                else
                    callback(undefined, undefined);
            }
        }, function(response) {
            if (callback)
                callback(undefined, {status: response.status, description: response.data.error});
        });
    };

    this.loadDependencies = function(dependencies) {
        // load modules in serie..
        if (dependencies.length > 0) {
            var defer = $q.defer();
            var dep = dependencies.shift();
            //console.log('Loading module: ' + dep.name);
            //console.log(dep.files);
            while(dep != undefined && dep.files.length == 0) {
                dep = dependencies.shift();
                if (dependencies.length == 0) {
                    setTimeout(function() {
                        defer.resolve();
                    });
                    return defer.promise;
                }
            }

            $ocLazyLoad.load(dep, {serie: true}).then(function() {
                if (dependencies.length > 0)
                    $smartboards.loadDependencies(dependencies).then(function() {
                        defer.resolve.apply(this, arguments);
                    }, function() {
                        defer.reject.apply(this, arguments);
                    });
                else
                    defer.resolve.apply(this, arguments);
            }, function() {
                defer.reject.apply(this, arguments);
            });

            return defer.promise;
        } else {
            return $q(function(resolve) {resolve();});
        }
    }
});

app.directive('bindTrustedHtml', ['$compile', function ($compile) {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            scope.$watch(function () {
                return scope.$eval(attrs.bindTrustedHtml);
            }, function (value) {
                element.html(value);
                $compile(element.contents())(scope);
            });
        }
    };
}]);
