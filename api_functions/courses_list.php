<?php
namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;

//return a list of courses that the user is allowed to see
API::registerFunction('core', 'getCoursesList', function() {
    $user = Core::getLoggedUser();
    
    if ($user->isAdmin()) {
        $courses = Core::getCourses();
        $myCourses = false;
        //get number of students per course
        foreach($courses as &$course){
            $cOb = Course::getCourse($course['id'], false);
            $students = sizeof($cOb->getUsersWithRole("Student"));
            $course['nstudents'] = $students;
        }
    }
    else {
        $coursesId = $user->getCourses();
        
        $courses=[];
        foreach($coursesId as $cid){
            $course = Core::getCourse($cid);
            if ($course["isVisible"]){
                $courses[]=$course;
            }
        }
        array_combine(array_column($courses,'id'),$courses);
        $myCourses = true;
    }
    API::response(array('courses' => $courses, 'myCourses' => $myCourses));
});

//request to create a new course
API::registerFunction('core', 'createCourse', function() {
    API::requireAdminPermission();
    API::requireValues('courseName', 'creationMode', 'courseShort', 'courseYear', 'courseColor', 'courseIsVisible', 'courseIsActive' );
    if (API::getValue('creationMode') == 'similar')
        API::requireValues('copyFrom');

    Course::newCourse(API::getValue('courseName'),API::getValue('courseShort'),API::getValue('courseYear'),API::getValue('courseColor'), API::getValue('courseIsVisible'), API::getValue('courseIsActive'),(API::getValue('creationMode') == 'similar') ? API::getValue('copyFrom') : null);
});

//request to edit an existing course
API::registerFunction('core', 'editCourse', function() {
    //may be used inside course settingd on 'this course' page
    //so users with teacher role can edit information about the course if needed
    API::requireCourseAdminPermission();
    API::requireValues('course','courseName', 'courseShort', 'courseYear', 'courseColor', 'courseIsVisible', 'courseIsActive' );
    $course = Course::getCourse(API::getValue('course'), false);
    if($course != null){
        $course->editCourse(API::getValue('courseName'),API::getValue('courseShort'),API::getValue('courseYear'),API::getValue('courseColor'), API::getValue('courseIsVisible'), API::getValue('courseIsActive'));
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});

//request to delete an existing course
API::registerFunction('core', 'deleteCourse', function() {
    API::requireAdminPermission();
    API::requireValues('course');

    $course_id = API::getValue('course');
    $course = Course::getCourse($course_id , false);
    if($course != null){
        Course::deleteCourse($course_id);
    }
    else{
        API::error("There is no course with that id: ". $course_id);
    }
});

//set course Visibility
API::registerFunction('core', 'setCoursesvisibility', function(){
    API::requireAdminPermission();
    API::requireValues('course_id');
    API::requireValues('visibility');

    
    $course_id = API::getValue('course_id');
    $visibility = API::getValue('visibility');
    
    $cOb = Course::getCourse($course_id, false);
    if($cOb != null){
        $cOb->setVisibleState($visibility);
    }
    else{
        API::error("There is no course with that id: ". $course_id);
    }
});

//set course ative
API::registerFunction('core', 'setCoursesActive', function(){
    API::requireAdminPermission();
    API::requireValues('course_id');
    API::requireValues('active');

    
    $course_id = API::getValue('course_id');
    $active = API::getValue('active');
    
    $cOb = Course::getCourse($course_id, false);
    if($cOb != null){
        $cOb->setActiveState($active);
    }
    else{
        API::error("There is no course with that id: ". $course_id);
    }
});

//import courses - check if it's working
API::registerFunction('core', 'importCourses', function(){
    API::requireAdminPermission();
    API::requireValues('file');
    $file = explode(",", API::getValue('file'));
    $fileType = explode(";", $file[0]);
    $fileContents = base64_decode($file[1]);
    $replace = API::getValue('replace');
    $nCourses = Course::importCourses($fileContents, $replace);
    API::response(array('nCourses' => $nCourses));
});

//export courses - check if it's working
API::registerFunction('core', 'exportCourses', function(){
    API::requireAdminPermission();
    $courses = Course::exportCourses();
    API::response(array('courses' => $courses));
});