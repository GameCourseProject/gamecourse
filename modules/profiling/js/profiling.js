//add config page to course
app.stateProvider.state('course.settings.profiling', {
    url: '/profiling',
    views: {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'profiling'
    }
});

function profilingPersonalizedConfig($scope, $element, $smartboards, $compile) {

    $scope.getHistory = function() {
        $smartboards.request('settings', 'getHistory', { course: $scope.course}, function(data, err){
            if (err) {
                giveMessage(err.description);
                return;
            }

            $scope.history = data.history;
            $scope.nodes = data.nodes;
            $scope.data = data.data;
            if (data.days.length > 0 ){
                $scope.days = data.days;
            }
            else {
                $scope.days = ["Current"];
            }

            if ($scope.data.length > 0){
                $scope.buildChart.call();
            }
            else {
                document.getElementById("overview").innerHTML = "Nothing to show yet"
            }

        });
    };

    $scope.closeEditClusters = function() {
        $smartboards.request('settings', 'deleteSaved', {course: $scope.course}, function(data, err){
            if (err) {
                giveMessage(err.description);
                return;
            }
            $scope.select = {};
            document.getElementById("cancel-button").remove();
            document.getElementById("commit-button").remove();
            document.getElementById("save-button").remove();

            var section = document.getElementById("buttons");
            var exportButton = document. createElement("BUTTON");
            exportButton.className += "icon export_icon profiling_button other";
            exportButton.onclick = $scope.exportItem;
            exportButton.id = "export_button";
            exportButton.value = "#export-item";


            var section = document.getElementById("buttons");
            var importButton = document. createElement("BUTTON");
            importButton.className += "icon import_icon profiling_button other";
            importButton.onclick = function(){openModal(this)};
            importButton.id = "import_button";
            importButton.value = "#import-item";

            var section = document.getElementById("buttons");
            var runButton = document. createElement("BUTTON");
            runButton.className += "button small";
            runButton.innerHTML = "Run";
            runButton.onclick = $scope.runProfiler;
            runButton.id = "run_button";

            section.appendChild(exportButton);
            section.appendChild(importButton);
            section.appendChild(runButton);
            $compile(section)($scope);
            $scope.getHistory.call();

            var header = "<th> Student </th><th ng-repeat='day in days'>{{day}}</th>";
            $("#cluster-table thead").html(header);
            var body = "<tr id='table-content' ng-repeat='(key, value) in history'><td>{{value.name}}</td><td ng-repeat='entry in value.history'>{{entry.cluster}}</td></tr>";
            $("#cluster-table tbody").html(body);

            var table = document.getElementById("cluster-table");
            $compile(table)($scope);
        });
    };

    $scope.commitClusters = function() {
        $smartboards.request('settings', 'commitClusters', {course: $scope.course, clusters: $scope.select}, alertUpdate);
    };

    $scope.saveClusters = function() {
        $smartboards.request('settings', 'saveClusters', {course: $scope.course, clusters: $scope.select}, function(data, err){
            if (err) {
                giveMessage(err.description);
                return;
            }
        });
    };

    $scope.updateClusterCounter = function() {
        $scope.selected_count = [];
        $scope.cluster_names.forEach(element => $scope.selected_count[element["name"]] = filterByCluster($scope.select, element["name"]));
        var results = document.getElementById("results");
        results.innerHTML = "<b>Profiling Results:</b><span ng-repeat='(key, value) in selected_count'>{{key}} : {{value}}</span>"
        $compile(results)($scope);
    };

    $scope.buildChart = function(){

        Highcharts.chart("overview", {
            chart: {
                marginRight: 40
            },
            title: {
                text: ""
            },
            series: [{
                keys: ["from", "to", "weight"],
                nodes: $scope.nodes,
                data: $scope.data,
                type: "sankey",
                name: "Cluster History",
                dataLabels: {
                    style: {
                        color: "#1a1a1a",
                        textOutline: false
                    }
                }
            }]
        });
    };

    $scope.buildButtons = function(){

            if (document.getElementById("run_button")){
                document.getElementById("run_button").remove();
                document.getElementById("export_button").remove();
                document.getElementById("import_button").remove();
            }

            var section = document.getElementById("buttons");
            var saveButton = document. createElement("BUTTON");
            saveButton.className += "button small profiling_button";
            saveButton.innerHTML = "Save";
            saveButton.value = "#cluster";
            saveButton.id = "save-button";
            saveButton.onclick = $scope.saveClusters;

            var commitButton = document. createElement("BUTTON");
            commitButton.className += "button small";
            commitButton.innerHTML = "Commit";
            commitButton.value = "#cluster";
            commitButton.id = "commit-button";
            commitButton.onclick = $scope.commitClusters;

            var cancelButton = document. createElement("BUTTON");
            cancelButton.className += "button cancel profiling_button";
            cancelButton.innerHTML = "Cancel";
            cancelButton.value = "#cluster";
            cancelButton.id = "cancel-button";
            cancelButton.style.backgroundColor = "tomato";
            cancelButton.onclick = $scope.closeEditClusters;
            
            section.appendChild(cancelButton);
            section.appendChild(saveButton);
            section.appendChild(commitButton);
            $compile(section)($scope);

    };

    $scope.runProfiler = function () {
        $smartboards.request('settings', 'runProfiler', {course: $scope.course}, function(data, err){
            if (err) {
                giveMessage(err.description);
                return;
            }
            $scope.clusters = data.clusters;
            $scope.cluster_names = data.names;
            $scope.select = {};

            var headerHtmlString = "<th> Student </th><th ng-repeat='day in days'>{{day}}</th><th></th><th> After </th>";
            $("#cluster-table thead").html(headerHtmlString);
            var htmlString = "<tr ng-repeat='(key, value) in history'><td>{{value.name}}</td><td ng-repeat='entry in value.history'>{{entry.cluster}}</td><td class=\"arrow_right\"></td><td><select class=\"dd-content\" ng-init=\"select[key]=clusters[key].cluster\" ng-model=\"select[key]\" ng-style=\"{'width' : '70%'}\" ng-options=\"cl.name as cl.name for cl in cluster_names\"></select></td></tr>";
            $("#cluster-table tbody").html(htmlString);
            var table = document.getElementById("cluster-table");
            $compile(table)($scope);

            $scope.buildButtons.call();
             
        });
    };

    var configurationSection = $($element);
    var overviewSection = createSection(configurationSection, 'Overview');
    overviewSection.append("<figure class=\"highcharts-figure\"><div id='overview'></div></figure>");
    
    var runSection = createSection(configurationSection, 'Run the Profiler');
    var action_buttons = $("<div id='buttons' class='config_save_button'>");
    runSection.append(action_buttons);

    importModal = $("<div class='modal' id='import-item'></div>");
    importVerification = $("<div class='verification modal_content'></div>");
    importVerification.append($('<button class="close_btn icon" value="#import-item" onclick="closeModal(this)"></button>'));
    importVerification.append($('<div class="warning">Please select a .csv or .txt file to be imported</div>'));
    importVerification.append($('<div class="target">The seperator must be comma</div>'));
    importVerification.append($('<input class="config_input" type="file" id="import_item" accept=".csv, .txt">')); //input file
    importVerification.append($('<div class="confirmation_btns"><button ng-click="importItems(false)">Import</button></div>'))
    importModal.append(importVerification);
    $compile(importModal)($scope);
    runSection.append(importModal);
    

    contentDiv = ($('<div class="title"><p id="results" ><b>Profiling Results:</b></p></div>'));
    content = $('<div class="content">');
    
    $scope.getHistory.call();
    $smartboards.request('settings', 'getSaved', {course: $scope.course}, function(data, err){
        if (err) {
            giveMessage(err.description);
            return;
        }
        $scope.cluster_names = data.names;
        $scope.select = data.saved;

        
        
        var dataTable = $('<div class="data-table" ></div>');
        var table = $('<table id="cluster-table">');
        rowHeader = $('<thead>');

        if ($scope.select.length == 0){
            action_buttons.append($("<button id='export_button' class='icon export_icon profiling_button other' value='#export-item' ng-click='exportItem()'></button></div>"));
            action_buttons.append($("<button id='import_button' class='icon import_icon profiling_button other' value='#import-item' onclick='openModal(this)'></button>"));
            action_buttons.append($('<button id="run_button" class="button small" ng-click="runProfiler()">Run</button>'));
            runSection.append($compile(action_buttons)($scope));
            rowHeader.append("<th> Student </th><th ng-repeat='day in days'>{{day}}</th>");
            rowHeader.append('</thead>')
            rowContent = $("<tr id='table-content' ng-repeat='item in history'>");
            rowContent.append("<td>{{item.name}}</td>");
            rowContent.append("<td ng-repeat='entry in item.history'>{{entry.cluster}}</td>");
        }
        else {
            rowHeader.append("<th> Student </th><th ng-repeat='day in days'>{{day}}</th><th></th><th> After </th>");
            rowHeader.append('</thead>')
            rowContent = $("<tr id='table-content' ng-repeat='(key, value) in history'>");
            rowContent.append("<td>{{value.name}}</td>");
            rowContent.append("<td ng-repeat='entry in value.history'>{{entry.cluster}}</td>");
            rowContent.append("<td class=\"arrow_right\"></td><td><select class=\"dd-content\" ng-model=\"select[key]\" ng-style=\"{'width' : '70%'}\" ng-options=\"cl.name as cl.name for cl in cluster_names\"></select></td>");
            $scope.buildButtons.call();
        }

        rowContent.append("</tr></table>");
        table.append(rowHeader);
        table.append(rowContent);
        dataTable.append($compile(table)($scope));
        content.append(dataTable);

        contentDiv.append(content);
        runSection.append($compile(contentDiv)($scope));
    });

    $smartboards.request('settings', 'getTime', { course: $scope.course }, function(data, err){
        if (err) {
            giveMessage(err.description);
            return;
        }
        
        if(data.time != null) {
            $scope.time = data.time;
        }
        else {
            $scope.time = "Never";
        }
        
        var time = $("<div id='time'></div>");
        time.append('<p><b>Last run:  </b>' + $scope.time + '</p>');
        runSection.prepend($compile(time)($scope));
        
    });
    
    $compile(configurationSection)($scope);
}