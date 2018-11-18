<?php
use SmartBoards\Core;

$user = Core::getLoggedUser();
?>
<html lang="en" ng-app="smartBoard">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1">
        <base href="<?php echo Utils::createBase(); ?>" target="_blank">
        <title data-base="SmartBoards">SmartBoards</title>
        <link rel="stylesheet" type="text/css" href="css/jquery.nestable.css" />
        <link rel="stylesheet" type="text/css" href="themes/<?php echo Core::getTheme(); ?>/main.css" />

        <script type="text/javascript" src="js/jquery.min.js"></script>
        <script type="text/javascript" src="js/angular.min.js"></script>
        <script type="text/javascript" src="js/angular-ui-router.min.js"></script>
        <script type="text/javascript" src="js/ocLazyLoad.min.js"></script>
        <script type="text/javascript" src="js/jquery.nestable.js"></script>
        <script type="text/javascript" src="js/builder.js"></script>
        <script type="text/javascript" src="js/app.js"></script>
        <script type="text/javascript" src="js/settings.js"></script>
        <script type="text/javascript" src="js/d3.min.js"></script>
        <script type="text/javascript" src="js/d3-star-plot-0.0.3.min.js"></script>
        <script type="text/javascript" src="js/tooltip.js"></script>

        <script>
            app.run(['$rootScope', function($rootScope) {
                $rootScope.user = {id: '<?= $user->getId(); ?>', name: '<?= $user->getName(); ?>'};
            }]);

            app.controller('SmartBoard', function($location, $rootScope, $scope, $smartboards, $timeout, $urlRouter) {
                $rootScope.toCourse = function(courseName, course, reloadState, gotoLandingPage) {
                    if ($rootScope.course != course) {
                        $rootScope.course = $scope.course = course;
                        $rootScope.courseName = courseName;
                        removeActiveLinks();
                        if ($scope.course != undefined) {
                            changeTitle(courseName, 0, false);
                            $smartboards.request('core', 'getCourseInfo', {course: $scope.course}, function (data) {
                                $rootScope.courseName = data.courseName;
                                changeTitle(data.courseName, 0, true, data.headerLink);
                                $smartboards.loadDependencies(data.resources).then(function () {
                                    if (reloadState)
                                        $urlRouter.sync();

                                    $timeout(function () {
                                        $scope.setNavigation(data.navigation);
                                        if (gotoLandingPage && $scope.landingPage != undefined && $scope.landingPage != '') {
                                            var landing = $scope.landingPage.replace(/^\//g, '');
                                            $location.path('courses/' + courseName + '-' + course + (landing.length == 0 ? '' : '/' + landing));
                                        }
                                    });
                                }, function () {
                                    console.log('Error loading dependencies: ');
                                    console.log(arguments);
                                });
                                $scope.landingPage = data.landingPage;
                                $scope.setNavigation([]);
                            });
                        }
                    } else if (gotoLandingPage && $scope.landingPage != undefined && $scope.landingPage != '') {
                        var landing = $scope.landingPage.replace(/^\//g, '');
                        $location.path('courses/' + courseName + '-' + course + (landing.length == 0 ? '' : '/' + landing));
                    }
                };

                $scope.mainNavigation = [];
                $scope.defaultNavigation = function() {
                    $scope.mainNavigation = [
                        {sref: 'home', image: 'images/leaderboard.svg', text: 'Main Page'},
                        {sref: 'courses', image: 'images/leaderboard.svg', text: 'Courses'},
                        <?php if ($user->isAdmin()) echo "{sref: 'settings', image: 'images/gear.svg', text: 'Settings'}," ?>
                    ]
                };

                $scope.setNavigation = function(newNav) {
                    $scope.mainNavigation = newNav;
                };

                $scope.setCourse = function(course) {
                    $scope.course = course;
                }

                $scope.defaultNavigation();
                $rootScope.modulesDir = '<?php echo MODULES_FOLDER; ?>';
                $scope.course = undefined;

                $scope.$on('$stateChangeSuccess', function(e, toState, toParams, fromState, fromParams) {
                    if (toState.name.startsWith('course.') || toState.name == 'course') {
                        $rootScope.toCourse(toParams.courseName, toParams.course, false, (toState.name == 'course'));
                    } else {
                        $rootScope.course= $scope.course = undefined;
                    }
                });
            });

            app.config(function($stateProvider, $urlRouterProvider){
                $urlRouterProvider.deferIntercept();
                $urlRouterProvider.otherwise('/');
            });


            var firstInitDone = false;
            app.run(function ($rootScope, $urlRouter, $location) {
                $urlRouterGlobal = $urlRouter;
                $locationGlobal = $location;
                $rootScope.$on('$locationChangeSuccess', function(e) {
                    e.preventDefault();
                    if (firstInitDone) {
                        $urlRouter.sync();
                        return;
                    }
                    firstInitDone = true;

                    e.preventDefault();
                    var path = $location.path();
                    var pathNormalized = path.toLowerCase();
                    var pathRegex = new RegExp(/\/courses\/([A-Za-z0-9]+)-([0-9]+)/, 'g');
                    var match = path.match(pathRegex);
                    if (match != null) {
                        var matchGroup = pathRegex.exec(path);
                        $rootScope.toCourse(matchGroup[1], matchGroup[2], true);
                    } else
                        $urlRouter.sync();
                });

                $urlRouter.listen();
            });
        </script>
    </head>
    <body ng-controller="SmartBoard">
        <nav>
            <div class="nav-header">
                <a ui-sref="home">SmartBoards</a>
                <span id="page-title"></span>
            </div>
            <div class="nav-collapse">
                <ul>
                    <li ng-repeat="link in mainNavigation track by $index">
                        <a ng-if="link.sref" ui-sref="{{link.sref}}"><div><img ng-src="{{link.image}}"></div><div><span>{{link.text}}</span><span ng-if="link.subtext">{{link.subtext}}</span></div></a>
                        <a ng-if="link.href" href="{{link.href}}"><div><img ng-src="{{link.image}}"></div><div><span>{{link.text}}</span><span ng-if="link.subtext">{{link.subtext}}</span></div></a>
                    </li>
                </ul>
            </div>
        </nav>

        <div id="wrapper">
            <div id="content-wrapper">
                <div ui-view="main-view"></div>
            </div>
        </div>
    </body>
</html>
