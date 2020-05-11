//All the aux functions used to create the views of the system

//from settings.js
function buildTabs(info, parent, $smartboards, $scope) {
    var el = $('<li>');
    var link = $('<a>', {'ui-sref': info.sref});
    link.append($('<span>', {text: info.text}));
    el.append(link);
    if (info.subItems != null) {
        var subList = $('<ul>');
        for (var i = 0; i < info.subItems.length; ++i) {
            subList.append(buildTabs(info.subItems[i], subList, $smartboards, $scope));
        }
        el.append(subList);
    }
    return el;
}

function createSection(parent, title) {
    var sec = $('<div>', {'class':'section'});
    sec.append($('<div>', {'class':'title', text: title}));
    var content = $('<div>', {'class':'content'});
    sec.append(content);
    parent.append(sec);
    return content;
}

function createSectionWithTemplate(parent, title, templateUrl) {
    var sec = $('<div>', {'class':'section'});
    sec.append($('<div>', {'class':'title', text: title}));
    var content = $('<div>', {'class':'content', 'ng-include': '\'' + templateUrl + '\''});
    sec.append(content);
    parent.append(sec);
    return content;
}

function createInputWithChange(id, text, placeholder, $compile, $smartboards, $parse, scope, ngModel, module, request, field, additionalData, successMsg) {
    //ngModel =  'data.roles.landingPage'
    var wrapperDiv = $('<div>');
    wrapperDiv.append('<label for="' + id + '" class="label">' + text + '</label>');
    var textInput = $('<input>', {type: 'text', id:'' + id + '', 'class': 'input-text', placeholder: placeholder, 'ng-model': ngModel});
    wrapperDiv.append($compile(textInput)(scope));

    textInput.bind('change paste keyup', function() {
        var input = $(this);
        createChangeButtonIfNone(id, textInput, function (status) {
            var data = {};
            data[field] = $parse(ngModel)(scope);
            $.extend(data, additionalData);
            $smartboards.request(module, request, data, function(data, err) {
                if (err) {
                    status.text('Error, please try again!');
                    input.prop('disabled', false);
                    return;
                }

                status.text(successMsg);
                input.prop('disabled', false);
            });
        }, {
            createMode: 'after',
            disableFunc: function () {
                input.prop('disabled', true);
            }
        });
    });

    return wrapperDiv;
}

function createChangeButtonIfNone(name, anchor, action, config) {
    var defaults = {
        'buttonText' : 'Save',
        'statusTextUpdating': 'Updating',
        'enableFunc': undefined,
        'disableFunc': undefined,
        'createMode': 'append',
        'onCreate': undefined
    };
    config = $.extend({}, defaults, config);

    if (anchor.parent().find('#' + name + '-button').length == 0) {
        var pageStatus = anchor.parent().find('#' + name + '-status');
        if (pageStatus.length != 0)
            pageStatus.remove();
        var changePage = $('<button>', {id: name + '-button', text: config.buttonText, 'class': 'button'});
        changePage.click(function() {
            var status = $('<span>', {id: name + '-status', text: config.statusTextUpdating});
            if (config.disableFunc != undefined)
                config.disableFunc();
            $(this).replaceWith(status);
            action(status);
            if (config.enableFunc != undefined)
                config.enableFunc();
        });
        anchor[config.createMode](changePage);
        if (config.onCreate != undefined)
            config.onCreate();
    }
}

function updateTabTitle(stateName, stateParams) {
    var title = '';

    var possibleLinks = $(document).find('#settings > .tabs > .tabs-container a[ui-sref^="' + stateName + '"]');
    if (possibleLinks.length == 0)
        return;

    var matchedParams = 0;

    var final = undefined;
    for (var i = 0; i < possibleLinks.length; ++i) {
        var link = $(possibleLinks.get(i));
        var sref = link.attr('ui-sref');

        if (sref == stateName) {
            final = link;
            break;
        } else if (sref.substr(stateName.length, 1) == '(') {
            var paramsStart = stateName.length + 2;
            var params = sref.substr(paramsStart, sref.length - paramsStart - 2).split(',');
            for(var j = 0; j < params.length; ++j) {
                var param = params[j].split(':');
                var key = param[0];
                var value = param[1].substr(1, param[1].length - 2);
                if (stateParams[key] == value) {
                    if (j + 1 > matchedParams) {
                        final = link;
                        matchedParams = j + 1;
                    }
                } else {
                    break;
                }
            }
        }
    }

    if (final == undefined)
        return;

    title = final.text();
    var parent = final.parent().parent();
    while(!parent.hasClass('tabs-container')) {
        final = parent.prev();
        parent = final.parent().parent();

        title = final.text() + ' > ' + title;
    }

    setSettingsTitle(title);
}

function setSettingsTitle(title) {
    $('#settings > .tabs > .tab-content-wrapper .tab-title').text(title);
}

//from the app.js

//muda o titulo no breadcrumb
function changeTitle(newTitle, depth, keepOthers, url) {
    var title = $('title');
    var baseTitle = title.attr('data-base');

    var titles = title.data('titles');
    if (titles == undefined)
        title.data('titles', titles = []);

    var titlesWithLinks = title.data('titles-with-links');
    if (titlesWithLinks == undefined)
        title.data('titles-with-links', titlesWithLinks = []);

    var newTitleWithLink = (url != undefined ? '<a href="' + url + '">' + newTitle + '</a>' : newTitle);

    if (depth < titles.length && !keepOthers) {
        titles.splice(depth, titles.length - depth, newTitle);
        titlesWithLinks.splice(depth, titlesWithLinks.length - depth, newTitleWithLink);
    } else {
        titles.splice(depth, 1, newTitle);
        titlesWithLinks.splice(depth, 1, newTitleWithLink);
    }

    var finalTitle = titles.join(' - ');
    var finalTitleWithLinks = titlesWithLinks.join(' - ');

    title.text(baseTitle + (finalTitle.length != 0 ? ' - ' : '') + finalTitle);
    $('#page-title').html((finalTitleWithLinks.length != 0 ? ' - ' : '') + finalTitleWithLinks);
}


