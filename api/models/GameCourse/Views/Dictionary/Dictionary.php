<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use Utils\Utils;

/**
 * Main dictionary for GameCourse's own Expression Language,
 * which holds libraries, functions and variables.
 * Each module can also define their own extensions for it.
 */
class Dictionary
{
    private static $instance; // singleton

    public static function get(): Dictionary
    {
        if (self::$instance == null) self::$instance = new Dictionary();
        return self::$instance;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Libraries ------------------ ***/
    /*** ----------------------------------------------- ***/

    /**
     * Gets a dictionary library by its ID.
     * Returns null if library doesn't exist in dictionary or
     * is not enabled in a given course.
     *
     * @param Course|null $course
     * @param string $libraryId
     * @return Library
     * @throws Exception
     */
    public function getLibraryById(string $libraryId, Course $course = null): Library
    {
        $libraries = $this->getLibraries($course);
        foreach ($libraries as $library)
            if ($library->getId() == $libraryId) return $library;

        throw new Exception("Library '" . $libraryId ."' doesn't exist" .
            ($course ? " or is not enabled in course with ID = " . $course->getId() : "") . ".");
    }

    /**
     * Gets all dictionary libraries available:
     * core libraries + modules' libraries.
     *
     * If a course is given, will get all dictionary libraries
     * available in that course:
     * core libraries + enabled modules' libraries.
     *
     * @param Course|null $course
     * @return array
     * @throws Exception
     */
    public function getLibraries(Course $course = null): array
    {
        $libraries = [];

        // Core libraries
        $coreLibrariesFolder = ROOT_PATH . "models/GameCourse/Views/Dictionary/libraries/";
        $libraries = array_merge($libraries, $this->getLibrariesInFolder($coreLibrariesFolder));

        // Modules libraries
        if ($course) $moduleIds = $course->getModules(true, true);
        else $moduleIds = Module::getModules(true);
        foreach ($moduleIds as $moduleId) {
            $dictionaryFolder = MODULES_FOLDER . "/" . $moduleId . "/dictionary/";
            if (file_exists($dictionaryFolder))
                $libraries = array_merge($libraries, $this->getLibrariesInFolder($dictionaryFolder));
        }

        return $libraries;
    }

    /**
     * Gets all libraries defined in a given folder.
     *
     * @param string $folder
     * @return array
     * @throws Exception
     */
    private function getLibrariesInFolder(string $folder): array
    {
        $libraries = [];
        $libraryFiles = array_column(Utils::getDirectoryContents($folder), "name");
        foreach ($libraryFiles as $fileName) {
            $libraryClass = "\\GameCourse\\Views\\Dictionary\\" . substr($fileName, 0, -4);
            $libraries[] = new $libraryClass();
        }
        return $libraries;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    /**
     * Calls a dictionary function defined in a given library.
     *
     * @param Course|null $course
     * @param string $libraryId
     * @param string $funcName
     * @param array $args
     * @param null $context
     * @param bool $mockData
     * @return mixed
     * @throws Exception
     */
    public function callFunction(?Course $course, string $libraryId, string $funcName, array $args, $context = null, bool $mockData = false)
    {
        $library = $this->getLibraryById($libraryId, $course);

        // Check function is defined on library
        if (!$library->hasFunction($funcName))
            throw new Exception("Function '" . $funcName . "' is not defined on library '" . $library->getName() . "'.");

        // Add context
        if ($context !== null) array_unshift($args, $context);

        // Mock data
        if ($mockData) $args[] = $mockData;

        // Call function
        return $library->{$funcName}(...$args);
    }
}