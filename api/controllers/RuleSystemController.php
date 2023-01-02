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
        API::requireValues("courseId", "section");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $section = API::getValue("section", "int");
        $active = API::getValue("active", "bool");

        $sectionRules = Rule::getRulesOfSection($section, $active);
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
        API::requireValues('course', 'section', 'name', 'description', 'whenClause',
            'thenClause', 'position', 'tags');

        $course = API::getValue("course", "int");
        $courseEntity = API::verifyCourseExists($course);

        API::requireCourseAdminPermission($courseEntity);

        // Get values
        $section = API::getValue("section", "int");
        $name = API::getValue("name");
        $description = API::getValue("description");
        $whenClause = API::getValue("whenClause");
        $thenClause = API::getValue("thenClause");
        $position= API::getValue("position", "int");
        $tags = API::getValue("tags");


        // Add rule to system
        $rule = Rule::addRule($course, $section, $name, $description, $whenClause, $thenClause, $position, true, $tags);

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
        API::requireValues('section', 'ruleId');

        $sectionId = API::getValue('section', "int");
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
        API::requireValues('course', 'name', 'color');

        $courseId = API::getValue("course", "int");
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

    /**
     * Gets all tag from a rule
     *
     * @throws Exception
     */
    public function getRuleTags(){
        API::requireValues("courseId","ruleId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        $ruleId = API::getValue("ruleId", "int");

        API::requireCourseAdminPermission($course);

        $ruleTags = Tag::getRuleTags($ruleId);
        foreach ($ruleTags as &$ruleTagInfo) {
            Tag::getTagById($ruleTagInfo["id"]);
        }
        API::response($ruleTags);

    }

    /**
     * Gets all tag from a course
     *
     * @throws Exception
     */
    public function getTags(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $tags = Tag::getTags($courseId);
        foreach ($tags as $tagInfo){
            Tag::getTagById($tagInfo["id"]);
        }
        API::response($tags);
    }

}