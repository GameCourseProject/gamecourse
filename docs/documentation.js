prevTab = "tab-views";
prevSection = "views";

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

$(document).ready(function(){

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


});

