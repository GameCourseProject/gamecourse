
//definie o que esta nas settings de um curso
app.controller('CourseSettings', function ($scope, $state, $compile, $smartboards) {
    changeTitle('Course Settings', 1); //muda no breadcrumb

    var refreshTabsBind = $scope.$on('refreshTabs', function () {
        $smartboards.request('settings', 'courseTabs', { course: $scope.course }, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }

            var tabs = $('#settings > .tabs > .tabs-container');
            tabs.html('');
            tabs.append($compile('<li><a ui-sref="course.settings.global">This Course</a></li>')($scope));
            tabs.append($compile('<li><a ui-sref="course.settings.roles">Roles</a></li>')($scope));
            tabs.append($compile('<li><a ui-sref="course.settings.modules">Modules</a></li>')($scope));
            tabs.append($compile('<li><a ui-sref="course.settings.rules">Rules</a></li>')($scope));
            for (var i = 0; i < data.length; ++i)
                tabs.append($compile(buildTabs(data[i], tabs, $smartboards, $scope))($scope));
            // tabs.append($compile('<li><a ui-sref="course.settings.about">About</a></li>')($scope));

            addActiveLinks($state.current.name);
            updateTabTitle($state.current.name, $state.params);
        });
    });
    
    $smartboards.request('settings', 'getStyleFile', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }
        $('#css-file').remove();
        if (data.url && data.styleFile != '') {
            $('head').append('<link id="css-file" rel="stylesheet" type="text/css" href="' + data.url + '">');
        }
    });

    $scope.$emit('refreshTabs');
    $scope.$on('$destroy', refreshTabsBind);
});


app.controller('CourseSettingsGlobal', function ($scope, $element, $smartboards, $compile, $timeout) {

    infoSection = $("<div id='configPage'></div>");
    $element.append(infoSection);

    

    $scope.$on('$stateChangeSuccess', function (e) {
        $smartboards.request('settings', 'getStyleFile', { course: $scope.course }, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            $scope.styleFile = data.styleFile;
            $('#css-file').remove();
            if (data.url && data.styleFile != '') {
                $('head').append('<link id="css-file" rel="stylesheet" type="text/css" href="' + data.url + '">');
            }
            $scope.showGlobalPage(e.targetScope.navigation);
        });
    });

    $scope.deleteRecord = function(){
        $smartboards.request('settings', 'deleteTableEntry', { course: $scope.course, table: $scope.table, rowData: $scope.rowData}, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }

            var table = $('#database').DataTable();
            var index = table.rows().eq(0).filter( function (rowIdx) {
                return table.cell( rowIdx, 0).data() === $scope.rowData['id'] ? true : false;
            });

            table.row(index)
                 .remove()
                 .draw(false);
        });
    };

    $scope.editRecord = function(row){
        $scope.rowData = row;
        $scope.newData = Object.assign({}, $scope.rowData);
    }

    $scope.submitRecord = function(update){
        $smartboards.request('settings', 'submitTableEntry', { course: $scope.course, table: $scope.table, update: update, rowData: $scope.rowData, newData: $scope.newData}, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            var table = $('#database').DataTable();
            var newRow = [];
            var dataSource = data.newRecord;
            var id = dataSource['id'];

            if(update) {   
                var index = table.rows().eq(0).filter( function (rowIdx) {
                    return table.cell( rowIdx, 0).data() === $scope.newData['id'] ? true : false;
                });   
            }
            
            $scope.newRecord[id] = Object.assign({}, dataSource);

            for (let column in $scope.columns){
                if(!(["user", "course"].includes($scope.columns[column])))
                    newRow.push(dataSource[$scope.columns[column]]);
            }

            var editButton = '<div id="new-edit-' + id + '" class="icon edit_icon" title="Edit" value="#edit-row" ng-click="editRecord(newRecord[' + id + '])" onclick="openModal(this)"></div>';
            var deleteButton = '<div id="new-delete-' + id + '" class="icon delete_icon" title="Delete" value="#delete-verification" ng-click="editRecord(newRecord[' + id + '])" onclick="openModal(this)"></div>';

            newRow.push(editButton);
            newRow.push(deleteButton);

            if(update) {
                // update row
                table.row(index).data(newRow).draw(false);
            }
            else {
                // create new row
                table.row.add(newRow).draw().show().draw(false);
            }

            var newEdit = document.getElementById("new-edit-" + id);
            var newDelete = document.getElementById("new-delete-" + id);
            $compile(newEdit)($scope);
            $compile(newDelete)($scope);

            $scope.newData = {};
        });
    }
    
    $scope.showDatabase = function(table) {
        infoSection.empty();
        $scope.table = table;
        $smartboards.request('settings', 'getTableData', { course: $scope.course, table: $scope.table }, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }

            var courseData = createSection(infoSection, $scope.table);
            var breadcrum = $("<div id='page_history'></div>");
            breadcrum.append($("<div class='go_back icon' ng-click='showGlobalPage()'></div>"));
            breadcrum.append($("<span class='clickable' ng-click='showGlobalPage()'> This Course </span>"));
            infoSection.prepend($compile(breadcrum)($scope));

            if (data.entries.length == 0) {
                courseData.append($compile("<div class='error_box'><div id='empty_search' class='error_msg'>Table {{table}} is empty</div></div>")($scope));
                return;
            }

            courseData.append($compile("<div class='action-buttons database'><div class='icon add_icon' value='#add-row' ng-click='editRecord({})' onclick='openModal(this)'></div></div>")($scope));

            $scope.columns = data.columns;
            $scope.entries = data.entries;
            $scope.newData = {};
            $scope.newRecord = [];

            var dataTable = $('<div class="data-table" ></div>');
            var table = $('<table id="database" class="order-column" style="width:100%"></table>');
            rowHeader = $('<thead></thead>');
            rowHeader.append("<tr><th ng-repeat='column in columns' ng-if=\"column !== 'user' && column !== 'course'\">{{column}}</th><th></th><th></th></tr>");
            rowContent = $("<tr id='table-content' ng-repeat='(key, value) in entries'></tr>");
            rowContent.append("<td ng-repeat='column in columns' ng-if=\"column !== 'user' && column !== 'course'\">{{value[column]}}</td>");
            rowContent.append('<td class="action-column"><div class="icon edit_icon" title="Edit" value="#edit-row" ng-click="editRecord(value)" onclick="openModal(this)"></div></td>');
            rowContent.append('<td class="action-column"><div class="icon delete_icon" title="Delete" value="#delete-verification" ng-click="editRecord(value)" onclick="openModal(this)"></div></td>');
            table.append(rowHeader);
            table.append(rowContent);
            dataTable.append(table);
            courseData.append($compile(dataTable)($scope));
            
            setTimeout(function () {
                $('#database thead tr').clone(true).appendTo( '#database thead' );
                $('#database thead tr:eq(1) th:lt(-2)').each( function (i) {
                    var title = $(this).text();
                    $(this).html( '<input type="text" class="database_search" placeholder="Search '+ title +'" />' );
             
                    $( 'input', this ).on( 'keyup change', function () {
                        if ( table.column(i).search() !== this.value ) {
                            table
                                .column(i)
                                .search( this.value )
                                .draw();
                        }
                    } );
                } );
                var table = $('#database').DataTable( {
                    "orderCellsTop": true,
                    "fixedHeader": true,
                    "pagingType": "full_numbers",
                    "columnDefs": [{
                        "targets": [-1, -2],     //remove sorting and searching on action columns        
                        "orderable": false,
                        "searchable": false 
                        
                    }]
                });

              }, 500);
            
            //delete verification modal
            modal = $("<div class='modal' id='delete-verification'></div>");
            verification = $("<div class='verification modal_content'></div>");
            verification.append( $('<button class="close_btn icon" value="#delete-verification" onclick="closeModal(this)"></button>'));
            verification.append( $('<div class="warning">Are you sure you want to delete this row?</div>'));
            verification.append( $('<div class="target"></div>'));
            verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#delete-verification" onclick="closeModal(this)">Cancel</button><button value="#delete-verification" class="continue" ng-click="deleteRecord()" onclick="closeModal(this)">Delete</button></div>'))
            modal.append(verification);
            courseData.append($compile(modal)($scope));

            //edit modal
            editModal = $("<div class='modal' id='edit-row'></div>");
            editVerification = $("<div class='verification modal_content'></div>");
            editVerification.append($('<button class="close_btn icon" value="#edit-row" onclick="closeModal(this)"></button>'));
            editVerification.append($('<div class="title" id="open_item_action"></div>'));
            content = $('<div class="content">');
            box = $('<div id="new_box" class= "inputs">');
            row_inputs = $('<div class= "row_inputs"></div>');
            details = $('<div class="details full config_item"></div>');
            details.append($('<div class="half" ng-repeat="column in columns" ng-if="column !== \'id\' && column !== \'course\' && column !== \'user\' && column !== \'name\'"><div class="container"><input type="text" class="form__input" ng-init="newData[column] = rowData[column]" ng-model="newData[column]" value="{{column}}" id="{{column}}" /><label for="{{column}}" class="form__label">{{column}}</label></div></div>'))
            row_inputs.append(details);
            box.append(row_inputs);
            content.append(box);
            content.append($('<button class="cancel" value="#edit-row" onclick="closeModal(this)" > Cancel </button>'));
            content.append($('<button class="save_btn" value="#edit-row" onclick="closeModal(this)" ng-click="submitRecord(true)"> Save </button>'));
            editVerification.append(content);
            editModal.append(editVerification);
            courseData.append($compile(editModal)($scope));

            //add modal
            addModal = $("<div class='modal' id='add-row'></div>");
            addVerification = $("<div class='verification modal_content'></div>");
            addVerification.append($('<button class="close_btn icon" value="#add-row" onclick="closeModal(this)"></button>'));
            addVerification.append($('<div class="title" id="open_item_action"></div>'));
            addContent = $('<div class="content">');
            addBox = $('<div id="new_box" class= "inputs">');
            addRowInputs = $('<div class= "row_inputs"></div>');
            addDetails = $('<div class="details full config_item"></div>');
            addDetails.append($('<div class="half" ng-repeat="column in columns" ng-if="column !== \'id\' && column !== \'course\' && column !== \'user\' && column !== \'name\'"><div class="container"><input type="text" class="form__input" ng-model="newData[column]" value="{{column}}" id="{{column}}" /><label for="{{column}}" class="form__label">{{column}}</label></div></div>'))
            addRowInputs.append(addDetails);
            addBox.append(addRowInputs);
            addContent.append(addBox);
            addContent.append($('<button class="cancel" value="#add-row" onclick="closeModal(this)" > Cancel </button>'));
            addContent.append($('<button class="save_btn" value="#add-row" onclick="closeModal(this)" ng-click="submitRecord(false)"> Save </button>'));
            addVerification.append(addContent);
            addModal.append(addVerification);
            courseData.append($compile(addModal)($scope));

            

        });
    };

    $scope.showGlobalPage = function (nav) {
        infoSection.empty();
        $smartboards.request('settings', 'courseGlobal', { course: $scope.course }, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }

            $scope.data = data;
            $scope.navigation = nav;
            var courseInfo = createSection(infoSection, 'Info');

            databaseModal = $("<div class='modal' id='edit-db'></div>");
            databaseVerification = $("<div class='verification modal_content'></div>");
            databaseVerification.append($('<button class="close_btn icon" value="#edit-db" onclick="closeModal(this)"></button>'));
            databaseVerification.append($('<div class="warning">Please select a table:</div>'));
            databaseVerification.append($('<div class="confirmation_btns"><button onclick="closeModal(this)" ng-click="showDatabase(\'award\')">Award</button><button onclick="closeModal(this)" ng-click="showDatabase(\'participation\')">Participation</button></div>'));
            databaseVerification.append($('<div class="confirmation_btns"></div>'));
            databaseModal.append(databaseVerification);
            $compile(databaseModal)($scope);
            courseInfo.append(databaseModal);

            var infoDiv = $('<div>');
            infoDiv.append('<p><b>Course Name: </b>' + $scope.data.name + '</p>');
            infoDiv.append('<p><b>Course ID: </b>' + $scope.course + '</p>');
            infoDiv.append('<p><b>Active Users: </b>' + $scope.data.activeUsers + '</p>');
            infoDiv.append('<p><b>Awards: </b>' + $scope.data.awards + '</p>');
            infoDiv.append('<p><b>Participations: </b>' + $scope.data.participations + '</p>');
            courseInfo.append($compile(infoDiv)($scope));

            var panicDiv = $('<div>');
            panicDiv.append('<button id="edit_db_button" class="button" value="#edit-db" onclick="openModal(this)">View Database</button>');
            courseInfo.append(panicDiv);

            if ($scope.navigation.length > 0) {

                var navigation = createSection(infoSection, 'Navigation');

                navigation.attr('id', 'navigation-config')
                navigationSection = $('<div class="data-table"></div>');
                tableNavigation = $('<table id="nav-table"></table>');
                rowHeaderNavigation = $("<tr></tr>");
                rowHeaderNavigation.append($("<th>Position</th>"));
                rowHeaderNavigation.append($("<th>Page</th>"));
                rowHeaderNavigation.append($("<th class='action-column'></th>")); // move up
                rowHeaderNavigation.append($("<th class='action-column'></th>")); // move down

                rowContentNavigation = $("<tr ng-repeat='(i, nav) in navigation'> ></tr>");
                attrs = ["seqId", "text"];
                jQuery.each(attrs, function (index) {
                    stg = "nav." + attrs[index];
                    rowContentNavigation.append($('<td>{{' + stg + '}}</td>'));
                });
                rowContentNavigation.append('<td class="action-column"><div class="icon up_icon" title="Move up" ng-click="moveUp(this)"></div></td>');
                rowContentNavigation.append('<td class="action-column"><div class="icon down_icon" title="Move down" ng-click="moveDown(this)"></div></td>');

                //append table
                tableNavigation.append(rowHeaderNavigation);
                tableNavigation.append(rowContentNavigation);
                navigationSection.append(tableNavigation);
                navigation.append(navigationSection);
                $compile(navigation)($scope);
            }

            if ($scope.data.viewsModuleEnabled) {
                var styling = createSection(infoSection, 'Styling');

                if ($scope.styleFile === false) {
                    $scope.content = '';
                } else {
                    $scope.content = $scope.styleFile;
                }

                var newDiv = $('<div>');
                newDiv.append('<button id="create_style_button" class="button" ng-click="createStyleFile()">Create Style File</button>');
                styling.append(newDiv);

                var editDiv = $('<div id="edit-css-file"></div>');
                action_button = $("<div id='save-change-css' class='action-buttons' style='width:140px;'></div>");
                action_button.append($('<button class="button" title="Save" ng-click="saveStyleFile()"> Save Changes </button>'));


                editDiv.append('<textarea type="text" id="style-file" class="form__input ng-pristine ng-valid ng-empty ng-touched" placeholder="Write your style here...">' + $scope.content + '</textarea>')

                styling.append(action_button);
                styling.append(editDiv);

                var cssCodeMirror = CodeMirror.fromTextArea(document.getElementById("style-file"), {
                    lineNumbers: true, styleActiveLine: true, mode: "css", value: $scope.content, autohint: true,
                    lineWrapping: true, theme: "mdn-like"
                });

                cssCodeMirror.on("keyup", function (cm, event) {
                    cm.showHint(CodeMirror.hint.css);
                });

                $scope.cssCodeMirror = cssCodeMirror;

                if ($scope.styleFile === false) {
                    $('#create_style_button').show();
                    $('#edit-css-file').hide();
                    $('#save-change-css').hide();
                } else {
                    $('#create_style_button').hide();
                    $('#edit-css-file').show();
                    $('#save-change-css').show();
                }



                $compile(styling)($scope);


            }


        });
    };

    $scope.createStyleFile = function () {
        $smartboards.request('settings', 'createStyleFile', { course: $scope.course }, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            if (data.url) {
                $('#create_style_button').hide();
                $('#edit-css-file').show();
                $('#save-change-css').show();
            }
        });
    }

    $scope.saveStyleFile = function () {
        $scope.styleFile = $scope.cssCodeMirror.getValue();
        $smartboards.request('settings', 'updateStyleFile', { course: $scope.course, content: $scope.styleFile }, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            if (data.url !== 0) {
                giveMessage('Saved!');
                $('#css-file').remove();
                if ($scope.styleFile != '') {
                    $('head').append('<link id="css-file" rel="stylesheet" type="text/css" href="' + data.url + '">');
                }
            } else {
                giveMessage('Something went wrong!');
            }
        });
    }

    $scope.moveUp = function (row) {
        var item = row.nav;
        var index = parseInt(row.$index);
        var newIdx = index - 1;
        var changed = false;


        if (newIdx >= 0 && newIdx <= $scope.navigation.length - 1) {
            $scope.navigation.splice(newIdx, 2, item, $scope.navigation[newIdx]);
            changed = true;
        }

        if (changed) {
            $smartboards.request('settings', 'saveNewNavigationOrder', { course: $scope.course, nav: $scope.navigation }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
            });
            window.location.reload();
        }

    }

    $scope.moveDown = function (row) {
        var item = row.nav;
        var index = parseInt(row.$index);
        var newIdx = index + 1;
        var changed = false;

        if (newIdx >= 0 && newIdx <= $scope.navigation.length - 1) {
            $scope.navigation.splice(index, 2, $scope.navigation[newIdx], item);
            changed = true;
        }

        if (changed) {
            $smartboards.request('settings', 'saveNewNavigationOrder', { course: $scope.course, nav: $scope.navigation }, function (data, err) {
                if (err) {
                    giveMessage(err.description);
                    return;
                }
            });
            window.location.reload();
        }

    }
});

app.controller('CourseSettingsModules', function ($scope, $element, $smartboards, $compile) {

    $scope.openModule = function (module) {
        $scope.module_open = {};
        Object.assign($scope.module_open, module);

        $scope.needsToBeSaved = function () {
            if ($scope.module_open.enabled != module.enabled) {
                return true
            } else {
                return false
            }
        }

        $scope.saveModule = function () {
            $smartboards.request('settings', 'saveCourseModule', { course: $scope.course, module: $scope.module_open.id, enabled: $scope.module_open.enabled }, alertUpdate);
        }
    }

    $smartboards.request('settings', 'courseModules', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        $scope.modules = data;
    });


    var tabContent = $($element);
    search = $("<div class='search'> <input type='text' id='seach_input' placeholder='Search..' name='search' ng-model='search' ><button class='magnifying-glass' id='search-btn'></button>  </div>")

    modules = $('<div id="modules"></div>');
    module_card = $('<div class="module_card" ng-repeat="(i, module) in mdls = ( modules | filter: search : [description, name])" value="#view-module" onclick="openModal(this)" ng-if="mdls.length > 0 && module.type === \'GameElement\'" ng-click="openModule(module)"></div> ')
    module_card.append($('<div class="icon" style="background-image: url(/gamecourse/modules/{{module.id}}/icon.svg)"></div>'));
    module_card.append($('<div class="header">{{module.name}}</div>'));
    module_card.append($('<div class="text">{{module.description}}</div>'));
    module_card.append($('<div ng-if="module.enabled != true" class="status disable">Disabled <div class="background"></div></div>'));
    module_card.append($('<div ng-if="module.enabled == true" class="status enable">Enabled <div class="background"></div></div>'));
    modules.append(module_card);
    //error section
    modules.append($("<div class='error_box'><div id='empty_search' class='error_msg' ng-if='mdls.length === 0'>No matches found</div></div>"));

    // Modules Section
    var modulesection = createSection($($element), 'Game Elements');
    tabContent.append($compile(modulesection)($scope));

    tabContent.append($compile(search)($scope));
    tabContent.append($compile(modules)($scope));

    //modal for details of the module
    modal = $("<div class='modal' style='' id='view-module'></div>");
    viewModal = $("<div class='modal_content'></div>");
    viewModal.append($('<button class="close_btn icon" value="#view-module" onclick="closeModal(this)"></button>'));
    header = $('<div class= "header"></div>');
    header.append($('<div class="icon" style="background-image: url(/gamecourse/modules/{{module_open.id}}/icon.svg)"></div>'));
    header.append($('<div class="title">{{module_open.name}} </div>'));
    //se tiver dependencias pendentes tira-se o input e a class slider leva disabled
    header.append($('<div class= "on_off" ng-if="module_open.canBeEnabled == true"><label class="switch"><input id="active" type="checkbox" ng-model="module_open.enabled"><span class="slider round"></span></label></div>'))
    header.append($('<div class= "on_off" ng-if="module_open.canBeEnabled != true"><label class="switch disabled"><input id="active" type="checkbox" ng-model="module_open.enabled" disabled><span class="slider round"></span></label></div>'))



    viewModal.append(header);
    content = $('<div class="content">');
    box = $('<div class="inputs">');
    box.append($('<div class="full" id="description">{{module_open.description}}</div>'))
    dependencies_row = $('<div class= "row"></div>');
    dependencies = $('<div ><span class="label">Dependencies: </span></div>');
    dependencies.append($('<span class="details" ng-repeat="(i, dependency) in module_open.dependencies" ng-if="dependency.enabled == true" ><span style="color: green">{{dependency.id}}</span> | </span>'))
    dependencies.append($('<span class="details" ng-repeat="(i, dependency) in module_open.dependencies" ng-if="dependency.enabled != true" ><span style="color: red">{{dependency.id}}</span> | </span>'))

    dependencies_row.append(dependencies);
    box.append(dependencies_row);
    box.append($('<div class= "row"><div ><span class="label">Version: </span><span class="details">{{module_open.version}}</span></div></div>'))
    box.append($('<div class= "row"><div ><span class="label">Path: </span><span class="details">{{module_open.dir}}</span></div></div>'))
    content.append(box);
    content.append($('<button class="save_btn" ng-click="saveModule()" ng-disabled="!needsToBeSaved()" > Save </button>'))
    content.append($('<button ng-if="module_open.hasConfiguration == true" class="config_btn" ui-sref="course.settings.{{module_open.id}}"> Configure </button>'));
    viewModal.append(content);
    modal.append(viewModal);
    $compile(modal)($scope);
    $element.append(modal);


    // Data Sources Section
    var dsources = createSection($($element), 'Data Sources');
    tabContent.append($compile(dsources)($scope));

    dsmodules = $('<div id="modules"></div>');
    dsmodule_card = $('<div class="module_card" ng-repeat="(i, module) in mdls = ( modules | filter: search : [description, name])" value="#view-module" onclick="openModal(this)" ng-if="mdls.length > 0  && module.type === \'DataSource\'" ng-click="openModule(module)"></div> ')
    dsmodule_card.append($('<div class="icon" style="background-image: url(/gamecourse/modules/{{module.id}}/icon.svg)"></div>'));
    dsmodule_card.append($('<div class="header">{{module.name}}</div>'));
    dsmodule_card.append($('<div class="text">{{module.description}}</div>'));
    dsmodule_card.append($('<div ng-if="module.enabled != true" class="status disable">Disabled <div class="background"></div></div>'));
    dsmodule_card.append($('<div ng-if="module.enabled == true" class="status enable">Enabled <div class="background"></div></div>'));
    dsmodules.append(dsmodule_card);
    //error section
    dsmodules.append($("<div class='error_box'><div id='empty_search' class='error_msg' ng-if='mdls.length === 0'>No matches found</div></div>"));

    tabContent.append($compile(dsmodules)($scope));

    //modal for details of the module
    dsmodal = $("<div class='modal' style='' id='view-module'></div>");
    dsviewModal = $("<div class='modal_content'></div>");
    dsviewModal.append($('<button class="close_btn icon" value="#view-module" onclick="closeModal(this)"></button>'));
    dsheader = $('<div class= "header"></div>');
    dsheader.append($('<div class="icon" style="background-image: url(/gamecourse/modules/{{module_open.id}}/icon.svg)"></div>'));
    dsheader.append($('<div class="title">{{module_open.name}} </div>'));
    //se tiver dependencias pendentes tira-se o input e a class slider leva disabled
    dsheader.append($('<div class= "on_off" ng-if="module_open.canBeEnabled == true"><label class="switch"><input id="active" type="checkbox" ng-model="module_open.enabled"><span class="slider round"></span></label></div>'))
    dsheader.append($('<div class= "on_off" ng-if="module_open.canBeEnabled != true"><label class="switch disabled"><input id="active" type="checkbox" ng-model="module_open.enabled" disabled><span class="slider round"></span></label></div>'))



    dsviewModal.append(dsheader);
    dscontent = $('<div class="content">');
    dsbox = $('<div class="inputs">');
    dsbox.append($('<div class="full" id="description">{{module_open.description}}</div>'))
    dsdependencies_row = $('<div class= "row"></div>');
    dsdependencies = $('<div ><span class="label">Dependencies: </span></div>');
    dsdependencies.append($('<span class="details" ng-repeat="(i, dependency) in module_open.dependencies" ng-if="dependency.enabled == true" ><span style="color: green">{{dependency.id}}</span> | </span>'))
    dsdependencies.append($('<span class="details" ng-repeat="(i, dependency) in module_open.dependencies" ng-if="dependency.enabled != true" ><span style="color: red">{{dependency.id}}</span> | </span>'))

    dsdependencies_row.append(dsdependencies);
    dsbox.append(dsdependencies_row);
    dsbox.append($('<div class= "row"><div ><span class="label">Version: </span><span class="details">{{module_open.version}}</span></div></div>'))
    dsbox.append($('<div class= "row"><div ><span class="label">Path: </span><span class="details">{{module_open.dir}}</span></div></div>'))
    dscontent.append(dsbox);
    dscontent.append($('<button class="save_btn" ng-click="saveModule()" ng-disabled="!needsToBeSaved()" > Save </button>'))
    dscontent.append($('<button ng-if="module_open.hasConfiguration == true" class="config_btn" ui-sref="course.settings.{{module_open.id}}"> Configure </button>'));
    dsviewModal.append(dscontent);
    dsmodal.append(dsviewModal);
    $compile(dsmodal)($scope);
    $element.append(dsmodal);


});

//pagina dos roles
app.controller('CourseRolesSettingsController', function ($scope, $stateParams, $element, $smartboards, $compile, $parse) {

    function buildItem(roleName) {
        var item = $('<li>', { 'class': 'dd-item', 'data-name': roleName });
        //default values can not be dragged or deletes
        if (roleName == "Teacher" || roleName == "Student" || roleName == "Watcher") {
            item.addClass("dd-nodrag");
        }
        item.append($('<div>', { 'class': 'dd-handle icon' }));
        item.append($('<div>', { 'class': 'dd-content', text: roleName }));
        page_section = $('<select>', { 'class': "dd-content", 'ng-model': roleName, 'ng-change': "saveLandingPage('" + roleName + "')" });
        page_section.append($('<option>', { text: 'Default Course Page', 'value': '' }));
        page_options = $scope.data.pages
        jQuery.each(page_options, function (index) {
            page = page_options[index];
            page_section.append($('<option>', { text: page.name, 'value': page.name }));
        });
        item.append(page_section);
        $compile(item)($scope); //esta a bloquear aqui

        //initialize scope value a select right option on dropdown
        role = $scope.data.roles_obj.find(x => x.name == roleName);
        $scope[roleName] = role.landingPage;
        return item;
    }
    // constroi tabela a primira vez
    function buildRoles(parent, roles) {
        var list = $('<ol>', { 'class': 'dd-list first-layer' });
        parent.append(list);
        for (var n = 0; n < roles.length; n++) {
            var role = roles[n];
            var item = buildItem(role.name);
            if (role.children != undefined)
                buildRoles(item, role.children);
            list.append(item);
        }
    }

    //deletes the specified role and all its children
    function deleteRoleAndChildren(hierarchy, roleToDelete = null) {
        for (var i = 0; i < hierarchy.length; i++) {
            if (!roleToDelete || hierarchy[i]['name'] === roleToDelete) {
                if ("children" in hierarchy[i])
                    deleteRoleAndChildren(hierarchy[i]['children']);

                //remove from list of new roles
                $scope.data.newRoles.splice($.inArray(hierarchy[i]['name'], $scope.data.newRoles), 1);
                //remove from relation role - landingpage
                $scope.data.roles_obj = $.grep($scope.data.roles_obj, function (e) {
                    return e.name != hierarchy[i]['name'];
                });

            } else if ("children" in hierarchy[i])
                deleteRoleAndChildren(hierarchy[i]['children'], roleToDelete);
        }
    }

    function createChangeButtonIfNone(anchor, action, list) {
        if (anchor.parent().find('#role-change-button').length == 0) {
            var pageStatus = anchor.parent().find('#role-change-status');
            if (pageStatus.length != 0)
                pageStatus.remove();
            var changePage = $('<button>', { id: 'role-change-button', text: "Save Changes", 'class': 'button', 'disabled': true });
            changePage.click(function () {
                updateChangeButton(true);
                action(list);
            });
            anchor.append(changePage);
        }
    }
    function updateChangeButton(disabled) {
        if ($('#role-change-button').length != 0) {
            var button = $('#role-change-button');
            button.prop('disabled', disabled);
        }
    }

    function saveRoles(list) {
        var newRolesAllInfo = [];

        jQuery.each($scope.data.newRoles, function (index) {
            role_name = $scope.data.newRoles[index];
            role_page = $scope[role_name];
            role_obj = $scope.data.roles_obj.find(x => x.name == role_name);
            role_id = role_obj ? role_obj.id : null;
            roleInfo = {
                name: role_name,
                id: role_id,
                landingPage: role_page
            };
            newRolesAllInfo.push(roleInfo);
        });
        $("#action_completed").empty();
        $("#action_not_completed").empty();
        $smartboards.request('settings', 'roles', { course: $scope.course, updateRoleHierarchy: true, hierarchy: list.nestable('serialize'), roles: newRolesAllInfo }, function (data, err) {
            if (err) {
                $("#action_not_completed").append("Error, please try again!");
                $("#action_not_completed").show().delay(3000).fadeOut();
                return;
            }

            $scope.$emit('refreshTabs');
            $("#action_completed").append("Role hierarchy changed!");
            $("#action_completed").show().delay(3000).fadeOut();
        });
    }
    function addNewRole() {
        $("#new-role").show();
        $("#new-role input:text, #new-role textarea").first().focus();
        $scope.newRole = {};
        $scope.newRole.name = "";
        $("#role_name").val("") //for the add coming from nestable, it does not clean field with scope
        $scope.isReadyToSubmit = function () {
            isValid = function (text) {
                return (text != "" && text != undefined && text != null)
            }
            //does role already exists?
            existing_role = $scope.data.newRoles.includes($scope.newRole.name);

            if (isValid($scope.newRole.name) && !existing_role) {
                return true;
            } else {
                return false;
            }
        }
        function getUserInput() {
            return new Promise((resolve, reject) => {
                //enter no input
                $('#role_name').keydown(function (e) {
                    if (e.keyCode == 13 && $scope.isReadyToSubmit()) {
                        resolve($scope.newRole.name);
                    }
                });
                //click no button
                $("#submit_role").click(function (e) {
                    resolve($scope.newRole.name);
                });
                //close modal
                $("#cancel_role").click(function (e) {
                    reject();
                });
            });
        }

        return getUserInput();
    }
    function redoTable(state) {
        //tenho de copiar por valor tambem
        //para nao alterar os valores/objetos que estao guardados no statemanager
        newHierarchy = Object.values(state['dd']);
        newRoles = state['roles'].slice();
        newRolesObjs = duplicateRolesObjs(state['obj']);
        //update scope
        $scope.data.rolesHierarchy = newHierarchy;
        $scope.data.newRoles = newRoles;
        $scope.data.roles_obj = newRolesObjs;

        dd = $("#roles-config");
        dd.children().last().remove();
        buildRoles(dd, newHierarchy);
        dd.nestable('init');
        updateChangeButton(false);
    }

    $scope.undo = function () {
        state = $scope.state_manager.undo();
        //now it is able to redo
        $("#redo_icon").removeClass("disabled");

        if (!$scope.state_manager.canUndo()) {
            $("#undo_icon").addClass("disabled");
        }
        redoTable(state);
    }
    $scope.redo = function () {
        state = $scope.state_manager.redo();
        //now it is able to undo
        $("#undo_icon").removeClass("disabled");

        if (!$scope.state_manager.canRedo()) {
            $("#redo_icon").addClass("disabled");
        }
        redoTable(state);
    }
    $scope.saveLandingPage = function (role_name) {
        role = $scope.data.roles_obj.find(x => x.name == role_name);
        role.landingPage = $scope[role_name];
    }

    function duplicateRolesObjs(roles) {
        duplicated_roles_obj = [];
        jQuery.each(roles, function (index) {
            role_obj = roles[index];
            new_obj = Object.assign({}, role_obj);
            duplicated_roles_obj.push(new_obj);
        });
        return duplicated_roles_obj;
    }

    function newAction(state) {
        //preciso de "duplicar" objs do roles_obj
        duplicated_roles_obj = duplicateRolesObjs($scope.data.roles_obj);

        $scope.state_manager.newState({ 'dd': state, 'roles': $scope.data.newRoles.slice(), 'obj': duplicated_roles_obj });
        $("#undo_icon").removeClass("disabled");
        $("#redo_icon").addClass("disabled");
        updateChangeButton(false);
    }

    $smartboards.request('settings', 'roles', { course: $scope.course }, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }

        var tabContent = $($element);
        $scope.data = data;
        $scope.data.newRoles = $scope.data.roles;

        // Roles
        var rolesSection = $('<div>', { 'class': 'content', 'id': 'roles_page' });
        tabContent.append(rolesSection);

        //inicio dd
        var dd = $('<div>', { 'class': 'dd', 'id': 'roles-config' });
        dd.append('<div class="header"><span class="role_sections">Roles</span><span class="page_sections">Landing Page</span></div>')

        // add button no fim da lista
        add_new_role = $('<div id="add_role_button_box"><button title="New" ng-click="addRole()" class="add_button icon"></button></div>')
        $compile(add_new_role)($scope)

        //action buttons
        action_buttons = $("<div class='action-buttons'></div>");
        action_buttons.append($("<div id='undo_icon' class='icon undo_icon disabled' title='Undo' ng-click='undo()'></div>"));
        action_buttons.append($("<div id='redo_icon' class='icon redo_icon disabled' title='Redo' ng-click='redo()'></div>"));
        $compile(action_buttons)($scope)
        createChangeButtonIfNone(action_buttons, saveRoles, dd, false);

        rolesSection.append(dd);
        rolesSection.append(add_new_role);
        rolesSection.append(action_buttons);
        //error section
        rolesSection.append($("<div class='error_box'><div id='action_not_completed' class='error_msg'></div></div>"));
        //success section
        rolesSection.append($("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

        // add modal
        modal = $("<div class='modal' id='new-role'></div>");
        newRole = $("<div class='modal_content little_modal'></div>");
        newRole.append($('<button class="close_btn icon" id="cancel_role" value="#new-role" onclick="closeModal(this)"></button>'));
        newRole.append($('<div class="title">New Role: </div>'));
        content = $('<div class="content">');
        box = $('<div class= "inputs">');
        box.append($('<div class="name full"><input type="text" class="form__input " id="role_name" placeholder="Name *" ng-model="newRole.name"/> <label for="name" class="form__label">Name</label></div>'));
        content.append(box);
        content.append($('<button class="save_btn" id="submit_role" ng-disabled="!isReadyToSubmit()" > Continue </button>'))
        newRole.append(content);
        modal.append(newRole);
        $compile(modal)($scope)
        rolesSection.append(modal);

        //generate table
        buildRoles(dd, $scope.data.rolesHierarchy);
        options = {}
        options.dropdown = $scope.data.pages
        dd.nestable(options);

        dd.on('change', function () {
            $compile(dd)($scope); //new elements need to be compiled into scope
            $scope.data.rolesHierarchy = dd.nestable('serialize');
            newAction($scope.data.rolesHierarchy);
            var list = $(this);
            updateChangeButton(false);

        }).on('additem', function (event, ret) {
            ret[0] = addNewRole();
            ret[0].then((roleName) => {
                $scope.data.newRoles.push(roleName);
                $scope.data.roles_obj.push({
                    'id': null,
                    'name': roleName,
                    'landingPage': ''
                })
                $scope[roleName] = "";
                $("#new-role").hide();
            });

        }).on('removeitem', function (event, data) {
            //delete de um role no nestable
            var roleName = data.name;
            deleteRoleAndChildren(dd.nestable('serialize'), roleName);
        });

        //duplicate by value array with role objs 
        duplicated_roles_obj = [];
        jQuery.each($scope.data.roles_obj, function (index) {
            role_obj = $scope.data.roles_obj[index];
            new_obj = Object.assign({}, role_obj);
            duplicated_roles_obj.push(new_obj);
        });
        //initialize state manager for undo/redo
        $scope.state_manager = new StateManager({ 'dd': dd.nestable('serialize'), 'roles': $scope.data.newRoles.slice(), 'obj': duplicated_roles_obj });


        // add role pelo add button
        $scope.addRole = function () {
            rolePromise = addNewRole();
            rolePromise.then((roleName) => {
                $scope.data.newRoles.push(roleName);
                $scope.data.roles_obj.push({
                    'id': null,
                    'name': roleName,
                    'landingPage': ''
                })
                $scope[roleName] = "";
                dd.nestable('createRootItem')(roleName, { name: roleName });
                dd.trigger('change');
                $("#new-role").hide();
            });

        };
    });

});
