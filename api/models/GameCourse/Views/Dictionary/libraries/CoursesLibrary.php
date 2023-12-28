<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class CoursesLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "courses";    // NOTE: must match the name of the class
    const NAME = "Courses";
    const DESCRIPTION = "Provides access to information regarding courses.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("color",
                [["name" => "course", "optional" => false, "type" => "Course"]],
                "Gets a given courses's color.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("getCourseById",
                [["name" => "courseId", "optional" => false, "type" => "int"]],
                "Gets a course by its ID.",
                ReturnType::OBJECT,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given course's color.
     *
     * @param $course
     * @return ValueNode
     * @throws Exception
     */
    public function color($course): ValueNode
    {
        // NOTE: on mock data, course will be mocked
        if (is_array($course)) $color = $course["color"];
        else $color = $course->getColor();
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a course by its ID.
     *
     * @param int $courseId
     * @return ValueNode
     * @throws Exception
     */
    public function getCourseById(int $courseId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

/*         if (Core::dictionary()->mockData()) {
            // TODO: mock course
            $course = [];

        } else  */$course = Course::getCourseById($courseId);
        return new ValueNode($course, $this);
    }
}
