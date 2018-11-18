var Builder = {};
Builder.blockCtors = {};
Builder.pageBlockCtors = {};

Builder.createPageBlock = function(info, customContentBuilder) {
    customContentBuilder = customContentBuilder || (info.customContentBuilder ? info.type : undefined);

    var pBlock = $('<div>', {'class': 'page-block'});
    var pBlockHeader = $('<div>', {'class': 'header'});
    if (info.includePageLink)
        pBlockHeader.append($('<a>', {name: info.text.replace(/\s/g, '')}));
    pBlockHeader.append($('<img>', {src: info.image}));
    pBlockHeader.append($('<span>', {text: info.text}));
    pBlock.append(pBlockHeader);
    var pBlockContent;
    if (customContentBuilder && {}.toString.call(customContentBuilder) == '[object Function]') {
        pBlockContent = $('<div>');
        pBlock.append(pBlockContent);
        customContentBuilder(pBlockContent, info);
    } else if (customContentBuilder) {
        pBlockContent = $('<div>');
        pBlock.append(pBlockContent);
        var ctorsForType = Builder.pageBlockCtors[customContentBuilder];
        if (ctorsForType == undefined)
            console.log('No handler for: ' + customContentBuilder);
        else {
            for(var i = 0; i < ctorsForType.length; ++i) {
                ctorsForType[i](pBlockContent, info);
            }
        }
    } else {
        pBlockContent = $('<div>', {'class': 'content'});
        pBlock.append(pBlockContent);
        for (var i = 0; i < info.blocks.length; ++i) {
            pBlockContent.append(Builder.buildBlock(info.blocks[i]));
        }
    }
    return pBlock;
}

Builder.onBlock = function(type, func) {
    var ctorsForType = Builder.blockCtors[type];
    if (ctorsForType == undefined)
        Builder.blockCtors[type] = [func];
    else
        Builder.blockCtors[type].push(func);
}

Builder.onPageBlock = function(type, func) {
    var ctorsForType = Builder.pageBlockCtors[type];
    if (ctorsForType == undefined)
        Builder.pageBlockCtors[type] = [func];
    else
        Builder.pageBlockCtors[type].push(func);
}

Builder.buildBlock = function(blockInfo, customContentBuilder) {
    var blockEl = $('<div class="block block-' + blockInfo.type + '"></div>');
    if (blockInfo.width != undefined)
        blockEl.css('width', blockInfo.width);
    if (blockInfo.height != undefined)
        blockEl.css('height', blockInfo.height);
    if (blockInfo.grow != undefined)
        blockEl.css('flex-grow', blockInfo.grow);
    if (blockInfo.mobileGrow != undefined)
        blockEl.addClass('mobile-grow');

    if (blockInfo.noHeader == undefined || !blockInfo.noHeader) {
        blockEl.append('<div class="block-header"><img src="' + blockInfo.image + '"><span>' + blockInfo.title + '<span></div>');
    }

    var blockContent = $('<div>', {'class': 'block-content'});
    blockEl.append(blockContent);

    if (customContentBuilder) {
        customContentBuilder(blockContent, blockInfo);
    } else {
        var ctorsForType = Builder.blockCtors[blockInfo.type];
        if (ctorsForType == undefined)
            console.log('No handler for: ' + blockInfo.type);
        else {
            for (var i = 0; i < ctorsForType.length; ++i) {
                ctorsForType[i](blockContent, blockInfo.content, blockInfo);
            }
        }
    }

    return blockEl;
}

Builder.getValue = function (obj, field) {
    return field.split('.').reduce(function (obj,i) { return obj[i]; }, obj);
}

Builder.buildTable = function(content, columns, buildHeader, tableOptions, settings) {
    var options = $.extend({'rowEditor': undefined}, settings);
    var table = $('<table>', tableOptions == undefined ? {} : tableOptions);
    if (buildHeader) {
        var tHeader = $('<thead>');
        var tr = $('<tr>');
        for(var col = 0; col < columns.length; ++col) {
            var colDef = columns[col];
            if(typeof colDef === 'string' || colDef instanceof String)
                tr.append('<th>' + colDef + '</th>');
            else if (colDef.headerConstructor != undefined) {
                var el = $('<th>');
                tr.append(el.append(colDef.headerConstructor(colDef.header, colDef.field, el, colDef, options)));
            } else if (colDef.header != undefined)
                tr.append('<th>' + colDef.header + '</th>');
            else
                tr.append('<th><!-- empty --></th>');
        }
        tHeader.append(tr);
        table.append(tHeader);
    }

    var tBody = $('<tbody>')
    for(var i = 0; i < content.length; ++i) {
        var tr = $('<tr>');
        for(var col = 0; col < columns.length; ++col) {
            var colDef = columns[col];
            if(typeof colDef === 'string' || colDef instanceof String)
                tr.append('<td>' + Builder.getValue(content[i], colDef) + '</td>');
            else if (colDef.constructor == undefined)
                tr.append('<td>' + Builder.getValue(content[i], colDef.field) + '</td>');
            else {
                var el = $('<td>');
                tr.append(el.append(colDef.constructor(Builder.getValue(content[i], colDef.field), el, colDef, content[i], options)));
            }

        }
        if (options.rowEditor != undefined)
            options.rowEditor(tr, content[i]);
        tBody.append(tr);
    }
    return table.append(tBody);
}
