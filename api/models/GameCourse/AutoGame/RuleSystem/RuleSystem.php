<?php
namespace GameCourse\AutoGame\RuleSystem;

use Exception;
use GameCourse\Course\Course;
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
        unlink(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt");
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
        return Rule::getRulesOfCourse($courseId, $active);
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
}
