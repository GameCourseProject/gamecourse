angular.module('module.views').run(function ($sbviews, $compile, $parse) {
    $sbviews.registerPartType('table', {
        name: 'Table',
        defaultPart: function () {
            var part = {
                partType: 'table',
                columns: 1,//[{}],//[{sortMode: 'def'}],
                headerRows: [],
                rows: [{values: [], parameters:{loopData: "{}",visibilityCondition: "{}",visibilityType: "conditional"} }],
                parameters: {loopData: "{}",visibilityCondition: "{}",visibilityType: "conditional"}
            };
            part.rows[0].values.push({value: $sbviews.defaultPart('text')});
            return part;
        },
        changePids: function(part, change) {
            for (var i = 0; i < part.rows.length; ++i) {
                var values = part.rows[i].values;
                for (var j = 0; j < values.length; ++j) {
                    change(values[j].value);
                }
            }

            for (var i = 0; i < part.headerRows.length; ++i) {
                var values = part.headerRows[i].values;
                for (var j = 0; j < values.length; ++j) {
                    change(values[j].value);
                }
            }
        },
        build: function (scope, part, options) {
            $sbviews.setDefaultParamters(part);
            var tableDiv = $(document.createElement('div')).addClass('table');
            var table = $(document.createElement('table'));

            /*part.filterBox = {
                field: 'd',
                options: ['abc', 'def']
            };*/

            var childOptions;
            if (options.edit) {
                childOptions = $sbviews.editOptions(options, {
                    toolOptions: {
                        canDelete: false,
                        canSwitch: true,
                        canDuplicate: false,
                        canSaveTemplate: true
                    },
                    toolFunctions: {
                        switch: function (oldPart, newPart) {
                            for (var i = 0; i < part.rows.length; ++i) {
                                var values = part.rows[i].values;
                                for (var j = 0; j < values.length; ++j) {
                                    if (values[j].value == oldPart) {
                                        part.rows[i].values[j].value = newPart;
                                        var newPartEl = $sbviews.build(scope, 'part.rows[' + i + '].values[' + j + '].value', childOptions);
                                        var oldPartEl = $($(table.children('tbody').children().get(i)).children().get(j));
                                        oldPartEl.replaceWith(newPartEl);
                                        $sbviews.destroy(oldPartEl);
                                        return;
                                    }
                                }
                            }

                            for (var i = 0; i < part.headerRows.length; ++i) {
                                var values = part.headerRows[i].values;
                                for (var j = 0; j < values.length; ++j) {
                                    if (values[j].value == oldPart) {
                                        part.headerRows[i].values[j].value = newPart;
                                        var newPartEl = $sbviews.build(scope, 'part.headerRows[' + i + '].values[' + j + '].value', childOptions);
                                        var oldPartEl = $($(table.children('thead').children().get(i)).children().get(j)).children().first();
                                        oldPartEl.replaceWith(newPartEl);
                                        $sbviews.destroy(oldPartEl);
                                        return;
                                    }
                                }
                            }
                        }
                    },
                    overlayOptions: {
                        allowEvents: true,
                        allowVariables: true,
                        allowStyle: true,
                        allowClass: true
                    }
                });
            }

            var thead = $(document.createElement('thead'));
            for (var ridx in  part.headerRows) {
                var row = part.headerRows[ridx];
                //$sbviews.setDefaultParamters(part.headerRows);
                $sbviews.setDefaultParamters(part.headerRows[ridx]);
                var rowEl = $(document.createElement('tr'));
                $sbviews.applyCommonFeatures(scope, row, rowEl, $.extend({disableEvents: true}, options));

                for (var cidx=0;cidx<part.columns;cidx++) {
                    var column = {};//part.columns[cidx];
                    var columnEl = $(document.createElement('th'));
                    //$sbviews.applyCommonFeatures(scope, column, columnEl, options);
                    columnEl.append($sbviews.build(scope, 'part.headerRows[' + ridx + '].values[' + cidx + '].value', childOptions));
                    rowEl.append(columnEl);
                }

                if (!options.edit && row.parameters.events) {//?
                    var keys = Object.keys(row.parameters.events);
                    for (var i = 0; i < keys.length; ++i) {
                        var key = keys[i];
                        var fn = $parse(row.parameters.events[key]);
                        (function(key, fn, row) {
                            var rowScope = scope.$new();
                            rowScope.event = key;
                            rowScope.row = row;
                            rowEl.on(key, function() { fn(rowScope); });
                        })(key, fn, row);
                    }
                }

                thead.append(rowEl);
            }
            table.append(thead);

            var tbody = $(document.createElement('tbody'));
            for (var ridx in  part.rows) {
                var row = part.rows[ridx];
               // $sbviews.setDefaultParamters(part.rows);
                $sbviews.setDefaultParamters(part.rows[ridx]);
                var rowEl = $(document.createElement('tr'));
                $sbviews.applyCommonFeatures(scope, row, rowEl, $.extend({disableEvents: true}, options));

                for (var cidx =0; cidx<part.columns;cidx++) {
                    var column = {};//part.columns[cidx];
                    var columnEl = $(document.createElement('td'));
                    //$sbviews.applyCommonFeatures(scope, column, columnEl, options);
                    columnEl.append($sbviews.build(scope, 'part.rows[' + ridx + '].values[' + cidx + '].value', childOptions));
                    rowEl.append(columnEl);
                }

                if (!options.edit && row.parameters.events) {//?
                    var keys = Object.keys(row.parameters.events);
                    for (var i = 0; i < keys.length; ++i) {
                        var key = keys[i];
                        var fn = $parse(row.parameters.events[key]);
                        (function(key, fn, row) {
                            var rowScope = scope.$new();
                            rowScope.event = key;
                            rowScope.row = row;
                            rowEl.on(key, function() { fn(rowScope); });
                        })(key, fn, row);
                    }
                }

                tbody.append(rowEl);
            }
            table.append(tbody);
            tableDiv.append(table);

            if (!options.edit ){//&& part.sort != undefined && part.sort) 
                table.tablesorter({sortList: [], headers: {} });
            }

            if (part.filterBox != undefined && !options.edit) {
                var filterField = part.filterBox.value;

                var applyFilter;
                var removeFilter;
                if (part.filterBox.mode == 'fade') {
                    applyFilter = function(el) {
                        el.css({opacity: 0.2});
                    };
                    removeFilter = function(el) {
                        el.css({opacity: 1.0});
                    };
                } else if (part.filterBox.mode == 'hide') {
                    applyFilter = function(el) {
                        el.hide();
                    };
                    removeFilter = function(el) {
                        el.show();
                    };
                } else {
                    alert('Filter mode ' + part.filterBox.mode + ' not implemented.');
                }

                var values = [];
                for (var ridx in  part.headerRows) {
                    if (part.headerRows[ridx].data == undefined)
                        continue;
                    var value = part.headerRows[ridx].data[filterField];
                    if (value != null && values.indexOf(value.value) == -1)
                        values.push(value.value);
                }

                for (var ridx in  part.rows) {
                    if (part.rows[ridx].data == undefined)
                        continue;
                    var value = part.rows[ridx].data[filterField];
                    if (value != null && values.indexOf(value.value) == -1)
                        values.push(value.value);
                }

                values.sort(function(a, b) {
                    return a.localeCompare(b);
                });

                var filterDefault = '-- Filter --';
                var selectBox = $(document.createElement('select')).addClass('filter-box');
                selectBox.append($(document.createElement('option')).text(filterDefault));
                for (var opt in values)
                    selectBox.append($(document.createElement('option')).text(values[opt]));
                tableDiv.prepend(selectBox);
                selectBox.on('change', function() {
                    var val = selectBox.val();
                    thead.children().each(function() { removeFilter($(this)); });
                    tbody.children().each(function() { removeFilter($(this)); });
                    if (val != filterDefault) {
                        for (var ridx in  part.headerRows) {
                            if (part.headerRows[ridx].data == undefined)
                                continue;
                            var value = part.headerRows[ridx].data[filterField];
                            if (value != null && value.value != val)
                                applyFilter($(thead.children().get(ridx)));
                        }

                        for (var ridx in  part.rows) {
                            if (part.rows[ridx].data == undefined)
                                continue;
                            var value = part.rows[ridx].data[filterField];
                            if (value != null && value.value != val)
                                applyFilter($(tbody.children().get(ridx)));
                        }
                    }
                });
            }

            if (options.edit) {
                var buildColumnToolbar;
                var buildRowToolbar;
                var checkEmpty;

                function insertColumn(idx) {
                    
                    part.columns++;
                    $($(thead.children().get(0)).children().get(idx)).before(buildColumnToolbar(idx));

                    for (var rid = 0; rid < part.headerRows.length; ++rid) {
                        var newPart = $sbviews.defaultPart('text');
                        part.headerRows[rid].values.splice(idx, 0, {value: newPart});
                        var newPartEl = $sbviews.buildElement(scope, newPart, childOptions);
                        var th = $(document.createElement('th')).append(newPartEl);
                        $($(thead.children().get(rid + 1)).children().get(idx)).before(th);
                    }
                    for (var rid = 0; rid < part.rows.length; ++rid) {
                        var newPart = $sbviews.defaultPart('text');
                        part.rows[rid].values.splice(idx, 0, {value: newPart});
                        var newPartEl = $sbviews.buildElement(scope, newPart, childOptions);
                        var td = $(document.createElement('td')).append(newPartEl);
                        $($(tbody.children().get(rid)).children().get(idx)).before(td);
                    }

                    $sbviews.notifyChanged(part, options);
                    checkEmpty(true);
                }

                function removeColumn(idx) {
                    part.columns--;
                    $($(thead.children().get(0)).children().get(idx)).remove();
                    for (var rid = 0; rid < part.headerRows.length; ++rid) {
                        $($(thead.children().get(rid + 1)).children().get(idx)).remove();
                        part.headerRows[rid].values.splice(idx, 1);
                    }

                    for (var rid = 0; rid < part.rows.length; ++rid) {
                        $($(tbody.children().get(rid)).children().get(idx)).remove();
                        part.rows[rid].values.splice(idx, 1);
                    }

                    $sbviews.notifyChanged(part, options);
                    checkEmpty(true);
                }

                function insertRow(container, idx, header) {
                    var rowIdx = idx - (header ? 1 : 0);
                    var row = {values:[], parameters: {loopData: "{}",visibilityCondition: "{}"} };
                    var newRowEl = $(document.createElement('tr'));
                    $sbviews.applyCommonFeatures(scope, row, rowEl, $.extend({disableEvents: true}, options));
                    for (var cid=0;cid<part.columns;cid++) {
                        var newPart = $sbviews.defaultPart('text');
                        row.values.push({value: newPart});
                        $sbviews.setDefaultParamters(row);
                        newRowEl.append($(document.createElement(header ? 'th' : 'td')).append($sbviews.buildElement(scope, newPart, childOptions)));
                    }
                    newRowEl.append(buildRowToolbar(container, row, header));

                    container.splice(rowIdx, 0, row);
                    var containerEl = (header ? thead : tbody);

                    if (rowIdx == container.length - 1)
                        containerEl.append(newRowEl);
                    else
                        $(containerEl.children().get(idx)).before(newRowEl);

                    $sbviews.notifyChanged(part, options);
                    checkEmpty(true);
                }

                function removeRow(container, idx, header) {
                    var rowIdx = idx - (header ? 1 : 0);
                    container.splice(rowIdx, 1);
                    $((header ? thead : tbody).children().get(idx)).remove();

                    $sbviews.notifyChanged(part, options);
                    checkEmpty(true);
                }

                function moveColumn(oldIdx, newIdx) {
                    if (newIdx < 0 || newIdx > part.columns - 1)
                        return;

                    $($(thead.children().get(0)).children().get(oldIdx)).replaceWith(buildColumnToolbar(oldIdx));
                    $($(thead.children().get(0)).children().get(newIdx)).replaceWith(buildColumnToolbar(newIdx));
                    for (var rid = 0; rid < part.headerRows.length; ++rid) {
                        var oldRowEl = part.headerRows[rid].values[oldIdx];
                        part.headerRows[rid].values[oldIdx] = part.headerRows[rid].values[newIdx];
                        part.headerRows[rid].values[newIdx] = oldRowEl;

                        var th = $(document.createElement('th')).append($sbviews.buildElement(scope, part.headerRows[rid].values[oldIdx].value, childOptions));
                        $($(thead.children().get(rid + 1)).children().get(oldIdx)).replaceWith(th);

                        var th2 = $(document.createElement('th')).append($sbviews.buildElement(scope, part.headerRows[rid].values[newIdx].value, childOptions));
                        $($(thead.children().get(rid + 1)).children().get(newIdx)).replaceWith(th2);
                    }
                    for (var rid = 0; rid < part.rows.length; ++rid) {
                        var oldRowEl = part.rows[rid].values[oldIdx];
                        part.rows[rid].values[oldIdx] = part.rows[rid].values[newIdx];
                        part.rows[rid].values[newIdx] = oldRowEl;

                        var td = $(document.createElement('td')).append($sbviews.buildElement(scope, part.rows[rid].values[oldIdx].value, childOptions));
                        $($(tbody.children().get(rid)).children().get(oldIdx)).replaceWith(td);

                        var td2 = $(document.createElement('td')).append($sbviews.buildElement(scope, part.rows[rid].values[newIdx].value, childOptions));
                        $($(tbody.children().get(rid)).children().get(newIdx)).replaceWith(td2);
                    }

                    $sbviews.notifyChanged(part, options);
                }

                function moveRow(container, oldIdx, newIdx, header) {
                    if (header){//bug fix:header toolbar is in a th, so the index counting inst acurate
                        oldIdx--;
                        newIdx--;
                    }
                    if (newIdx < 0 || newIdx > container.length - 1)
                        return;

                    var oldRow = container[oldIdx];
                    container[oldIdx] = container[newIdx];
                    container[newIdx] = oldRow;

                    var newRowEl = $(document.createElement('tr'));
                    var newRowEl2 = $(document.createElement('tr'));
                    for (var cid=0;cid< part.columns;cid++) {
                        newRowEl.append($(document.createElement(header ? 'th' : 'td')).append($sbviews.buildElement(scope, container[oldIdx].values[cid].value, childOptions)));
                        newRowEl2.append($(document.createElement(header ? 'th' : 'td')).append($sbviews.buildElement(scope, container[newIdx].values[cid].value, childOptions)));
                    }
                    newRowEl.append(buildRowToolbar(container, container[oldIdx], header));
                    newRowEl2.append(buildRowToolbar(container, container[newIdx], header));
                    $sbviews.applyCommonFeatures(scope, container[oldIdx], newRowEl, $.extend({disableEvents: true}, options));
                    $sbviews.applyCommonFeatures(scope, container[newIdx], newRowEl2, $.extend({disableEvents: true}, options));
                    
                    if (header){//going back to the inacurate indx values (because they match the table elements)
                        oldIdx++;
                        newIdx++;
                    }
                    var containerEl = (header ? thead : tbody);

                    $(containerEl.children().get(oldIdx)).replaceWith(newRowEl);
                    $(containerEl.children().get(newIdx)).replaceWith(newRowEl2);
                    $sbviews.notifyChanged(part, options);
                }

                buildColumnToolbar = function (cidx) {
                    var columnEl = $(document.createElement('th'));
                    var toolbar = $sbviews.createToolbar(scope, {}, {
                        view: options.view,
                        notifiedPart: part,                        
                        tools: {
                            noSettings: true
                        },
                        editData: options.editData
                    });

                    toolbar.prepend($sbviews.createTool('Insert Right', 'images/insert-down.svg', function () {
                        insertColumn(columnEl.index() + 1);
                    }).attr('style', 'transform: rotate(-90deg)'));
                    toolbar.prepend($sbviews.createTool('Insert Left', 'images/insert-up.svg', function () {
                        insertColumn(columnEl.index());
                    }).attr('style', 'transform: rotate(-90deg)'));

                    toolbar.prepend($sbviews.createTool('Move Right', 'images/move-down.svg', function () {
                        moveColumn(columnEl.index(), columnEl.index() + 1);
                    }).attr('style', 'transform: rotate(-90deg)'));
                    toolbar.prepend($sbviews.createTool('Move Left', 'images/move-up.svg', function () {
                        moveColumn(columnEl.index(), columnEl.index() - 1);
                    }).attr('style', 'transform: rotate(-90deg)'));

                    toolbar.append($sbviews.createTool('Delete Column', 'images/trashcan.svg', function () {
                        removeColumn(columnEl.index());
                    }));
                    columnEl.append(toolbar);
                    return columnEl;
                };
                buildRowToolbar = function(container, row, header) {
                    var rowEl = $(document.createElement(header ? 'th' : 'td'));
                    var toolbar = $sbviews.createToolbar(scope, row, {
                        view: options.view,
                        notifiedPart: part,
                        overlayOptions: {
                            allowStyle: true,
                            allowClass: true,
                            allowDataLoop: true,
                            allowEvents: true,
                            allowVariables: true,
                            allowIf: true
                        }, tools: {},
                        editData: options.editData
                    });
                    toolbar.prepend($sbviews.createTool('Insert Down', 'images/insert-down.svg', function () {
                        insertRow(container, rowEl.parent().index() + 1, header);
                    }));
                    toolbar.prepend($sbviews.createTool('Insert Up', 'images/insert-up.svg', function () {
                        insertRow(container, rowEl.parent().index(), header);
                    }));

                    toolbar.prepend($sbviews.createTool('Move Down', 'images/move-down.svg', function () {
                        moveRow(container, rowEl.parent().index(), rowEl.parent().index() + 1, header);
                    }));
                    toolbar.prepend($sbviews.createTool('Move Up', 'images/move-up.svg', function () {
                        moveRow(container, rowEl.parent().index(), rowEl.parent().index() - 1, header);
                    }));

                    toolbar.append($sbviews.createTool('Delete Row', 'images/trashcan.svg', function () {
                        removeRow(container, rowEl.parent().index(), header);
                    }));
                    rowEl.append(toolbar);
                    rowEl.css({'text-align': 'center'});
                    return rowEl;
                };

                checkEmpty = function(editing) {
                    var emptyTableDivSearch = tableDiv.find('.empty-table');
                    var hasEmptyTableDiv = emptyTableDivSearch.length != 0;
                    var emptyTable = (part.headerRows.length == 0 && part.rows.length == 0) || part.columns == 0;

                    if (emptyTable && !editing) {
                        if (!hasEmptyTableDiv)
                            tableDiv.append('<span class="empty-table red">(Table)</span>');
                    } else if (hasEmptyTableDiv) {
                        emptyTableDivSearch.remove();
                    }

                    var addHeaderRowsRowSearch = thead.find('.add-header-rows');
                    var hasAddHeaderRowsRow = addHeaderRowsRowSearch.length != 0;
                    var hasHeaderRows = (part.headerRows.length != 0);

                    if (!hasHeaderRows && editing) {
                        if (!hasAddHeaderRowsRow) {
                            var addRow = $(document.createElement('button')).text('Add Header Row').on('click', function() {
                                insertRow(part.headerRows, 1, true);
                            });
                            thead.append($('<tr>', {'class': 'add-header-rows'}).append($('<th>').append(addRow)));
                        }
                    } else if (hasAddHeaderRowsRow) {
                        addHeaderRowsRowSearch.remove();
                    }

                    var addBodyRowsRowSearch = tbody.find('.add-body-rows');
                    var hasAddBodyRowsRow = addBodyRowsRowSearch.length != 0;
                    var hasBodyRows = (part.rows.length != 0);

                    if (!hasBodyRows && editing) {
                        if (!hasAddBodyRowsRow) {
                            var addRow = $(document.createElement('button')).text('Add Row').on('click', function() {
                                insertRow(part.rows, 0, false);
                            });
                            tbody.append($('<tr>', {'class': 'add-body-rows'}).append($('<td>').append(addRow)));
                        }
                    } else if (hasAddBodyRowsRow) {
                        addBodyRowsRowSearch.remove();
                    }

                    var addColumnRowSearch = thead.find('.add-column');
                    var hasAddColumnRow = addColumnRowSearch.length != 0;
                    var hasColumn = (part.columns != 0);

                    if (!hasColumn && editing) {
                        if (!hasAddColumnRow) {
                            var addColumn = $(document.createElement('button')).text('Add Column').on('click', function() {
                                insertColumn(0);
                            });
                            thead.prepend($('<tr>', {'class': 'add-column'}).append($('<th>').append(addColumn)));
                        }
                    } else if (hasAddColumnRow) {
                        addColumnRowSearch.remove();
                    }
                };

                checkEmpty(false);

                $sbviews.bindToolbar(tableDiv, scope, part, options, {
                    layoutEditor: true,
                    toolFunctions: {
                        layoutEditStart: function () {
                            checkEmpty(true);

                            var rowEditHeader = $(document.createElement('tr')).attr('style', 'height: 24px');
                            for (var cidx = 0; cidx < part.columns; ++cidx) {
                                rowEditHeader.append(buildColumnToolbar(cidx));
                            }
                            rowEditHeader.append($(document.createElement('th'))); // empty th
                            thead.prepend(rowEditHeader);
                            for (var ridx = 0; ridx < part.headerRows.length; ++ridx) {
                                $(thead.children().get(ridx + 1)).append(buildRowToolbar(part.headerRows, part.headerRows[ridx], true));
                            }
                            for (var ridx = 0; ridx < part.rows.length; ++ridx) {
                                $(tbody.children().get(ridx)).append(buildRowToolbar(part.rows, part.rows[ridx]));
                            }
                        },
                        layoutEditEnd: function () {
                            checkEmpty(false);

                            $(thead.children().get(0)).remove();
                            for (var ridx = 0; ridx < part.headerRows.length; ++ridx) {
                                $($(thead.children().get(ridx)).children().get(part.columns)).remove();
                            }
                            for (var ridx = 0; ridx < part.rows.length; ++ridx) {
                                $($(tbody.children().get(ridx)).children().get(part.columns)).remove();
                            }
                        }
                    },
                    overlayOptions: {
                        callbackFunc: function(el, execClose, optionsScope, watch) {
                            /*var partSpecificMenu = $('<sb-menu sb-menu-title="Content" sb-menu-icon="images/gear.svg"></sb-menu>');
                            var filterBox = $('<sb-checkbox sb-checkbox="part.filterBox" sb-checkbox-label="Enable Filter" sb-checkbox-default="{value: \'\', mode: \'hide\'}" sb-checkbox-info="Allows the viewer to filter the items in the table." sb-checkbox-link="./docs/#PartTableFilter"></sb-checkbox>');
                            filterBox.append($('<sb-input sb-input="part.filterBox.value" sb-input-label="Filter"></sb-input>'));
                            filterBox.append($('<div class="sb-component"><label for="mode-select">Mode</label><select id="mode-select" ng-model="part.filterBox.mode"><option value="hide">Hide</option><option value="fade">Fade</option></select></div>'));
                            partSpecificMenu.append(filterBox);
                            partSpecificMenu.append($('<sb-checkbox sb-checkbox="part.sort" sb-checkbox-label="Enable Sort" sb-checkbox-default="true" sb-checkbox-info="Enables sorting of the columns of the table." sb-checkbox-link="./docs/#PartTableSort"></sb-checkbox>'));

                            watch('part.sort');
                            watch('part.filterBox');
                            el.children('.partSpecific').after($compile(partSpecificMenu)(optionsScope));*/
                        }
                    }
                });
                tableDiv.css('padding-top', 18);
            }
            return tableDiv;
        },
        destroy: function (element) {
            element.find('td, th').children().each(function () {
                $sbviews.destroy($(this));
            });
        }
    });
});