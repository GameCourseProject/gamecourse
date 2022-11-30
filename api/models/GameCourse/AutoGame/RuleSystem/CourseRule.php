<?php

namespace GameCourse\AutoGame\RuleSystem;

use http\Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the CourseRule model, which implements the necessary methods
 * to interact with course rules in the MySQL database.
 */
class CourseRule extends Rule
{
    const TABLE_RULE = "course_rule";

    const HEADERS = [ // headers for import/export functionality (+Rule headers)
        "isActiveInCourse"
    ];

    protected  $course;

    public function __construct(int $id, Course $course)
    {
        parent::__construct($id);
        $this->course = $course;
    }

    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getCourse(): Course {
        return $this->course;
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a course rule by its ID.
     * Returns null if course rule doesn't exist.
     *
     * @param int $ruleId
     * @param Course $course
     * @return CourseRule|null
     */
    public static function getCourseRuleById(int $ruleId, Course $course): ?CourseRule
    {
        $courseRule = new CourseRule($ruleId, $course);
        if ($courseRule->exists()) return $courseRule;
        else return null;
    }
    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a course rule coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $rule
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    public static function parse(array $rule = null, $field = null, string $fieldName = null)
    {
        $intValues = ["course"];
        $boolValues = ["isActive", "isActiveInCourse"];

        if ($rule) $rule = parent::parse($rule);
        else $field = parent::parse(null, $field, $fieldName);
        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $rule, $field, $fieldName);
    }


}