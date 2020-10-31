//Controllers for pages of the system, except for setting pages

app.controller('MyInfo', function($element, $scope, $smartboards, $compile, $state) {


    $smartboards.request('core', 'getUserInfo', {}, function(data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }
        $scope.myInfo = data.userInfo;

        myInfo = $("<div id='myInfo'></div>");
        title = $("<div class='title'>My Information</div>");
        subtitle = $("<div class='warning'>If some of the following information is not right please contact your Teacher to fix it.</div>");
        informationbox = $("<div class='information_box'></div>");
        image = $('<div class="profile_image"></div>');

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

    $scope.importCourses = function () {
        $scope.importedCourses = null;
        var fileInput = document.getElementById('import_course');
        var file = fileInput.files[0];

        var reader = new FileReader();

        reader.onload = function(e) {
            $scope.importedCourses = reader.result;
            $smartboards.request('core', 'importCourses', { file: $scope.importedCourses }, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }
                nCourses = data.nCourses;
                $("#import-course").hide();
                $("#action_completed").empty();
                $("#action_completed").append(nCourses + " Courses Imported");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        }     
        reader.readAsDataURL(file);
        
    }
    $scope.exportCourses = function(){
        $smartboards.request('core', 'exportCourses', { }, function(data, err) {
            if (err) {
                console.log(err.description);
                return;
            }
            download("courses.csv", data.courses);
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
    action_buttons.append( $("<div class='icon import_icon' value='#import-course' onclick='openModal(this)'></div>"));
    action_buttons.append( $("<div class='icon export_icon' ng-click='exportCourses()'></div>"));
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


    //the import modal
    importModal = $("<div class='modal' id='import-course'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#import-course" onclick="closeModal(this)"></button>'));
    verification.append( $('<div class="warning">Please select a .csv or .txt file to be imported</div>'));
    verification.append( $('<div class="target">The seperator must be comma</div>'));
    verification.append( $('<input class="config_input" type="file" id="import_course" accept=".csv, .txt">')); //input file
    verification.append( $('<div class="confirmation_btns"><button ng-click="importCourses()">Import Courses</button></div>'))
    importModal.append(verification);
    allCourses.append(importModal);

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
        $scope.newUser.userHasImage = "false";


        var imageInput = document.getElementById('profile_image');
        var imageDisplayArea = document.getElementById('display_profile_image'); //ver este limpar tem de ter o span
        imageDisplayArea.innerHTML = "";
        $('#display_profile_image').append($('<span>Select a profile image</span>'));

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
                    $scope.newUser.userHasImage = "true";
                    imageDisplayArea.appendChild(img);
				}

                reader.readAsDataURL(file);	
                
			} else {
                $('#display_profile_image').empty();
                $('#display_profile_image').append($("<span>Please choose a valid type of file (hint: image)</span>"));
                $scope.newUser.userImage = null;
                $scope.newUser.userHasImage = "false";
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
                userImage: $scope.newUser.userImage,
                userHasImage: $scope.newUser.userHasImage
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
        $scope.editUser.userImage = null;
        $scope.editUser.userHasImage = "false";
        
                
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
        

        var imageInput = document.getElementById('edit_profile_image');
        var imageDisplayArea = document.getElementById('edit_display_profile_image');
        imageDisplayArea.innerHTML = "";

        //set initial image
        var profile_image = new Image();
        profile_image.onload = function() {
            image.append(profile_image);
        }
        profile_image.onerror = function() {
            image.append($('<span>No profile image was selected</span>'));
        }
        profile_image.src = 'photos/'+ $scope.myInfo.id +'.png?'+ new Date().getTime();


<<<<<<< HEAD
=======
        var reader = new FileReader();

        reader.onload = function(e) {
            $scope.importedUsers = reader.result;
            $smartboards.request('core', 'importUser', { file: $scope.importedUsers }, function(data, err) {
                if (err) {
                    console.log(err.description);
                    return;
                }
                nUsers = data.nUsers;
                $("#import-user").hide();
                $("#action_completed").empty();
                $("#action_completed").append(nUsers + " Users Imported");
                $("#action_completed").show().delay(3000).fadeOut();
            });
        }
        reader.readAsDataURL(file);	
>>>>>>> a665a19c...  - Returns the file content as base64, which is handled in backend
        
        atributes = ['Name', 'Nickname', 'Student Number', 'Email', 'Authentication', 'Username']
        values = ['name', 'nickname', 'studentNumber', 'email', 'authenticationService', 'username']
        atributes_column = $("<div class='info'></div>");
        values_column = $("<div class='info'></div>");
        jQuery.each(atributes, function(index){
            atributes_column.append($('<span class="label">'+atributes[index]+'</span>'));
            if($scope.myInfo[values[index]] == null){
                values_column.append($('<span class="not_set">Not set</span>'));
            }else{
                values_column.append($('<span>'+$scope.myInfo[values[index]]+'</span>'));
            }
           
        });
        informationbox.append(image);
        informationbox.append(atributes_column);
        informationbox.append(values_column);

        myInfo.append(title);
        myInfo.append(informationbox);
        myInfo.append(subtitle);
        $element.append(myInfo);
    });
    
});
