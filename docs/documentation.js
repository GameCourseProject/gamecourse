//functions to interact on the documentation pages

function showContent(selector, name) {
    //define the selected tab
    tab = document.getElementById(prevTab);
    tab.classList.remove("selected");
    document.getElementById(selector).classList.add("selected");
    prevTab = selector;

    // define the visible section
    section = document.getElementById(prevSection);
    section.classList.remove("visible");
    document.getElementById(name).classList.add("visible");
    prevSection = name;
} 

function addClickEventsViews(){
    prevTab = "tab-views";
    prevSection = "views";
    prevMenu = "#menu-views";

    $("#tab-views").on('click', function (){ 
        showContent( "tab-views","views");}
    );
    $("#tab-parts").on('click', function (){ 
        showContent("tab-parts", "view-parts");}
    );
    $("#tab-exp-language").on('click', function (){ 
        showContent("tab-exp-language", "expression-language");}
    );
    $("#tab-config").on('click', function (){ 
        showContent("tab-config", "part-configuration");}
    );
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
            console.log("views menu");
            php_div = 'pages/plugins/views.html';
            page.load(php_div, function(){
                addClickEventsViews();
            });
            break;
        case "#menu-module":
            console.log("module menu");
            php_div = 'pages/modules.html';
            page.load(php_div);
            break;
    }
    prevMenu = new_menu;
}

$(document).ready(function(){
    addClickEventsViews();
    
    $("#menu-views").on('click', function (){ 
        changeMenu("#menu-views");}
    );
    $("#menu-module").on('click', function (){ 
        changeMenu("#menu-module");}
    );


});

