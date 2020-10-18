//Controllers for pages of the system, except for setting pages

app.controller('HomePage', function($element, $scope, $timeout) {
    $scope.setNavigation([], []);
    $timeout(function() {
        $scope.defaultNavigation();
        $timeout(function() {
            addActiveLinks('home');
        });
    });
    changeTitle('', 0, false);

    $element.append(Builder.createPageBlock({
        image: 'images/leaderboard.svg',
        text: 'Main Page'
    }, function(el, info) {
        el.append(Builder.buildBlock({
            image: 'images/awards.svg',
            title: 'Welcome'
        }, function(blockContent) {
            var divText = $('<div style="padding: 4px">');
            divText.append('<p>Welcome to the GameCourse system.</p>');
            divText.append('<p>Hope you enjoy!</p>');
            blockContent.append(divText);
        }));
    }));
});

//courses list
app.controller('Courses', function($element, $scope, $smartboards, $compile, $state) {
    $scope.courses = {};
    $scope.coursesActive = [];
    $scope.coursesNotActive = [];
    $scope.coursesActiveAll = [];
    $scope.coursesNotActiveAll = [];
    //changeTitle('Courses', 0);

    $scope.deleteCourse = function(course) {
        $("#action_completed").empty();
        $smartboards.request('core', 'deleteCourse', {course: course.id}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            getCourses(); //closes the modal
            $("#action_completed").append("Course: " + course.name + " deleted");
            $("#action_completed").show().delay(3000).fadeOut();
        });
    };

    $scope.visibleCouse = function( course_id){
        id = "#visible-" + course_id + ":checked";
        if($(id).length > 0){
            $visible = 0; //false
        }
        else{
            $visible = 1; //true
        }
        $smartboards.request('core', 'setCoursesvisibility', {course_id: course_id, visibility: $visible}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            $scope.courses.find(x => x.id === course_id).isVisible = $visible;
            $scope.allCourses.find(x => x.id === course_id).isVisible = $visible;
        });
    }

    $scope.activeCouse = function( course_id){
        id = "#active-" + course_id + ":checked"; 
        if($(id).length > 0){
            $active = 0; //false
        }
        else{
            $active = 1; //true
        }
        $smartboards.request('core', 'setCoursesActive', {course_id: course_id, active: $active}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            $scope.courses.find(x => x.id === course_id).isActive = $active;
            $scope.allCourses.find(x => x.id === course_id).isActive = $active;
        });
    }

    $scope.reduceList = function(){
        if($scope.usingMyCourses){
            $("#empty_active").empty();
            $("#empty_notactive").empty();
            $scope.coursesActive = $scope.coursesActiveAll.slice();
            $scope.coursesNotActive = $scope.coursesNotActiveAll.slice();
            $scope.searchList();
        }
        else{
            $("#empty_table").empty();
            $("#courses-table").show();
            $scope.courses = $scope.allCourses.slice();
            $scope.searchList();
            $scope.filterList();
        }
        
    }

    $scope.searchList = function(){
        if($scope.usingMyCourses){
            text = $scope.search;
            if (validateSearch(text)){
                newActive = [];
                newNotActive = [];
                jQuery.each($scope.coursesActive , function( index ){
                    course = $scope.coursesActive[index];
                    //a.toLowerCase().includes(text.toLowerCase())
                    if (course.name.toLowerCase().includes(text.toLowerCase())
                    || course.short.toLowerCase().includes(text.toLowerCase())
                    || course.year.toLowerCase().includes(text.toLowerCase())){
                        newActive.push(course);
                    }
                });
                jQuery.each($scope.coursesNotActive , function( index ){
                    course = $scope.coursesNotActive[index];
                    if (course.name.toLowerCase().includes(text.toLowerCase())
                    || course.short.toLowerCase().includes(text.toLowerCase())
                    || course.year.toLowerCase().includes(text.toLowerCase())){
                        newNotActive.push(course);
                    }
                });
                if(newActive.length == 0){
                    $("#empty_active").append("No matches found");
                }
                if(newNotActive.length == 0){
                    $("#empty_notactive").append("No matches found");
                }

                $scope.coursesActive = newActive;
                $scope.coursesNotActive = newNotActive;
            }
        }else{
            filteredCourses = [];
            text = $scope.search;
            if (validateSearch(text)){
                //match por name e short
                jQuery.each($scope.courses , function( index ){
                    course = $scope.courses[index];
                    if (course.name.toLowerCase().includes(text.toLowerCase())
                    || course.short.toLowerCase().includes(text.toLowerCase())
                    || course.year.toLowerCase().includes(text.toLowerCase())){
                        filteredCourses.push(course);
                    }
                });
                if(filteredCourses.length == 0){
                    $("#courses-table").hide();
                    $("#empty_table").append("No matches found");
                }
                $scope.courses = filteredCourses;
            }
        }
        
    }

    //only used on admin version
    $scope.filterList = function(){
        active = $scope.filterActive;
        inactive = $scope.filterInactive;
        visible = $scope.filterVisible;
        invisible = $scope.filterInvisible;

        //reset list of courses
        coursesList = $scope.courses;
        filteredCourses = [];
        error_msg = "";

        //cases of empty result
        if (!active && !inactive){
            //inserir aviso
            //filteredCourses is empty
            error_msg = "You must select at least one of the options: Active or Inactive"
        }
        else if(!visible & !invisible){
            //inserir aviso
            //filteredCourses is empty
            error_msg = "You must select at least one of the options: Visible or Invisible"
        }
        else if(active && inactive && visible & invisible){
            filteredCourses = coursesList;
        }
        else{
            jQuery.each(coursesList , function( index ) {
                course = coursesList[index];
                validA = false;
                validV = false;
                if (course.isActive == true && active){
                    validA = true;
                }
                else if ( course.isActive == false && inactive){
                    validA = true;
                }
                if (validA && course.isVisible == true && visible){
                    validV = true;
                }
                else if(validA && course.isVisible == false && invisible){
                    validV = true;
                }

                if (validA && validV){
                    filteredCourses.push(course);
                }
            });
        
            if (filteredCourses.length == 0){
                //nao ha nada para o filtro aplicado
                error_msg = "No matches found for your filter"
            }
        }
        if(error_msg != ""){
            $("#courses-table").hide();
            $("#empty_table").append(error_msg);
        }
        $scope.courses = filteredCourses;
    }

    //functions to visually change the "order by" arrows
    $scope.sortUp = function(){
        document.getElementById("triangle-up").classList.add("checked");
        document.getElementById("triangle-down").classList.remove("checked");
    }
    $scope.sortDown = function() {
        document.getElementById("triangle-down").classList.add("checked");
        document.getElementById("triangle-up").classList.remove("checked");
    }

    $scope.orderList = function(){
        order_by_id = $('input[type=radio]:checked', ".order-by")[0].id;
        order = getNameFromId(order_by_id);
        up = $("#triangle-up").hasClass("checked");

        if (up){ arrow = "up";}
        else{ arrow = "down";}

        if ($scope.lastOrder =="none" || $scope.lastOrder != order){
            switch (order){
                //default sort made with arrow down
                case "Name":
                    $scope.courses.sort(orberByName);
                    $scope.allCourses.sort(orberByName);
                    $scope.coursesActive.sort(orberByName);
                    $scope.coursesNotActive.sort(orberByName);
                    break;
                case "Short":
                    $scope.courses.sort(orberByShort);
                    $scope.allCourses.sort(orberByShort);
                    $scope.coursesActive.sort(orberByShort);
                    $scope.coursesNotActive.sort(orberByShort);
                    break;
                case "N Students":
                    $scope.courses.sort(orberByNStudents);
                    $scope.allCourses.sort(orberByNStudents);
                    $scope.coursesActive.sort(orberByNStudents);
                    $scope.coursesNotActive.sort(orberByNStudents);
                    break;
                case "Year":
                    $scope.courses.sort(orberByYear);
                    $scope.allCourses.sort(orberByYear);
                    $scope.coursesActive.sort(orberByYear);
                    $scope.coursesNotActive.sort(orberByYear);
                    break;
            }
            if (up){ 
                $scope.courses.reverse();
                $scope.allCourses.reverse();
                $scope.coursesActive.reverse();
                $scope.coursesNotActive.reverse();
            }

        }else{
            if (arrow ==  $scope.lastArrow){
                //nothing changes
                return;
            }
            else{
                //only the ascendent/descent order changed
                $scope.courses.reverse();
                $scope.allCourses.reverse();
                $scope.coursesActive.reverse();
                $scope.coursesNotActive.reverse();
            }

        }

        //set values of the existing orderby
        $scope.lastOrder = order;
        $scope.lastArrow = arrow;
    }

    $scope.createCourse = function(){
        $("#action_completed").empty();
        $scope.newCourse = {};
        //inputs start not checked
        $scope.newCourse.courseIsActive = false;
        $scope.newCourse.courseIsVisible = false;

        const inputElement_colorPicker = document.querySelector('#new_pickr');
        const color_sample = $("#color-sample");
        const pickr = new Pickr({
            el: inputElement_colorPicker,
            useAsButton: true,
            default: '#ffffff',
            theme: 'monolith',
            components: {
                hue: true,
                interaction: { input: true, save: true }
            }
        }).on('init', pickr => {
            inputElement_colorPicker.value = pickr.getSelectedColor().toHEXA().toString(0);
        }).on('save', color => {
            inputElement_colorPicker.value = color.toHEXA().toString(0);
            pickr.hide();
        }).on('change', color => {
            inputElement_colorPicker.value = color.toHEXA().toString(0);
            color_sample[0].children[0].style.backgroundColor = color.toHEXA().toString(0);
            color_sample[0].children[1].style.borderColor = color.toHEXA().toString(0);
        })

        $scope.isReadyToSubmit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            //validate inputs
            if (isValid($scope.newCourse.courseName) &&
            isValid($scope.newCourse.courseShort) &&
            isValid($scope.newCourse.courseYear) ){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitCourse = function() {
            courseColor = $("#new_pickr")[0].value;
            isActive = $scope.newCourse.courseIsActive ? 1 : 0; //to transform from true-false
            isVisible = $scope.newCourse.courseIsVisible ? 1 : 0; //same
            var reqData = {
                courseName: $scope.newCourse.courseName,
                courseShort: $scope.newCourse.courseShort,
                courseYear: $scope.newCourse.courseYear,
                courseColor: courseColor,
                courseIsActive: isActive,
                courseIsVisible: isVisible,
                creationMode: 'blank'
            };
            $smartboards.request('core', 'createCourse', reqData, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }
                $("#new-course").hide();
                getCourses();
                $("#action_completed").append("New course created");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        };
    }

    $scope.modifyCourse = function(course){
        $("#action_completed").empty();
        $("#active_visible_inputs").remove();
        $scope.editCourse = {};
        $scope.editCourse.courseId = course.id;
        $scope.editCourse.courseName = course.name;
        $scope.editCourse.courseShort = course.short;
        $scope.editCourse.courseYear = course.year;
        
        //define color preview
        const color_sample = $("#edit-color-sample");
        color_sample[0].children[0].style.backgroundColor = course.color;
        color_sample[0].children[1].style.borderColor = course.color;

        //on/off inputs
        editbox = $("#edit_box");
        editrow = $('<div class= "row" id="active_visible_inputs"></div>');
        if (course.isActive == true){
            console.log("active checked");
            editrow.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="editCourse.courseIsActive" checked><span class="slider round"></span></label></div>'));
            $scope.editCourse.courseIsActive = true;
        }
        else{
            editrow.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="editCourse.courseIsActive"><span class="slider round"></span></label></div>'));
            $scope.editCourse.courseIsActive = false;
        }
        if (course.isVisible == true){
            console.log("visible checked");
            editrow.append( $('<div class= "on_off"><span>Visible </span><label class="switch"><input id="visible" type="checkbox" ng-model="editCourse.courseIsVisible" checked><span class="slider round"></span></label></div>'))
            $scope.editCourse.courseIsVisible = true;
        }
        else{
            editrow.append( $('<div class= "on_off"><span>Visible </span><label class="switch"><input id="visible" type="checkbox" ng-model="editCourse.courseIsVisible" ><span class="slider round"></span></label></div>'))
            $scope.editCourse.courseIsVisible = false;
        }
        editbox.append(editrow);
        $compile(editbox)($scope);
        


        const inputElement_colorPicker = document.querySelector('#edit_pickr');
        const pickr = new Pickr({
            el: inputElement_colorPicker,
            useAsButton: true,
            default: course.color,
            theme: 'monolith',
            components: {
                hue: true,
                interaction: { input: true, save: true }
            }
        }).on('init', pickr => {
            inputElement_colorPicker.value = pickr.getSelectedColor().toHEXA().toString(0);
        }).on('save', color => {
            inputElement_colorPicker.value = color.toHEXA().toString(0);
            pickr.hide();
        }).on('change', color => {
            inputElement_colorPicker.value = color.toHEXA().toString(0);
            color_sample[0].children[0].style.backgroundColor = color.toHEXA().toString(0);
            color_sample[0].children[1].style.borderColor = color.toHEXA().toString(0);
        })

        $scope.isReadyToEdit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            //validate inputs
            if (isValid($scope.editCourse.courseName) &&
            isValid($scope.editCourse.courseShort) &&
            isValid($scope.editCourse.courseYear) ){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitEditCourse = function() {
            courseColor = $("#edit_pickr")[0].value;
            isActive = $scope.editCourse.courseIsActive ? 1 : 0;
            isVisible = $scope.editCourse.courseIsVisible ? 1 : 0;
            var reqData = {
                courseName: $scope.editCourse.courseName,
                courseShort: $scope.editCourse.courseShort,
                courseYear: $scope.editCourse.courseYear,
                courseColor: courseColor,
                courseIsVisible: isVisible,
                courseIsActive: isActive,
                courseId: $scope.editCourse.courseId
            };
            $smartboards.request('core', 'editCourse', reqData, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }
                $("#edit-course").hide();
                getCourses();
                $("#action_completed").append("Course: "+ $scope.editCourse.courseName + " edited");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        };
    }

    $scope.duplicateCourse = function(course){
        $("#action_completed").empty();

        courseName = course.name +" - Copy";
        var reqData = {
            courseName: courseName,
            courseShort: course.short,
            courseYear: course.year,
            courseColor: course.color,
            courseIsVisible: course.isVisible,
            courseIsActive: course.isActive,
            creationMode: 'similar',
            copyFrom: course.id
        };
        $smartboards.request('core', 'createCourse', reqData, function(data, err) {
            if (err) {
                console.log(err.description);
                //return;  //uncomennt after bug fixed on Course.php
            }
            $("#new-course").hide();
            getCourses();
            $("#action_completed").append("New course created from " + course.name);
            $("#action_completed").show().delay(3000).fadeOut();
        });
    }


    mainContent = $("<div id='mainContent'></div>");

    //each version of the page courses
    allCourses = $("<div id='allCourses' ></div>")
    allCoursesSection = $("<div class='data-table'></div>")
    myCourses = $("<div id='myCourses'></div>")


    //------------------non admin version of the page-------------
    optionsFilter = [];
    optionsOrder = ["Name", "Year"];

    sidebarMy = createSidebar( optionsFilter, optionsOrder);
    $compile(sidebarMy)($scope)    

    var containerActive = createSection($(myCourses),"Active");
    containerActive.attr("id", "active_courses");
    box = $('<div class="card" ng-repeat="(i, course) in coursesActive" ui-sref="course({courseName:course.nameUrl, course: course.id})"></div>');
    box.append( $('<div class="color_box"><div class="box" style="background-color:{{course.color}};"></div> <div  class="frame frame-course" style="border: 1px solid {{course.color}}"><span style="color:{{course.color}};">{{course.name}}</span></div></div>'));
    box.append( $('<div class="footer"><div class="course_name">{{course.short}}</div><div class="course_year">{{course.year}}</div></div>'))
    containerActive.append(box);
    containerActive.append( $("<div class='error_box'><div id='empty_active' class='error_msg'></div></div>"));

    var containerNotActive = createSection($(myCourses),"Not Active");
    containerNotActive.attr("id", "not_active_courses");
    box = $('<div class="card" ng-repeat="(i, course) in coursesNotActive" ui-sref="course({courseName:course.nameUrl, course: course.id})"></div>');
    box.append( $('<div class="color_box"><div class="box" style="background-color:{{course.color}};"></div> <div  class="frame frame-course" style="border: 1px solid {{course.color}}"><span style="color:{{course.color}};">{{course.name}}</span></div></div>'));
    box.append( $('<div class="footer"><div class="course_name">{{course.short}}</div><div class="course_year">{{course.year}}</div></div>'))
    containerNotActive.append(box);
    containerNotActive.append( $("<div class='error_box'><div id='empty_notactive' class='error_msg'></div></div>"));




    //--------------------admin version of the page----------
    //sidebar
    optionsFilter = ["Active", "Inactive", "Visible", "Invisible"];
    optionsOrder = ["Name", "Short","# Students", "Year"];
    //start checkboxs checked, tied by the ng-model in each input
    $scope.filterActive=true;
    $scope.filterInactive=true;
    $scope.filterVisible=true;
    $scope.filterInvisible=true;
    sidebarAll = createSidebar( optionsFilter, optionsOrder);
    $compile(sidebarAll)($scope)

    //table structure
    table = $('<table id="courses-table"></table>');
    rowHeader = $("<tr></tr>");
    header = [{class: "first-column", content: ""},
              {class: "name-column", content: "Name"},
              {class: "", content: "Short"},
              {class: "", content: "# Students"},
              {class: "", content: "Year"},
              {class: "check-column", content: "Active"},
              {class: "check-column", content: "Visible"},
              {class: "action-column", content: ""},
              {class: "action-column", content: ""},
              {class: "action-column", content: ""},
            ];
    jQuery.each(header, function(index){
        rowHeader.append( $("<th class="+ header[index].class + ">" + header[index].content + "</th>"));
    });

    rowContent = $("<tr ng-repeat='(i, course) in courses' id='course-{{course.id}}'> ></tr>");
    rowContent.append('<td class="first-column"><div class="profile-icon"><div class="box" style="background-color:{{course.color}};"></div> <div  class="frame" style="border: 1px solid {{course.color}}"></div></div></td>');
    rowContent.append('<td class="name-column" ui-sref="course({courseName:course.nameUrl, course: course.id})"><span>{{course.name}}</span></td>');
    rowContent.append('<td>{{course.short}}</td>');
    rowContent.append('<td>{{course.nstudents}}</td>');
    rowContent.append('<td>{{course.year}}</td>');
    rowContent.append('<td class="check-column"><label class="switch"><input ng-if="course.isActive == true" id="active-{{course.id}}" type="checkbox" checked><input ng-if="course.isActive == false" id="active-{{course.id}}" type="checkbox"><span ng-click= "activeCouse(course.id)" class="slider round"></span></label></td>');
    rowContent.append('<td class="check-column"><label class="switch"><input ng-if="course.isVisible == true" id="visible-{{course.id}}" type="checkbox" checked><input ng-if="course.isVisible == false" id="visible-{{course.id}}" type="checkbox"><span ng-click= "visibleCouse(course.id)" class="slider round"></span></label></td>');
    rowContent.append('<td class="action-column"><div class="icon duplicate_icon" ng-click="duplicateCourse(course)"></div></td>');
    rowContent.append('<td class="action-column"><div class="icon edit_icon" value="#edit-course" onclick="openModal(this)" ng-click="modifyCourse(course)"></div></td>');
    rowContent.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification-{{course.id}}" onclick="openModal(this)"></div></td>');

    //the verification modals
    modal = $("<div class='modal' id='delete-verification-{{course.id}}'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#delete-verification-{{course.id}}" onclick="closeModal(this)"></button>'));
    verification.append( $('<div class="warning">Are you sure you want to delete the Course?</div>'));
    verification.append( $('<div class="target">{{course.name}}</div>'));
    verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#delete-verification-{{course.id}}" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="deleteCourse(course)"> Delete</button></div>'))
    modal.append(verification);
    rowContent.append(modal);

    //append table
    table.append(rowHeader);
    table.append(rowContent);
    allCoursesSection.append(table);
    allCourses.append(allCoursesSection);

    //error section
    allCourses.append( $("<div class='error_box'><div id='empty_table' class='error_msg'></div></div>"));
    //success section
    success_box = $("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>");

    //action buttons
    action_buttons = $("<div class='action-buttons'></div>");
    action_buttons.append( $("<div class='icon add_icon' value='#new-course' onclick='openModal(this)' ng-click='createCourse()'></div>"));
    action_buttons.append( $("<div class='icon import_icon'></div>"));
    action_buttons.append( $("<div class='icon export_icon'></div>"));
    $compile(action_buttons)($scope);

    //new course modal
    currentYear = new Date().getFullYear();
    yearsOptions = semestersYears(currentYear - 5, currentYear + 5); //options for combobox
    modal = $("<div class='modal' id='new-course'></div>");
    newCourse = $("<div class='modal_content'></div>");
    newCourse.append( $('<button class="close_btn icon" value="#new-course" onclick="closeModal(this)"></button>'));
    newCourse.append( $('<div class="title">New Course: </div>'));
    content = $('<div class="content">');
    box = $('<div class= "inputs">');
    //text inputs
    box.append( $('<div class="name full"><input type="text" class="form__input " id="name" placeholder="Name *" ng-model="newCourse.courseName"/> <label for="name" class="form__label">Name</label></div>'))
    row_inputs = $('<div class= "row_inputs"></div>');
    row_inputs.append($('<div class="short_name half"><input type="text" class="form__input" id="short_name" placeholder="Short Name *" ng-model="newCourse.courseShort"/><label for="sort_name" class="form__label">Short Name</label></div>'))
    //year combobox
    year_section = $('<div class="year half right"></div>');
    year_section.append( $('<input list="years" class="form__input" id="year" placeholder="Year *" ng-model="newCourse.courseYear"/>'))
    year_section.append( $('<label for="year" class="form__label">Year</label>'))
    datalist_options = ( $('<datalist id="years"></datalist>'))
    jQuery.each(yearsOptions, function(index){
        datalist_options.append( $('<option value='+ yearsOptions[index] +'>'));
    });
    year_section.append(datalist_options);
    row_inputs.append(year_section);
    //color picker
    color_picker_section = $('<div class="color_picker half"></div>');
    color_picker_section.append( $('<input type="text" class="form__input pickr" id="new_pickr" placeholder="Color *" ng-model="newCourse.courseColor"/>'));
    color_picker_section.append( $('<label for="color" class="form__label">Color</label>'));
    color_picker_section.append( $('<div id="color-sample"><div class="box" style="background-color: white;"></div><div  class="frame" style="border: 1px solid lightgray"></div>'));
    row_inputs.append(color_picker_section);
    box.append(row_inputs);
    //on/off inputs
    row = $('<div class= "row"></div>');
    row.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="newCourse.courseIsActive"><span class="slider round"></span></label></div>'))
    row.append( $('<div class= "on_off"><span>Visible </span><label class="switch"><input id="visible" type="checkbox" ng-model="newCourse.courseIsVisible"><span class="slider round"></span></label></div>'))
    box.append(row);
    content.append(box);
    content.append( $('<button class="save_btn" ng-click="submitCourse()" ng-disabled="!isReadyToSubmit()" > Save </button>'))
    newCourse.append(content);
    modal.append(newCourse);
    allCourses.append(modal);

    //edit course modal
    editmodal = $("<div class='modal' id='edit-course'></div>");
    editCourse = $("<div class='modal_content'></div>");
    editCourse.append( $('<button class="close_btn icon" value="#edit-course" onclick="closeModal(this)"></button>'));
    editCourse.append( $('<div class="title">Edit Course: </div>'));
    editcontent = $('<div class="content">');
    editbox = $('<div class= "inputs" id="edit_box">');
    //text inputs
    editbox.append( $('<div class="name full"><input type="text" class="form__input " id="name" placeholder="Name *" ng-model="editCourse.courseName"/> <label for="name" class="form__label">Name</label></div>'))
    editrow_inputs = $('<div class= "row_inputs"></div>');
    editrow_inputs.append($('<div class="short_name half"><input type="text" class="form__input" id="short_name" placeholder="Short Name *" ng-model="editCourse.courseShort"/><label for="sort_name" class="form__label">Short Name</label></div>'))
    //year combobox
    edityear_section = $('<div class="year half right"></div>');
    edityear_section.append( $('<input list="years" class="form__input" id="year" placeholder="Year *" ng-model="editCourse.courseYear"/>'))
    edityear_section.append( $('<label for="year" class="form__label">Year</label>'))
    editdatalist_options = ( $('<datalist id="years"></datalist>'))
    jQuery.each(yearsOptions, function(index){
        editdatalist_options.append( $('<option value='+ yearsOptions[index] +'>'));
    });
    edityear_section.append(editdatalist_options);
    editrow_inputs.append(edityear_section);
    //color picker
    editcolor_picker_section = $('<div class="color_picker half"></div>');
    editcolor_picker_section.append( $('<input type="text" class="form__input pickr" id="edit_pickr" placeholder="Color *" ng-model="editCourse.courseColor"/>'));
    editcolor_picker_section.append( $('<label for="color" class="form__label">Color</label>'));
    editcolor_picker_section.append( $('<div id="edit-color-sample"><div class="box" style="background-color: white;"></div><div  class="frame" style="border: 1px solid lightgray"></div>'));
    editrow_inputs.append(editcolor_picker_section);
    editbox.append(editrow_inputs);
    editcontent.append(editbox);
    editcontent.append( $('<button class="save_btn" ng-click="submitEditCourse()" ng-disabled="!isReadyToEdit()" > Save </button>'))
    editCourse.append(editcontent);
    editmodal.append(editCourse);
    allCourses.append(editmodal);


    //compile both version of the page for scope values
    $compile(allCourses)($scope);
    $compile(myCourses)($scope);

    
    //it is in a function so it can be called after add new course, delete or edit
    getCourses = function(){
        //request to get all courses info
        $smartboards.request('core', 'getCoursesList', {}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            $scope.courses = data.courses;
            $scope.allCourses = data.courses.slice(); //using slice so it is a copy by value and not reference
            $scope.usingMyCourses = data.myCourses;  //bool - to see this view use !
            for (var i in $scope.courses) {
                var course = $scope.courses[i];
                course.nameUrl = course.name.replace(/\W+/g, '');
            }

            if($scope.usingMyCourses){
                //seperate between active and notactive (but visible)
                jQuery.each($scope.courses, function(index){
                    course = $scope.courses[index];
                    if (course.isVisible != false){
                        if(course.isActive == true){
                            $scope.coursesActive.push(course);
                        }else{
                            $scope.coursesNotActive.push(course);
                        }
                    }
                    $scope.coursesActiveAll = $scope.coursesActive.slice();
                    $scope.coursesNotActiveAll = $scope.coursesNotActive.slice();
                });
                $element.append(sidebarMy);
                mainContent.append(myCourses);


            }
            else{
                $element.append(sidebarAll);
                mainContent.append(allCourses);
                mainContent.append(success_box);
                mainContent.append(action_buttons);
            }
            $element.append(mainContent);

            //set order by parameters
            $scope.lastOrder = "none";
            $scope.lastArrow = "none";
            $scope.orderList();
            $scope.reduceList(); //to cover new course, duplicate and delete actions
        }); 
    }
    getCourses(); //! important do not remove
    
});




//-------------------------------------USERS------------------------------

app.controller('Users', function($scope, $state, $compile, $smartboards, $element) {

    $scope.deleteUser = function(user) {
        $("#action_completed").empty();
        $smartboards.request('core', 'deleteUser', {user_id: user.id}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            getUsers();
            $("#action_completed").append("User: " + user.name +"-" + user.studentNumber + " deleted");
            $("#action_completed").show().delay(3000).fadeOut();
        });
    };

    $scope.adminUser = function( user_id){
        id = "#admin-" + user_id + ":checked";
        if($(id).length > 0){
            $admin = 0; //false
        }
        else{
            $admin = 1; //true
        }
        $smartboards.request('core', 'setUserAdmin', {user_id: user_id, isAdmin: $admin}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            $scope.users.find(x => x.id === user_id).isAdmin = $admin;
            $scope.allUsers.find(x => x.id === user_id).isAdmin = $admin;
        });
    }

    $scope.activeUser = function( user_id){
        id = "#active-" + user_id + ":checked"; 
        if($(id).length > 0){
            $active = 0; //false
        }
        else{
            $active = 1; //true
        }
        $smartboards.request('core', 'setUserActive', {user_id: user_id, isActive: $active}, function(data, err) {
            if (err) {
                alert(err.description);
                return;
            }
            $scope.users.find(x => x.id === user_id).isActive = $active;
            $scope.allUsers.find(x => x.id === user_id).isActive = $active;
        });
    }
    $scope.createUser = function(){
        $("#action_completed").empty();
        $scope.newUser = {};
        //inputs start not checked
        $scope.newUser.userIsActive = false;
        $scope.newUser.userIsAdmin = false;
        $scope.newUser.userImage = null;


        var imageInput = document.getElementById('profile_image');
		var imageDisplayArea = document.getElementById('display_profile_image');


		imageInput.addEventListener('change', function(e) {
			var file = imageInput.files[0];
			var imageType = /image.*/;

			if (file.type.match(imageType)) {
				var reader = new FileReader();

				reader.onload = function(e) {
					imageDisplayArea.innerHTML = "";

					var img = new Image();
                    img.src = reader.result;
                    $scope.newUser.userImage = reader.result;
                    imageDisplayArea.appendChild(img);
				}

                reader.readAsDataURL(file);	
                
			} else {
                $('#display_profile_image').empty();
                $('#display_profile_image').append($("<span>Please choose a valid type of file (hint: image)</span>"));
                $scope.newUser.userImage = null;
            }
		});


        $scope.isReadyToSubmit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            //validate inputs
            if (isValid($scope.newUser.userName) &&
            isValid($scope.newUser.userStudentNumber) &&
            isValid($scope.newUser.userEmail) &&
            isValid($scope.newUser.userAuthService) &&
            isValid($scope.newUser.userUsername)){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitUser = function() {
            isActive = $scope.newUser.userIsActive ? 1 : 0; //to transform from true-false
            isAdmin = $scope.newUser.userIsAdmin ? 1 : 0; //same
            var reqData = {
                userName: $scope.newUser.userName,
                userStudentNumber: $scope.newUser.userStudentNumber,
                userNickname: $scope.newUser.userNickname,
                userUsername: $scope.newUser.userUsername,
                userEmail: $scope.newUser.userEmail,
                userIsActive: isActive,
                userIsAdmin: isAdmin,
                userAuthService: $scope.newUser.userAuthService,
                userImage: $scope.newUser.userImage
            };
            $smartboards.request('core', 'createUser', reqData, function(data, err) {
                if (err) {
                    console.log(err.description);
                    //falta apanhar erro de student number ja existente
                    return;
                }
                $("#new-user").hide();
                //set profile image to initial state
                $('#display_profile_image').empty();
                $('#display_profile_image').append($("<span>Select a profile image</span>"));
                
                getUsers();
                $("#action_completed").append("New User created");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        };
    }
    $scope.modifyUser = function(user){
        $("#action_completed").empty();
        $("#active_visible_inputs").remove();
        $("#courses_list").remove();
        $scope.editUser = {};
        $scope.editUser.userId = user.id;
        $scope.editUser.userName = user.name;
        $scope.editUser.userEmail = user.email;
        $scope.editUser.userStudentNumber = user.studentNumber;
        $scope.editUser.userNickname = user.nickname;
        $scope.editUser.userUsername = user.username;
        $scope.editUser.userAuthService = user.authenticationService;
        
                
        editbox = $("#edit_box");
        //list of courses
        courses_row = $('<div id="courses_list"><span style="margin-right: 10px;">Courses: </span></div>')
        jQuery.each(user.courses , function( index ) {
            course = user.courses[index];
            courses_row.append($('<div class="course_tag">'+course.name+'</div>'));
        });
        editbox.append(courses_row);
        //on/off inputs
        editrow = $('<div class= "row" id="active_visible_inputs"></div>');
        if (user.isAdmin == true){
            editrow.append( $('<div class= "on_off"><span>Admin </span><label class="switch"><input id="admin" type="checkbox" ng-model="editUser.userIsAdmin" checked><span class="slider round"></span></label></div>'))
            $scope.editUser.userIsAdmin = true;
            console.log("is admin");
        }
        else{
            editrow.append( $('<div class= "on_off"><span>Admin </span><label class="switch"><input id="admin" type="checkbox" ng-model="editUser.userIsAdmin" ><span class="slider round"></span></label></div>'))
            $scope.editUser.userIsAdmin = false;
        }
        if (user.isActive == true){
            editrow.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="editUser.userIsActive" checked><span class="slider round"></span></label></div>'));
            $scope.editUser.userIsActive = true;
            console.log("is active");
        }
        else{
            editrow.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="editUser.userIsActive"><span class="slider round"></span></label></div>'));
            $scope.editUser.userIsActive = false;
        }
        editbox.append(editrow);
        $compile(editbox)($scope);
        


        $scope.isReadyToEdit = function() {
            isValid = function(text){
                return  (text != "" && text != undefined && text != null)
            }
            //validate inputs
            if (isValid($scope.editUser.userName) &&
            isValid($scope.editUser.userEmail) &&
            isValid($scope.editUser.userStudentNumber) &&
            isValid($scope.editUser.userUsername) &&
            isValid($scope.editUser.userAuthService)){
                return true;
            }
            else{
                return false;
            }
        }

        $scope.submitEditUser = function() {
            isActive = $scope.editUser.userIsActive ? 1 : 0;
            isAdmin = $scope.editUser.userIsAdmin ? 1 : 0;
            var reqData = {
                userName: $scope.editUser.userName,
                userId: $scope.editUser.userId,
                userStudentNumber: $scope.editUser.userStudentNumber,
                userNickname: $scope.editUser.userNickname,
                userUsername:  $scope.editUser.userUsername,
                userEmail: $scope.editUser.userEmail,
                userIsActive: isActive,
                userIsAdmin: isAdmin,
                userAuthService: $scope.editUser.userAuthService
            };
            $smartboards.request('core', 'editUser', reqData, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }
                $("#edit-user").hide();
                getUsers();
                $("#action_completed").append("User: "+ $scope.editUser.userName+"-"+ $scope.editUser.userStudentNumber + " edited");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        };
    }

    $scope.reduceList = function(){
        $("#empty_table").empty();
        $("#users-table").show();
        $scope.users = $scope.allUsers.slice();
        $scope.searchList();
        $scope.filterList();
        
    }

    $scope.searchList = function(){
        filteredUsers = [];
        text = $scope.search;
        if (validateSearch(text)){
            //match por name e short
            jQuery.each($scope.users , function( index ){
                user = $scope.users[index];
                if (user.name && user.name.toLowerCase().includes(text.toLowerCase())
                || user.nickname && user.nickname.toLowerCase().includes(text.toLowerCase())
                || user.studentNumber && user.studentNumber.toLowerCase().includes(text.toLowerCase())){
                    filteredUsers.push(user);
                }
            });
            if(filteredUsers.length == 0){
                $("#users-table").hide();
                $("#empty_table").append("No matches found");
            }
            $scope.users = filteredUsers;
        }
        
    }

    $scope.filterList = function(){
        active = $scope.filterActive;
        inactive = $scope.filterInactive;
        admin = $scope.filterAdmin;
        nonAdmin = $scope.filterNonAdmin;

        //reset list of courses
        usersList = $scope.users;
        filteredUsers = [];
        error_msg = "";

        //cases of empty result
        if (!active && !inactive){
            error_msg = "You must select at least one of the options: Active or Inactive"
        }
        else if(!admin & !nonAdmin){
            error_msg = "You must select at least one of the options: Admin or NonAdmin"
        }
        else if(active && inactive && admin & nonAdmin){
            filteredUsers = usersList;
        }
        else{
            jQuery.each(usersList , function( index ) {
                user = usersList[index];
                validA = false;
                validV = false;
                if (user.isActive == true && active){
                    validA = true;
                }
                else if ( user.isActive == false && inactive){
                    validA = true;
                }
                if (validA && user.isAdmin == true && admin){
                    validV = true;
                }
                else if(validA && user.isAdmin == false && nonAdmin){
                    validV = true;
                }

                if (validA && validV){
                    filteredUsers.push(user);
                }
            });
        
            if (filteredUsers.length == 0){
                error_msg = "No matches found for your filter"
            }
        }
        if(error_msg != ""){
            $("#users-table").hide();
            $("#empty_table").append(error_msg);
        }
        $scope.users = filteredUsers;
    }

    //functions to visually change the "order by" arrows
    $scope.sortUp = function(){
        document.getElementById("triangle-up").classList.add("checked");
        document.getElementById("triangle-down").classList.remove("checked");
    }
    $scope.sortDown = function() {
        document.getElementById("triangle-down").classList.add("checked");
        document.getElementById("triangle-up").classList.remove("checked");
    }

    $scope.orderList = function(){
        order_by_id = $('input[type=radio]:checked', ".order-by")[0].id;
        order = getNameFromId(order_by_id);
        up = $("#triangle-up").hasClass("checked");

        if (up){ arrow = "up";}
        else{ arrow = "down";}

        if ($scope.lastOrder =="none" || $scope.lastOrder != order){
            switch (order){
                //default sort made with arrow down
                case "Name":
                    $scope.users.sort(orberByName);
                    $scope.allUsers.sort(orberByName);
                    break;
                case "Nickname":
                    $scope.users.sort(orberByNickname);
                    $scope.allUsers.sort(orberByNickname);
                    break;
                case "Student Number":
                    $scope.users.sort(orberByStudentNumber);
                    $scope.allUsers.sort(orberByStudentNumber);
                    break;
                case "N Courses":
                    $scope.users.sort(orberByNCourses);
                    $scope.allUsers.sort(orberByNCourses);
                    break;
                case "Last Login":
                    $scope.users.sort(orberByLastLgin);
                    $scope.allUsers.sort(orberByLastLgin);
                    break;
            }
            if (up){ 
                $scope.users.reverse();
                $scope.allUsers.reverse();
            }

        }else{
            if (arrow ==  $scope.lastArrow){
                //nothing changes
                return;
            }
            else{
                //only the ascendent/descent order changed
                $scope.users.reverse();
                $scope.allUsers.reverse();
            }

        }

        //set values of the existing orderby
        $scope.lastOrder = order;
        $scope.lastArrow = arrow;
    }


    mainContent = $("<div id='mainContent'></div>");

    //sidebar
    optionsFilter = ["Admin", "NonAdmin", "Active", "Inactive"];
    optionsOrder = ["Name", "Nickname","Student Number", "# Courses","Last Login"];
    //start checkboxs checked, tied by the ng-model in each input
    $scope.filterAdmin=true;
    $scope.filterNonAdmin=true;
    $scope.filterActive=true;
    $scope.filterInactive=true;
    sidebarAll = createSidebar( optionsFilter, optionsOrder);
    $compile(sidebarAll)($scope)

    //table structure

    allUsers=$("<div id='allUsers'></div>")
    allUsersSection = $('<div class="data-table" ></div>');
    table = $('<table id="users-table"></table>');
    rowHeader = $("<tr></tr>");
    header = [{class: "name-column", content: "Name"},
              {class: "", content: "Nickname"},
              {class: "", content: "Student nº"},
              {class: "", content: "# Courses"},
              {class: "", content: "Last Login"},
              {class: "check-column", content: "Admin"},
              {class: "check-column", content: "Active"},
              {class: "action-column", content: ""},
              {class: "action-column", content: ""},
            ];
    jQuery.each(header, function(index){
        rowHeader.append( $("<th class="+ header[index].class + ">" + header[index].content + "</th>"));
    });

    
    rowContent = $("<tr ng-repeat='(i, user) in users' id='user-{{user.id}}'> ></tr>");
    rowContent.append('<td class="name-column"><span>{{user.name}}</span></td>');
    rowContent.append('<td>{{user.nickname}}</td>');
    rowContent.append('<td>{{user.studentNumber}}</td>');
    rowContent.append('<td>{{user.ncourses}}</td>');
    rowContent.append('<td>{{user.lastLogin}}</td>');
    rowContent.append('<td class="check-column"><label class="switch"><input ng-if="user.isAdmin == true" id="admin-{{user.id}}" type="checkbox" checked><input ng-if="user.isAdmin == false" id="admin-{{user.id}}" type="checkbox"><span ng-click= "adminUser(user.id)" class="slider round"></span></label></td>');
    rowContent.append('<td class="check-column"><label class="switch"><input ng-if="user.isActive == true" id="active-{{user.id}}" type="checkbox" checked><input ng-if="user.isActive == false" id="active-{{user.id}}" type="checkbox"><span ng-click= "activeUser(user.id)" class="slider round"></span></label></td>');
    rowContent.append('<td class="action-column"><div class="icon edit_icon" value="#edit-user" onclick="openModal(this)" ng-click="modifyUser(user)"></div></td>');
    rowContent.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification-{{user.id}}" onclick="openModal(this)"></div></td>');

    //the verification modals
    modal = $("<div class='modal' id='delete-verification-{{user.id}}'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#delete-verification-{{user.id}}" onclick="closeModal(this)"></button>'));
    verification.append( $('<div class="warning">Are you sure you want to delete this User?</div>'));
    verification.append( $('<div class="target">{{user.name}} - {{user.studentNumber}}</div>'));
    verification.append( $('<div class="confirmation_btns"><button class="cancel" value="#delete-verification-{{user.id}}" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="deleteUser(user)"> Delete</button></div>'))
    modal.append(verification);
    rowContent.append(modal);

    //append table
    table.append(rowHeader);
    table.append(rowContent);
    allUsersSection.append(table);
    allUsers.append(allUsersSection);


    //new user modal
    modal = $("<div class='modal' id='new-user'></div>");
    newUser = $("<div class='modal_content'></div>");
    newUser.append( $('<button class="close_btn icon" value="#new-user" onclick="closeModal(this)"></button>'));
    newUser.append( $('<div class="title">New User: </div>'));
    content = $('<div class="content">');
    box = $('<div class= "inputs">');
    row_inputs = $('<div class= "row_inputs"></div>');
    //image input
    row_inputs.append($('<div class="image smaller"><div class="profile_image"><div id="display_profile_image"><span>Select a profile image</span></div></div><input type="file" class="form__input" id="profile_image" required="" /></div>'))
    //text inputs
    details = $('<div class="details bigger right"></div>')
    details.append($('<div class="container"><input type="text" class="form__input" id="name" placeholder="Name *" ng-model="newUser.userName"/> <label for="name" class="form__label">Name</label></div>'))
    details.append($('<div class="container"><input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="newUser.userNickname"/><label for="nickname" class="form__label">Nickname</label></div>'))
    details.append($('<div class="container"><input type="email" class="form__input" id="email" placeholder="Email *" ng-model="newUser.userEmail"/><label for="email" class="form__label">Email</label></div>'))
    details.append($('<div class="container"><input type="text" class="form__input" id="studentNumber" placeholder="Student Number *" ng-model="newUser.userStudentNumber"/><label for="studentNumber" class="form__label">Student Number</label></div>'))
    row_inputs.append(details);
    box.append(row_inputs);
    // authentication information - service and username
    row_auth = $('<div class= "row_inputs"></div>');
    selectAuth = $('<div class="smaller">');
    select = $('<select id="authService" class="form__input" name="authService" ng-model="newUser.userAuthService"></select>');
    select.append($('<option value="" disabled selected>Auth Service</option>'));
    optionsAuth = ["fenix", "google", "facebook", "linkedin"];
    jQuery.each(optionsAuth, function( index ){
        option = optionsAuth[index];
        select.append($('<option value="'+option+'">'+option+'</option>'))
    });
    selectAuth.append(select);
    row_auth.append(selectAuth);
    row_auth.append($('<div class="details bigger right"><div class="container"><input type="text" class="form__input" id="username" placeholder="Username *" ng-model="newUser.userUsername"/> <label for="username" class="form__label">Username</label></div></div>'))
    box.append(row_auth);
    //on/off inputs
    row = $('<div class= "row"></div>');
    row.append( $('<div class= "on_off"><span>Admin </span><label class="switch"><input id="admin" type="checkbox" ng-model="newUser.userIsAdmin"><span class="slider round"></span></label></div>'))
    row.append( $('<div class= "on_off"><span>Active </span><label class="switch"><input id="active" type="checkbox" ng-model="newUser.userIsActive"><span class="slider round"></span></label></div>'))
    box.append(row);
    content.append(box);
    content.append( $('<button class="save_btn" ng-click="submitUser()" ng-disabled="!isReadyToSubmit()" > Save </button>'))
    newUser.append(content);
    modal.append(newUser);
    allUsers.append(modal);


    //edit user modal
    editmodal = $("<div class='modal' id='edit-user'></div>");
    editUser = $("<div class='modal_content'></div>");
    editUser.append( $('<button class="close_btn icon" value="#edit-user" onclick="closeModal(this)"></button>'));
    editUser.append( $('<div class="title">Edit User: </div>'));
    editcontent = $('<div class="content">');
    editbox = $('<div id="edit_box" class= "inputs">');
    editrow_inputs = $('<div class= "row_inputs"></div>');
    //image input
    editrow_inputs.append($('<div class="image smaller"><div class="profile_image"></div><input type="file" class="form__input" id="profile_image" required="" /></div>'))
    //text inputs
    editdetails = $('<div class="details bigger right"></div>')
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="name" placeholder="Name *" ng-model="editUser.userName"/> <label for="name" class="form__label">Name</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="editUser.userNickname"/><label for="nickname" class="form__label">Nickname</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="email" placeholder="Email *" ng-model="editUser.userEmail"/><label for="email" class="form__label">Email</label></div>'))
    editdetails.append($('<div class="container" ><input type="text" class="form__input" id="studentNumber" placeholder="Student Number *" ng-model="editUser.userStudentNumber"/><label for="studentNumber" class="form__label">Student Number</label></div>'))
    editrow_inputs.append(editdetails);
    editbox.append(editrow_inputs);
    // authentication information - service and username
    editrow_auth = $('<div class= "row_inputs"></div>');
    editSelectAuth = $('<div class="smaller">');
    editSelect = $('<select id="authService" class="form__input" name="authService" ng-model="editUser.userAuthService"></select>');
    editSelect.append($('<option value="" disabled selected>Auth Service</option>'));
    optionsAuth = ["fenix", "google", "facebook", "linkedin"];
    jQuery.each(optionsAuth, function( index ){
        option = optionsAuth[index];
        editSelect.append($('<option value="'+option+'">'+option+'</option>'))
    });
    editSelectAuth.append(editSelect);
    editrow_auth.append(editSelectAuth);
    editrow_auth.append($('<div class="details bigger right"><div class="container"><input type="text" class="form__input" id="username" placeholder="Username *" ng-model="editUser.userUsername"/> <label for="username" class="form__label">Username</label></div></div>'))
    editbox.append(editrow_auth);
    editcontent.append(editbox);
    editcontent.append( $('<button class="save_btn" ng-click="submitEditUser()" ng-disabled="!isReadyToEdit()" > Save </button>'))
    editUser.append(editcontent);
    editmodal.append(editUser);
    allUsers.append(editmodal);




    //error section
    allUsers.append( $("<div class='error_box'><div id='empty_table' class='error_msg'></div></div>"));
    //success section
    mainContent.append( $("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

    //action buttons
    action_buttons = $("<div class='action-buttons'></div>");
    action_buttons.append( $("<div class='icon add_icon' value='#new-user' onclick='openModal(this)' ng-click='createUser()'></div>"));
    action_buttons.append( $("<div class='icon import_icon'></div>"));
    action_buttons.append( $("<div class='icon export_icon'></div>"));
    mainContent.append($compile(action_buttons)($scope));


    
    mainContent.append(allUsers);
    $compile(mainContent)($scope);

    getUsers = function() {
        $smartboards.request('core', 'users', {}, function(data, err) {
            if (err) {
                $($element).text(err.description);
                return;
            }
            console.log(data)

            $scope.users = data.users.slice();
            $scope.allUsers = data.users.slice();


            // $scope.isValidString = function(s) { return s != undefined && s.length > 0};

            // $scope.userUpdateInfo = {};
            // $scope.updateUsername = function() {
            //     $smartboards.request('core', 'users', {updateUsername: $scope.userUpdateInfo}, function(data, err) {
            //         if (err) {
            //             alert(err.description);
            //             return;
            //         }

            //         console.log('ok!');
            //         $scope.userUpdateInfo = {};
            //     });
            // };

            // var updateUsernameSection = createSection($($element), 'Update usernames').attr('id', 'update-usernames');
            // var setUsernameDiv = $('<div>');
            // setUsernameDiv.append('<div>Update username: </div>')
            // setUsernameDiv.append('<div><label for="update-id" class="label">IST Id:</label><input type="text" class="input-text" id="update-id" ng-model="userUpdateInfo.id"></div>');
            // setUsernameDiv.append('<div><label for="update-username" class="label">IST Username:</label><input type="text" class="input-text" id="update-username" ng-model="userUpdateInfo.username"></div>');
            // setUsernameDiv.append('<div><button ng-disabled="!isValidString(userUpdateInfo.id) || !isValidString(userUpdateInfo.username)" ng-click="updateUsername()">Update username</button></div>');
            // updateUsernameSection.append(setUsernameDiv);
            // $compile(updateUsernameSection)($scope);

            //no fim do request
            $scope.lastOrder = "none";
            $scope.lastArrow = "none";
            $element.append(sidebarAll);
            $element.append(mainContent);
            $scope.orderList();
            $scope.reduceList();
            
        });  
    }          
    getUsers();


    
    
});