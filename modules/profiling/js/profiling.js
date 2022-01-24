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
            runButton.id = "run-button";

            section.appendChild(exportButton);
            section.appendChild(importButton);
            section.appendChild(runButton);
            $compile(section)($scope);
            $scope.getHistory.call();

            var header = "<th><div class='container'><div ng-click='sortColumn(\"name\", false)' class='triangle-up' ng-class=\"{'checked_arrow': column == 'name' && !ascending}\"></div><div ng-click='sortColumn(\"name\", true)' class='triangle-down' ng-class=\"{'checked_arrow': column == 'name' && ascending}\"></div> Student </div></th><th ng-repeat='day in days'><div class='container'><div ng-click='sortColumn(day, false)' class='triangle-up' ng-class=\"{'checked_arrow': column == day && !ascending}\"></div><div ng-click='sortColumn(day, true)' class='triangle-down' ng-class=\"{'checked_arrow': column == day && ascending}\"></div>{{day}}</div></th>";
            $("#cluster-table thead").html(header);
            var body = "<tr id='table-content' ng-repeat='(key, value) in history | orderBy:predicate:ascending'><td>{{value.name}}</td><td ng-repeat='day in days'>{{value[day]}}</td></tr>";
            $("#cluster-table tbody").html(body);

            var table = document.getElementById("cluster-table");
            $compile(table)($scope);

            var statusDiv = document.getElementById("running-tag");
            statusDiv.innerHTML = '<p><b>Status:  </b> not running </p>';
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

    // called on header click
    $scope.sortColumn = function(col, descending){
        $scope.column = col;
        if(descending){
            $scope.ascending = true;
        }
        else {
            $scope.ascending = false;
        }
    };

    $scope.predicate = function(rows) {
        return rows[$scope.column];
      }

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

            if (document.getElementById("run-button")){
                document.getElementById("run-button").remove();
            }
            if (document.getElementById("export_button")){
                document.getElementById("export_button").remove();
            }
            if (document.getElementById("import_button")){
                document.getElementById("import_button").remove();
            }
            if (document.getElementById("refresh-button")){
                document.getElementById("refresh-button").remove();
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

    $scope.checkRunningStatus = function () {
        $smartboards.request('settings', 'checkRunningStatus', {course: $scope.course}, function(data, err){
		    var statusDiv = document.getElementById("running-tag");
            if (err) {
                //clearInterval($scope.timerID);
                statusDiv.innerHTML = '<p><b>Status:  </b> not running </p>';
                if (document.getElementById("refresh-button")){
                    document.getElementById("refresh-button").remove();
                }
                if (!document.getElementById("run-button")){
                    var section = document.getElementById("buttons");
                    var runButton = document. createElement("BUTTON");
                    runButton.className += "button small";
                    runButton.innerHTML = "Run";
                    runButton.onclick = $scope.runProfiler;
                    runButton.id = "run-button";
                    section.appendChild(runButton);
                    $compile(section)($scope);
                }
		        giveMessage(err.description);
                return;
            }

            if(!('running' in data)){
                //clearInterval($scope.timerID);
                $scope.running = false;
                $scope.clusters = data.clusters;
                $scope.cluster_names = data.names;
                $scope.select = {};

                var headerHtmlString = "<th><div class='container'><div ng-click='sortColumn(\"name\", false)' class='triangle-up' ng-class=\"{'checked_arrow': column == 'name' && !ascending}\"></div><div ng-click='sortColumn(\"name\", true)' class='triangle-down' ng-class=\"{'checked_arrow': column == 'name' && ascending}\"></div> Student </div></th><th ng-repeat='day in days'><div class='container'><div ng-click='sortColumn(day, false)' class='triangle-up' ng-class=\"{'checked_arrow': column == day && !ascending}\"></div><div ng-click='sortColumn(day, true)' class='triangle-down' ng-class=\"{'checked_arrow': column == day && ascending}\"></div>{{day}}</div></th><th></th><th> After </th>";
                $("#cluster-table thead").html(headerHtmlString);

                var htmlString = "<tr ng-repeat='(key, value) in history | orderBy:predicate:ascending'><td>{{value.name}}</td><td ng-repeat='day in days'>{{value[day]}}</td><td class=\"arrow_right\"></td><td><select class=\"dd-content\" ng-init=\"select[value.id]=clusters[value.id].cluster\" ng-model=\"select[value.id]\" ng-style=\"{'width' : '70%'}\" ng-options=\"cl.name as cl.name for cl in cluster_names\"></select></td></tr>";
                $("#cluster-table tbody").html(htmlString);
                var table = document.getElementById("cluster-table");
                $compile(table)($scope);

                $scope.buildButtons.call();
                statusDiv.innerHTML = '<p><b>Status:  </b> not running </p>';
                //clearInterval($scope.timerID);
            }
            else if(data.running){
                $scope.running = true;
                
                statusDiv.innerHTML = '<p><b>Status:  </b> running </p>';
                if (document.getElementById("run-button")){
                    document.getElementById("run-button").remove();
                }
                if (!document.getElementById("refresh-button")){
                    var section = document.getElementById("buttons");
                    var button = document. createElement("BUTTON");
                    button.innerHTML = "Refresh";
                    button.id = "refresh-button"
                    button.onclick = $scope.checkRunningStatus;
                    section.appendChild(button);
                }
                return;
            }
            else{
                statusDiv.innerHTML = '<p><b>Status:  </b> not running </p>';
                if (document.getElementById("refresh-button")){
                    document.getElementById("refresh-button").remove();
                }
                if (!document.getElementById("run-button")){
                    var runButton = document. createElement("BUTTON");
                    runButton.className += "button small";
                    runButton.innerHTML = "Run";
                    runButton.onclick = $scope.runProfiler;
                    runButton.id = "run-button";
                }
                
                $scope.running = false;
                //clearInterval($scope.timerID);
            }
        })
    };

    $scope.runProfiler = function () {
        $smartboards.request('settings', 'runProfiler', {course: $scope.course, nClusters: $scope.n_clusters, minSize: $scope.min_cluster_size}, function(data, err){
            if (err) {
                giveMessage(err.description);
                return;
            }
            var statusDiv = document.getElementById("running-tag");
            statusDiv.innerHTML = '<p><b>Status:  </b> running </p>';

            document.getElementById("run-button").remove();
            var section = document.getElementById("buttons");

            var button = document. createElement("BUTTON");
            button.innerHTML = "Refresh";
            button.id = "refresh-button"
            button.onclick = $scope.checkRunningStatus;
            section.appendChild(button);

            //$scope.timerID = setInterval($scope.checkRunningStatus, 30000);
        });
    };

    $scope.checkPredictorStatus = function () {
        $smartboards.request('settings', 'checkPredictorStatus', {course: $scope.course}, function(data, err){
            if (err) {
                //clearInterval($scope.timerID);
                if (document.getElementById("predict-refresh-button")){
                    document.getElementById("predict-refresh-button").remove();
                }
                if (!document.getElementById("predict-button")){
                    var row = document.getElementById("row1");
                    var button = document.createElement("BUTTON");
                    button.className += "button small predict-button";
                    button.innerHTML = "Predict";
                    button.value = "#predictor-modal"
                    button.onclick = function(){openModal(this)};
                    button.id = "predict-button";
                    row.appendChild(button);
                }
		        giveMessage(err.description);
                return;
            }

            if(!('predicting' in data)){
                if (document.getElementById("predict-refresh-button")){
                    document.getElementById("predict-refresh-button").remove();
                }
                if (!document.getElementById("predict-button")){
                    var row = document.getElementById("row1");
                    var button = document.createElement("BUTTON");
                    button.className += "button small predict-button";
                    button.innerHTML = "Predict";
                    button.value = "#predictor-modal"
                    button.onclick = function(){openModal(this)};
                    button.id = "predict-button";
                    row.appendChild(button);
                }

                $scope.predictedClusters = data.nClusters;
                var placeholder = document.createElement("param");
                placeholder.value = "#results-modal";
                openModal(placeholder);
                //clearInterval($scope.timerID);
            }
            else if(data.predicting === true){
                if (!document.getElementById("predict-refresh-button")){
                    var row = document.getElementById("row1");
                    var button = document. createElement("BUTTON");
                    button.innerHTML = "Refresh";
                    button.id = "predict-refresh-button"
                    button.classList.add("predict-button");
                    button.onclick = $scope.checkPredictorStatus;
                    row.appendChild(button);
                }
            }
            else {
                if (document.getElementById("predict-refresh-button")){
                    document.getElementById("predict-refresh-button").remove();
                }
                if (!document.getElementById("predict-button")){
                    var row = document.getElementById("row1");
                    var button = document.createElement("BUTTON");
                    button.className += "button small predict-button";
                    button.innerHTML = "Predict";
                    button.value = "#predictor-modal"
                    button.onclick = function(){openModal(this)};
                    button.id = "predict-button";
                    row.appendChild(button);
                }
                //clearInterval($scope.timerID);
            }
        });
    };

    $scope.runPredictor = function () {
        $smartboards.request('settings', 'runPredictor', {course: $scope.course, method: $scope.method}, function(data, err){
            if (err) {
                giveMessage(err.description);
                return;
            }

            document.getElementById("predict-button").remove();
            var row = document.getElementById("row1");

            var button = document. createElement("BUTTON");
            button.innerHTML = "Refresh";
            button.id = "predict-refresh-button"
            button.classList.add("predict-button");
            button.onclick = $scope.checkPredictorStatus;
            row.appendChild(button);

            //$scope.timerID = setInterval($scope.checkPredictorStatus, 30000);
        });
    };

    $scope.replaceNClusters = function(predictedClusters){
        $scope.n_clusters = parseInt(predictedClusters);
    }

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
    importVerification.append($('<div class="confirmation_btns"><button ng-click="importItems(false)">Import</button></div>'));
    importModal.append(importVerification);
    $compile(importModal)($scope);
    runSection.append(importModal);
    
    var methods = [{name: "Elbow method", char: "e"}, {name: "Silhouette method", char: "s"}];
    $scope.method = "e";

    predictorModal = $("<div class='modal' id='predictor-modal'></div>");
    predictorVerification = $("<div class='verification modal_content'></div>");
    predictorVerification.append($('<button class="close_btn icon" value="#predictor-modal" onclick="closeModal(this)"></button>'));
    predictorVerification.append($('<div class="warning">Please select a method to be used to predict the number of clusters:</div>'));
    predictorContent = $('<div class="content"></div>');
    methodOptions = $("<div class='predictor-methods'></div>");
    jQuery.each(methods, function (index) {
        methodOptions.append($("<label class='predictor-container'> " + methods[index]["name"] + 
        "<input type='radio' checked='checked' ng-model='method' name='radio' ng-value='\""+ methods[index]["char"] + 
        "\"' id='"+ methods[index]["char"] + "'><span class='checkmark'></span></label>")
        ); 
    });
    predictorContent.append(methodOptions);
    predictorVerification.append(predictorContent);
    predictorButtons = $('<div class="results_row" ></div>');
    predictorButtons.append($('<div class="confirmation_btns"><button value="#predictor-modal" onclick="closeModal(this)" ng-click="runPredictor()">Predict</button></div>'));
    predictorButtons.append($('<div class="confirmation_btns"><button class="prediction-cancel" value="#predictor-modal" onclick="closeModal(this)">Cancel</button></div>'));
    predictorVerification.append(predictorButtons);
    predictorModal.append(predictorVerification);
    runSection.append($compile(predictorModal)($scope));

    resultsModal = $("<div class='modal' id='results-modal'></div>");
    resultsVerification = $("<div class='verification modal_content'></div>");
    resultsVerification.append($('<button class="close_btn icon" value="#results-modal" onclick="closeModal(this)"></button>'));
    resultsVerification.append($('<div class="title">Predictor results</div>'));
    resultsVerification.append($('<div class="warning">The predicted number of clusters for the next run is <b>{{predictedClusters}}</b>.</div>'));
    resultsVerification.append($('<div class="warning">Replace number of clusters?</div>'));
    verificationButtons = $('<div class="results_row" ></div>');
    verificationButtons.append($('<div class="confirmation_btns"><button class="cancel" value="#results-modal" onclick="closeModal(this)" ng-click="replaceNClusters(predictedClusters)">Yes</button></div>'));
    verificationButtons.append($('<div class="confirmation_btns"><button class="continue" id ="no-predictor-button" value="#results-modal" onclick="closeModal(this)">No</button></div>'));
    resultsVerification.append(verificationButtons);
    resultsModal.append(resultsVerification);
    runSection.append($compile(resultsModal)($scope));

    var runningTag = $("<div id='running-tag'></div>");
    runSection.append(runningTag);

    contentDiv = ($('<div class="title"><p id="results" ><b>Profiling Results:</b></p></div>'));
    content = $('<div class="content">');

    $scope.getHistory.call();
    $scope.n_clusters = 4;
    $scope.min_cluster_size = 4;

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

        runConfig = $('<div class="cluster_column" ></div>');
        row1 = $("<div id='row1' class='cluster_row cluster_input'></div>");
        row1.append('<span > Number of clusters: </span>');
        row1.append('<input class="config_input" ng-init="n_clusters" ng-model="n_clusters" type="number" min="3" max="10">');

        row2 = $("<div class='cluster_row cluster_input'></div>");
        row2.append('<span > Minimun cluster size: </span>');
        row2.append('<input class="config_input" ng-init="min_cluster_size" ng-model="min_cluster_size" type="number" min="0">');
        
        runConfig.append(row1);
        runConfig.append(row2);
        runSection.prepend($compile(runConfig)($scope));
        
    });
    
    $smartboards.request('settings', 'getSaved', {course: $scope.course}, function(data, err){
        if (err) {
            giveMessage(err.description);
            return;
        }
        $scope.cluster_names = data.names;
        $scope.select = data.saved;
        

        // sort ordering (Ascending or Descending). Set true for descending
        $scope.ascending = false;
        // column to sort
        $scope.column = 'name';
        
        
        var dataTable = $('<div class="data-table" ></div>');
        var table = $('<table id="cluster-table">');
        rowHeader = $('<thead>');

        if ($scope.select.length == 0){

            action_buttons.append($("<button id='export_button' class='icon export_icon profiling_button other' value='#export-item' ng-click='exportItem()'></button></div>"));
            action_buttons.append($("<button id='import_button' class='icon import_icon profiling_button other' value='#import-item' onclick='openModal(this)'></button>"));
            action_buttons.append($('<button id="run-button" class="button small" ng-click="runProfiler()">Run</button>'));
            
            runSection.append($compile(action_buttons)($scope));
            rowHeader.append("<th><div class='container'><div ng-click='sortColumn(\"name\", false)' class='triangle-up' ng-class=\"{'checked_arrow': column == 'name' && !ascending}\"></div><div ng-click='sortColumn(\"name\", true)' class='triangle-down' ng-class=\"{'checked_arrow': column == 'name' && ascending}\"></div> Student </div></th><th ng-repeat='day in days'><div class='container'><div ng-click='sortColumn(day, false)' class='triangle-up' ng-class=\"{'checked_arrow': column == day && !ascending}\"></div><div ng-click='sortColumn(day, true)' class='triangle-down' ng-class=\"{'checked_arrow': column == day && ascending}\"></div>{{day}}</div></th>");
            rowHeader.append('</thead>')
            rowContent = $("<tr id='table-content' ng-repeat='(key, value) in history | orderBy:predicate:ascending'>");
            rowContent.append("<td>{{value.name}}</td>");
            rowContent.append("<td ng-repeat='day in days'>{{value[day]}}</td>");
            
            $scope.checkRunningStatus.call();
 
            
        }
        else {
            rowHeader.append("<th><div class='container'><div ng-click='sortColumn(\"name\", false)' class='triangle-up' ng-class=\"{'checked_arrow': column == 'name' && !ascending}\"></div><div ng-click='sortColumn(\"name\", true)' class='triangle-down' ng-class=\"{'checked_arrow': column == 'name' && ascending}\"></div> Student </div></th><th ng-repeat='day in days'><div class='container'><div ng-click='sortColumn(day, false)' class='triangle-up' ng-class=\"{'checked_arrow': column == day && !ascending}\"></div><div ng-click='sortColumn(day, true)' class='triangle-down' ng-class=\"{'checked_arrow': column == day && ascending}\"></div>{{day}}</div></th><th></th><th> After </th>");
            rowHeader.append('</thead>')
            rowContent = $("<tr id='table-content' ng-repeat='(key, value) in history | orderBy:predicate:ascending'>");
            rowContent.append("<td>{{value.name}}</td>");
            rowContent.append("<td ng-repeat='day in days'>{{value[day]}}</td>");
            rowContent.append("<td class=\"arrow_right\"></td><td><select class=\"dd-content\" ng-model=\"select[value.id]\" ng-style=\"{'width' : '70%'}\" ng-options=\"cl.name as cl.name for cl in cluster_names\"></select></td>");
            var statusDiv = document.getElementById("running-tag");
            statusDiv.innerHTML = '<p><b>Status:  </b> not running </p>';
            $scope.buildButtons.call();
        }

        $scope.checkPredictorStatus.call();

        rowContent.append("</tr></table>");
        table.append(rowHeader);
        table.append(rowContent);
        dataTable.append(table);
        content.append(dataTable);

        contentDiv.append(content);
        runSection.append($compile(contentDiv)($scope));      
    });

    //$compile(configurationSection)($scope);

}