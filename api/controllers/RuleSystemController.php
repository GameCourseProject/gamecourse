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
        API::requireValues('course', 'name', 'position');

        $courseId = API::getValue("course", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        // Get values
        $name = API::getValue("name");
        $position = API::getValue("position");

        // Add section to system
        $section = Section::addSection($courseId, $name);

        $sectionInfo = $section->getData();
        API::response($sectionInfo);
    }

    /**
     * Edits a section from a specific course
     *
     * @throws Exception
     */
    public function editSection()
    {
        API::requireValues('id', 'course', 'name', 'position');

        $courseId = API::getValue("course", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireAdminPermission($course);

        $sectionId = API::getValue("id", "int");
        $section = Section::getSectionById($sectionId);

        // Get values
        $name = API::getValue("name");
        $position = API::getValue("position", "int");

        $section->editSection($name, $position);
        $sectionInfo = $section->getData();

        API::response($sectionInfo);
    }

    /**
     * Deletes section and assigned rules from system
     *
     * @throws Exception
     */
    public function deleteSection(){
        API::requireValues('sectionId', 'rules');

        $sectionId = API::getValue("sectionId", "int");
        $rules = API::getValue("rules");

        Section::deleteSection($sectionId);

        // remove rules?

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
            'thenClause', 'position', 'isActive', 'tags');

        $course = API::getValue("course", "int");
        $courseEntity = API::verifyCourseExists($course);

        API::requireCourseAdminPermission($courseEntity);

        // Get values
        $section = API::getValue("section", "int");
        $name = API::getValue("name");
        $description = API::getValue("description");
        $whenClause = API::getValue("whenClause");
        $thenClause = API::getValue("thenClause");
        $position = API::getValue("position", "int");
        $isActive = API::getValue("isActive");
        $tags = API::getValue("tags");

        // Add rule to system
        $rule = Rule::addRule($course, $section, $name, $description, $whenClause, $thenClause, $position, $isActive, $tags);

        $ruleInfo = $rule->getData();
        API::response($ruleInfo);
    }

    /**
     * Edits a rule from a given section
     *
     * @throws Exception
     */
    public function editRule()
    {
        API::requireValues('id', 'course', 'name', 'description', 'whenClause',
            'thenClause', 'position', 'isActive', 'tags');

        $courseId = API::getValue("course", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $ruleId = API::getValue("id", "int");
        $rule = Rule::getRuleById($ruleId);

        //Get values
        $name = API::getValue("name");
        $description = API::getValue("description");
        $whenClause = API::getValue("whenClause");
        $thenClause = API::getValue("thenClause");
        $position = API::getValue("position", "int");
        $isActive = API::getValue("isActive");
        $tags = API::getValue("tags");

        // Edit rule
        $rule->editRule($name, $description, $whenClause, $thenClause, $position, $isActive, $tags);

        $ruleInfo = $rule->getData();
        $ruleInfo["tags"] = $rule->getTags();
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

    /**
     * Removes a specific tag from a course given tag id
     *
     * @throws Exception
     */
    public function removeTag(){
        API::requireValues("courseId", "tagId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $tagId = API::getValue("tagId", "int");
        Tag::deleteTag($tagId);
    }

}