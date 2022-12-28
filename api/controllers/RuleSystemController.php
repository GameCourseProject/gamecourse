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
    /*** ------------------  Section ----------------- ***/
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
     * Create a new section in the system.
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

        $sectionInfo = $section->getData();
        API::response($sectionInfo);
    }

    /*** --------------------------------------------- ***/
    /*** ------------------- Rules ------------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function getRulesOfSection()
    {
        API::requireValues("courseId", "sectionId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $sectionId = API::getValue("sectionId", "int");
        $active = API::getValue("active", "bool");

        $sectionRules = Rule::getRulesOfSection($sectionId, $active);
        foreach ($sectionRules as &$sectionRuleInfo) {
            Rule::getRuleById($sectionRuleInfo["id"]);
        }
        API::response($sectionRules);
    }


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
     * Creates a new rule in the system
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

        $ruleInfo = $rule->getData();
        API::response($ruleInfo);
    }

    /**
     * Removes a rule from a given section
     *
     * @throws Exception
     */
    public function removeRuleFromSection()
    {
        API::requireValues('sectionId', 'ruleId');

        $sectionId = API::getValue('sectionId', "int");
        $ruleId = API::getValue('ruleId', "int");

        $section = Section::getSectionById($sectionId);
        $section->removeRule($ruleId);
    }

    /*** --------------------------------------------- ***/
    /*** -------------------- Tags ------------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Create a new tag in the system
     *
     * @throws Exception
     */
    public function createTag()
    {
        API::requireValues('courseId', 'name', 'color');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        // Get values
        $name = API::getValue("name");
        $color = API::getValue("color");

        // Add tag to system
        $tag = Tag::addTag($courseId, $name, $color);

        $tagInfo = $tag->getData();
        API::response($tagInfo);
    }

}