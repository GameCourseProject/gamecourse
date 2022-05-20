<?php
namespace API;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;

/**
 * This is the Course controller, which holds API endpoints for
 * course related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Course",
 *     description="API endpoints for course related actions"
 * )
 */
class CourseController
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Get courses on the system.
     * Option for 'active' and/or 'visible'.
     *
     * @param bool $isActive (optional)
     * @param bool $isVisible (optional)
     */
    public function getCourses()
    {
        API::requireAdminPermission();
        $isActive = API::getValue("isActive", "bool");
        $isVisible = API::getValue("isVisible", "bool");

        $courses = Course::getCourses($isActive, $isVisible);
        foreach ($courses as &$courseInfo) {
            $course = Course::getCourseById($courseInfo["id"]);
            $courseInfo["nrStudents"] = count($course->getStudents());
        }

        API::response($courses);
    }


    /*** --------------------------------------------- ***/
    /*** ------------ Course Manipulation ------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function createCourse()
    {
        API::requireAdminPermission();
        API::requireValues('name', 'short', 'year', 'color', 'startDate', 'endDate', 'isActive', 'isVisible');

        // Get values
        $name = API::getValue("name");
        $short = API::getValue("short");
        $year = API::getValue("year");
        $color = API::getValue("color");
        $startDate = API::getValue("startDate");
        $endDate = API::getValue("endDate");
        $isActive = API::getValue("isActive", "bool");
        $isVisible = API::getValue("isVisible", "bool");

        // Add new course
        $course = Course::addCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible);

        $courseInfo = $course->getData();
        if (Core::getLoggedUser()->isAdmin())
            $courseInfo["nrStudents"] = count($course->getStudents());
        API::response($courseInfo);
    }

    /**
     * @throws Exception
     */
    public function duplicateCourse()
    {
        API::requireAdminPermission();
        API::requireValues('courseId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        // Duplicate course
        $course = Course::copyCourse($courseId);

        $courseInfo = $course->getData();
        if (Core::getLoggedUser()->isAdmin())
            $courseInfo["nrStudents"] = count($course->getStudents());
        API::response($courseInfo);
    }

    /**
     * @throws Exception
     */
    public function editCourse()
    {
        API::requireAdminPermission();
        API::requireValues('courseId', 'name', 'short', 'year', 'color', 'startDate', 'endDate', 'isActive', 'isVisible');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        // Get values
        $name = API::getValue("name");
        $short = API::getValue("short");
        $year = API::getValue("year");
        $color = API::getValue("color");
        $startDate = API::getValue("startDate");
        $endDate = API::getValue("endDate");
        $isActive = API::getValue("isActive", "bool");
        $isVisible = API::getValue("isVisible", "bool");

        // Edit course
        $course->editCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible);

        $courseInfo = $course->getData();
        if (Core::getLoggedUser()->isAdmin())
            $courseInfo["nrStudents"] = count($course->getStudents());
        API::response($courseInfo);
    }

    /**
     * @throws Exception
     */
    public function deleteCourse()
    {
        API::requireAdminPermission();
        API::requireValues('courseId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        Course::deleteCourse($courseId);
    }

    /**
     * @throws Exception
     */
    public function setActive()
    {
        API::requireAdminPermission();
        API::requireValues('courseId', 'isActive');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $isActive = API::getValue("isActive", "bool");
        $course->setActive($isActive);
    }

    /**
     * @throws Exception
     */
    public function setVisible()
    {
        API::requireAdminPermission();
        API::requireValues('courseId', 'isVisible');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $isVisible = API::getValue("isVisible", "bool");
        $course->setVisible($isVisible);
    }


    /*** --------------------------------------------- ***/
    /*** -------------- Import / Export -------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Import courses into the system.
     *
     * @param $file
     * @param bool $replace
     * @throws Exception
     */
    public function importCourses()
    {
        API::requireAdminPermission();
        API::requireValues("file", "replace");

        $file = API::getValue("file");
        $replace = API::getValue("replace", "bool");

        $nrCoursesImported = Course::importCourses($file, $replace);
        API::response($nrCoursesImported);
    }
}
