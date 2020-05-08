function addClickEvents(){
    prevTab = "#tab-create";
    prevSection = "#create";
    
    tabs = ["#tab-create","#tab-init","#tab-resources","#tab-data"]
    sections = ["#create","#init","#resources","#data"]    

    //tabs onclick events
    jQuery.each(tabs , function( index ) {
        $(tabs[index]).on('click', function (){ 
            showContent( tabs[index],sections[index]);}
        );
    });
}