<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use InvalidArgumentException;

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
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace provides utilities for obtaining a specified course, and to obtain a course's configurations,
        such as the defined color. A course has the following data that you can access:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{
            "id": 1,
            "name": "Multimedia Content Production",
            "short": "MCP",
            "year": "2024-2025",
            "color": "#7E57C2",
            "theme": null,
            "avatars": true
        }</code></pre>
        </div><br>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockCourse(int $id) : array
    {
        $fakeStart = Core::dictionary()->faker()->dateTimeThisYear();
        return [
            "id" => $id,
            "name" => Core::dictionary()->faker()->text(25),
            "short" => Core::dictionary()->faker()->text(5),
            "color" => Core::dictionary()->faker()->hexColor(),
            "theme" => Core::dictionary()->faker()->text(10),
            "avatars" => Core::dictionary()->faker()->boolean(),
            "nicknames" => Core::dictionary()->faker()->boolean(),
            "year" => date("Y"),
            "startDate" => $fakeStart->format("Y-m-d H:m:s"),
            "endDate" => $fakeStart->modify('+6 month')->format("Y-m-d H:m:s")
        ];
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("name",
                [["name" => "course", "optional" => false, "type" => "Course"]],
                "Gets a given course's name.",
                ReturnType::TEXT,
                $this,
                "%someCourse.name\nReturns, for example, 'Multimedia Content Production'"
            ),
            new DFunction("short",
                [["name" => "course", "optional" => false, "type" => "Course"]],
                "Gets a given course's short.",
                ReturnType::TEXT,
                $this,
                "%someCourse.short\nReturns, for example, 'MCP'"
            ),
            new DFunction("year",
                [["name" => "course", "optional" => false, "type" => "Course"]],
                "Gets a given course's year.",
                ReturnType::TEXT,
                $this,
                "%someCourse.year\nReturns, for example, '2024-2025'"
            ),
            new DFunction("color",
                [["name" => "course", "optional" => false, "type" => "Course"]],
                "Gets a given course's color.",
                ReturnType::TEXT,
                $this,
                "%someCourse.color"
            ),
            new DFunction("theme",
                [["name" => "course", "optional" => false, "type" => "Course"]],
                "Gets a given course's theme.",
                ReturnType::TEXT,
                $this,
                "%someCourse.theme"
            ),
            new DFunction("avatars",
                [["name" => "course", "optional" => false, "type" => "Course"]],
                "Returns whether the avatars are enabled in the course or not.",
                ReturnType::BOOLEAN,
                $this,
                "%someCourse.avatars\nReturns true or false."
            ),
            new DFunction("nicknames",
                [["name" => "course", "optional" => false, "type" => "Course"]],
                "Returns whether the nicknames are enabled in the course or not.",
                ReturnType::BOOLEAN,
                $this,
                "%someCourse.nicknames\nReturns true or false."
            ),
            new DFunction("getCourseById",
                [["name" => "courseId", "optional" => false, "type" => "int"]],
                "Gets a course by its ID.",
                ReturnType::OBJECT,
                $this,
                "courses.getCourseById(1)"
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given course's name.
     *
     * @param $course
     * @return ValueNode
     * @throws Exception
     */
    public function name($course): ValueNode
    {
        // NOTE: on mock data, course will be mocked
        if (is_array($course)) $color = $course["name"];
        elseif (is_object($course) && method_exists($course, 'getName')) $color = $course->getName();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a course.");
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given course's short.
     *
     * @param $course
     * @return ValueNode
     * @throws Exception
     */
    public function short($course): ValueNode
    {
        // NOTE: on mock data, course will be mocked
        if (is_array($course)) $color = $course["short"];
        elseif (is_object($course) && method_exists($course, 'getShort')) $color = $course->getShort();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a course.");
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given course's year.
     *
     * @param $course
     * @return ValueNode
     * @throws Exception
     */
    public function year($course): ValueNode
    {
        // NOTE: on mock data, course will be mocked
        if (is_array($course)) $color = $course["year"];
        elseif (is_object($course) && method_exists($course, 'getYear')) $color = $course->getYear();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a course.");
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

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
        elseif (is_object($course) && method_exists($course, 'getColor')) $color = $course->getColor();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a course.");
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given course's theme.
     *
     * @param $course
     * @return ValueNode
     * @throws Exception
     */
    public function theme($course): ValueNode
    {
        // NOTE: on mock data, course will be mocked
        if (is_array($course)) $color = $course["theme"];
        elseif (is_object($course) && method_exists($course, 'getTheme')) $color = $course->getTheme();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a course.");
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Returns whether the avatars are enabled in the course or not.
     *
     * @param $course
     * @return ValueNode
     * @throws Exception
     */
    public function avatars($course): ValueNode
    {
        // NOTE: on mock data, course will be mocked
        if (is_array($course)) $avatars = $course["avatars"];
        elseif (is_object($course) && method_exists($course, 'getAvatars')) $avatars = $course->getAvatars();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a course.");
        return new ValueNode($avatars, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Returns whether the avatars are enabled in the course or not.
     *
     * @param $course
     * @return ValueNode
     * @throws Exception
     */
    public function nicknames($course): ValueNode
    {
        // NOTE: on mock data, course will be mocked
        if (is_array($course)) $nicknames = $course["nicknames"];
        elseif (is_object($course) && method_exists($course, 'getNicknames')) $nicknames = $course->getNicknames();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a course.");
        return new ValueNode($nicknames, Core::dictionary()->getLibraryById(BoolLibrary::ID));
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

        if (Core::dictionary()->mockData()) {
            $course = $this->mockCourse($courseId);
        } else {
            $course = Course::getCourseById($courseId);
        }
        return new ValueNode($course, $this);
    }
}
