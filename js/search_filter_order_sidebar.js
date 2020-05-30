
function createSidebar( optionsFilter, optionsOrder){
  //criar 2 secoes - sidebar e maincontent
  sidebar = $("<div class='sidebar'></div>");
  search = $("<div class='search'> <input type='text' placeholder='Search..' name='search'><button class='magnifying-glass' id='search-btn'></button>  </div>")
  filter = $(" <div class='filter'> <div class='title'>Filter</div> </div>");
  oderby = $("<div class='order-by'> <div class='title'>Order by</div></div>");

  
  jQuery.each(optionsFilter, function(index){
      filter.append( $("<label class='container'>" + optionsFilter[index] + " <input type='checkbox'><span class='checkmark'></span>   </label>"));
  });
  jQuery.each(optionsOrder, function(index){
      oderby.append( $("<label class='container'>" + optionsOrder[index] + " <input type='radio' checked='checked' name='radio'><span class='checkmark'></span>   </label>"));
  });
  oderby.append( $( '<div class="sort"><div class="triangle checked" id="triangle-up"></div><div class="triangle" id="triangle-down"></div></div>' ));
 
  sidebar.append(search);
  sidebar.append(filter);
  sidebar.append(oderby);

  return sidebar;
}



function sortUp() {
  document.getElementById("triangle-up").classList.add("checked");
  
  document.getElementById("triangle-down").classList.remove("checked");
}

function sortDown() {
  document.getElementById("triangle-down").classList.add("checked");
  document.getElementById("triangle-up").classList.remove("checked");
}

$(document).ready(function(){
    document.getElementById("triangle-up").addEventListener("click", sortUp);

    document.getElementById("triangle-down").addEventListener("click", sortDown);

});