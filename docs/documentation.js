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
    addClickEvents();
    bindSearch();
});

