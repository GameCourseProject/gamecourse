prevTab = "";
prevSection = "";

tabs = []
sections = []

function addClickEvents() {
    //tabs onclick events
    jQuery.each(tabs, function (index) {
        $(tabs[index]).on('click', function () {
            showContent(tabs[index], sections[index]);
        }
        );
    });

    //open first
    $(tabs[0]).click();
}

function clearFunctions() {
    $(".course-related").remove();
}

function reduceNameToId(name) {
    id = name.replace(/\s/g, '-');
    return id;
}
function addTabGroup() {
    sidebar = $(".sidebar");
    group = jQuery('<div/>', {
        "class": 'tabgroup course-related'
    });
    sidebar.append(group);
}
function addTab(name) {
    sidebar = $(".tabgroup");
    div = '<div class="tab course-related" id="tab-' + reduceNameToId(name) + '">' + name + '</div>';
    sidebar.append(div);
    tab = "#tab-" + reduceNameToId(name);
    tabs.push(tab);
}

function addContent(lib) {
    content = $(".content");
    div = jQuery('<div/>', {
        id: reduceNameToId(lib.name),
        "class": 'section course-related'
    });
    title = jQuery('<h2/>', {
        "class": 'title',
        text: lib.name
    });
    description = jQuery('<div/>', {
        "class": 'description',
        text: lib.desc
    });
    functions = jQuery('<div/>', {
        "class": 'accordion js-accordion'
    });
    jQuery.each(lib.functions, function (func_call, func_desc) {
        func = jQuery('<div/>', {
            "class": 'accordion__item js-accordion-item'
        });
        header = jQuery('<div/>', {
            "class": 'accordion-header js-accordion-header',
            text: func_call
        });
        body = jQuery('<div/>', {
            "class": 'accordion-body js-accordion-body'
        });
        line = jQuery('<div/>', {
            "class": 'line'
        });
        if (func_desc) {

            func_content = jQuery('<div/>', {
                "class": 'accordion-body__contents',
                html: func_desc.replaceAll("\n","<br>&nbsp&nbsp&nbsp&nbsp- ") 
                });
        } else {
            func_content = jQuery('<div/>', {
                "class": 'accordion-body__contents',
                text: func_desc
            });
        }

        body.append(line);
        body.append(func_content);
        func.append(header);
        func.append(body);
        functions.append(func);
    });

    div.append(title);
    div.append(description);
    div.append(functions);
    content.append(div);

    sections.push("#" + reduceNameToId(lib.name));
}

//code got from a codepen example
var accordion = (function () {

    // default settings 
    var settings = {
        // animation speed
        speed: 400,
        // close all other accordion items if true
        oneOpen: false
    };

    return {
        // pass configurable object literal
        init: function ($settings) {
            var $accordion = $('.js-accordion');
            var $accordion_header = $accordion.find('.js-accordion-header');
            var $accordion_item = $('.js-accordion-item');

            $accordion_header.on('click', function () {
                accordion.toggle($(this));
            });

            $.extend(settings, $settings);

            // ensure only one accordion is active if oneOpen is true
            if (settings.oneOpen && $('.js-accordion-item.active').length > 1) {
                $('.js-accordion-item.active:not(:first)').removeClass('active');
            }

            // reveal the active accordion bodies
            $('.js-accordion-item.active').find('> .js-accordion-body').show();
        },
        toggle: function ($this) {

            if (settings.oneOpen && $this[0] != $this.closest('.js-accordion').find('> .js-accordion-item.active > .js-accordion-header')[0]) {
                $this.closest('.js-accordion')
                    .find('> .js-accordion-item')
                    .removeClass('active')
                    .find('.js-accordion-body')
                    .slideUp()
            }

            // show/hide the clicked accordion item
            $this.closest('.js-accordion-item').toggleClass('active');
            $this.next().stop().slideToggle(settings.speed);
        }
    }
})();
