function addClickEvents(){
    prevTab = "";
    prevSection = "";
    
    tabs = []
    sections = []    

    //tabs onclick events
    jQuery.each(tabs , function( index ) {
        $(tabs[index]).on('click', function (){ 
            showContent( tabs[index],sections[index]);}
        );
    });
}