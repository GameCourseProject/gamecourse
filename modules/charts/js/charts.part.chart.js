angular.module('module.charts', []).run(function($sbviews, $compile) {
    function runWhenInDom(el, func) {
        // check if element is in DOM
        if (el.closest(document.documentElement).length != 0) {
            func(); //setTimeout(function() { func(); }, 0);
        } else {
            var observer = new MutationObserver(function(mutations) {
                // is in dom yet
                if (el.closest(document.documentElement).length != 0) {
                    observer.disconnect();
                    func(); //setTimeout(function() { func(); }, 0);
                }
            });
            var config = { attributes: false, childList: true, characterData: false, subtree: true};
            observer.observe(document.documentElement, config);
        }
    }

    function lineChart(el, options, data) {
        var svg = d3.select(el.get(0));
        svg.attr('width', options.width)
            .attr('height', options.height);

        var x = d3.scale.linear()
            .range([options.paddingLeft, options.width-options.paddingRightTop]);

        var rangeY = [options.height-options.paddingBottom, options.paddingRightTop];
        if (options.invertY)
            rangeY = [rangeY[1], rangeY[0]];

        var y = d3.scale.linear()
            .range(rangeY);

        var xAxis = d3.svg.axis()
            .scale(x)
            .orient('bottom');

        var yAxis = d3.svg.axis()
            .scale(y)
            .orient('left');

        var line = d3.svg.line()
            .x(function(d) { return x(d.x); })
            .y(function(d) { return y(d.y); });

        x.domain(options.domainX).nice();
        y.domain(options.domainY).nice();

        var numTicks = y.ticks().length;

        if (options.startAtOneY) {
            var ticks = y.ticks();
            ticks[0] = 1;
            yAxis.tickValues(ticks);
            var domain = y.domain();
            domain[0] = 1;
            y.domain(domain);
        }

        if (!options.spark) {
            svg.append('g')
                .attr('class', 'x axis')
                .attr('transform', 'translate(0,' + (options.height - options.paddingBottom) + ')')
                .call(xAxis)
                .selectAll('.tick')
                .filter(function(d, i) { return i > 0; })
                .insert('line')
                .attr('class', 'rule')
                .attr('y1', - (options.height - options.paddingBottom - options.paddingRightTop))
                .attr('y2', 0);

            svg.append('g')
                .attr('transform', 'translate(' + options.paddingLeft + ', ' + 0 + ')')
                .attr('class', 'y axis')
                .call(yAxis)
                .selectAll('.tick')
                .filter(function(d, i) { return (options.invertY ? i < numTicks - 1 : i > 0); })
                .insert('line')
                .attr('class', 'rule')
                .attr('x1', 0)
                .attr('x2', options.width-options.paddingRightTop-options.paddingLeft);

            //Axis Labels
            //text label for the xAxis
            svg.append("text")
                .attr("transform", "translate(" + (options.width/2) + " ," + (options.height - (options.paddingBottom/5)) + ")")
                .attr("class", "text label")
                .style("text-anchor", "middle")
                .text(options.labelX);

            //text label for the yAxis
            svg.append("text")
                .attr("transform", "rotate(-90)")
                .attr("y", 0 + (options.paddingLeft/5))
                .attr("x",0 - (options.height / 2))
                .attr("dy", "1em")
                .attr("class", "text label")
                .style("text-anchor", "middle")
                .text(options.labelY);
        }

        svg.append('path')
            .datum(data)
            .attr('class', 'line')
            .attr('d', line);
    }

    function barChart(el, options, data) {
        var svg = d3.select(el.get(0));
        svg.attr('width', options.width)
            .attr('height', options.height);

        var x = d3.scale.ordinal()
            .domain(options.domainX)
            .rangeRoundBands([options.paddingLeft, options.width-options.paddingRightTop], 0.1);

        var y = d3.scale.linear()
            .domain(options.domainY)
            .range([options.height-options.paddingBottom, options.paddingRightTop]).nice();

        var xAxis = d3.svg.axis()
            .scale(x)
            .tickValues(x.domain().filter(function(d, i) { return (i == 0 || (i == options.domainX.length-1 && i % 4 > 2) || i % 4 == 0); }))
            .orient('bottom');

        var yAxis = d3.svg.axis()
            .scale(y)
            .tickFormat(d3.format('.0f'))
            .orient('left');

        if (options.tickValuesY)
            yAxis.tickValues(options.tickValuesY);

        svg.append('g')
            .attr('class', 'x axis')
            .attr('transform', 'translate(0,' + (options.height - options.paddingBottom) + ')')
            .call(xAxis);

        svg.append('g')
            .attr('transform', 'translate(' + options.paddingLeft + ', 0)')
            .attr('class', 'y axis')
            .call(yAxis);

        //Axis Labels
        //text label for the xAxis
        svg.append("text")
            .attr("transform", "translate(" + (options.width/2) + " ," + (options.height - (options.paddingBottom/5)) + ")")
            .attr("class", "text label")
            .style("text-anchor", "middle")
            .text(options.labelX);

        //text label for the yAxis
        svg.append("text")
            .attr("transform", "rotate(-90)")
            .attr("y", 0 + (options.paddingLeft/5))
            .attr("x",0 - (options.height / 2))
            .attr("dy", "1em")
            .attr("class", "text label")
            .style("text-anchor", "middle")
            .text(options.labelY);


        svg.selectAll('rect')
            .data(data)
            .enter()
            .append('rect')
            .attr('x', function(d) { return x(d.x) + (options.shiftBar ? x.rangeBand() / 2 : 0); })
            .attr('width', x.rangeBand())
            .attr('y', function(d) { return y(d.y); })
            .attr('height', function(d) { return options.height - options.paddingBottom - y(d.y); })
            .attr('class', function(d) {
                if (options.highlightValue != undefined && d.x == options.highlightValue)
                    return 'highlighted';
                return 'normal';
            });
    }

    $sbviews.registerPartType('chart', {
        name: 'Chart',
        defaultPart: function() {
            return {
                type: 'chart',
                chartType: 'line',
                info: {
                    provider:''
                }
            };
        },
        changePids: function(part, change) {
        },
        build: function(scope, part, options) {
            function createSVGElement(tag) {
                return document.createElementNS('http://www.w3.org/2000/svg', tag);
            }

            var chartWrapper = $(document.createElement('div')).addClass('chart');
            var chart = $(createSVGElement('svg'));
            chartWrapper.append(chart);
            if (options.edit) {
                chart.css({backgroundColor: 'darkmagenta'}).attr('width', '200').attr('height', '150').attr('viewBox', '0 0 200 150');
                var text = $(createSVGElement('text')).text('Chart');
                text.css({fill: '#ffffff', fontSize: '20px'}).attr('x', 100).attr('y', 75).attr('text-anchor', 'middle').attr('alignment-baseline', 'middle');
                chart.append(text);
                $sbviews.bindToolbar(chartWrapper, scope, part, options, { overlayOptions: {callbackFunc: function(el, execClose, optionsScope, watch) {
                    var root = $('<sb-menu sb-menu-title="Part Specific" sb-menu-icon="images/gear.svg"></sb-menu>');

                    // Chart Type
                    var chartTypeWrapper = $('<div class="sb-component"></div>');
                    var chartTypeLabel = $('<label for="part-type">Type</label>');
                    var chartTypeSelect = $('<select id="part-type" ng-model="part.chartType"></select>');
                    chartTypeSelect.append('<option value="line">Line</option>');
                    chartTypeSelect.append('<option value="bar">Bar</option>');
                    chartTypeSelect.append('<option value="star">Star</option>');
                    chartTypeSelect.append('<option value="progress">Progress</option>');
                    chartTypeWrapper.append(chartTypeLabel);
                    chartTypeWrapper.append(chartTypeSelect);
                    root.append(chartTypeWrapper);
                    watch('part.chartType', function(n) {
                        //this is not doing anything usefull because it should be changing the child scope
                        if (n == 'star') {
                            scope.part.info.provider = 'starPlot';
                            scope.part.info.params = [];
                        } else {
                            delete scope.part.info.params;
                            scope.part.info.provider = '';
                        }
                    });
                    
                    // Chart Provider
                    root.append('<sb-input ng-if="part.info.provider != \'starPlot\' && part.chartType != \'progress\'" sb-input="part.info.provider" sb-input-label="Chart Provider"></sb-input>');
                    watch('part.info.provider');

                    // Sparkline
                    root.append('<sb-checkbox ng-if="part.chartType == \'line\'" sb-checkbox="part.info.spark" sb-checkbox-label="Enable Sparkline"></sb-input>');
                    watch('part.info.spark');

                    // Star Params
                    optionsScope.starParams = {};
                    optionsScope.addParam = function(part) {
                        var id = optionsScope.starParams.id;
                        var label = optionsScope.starParams.label;
                        var max = optionsScope.starParams.max;
                        
                        part.info.params.push({id: id, label: label, max: max});

                        optionsScope.starParams.id = '';
                        optionsScope.starParams.label = '';
                        optionsScope.starParams.max = '';
                    };
                    optionsScope.removeParam = function(index, part) {
                        part.info.params.splice(index, 1);
                    };
                    var starParamsWrapper = $('<div ng-if="part.info.params != undefined">');
                    starParamsWrapper.append('<ul><li ng-repeat="param in part.info.params track by $index">{{param.id}} - {{param.label}} - {{param.max}} <button ng-click="removeParam($index, part)">Remove</button></li></ul>');
                    root.append(starParamsWrapper);

                    var starAddParam = $('<div class="sb-component" ng-if="part.chartType == \'star\'"></div>');
                    starAddParam.append('<span>Add new parameter</span>');
                    var starAddParamInputs = $('<div style="margin-left: 14px"></div>');
                    starAddParamInputs.append('<sb-input sb-input="starParams.id" sb-input-label="Id"></sb-input>');
                    starAddParamInputs.append('<sb-input sb-input="starParams.label" sb-input-label="Label"></sb-input>');
                    starAddParamInputs.append('<sb-input sb-input="starParams.max" sb-input-label="Max"></sb-input>');
                    starAddParamInputs.append('<button class="sb-component" ng-click="addParam(part)">Add</button>');
                    starAddParam.append(starAddParamInputs);
                    root.append(starAddParam);
                    watch('part.info.params');

                    // Progress Bar
                    root.append('<sb-expression ng-if="part.chartType == \'progress\'" sb-expression="part.info.value" sb-expression-label="Value"></sb-expression>');
                    root.append('<sb-expression ng-if="part.chartType == \'progress\'" sb-expression="part.info.max" sb-expression-label="Max"></sb-expression>');
                    watch('part.info.value');
                    watch('part.info.max');

                    el.children('.title').after($compile(root)(optionsScope));
                }}});
            } else {
                runWhenInDom(chart, function() {
                    var block = chart.parent();
                    var size = Math.min(Math.max(block.width() - 16, 450), 550); // padding
                    var sizeH = Math.min(Math.max(block.height() - 16, 190), 250); // padding

                    if (part.chartType == 'line') {
                        var options = {
                            width: size,
                            height: 200,
                            paddingRightTop: 10,
                            paddingLeft: 75,
                            paddingBottom: 45,
                            domainX: part.info.domainX,
                            domainY: part.info.domainY,
                            spark: part.info.spark,
                            labelX: part.info.labelX,
                            labelY: part.info.labelY
                        };

                        if (part.info.spark) {
                            options.width = 50;
                            options.height = 20;
                            options.paddingLeft = 1;
                            options.paddingRightTop = 1;
                            options.paddingBottom = 1;
                        }

                        if (part.info.invertY != undefined)
                            options.invertY = part.info.invertY;

                        if (part.info.startAtOneY != undefined)
                            options.startAtOneY = part.info.startAtOneY;

                        chart.attr('class', 'chart-line');
                        lineChart(chart, options, part.info.values);
                    } else if (part.chartType == 'bar') {
                        var options = {
                            width: size,
                            height: 200,
                            paddingRightTop: 10,
                            paddingLeft: 50,
                            paddingBottom: 45,
                            domainX: part.info.domainX,
                            domainY: part.info.domainY,
                            labelX: part.info.labelX,
                            labelY: part.info.labelY
                        };

                        if (part.info.shiftBar != undefined)
                            options.shiftBar = part.info.shiftBar;

                        if (part.info.highlightValue != undefined)
                            options.highlightValue = part.info.highlightValue;

                        chart.attr('class', 'chart-bar');
                        barChart(chart, options, part.info.values);
                    } else if (part.chartType == 'star') {
                        var options = {
                            width: sizeH,
                            height: sizeH
                        };

                        chart.attr('class', 'chart-star');

                        var content = part.info;
                       
                        var svg = d3.select(chart.get(0));
                        svg.attr('width', options.width)
                            .attr('height', options.height);
                        var star = d3.starPlot()
                            .properties(content.params.map(function(datum) { return datum.id; }))
                            .labels(content.params.map(function(datum) { return datum.label; }))
                            .scales(content.params.map(function(datum) { return d3.scale.linear().domain([0, datum.max]).range([0, 100]); }))
                            .labelMargin(20)
                            .margin({top: 40, right: 20, bottom: 0, left: 30})
                            .width(options.width - 70)
                            .includeGuidelines(true);

                        svg.append('g')
                            .attr('class', 'average')
                            .data([content.average])
                            .call(star);
                        var userStar = svg.append('g')
                            .attr('class', 'user')
                            .data([content.user])
                            .call(star);
                        userStar.selectAll('.star-axis').remove();
                        userStar.selectAll('.star-label').remove();
                        userStar.selectAll('.star-origin').remove();
                        userStar.selectAll('.star-title').remove();
                    } else if (part.chartType == 'progress') {
                        chart.remove();
                        chartWrapper.append($('<div>', {text: part.info.value + ' out of ' + part.info.max}));
                        var progressBar = $('<div>', {style: 'height:5px; border:2px solid #555; padding:0; width:80px;'});
                        progressBar.append($('<div>', {style: 'height:100%; width:' + ((part.info.value / part.info.max) * 80) + 'px; background-color:#555;'}));
                        chartWrapper.append(progressBar);
                    }
                });
            }
            return chartWrapper;
        },
        destroy: function(element) {
        }
    });
});
