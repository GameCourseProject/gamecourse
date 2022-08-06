<?php
namespace GameCourse\AutoGame;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the Rule System class, which implements the necessary methods
 * to interact with autogame rule system.
 */
abstract class RuleSystem
{
    const TABLE_RULE_SECTION = "rule_section";
    const TABLE_RULE_TAG = "rule_tag";

    const DATA_FOLDER = "rules";


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
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
     * Gets a section by its ID.
     * Returns null if section doesn't exist.
     *
     * @param int $id
     * @return array|null
     */
    public static function getSectionById(int $id): ?array
    {
        $section = Core::database()->select(self::TABLE_RULE_SECTION, ["id" => $id]);
        if (!empty($section)) return $section;
        else return null;
    }

    /**
     * Gets a section by its name.
     * Returns null if section doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return array|null
     */
    public static function getSectionByName(int $courseId, string $name): ?array
    {
        $section = Core::database()->select(self::TABLE_RULE_SECTION, ["course" => $courseId, "name" => $name]);
        if (!empty($section)) return $section;
        else return null;
    }

    /**
     * Gets a section by its position.
     * Returns null if section doesn't exist.
     *
     * @param int $courseId
     * @param int $position
     * @return array|null
     */
    public static function getSectionByPosition(int $courseId, int $position): ?array
    {
        $section = Core::database()->select(self::TABLE_RULE_SECTION, ["course" => $courseId, "position" => $position]);
        if (!empty($section)) return $section;
        else return null;
    }

    /**
     * Gets sections in the rule system.
     *
     * @param int $courseId
     * @return array
     */
    public static function getSections(int $courseId): array
    {
        $sections = Core::database()->selectMultiple(self::TABLE_RULE_SECTION, ["course" => $courseId], "*", "position");
        foreach ($sections as &$section) { $section = self::parseSection($section); }
        return $sections;
    }

    /**
     * Adds a section to the database.
     * Returns the newly created section.
     *
     * @param int $courseId
     * @param string $name
     * @return array
     * @throws Exception
     */
    public static function addSection(int $courseId, string $name): array
    {
        self::validateSection($name);
        $position = count(self::getSections($courseId));
        $section = [
            "course" => $courseId,
            "name" => $name,
            "position" => $position
        ];
        $id = Core::database()->insert(self::TABLE_RULE_SECTION, $section);
        $section["id"] = $id;
        file_put_contents(self::getSectionFile($courseId, $id, $section["position"] + 1, $name), "");
        return $section;
    }

    /**
     * Edits an existing section in the database.
     * Returns the edited section.
     *
     * @param int $id
     * @param string $name
     * @param int $position
     * @return array
     * @throws Exception
     */
    public static function editSection(int $id, string $name, int $position): array
    {
        $section = self::getSectionById($id);
        $courseId = $section["course"];

        // Update name
        $oldName = $section["name"];
        if ($name != $oldName) {
            self::validateSection($name);
            Core::database()->update(self::TABLE_RULE_SECTION, ["name" => $name], ["id" => $id]);
            rename(self::getSectionFile($courseId, $id, $section["position"] + 1, $oldName),
                self::getSectionFile($courseId, $id, $section["position"] + 1, $name));
            $section["name"] = $name;
        }

        // Update position
        $oldPosition = $section["position"];
        if ($position != $oldPosition) {
            $switchWith = self::getSectionByPosition($courseId, $position);
            Core::database()->update(self::TABLE_RULE_SECTION, ["position" => null], ["id" => $id]);
            Core::database()->update(self::TABLE_RULE_SECTION, ["position" => $oldPosition], ["id" => $switchWith["id"]]);
            Core::database()->update(self::TABLE_RULE_SECTION, ["position" => $position], ["id" => $id]);
            rename(self::getDataFolder($courseId) . "/" . ($oldPosition + 1) . " - " . $name . ".txt",
                self::getDataFolder($courseId) . "/" . ($position + 1) . " - " . $name . ".txt");
            $section["position"] = $position;
        }

        return $section;
    }

    /**
     * Deletes a section from the database.
     *
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function deleteSection(int $id)
    {
        $section = self::getSectionById($id);
        $courseId = $section["course"];

        // Update position
        $sections = self::getSections($courseId);
        $pos = $section["position"];
        Core::database()->update(self::TABLE_RULE_SECTION, ["position" => null], ["id" => $id]);

        $moveUp = array_filter($sections, function ($s) use ($pos) { return $s["position"] > $pos; });
        foreach ($moveUp as $s) {
            self::editSection($s["id"], $s["name"], $s["position"] - 1);
        }

        Core::database()->delete(self::TABLE_RULE_SECTION, ["id" => $id]);
        unlink(self::getSectionFile($courseId, $id, $section["position"] + 1, $section["name"]));
    }

    /**
     * Gets section file name.
     *
     * @param int $courseId
     * @param int $sectionId
     * @param int|null $priority
     * @param string|null $name
     * @return string
     */
    public static function getSectionFile(int $courseId, int $sectionId, int $priority = null, string $name = null): string
    {
        if (is_null($priority) || is_null($name)) {
            $section = self::getSectionById($sectionId);
            $priority = $section["position"] + 1;
            $name = $section["name"];
        }
        return self::getDataFolder($courseId) . "/" . $priority . " - " . Utils::strip($name, "_") . ".txt";
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tags ----------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a tag by its ID.
     * Returns null if tag doesn't exist.
     *
     * @param int $id
     * @return array|null
     */
    public static function getTagById(int $id): ?array
    {
        $tag = Core::database()->select(self::TABLE_RULE_TAG, ["id" => $id]);
        if (!empty($tag)) return $tag;
        else return null;
    }

    /**
     * Gets a tag by its name.
     * Returns null if tag doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return array|null
     */
    public static function getTagByName(int $courseId, string $name): ?array
    {
        $tag = Core::database()->select(self::TABLE_RULE_TAG, ["course" => $courseId, "name" => $name]);
        if (!empty($tag)) return $tag;
        else return null;
    }

    /**
     * Gets tags in the rule system.
     *
     * @param int $courseId
     * @return array
     */
    public static function getTags(int $courseId): array
    {
        $tags = Core::database()->selectMultiple(self::TABLE_RULE_TAG, ["course" => $courseId], "*", "name");
        foreach ($tags as &$tag) { $tag = self::parseTag($tag); }
        return $tags;
    }

    /**
     * Adds a tag to the database.
     * Returns the newly created tag.
     *
     * @param int $courseId
     * @param string $name
     * @param string $color
     * @return array
     * @throws Exception
     */
    public static function addTag(int $courseId, string $name, string $color): array
    {
        self::validateTag($name, $color);
        $tag = [
            "course" => $courseId,
            "name" => $name,
            "color" => $color
        ];
        $id = Core::database()->insert(self::TABLE_RULE_TAG, $tag);
        $tag["id"] = $id;
        return $tag;
    }

    /**
     * Edits an existing tag in the database.
     * Returns the edited tag.
     *
     * @param int $id
     * @param string $name
     * @param string $color
     * @return array
     * @throws Exception
     */
    public static function editTag(int $id, string $name, string $color): array
    {
        self::validateTag($name, $color);
        $tag = self::getTagById($id);

        // Update name
        $oldName = $tag["name"];
        if ($name != $oldName) {
            Core::database()->update(self::TABLE_RULE_TAG, ["name" => $name], ["id" => $id]);
            // TODO: update tag name on all rules
            $tag["name"] = $name;
        }

        // Update color
        $oldColor = $tag["color"];
        if ($color != $oldColor) {
            Core::database()->update(self::TABLE_RULE_TAG, ["color" => $color], ["id" => $id]);
            $tag["color"] = $color;
        }

        return $tag;
    }

    /**
     * Deletes a tag from the database.
     *
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function deleteTag(int $id)
    {
        $tag = self::getTagById($id);
        Core::database()->delete(self::TABLE_RULE_TAG, ["id" => $id]);
        // TODO: remove tag from all rules
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


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates section parameters.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateSection($name)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Rule section name can't be null neither empty.");

        preg_match("/[^\w()\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Rule section name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Rule section name is too long: maximum of 50 characters.");
    }

    /**
     * Validates tag parameters.
     *
     * @param $name
     * @param $color
     * @return void
     * @throws Exception
     */
    private static function validateTag($name, $color)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Tag name can't be null neither empty.");

        preg_match("/[^\w()\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Tag name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Tag name is too long: maximum of 50 characters.");

        preg_match("/^#[\w\d]{6}$/", $color, $matches);
        if (!is_string($color) || empty($color) || count($matches) == 0)
            throw new Exception("Tag color needs to be in HEX format.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a section coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $section
     * @return array|null
     */
    private static function parseSection(array $section): ?array
    {
        if (isset($section["id"])) $section["id"] = intval($section["id"]);
        if (isset($section["course"])) $section["course"] = intval($section["course"]);
        if (isset($section["position"])) $section["position"] = intval($section["position"]);
        return $section;
    }

    /**
     * Parses a tag coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $tag
     * @return array|null
     */
    private static function parseTag(array $tag): ?array
    {
        if (isset($tag["id"])) $tag["id"] = intval($tag["id"]);
        if (isset($tag["course"])) $tag["course"] = intval($tag["course"]);
        return $tag;
    }
}
