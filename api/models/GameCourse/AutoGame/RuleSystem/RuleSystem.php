<?php
namespace GameCourse\AutoGame\RuleSystem;

use Exception;
use GameCourse\Course\Course;
use GameCourse\Views\Dictionary\Dictionary;
use GameCourse\Views\Dictionary\Library;
use Utils\Utils;

/**
 * This is the Rule System class, which implements the necessary methods
 * to interact with autogame rule system.
 */
abstract class RuleSystem
{
    const DATA_FOLDER = "rules";


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Setup ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Initializes Rule System for a given course.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function initRuleSystem(int $courseId)
    {
        // Setup rules folder
        self::createDataFolder($courseId);

        // Setup autogame functions and config
        $functionsFolder = AUTOGAME_FOLDER . "/imported-functions/" . $courseId;
        $functionsFileDefault = AUTOGAME_FOLDER . "/imported-functions/defaults.py";
        $defaultFunctionsFile = "/defaults.py";
        $metadataFile = AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt";
        mkdir($functionsFolder, 0777, true);
        file_put_contents($functionsFolder . $defaultFunctionsFile, file_get_contents($functionsFileDefault));
        file_put_contents($metadataFile, "");

        // FIXME: Check if it makes sense to add here
        Section::addSection($courseId, "Miscellaneous");
    }

    /**
     * Copies Rule System info from one course to another.
     *
     * @param Course $from
     * @param Course $to
     * @return void
     * @throws Exception
     */
    public static function copyRuleSystem(Course $from, Course $to)
    {
        $sections = self::getSections($from->getId());
        foreach ($sections as $section) {
            if (!self::hasSection($to->getId(), $section["name"])) {
                $section = new Section($section["id"]);
                $section->copySection($to);
            }
        }
    }

    /**
     * Deletes Rule System information from a given course.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function deleteRuleSystemInfo(int $courseId)
    {
        self::removeDataFolder($courseId);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions/" . $courseId);
        Utils::deleteFile(AUTOGAME_FOLDER . "/config", "config_" . $courseId . ".txt", false);
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Sections --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets all sections in the Rule System.
     *
     * @param int $courseId
     * @return array
     */
    public static function getSections(int $courseId): array
    {
        return Section::getSections($courseId);
    }

    /**
     * Adds a section to the Rule System.
     * Returns the newly created section.
     *
     * @param int $courseId
     * @param string $name
     * @param int|null $position
     * @param string|null $moduleId
     * @return Section
     * @throws Exception
     */
    public static function addSection(int $courseId, string $name, int $position = null, string $moduleId = null): Section
    {
        return Section::addSection($courseId, $name, $position, $moduleId);
    }

    /**
     * Edits an existing section in the Rule System.
     * Returns the edited section.
     *
     * @param int $sectionId
     * @param string $name
     * @param int $position
     * @return Section
     * @throws Exception
     */
    public static function editSection(int $sectionId, string $name, int $position): Section
    {
        $section = Section::getSectionById($sectionId);
        return $section->editSection($name, $position);
    }

    /**
     * Deletes a section from the Rule System.
     *
     * @param int $sectionId
     * @return void
     * @throws Exception
     */
    public static function deleteSection(int $sectionId)
    {
        Section::deleteSection($sectionId);
    }

    /**
     * Checks whether a given section exists in the Rule System.
     *
     * @param int $courseId
     * @param string|null $sectionName
     * @param int|null $sectionId
     * @return bool
     * @throws Exception
     */
    public static function hasSection(int $courseId, string $sectionName = null, int $sectionId = null): bool
    {
        if ($sectionName === null && $sectionId === null)
            throw new Exception("Need either section name or ID to check whether a section exists in the Rule System.");

        if ($sectionName) $section = Section::getSectionByName($courseId, $sectionName);
        else $section = Section::getSectionById($sectionId);
        return !!$section && $section->getCourse()->getId() == $courseId;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tags ----------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets all tags in the Rule System.
     *
     * @param int $courseId
     * @return array
     */
    public static function getTags(int $courseId): array
    {
        return Tag::getTags($courseId);
    }

    /**
     * Adds a tag to the Rule System.
     * Returns the newly created tag.
     *
     * @param int $courseId
     * @param string $name
     * @param string $color
     * @return Tag
     * @throws Exception
     */
    public static function addTag(int $courseId, string $name, string $color): Tag
    {
        return Tag::addTag($courseId, $name, $color);
    }

    /**
     * Edits an existing tag in the Rule System.
     * Returns the edited tag.
     *
     * @param int $tagId
     * @param string $name
     * @param string $color
     * @return Tag
     * @throws Exception
     */
    public static function editTag(int $tagId, string $name, string $color): Tag
    {
        $tag = Tag::getTagById($tagId);
        return $tag->editTag($name, $color);
    }

    /**
     * Deletes a tag from the Rule System.
     *
     * @param int $tagId
     * @return void
     * @throws Exception
     */
    public static function deleteTag(int $tagId)
    {
        Tag::deleteTag($tagId);
    }

    /**
     * Checks whether a given tag exists in the Rule System.
     *
     * @param int $courseId
     * @param string|null $tagName
     * @param int|null $tagId
     * @return bool
     * @throws Exception
     */
    public static function hasTag(int $courseId, string $tagName = null, int $tagId = null): bool
    {
        if ($tagName === null && $tagId === null)
            throw new Exception("Need either tag name or ID to check whether a tag exists in the Rule System.");

        if ($tagName) $tag = Tag::getTagByName($courseId, $tagName);
        else $tag = Tag::getTagById($tagId);
        return !!$tag && $tag->getCourse()->getId() == $courseId;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Rules ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets all rules in the Rule System.
     *
     * @param int $courseId
     * @param bool|null $active
     * @return array
     * @throws Exception
     */
    public static function getRules(int $courseId, bool $active = null): array
    {
        return Rule::getRules($courseId, $active);
    }

    /**
     * Checks whether a given rule exists in the Rule System.
     *
     * @param int $courseId
     * @param string|null $ruleName
     * @param int|null $ruleId
     * @return bool
     * @throws Exception
     */
    public static function hasRule(int $courseId, string $ruleName = null, int $ruleId = null): bool
    {
        if ($ruleName === null && $ruleId === null)
            throw new Exception("Need either rule name or ID to check whether a rule exists in the Rule System.");

        if ($ruleName) $rule = Rule::getRuleByName($courseId, $ruleName);
        else $rule = Rule::getRuleById($ruleId);
        return !!$rule && $rule->getCourse()->getId() == $courseId;
    }

    /**
     * Gets all rule functions available from autogame for rule editor UI
     *
     * @param int $courseId
     * @return array|mixed
     */
    public static function getRuleFunctions(int $courseId) {
        $dbHost = DB_HOST;
        $dbName = DB_NAME;
        $dbUser = DB_USER;
        $dbPass = DB_PASSWORD;

        $scriptPath = ROOT_PATH . "autogame/get_functions.py";
        $cmd = "python \"$scriptPath\" $courseId \"$dbHost\" \"$dbName\" \"$dbUser\" \"$dbPass\""; //FIXME later --> change "python" to "python3"

        $output = null;
        exec($cmd, $output);
        $funcs = array();
        if ($output != null && sizeof($output) > 0) {
            $funcs = json_decode($output[0]);
        }
        return $funcs;
    }

    /**
     * Gets functions from the EL dictionary
     * @throws Exception
     */
    public static function getELFunctions(): array{
        $dictionary = new Dictionary();
        $libraries = $dictionary->getLibraries();
        $myFunctions = [];

        foreach ($libraries as $library) {
            //var_dump($library->getName());
            $myLibrary = $dictionary->getLibraryById($library->getId());
            $functions = $myLibrary->getFunctions();

            foreach ($functions as $function) {
                $myFunction["name"] = $library->getName();
                $myFunction["keyword"] = $function->getName();
                $myFunction["args"] = $function->getArgs();
                $myFunction["description"] = $function->getDescription();
                $myFunction["returnType"] = $function->getReturnType();
                //var_dump($myFunction);
                array_push($myFunctions, $myFunction);
            }
            //var_dump(count($myFunctions));

        }
        //var_dump($myFunctions);
        return $myFunctions ?? [];
    }

    /**
     * Gets all metadata (aka global variables) available from autogame for rule editor UI
     *
     * @return array|mixed
     */
    public static function getMetadata(int $courseId){

        $scriptPath = ROOT_PATH . "autogame/get_metadata.py";
        $cmd = "python \"$scriptPath\" $courseId"; //FIXME later --> change "python" to "python3"

        $output = null;
        exec($cmd, $output);
        $metadata = array();
        if ($output != null && sizeof($output) > 0){
            $metadata = json_decode($output[0]);
        }

        return $metadata;
    }


    /**
     * Update course metadata (global variables) used by the rule system
     *
     * @param int $courseId
     * @param string $metadata
     * @return string
     * @throws Exception
     */
    public static function updateMetadata(int $courseId, string $metadata): string{
        $metadataPath = ROOT_PATH . "autogame/config/config_" . $courseId . ".txt";
        $res = file_put_contents($metadataPath, $metadata);
        if ($res) return $metadata;
        else return "";
    }

    /**
     * @throws Exception
     */
    public static function previewRule(int $courseId, string $name, string $description, string $whenClause, string $thenClause, bool $isActive, $tags) {
        $ruleTxt = Rule::generateText($name, $description, $whenClause, $thenClause, $isActive, $tags);
        $folderPath = self::createTestDataFolder($courseId);
        $rulePath = $folderPath . "rule.txt";
        file_put_contents($rulePath, $ruleTxt);



    }

    /*** ---------------------------------------------------- ***/
    /*** -------------------- Rules Data -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets rules data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param int $courseId
     * @param bool $fullPath
     * @return string
     */
    public static function getDataFolder(int $courseId, bool $fullPath = true): string
    {
        $course = new Course($courseId);
        return $course->getDataFolder($fullPath) . "/" . self::DATA_FOLDER;
    }

    /**
     * Gets rules data folder contents.
     *
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public function getDataFolderContents(int $courseId): array
    {
        return Utils::getDirectoryContents(self::getDataFolder($courseId));
    }

    /**
     * Creates rules data folder for a given course. If folder exists, it
     * will delete its contents.
     *
     * @param int $courseId
     * @return string
     * @throws Exception
     */
    public static function createDataFolder(int $courseId): string
    {
        $dataFolder = self::getDataFolder($courseId);
        if (file_exists($dataFolder)) self::removeDataFolder($courseId);
        mkdir($dataFolder, 0777, true);
        return $dataFolder;
    }

    /**
     * Deletes a given course's data folder.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function removeDataFolder(int $courseId)
    {
        $dataFolder = self::getDataFolder($courseId);
        if (file_exists($dataFolder)) Utils::deleteDirectory($dataFolder);
    }

    /**
     * Creates rules data testing folder for a given course.
     *
     * @throws Exception
     */
    public static function createTestDataFolder(int $courseId): string
    {
        $dataFolder = self::getDataFolder($courseId) . "/rule-tests/";
        if (!file_exists($dataFolder)) {
            mkdir($dataFolder, 0777, true);
        }
        return $dataFolder;
    }

    /**
     * Deletes a given course's data folder.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function removeTestDataFolder(int $courseId)
    {
        $dataFolder = self::getDataFolder($courseId);
        if (file_exists($dataFolder)) Utils::deleteDirectory($dataFolder);
    }

}
