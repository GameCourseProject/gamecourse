
function createSidebar( optionsFilter, optionsOrder){
  sidebar = $("<div class='sidebar'></div>");
  search = $("<div class='search'> <input type='text' id='seach_input' placeholder='Search..' name='search' ng-change='reduceList()' ng-model='search' ><button class='magnifying-glass' id='search-btn' ng-click='reduceList()'></button>  </div>")
  sidebar.append(search);

  
  if (optionsFilter.length > 0 ){
    filter = $(" <div class='filter'> <div class='title'>Filter</div> </div>");
    jQuery.each(optionsFilter, function(index){
      filter.append( $("<label class='container'>" + optionsFilter[index] + " <input type='checkbox' checked='checked' id='filter-" + optionsFilter[index] + "' ng-change='reduceList()' ng-model='filter"+optionsFilter[index]+"'><span class='checkmark'></span>   </label>"));
    });
    sidebar.append(filter);
  }

  if (optionsOrder.length > 0 ){
    oderby = $("<div class='order-by'> <div class='title'>Order by</div></div>");
    jQuery.each(optionsOrder, function(index){
        if (index == 0){
          oderby.append( $("<label class='container'>" + optionsOrder[index] + " <input type='radio' checked='checked' name='radio' id='" + transformNameToId(optionsOrder[index]) + "' ng-click='orderList()'><span class='checkmark'></span>   </label>"));
        }else{
          oderby.append( $("<label class='container'>" + optionsOrder[index] + " <input type='radio' name='radio' id='" + transformNameToId(optionsOrder[index]) + "' ng-click='orderList()'><span class='checkmark'></span>   </label>"));
        }
      });
    oderby.append( $( '<div class="sort"><div class="triangle checked" id="triangle-up" ng-click="sortUp(); orderList();"></div><div class="triangle" id="triangle-down" ng-click="sortDown(); orderList();"></div></div>' ));
    sidebar.append(oderby);
  }

  return sidebar;
}

function transformNameToId(name){
  var res = name.replace(" ", "-");
  res = res.replace("#", "N");
  id = "order-" + res;
  return id;
}
function getNameFromId(id){
  var res = id.replace("order-", "");
  var name = res.replace("-", " ");
  return name;
}


//aux funtions to order by a attibute
function orberByName(a, b) {
  if (a.name > b.name) { return 1;}
  if (a.name < b.name) { return -1;}
  // a must be equal to b
  return 0;
}
function orberByNickname(a, b) {
  if (a.nickname > b.nickname) { return 1;}
  if (a.nickname < b.nickname) { return -1;}
  return 0;
}
function orberByStudentNumber(a, b) {
  if (a.studentNumber > b.studentNumber) { return 1;}
  if (a.studentNumber < b.studentNumber) { return -1;}
  return 0;
}
function orberByLastLgin(a, b) {
  if (a.lastLogin > b.lastLogin) { return 1;}
  if (a.lastLogin < b.lastLogin) { return -1;}
  return 0;
}
function orberByNCourses(a, b) {
  if (a.ncourses > b.ncourses) { return 1;}
  if (a.ncourses < b.ncourses) { return -1;}
  return 0;
}
function orberByShort(a, b) {
  if (a.short > b.short) { return 1;}
  if (a.short < b.short) { return -1;}
  return 0;
}
function orberByYear(a, b) {
  if (a.year > b.year) { return 1;}
  if (a.year < b.year) { return -1;}
  return 0;
}
function orberByYear(a, b) {
  if (a.year > b.year) { return 1;}
  if (a.year < b.year) { return -1;}
  return 0;
}
function orberByNStudents(a, b) {
  if (a.nstudents > b.nstudents) { return 1;}
  if (a.nstudents < b.nstudents) { return -1;}
  return 0;
}


function validateSearch(text){
  exp = /^ *$/; //matches white spaces and empty string
  if (text == "" || text== null){
    return false;
  }
  else if (exp.test(text)){
    return false;
  }
  else{
    return true;
  }
}