<?php

namespace API;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\AutoGame\RuleSystem\Tag;

class RuleSystemController
{
    /*** --------------------------------------------- ***/
    /*** --------------- Course Section ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets section of specific course
     *
     * @throws Exception
     */
    public function getCourseSections(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $courseSections = Section::getSections($courseId);
        foreach ($courseSections as &$courseSectionInfo) {
            Section::getSectionById($courseSectionInfo["id"]);
        }
        API::response($courseSections);
    }

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

        $courseRules = Rule::getRules($courseId, $active);
        foreach ($courseRules as &$courseRuleInfo) {
            Rule::getRuleById($courseRuleInfo["id"]);
        }
        API::response($courseRules);
    }

    /**
     * Creates a new rule in the system and adds it to the course
     *
     * @throws Exception
     */
    public function createRule()
    {
        API::requireValues('courseId', 'sectionId', 'name', 'description', 'when',
            'then', 'position', 'tags');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        // Get values
        $sectionId = API::getValue("sectionId");
        $name = API::getValue("name");
        $description = API::getValue("description");
        $when = API::getValue("when");
        $then = API::getValue("then");
        $position= API::getValue("position");
        $tags = API::getValue("tags");


        // Add rule to system
        $rule = Rule::addRule($courseId, $sectionId, $name, $description, $when, $then, $position, true, $tags);

        // Add rule to course
        $courseRule = $course->AddRuleToCourse($rule->getId());

        $courseRuleInfo = $courseRule->getData();
        API::response($courseRuleInfo);
    }

    public function getCourseTags(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $active = API::getValue("active", "bool");

        $courseTags = RuleSystem::getTags($courseId);
        foreach ($courseTags as &$courseTagInfo){
            Tag::getTagById($courseTagInfo["id"]);
        }
        API::response($courseTags);
    }

}