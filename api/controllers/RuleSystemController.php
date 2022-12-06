<?php

namespace API;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\Section;

class RuleSystemController
{
    /*** --------------------------------------------- ***/
    /*** --------------- Course Section ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Create a new section in the system and add it to the course.
     *
     * @throws Exception
     */
    public function createSection()
    {
        API::requireValues('courseId', 'name');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        // Get values
        $name = API::getValue("name");

        // Add section to system
        $section = Section::addSection($courseId, $name);

        // Add section to course missing? -- NOT SURE

        $sectionInfo = $section->getData();
        API::response($sectionInfo);
    }

    /*** --------------------------------------------- ***/
    /*** --------------- Course Rules ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function getCourseRules()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $active = API::getValue("active", "bool");

        $courseRules = $course -> getCourseRules($active);
        foreach ($courseRules as &$courseRuleInfo) {
            $course->getCourseRuleById($courseRuleInfo["id"]);
        }
        API::response($courseRules);
    }

    /**
     * Creates a new rule in the system and adds it to the course
     *
     * @throws Exception
     */
    public function createCourseRule()
        // CHECK THIS -- INCOMPLETE
    {
        API::requireValues('courseId', 'name', 'section');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        // Get values
        $name = API::getValue("name");
        $description = API::getValue("description");
        $when = API::getValue("when");
        $then = API::getValue("then");
        $isActive = API::getValue("isActive");
        $tags = API::getValue("tags");
        $section = API::getValue("section");

        // Add rule to system
        $rule = Rule::addRule($name, $description, $when, $then, $isActive, $tags, $section);

        // Add rule to course
        $courseRule = $course->AddRuleToCourse($rule->getId());

        $courseRuleInfo = $courseRule->getData();
        API::response($courseRuleInfo);

    }
}