
app.controller('Settings', function($scope, $state, $compile, $smartboards) {
    changeTitle('Settings', 0);
    var refreshTabsBind = $scope.$on('refreshTabs', function() {

        var tabs = $('#settings > .tabs > .tabs-container');
        tabs.html('');
        //side bar nas settings globais
        tabs.append($compile('<li><a ui-sref="settings.global">Global</a></li>')($scope));
        tabs.append($compile('<li><a ui-sref="settings.modules">Installed Modules</a></li>')($scope));
        tabs.append($compile('<li><a ui-sref="settings.about">About</a></li>')($scope));

        addActiveLinks($state.current.name);
        updateTabTitle($state.current.name, $state.params);
    });
    $scope.$emit('refreshTabs');
    $scope.$on('$destroy', refreshTabsBind);
});


app.controller('SettingsGlobal', function($scope, $element, $smartboards, $compile) {
    $smartboards.request('settings', 'global', {}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;

        // Themes
        var themes = createSection(tabContent, 'Themes');
        themes.attr('id', 'themes');
        for(var i = 0; i < $scope.data.themes.length; i++) {
            var theme = $scope.data.themes[i];
            var themeWrapper = $('<div>', {'class': 'theme'});
            themeWrapper.append($('<div>').text(theme.name));
            themeWrapper.append($('<img>', {'class': 'preview', src: theme.preview ? ('themes/' + theme.name + '/preview.png') : 'images/no-preview.png'}));
            (function(theme) {
                if (theme.name == data.theme)
                    themeWrapper.addClass('current').attr('title', 'Current theme');
                else {
                    themeWrapper.click(function() {
                        $smartboards.request('settings', 'global', {setTheme: theme.name}, function(data, err) {
                            window.location = window.location;
                        });
                    }).attr('title', 'Set theme').addClass('pointer');
                }
            })(theme);
            themes.append(themeWrapper);
        }
    });
});


app.controller('SettingsModules', function($scope, $element, $smartboards, $compile) {
    $smartboards.request('settings', 'modules', {}, function(data, err) {
        if (err) {
            $($element).text(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;
        
        var columns = ['c1', {field:'c2', constructor: function(content) {
            return content;
        }}];

        var modulesSection = createSection(tabContent, 'Modules');
        modulesSection.attr('id', 'modules');
        var modules = $scope.data;
        for(var i in modules) {
            var module = modules[i];
            var dependencies = [];
            var canEnable = true;
            for (var d in module.dependencies) {
                var dependency = module.dependencies[d];
                dependencies.push(dependency.id);
            }
            dependencies = dependencies.join(', ');
            if (dependencies == '')
                dependencies = 'None';
            var table = Builder.buildTable([
                { c1:'Name:', c2: module.name},
                { c1:'Version:', c2: module.version},
                { c1:'Path:', c2: module.dir},
                { c1:'Dependencies:', c2: dependencies}
            ], columns);
            modulesSection.append(table);
        }
    });
});

//retirar no futuro
app.controller('SettingsCourses', function($scope, $state, $compile, $smartboards, $element) {

    //talvez precise do if abaixo
    $smartboards.request('core', 'getCoursesList', {}, function(data, err) {
        // make sure courses is an object
        var courses = data.courses;
        if (Array.isArray(courses)) {
            var newCourses = {};
            for (var i in courses){
                courses[i]['active'] = courses[i]['active']==='1' ? true : false;
                newCourses[i] = courses[i];
            }
            courses = newCourses;
        }
        $scope.courses = courses;
        $($element).append($compile($('<ul style="list-style: none"><li ng-repeat="course in courses">{{course.name}}{{course.isActive ? \'\' : \' - Inactive\'}} <button ng-click="toggleCourse(course)">{{course.isActive ? \'Deactivate\' : \'Activate\'}}</button><img src="images/trashcan.svg" ng-click="deleteCourse(course.id)"></li></ul>'))($scope));
        $($element).append($compile($('<button>', {'ng-click': 'newCourse()', text: 'Create new'}))($scope));
    });
});