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

        API::requireAdminPermission();

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

        API::requireAdminPermission();

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
        API::requireAdminPermission();
        API::requireValues('sectionId');

        $sectionId = API::getValue("sectionId", "int");
        Section::deleteSection($sectionId);

    }

    /**
     * Exports rules from a specific section into a .csv file
     *
     * @return void
     * @throws Exception
     */
    public function exportRules(){
        API::requireValues("courseId", "ruleIds");

        // Get values
        $courseId = API::getValue("courseId", "int");
        $ruleIds = API::getValue("ruleIds", "array");

        API::requireAdminPermission();
        $csv = Rule::exportRules($courseId, $ruleIds);

        API::response($csv);
    }

    /**
     *
     * Imports rules to a specific section inside a course
     * @return void
     * @throws Exception
     */
    public function importRules(){
        API::requireAdminPermission();
        API::requireValues("courseId", "sectionId", "file", "replace");

        // Get values
        $courseId = API::getValue("courseId", "int");
        $sectionId = API::getValue("sectionId", "int");

        $file = API::getValue("file");
        $replace = API::getValue("replace", "bool");

        $nrRulesImported = Rule::importRules($courseId, $sectionId, $file, $replace);
        API::response($nrRulesImported);

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
            $rule = Rule::getRuleById($sectionRuleInfo["id"]);
            $sectionRuleInfo["tags"] = $rule->getTags();
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
            $rule = Rule::getRuleById($courseRuleInfo["id"]);
            $courseRuleInfo["tags"] = $rule->getTags();
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
        API::requireAdminPermission();
        API::requireValues('section', 'ruleId');

        $sectionId = API::getValue('section', "int");
        $ruleId = API::getValue('ruleId', "int");

        $section = Section::getSectionById($sectionId);
        $section->removeRule($ruleId);
    }

    /**
     * @throws Exception
     */
    public function duplicateRule(){
        API::requireAdminPermission();
        API::requireValues('ruleId');

        $ruleId = API::getValue('ruleId', "int");

        // Duplicate rule
        $rule = Rule::duplicateRule($ruleId);
        $ruleInfo = $rule->getData();
        API::response($ruleInfo);
    }

    /**
     * Changes status from a specific rule
     *
     * @return void
     * @throws Exception
     */
    public function setCourseRuleActive(){
        API::requireAdminPermission();
        API::requireValues("ruleId", "isActive");

        // Get values
        $ruleId = API::getValue('ruleId', "int");
        $isActive = API::getValue('isActive', "bool");

        $rule = Rule::getRuleById($ruleId);
        $rule->setActive($isActive);
        $ruleInfo = $rule->getData();
        API::response($ruleInfo);
    }

    /**
     * @throws Exception
     */
    public function getRuleFunctions() {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $response = RuleSystem::getRuleFunctions($courseId);
        API::response($response);
    }

    /**
     * @throws Exception
     */
    public function getMetadata(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $response = RuleSystem::getMetadata($courseId);
        API::response($response);
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
        API::requireValues('course', 'name', 'color', 'rules');

        $courseId = API::getValue("course", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        // Get values
        $name = API::getValue("name");
        $color = API::getValue("color");
        $ruleNames = API::getValue("rules", "array");

        // Add tag to system
        $tag = Tag::addTag($courseId, $name, $color);

        foreach ($ruleNames as $ruleName){
            $rule = Rule::getRuleByName($courseId, $ruleName);
            $rule->addTag($tag->getId());
        }

        $tagInfo = $tag->getData();
        $tagInfo["rules"] = Rule::getRulesWithTag($tag->getId());
        API::response($tagInfo);
    }

    /**
     * Edit a tag in the system given a course, tag id and new tag information
     *
     * @throws Exception
     */
    public function editTag(){
        API::requireValues('courseId', 'tagId', 'name', 'color', 'rules');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        // Get values
        $tagId = API::getValue("tagId", "int");
        $name = API::getValue("name");
        $color = API::getValue("color");
        $ruleNames = API::getValue("rules", "array");

        $tag = Tag::getTagById($tagId);
        $tag->editTag($name, $color);

        $rules = [];
        foreach ($ruleNames as $ruleName){
            $rule = Rule::getRuleByName($courseId, $ruleName);
            array_push($rules, $rule);
        }

        $tag->updateRules($rules);

        $response = $tag->getData();
        $response["rules"] = Rule::getRulesWithTag($tag->getId());
        API::response($response);
    }

    /**
     * Gets all tag from a rule
     *
     * @throws Exception
     */
    public function getRuleTags(){
        API::requireValues("courseId", "ruleId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        $ruleId = API::getValue("ruleId", "int");

        API::requireCourseAdminPermission($course);

        $ruleTags = Tag::getRuleTags($ruleId);
        foreach ($ruleTags as &$ruleTagInfo) {
            $tag = Tag::getTagById($ruleTagInfo["id"]);
            $ruleTagInfo["rules"] = Rule::getRulesWithTag($tag->getId());
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
        foreach ($tags as &$tagInfo){
            $tag = Tag::getTagById($tagInfo["id"]);
            $tagInfo["rules"] = Rule::getRulesWithTag($tag->getId());
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



    // FIXME --  delete later
    /**
     * Returns all rules in the system with a specific tag inside a course
     *
     * @return void
     * @throws Exception
     */
    public function getRulesWithTag(){
        API::requireAdminPermission();
        API::requireValues("tagId");

        // Get values
        $tagId = API::getValue("tagId", "int");

        $rules = Rule::getRulesWithTag($tagId);
        foreach ($rules as $ruleInfo) {
            Rule::getRuleById($ruleInfo["id"]);
        }

        API::response($rules);
    }

}