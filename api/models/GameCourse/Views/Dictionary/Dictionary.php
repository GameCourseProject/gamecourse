<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use InvalidArgumentException;
use Faker\Factory;
use Faker\Generator;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ExpressionLanguage\ValueNode;
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


    private $course;
    private $views = [];
    private $viewIdsWithLoopData = [];
    private $visitor;

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    /**
     * @throws Exception
     */
    public function getView(int $viewId): array
    {
        if (!in_array($viewId, array_keys($this->views)))
            throw new Exception("View with ID = $viewId is not stored in the dictionary.");
        return $this->views[$viewId];
    }

    /**
     * @throws Exception
     */
    public function storeView(array $view)
    {
        $viewId = $view["id"];
        if (in_array($viewId, array_keys($this->views)))
            throw new Exception("View with ID = $viewId is already stored in the dictionary.");
        $this->views[$viewId] = $view;
    }

    public function getViewIdsWithLoopData(): array
    {
        return $this->viewIdsWithLoopData;
    }

    public function setViewIdsWithLoopData(array $viewIds)
    {
        $this->viewIdsWithLoopData = $viewIds;
    }

    /**
     * @throws Exception
     */
    public function storeViewIdAsViewWithLoopData(int $viewId)
    {
        if (in_array($viewId, array_keys($this->viewIdsWithLoopData)))
            throw new Exception("View with ID = $viewId is already stored in the dictionary as a view with loop data.");
        $this->viewIdsWithLoopData[] = $viewId;
    }

    public function getVisitor(): EvaluateVisitor
    {
        return $this->visitor;
    }

    public function setVisitor(EvaluateVisitor $visitor)
    {
        $this->visitor = $visitor;
    }

    public function cleanViews() {
        $this->views = [];
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

    private $mockData;
    private $faker;

    public function mockData(): bool
    {
        return $this->mockData;
    }

    public function faker(): Generator
    {
        return $this->faker;
    }


    /**
     * Calls a dictionary function defined in a given library.
     *
     * The arguments format is as follows:
     *  - whether to mock data
     *  - if a course is passed, the course it refers to
     *  - if context data is passed, the context data
     *  - actual arguments passed in function
     *
     * @param Course|null $course
     * @param string $libraryId
     * @param string $funcName
     * @param array $args
     * @param null $context
     * @param bool $mockData
     * @return ValueNode
     * @throws Exception
     */
    public function callFunction(?Course $course, string $libraryId, string $funcName, array $args, $context = null, bool $mockData = false): ValueNode
    {
        $library = $this->getLibraryById($libraryId, $course);

        // Check function is defined on library
        if (!$library->hasFunction($funcName))
            throw new Exception("Function '" . $funcName . "' is not defined on library '" . $library->getName() . "'.");

        // Add context
        if ($context !== null) array_unshift($args, $context);

        // Check number and types of arguments
        $ref = $library->getFunctionReflection($funcName);
        foreach ($ref->getParameters() as $index => $parameter) {
            if (array_key_exists($index, $args)) {
                $expectedType = $parameter->getType();

                if ($expectedType) {
                    $actualType = get_debug_type($args[$index]);
                    $expectedTypeName = $expectedType->getName();

                    if ($expectedTypeName == "bool" && ($args[$index] == 1 || $args[$index] == 0)) continue;
                    if ($expectedTypeName == "float" && $actualType == "int") continue;
                    if ($index >= $ref->getNumberOfRequiredParameters() && $actualType == "null") continue;

                    if ($expectedTypeName !== $actualType && !is_a($args[$index], $expectedTypeName)) {
                        throw new Exception("Argument " . ($index + 1) . " passed to function '" . $funcName . "' must be of the type " . $expectedTypeName . ", " . $actualType . " given.");
                    }
                }
            } else if ($index < $ref->getNumberOfRequiredParameters()) {
                throw new Exception("Function '$funcName' requires more arguments than provided.");
            }
        }

        // Add course
        if ($course) $this->course = $course;

        // Mock data
        $this->mockData = $mockData;
        $this->faker = Factory::create(); // Check out https://fakerphp.github.io/

        // Call function
        try {
            return $library->{$funcName}(...$args);
        } catch (InvalidArgumentException $e) {
            $errorMessage = $e->getMessage();
            $position = strpos($errorMessage, ':');
            if ($position !== false) {
                throw new Exception(substr($errorMessage, 0, $position) . " on function $funcName" . substr($errorMessage, $position));
            }
        }
    }
}