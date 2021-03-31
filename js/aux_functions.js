//All the aux functions used to create the views of the system

function addPagesBackToNavBar() {
    if ($("#other-pages") && $("#other-pages")[0]) {
        menu = $('.menu')[0];
        menu_div = $('.menu');
        menu_length = menu.children.length;

        other_pages = $("#other-pages")[0];
        other_content = other_pages.children[1];

        //to restore order remove last options (dropdowns)
        settings = menu.children[menu_length - 1];
        settings.remove();
        other_pages.remove();

        while (other_content.children.length > 0) {
            menu_option = $("<li class='reputed'></li>");
            child = other_content.children[0];
            menu_option.append(child);
            menu_div.append(menu_option);
        }

        menu_div.append(settings);
    }


}

function beginNavbarResize() {
    $(".reputed").remove();
    $("#other-pages").remove();
    checkNavbarLength();
}

//so pode ser chamado até x size depois passa a versao mobile
//to make sure everything fits on the navbar
function checkNavbarLength() {
    var menu_div = $('.menu');
    if (menu_div.prop('scrollWidth') > menu_div.prop('clientWidth') || menu_div.height() > 55) {
        menu = menu_div[0];
        menu_length = menu.children.length;

        if (menu_div.find("#other-pages").size() > 0) {
            //gets the li element
            last_before_otherpages_and_settings = menu.children[menu_length - 3];
            //gets the a element
            a_last_before_otherpages_and_settings = last_before_otherpages_and_settings.children[0];

            //gets other pages divs
            other_pages = $("#other-pages")[0];
            other_content = other_pages.children[1];

            last_before_otherpages_and_settings.remove();
            other_content.prepend(a_last_before_otherpages_and_settings);
        } else {
            settings = menu.children[menu_length - 1];
            //gets the li element
            last_before_settings = menu.children[menu_length - 2];
            before_last_before_settings = menu.children[menu_length - 3];
            //gets the a element
            a_last_before_settings = last_before_settings.children[0];
            a_before_last_before_settings = before_last_before_settings.children[0];

            //removes li elements from menu
            last_before_settings.remove();
            before_last_before_settings.remove();

            //creates dropdown for other pages
            other_pages = $("<li class='dropdown' id='other-pages'></li>");
            other_title = $("<a>Other Pages</a>");
            other_content = $("<div class='dropdown-content'></div>");

            other_content.prepend(a_last_before_settings);
            other_content.prepend(a_before_last_before_settings);

            other_pages.append(other_title);
            other_pages.append(other_content);

            settings.remove();
            menu_div.append(other_pages);
            menu_div.append(settings);
        }
        checkNavbarLength();
    }

}

$(window).resize(function () {
    //resize just happened, pixels changed
    addPagesBackToNavBar();
    checkNavbarLength();
});




function range(start, end) {
    return Array(end - start + 1).fill().map((_, idx) => start + idx)
}

function semestersYears(start, end) {
    years = range(start, end);
    semesters = [];
    jQuery.each(years, function (index) {
        se = years[index].toString() + "-" + (years[index] + 1).toString();
        semesters.push(se)
    });
    return semesters;
}

//triggers download of csv file
function download(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

function removeSpacefromName(name) {
    name = name.replace(/\s/g, '');
    name = name.replace('-', '');
    return name;
}


//from settings.js
function buildTabs(info, parent, $smartboards, $scope) {
    var el = $('<li>');
    var link = $('<a>', { 'ui-sref': info.sref });
    link.append($('<span>', { text: info.text }));
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
    var sec = $('<div>', { 'class': 'section' });
    var divider = $('<div class="divider"><div class="title"><span>' + title + '</span></div></div>');
    sec.append(divider);
    var content = $('<div>', { 'class': 'content' });
    sec.append(content);
    parent.append(sec);
    return content;
}

function createSectionWithTemplate(parent, title, templateUrl) {
    var sec = $('<div>', { 'class': 'section' });
    sec.append($('<div>', { 'class': 'title', text: title }));
    var content = $('<div>', { 'class': 'content', 'ng-include': '\'' + templateUrl + '\'' });
    sec.append(content);
    parent.append(sec);
    return content;
}

function createInputWithChange(id, text, placeholder, $compile, $smartboards, $parse, scope, ngModel, module, request, field, additionalData, successMsg) {
    //ngModel =  'data.roles.landingPage'
    var wrapperDiv = $('<div>');
    wrapperDiv.append('<label for="' + id + '" class="label">' + text + '</label>');
    var textInput = $('<input>', { type: 'text', id: '' + id + '', 'class': 'input-text', placeholder: placeholder, 'ng-model': ngModel });
    wrapperDiv.append($compile(textInput)(scope));

    textInput.bind('change paste keyup', function () {
        var input = $(this);
        createChangeButtonIfNone(id, textInput, function (status) {
            var data = {};
            data[field] = $parse(ngModel)(scope);
            $.extend(data, additionalData);
            $smartboards.request(module, request, data, function (data, err) {
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
        'buttonText': 'Save',
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
        var changePage = $('<button>', { id: name + '-button', text: config.buttonText, 'class': 'button' });
        changePage.click(function () {
            var status = $('<span>', { id: name + '-status', text: config.statusTextUpdating });
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
            for (var j = 0; j < params.length; ++j) {
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
    while (!parent.hasClass('tabs-container')) {
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

//muda o titulo no breadcrumb - titulo pagina geral

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


function lightOrDark(color) {

    // Check the format of the color, HEX or RGB?
    if (color.match(/^rgb/)) {

        // If HEX --> store the red, green, blue values in separate variables
        color = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/);

        r = color[1];
        g = color[2];
        b = color[3];
    } else {

        // If RGB --> Convert it to HEX: http://gist.github.com/983661
        color = +("0x" + color.slice(1).replace(
            color.length < 5 && /./g, '$&$&'
        ));

        r = color >> 16;
        g = color >> 8 & 255;
        b = color & 255;
    }

    // HSP (Highly Sensitive Poo) equation from http://alienryderflex.com/hsp.html
    hsp = Math.sqrt(
        0.299 * (r * r) +
        0.587 * (g * g) +
        0.114 * (b * b)
    );

    // Using the HSP value, determine whether the color is light or dark
    if (hsp > 127.5) {
        return 'light';
    } else {
        return 'dark';
    }
}

function changeElColor(el, color) {
    brightness = lightOrDark(color);
    if (brightness == 'dark') {
        $(el).css("backgroundColor", color);
        $(el).css("color", "white");
    } else {
        if (color == "#FFFFFF") {
            $(el).css("backgroundColor", "#e6e6e6");
            $(el).css("color", "#484848");
        } else {
            $(el).css("backgroundColor", color);
            $(el).css("color", "#484848");
        }
    }
}

function changeSelectTextColor(el) {
    if (el.value == "")
        el.style.color = "rbg(106,106,106)";
    else
        el.style.color = "#333";
}

function hideIfNeed(id) {
    var el = document.getElementById(id);
    if (el.src == "") {
        $(el).hide();
    } else {
        $(el).show();
    }
}

function resetSelectTextColor(id) {
    var el = document.getElementById(id);
    el.removeAttribute("style");
    el.style.color = "rbg(106,106,106)";
}


function buildImagePicker($scope, $compile) {
    modal_picker = $("<div class='modal' id='image-picker' value='#image-picker'></div>");
    modal_picker_content = $("<div class='modal_content' style='display: flex;padding-bottom: 60px;'></div>");
    modal_picker_content.append($('<button class="close_btn icon" value="#image-picker" onclick="closeModal(this)"></button>'));
    //modal_content.append($('<div class="title centered" >Where do you want to pick your image from? </div>'));
    tabs = $('<div class="tab"></div>');
    tabs.append($('<button class="tablinks" onclick="openTab(event,\'upload\')" id="defaultOpen">Upload file</button>'));
    tabs.append($('<button class="tablinks" onclick="openTab(event,\'browse\')">Browse file</button>'));

    upload = $('<div class="tabcontent" id="upload" ></div>');
    upload.append($('<div class="full"><div class="picker">' +
        '<div class="config_input" style="flex: none;"><input style="display: none;" id="upload-picker" type="file" accept=".png, .jpeg, .jpg" class="form__input"/> ' +
        '<input type="button" value="Choose File" onclick="document.getElementById(\'upload-picker\').click();" />' +
        '<span id="text_upload-picker" style="margin-left: 10px;"> No file chosen </span></div> <img id="img_upload-picker"/></div></div>'));


    browse = $('<div class="tabcontent" id="browse" ></div>');

    modal_picker_content.append(tabs);
    modal_picker_content.append(upload);
    modal_picker_content.append(browse);
    modal_picker_content.append($('<button class="cancel" style="right:95px;bottom:10px;" value="#image-picker" onclick="closeModal(this)" > Cancel </button>'));
    modal_picker_content.append($('<button class="save_btn" style="right:15px;bottom:10px;" value="#image-picker" onclick="closeModal(this)" ng-click="saveChosenImage()"> Save </button>'))
    modal_picker.append(modal_picker_content);
    $compile(modal_picker)($scope);
    return modal_picker;
    //document.getElementById("defaultOpen").click();
};

function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

// function moveRow(containerId, oldIdx, newIdx, atributes, item) {
//     // children[0] - gives tbody; children gives the rows
//     // cut the first child because the header is inside tbody
//     var rows = Array.from(document.getElementById(containerId).children[0].children).slice(1);

//     if (containerId == "listing-table") {
//         var myTier = rows[oldIdx].children[0].innerHTML;
//         if (newIdx < 0 || newIdx > rows.length - 1 || rows[newIdx].children[0].innerHTML != myTier)
//             return false;


//         var tbody = $(document.getElementById(containerId).children[0]);
//         var oldRow = rows[oldIdx];
//         var newRow = rows[newIdx];


//         $(tbody.children().get(oldIdx + 1)).detach();
//         //$('#listing-table > tbody > tr').get(newIdx).after(oldRow);
//         return true;
//     } else {
//         if (newIdx < 0 || newIdx > rows.length - 1)
//             return false;

//         var oldRow = rows[oldIdx];
//         // var newRow = rows[newIdx];

//         var tbody = $(document.getElementById(containerId).children[0]);

//         $(tbody.children().get(oldIdx + 1)).remove();
//         $('#tier-table > tbody > tr').eq(newIdx).after(oldRow);
//         return true;
// //     }




// }