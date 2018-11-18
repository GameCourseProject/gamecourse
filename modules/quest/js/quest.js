angular.module('module.quest', []);
angular.module('module.quest').controller('Quest', function($scope, $http, $state, $stateParams, $compile, $smartboards) {
    changeTitle('Quest', 1);

    function showQuestLevel(data) {
        var container = $('#quest-container');
        container.append($('<h1>', {text: data.level + ' - ' + data.title}));
        var page = $('<div>');
        page.html(data.page);
        container.append($compile(page)($scope));
    }

    $smartboards.request('quest', 'level', {course: $scope.course, level: $stateParams.level}, function(data, err) {
        if (err) {
            if (err.status == '404' || err.status == '401')
                $('#quest-container').text(err.description);
            else
                window.location = document.baseURI;
            return;
        }

        var container = $('#quest-container');

        if (data.validation) {
            var requestFileDiv = $('<div>', {'class': 'request-solution'});
            requestFileDiv.append($('<p>', {text: 'Hold on adventurer! The keyword is correct, however must provide a proof of your solution.'}));
            var fileInput = $('<input>', {type: 'file'});
            requestFileDiv.append(fileInput);
            var uploadButton = $('<button>', {text: 'Upload Solution'});
            uploadButton.click(function(){
                var f = fileInput.get(0).files[0];
                var r = new FileReader();
                r.onloadend = function(e){
                    var data = e.target.result;
                    $smartboards.sendFile('quest', 'questSolution&course=' + $scope.course + '&level=' + $stateParams.level, data, function(data, err) {
                        if (data.error) {
                            alert(data.error);
                        } else if (data.control) {
                            $('#quest-container').html($('<p>', {text: data.control}));
                        } else {
                            requestFileDiv.remove();
                            showQuestLevel(data);
                        }
                    });
                    //console.log(data);
                }
                if (f.size <= 5000000)
                    r.readAsArrayBuffer(f);
                else
                    console.log('file too big!');
            });

            requestFileDiv.append(uploadButton);
        } else if (data.error) {
            $('#quest-container').append($('<p>', {text: data.error}));
        } else if (data.control) {
            $('#quest-container').append($('<p>', {text: data.control}));
        } else {
            showQuestLevel(data);
        }

        container.append(requestFileDiv);

    });
});

angular.module('module.quest').controller('Quests', function($rootScope, $element, $compile, $scope, $smartboards) {
    var unsavedChanges = 0;
    $compile(createSectionWithTemplate($element, 'Quests', $scope.modulesDir + '/quest/partials/quests.html'))($scope);

    $scope.questsControl = {};
    $scope.deleteQuest = function(quest) {
        if (!confirm('Do you really want to delete this quest?'))
            return;
        $smartboards.request('quest', 'settings', {course: $scope.course, deleteQuest: quest}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }

            $scope.quests.splice($scope.quests.indexOf(quest), 1);
            if ($scope.questsControl.activeQuest.id == quest)
                $scope.questsControl.activeQuest = {id: -1};
            $scope.$emit('refreshTabs');
        });
    };

    $scope.createQuest = function() {
        $smartboards.request('quest', 'settings&createQuest', {course: $scope.course}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }

            $scope.quests.push(data);
            $scope.$emit('refreshTabs');
        });
    };

    $scope.$watch('quests', function(n, o) {
        if (n != o) {
            $scope.questsPossible = [{id: '-1', name:'None'}];
            for (var i in $scope.quests) {
                var qNum = $scope.quests[i];
                $scope.questsPossible.push({id: qNum, name: 'Quest ' + (qNum + 1)});
            }
        }
    }, true);

    $scope.showActiveQuestChangeButton = function() {
        createChangeButtonIfNone('active-quest', $('#active-quest'), function (status) {
            $smartboards.request('quest', 'settings', {course: $scope.course, activeQuest: $scope.questsControl.activeQuest.id}, function(data, err) {
                if (err) {
                    status.text('Error, please try again!');
                    $('#active-quest').prop('disabled', false);
                    return;
                }

                status.text('New active quest set!');
                $('#active-quest').prop('disabled', false);
                unsavedChanges -= 1;
            });
        }, {
            createMode: 'after',
            disableFunc: function () {
                $('#active-quest').prop('disabled', true);
            },
            onCreate: function() {
                unsavedChanges += 1;
            }
        });
    };

    $smartboards.request('quest', 'settings', {course: $scope.course}, function(data, err) {
        if (err) {
            alert(err.description);
            return;
        }

        $scope.quests = data.quests;
        $scope.questsControl.activeQuest = {id: data.activeQuest};
    });

    var watcherDestroy = $rootScope.$on('$stateChangeStart', function($event, toState, toParams) {
        if (unsavedChanges > 0 ) {
            if (confirm("There are unsaved changes. Leave page without saving?")) {
                watcherDestroy();
                return;
            }
            $event.preventDefault();
        } else
            watcherDestroy();
    });

    createSection($element, 'Statistics').append('TODO');
});

angular.module('module.quest').directive('delayedModel', function($parse, $compile) {
    function delayedCall(el, delay, call) {
        el = $(el);
        var delayData = el.data('delay');
        if (delayData)
            clearTimeout(delayData);

        delayData = setTimeout(function() {
            el.removeData('delay');
            call();
        }, delay);
        el.data('delay', delayData);
    }

    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            element.html(scope.$eval(attrs.delayedModel));
            $compile(element.contents())(scope);

            scope.$watch(function () {
                return scope.$eval(attrs.delayedModel);
            }, function (value) {
                if (value == undefined)
                    return;
                var delayData = element.data('delay');
                if (delayData)
                    clearTimeout(delayData);

                delayData = setTimeout(function() {
                    element.html(value.replace(/\$\$resource_dir\$\$/g, scope.modulesDir + '/quest/resources/' + scope.quest + '/'));
                    $compile(element.contents())(scope);

                    element.removeData('delay');
                }, 1000);
                element.data('delay', delayData);
            });
        }
    };
});

angular.module('module.quest').directive('nestableItem', function () {
    return {
        link: function (scope, element, attrs) {
            element.closest('.dd').nestable('call')(function (list) {
                list.initializeItem(element);
            });
        }
    };
});

angular.module('module.quest').controller('QuestInfo', function($rootScope, $scope, $smartboards, $stateParams, $compile) {
    var unsavedChangesQuestControl = 0;
    var unsavedChangesQuestEdit = false;
    $scope.quest = $stateParams.quest - 1;

    $scope.editLevel = function (level) {
        $('#quest-level-editor').show();
        $('#quest-level-preview').show();
        $scope.editorLevel = level;
    };

    $scope.hideEditor = function () {
        $('#quest-level-editor').hide();
        $('#quest-level-preview').hide();
    };

    $scope.newLevel = function() {
        $scope.levels.push({keyword: 'empty', title: 'Quest Level', page: '', requiresValidation: false});
    };

    $scope.saveQuest = function() {
        var levelsRaw = $('#quest-level-list').nestable('serialize');
        var levels = [];
        for (var i = 0; i < levelsRaw.length; ++i) {
            var l = levelsRaw[i].level;
            var newLevel = {
                keyword: l.keyword,
                title: l.title,
                page: l.page,
                requiresValidation: l.requiresValidation
            };
            if (l.requiresValidation && l.validation)
                newLevel.validation = l.validation;
            levels.push(newLevel);
        }

        $smartboards.request('quest', 'saveLevels', {course: $scope.course, quest: $scope.quest, levels: levels}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            console.log('ok!');
            unsavedChangesQuestEdit = false;
        });
    };

    $scope.showCurrentLevelChangeButton = function() {
        var input = $('#unlocked-level');
        createChangeButtonIfNone('unlocked-level', input, function (status) {
            var levelNum = $scope.levels.indexOf($scope.control.currentLevel);
            $smartboards.request('quest', 'setLevel', {course: $scope.course, quest: $scope.quest, level: levelNum}, function(data, err) {
                if (err) {
                    status.text('Error, please try again!');
                    input.prop('disabled', false);
                    return;
                }

                status.text('New unlocked level set!');
                input.prop('disabled', false);
                unsavedChangesQuestControl -= 1;
            });
        }, {
            createMode: 'after',
            disableFunc: function () {
                input.prop('disabled', true);
            },
            onCreate: function() {
                unsavedChangesQuestControl += 1;
            }
        });
    };

    $scope.showRateLimitChangeButton = function() {
        var input = $('#rate-limit');
        createChangeButtonIfNone('rate-limit', input, function (status) {
            $smartboards.request('quest', 'setRateLimit', {course: $scope.course, quest: $scope.quest, rateLimit: $scope.control.rateLimit}, function(data, err) {
                if (err) {
                    status.text('Error, please try again!');
                    input.prop('disabled', false);
                    return;
                }

                status.text('New rate limit set!');
                input.prop('disabled', false);
                unsavedChangesQuestControl -= 1;
            });
        }, {
            createMode: 'after',
            disableFunc: function () {
                input.prop('disabled', true);
            },
            onCreate: function() {
                unsavedChangesQuestControl += 1;
            }
        });
    };

    $scope.showTimeoutChangeButton = function() {
        var input = $('#timeout');
        createChangeButtonIfNone('timeout', input, function (status) {
            $smartboards.request('quest', 'setTimeout', {course: $scope.course, quest: $scope.quest, timeout: $scope.control.timeout}, function(data, err) {
                if (err) {
                    status.text('Error, please try again!');
                    input.prop('disabled', false);
                    return;
                }

                status.text('New timeout set!');
                input.prop('disabled', false);
                unsavedChangesQuestControl -= 1;
            });
        }, {
            createMode: 'after',
            disableFunc: function () {
                input.prop('disabled', true);
            },
            onCreate: function() {
                unsavedChangesQuestControl += 1;
            }
        });
    };

    $scope.showStartTimeChangeButton = function() {
        var input = $('#startTime');
        var timeValue = $('#startTime').datetimepicker('getValue').getTime()/1000;
        createChangeButtonIfNone('startTime', input, function (status) {
            $smartboards.request('quest', 'setStartTime', {course: $scope.course, quest: $scope.quest, startTime: timeValue}, function(data, err) {
                if (err) {
                    status.text('Error, please try again!');
                    input.prop('disabled', false);
                    return;
                }

                status.text('New start time set!');
                input.prop('disabled', false);
                unsavedChangesQuestControl -= 1;
            });
        }, {
            createMode: 'after',
            disableFunc: function () {
                input.prop('disabled', true);
            },
            onCreate: function() {
                unsavedChangesQuestControl += 1;
            }
        });
    };

    $scope.showEndTimeChangeButton = function() {
        var input = $('#endTime');
        var timeValue = $('#endTime').datetimepicker('getValue').getTime()/1000;
        createChangeButtonIfNone('endTime', input, function (status) {
            $smartboards.request('quest', 'setEndTime', {course: $scope.course, quest: $scope.quest, endTime: timeValue}, function(data, err) {
                if (err) {
                    status.text('Error, please try again!');
                    input.prop('disabled', false);
                    return;
                }

                status.text('New end time set!');
                input.prop('disabled', false);
                unsavedChangesQuestControl -= 1;
            });
        }, {
            createMode: 'after',
            disableFunc: function () {
                input.prop('disabled', true);
            },
            onCreate: function() {
                unsavedChangesQuestControl += 1;
            }
        });
    };

    $scope.resetStats = function() {
        $smartboards.request('quest', 'resetStats', {course: $scope.course, quest: $scope.quest}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            console.log('ok!');
        })
    };

    $scope.initValidation = function() {
        if (!$scope.editorLevel.validation)
            $scope.editorLevel.validation = {type: 'png'};
    };

    $scope.initLevelList = function() {
        $('#quest-level-list').nestable({
            group: 5,
            maxDepth: 1
        }).on('change', function() {
            var list = $(this);
            var questNumbers = list.find('.quest-number');
            for (var i = 0; i < questNumbers.length; ++i) {
                $(questNumbers[i]).text(i + 1);
            }
            unsavedChangesQuestEdit = true;
        }).on('removeitem', function(event, data) {
            if (data.$scope.level == $scope.editorLevel)
                $scope.hideEditor();

            $scope.levels.splice($scope.levels.indexOf(data.$scope.level), 1);
        });
    };

    $scope.initResourceList = function () {
        $('#quest-resource-list').nestable({
            group: 6,
            maxDepth: 1
        }).on('removeitem', function(event, data, cancel) {
            cancel();
            if (!confirm('Do you really want to delete this resource?'))
                return;
            var file = data.$scope.file;
            $smartboards.request('quest', 'deleteResource', {course: $scope.course, quest: $scope.quest, resource: file}, function(data, err) {
                if (err) {
                    console.log(err);
                    alert('Unexpected error! Please consult the developer console to know what happened.');
                    return;
                }

                var idx = $scope.resources.indexOf(file);
                if (idx != -1)
                    $scope.resources.splice(idx, 1);
            });
        });
    };

    $scope.uploadQuestResource = function() {
        var files = $('#quest-resource-file').get(0).files;
        if (files.length == 1) {
            var f = files[0];
            var r = new FileReader();
            r.onloadend = function(e){
                var data = e.target.result;
                $smartboards.sendFile('quest', 'uploadResource&course=' + $scope.course + '&quest=' + $scope.quest + '&resource=' + encodeURI(f.name), data, function(data, err) {
                    if (err) {
                        console.log(err);
                        alert('Unexpected error! Please consult the developer console to know what happened.');
                        return;
                    }

                    if ($scope.resources.indexOf(f.name) == -1)
                        $scope.resources.push(f.name);
                });
            }

            if (f.size <= 5000000)
                r.readAsArrayBuffer(f);
            else
                alert('file too big!');
        }
    };

    $smartboards.request('quest', 'questInfo', {course: $scope.course, quest: $scope.quest}, function(data, err) {
        if (err) {
            console.log(err);
            $('#quest-info-container').text(err);
            return;
        }

        function convertTime(t) {
            return new DateFormatter().formatDate(new Date(t * 1000), 'Y/m/d H:i');
        }

        $scope.resources = data.resources;
        $scope.levels = data.levels;
        $scope.control = {
            currentLevel: $scope.levels[data.currentLevel],
            timeout: data.timeout,
            rateLimit: data.rateLimit,
            startTime: convertTime(data.startTime),
            endTime: convertTime(data.endTime)
        };
        $scope.resourceDir = data.resourceDir;

        var container = $('#quest-info-container');

        $compile(createSectionWithTemplate(container, 'Quest Control', $scope.modulesDir + '/quest/partials/quest-control.html').addClass('quest-control'))($scope);
        $compile(createSectionWithTemplate(container, 'Quest Edit', $scope.modulesDir + '/quest/partials/quest-editor.html'))($scope);

        $scope.$watch('levels', function(newV, oldV) {
            if (oldV != newV)
                unsavedChangesQuestEdit = true;
        }, true);

        if ($scope.levels.length > 0)
            $scope.editLevel($scope.levels[0]);
        else
            $scope.hideEditor();

        setTimeout(function() {
            container.find('#endTime').datetimepicker({
                format:'Y/m/d H:i'
            });
            container.find('#startTime').datetimepicker({
                format:'Y/m/d H:i'
            });
        }, 100);
    });

    var watcherDestroy = $rootScope.$on('$stateChangeStart', function($event, toState, toParams) {
        if (unsavedChangesQuestEdit || unsavedChangesQuestControl > 0) {
            if (confirm("There are unsaved changes. Quit without saving?")) {
                watcherDestroy();
                return;
            }
            $event.preventDefault();
        } else
            watcherDestroy();
    });
});

angular.module('module.quest').run(['$rootScope', '$state', function($rootScope, $state) {
    $rootScope.$on('$stateChangeSuccess', function(e, toState, toParams, fromState, fromParams) {
        if (toState.name.indexOf('course.quest') == 0)
            $(document.body).addClass('quest-body');
        else if (fromState.name.indexOf('course.quest') == 0)
            $(document.body).removeClass('quest-body');
    });
}]);

angular.module('module.quest').config(function($stateProvider) {
    $stateProvider.state('course.quest', {
        url: '/quest',
        views: {
            'main-view@': {
                controller: ['$state', '$rootScope', function ($state, $rootScope) {
                    $state.go('course.questLevel', {level: 'beginning'}, {location: 'replace'});
                }]
            }
        }
    }).state('course.questLevel', {
        url: '/quest/{level:.+}',
        views: {
            'main-view@': {
                template: '<div id="quest-container"></div>',
                controller: 'Quest'
            }
        }
    }).state('course.settings.quest', {
        url: '/quests',
        views: {
            'tabContent@course.settings': {
                template: '<div id="quest-settings-container"></div>',
                controller: 'Quests'
            }
        }
    }).state('course.settings.quest.info', {
        url: '/{quest:[0-9]+}',
        views: {
            'tabContent@course.settings': {
                template: '<div id="quest-info-container"></div>',
                controller: 'QuestInfo'
            }
        }
    }) ;
});
