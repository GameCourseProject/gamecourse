//pagina das rules
app.controller('CourseSettingsRules', function($rootScope, $scope, $stateParams, $element, $smartboards, $compile, $parse) {
    $rootScope.loaded=true;
    var tabContent = $($element);
    tabContent.css("overflow-y", "auto");
    $(".tab-content-wrapper").scrollTop(0);
    

    /////////////////////////////////////////////////////////////////////////////////////////////////////////

    $smartboards.request('settings', 'getRulesFromCourse', {course: $scope.course}, function(data, err) {
        
        if (err) {
            giveMessage(err.description);
            return;
        }

        $('head').append('<link rel="stylesheet" type="text/css" href="css/rule_editor.css" />');
        $('head').append('<script type="text/javascript" src="js/rules.js"></script>');
        $('head').append('<script src="js/codemirror.js"></script>');
        $('head').append('<link rel="stylesheet" href="css/codemirror.css">');
        $('head').append('<link rel="stylesheet" href="css/mdn-like.css">');
        $('head').append('<link rel="stylesheet" href="css/base16-light.css">');
        $('head').append('<link rel="stylesheet" href="css/show-hint.css">');
        $('head').append('<script type="text/javascript" src="js/python.js"></script>');
        $('head').append('<script type="text/javascript" src="js/show-hint.js"></script>');
        
        var first_run = true;
        $scope.gamerules_funcs = data.funcs;
        console.log($scope.gamerules_funcs);


        // -------------- GENERAL FUNCS --------------

        Array.prototype.swap = function (x,y, mod_id=null, filename=false) {
            // general swapping function / rule swapping function
            var b = this[x];
            this[x] = this[y];
            this[y] = b;
            if (mod_id == "skills") {
                temp_rank = this[x].rulerank;
                this[x].rulerank = this[y].rulerank;
                this[y].rulerank = temp_rank;
            }

            if (filename) {
                // when swapping sections, change filenames
                target_split = this[x].filename.split(" -");
                coll_split = this[y].filename.split(" -");
                t = target_split[0];
                target_split[0] = coll_split[0];
                coll_split[0] = t;

                this[x].filename = target_split.join(" -");
                this[y].filename = coll_split.join(" -");

                this[x].rules.forEach(el => el.rulefile = this[x].filename);
                this[y].rules.forEach(el => el.rulefile = this[y].filename);
            }
            return this;
        }


        // -------------- TEMP -------------- TO DO

        $scope.getRsState = function() {

            $smartboards.request('settings', 'getRuleSystemState', {course: $scope.course, getRuleSystemState: true}, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $scope.rs_state = data.rsState;
            });
        }

        $scope.refreshState = function() {

            $smartboards.request('settings', 'getRuleSystemState', {course: $scope.course, refreshState: true}, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $scope.rs_state = data.rsState;
            });
        }


        // -------------- EXTRA ACTIONS --------------

        $scope.closeModal = function () {
            $(".modal").hide();
        }

        $scope.closeSettingsModal = function () {
            $("#rule-settings-modal").hide();
            $scope.resetMetadata();
        }

        $scope.resetMetadata = function () {
            $scope.metadata_edit = $scope.metadata;
        } 


        // -------------- GENERAL ACTIONS --------------

        $scope.searchInput = function () {
            var value = $("#search-input").val();
            $scope.rules = angular.copy($scope.rules_save);

            $scope.rules.forEach(function (element) {
                element.rules = element.rules.filter(el => el["name"].includes(value) || el["name"].toLowerCase().includes(value));
            });
            // TO DO check if pos is correct after filtering
        }

        $scope.filterByTag = function () {
            var value = $("#search-input-tag").val().toLowerCase();
            $scope.rules = angular.copy($scope.rules_save);

            $scope.rules.forEach(function (element) {
                element.rules = element.rules.filter(el => el["tags"].includes(value));
            });
            // TO DO check if pos is correct after filtering
        }


        $scope.newSection = function (item) {

            $scope.openNewSectionModal = function() {
                //new section verification modal
                newsectionmodal = $("<div class='modal' id='new-section-modal' style='display:block;'></div>");
                verification = $("<div class='modal_content'></div>");
                verification.append($('<div class="title" id="open_tier_action">New Section:</div>'));
                content = $('<div class="content"><div id="new_box_tier" class="inputs"><div class="row_inputs"><div class="details full config_item">');
                content.append($('<div class="half"><div class="container"><input type="text" id="new-section-val" class="form__input ng-pristine ng-valid ng-empty ng-touched" placeholder="Section Name"><div id="new-section-error"></div>'));
                content.append($('</div></div><button class="cancel" ng-click="closeModal()"> Cancel </button><button class="save_btn" ng-click="submitItem()">Save</button></div>'));
                verification.append(content);
                newsectionmodal.append(verification);
                tabContent.append($compile(newsectionmodal)($scope));
            }

            $scope.submitItem = function() {
                section = {};
                $input = $("#new-section-val").val();
                $name = $input.trim();
                prec = $scope.rules.length + 1;

                section.id = $name.toLowerCase();
                section.filename = prec.toString() + " - " + section.id + ".txt" ;
                section.name = $name;
                section.rules = [];
                
                found_module = false; 
                for (var i = 0; i < $scope.rules.length; i++) {
                    if ($scope.rules[i].id == section.id) {
                        found_module = true;
                        break;
                    }
                }
                
                if ($name != "" && !found_module) { // TO DO : input sanitization
                    $smartboards.request('settings', 'ruleGeneralActions', {course: $scope.course, newSection: true, sectionName: section.id, sectionPrecedence : prec}, alertUpdateNoReload);    
                    $scope.rules.push(section);
                    $("#new-section-modal").hide();
                }
                else if ($name.trim() == "") { // if not empty
                    $("#new-section-val").val("");
                    $("#new-section-error").text("Name must not be empty");
                }
                else if (found_module) { // if module doesn't exist
                    $("#new-section-val").val("");
                    $("#new-section-error").text("Section already exists");
                }
            }
            $scope.openNewSectionModal();
        }

        // -------------- GENERAL ACTIONS : TAGS MENU --------------
        
        $scope.editTags = function () {

            $scope.deleteTag = function ($event, $index) {
                $scope.tags.splice($index, 1);
            }

            $scope.openColorPicker = function ($event, $index, tag) {
                $($event.target).children(".color-picker-tags-menu").show();

                var default_color = tag.color;
                const pickr = new Pickr({
                    el: $event.target,
                    useAsButton: true,
                    default: default_color,
                    theme: 'monolith',
                    components: {
                        hue: true,
                        interaction: { input: true, save: true }
                    }
                }).on('init', pickr => {
                    pickr.show();
                    color = pickr.getSelectedColor().toHEXA().toString(0);
                    $($event.target).css("background-color", color);
                }).on('save', color => {
                    color = color.toHEXA().toString(0);
                    $($event.target).css("background-color", color);
                    const mod_tag = $scope.tags.filter(el => el.name === tag.name)[0];
                    mod_tag.color = color;
                    $scope.$apply($scope);
                    pickr.hide();
                }).on('change', color => {
                    color = color.toHEXA().toString(0);
                    $($event.target).css("background-color", color);
                })
            }

            $scope.editTag = function ($event, $index, tag) {
                tag.editing = true;
            }

            $scope.saveTag = function ($event, $index, tag) {
                tag.editing = false;
                var row = $($event.target).parent().parent();
                var name = row.children().eq(0).find("input").val();
                $scope.tags[$index].name = name;
                $scope.tags[$index].editing = false;

                $scope.rules.forEach(function(module) {
                    module.rules.forEach(function(rule) {
                        rule.tags.forEach(function(tag) {
                            if (tag.name == name) {
                                tag.color = $scope.tags[$index].color;
                            }
                        });
                    });
                });
            }
            
            $scope.saveTags = function () {
                $smartboards.request('settings', 'ruleGeneralActions', {course: $scope.course, submitTagsEdit: true, tags: $scope.tags}, function(data, err) {
                });
                $scope.listRules(); 
            }

            $scope.closeTagsModal = function () {
                $scope.tags = angular.copy($scope.tags_save);
                $scope.closeModal();
            }


            $scope.openTagsModal = function() {
                //new tags modal
                edit_tags_modal = $("<div class='modal' id='tags-modal' style='display:block;'></div>");
                verification = $("<div class='modal_content'></div>");
                verification.append($('<button class="close_btn icon" value="#tags-modal" ng-click="closeTagsModal()"></button>'));
                verification.append($('<div class="title" id="open_tier_action">Edit Tags: </div>'));
                content = $('<div class="content data-table tags-table"></div>');

                var table = $('<table>');
                rowHeader = $("<tr class='tableheader'></tr>");
                rowHeader.append("<th>Tag</th><th>Color</th><th>Actions</th>");
                table.append(rowHeader);

                row_content = $("<tr ng-repeat='tag in tags'>");
                row_content.append("<td class='ng-binding'><span ng-if='!tag.editing'>{{tag.name}}</span><input ng-if='tag.editing' type='text' id='var-name' class='form__input ng-pristine ng-valid ng-empty ng-touched' placeholder='Tag Name' value={{tag.name}}></td>");
                row_content.append("<td class='ng-binding'><div style='display:flex;justify-content:center;'><div ng-if='!tag.editing' class='color-sample color-picker-tags-menu pickr' style='margin-right:8px'><div class='box' style='border:none;background-color:{{tag.color}};width:20px;height:20px;opacity:1;'></div></div><div ng-if='tag.editing' class='color-sample color-picker-tags-menu pickr' ng-click='openColorPicker($event, $index,tag)' style='margin-right:8px'><div class='box' style='border:none;background-color:{{tag.color}};width:20px;height:20px;opacity:1;'></div></div><div style='width: 80px;'>{{tag.color}}</div></td>");
                row_content.append("<td class='ng-binding'><div class='icon delete_icon' title='Delete Tag' ng-click='deleteTag($event, $index)'></div> <div ng-if='tag.editing' class='icon save_icon' title='Save Tag' ng-click='saveTag($event, $index, tag)'></div><div ng-if='!tag.editing' class='icon edit_icon' title='Edit Tag' ng-click='editTag($event, $index, tag)'></div></td>");

                table.append($(row_content));
                content.append(table);
                content.append($('<button class="save_button save-tags-settings" ng-click="saveTags()">Save</button>'));
                verification.append(content);
                edit_tags_modal.append(verification);
                tabContent.append($compile(edit_tags_modal)($scope));
                
            }

            $scope.openTagsModal();

        }


        $scope.importRuleFile = function () {

            $scope.openImportFileModal = function() {
                //new section verification modal
                newrulefilemodal = $("<div class='modal' id='import-rule-modal' style='display:block;'></div>");
                verification = $("<div class='verification modal_content'></div>");
                verification.append($('<button class="close_btn icon" value="#import-rule" ng-click="closeModal(this)"></button>'));
                verification.append($('<div class="warning">Please select a .txt file to be imported</div>'));
                verification.append($('<div class="target">The file must be adequately formatted</div>'));
                verification.append($('<input class="config_input" type="file" id="import_item" accept=".txt">')); //input file
                verification.append($('<div class="confirmation_btns"><button ng-click="importRules(true)">Import Rules<br>(Replace file if it exists)</button><button ng-click="importRules(false)">Import Rules<br>(Add to file if it exists)</button></div>'));
                newrulefilemodal.append(verification);
                tabContent.append($compile(newrulefilemodal)($scope));
                // fix this modal
            }

            $scope.importRules = function (action) {
                $scope.importedItems = null;
                var fileInput = document.getElementById('import_item');
                var file = fileInput.files[0];
                var filename = fileInput.files[0].name;
                var reader = new FileReader();
                reader.onload = function(e) {
                    filecontent = reader.result;

                    if (action) {
                        // file is replaced if it exists
                        $smartboards.request('settings', 'ruleGeneralActions', {course: $scope.course, importFile: true, filename: filename, file: filecontent, replace: action}, function(data, err) {
                            $scope.rules = data.rules;
                            $("#import-rule-modal").hide();
                        });
                    }
                    else {
                        // appends to end of file if it exists
                        $smartboards.request('settings', 'ruleGeneralActions', {course: $scope.course, importFile: true, filename: filename, file: filecontent, replace: action}, function(data, err) {
                            $scope.rules = data.rules;
                            $("#import-rule-modal").hide();
                        });
                    }
                }
                reader.readAsDataURL(file);
            }
            
            $scope.openImportFileModal();
        }

        $scope.exportRuleFiles = function () {
            filename = "rules_course_" + String($scope.course) + ".zip";
            
            $smartboards.request('settings', 'ruleGeneralActions', {course: $scope.course, exportRuleFiles: true, filename: filename}, function(data, err) {
                var file = "http://localhost/gamecourse/" + filename;
                location.replace(file);
            });
        }


        $scope.rulesystemSettings = function () {
            // Rule System Settings/Config Menu
            $smartboards.request('settings', 'ruleSystemSettings', {course: $scope.course, getAutoGameStatus: true, getMetadataVars: true}, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }

                $scope.autogame_status = data.autogameStatus;
                $scope.metadata = data.autogameMetadata;
                $scope.metadata.forEach(function (element) {
                    element.editing = false;
                });
                  
                $scope.target_role = "Student"; // TO DO

                if (first_run) {
                    $scope.openSettingsModal($scope);
                    $("#rulesystem-settings-tabs").tabs();
                }
                else {
                    $("#rule-settings-modal").show();
                }
            });

            $smartboards.request('settings', 'ruleSystemSettings', {course: $scope.course, getAvailableRoles: true}, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $scope.available_roles = data.availableRoles;
            });

            $scope.addNewVariable = function () {
                var newvar = {};
                newvar.var = "";
                newvar.val = "";
                newvar.editing = true;
                $scope.metadata_edit.unshift(newvar);
            }


            // -------------- SETTINGS : RULE SYSTEM --------------

            $scope.runAllTargets = function() {
                /*$smartboards.request('settings', 'runRuleSystem', {course: $scope.course, runAllTargets: true}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                });*/ // TESTS
            }

            $scope.runSelectedTargets = function() { // TO DO
                /*var selected_targets = [];
                targets = angular.copy($scope.selected_targets.sort());
                targets = targets.map(el => el.id);
                targets.forEach(function(el){
                    selected_targets.push(el);
                });
                
                selected_targets = "[" + selected_targets.join(",") + "]";
                
                $smartboards.request('settings', 'runRuleSystem', {course: $scope.course, runSelectedTargets: true, selectedTargets: selected_targets}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                });*/ // TESTS
                
            }

            $scope.runRuleSystem = function () {
            }

            $scope.deleteTarget = function (target, $index) {
                $scope.selected_targets.splice($index, 1);
            }
            
            $scope.editVariable = function($event, item, pos) {
                $scope.metadata_edit[pos].editing = true;
            }

            $scope.saveVariable = function($event, item, pos) {
                var row = $($event.target).parent().parent();
                var name = row.children().eq(0).find("input").val();
                var val = row.children().eq(1).find("input").val();
                $scope.metadata_edit[pos].var = name;
                $scope.metadata_edit[pos].val = val;
                $scope.metadata_edit[pos].editing = false;

            }

            $scope.deleteVariable = function($event, item, pos) {
                $scope.metadata_edit.splice(pos, 1);
                /*
                $scope.confirmDeleteRule = function (){
                    mod.rules.splice(pos, 1);
                    $( ".modal" ).remove();
                    $smartboards.request('settings', 'ruleSystemSettings', {course: $scope.course, deleteVariable: true, index: pos}, alertUpdateNoReload);
                }*/
            }

            $scope.saveVariablesToFile = function() {
                $smartboards.request('settings', 'ruleSystemSettings', {course: $scope.course, saveVariable: true, variables: $scope.metadata_edit}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                });

                $scope.metadata = $scope.metadata_edit;
                console.log($scope.metadata);
            }



            $scope.openSettingsModal = function($scope) {

                $scope.metadata_edit = angular.copy($scope.metadata);

                //new tags modal
                rule_settings_modal = $("<div class='modal' id='rule-settings-modal' style='display:block;'></div>");
                verification = $("<div class='modal_content'></div>");
                verification.append($('<button class="close_btn icon" value="#rule-settings-modal" ng-click="closeSettingsModal(this)"></button>'));
                verification.append($('<div class="title" id="open_tier_action">Rule System Settings </div>'));
                content = $('<div class="content"></div>');

                /* Socket table */
                tabs_container = $('<div id="rulesystem-settings-tabs"></div>');
                tabs_nav = $('<ul></ul>');
                tab_one_nav = $('<li><a href="#tab-1"><span>Communication</span></a></li>');
                tab_two_nav = $('<li><a href="#tab-2"><span>Variables</span></a></li>');
                tab_three_nav = $('<li><a href="#tab-3"><span>Targets</span></a></li>');
                tab_four_nav = $('<li><a href="#tab-4"><span>General</span></a></li>');
                tab_one = $('<div id="tab-1">');
                tab_two = $('<div id="tab-2">');
                tab_three = $('<div id="tab-3">');
                tab_four = $('<div id="tab-4">');

                tab_one_section = $('<div class="config-section"></div>');
                socketdesc = $("<h3>Socket Communication <div class='help-icon' title='Communication in GameRules components is done through sockets. This table shows the state of the two sockets that communicate with each other for retrieving information.'></div></h3>");
                content_table = $('<div class="content data-table socket-table"></div>');
                var table = $('<table id="autogame-table" style="margin-bottom: 3em;">');
                rowHeader = $("<tr class='tableheader'></tr>");
                rowHeader.append("<th>Course Id</th><th>Description</th><th>Last Started Running</th><th>Last Finished Running</th><th>Running</th>");
                table.append(rowHeader);

                row_content = $("<tr ng-repeat='row in autogame_status'>");
                row_content.append("<td class='ng-binding'>{{row.course}}</td>");
                row_content.append("<td class='ng-binding'><span ng-if='row.course != \"0\"'>This Course</span><span ng-if='row.course == \"0\"'>Socket</span></td>");
                row_content.append("<td class='ng-binding'>{{row.startedRunning}}</td>");
                row_content.append("<td class='ng-binding'>{{row.finishedRunning}}</td>");
                row_content.append("<td class='ng-binding'>{{row.isRunning}}</td>");

                table.append($(row_content));
                content_table.append(table);
                tab_one_section.append(socketdesc,content_table);

                reset_socket = $('<button id="reset-socket-button" class="button" ng-click="resetSocket()" style="background-color: tomato;" title="Sets the Socket as non-running">Reset Socket</button>');
                reset_course = $('<button id="reset-course-button" class="save_btn button" ng-click="resetCourse()" style="background-color: tomato;" title="Sets the Course as non-running">Reset Course</button>');

                tab_one.append(tab_one_section, reset_socket, reset_course);

                
                //save_button_2 = $('<div class="settings-wrapper"><button class="save-settings" ng-click="saveSettings()">Save Settings</button></div>');
                
                // Metadata Vars table

                content_table = $('<div class="content data-table vars-table"></div>');
                action_buttons = $("<div class='icon add_icon' title='Add New Variable' value='#add-variable' ng-click='addNewVariable(this)'></div>");

                tab_two_section = $('<div class="config-section"></div>');
                var table = $('<table id="metadata-table" style="margin-bottom: 3em;">'); 
                metadata_header = $("<tr class='tableheader'></tr>");
                metadata_header.append("<th class='name-column'>Variable Name</th><th>Value</th><th>Actions</th>");
                table.append(metadata_header);

                metadata = $("<tr ng-repeat='var in metadata_edit'>");
                
                metadata.append("<td class='ng-binding name-column'><span ng-if='!var.editing'>{{var.var}}</span><input ng-if='var.editing' type='text' id='var-name' class='form__input ng-pristine ng-valid ng-empty ng-touched' placeholder='Variable Name' value={{var.var}}></td>");
                metadata.append("<td class='ng-binding'><span ng-if='!var.editing'>{{var.val}}</span><input ng-if='var.editing' type='text' id='var-value' class='form__input ng-pristine ng-valid ng-empty ng-touched' placeholder='Variable Value' value={{var.val}}></td>");
                metadata.append("<td class='action-column metadata-icons'><div class='icon delete_icon' title='Remove Variable' ng-click='deleteVariable($event, var, $index)'></div><div ng-if='!var.editing' class='icon edit_icon' title='Edit Variable' ng-click='editVariable($event, var, $index)'></div><div ng-if='var.editing' class='icon save_icon' title='Save Variable' ng-click='saveVariable($event, var, $index)'></div></td>");
                
                metadata_buttons = $('<div class="settings-wrapper"><button class="cancel" value="#delete-verification" ng-click="closeModal(this);resetMetadata()">Cancel</button><button class="save-settings" ng-click="saveVariablesToFile();closeModal(this)">Save Settings</button></div>');


                table.append($(metadata));
                content_table.append(table);
                tab_two_section.append($('<h3>Metadata <div class="help-icon" title="In this section you can define variables to be used when creating rules for the rulesystem. Some example variables would be number_of_classes, max_xp, etc."></div></h3><span class="settings-desc">Add or edit metadata variables to be used in the RuleSystem rules.</span>'));
                tab_two_section.append(action_buttons);
                tab_two_section.append(content_table);


                tab_two.append(tab_two_section, metadata_buttons);


                // Running Mode // Targets
                
                //tab_three_section = $('<div class="config-section"></div>');
                //tab_three_section.append($("<h3>Targets <div class='help-icon' title='Targets are the entities over which the Rule System runs. In a class setting the targets should be the students, since they are the system entities that will be given rewards.'></div></h3><span>Set target role eligible for the RuleSystem:</span><br>"));
                //role_select = $('<select id="target-roles" class="form__input" name="roles">');
                //role_select.append($('<option ng-repeat="role in available_roles" value=role.name>{{role.name}}</option>'));
                //tab_three_section.append(role_select);
                //tab_three.append(tab_three_section);

                tab_three_section = $('<div class="config-section"></div>');
                tab_three_section.append($("<h3>All Targets</h3><span>Run Rule System for all existing course targets (all users with the {{target_role}} type role).</span><br>"));
                run_all_targets_button = $('<button id="run-all-targets-button" class="button" ng-click="runAllTargets()" title="Runs RuleSystem for all course targets">Run All Targets</button>');
                
                tab_three_section.append(run_all_targets_button);
                tab_three.append(tab_three_section);

                tab_three_section = $('<div class="config-section"></div>');
                ruletargetsh3 = $("<h3>Select Targets</h3><span>Select targets to run the Rule System for. Type a Student's name in the box below and click enter to select.</span><br>");
                ruletargetsinput = $('<input type="text" id="rulesystem-targets" class="form__input ng-pristine ng-valid ng-empty ng-touched" placeholder="Type student name here">');
                ruletargetslist = $('<div id="targets-list"></div>');
                ruletargetslist.append('<div class="rule-targets" ng-repeat="target in selected_targets"><span class="target-text">{{target.id}} - {{target.name}}</span><span class="delete-target delete_icon" ng-click="deleteTarget(target,$index)"></span></div>');
                run_targets_button = $('<button id="run-all-targets-button" class="button" ng-click="runSelectedTargets()" title="Runs RuleSystem for selected targets">Run Selected Targets</button>');
                
                tab_three_section.append(ruletargetsh3, ruletargetsinput, ruletargetslist, run_targets_button);
                tab_three.append(tab_three_section);

                //tab_three.append(save_button_2); // optional, just for case where role for target is choosable

                // General
            
                tab_four_section = $('<div class="config-section"></div>');
                tab_four_section.append($('<h3>Run</h3><span class="settings-desc">Run the rulesystem manually if it is not already running.</span>'));
                run_button = $('<button id="run-all-targets-button" class="button" ng-click="runRuleSystem()" title="Runs RuleSystem for available targets">Run Rule System</button>');
                tab_four_section.append(run_button);
                tab_four.append(tab_four_section);

                tabs_nav.append(tab_four_nav, tab_one_nav, tab_two_nav, tab_three_nav); 
                tabs_container.append(tabs_nav, tab_four, tab_one, tab_two, tab_three);

                content.append(tabs_container);

                verification.append(content);
                rule_settings_modal.append(verification);
                tabContent.append($compile(rule_settings_modal)($scope));
                
                first_run = false;
                $( ".help-icon" ).tooltip({
                    // start tooltip for the help bubbles
                    classes: {
                      "ui-tooltip": "help-tooltip"
                    },
                    track: true
                });

                // target autocomplete configuration TO DO
                $(function() {
                    $("#rulesystem-targets").autocomplete({
                    source: $scope.student_targets.map(student => student.id + " - " + student.name),
                    appendTo: '.tab-content',
                    delay: 0,
                    select: function( event, ui ) { // selecting a value from the list
                        st = ui.item;
                        params = st.label.split(" - ")
                        user = {};
                        user.id = params[0];
                        user.name = params[1];
                        if (!($scope.selected_targets.find(el => el.id == user.id))) {
                            $scope.$apply($scope.selected_targets.push(user));
                        }
                        $(this).val('');
                        return false;
                    }
                    });

                } );

            }


            $scope.resetSocket = function() {
                $smartboards.request('settings', 'ruleSystemSettings', {course: $scope.course, resetSocket: true}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    if (data.socketUpdated) {
                        $scope.autogame_status = data.autogameStatus;
                    }
                    else {
                        // open modal or indicate error TODO
                    }
                });
            }

            $scope.resetCourse = function() {
                $smartboards.request('settings', 'ruleSystemSettings', {course: $scope.course, resetCourse: true}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    $scope.autogame_status = data.autogameStatus;
                });
            }

        }
        


        // -------------- SECTION ACTIONS --------------

        $scope.exportRules = function(mod) {
            $smartboards.request('settings', 'ruleSectionActions', { course: $scope.course, exportRuleFile: true, module: mod }, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                downloadPlainText(mod + ".txt", data);
            });
        }

        $scope.increasePriority = function (item, mod, index) {
            if (index > 0 && index < $scope.rules.length) {
                $smartboards.request('settings', 'ruleSectionActions', {course: $scope.course, increasePriority: true, module: mod.id, filename : mod.filename}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                });
                $scope.rules.swap(index, index - 1, null, true);
            }
        }

        $scope.decreasePriority = function (item, mod, index) {
            if (index >= 0 && index < $scope.rules.length - 1) {
                $smartboards.request('settings', 'ruleSectionActions', {course: $scope.course, decreasePriority: true, module: mod.id, filename : mod.filename}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                });
                $scope.rules.swap(index + 1, index, null, true);
            }
        }

        $scope.deleteSection = function (item, mod, index) {
            //delete section verification modal
            deletemodal = $("<div class='modal' id='delete-section-verification' style='display:block;'></div>");
            verification = $("<div class='verification modal_content'></div>");
            verification.append($('<button class="close_btn icon" value="#delete-section-verification" ng-click="closeModal(this)"></button>'));
            verification.append($('<div class="warning">Are you sure you want to delete section</div>'));
            verification.append($('<div class="target" id="delete_action_info">'+ mod.name +'</div>'));
            verification.append($('<div class="confirmation_btns"><button class="cancel" value="#delete-section-verification" ng-click="closeModal(this)">Cancel</button><button class="continue" ng-click="confirmDeleteSection()">Delete</button></div>'))
            deletemodal.append(verification);
            tabContent.append($compile(deletemodal)($scope));

            $scope.confirmDeleteSection = function () {
                $smartboards.request('settings', 'ruleSectionActions', {course: $scope.course, deleteSection: true, module: mod.id, filename: mod.filename}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                                
                    $scope.rules.splice(index, 1);
                    $scope.fixPrecedences();
                    $scope.closeModal();
                });
            }
            
        }

        $scope.fixPrecedences = function () {
            for (i = 0; i < $scope.rules.length; i++) {
                pos = i + 1; 
                $scope.rules[i].filename = pos.toString() + " - " + $scope.rules[i].id + ".txt";
                $scope.rules[i].rules.forEach(el => el.rulefile = pos.toString() + " - " + $scope.rules[i].id + ".txt");
            }
        }

        $scope.collapseExpand = function ($event, item, module, index) {
            target = $event.target;
            selector =  "#" + module + ".data-table";
            var state = $(selector).css("display");

            if (state == 'block') {
                $(selector).css("display", "none");
                target.classList.remove("collapse_icon");
                target.classList.add("expand_icon");
            }
            else if (state == 'none') {
                $(selector).css("display", "block");
                target.classList.remove("expand_icon");
                target.classList.add("collapse_icon");
            }
            
        }


        // -------------- RULE ACTIONS --------------

        // action performed for toggling a rule
        $scope.toggleRule = function(item, pos) {
            $scope.rule = item;
            $smartboards.request('settings', 'ruleSpecificActions', {course: $scope.course, toggleRule: true, rule: $scope.rule, index: pos}, alertUpdateNoReload);
        }

        // hides + shows the description of a rule when the rule's name is clicked
        $scope.toggleDesc = function($event){
                el = $event.target;
                try {
                    currentState = el.parentElement.children[1].style.display;
                    if (currentState == "none" || typeof currentState == 'undefined') {
                        el.parentElement.children[1].style.display = "inline-block";
                        el.parentElement.parentElement.style.backgroundColor = "#f6f6f6";
                    }
                    else {
                        el.parentElement.children[1].style.display = "none";
                        el.parentElement.parentElement.style.backgroundColor = "#ffffff";
                    }
                }
                catch (err) {}
        }

        $scope.togglePreviewSection = function($event) {

            target = $event.target;
            selector =  "#preview-functions";
            var state = $(selector).css("display");

            if (state == 'block') {
                $(selector).css("display", "none");
                target.classList.remove("collapse_icon");
                target.classList.add("expand_icon");
            }
            else if (state == 'none') {
                $(selector).css("display", "block");
                target.classList.remove("expand_icon");
                target.classList.add("collapse_icon");
            }

        }

       // edit rule TO DO move here

        // duplicating a rule
        $scope.duplicateRule = function($event, item, pos, mod) {
            $scope.rule = item;
            new_rule = angular.copy(item);
            new_rule.name = new_rule.name + " Copy";
            new_rule.active = false;
            $smartboards.request('settings', 'ruleSpecificActions', {course: $scope.course, duplicateRule: true, rule: new_rule, rules: $scope.rules, index: pos}, alertUpdateNoReload);
            mod.rules.splice(pos + 1, 0, new_rule);
        }

        // deleting a rule
        $scope.deleteRule = function($event, item, pos, mod) {

            $scope.confirmDeleteRule = function (){
                mod.rules.splice(pos, 1);
                $( ".modal" ).remove();
                $smartboards.request('settings', 'ruleSpecificActions', {course: $scope.course, deleteRule: true, rule: $scope.rule, index: pos}, alertUpdateNoReload);
            }

            $scope.rule = item;
            
            //delete rule verification modal
            deletemodal = $("<div class='modal' id='delete-verification' style='display:block;'></div>");
            verification = $("<div class='verification modal_content'></div>");
            verification.append($('<button class="close_btn icon" value="#delete-verification" ng-click="closeModal(this)"></button>'));
            verification.append($('<div class="warning">Are you sure you want to delete</div>'));
            verification.append($('<div class="target" id="delete_action_info">'+ item.name +'</div>'));
            verification.append($('<div class="confirmation_btns"><button class="cancel" value="#delete-verification" ng-click="closeModal(this)">Cancel</button><button class="continue" ng-click="confirmDeleteRule()"> Delete</button></div>'))
            deletemodal.append(verification);
            tabContent.append($compile(deletemodal)($scope));
        }

        // moving a rule up
        $scope.moveUpRule = function($event, item, pos, mod) {
            $scope.rule = item;
            $smartboards.request('settings', 'ruleSpecificActions', {course: $scope.course, moveUpRule: true, rule: $scope.rule, index: pos}, alertUpdateNoReload);
            // if not error
            mod.rules.swap(pos, pos - 1, mod.id);
        }

        // moving a rule down
        $scope.moveDownRule = function($event, item, pos, mod) {
            $scope.rule = item;
            $smartboards.request('settings', 'ruleSpecificActions', {course: $scope.course, moveDownRule: true, rule: $scope.rule, index: pos}, alertUpdateNoReload);
            mod.rules.swap(pos, pos + 1, mod.id);
        }

        $scope.listRules = function () {
            
            $smartboards.request('settings', 'ruleSystemSettings', {course: $scope.course, getAutoGameStatus: true, getMetadataVars: true}, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }

                $scope.metadata = data.autogameMetadata;
            });

            $smartboards.request('settings', 'ruleGeneralActions', {course: $scope.course, getTargets: true}, function(data, err) {
                $scope.student_targets = data.targets;
            });

            tabContent.empty();
            
            $scope.rules = data.rules;
            $scope.rules_save = angular.copy($scope.rules);
            $scope.tags = data.tags;
            $scope.tags_save = angular.copy($scope.tags);
            $scope.selected_targets = [];
            
            
            console.log(data.rules);

            search = $("<div class='search'><button class='magnifying-glass' id='search-btn'></button><input ng-change='searchInput()' type='text' id='search-input' placeholder='Search' name='search' ng-model='search'></div>")
            $compile(search)($scope);
            tabContent.append(search);
            sectionstart = $('<div class="section course-related visible rule-header"><h2 class="title">Rule List</h2><div class="description">List of available rules in the course.</div>');
            action_buttons = $("<div class='action-buttons general-rule-actions'></div>");
            action_buttons.append($("<div class='icon add_icon' title='Create New Section' value='#add-rulefile' ng-click='newSection(this)'></div>"));
            action_buttons.append($("<div class='icon tags_circle_icon' title='Edit Tags' value='#edit-tags' ng-click='editTags()'></div>"));
            action_buttons.append($("<div class='icon import_icon' title='Import Rule File' value='#import-rule-file' ng-click='importRuleFile()'></div>"));
            action_buttons.append($("<div class='icon export_icon' title='Export Rule Files' value='#export-rule-files' ng-click='exportRuleFiles()'></div>"));
            action_buttons.append($("<div class='icon configure_circle_icon' title='Settings' value='#rule-settings' ng-click='rulesystemSettings()'></div>"));
        
            sectionstart.append($compile(action_buttons)($scope));
            tabContent.append(sectionstart);

            section = $("<div id='module-listing' class='section' ng-repeat='mod in rules'>");
            var divider = $('<div class="divider"><div class="title"><span>{{mod.name}}</span></div></div>');
            var content = $('<div>', { 'class': 'content' });
            action_buttons = $("<div class='action-buttons rules-actions'></div>");
            action_buttons.append($("<div class='icon add_icon' title='New Rule' value='' ng-click='editRule($event, null, $index, mod, true)'></div>"));
            action_buttons.append($("<div class='icon export_icon' title='Export Rules' ng-click='exportRules(mod)'></div>"));
            action_buttons.append($("<div class='icon increase_circle_icon icon_background' title='Increase Priority' value='' ng-click='increasePriority(this, mod, $index)'></div>"));
            action_buttons.append($("<div class='icon decrease_circle_icon icon_background' title='Decrease Priority' value='' ng-click='decreasePriority(this, mod, $index)'></div>"));
            action_buttons.append($("<div class='icon delete_circle_icon icon_background' title='Delete Section' value='' ng-click='deleteSection(this, mod, $index); $event.stopPropagation();'></div>"));
            action_buttons.append($("<div class='icon collapse_icon' title='Collapse' value='' ng-click='collapseExpand($event, this, mod.id, $index)'></div>"));
            var moduletable = $('<div class="data-table rule-listing" id="{{mod.id}}"></div>');
            var table = $('<table>');
            rowHeader = $("<tr class='tableheader'></tr>");
            rowHeader.append("<th>#</th><th>Status</th><th>Rule Name</th><th>Tags</th><th>Actions</th>");
            table.append(rowHeader);
            

            row_content = $("<tr ng-repeat='rule in mod.rules'>");
            row_content.append("<td class='ng-binding' style='width: 70px;'>{{$index + 1}}</td>");
            row_content.append("<td class='check-column' style='width: 70px;'><label class='switch'><input type='checkbox' ng-model='rule.active'><span class='slider round' ng-click='toggleRule(rule, $index); $event.stopPropagation();'></span></label></td>");
            row_content.append("<td class='rule-name-td'><strong ng-click='toggleDesc($event)' style='display: block; width: 100%;' class='rule-name'>{{rule.name}}</strong><p class='rule-description' style='display: none;' ng-if='rule.description'>{{rule.description}}</p></td>");
            //row_content.append("<td class='ng-binding' style='width: 200px;'><div ng-if='!rule.tags.length'>—</div><div class='rule-tag-preview' ng-repeat='tag in rule.tags' style='background-color: {{tag.color}}'>{{tag.name}}</div></td>");
            row_content.append("<td class='ng-binding' style='width: 200px;'><div ng-if='!rule.tags.length'>—</div><div class ng-repeat='tag in tags' style='display: inline-block;'><div class='rule-tag-preview' ng-repeat='ruletag in rule.tags' ng-if='ruletag.name == tag.name' style='background-color: {{tag.color}}'>{{tag.name}}</div></div></td>");
            //ruletagslist.append('<div class="rule-tag" ng-repeat="tag in tags" ng-if="ruletags.includes(tag.name)" style="background-color: {{tag.color}}">{{tag.name}}</div>');
            row_content.append('<td class="action-column"><div class="icon edit_icon" title="Edit Rule" ng-click="editRule($event, rule, $index, mod)"></div></td>');
            row_content.append('<td class="action-column"><div class="icon duplicate_icon" title="Duplicate Rule" ng-click="duplicateRule($event, rule, $index, mod)"></div></td>');
            row_content.append('<td class="action-column"><div class="icon delete_icon" title="Remove" ng-click="deleteRule($event, rule, $index, mod)"></div></td>');
            row_content.append('<td class="action-column"><div class="icon up_icon" title="Move up" ng-click="moveUpRule($event, rule, $index, mod)"></div></td>');
            row_content.append('<td class="action-column"><div class="icon down_icon" title="Move down" ng-click="moveDownRule($event, rule, $index, mod)"></div></td>');

            table.append(row_content);
            moduletable.append(table);
            
            
            section.append(divider);
            content.append(moduletable);
            content.append(action_buttons);
            section.append(content);
            
            tabContent.append($compile(section)($scope));

            $( ".help-icon" ).tooltip({
                // start tooltip for the help bubbles
                classes: {
                  "ui-tooltip": "help-tooltip"
                },
                track: true
            });

        }


        // Edit Rule Page
        $scope.editRule = function($event, item, pos, mod, add=false) {

            tabContent.empty();
            
            $(".tab-content-wrapper").scrollTop(0);

            if (add) {
                $scope.rule = {};
                $scope.rule.name = "";
                $scope.rule.active = true;
                $scope.rule.module = mod.id;
                $scope.rule.tags = [];
                $scope.rule.description = "";
                $scope.rule.when = "";
                $scope.rule.then = "";
                $scope.rule.rulefile = mod.filename;
                $scope.rule.filename = mod.filename.split("-")[1].trim();
                $scope.index = 0;
            }
            else {
                $scope.rule = item;
                $scope.index = pos;
            }

            // originals
            $scope.libraries = {};
            $scope.functions = {};
            $scope.grfunctions = $scope.gamerules_funcs;
            $scope.preview_history = [];
            $scope.rules_variables = [];

            // duplicates
            $scope.curr_libraries_when = {};
            $scope.curr_functions_when = {};
            $scope.curr_functions_then = {};
            $scope.active_func_when = {};
            $scope.active_func_then = {};


            $smartboards.request('settings', 'ruleEditorActions', {course: $scope.course, getLibraries: true}, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $scope.libraries = data;
                console.log(data);
            });

            $smartboards.request('settings', 'ruleEditorActions', {course: $scope.course, getFunctions: true}, function(data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
                $scope.functions = data;
                console.log(data);
            });

            $scope.isEmptyObject = function(obj) {
                return angular.equals({}, obj) || angular.equals(null, obj) ;
            };
            

            // --------- TOP SECTION ---------
            topnav = $('<div id="top-nav"></div>');
            back = $('<div class="icon return_icon" id="return-button" title="Return to List" ng-click="listRules()" ></div>');
            topnav.append($compile(back)($scope));
            buttoncontainer = $('<div id="button-container"></div>');
            cancel = $('<button id="cancel-rule-button" class="button" ng-click="listRules()">Cancel</button>');
            save = $('<button id="save-rule-button" class="button" ng-click="submitRule()">Save</button>'); // TO DO
            buttoncontainer.append($compile(cancel)($scope), $compile(save)($scope));
            topnav.append(buttoncontainer);
            tabContent.append(topnav);

            if (add)
                sectionstart = $('<div class="section course-related visible rule-header-edit"><h2 class="title">Add Rule — [' + mod.name + ']</h2>');
            else
                sectionstart = $('<div class="section course-related visible rule-header-edit"><h2 class="title">Edit Rule</h2>');

            sectionstart.append($compile(sectionstart)($scope));
            tabContent.append(sectionstart);

            wrapper = $('<div class="rule-container">');
            cont = $('<div class="rule-section">');

                // --------- NAME SECTION ---------
            box = $('<div class="rule-part-box">');
            rname = $('<div class="title"><span>Rule Name</span></div>');
            rulenameinput = $('<input type="text" id="rule-name" class="form__input ng-pristine ng-valid ng-empty ng-touched" placeholder="Rule Name" value={{rule.name}}><br>');
            box.append(rname,rulenameinput);
            cont.append(box);

                // --------- DESCRIPTION SECTION ---------
            box = $('<div class="rule-part-box">');
            rdesc = $('<div class="title"><span>Description</span></div>');
            ruledescinput = $('<textarea type="text" id="rule-description" class="form__input ng-pristine ng-valid ng-empty ng-touched" placeholder="Rule Description">{{rule.description}}</textarea><br>');
            box.append(rdesc, ruledescinput);
            cont.append(box);

                // --------- TAGS SECTION ---------
            box = $('<div class="rule-part-box">');
            rtags = $('<div class="title"><span>Tags</span></div>');
            ruletagsinput = $('<input type="text" id="rule-tags" class="form__input ng-pristine ng-valid ng-empty ng-touched" placeholder="Rule Tags">');
            ruletagslist = $('<div id="tags-list" ></div>');
            ruletagslist.append('<div class="rule-tag color-picker-tags pickr" ng-repeat="tag in rule.tags" style="background-color: {{tag.color}}" id="tag-named-{{tag.name}}"><span class="tag-text">{{tag.name}}</span><span class="delete-tag delete_icon" ng-click="deleteRuleTag(tag)"></span></div>');

            rdivider = $('<hr class="divider-strong">');

            box.append(rtags,ruletagsinput,ruletagslist);
            cont.append(box);

            wrapper.append(cont);
            tabContent.append($compile(wrapper)($scope));
            tabContent.append(rdivider);

            // --------- LEFT SECTION ---------
            wrapper = $('<div class="rule-container">');
            cont = $('<div class="rule-section" id="clauses-container">');
            leftbox = $('<div class="rule-left-box">');
            box = $('<div class="rule-part-box">');

            rwhen = $('<div class="title"><span>When</span></div>');
            code_when = $scope.rule.when;
            rulewheninput = $('<textarea type="text" id="rule-when" class="form__input ng-pristine ng-valid ng-empty ng-touched">' + code_when + '</textarea>');
            box.append(rwhen,rulewheninput);
            leftbox.append(box);

            box = $('<div class="rule-part-box">');
            rthen = $('<div class="title"><span>Then</span></div>');
            code_then = $scope.rule.then;
            ruletheninput = $('<textarea type="text" id="rule-then" class="form__input ng-pristine ng-valid ng-empty ng-touched">' + code_then + '</textarea>');
            box.append(rthen,ruletheninput);
            leftbox.append($compile(box)($scope));
            
            // --------- RIGHT SECTION ---------
            rightbox = $('<div class="rule-right-box">');
            box = $('<div class="rule-part-box" id="right-part-box">');

                // --------- FUNCTION SECTION ---------
            rfunctions = $('<div class="title"><span>Tools</span> <div class="info-icon" title="Informations" ng-click="openHelpModal(); $event.stopPropagation();"></div></div>');
            rfunctionsbuttons = $('<div id="rule-functions-buttons"><button class="rule-functions-button" value="#rule-functions" ng-click="switchTabs($event)">Functions</button><button class="rule-metadata-button" value="#rule-metadata" ng-click="switchTabs($event)">Metadata</button></div>');
            rfunctionsinput = $('<div id="rule-functions"></div>');
            typing_help = $('<div id="typing-help"><p>Type something in the boxes on the left for suggestions</p><p>Type "GC." in the When box for Expression Language suggestions</p><p>More information on the info menu</p></div>');
            rfunctionsulwhen = $('<ul id="rule-when-help"></ul>');
            rfunctionsulthen = $('<ul id="rule-then-help"></ul>');
            rfunctionslibs = $('<li ng-repeat="lib in curr_libraries_when" ng-if="lib.moduleId != null" class="lib-list-item">{{lib.name}}</li><li ng-repeat="func in curr_functions_when" ng-if="func.moduleId != null" class="func-list-item">{{func.keyword}}</li>');
            functiondescwhen = $('<div id="func-info-when" style="display:none;"><div ng-if="!isEmptyObject(active_func_when.args)" class="arguments-div">Arguments:<ul id="func-info-args"><li ng-repeat="arg in active_func_when.args" class="arg-list-item"><strong>{{arg.name}}</strong><span ng-if="arg.type != \'\'"><strong>:</strong> {{arg.type}}</span><span ng-if="arg.optional != \'1\'" class="optional">*</span></li></ul></div><div class="function-description" ng>{{active_func_when.description}}</div></div>'); // TO DO check if this is ok
            functiondescthen = $('<div id="func-info-then" ng-if="!isEmptyObject(active_func_then)" style="display:none;"><div ng-if="!isEmptyObject(active_func_then.args)" class="arguments-div">Arguments:<ul id="func-info-args"><li ng-repeat="arg in active_func_then.args" class="arg-list-item"><strong>{{arg.name}}</strong><span ng-if="arg.optional == \'1\'">{{arg.type}}</span><span ng-if="arg.optional != \'1\'" class="optional">*</span></li></ul></div><div class="function-description" ng>{{active_func_then.description}}</div></div>'); // TO DO check if this is ok
            rfunctionsgc = $('<li ng-repeat="func in curr_functions_then" class="gcfunc-list-item">{{func.keyword}}</li>');

                // --------- METADATA SECTION ---------
            rmetadatainput = $('<div id="rule-metadata" style="display: none"><ul></ul></div>');
            rmetadatali = $('<li ng-repeat="var in metadata" class="metadata-list-item" ng-click="addMetadataToEditor($index)"><span class="blue">{{var.var}} = </span><span class="red">{{var.val}}</span></li>');
            
            

            rfunctionsulwhen.append(rfunctionslibs);
            rfunctionsulthen.append(rfunctionsgc);
            rmetadatainput.append(rmetadatali);

            rfunctionsinput.append(typing_help, rfunctionsulwhen, rfunctionsulthen, functiondescwhen, functiondescthen);
            box.append(rfunctions, rfunctionsbuttons, rfunctionsinput, rmetadatainput);
            rightbox.append(box);

            cont.append(leftbox);
            cont.append($compile(rightbox)($scope));

            // --------- PREVIEW SECTION ---------
            contpreview = $('<div class="rule-section">');
            preview = $('<div class="rule-part-box" id="preview-box">');
            previewfunctions = $('<div class="title"></div>');
            previewfunctions.append("<div class='icon editor-icon collapse_icon' style='float: left;' id='preview-box-toggle' title='Toggle' ng-click='togglePreviewSection($event)'></div>");
            previewfunctions.append('<span>Preview Functions</span>');
            preview_buttons_container = $('<div class="preview-buttons-container"></div>');
            previewbuttons = $('<button id="preview-function-button" ng-click="previewFunction(); $event.stopPropagation();">Preview Function</button> <button id="preview-rule-button" ng-click="previewRule()">Preview Rule</button>');
            preview_buttons_container.append(previewbuttons);

            previewbox = $('<div id="preview-functions"></div>');
            previewboxlist = $('<ul></ul>');
            previewboxlist.append('<span ng-repeat="cmd in preview_history"><li class="request-li">> {{cmd.alias}}</li><span ng-if="cmd.response_array != null"><li class="response-index-li" ng-if="cmd.preview_type == \'function\'">Preview finished with no errors. {{cmd.response_array.length}} line(s) retrieved.</li><span ng-if="cmd.response_array != null"><li class="response-index-li" ng-if="cmd.preview_type == \'rule\'">Execution finished with no errors. {{cmd.response_array.length}} line(s) inserted.</li></span><span ng-if="cmd.response_array" ng-repeat="line in cmd.response_array"><li class="response-index-li">[{{$index}}]:</li><li class="response-item-li"><span ng-repeat="(key,value) in line">{{key}} : {{value}}<span ng-show="!$last">, </span></span></li></span><span ng-if="cmd.response"><li class="response-single-li">{{cmd.response}}</li></span></span>');
            
            previewbox.append(previewboxlist);
            preview.append(previewfunctions, preview_buttons_container, previewbox);
            contpreview.append(preview);

            wrapper.append(cont, contpreview);
            tabContent.append($compile(wrapper)($scope));

            $scope.previewFunction = function () {
                func = $scope.active_func_when["keyword"];
                funcmod = $scope.active_func_when["name"];
                args = $scope.curr_args;
                return_type = $scope.active_func_when["returnType"];
                new_args = [];
                args.forEach(function(el) {
                    if (el.trim() != "") {
                        new_args.push(el.trim());
                    }
                });
                alias = "GC." + funcmod + "." + func + "(" + args + ")";
                request = {"alias" : alias, "func" : func, "funcmod" : funcmod, "args" : args, "returnType" : return_type};
                // TO DO find out why two execs?

                $smartboards.request('settings', 'ruleEditorActions', {course: $scope.course, previewFunction: true, lib: funcmod, func: func, args: new_args}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }

                    if (return_type == "collection") {
                        request["response_array"] = JSON.parse(data);
                        request["response"] = null;
                        request["preview_type"] = "function";
                    }
                    else {
                        request["response_array"] = null;
                        request["response"] = data;
                        request["preview_type"] = "function";
                    }

                    $scope.preview_history.push(request);
                    
                });
            }

            $scope.addMetadataToEditor = function ($index) {
                metadata_var = $scope.metadata[$index];

                var cm = $(".CodeMirror")[0].CodeMirror;
                var doc = cm.getDoc();
                var cursor = doc.getCursor();
                var line = doc.getLine(cursor.line);
                var pos = {
                    line: cursor.line
                };

                text = "METADATA[\"" + metadata_var.var + "\"]";
                doc.replaceRange(text, pos);
            }

            $scope.openHelpModal = function() { 
                help_rules_modal = $("<div class='modal' id='help-rules-modal' style='display:block;'></div>");
                verification = $("<div class='modal_content'></div>");
                verification.append($('<button class="close_btn icon" ng-click="closeModal()"></button>'));
                verification.append($('<div class="title">Rule Writing Tips:</div>'));
                content = $('<div class="content help-modal-content"></div>');

                h0 = $('<h3>Target</h3>');
                h0desc = $('<div class="help-modal-content-span"></div>');
                h0p1 = $('<p> The <span class="hblue">target</span> keyword is used to represent the users to which the rule applies. If a function requires a "user" argument, instead of providing a single user, you <span class="hpink">must</span> provide the target keyword instead.</p>');
                h0p2 = $('<p>The target keyword generalizes the rule so it can be applied to all possible targets. When the rule is ran, it will be run for each user at a time, and the target keyword will allow the system to know for which user the rule is being run.</p>');

                h1 = $('<h3>Functions</h3>');
                h1desc = $('<div class="help-modal-content-span"></div>');
                h1p1 = $('<p>The rule syntax allows for the use of functions both from the GameCourse Expression Language and the GameRules Rule System.</p>');
                h1p2 = $('<p> To view GameCourse expression language suggestions, type <span class="hpink">GC.</span> in the When text box. These functions can be previewed by clicking <span class="hblue">"Preview Function"</span> in the <span class="hblue">"Preview"</span> section.</p>');
                h1p3 = $('<p>Suggestions for Game Rules syntax will show up automatically when something is typed on one of the boxes.</p>');

                h2 = $('<h3>Metadata</h3>');
                h2desc = $('<div class="help-modal-content-span"></div>');
                h2p1 = $('<p>The GameRules Rule System allows for user defined metadata to be used in rule writing.</p>');
                h2p2 = $('<p>A full list of the currently defined variables can be found on the <span class="hblue">Tools > Metadata</span> menu.</p>');
                h2p3 = $('<p><span class="hpink">Click</span> on a variable to add it to the boxes on the left. To alter or add more variables to the system, refer to the Rule System Settings menu on the Rule List page.</p>');

                
                h3 = $('<h3>Operators and Types</h3>');
                h3desc = $('<div class="help-modal-content-span"></div>');
                h3p1 = $('<p>The Rules follow python-like syntax. Valid operators that can be used are <span class="hpink">=</span>, <span class="hpink">+</span>, <span class="hpink">-</span>, <span class="hpink">+=</span>, <span class="hpink">-=</span>, <span class="hpink">*</span>, <span class="hpink">*=</span></p>');
                h3p2 = $('<p>Main types are available: boolean (<span class="hpink">True</span>, <span class="hpink">False</span>), int, float, string, list, etc.</p>');
                
                h4 = $('<h3>Dictionary</h3>');
                h4desc = $('<div class="help-modal-content-span"><p>The GameCourse Expression Language dictionary reference is located <a href="docs" class="hlink">here</a>.</p><p>The Game Rules python library is not documented in this page but can be expanded.</p></span>');

                h0desc.append(h0p1, h0p2);
                h1desc.append(h1p1, h1p2, h1p3);
                h2desc.append(h2p1, h2p2, h2p3);
                h3desc.append(h3p1, h3p2);
                content.append(h0, h0desc, h1, h1desc, h2, h2desc, h3, h3desc, h4, h4desc);

                verification.append(content);
                help_rules_modal.append(verification);
                tabContent.append($compile(help_rules_modal)($scope));
            }

            $scope.switchTabs = function($event) {
                target = $event.target;
                selector =  target.value;

                var state = $(selector).css("display");
                var tabs = ["rule-functions", "rule-variables", "rule-metadata"];

                if (state == 'none') {
                    $(selector).css("display", "block");
                    tabs.forEach(function (el) {
                        elid = "#" + el;
                        elclass = "." + el + "-button";
                        if (elid != selector) {
                            $(elid).css("display", "none" );
                            $(elclass).css("background-color", "gray" );
                        }
                        else {
                            $(elclass).css("background-color", "#07a" );
                        }
                    });
                }
            }

            // --------- WHEN EDITOR ---------

            var whenCodeMirror = CodeMirror.fromTextArea(document.getElementById("rule-when"), {
                lineNumbers: true, styleActiveLine: true, autohint: true
              });

              whenCodeMirror.setOption("theme", "mdn-like");
              whenCodeMirror.on("change", function (cm, event) {
                  $("#typing-help").hide();
              });

              whenCodeMirror.on("focus", function (cm, event) {
                  // display the module help toolbox
                $("#rule-then-help").hide();
                $("#rule-when-help").show();
                $("#func-info-then").hide();
              });
              
              

              whenCodeMirror.on("keyup", function (cm, event) {
                var liblist = angular.copy($scope.libraries);
                var funclist = angular.copy($scope.functions);
                liblist = liblist.filter(el => el["moduleId"]);
                liblist = liblist.map(value => value["name"]);

                if ( !(event.keyCode == 37 || event.keyCode == 38 || event.keyCode == 39 || event.keyCode == 40)) { 
                    // checks if arrow keys were not pressed - causes errors
                    // this simple check solves errors with selection on autocomplete
                    var line = whenCodeMirror.doc.getCursor().line;
                    ch = whenCodeMirror.doc.getCursor().ch;
                    textBefore = whenCodeMirror.doc.getLine(line).substr(0, ch);
                    
                    if (textBefore.match(/GC\..*$/)) {
                        console.log("1");
                        if (textBefore.match(/GC\.[A-Za-z]*$/)) {
                            console.log("2");
                            $scope.$apply($scope.curr_functions_when = {});
                            $(".func-list-item").css("font-weight", "normal");
                            $("#func-info-when").hide();
                            $scope.$apply($scope.active_func_when = {});
                            $scope.$apply($scope.curr_functions_when = {});
                            $scope.$apply($scope.curr_libraries_when = $scope.libraries);
                            gccomp = textBefore.split(".");
                            module_typed = gccomp[gccomp.length-1];
                            if (module_typed != "") {
                                $scope.$apply($scope.curr_libraries_when = $scope.curr_libraries_when.filter(el => el["moduleId"] != null && el["name"].startsWith(module_typed)));
                            }
                            else {
                                $scope.$apply($scope.curr_libraries_when = $scope.curr_libraries_when.filter(el => el["moduleId"] != null));
                            }

                            var options = {
                                hint: function(whenCodeMirror) {
                                    var cur = whenCodeMirror.getCursor();
                                    var curLine = whenCodeMirror.getLine(cur.line);
                                    var start = cur.ch;
                                    var end = start;
                                    while (end < curLine.length && /[\w$]/.test(curLine.charAt(end))) ++end;
                                    while (start && /[\w$]/.test(curLine.charAt(start - 1))) --start;
                                    var curWord = start !== end && curLine.slice(start, end);
                                    var regex = new RegExp('^' + curWord, 'i');
                                    return {
                                        list: (!curWord ? [] : liblist.filter(function(item) {
                                            return item.match(regex);
                                        })).sort(),
                                        from: CodeMirror.Pos(cur.line, start),
                                        to: CodeMirror.Pos(cur.line, end)
                                    }
                                }
                            , completeSingle: false};
                            cm.showHint(options);
                        }

                        if (textBefore.match(/GC\.[A-Za-z]+\.[A-Za-z]*\(*$/)) {
                            console.log("3");
                            $scope.$apply($scope.curr_functions_when = {});
                            $(".func-list-item").css("font-weight", "normal");
                            $("#func-info-when").hide();
                            $scope.$apply($scope.curr_functions_when = {});
                            $scope.$apply($scope.curr_libraries_when = {});
                            $scope.$apply($scope.curr_functions_when = $scope.functions);
                            gcfunc = textBefore.split(".");
                            function_typed = gcfunc[gcfunc.length-1];
                            func_module = gcfunc[gcfunc.length-2];
                            //console.log(function_typed);

                            if (function_typed != "") {
                                if (function_typed.endsWith("(")) {
                                    $scope.$apply($scope.curr_functions_when = $scope.curr_functions_when.filter(el => el["moduleId"] != null && el["name"] == func_module && el["keyword"].startsWith(function_typed.replace('(',''))));
                                }
                                else {
                                    $scope.$apply($scope.curr_functions_when = $scope.curr_functions_when.filter(el => el["moduleId"] != null && el["name"] == func_module && el["keyword"].startsWith(function_typed)));
                                }

                                
                                if (function_typed.endsWith("(") && $scope.curr_functions_when.length == 1) {
                                    $scope.$apply($scope.active_func_when = $scope.curr_functions_when[0]);
                                    $("#func-info-when").show();
                                    $(".func-list-item").css("font-weight", "bold");
                                    $(".func-list-item").css("color", "#905");
                                }
                            }
                            else {
                                $scope.$apply($scope.curr_functions_when = $scope.curr_functions_when.filter(el => el["moduleId"] != null && el["name"] == func_module));
                            }

                            var funclist = angular.copy($scope.functions);
                            funclist = funclist.filter(el => el["moduleId"] != null && el["name"] == func_module);
                            funclist = funclist.map(value => value["keyword"]);

                            var options = {
                                hint: function(whenCodeMirror) {
                                    var cur = whenCodeMirror.getCursor();
                                    var curLine = whenCodeMirror.getLine(cur.line);
                                    var start = cur.ch;
                                    var end = start;
                                    while (end < curLine.length && /[\w$]/.test(curLine.charAt(end))) ++end;
                                    while (start && /[\w$]/.test(curLine.charAt(start - 1))) --start;
                                    var curWord = start !== end && curLine.slice(start, end);
                                    var regex = new RegExp('^' + curWord, 'i');
                                    return {
                                        list: (!curWord ? [] : funclist.filter(function(item) {
                                            return item.match(regex);
                                        })).sort(),
                                        from: CodeMirror.Pos(cur.line, start),
                                        to: CodeMirror.Pos(cur.line, end)
                                    }
                                }
                            , completeSingle: false};
                            cm.showHint(options);
                        }

                        if (textBefore.match(/GC\.[A-Za-z]+\.[A-Za-z]+\([0-9A-Za-z,"'_ ]*\).*$/)) {
                            comp = textBefore.split("GC");
                            info = comp[comp.length-1];
                            args_part = info.split("(");
                            args = args_part[args_part.length - 1].split(")");
                            func_args = args[0].split(",");

                            new_args = [];
                            
                            func_args.forEach( function (el) {
                                arg = el.replace(/^\"+|\"+$/g, '');
                                argn = arg.replace(/^\'+|\'+$/g, '');
                                new_args.push(argn);
                            });
                            console.log(new_args);
                            console.log("---------------");
                            $scope.$apply($scope.curr_args = new_args);
                            console.log("~~~~~~~");
                            console.log($scope.curr_args);
                            console.log("~~~~~~~");
                        }

                        if (!textBefore.match(/GC\.*[A-Za-z]*\.*[A-Za-z]*\(*[A-Za-z,]*\)*.*$/)) {
                            $scope.$apply($scope.curr_functions_when = {});
                            $("#func-info-when").hide();
                            $(".func-list-item").css("font-weight", "normal");
                        }

                    }
                    else {
                        // Present gamerules language suggestions
                        gr_funclist = angular.copy($scope.grfunctions);
                        gr_funclist = gr_funclist.map(value => value["keyword"]);

                        $scope.$apply($scope.curr_functions_then = {});
                        $(".func-list-item").css("font-weight", "normal");
                        $("#func-info-then").hide();
                        $scope.$apply($scope.active_func_when = {});
                        $scope.$apply($scope.curr_functions_when = $scope.grfunctions);
                        
                        function_typed = textBefore.split(" "); 
                        typed = function_typed[function_typed.length-1];

                        
                        if (typed != "") {
                            if (typed.match(/[A-Za-z_]+.*$/)) {
                                if (typed.match(/[A-Za-z_]+\(.*$/)) {
                                    
                                    if (typed.match(/[A-Za-z_]+\([A-Za-z0-9, _"']*\)$/)) {
                                        $scope.$apply($scope.active_func_when = {});
                                        $("#func-info-when").hide();
                                    }
                                    else if (typed.match(/[A-Za-z_]+\([A-Za-z0-9, _"']*$/)) {
                                        typed_func = typed.split("(");
                                        console.log($scope.curr_functions_when);
                                        $scope.$apply($scope.curr_functions_when = $scope.curr_functions_when.filter(el => el.keyword.startsWith(typed_func[0])));
                                        $scope.$apply($scope.active_func_when = $scope.curr_functions_when[0]);
                                        $("#func-info-when").show();
                                        $(".gcfunc-list-item").css("font-weight", "bold");
                                        $(".gcfunc-list-item").css("color", "#905");
                                    }
                                }
                                else {
                                    $scope.$apply($scope.curr_functions_when = $scope.curr_functions_when.filter(el => el.keyword.startsWith(typed)));
                                    $scope.$apply($scope.active_func_when = {});
                                }
                            }
                        }
                        else {
                            $scope.$apply($scope.curr_functions_then = $scope.grfunctions);
                            $scope.$apply($scope.active_func_then = {});
                            $("#func-info-then").hide();
                        }


                        $scope.$apply($scope.curr_libraries_when = {});
                        //$scope.$apply($scope.curr_functions_when = $scope.grfunctions);

                        var grfunclist = angular.copy($scope.grfunctions);
                        grfunclist = grfunclist.map(value => value["keyword"]);
                        
                        var options = {
                            hint: function(whenCodeMirror) {
                                var cur = whenCodeMirror.getCursor();
                                var curLine = whenCodeMirror.getLine(cur.line);
                                var start = cur.ch;
                                var end = start;
                                while (end < curLine.length && /[\w$]/.test(curLine.charAt(end))) ++end;
                                while (start && /[\w$]/.test(curLine.charAt(start - 1))) --start;
                                var curWord = start !== end && curLine.slice(start, end);
                                var regex = new RegExp('^' + curWord, 'i');
                                return {
                                    list: (!curWord ? [] : grfunclist.filter(function(item) {
                                        return item.match(regex);
                                    })).sort(),
                                    from: CodeMirror.Pos(cur.line, start),
                                    to: CodeMirror.Pos(cur.line, end)
                                }
                            }
                        , completeSingle: false};
                        cm.showHint(options);
                        
                    }
                }
            });

            // --------- THEN EDITOR ---------
            var thenCodeMirror = CodeMirror.fromTextArea(document.getElementById("rule-then"), {
                lineNumbers: true, styleActiveLine: true, autohint: true
            });

            thenCodeMirror.setOption("theme", "mdn-like");

            thenCodeMirror.on("change", function (cm, event) {
                $("#typing-help").hide();
            });

            thenCodeMirror.on("focus", function (cm, event) {
                // display the func help toolbox
                $("#rule-when-help").hide();
                $("#rule-then-help").show();
                $("#func-info-when").hide();
            });

            thenCodeMirror.on("keyup", function (cm, event) {
                var line = thenCodeMirror.doc.getCursor().line;
                ch = thenCodeMirror.doc.getCursor().ch;
                textBefore = thenCodeMirror.doc.getLine(line).substr(0, ch);

                if ( !(event.keyCode == 37 || event.keyCode == 38 || event.keyCode == 39 || event.keyCode == 40)) { 
                    gr_funclist = angular.copy($scope.grfunctions);
                    gr_funclist = gr_funclist.map(value => value["keyword"]);

                    $scope.$apply($scope.curr_functions_then = {});
                    $(".func-list-item").css("font-weight", "normal");
                    $("#func-info-when").hide();
                    $scope.$apply($scope.active_func_then = {});
                    $scope.$apply($scope.curr_functions_then = $scope.grfunctions);
                    
                    function_typed = textBefore.split(" "); 
                    typed = function_typed[function_typed.length-1];

                    if (typed != "") {
                        if (typed.match(/[A-Za-z_]+.*$/)) {
                            if (typed.match(/[A-Za-z_]+\(.*$/)) {
                                
                                if (typed.match(/[A-Za-z_]+\([A-Za-z0-9, _"']*\)$/)) {
                                    $scope.$apply($scope.active_func_then = {});
                                }
                                else if (typed.match(/[A-Za-z_]+\([A-Za-z0-9, _"']*$/)) {
                                    typed_func = typed.split("(");
                                    $scope.$apply($scope.curr_functions_then = $scope.curr_functions_then.filter(el => el.keyword.startsWith(typed_func[0])));
                                    $scope.$apply($scope.active_func_then = $scope.curr_functions_then[0]);
                                    $("#func-info-then").show();
                                    $(".gcfunc-list-item").css("font-weight", "bold");
                                    $(".gcfunc-list-item").css("color", "#905");
                                }
                            }
                            else {
                                $scope.$apply($scope.curr_functions_then = $scope.curr_functions_then.filter(el => el.keyword.startsWith(typed)));
                                $scope.$apply($scope.active_func_then = {});
                            }
                        }
                    }
                    else {
                        $scope.$apply($scope.curr_functions_then = $scope.grfunctions);
                        $scope.$apply($scope.active_func_then = {});
                        $("#func-info-then").hide();
                    }

                    var options = {
                        hint: function(thenCodeMirror) {
                            var cur = thenCodeMirror.getCursor();
                            var curLine = thenCodeMirror.getLine(cur.line);
                            var start = cur.ch;
                            var end = start;
                            while (end < curLine.length && /[\w$]/.test(curLine.charAt(end))) ++end;
                            while (start && /[\w$]/.test(curLine.charAt(start - 1))) --start;
                            var curWord = start !== end && curLine.slice(start, end);
                            var regex = new RegExp('^' + curWord, 'i');
                            return {
                                list: (!curWord ? [] : gr_funclist.filter(function(item) {
                                    return item.match(regex);
                                })).sort(),
                                from: CodeMirror.Pos(cur.line, start),
                                to: CodeMirror.Pos(cur.line, end)
                            }
                        }
                    , completeSingle: false};
                    cm.showHint(options);
                }
            });


            $scope.submitRule = function () { // TO DO check if this works
                // get new vals and update scope
                $scope.rule.name = $('#rule-name').val();
                $scope.rule.description = $('#rule-description').val();

                $scope.rule.when = $('#rule-when').val();
                $scope.rule.then = $('#rule-then').val();

                if (add) {
                    $scope.rules[pos].rules.unshift($scope.rule);
                    $smartboards.request('settings', 'ruleEditorActions', {course: $scope.course, submitRule: true, rule: $scope.rule, add: true, index: $scope.index}, function(data, err) {
                        if (err) {
                            giveMessage(err.description);
                            return;
                        }
                        $scope.listRules();
                    });
                }
                else {
                    $smartboards.request('settings', 'ruleEditorActions', {course: $scope.course, submitRule: true, rule: $scope.rule, index: $scope.index}, function(data, err) {
                        if (err) {
                            giveMessage(err.description);
                            return;
                        }
                        $scope.listRules();
                    });
                }
            }

            $scope.deleteRuleTag = function (tag) {
                function removeElement(array, elem) {
                    var index = array.indexOf(elem);
                    if (index > -1) {
                        array.splice(index, 1);
                    }
                }
                removeElement($scope.rule.tags, tag);
            }
            

            // tag autocomplete configuration
            $(function() {
                $("#rule-tags").autocomplete({
                source: $scope.tags.map(tag => tag.name),
                appendTo: '.tab-content',
                delay: 0,
                select: function( event, ui ) { // selecting a value from the list
                    newtag = ui.item.value;
                    $scope.$apply($scope.newTag(newtag));
                    $(this).val('');
                    return false;
                }
                });

                $("#rule-tags").keydown(function (event) {
                    newtag = $("#rule-tags").val();
                    if (event.which === 13) {
                        old_nr_tags = $scope.tags.length;
                        $scope.$apply(new_name = $scope.newTag(newtag));
                        $(this).val('');
                        new_nr_tags = $scope.tags.length;

                        if (new_nr_tags > old_nr_tags) {

                            new_tag_id = "#tag-named-" + new_name;
                            $(new_tag_id + "> .color-picker-tags").show();

                            const color_tag = $(new_tag_id + ".color-picker-tags");
                            const active_tag = $(new_tag_id + ".color-picker-tags")[0];
                            const pickr = new Pickr({
                                el: active_tag,
                                useAsButton: true,
                                default: '#898989',
                                theme: 'monolith',
                                components: {
                                    hue: true,
                                    interaction: { input: true, save: true }
                                }
                            }).on('init', pickr => {
                                pickr.show();
                                color = pickr.getSelectedColor().toHEXA().toString(0);
                                color_tag.css("background-color", color);
                            }).on('save', color => {
                                color = color.toHEXA().toString(0);
                                color_tag.css("background-color", color);
                                const mod_tag = $scope.tags.filter(tag => tag.name === new_name)[0];
                                mod_tag.color = color
                                $scope.$apply($scope);
                                pickr.hide();
                            }).on('change', color => {
                                color = color.toHEXA().toString(0);
                                color_tag.css("background-color", color);
                            })
                        }
                    }
                });
            } );


            $scope.newTag = function (newtag) { // TO DO fix color thing
                tag = {};
                tag.name = newtag.toLowerCase();
                tag.color = "#8a8a8a";
                
                tag_names = $scope.rule.tags.map(tag => tag.name); // rule tags
                all_tag_names = $scope.tags.map(tag => tag.name); // all system tags

                if (tag.name.includes(',') || tag.name.trim() == "" ){
                    return;
                }
                if (!tag_names.includes(tag.name)) {
                    $scope.rule.tags.push(tag);
                }
                if (!all_tag_names.includes(tag.name)) {
                    $scope.tags.push(tag);
                }
                return tag.name;
            }

            $scope.previewRule = function() {
                // get code from boxes
                var test_rule = {};

                test_rule.name = $('#rule-name').val();
                test_rule.description = $('#rule-description').val();
                test_rule.tags = $scope.rule.tags;
                test_rule.active = true; // set as active, otherwise the rule won't run
                test_rule.when = whenCodeMirror.getValue();
                test_rule.then = thenCodeMirror.getValue();
                request = {"alias" : "Preview Rule"};

                $smartboards.request('settings', 'ruleEditorActions', {course: $scope.course, previewRule: true, rule: test_rule}, function(data, err) {
                    if (err) {
                        giveMessage(err.description);
                        console.log("if");

                    }
                    else {
                        console.log("else");
                        console.log(data);

                        if (data.error != "" && data.error != null) { // error occurred
                            console.log("err");
                            request["response_array"] = null;
                            request["response"] = data.error;
                            request["preview_type"] = "rule";
                        }
                        else {
                            console.log("good");
                            console.log(data.result);
                            request["response_array"] = data.result;
                            request["response"] = null;
                            request["preview_type"] = "rule";
                        }
                        
                        $scope.preview_history.push(request);
                        console.log($scope.preview_history);
                    }
                });
            }


        }

        $scope.listRules();
    });
});
