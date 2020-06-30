function addClickEvents(){
    prevTab = "#tab-views";
    prevSection = "#views";

    tabs = ["#tab-views","#tab-parts","#tab-exp-language","#tab-config"]
    sections = ["#views","#view-parts","#expression-language","#part-configuration"]

    //tabs onclick events
    jQuery.each(tabs , function( index ) {
        $(tabs[index]).on('click', function (){ 
            showContent( tabs[index],sections[index]);}
        );
    });
    
}