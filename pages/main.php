<?php

use GameCourse\Core;

$user = Core::getLoggedUser();
?>
<html lang="en" ng-app="smartBoard">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?php echo Utils::createBase(); ?>" target="_blank">
    <title data-base="GameCourse">GameCourse</title>
    <link rel="stylesheet" type="text/css" href="css/jquery.nestable.css" />
    <link rel="stylesheet" type="text/css" href="themes/<?php echo $GLOBALS['theme'] ?>/main.css" />
    <link rel="stylesheet" type="text/css" href="css/navbar.css" />
    <link rel="stylesheet" type="text/css" href="css/geral.css" />
    <link rel="stylesheet" type="text/css" href="css/search_filter_sidebar.css" />
    <link rel="stylesheet" type="text/css" href="css/modals.css" />
    <link rel="stylesheet" type="text/css" href="css/settings.css" />
    <link rel="stylesheet" type="text/css" href="css/myInfo.css" />
    <link rel="stylesheet" type="text/css" href="css/mainpage.css" />
    <link rel="stylesheet" type="text/css" href="css/inside_course_exceptions.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui.min.css" />
    <link rel="stylesheet" type="text/css" href="css/codemirror.css">
    <link rel="stylesheet" type="text/css" href="css/mdn-like.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css">

    <script type="text/javascript" src="js/html2canvas.js"></script>
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/angular.min.js"></script>
    <script type="text/javascript" src="js/angular-ui-router.min.js"></script>
    <script type="text/javascript" src="js/ocLazyLoad.min.js"></script>
    <script type="text/javascript" src="js/jquery.nestable.js"></script>
    <script type="text/javascript" src="js/builder.js"></script>
    <script type="text/javascript" src="js/app.js"></script>
    <script type="text/javascript" src="js/states/settings.js"></script>
    <script type="text/javascript" src="js/states/other_pages.js"></script>
    <script type="text/javascript" src="js/aux_functions.js"></script>
    <script type="text/javascript" src="js/modals.js"></script>
    <script type="text/javascript" src="js/search_filter_order_sidebar.js"></script>
    <script type="text/javascript" src="js/controllers/inside_course/settings.js"></script>
    <script type="text/javascript" src="js/controllers/inside_course/other_pages.js"></script>
    <script type="text/javascript" src="js/controllers/inside_course/configurations.js"></script>
    <script type="text/javascript" src="js/controllers/system/settings.js"></script>
    <script type="text/javascript" src="js/controllers/system/other_pages.js"></script>
    <script type="text/javascript" src="js/controllers/system/home_page.js"></script>
    <script type="text/javascript" src="js/controllers/system/courses_page.js"></script>
    <script type="text/javascript" src="js/controllers/system/users_page.js"></script>
    <script type="text/javascript" src="js/d3.min.js"></script>
    <script type="text/javascript" src="js/d3-star-plot-0.0.3.min.js"></script>
    <script type="text/javascript" src="js/tooltip.js"></script>
    <script type="text/javascript" src="js/state_manager_undo_redo.js"></script>
    <script type="text/javascript" src="js/codemirror.js"></script>
    <script type="text/javascript" src="js/css.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.10.25/api/row().show().js"></script>
    <script type="text/javascript" src="js/rules.js"></script>
    <script type="text/javascript" src="js/jquery-ui.min.js"></script>


    <!-- Color picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/monolith.min.css" /> <!-- 'monolith' theme -->
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.es5.min.js"></script>

    <!-- Main Quill library -->
    <script src="//cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="//cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <script src="js/image-resize.min.js"></script>
    <script src="js/quill.htmlEditButton.min.js"></script>
    <!-- Quill stylesheet -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <!-- Merge images library-->
    <script src="https://unpkg.com/merge-images"></script>

    <script>
        app.run(['$rootScope', function($rootScope) {
            $rootScope.user = {
                id: '<?= $user->getId(); ?>',
                name: "<?= $user->getName(); ?>",
                username: '<?= $user->getUsername(); ?>'
            };
        }]);

        app.controller('SmartBoard', function($location, $rootScope, $scope, $smartboards, $timeout, $urlRouter) {
            $rootScope.loaded = true;

            //em caso de entrarmos num curso
            $rootScope.toCourse = function(courseName, course, reloadState, gotoLandingPage) {
                if ($rootScope.course != course) {
                    $rootScope.course = $scope.course = course;
                    $rootScope.courseName = courseName;

                    if ($scope.course != undefined) {
                        changeTitle(courseName, 0, false);
                        //na funcao da API devolve o que vai aparecer na navbar
                        $smartboards.request('core', 'getCourseInfo', {
                            course: $scope.course
                        }, function(data, err) {
                            if (err) {
                                giveMessage(err.description);
                                return;
                            }
                            $rootScope.courseName = data.courseName;

                            $("#course_name").text(data.courseName);
                            changeElColor("#course_name", data.courseColor);
                            $scope.courseColor = data.courseColor;

                            var path = 'courses/' + courseName + '-' + course;
                            changeTitle(data.courseName, 0, true, path);
                            $smartboards.loadDependencies(data.resources).then(function() {
                                if (reloadState)
                                    $urlRouter.sync();

                                $timeout(function() {
                                    $scope.setNavigation(data.navigation, data.settings);
                                    beginNavbarResize();
                                    if (gotoLandingPage && $scope.landingPage != undefined && $scope.landingPage != '') {
                                        var landing = $scope.landingPage.replace(/^\//g, '');
                                        if (data.landingPageType == "ROLE_SINGLE")
                                            $location.path('courses/' + courseName + '-' + course + (landing.length == 0 ? '' : '/' + landing + '-' + $scope.landingPageID));
                                        else
                                            $location.path('courses/' + courseName + '-' + course + (landing.length == 0 ? '' : '/' + landing + '-' + $scope.landingPageID + '/' + '<?= $user->getId(); ?>'));
                                    }
                                });
                            }, function() {
                                console.log('Error loading dependencies: ');
                                console.log(arguments);
                            });
                            $scope.landingPage = data.landingPage;
                            $scope.landingPageID = data.landingPageID;
                            $scope.setNavigation([], []);
                            $scope.ruleSystemLastRun = data.ruleSystemLastRun;
                        });
                    }
                } else if (gotoLandingPage && $scope.landingPage != undefined && $scope.landingPage != '') {
                    var landing = $scope.landingPage.replace(/^\//g, '');
                    if (data.landingPageType == "ROLE_SINGLE")
                        $location.path('courses/' + courseName + '-' + course + (landing.length == 0 ? '' : '/' + landing + '-' + $scope.landingPageID));
                    else
                        $location.path('courses/' + courseName + '-' + course + (landing.length == 0 ? '' : '/' + landing + '-' + $scope.landingPageID + '/' + '<?= $user->getId(); ?>'));

                }
            };
            $scope.user = $rootScope.user;
            //conteúdo da nav bar
            $scope.mainNavigation = [];
            $scope.settingsNavigation = [];
            $scope.defaultNavigation = function() {
                //sref is the state name
                $scope.mainNavigation = [{
                        sref: 'home',
                        image: 'images/leaderboard.svg',
                        text: 'Main Page',
                        class: ''
                    },
                    {
                        sref: 'courses',
                        image: 'images/leaderboard.svg',
                        text: 'Courses',
                        class: ''
                    },
                    <?php if ($user->isAdmin()) echo "
                        {sref: 'users',
                        image: 'images/leaderboard.svg',
                        text: 'Users',
                        class: '' }
                    " ?>,
                    <?php if ($user->isAdmin()) echo "{sref: 'settings', image: 'images/gear.svg', text: 'Settings', class:'dropdown', children:'true'}," ?>
                ];

                $scope.settingsNavigation = [
                    <?php if ($user->isAdmin()) echo "
                        {sref: 'settings.about', text: 'About'},
                        {sref: 'settings.global', text: 'Global'},
                        {sref: 'settings.modules', text: 'Modules'}
                        " ?>
                ];
            };

            $scope.setNavigation = function(newNav, newSet) {
                $scope.mainNavigation = newNav;
                $scope.settingsNavigation = newSet;

                //remove users and settings
                //to be used in the course settings to change the order of the pages in the navbar
                $rootScope.navigation = newNav.slice(0, -2);
            };

            $scope.setCourse = function(course) {
                $scope.course = course;
            };

            $scope.defaultNavigation();
            $rootScope.modulesDir = '<?php echo MODULES_FOLDER; ?>';
            $scope.course = undefined;

            $scope.$on('$stateChangeSuccess', function(e, toState, toParams, fromState, fromParams) {
                //vai para um curso
                if (toState.name.startsWith('course.') || toState.name == 'course') {
                    $rootScope.toCourse(toParams.courseName, toParams.course, false, (toState.name == 'course'));

                    //vai para o sistema
                } else {
                    $rootScope.course = $scope.course = undefined;
                }
            });
        });

        app.config(function($stateProvider, $urlRouterProvider) {
            $urlRouterProvider.deferIntercept();
            $urlRouterProvider.otherwise('/');
        });


        var firstInitDone = false;
        app.run(function($rootScope, $urlRouter, $location) {
            //only goes through here on reloads
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

    <!-- Sankey Chart -->
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/sankey.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
</head>

<body ng-controller="SmartBoard" class="body-footer-fix">
    <div class="navbar">
        <a class="logo" ui-sref="home"></a>
        <div class="user_info">
            <div ng-if="user" class="user_id">{{user.username}}</div>
            <a class="icon" ng-if="!course" id="user_icon" style="background-image: url(/gamecourse/photos/{{user.username}}.png);" ui-sref="myInfo"></a>
            <a class="icon" ng-if="course" id="user_icon" style="background-image: url(/gamecourse/photos/{{user.username}}.png);" ui-sref="course.myInfo"></a>
            <a class="icon" id="user_exit" href="?logout" target="_parent"></a>
        </div>
        <ul class="menu">
            <li ng-repeat="link in mainNavigation track by $index" class="{{link.class}}">
                <a ng-if="link.sref" ui-sref="{{link.sref}}">{{link.text}}</a>
                <a ng-if="link.href" href="{{link.href}}">{{link.text}}</a>
                <div ng-if="link.children" class="dropdown-content">
                    <a ng-repeat="sublink in settingsNavigation track by $index" ui-sref="{{sublink.sref}}">{{sublink.text}}</a>
                </div>
            </li>
        </ul>

    </div>
    <div ng-if="course" id="course_name"></div>
    <!-- <nav>
            <div class="nav-header">
                <a ui-sref="home">GameCourse</a> 
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
        </nav> -->

    <div ng-if="!course" id="wrapper">
        <!-- conteudo da página -->
        <div id="content-wrapper">
            <div ui-view="main-view"></div>
        </div>
        <div ng-hide="loaded" id="page-loading">
            <img src="images/loader.gif">
        </div>
    </div>
    <div ng-if="course" id="wrapper" class="smaller_window">
        <!-- conteudo da página -->
        <div id="content-wrapper">
            <div ui-view="main-view"></div>
        </div>
        <div ng-hide="loaded" id="page-loading">
            <img src="images/loader.gif">
        </div>
    </div>
    <div ng-if="course" class="navfooter">
        <div class="lastrun">Last Run: {{ruleSystemLastRun}}</div>
    </div>
</body>

</html>