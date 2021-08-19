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

//triggers download of plaintext file
function downloadPlainText(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

//triggers download of zip file
function downloadZip(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:application/zip;charset=utf-8,' + encodeURIComponent(text));
    console.log(filename);
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

function createSection(parent, title, id = "") {
    var sec = $('<div>', { 'class': 'section' });
    if (id)
        sec.attr("id", id);
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
        '<div class="config_input" style="flex: none;"><input style="display: none;" id="upload-picker" type="file" class="form__input"/> ' +
        '<input type="button" value="Choose File" onclick="document.getElementById(\'upload-picker\').click();" />' +
        '<span id="text-upload-picker" style="margin-left: 10px;"> No file chosen </span></div><div class="file" id="div-upload-picker" style="display:inline-block;margin-left: 20px;margin-top: 10px;"> <img id="img-upload-picker" style="width: 100px; height: 100px;"/></div></div></div>'));


    browse = $('<div class="tabcontent" id="browse" ></div>');
    browseContainer = populateBrowseFolders($scope);
    browse.append($('<div class="top_browser"><div id="back" ></div><div class="path"><span id="browse-path" >' + $scope.path + '</span></div></div>'));
    browse.append(browseContainer);

    modal_picker_content.append(tabs);
    modal_picker_content.append(upload);
    modal_picker_content.append(browse);
    modal_picker_content.append($('<button id="delete" value="#delete-verification-file" style="left:60px;bottom:10px;" > Delete </button>'));
    modal_picker_content.append($('<button class="cancel_btn" style="right:105px;bottom:10px;" value="#image-picker" onclick="closeModal(this)" > Cancel </button>'));
    modal_picker_content.append($('<button class="save_btn" id="save-picker" style="right:15px;bottom:10px;" value="#image-picker" onclick="closeModal(this);" ng-click="saveChosenImage()"> Select </button>'))
    modal_picker.append(modal_picker_content);

    //delete verification modal
    deletemodal = $("<div class='modal' id='delete-verification-file'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append($('<button class="close_btn icon" value="#delete-verification-file" onclick="closeModal(this)"></button>'));
    verification.append($('<div class="warning">Are you sure you want to delete?</div>'));
    verification.append($('<div class="target" id="delete_file_info"></div>'));
    verification.append($('<div class="confirmation_btns"><button class="cancel" value="#delete-verification-file" onclick="closeModal(this)">Cancel</button><button class="continue" value="#delete-verification-file" id="confirm_delete"> Delete</button></div>'))
    deletemodal.append(verification);
    modal_picker.append(deletemodal);
    //$compile(deletemodal)($scope);

    $compile(modal_picker)($scope);
    return modal_picker;
};

function populateBrowseFolders($scope, folder = "", isBack = false, isDelete = false) {
    $("#browse-grid").remove();
    if (isBack) {
        var temp = $scope.path.split("/").slice(0, $scope.path.split("/").length - 1);
        $scope.path = temp.join("/");
    } else if (!isBack && folder != "" && !isDelete) {
        $scope.path = $scope.path + "/" + folder;
    }

    $("#browse-path").text($scope.path);

    let files = $scope.folders;
    if (folder != "" || isBack) {
        var path_folders = $scope.path.split("/").slice(2);
        for (i = 0; i < path_folders.length; i++) {
            files = files[path_folders[i]].files;
        }
    }
    browseContainer = $('<div class="grid" id="browse-grid" ></div>');
    jQuery.each(files, function (index) {
        file = files[index];
        switch (file.filetype) {
            case 'file':
                if ($scope.allowedExtensions.length == 0 || $scope.allowedExtensions.includes(file.extension)) {
                    if (file.extension != ".png" && file.extension != ".jpeg" && file.extension != ".jpg" && file.extension != ".gif") {
                        browseContainer.append($('<div class="square file"><img class="square-image" style="width: 60px; height: 60px;" src="images/file.svg"/><span>' + file.name + '</span></div>'))
                    } else {
                        browseContainer.append($('<div class="square file"><img class="square-image" style="width: 60px; height: 60px;" src="' + $scope.path + "/" + file.name + '"/><span>' + file.name + '</span></div>'))
                    }
                }

                break;
            case 'folder':
                browseContainer.append($('<div class="square folder" value="' + file.name + '"><img style="width: 60px; height: 60px;" src="images/folder.svg"/><span>' + file.name + '</span></div>'))
                break;
        }
    });
    return browseContainer;
}

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

function openImagePicker($scope, $smartboards) {

    //const imageUploaded = document.getElementById("img-upload-picker");

    const modal = document.getElementById("image-picker");
    openModal(modal);
    document.getElementById("defaultOpen").click();

    //file input
    document.getElementById("upload-picker").onchange = function () {
        chooseFileFromPC($scope, $smartboards);
    }
    const allowed = $scope.allowedExtensions.join(", ");
    document.getElementById("upload-picker").setAttribute("accept", allowed);

    //reset
    resetUploadImage("upload-picker");
    if ($("#browse-path").html() != $scope.courseFolder) {
        $scope.path = $scope.courseFolder;
        browseContainer = populateBrowseFolders($scope, "");
        $("#browse").append(browseContainer);
    }

    //delete file and refresh view
    $(document.getElementById("delete")).hide();
    document.getElementById("delete").onclick = function () {
        openModal(this);
        const src = $scope.selectedFile.children[0].src.split("/");
        $('#delete_file_info').text('File: ' + src[src.length - 1]);
        //delete confirmation
        document.getElementById("confirm_delete").onclick = function () {
            confirmDelete($scope, $smartboards);
            closeModal(this);
        }
    }

    // //save file
    // document.getElementById("save-picker").onclick = function () {
    //     closeModal(this);
    //     if (imageUploaded.src != "")
    //         resetUploadImage('img-upload-picker');
    //     //saveImage($scope, $smartboards, itemId);
    //     if (imageUploaded.src != "" && imageUploaded.style.borderColor != "rgb(255, 255, 255)") {
    //         if ($scope.module && $scope.module.name == "Skills") {
    //             $scope.insertToEditor(imageUploaded.src);
    //         }
    //     } else {
    //         document.getElementsByClassName("square-image").forEach(element => {
    //             if ($(element).css("borderColor") != "rgb(255, 255, 255)") {
    //                 document.getElementById(itemId).src = element.src;
    //                 hideIfNeed(itemId);
    //             }
    //         });
    //     }
    // }

    //back button
    document.getElementById("back").onclick = function () {
        if ($scope.path != $scope.courseFolder) {
            browseContainer = populateBrowseFolders($scope, "", true);
            $("#browse").append(browseContainer);
            setClickEvent($scope);
            setClickOnFiles($scope);
        }
    }

    // folder click
    setClickEvent($scope);
    setClickOnFiles($scope);
}

function confirmDelete($scope, $smartboards) {
    document.getElementsByClassName("file").forEach(element => {
        if ($(element).css("borderColor") != "rgb(255, 255, 255)") {
            const divider = $scope.courseFolder.split("/")[1].replaceAll(" ", "%20"); // to match url spaces
            const path = element.children[0].src.split(divider)[1];
            $smartboards.request('settings', 'deleteFile', { course: $scope.course, path: path }, function (data, err) {
                if (err) {
                    giveMessage(err);
                    return;
                }
                if ($("#upload").css("display") == "block") {
                    resetUploadImage("upload-picker");
                }
                $smartboards.request('course', 'getDataFolders', { course: $scope.course }, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    $scope.folders = data.folders;
                    const folder = path.split("/")[1];
                    browseContainer = populateBrowseFolders($scope, folder, false, true);
                    $("#browse").append(browseContainer);
                    setClickEvent($scope);
                    setClickOnFiles($scope);
                    $(document.getElementById("delete")).hide();
                });
            });
        }
    });
}

function setClickEvent($scope) {
    document.getElementsByClassName("folder").forEach(element => {
        element.onclick = function () {
            browseContainer = populateBrowseFolders($scope, element.getAttribute("value"));
            $("#browse").append(browseContainer);
            setClickEvent($scope);
            setClickOnFiles($scope);
        }
    });
}

function setClickOnFiles($scope) {
    document.getElementsByClassName("file").forEach(element => {
        element.onclick = function () {
            // remove border from previous selected
            if ($scope.selectedFile != null)
                changeBorderColor($scope.selectedFile);
            // first click or when there is another one selected
            else if ($scope.selectedFile == null || $scope.selectedFile != element) {
                changeBorderColor(element);
                $scope.selectedFile = element;
            }
            // none selected
            else {
                $scope.selectedFile = null;
            }
        }
    })
}

function changeBorderColor(element) {
    if ($(element).css("borderColor") == "rgb(255, 255, 255)") {
        element.style.borderColor = "#0070f9";
        $(document.getElementById("delete")).show();
    } else {
        element.style.borderColor = "rgb(255, 255, 255)";
        $(document.getElementById("delete")).hide();
    }
}

function chooseFileFromPC($scope, $smartboards) {
    const input = document.getElementById("upload-picker");
    const file = document.getElementById(input.id).files[0];
    if (file == undefined)
        return;
    const filename = file.name;
    let subfolder;

    if ($scope.openItem) {
        subfolder = $scope.openItem.name;
    } else {
        subfolder = $scope.selectedInput.charAt(0).toUpperCase() + $scope.selectedInput.slice(1);
    }

    $(".config_input #text-" + input.id).text(filename);
    var reader = new FileReader();
    reader.onload = function (e) {
        $scope.uploadFile = reader.result;
        $smartboards.request('settings', 'upload', { course: $scope.course, newFile: $scope.uploadFile, fileName: filename, module: $scope.module.name, subfolder: subfolder }, function (data, err) {
            if (err) {
                giveMessage(err.description);
                return;
            }
            if (data.url != 0) {
                document.getElementById('img-upload-picker').src = data.url;
                hideIfNeed('img-upload-picker');
                // document.getElementById(itemId).src = data.url;
                // hideIfNeed(itemId);
                // $(".config_input #text-" + itemId).text(file["name"]);
                //insertToEditor(data.url);// Display image element
            } else {
                alert('file not uploaded');
            }
        }
        );
    }
    reader.readAsDataURL(file);
};

// function saveImage($scope, $smartboards, itemId) {
//     const imageUploaded = document.getElementById("img-upload-picker");
//     if (imageUploaded.src != "" && imageUploaded.style.borderColor != "rgb(255, 255, 255)") {

//     }
// }

function resetUploadImage(id) {
    var element = document.getElementById("img-" + id);
    element.removeAttribute("src");
    hideIfNeed("img-" + id);
    document.getElementById("div-" + id).style.borderColor = "rgb(255, 255, 255)";
    $(".config_input #text-" + id).text("No file chosen");
}

// parse role from role.Default to Default or role.Default>role.Default to Default>Default
function parseRole(role) {
    if (role.includes(">")) {
        var viewer = role.split(">")[1].split(".")[1];
        var user = role.split(">")[0].split(".")[1];
        return user + ">" + viewer;
    } else {
        return role.split(".")[1];
    }
}

// (un)parse role from Default to role.Default or Default>Default to role.Default>role.Default
function unparseRole(role) {
    if (role.includes(">")) {
        var viewer = "role." + role.split(">")[1];
        var user = "role." + role.split(">")[0];
        return user + ">" + viewer;
    } else {
        return "role." + role;
    }
}

//get viewer
function getViewerFromRole(role, parsed = false) {
    if (!parsed) {
        if (!role.includes("."))
            role = unparseRole(role);
    }
    if (role.includes(">")) {
        return role.split(">")[1];
    } else {
        return role;
    }
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