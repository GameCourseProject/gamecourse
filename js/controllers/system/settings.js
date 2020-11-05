
app.controller('Settings', function($scope, $state, $compile, $smartboards) {
    var refreshTabsBind = $scope.$on('refreshTabs', function() {

        var tabs = $('#settings > .tabs > .tabs-container');
        tabs.html('');
        //side bar nas settings globais
        tabs.append($compile('<li><a ui-sref="settings.global">Global</a></li>')($scope));
        tabs.append($compile('<li><a ui-sref="settings.modules">Installed Modules</a></li>')($scope));
        tabs.append($compile('<li><a ui-sref="settings.about">About</a></li>')($scope));

        addActiveLinks($state.current.name);
        //updateTabTitle($state.current.name, $state.params);
    });
    $scope.$emit('refreshTabs');
    $scope.$on('$destroy', refreshTabsBind);
});


app.controller('SettingsGlobal', function($scope, $element, $smartboards, $compile) {
    $smartboards.request('settings', 'global', {}, function(data, err) {
        if (err) {
            giveMessage(err.description);
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

        //Autentication
        var autentication = createSection(tabContent, 'Autentication');
        //LMS
        var sistem = createSection(tabContent, 'Learning Management System');
    });
});


app.controller('SettingsModules', function($scope, $element, $smartboards, $compile) {
    
    //falta o add, imagem e descricao de cada modulo

    $scope.reduceList = function(){
        $("#empty_search").empty();
        $scope.modules = $scope.allModules;
        filteredModules = [];
        text = $scope.search;
        if (validateSearch(text)){
            //match por name e short
            jQuery.each($scope.modules , function( index ){
                module_obj = $scope.modules[index];
                if (module_obj.name.toLowerCase().includes(text.toLowerCase())
                || module_obj.description.toLowerCase().includes(text.toLowerCase())){
                    filteredModules.push(module_obj);
                }
            });
            if(filteredModules.length == 0){
                $("#courses-table").hide();
                $("#empty_search").append("No matches found");
            }
            $scope.modules = filteredModules;
        }
        
    }
    $scope.importModule = function (){
        $scope.importedModule = null;
        var fileInput = document.getElementById('import_module');
        var file = fileInput.files[0];

        //todo

        // var reader = new FileReader();
        // reader.onload = function(e) {
        //     $scope.importedModule = reader.result;
        //     $smartboards.request('course', 'importUser', { file: $scope.importedModule }, function(data, err) {
        //         if (err) {
        //             console.log(err.description);
        //             return;
        //         }
        //         nUsers = data.nUsers;
        //         $("#import-user").hide();
        //         $("#action_completed").empty();
        //         $("#action_completed").append(nUsers + " Users Imported");
        //         $("#action_completed").show().delay(3000).fadeOut();
        //     });
        // }
        // reader.readAsText(file);	
    }
    $scope.exportModules = function(){
        //todo
        // $smartboards.request('course', 'exportUsers', { course: $scope.course }, function(data, err) {
        //     if (err) {
        //         console.log(err.description);
        //         return;
        //     }
        //     download("courseUsers.csv", data.courseUsers);
        // });
        
    }


    var tabContent = $($element);
    

    search = $("<div class='search'> <input type='text' id='seach_input' placeholder='Search..' name='search' ng-change='reduceList()' ng-model='search' ><button class='magnifying-glass' id='search-btn' ng-click='reduceList()'></button>  </div>")
    //action buttons
    action_buttons = $("<div class='action-buttons' id='install_modules'></div>");
    action_buttons.append( $("<div class='icon import_icon' value='#import-module' onclick='openModal(this)'></div>"));
    action_buttons.append( $("<div class='icon export_icon' ng-click='exportModules()'></div>"));
    $compile(action_buttons)($scope);

    modules = $('<div id="modules"></div>');
    module_card = $('<div class="module_card" ng-repeat="(i, module) in modules"></div>')
    module_card.append($('<div class="icon" style="background-image: url(/gamecourse/modules/{{module.id}}/icon.svg)"></div>'));
    module_card.append($('<div class="header">{{module.name}}</div>'));
    module_card.append($('<div class="text no-status">{{module.description}}</div>'));
    modules.append(module_card);
    //error section
    modules.append( $("<div class='error_box'><div id='empty_search' class='error_msg'></div></div>"));
    
    //the import modal
    importModal = $("<div class='modal' id='import-module'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#import-module" onclick="closeModal(this)"></button>'));
    verification.append( $('<div class="warning">Please select a .zip file to be imported</div>'));
    verification.append( $('<div class="target">Be sure you followed the <a target="_blank" href="./docs/modules" >module gidelines</a></div>'));
    verification.append( $('<input class="config_input" type="file" id="import_module" accept=".zip">')); //input file
    verification.append( $('<div class="confirmation_btns"><button ng-click="importModule()">Install New Module</button></div>'))
    importModal.append(verification);
    tabContent.append(importModal);
    
    $compile(modules)($scope);
    $compile(search)($scope);
    tabContent.append(search);
    tabContent.append(action_buttons);
    tabContent.append(modules);
    

    $smartboards.request('settings', 'modules', {}, function(data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        console.log(data);
        $scope.modules = data;
        $scope.allModules = data.slice();
    });
});
