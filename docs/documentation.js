//functions to interact on the documentation pages

function showContent(selector, name) {
    //define the selected tab
    $(prevTab).removeClass("selected");
    $(selector).addClass("selected");
    prevTab = selector;

    // define the visible section
    $(prevSection).removeClass("visible");
    $(name).addClass("visible");
    prevSection = name;
} 

function addClickEventsViews(){
    prevTab = "#tab-views";
    prevSection = "#views";
    prevMenu = "#menu-views";

    tabs = ["#tab-views","#tab-parts","#tab-exp-language","#tab-config"]
    sections = ["#views","#view-parts","#expression-language","#part-configuration"]

    //tabs onclick events
    jQuery.each(tabs , function( index ) {
        $(tabs[index]).on('click', function (){ 
            showContent( tabs[index],sections[index]);}
        );
    });
    //search
    bindSearch();
}

function addClickEventsModules(){
    prevTab = "#tab-create";
    prevSection = "#create";
    prevMenu = "#menu-modules";
    
    tabs = ["#tab-create","#tab-init","#tab-resources","#tab-data"]
    sections = ["#create","#init","#resources","#data"]    

    //tabs onclick events
    jQuery.each(tabs , function( index ) {
        $(tabs[index]).on('click', function (){ 
            showContent( tabs[index],sections[index]);}
        );
    });
    //search
    bindSearch();
}

function changeMenu(new_menu){
    menu = $(new_menu);
    page = $(".page");

    //focus right menu option
    $(prevMenu).children().first().removeClass("focused");
    menu.children().first().addClass("focused");

    //reveal right partial
    switch (new_menu){
        case "#menu-views":
            php_div = 'pages/plugins/views.html';
            page.load(php_div, function(){
                addClickEventsViews();
            });
            break;
        case "#menu-modules":
            php_div = 'pages/plugins/modules.html';
            page.load(php_div, function(){
                addClickEventsModules();
            });
            break;
    }
    prevMenu = new_menu;
}

function clearSearch(){
    //remove highlight from content
    $(".search-found").contents().unwrap();
    //remove highlight from tags
    $(".search-notification").remove();
    //remove error for not found
    $(".search-error").remove();
}

function searchChild(item, look, text){
    //for it to work at 100%
    //all text must be inside a div with no children
    console.log("searchChild");
    childs = item.children();
    if (childs.size() > 0){
        jQuery.each(childs , function( index ) {
            searchChild($(this), look, text);
            return;
        });
    }
    else {
        $(item).html( $(item).html().replace(look, '<span class="search-found">'+ text +'</span>') );
        return;
    }
}

function search(){
    clearSearch();

    text = $("#find-tab").children()[0].value
    exp = /^ *$/; //matches white spaces and empty string

    if (!exp.test(text)){
        console.log("search: "+ text);
        look = new RegExp(text, 'gi');

        //verifies all content for matches
        searchChild($('.content'), look, text);

        //old method, replaces html elements like div if text is div
        //$('.content').html( $('.content').html().replace(look, '<span class="search-found">'+ text +'</span>') );

        //add tab highlights for those with matches
        first = true;
        jQuery.each(tabs , function( index ) {
            if($(sections[index]).find(".search-found").size() > 0){
                $(tabs[index]).prepend("<div class='search-notification'></div>")
                if (first){
                    $(tabs[index]).click();
                    first = false;
                }
            }
        });
        if (first){
            //no match foound
            $('#find-tab').append("<div class='search-error'>No matches</div>")
        }
    }
    
}

function bindSearch(){
    //by enter
    $("input[name ='search']").on('keyup', function (e) {
        if (e.keyCode === 13) {
            search();
        }
    });

    //by button
    $("#search-btn").on('click', function (){ 
        search();}
    );
}

$(document).ready(function(){
    addClickEventsViews();
    
    $("#menu-views").on('click', function (){ 
        changeMenu("#menu-views");}
    );
    $("#menu-modules").on('click', function (){ 
        changeMenu("#menu-modules");}
    );

});

