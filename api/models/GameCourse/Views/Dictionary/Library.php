<?php
namespace GameCourse\Views\Dictionary;

use Exception;

/**
 * This is the Library model, which implements the necessary methods
 * to interact with dictionary libraries.
 */
abstract class Library
{
    private $id;
    private $name;
    private $description;

    public function __construct(string $id, string $name, string $description)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------- Getters ------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Variables ------------------ ***/
    /*** ----------------------------------------------- ***/

    /**
     * Gets library variables metadata. This is useful for
     * Expression Language auto-complete functionality.
     *
     * @return array|null
     */
    public function getVariables(): ?array
    {
        return null;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    /**
     * Gets library functions metadata. This is useful for
     * Expression Language auto-complete functionality.
     *
     * @return array|null
     */
    public function getFunctions(): ?array
    {
        return null;
    }

    /**
     * Adds a new function to the library.
     * NOTE: only core libraries can be extended.
     *
     * @param string $name
     * @param string $description
     * @param string $returnType
     * @param string $function
     * @param array $args
     * @return void
     */
    public function addFunction(string $name, string $description, string $returnType, string $function, array $args = [])
    {
        // Get library contents
        $coreLibrariesFolder = ROOT_PATH . "models/GameCourse/Views/Dictionary/libraries/";
        $libraryFile = $coreLibrariesFolder . ucfirst($this->id) . "Library.php";
        $contents = file_get_contents($libraryFile);

        // Add function info to library
        $pattern = "/(getFunctions\(\)(.|\n)*?{(.|\n)*?return\s*\[[\s\n\t\r]*)((.|\n)*?)([\s\n\t\r]*](.|\n)*?})/";
        preg_match($pattern, $contents, $matches);
        $functions = array_map(function ($f) {
            if (substr($f, -1) == ",") $f = substr($f, 0, strlen($f) - 1);
            return trim($f);
        }, array_filter(explode(",\n", $matches[4]), function ($f) { return !empty($f); }));
        $functions[] = "new DFunction(\"$name\",
            \t\"$description\",
            \t\"$returnType\",
            \t\$this
        \t)";
        $single = count($functions) === 1;
        $contents = preg_replace($pattern, $matches[1] . ($single ? "\n\t\t\t" : "") .
            implode(",\n\t\t\t", $functions) . ($single ? "\n\t\t" : "") . $matches[6], $contents);

        // Add function to library
        $function = "public function $name(" . implode(", ", $args) . "): ValueNode\n\t{\n\t\t$function\n\t}";
        $lines = explode("\n", $contents);
        array_splice($lines, count($lines) - 2, 0, "\n\t$function");
        $contents = implode("\n", $lines);

        file_put_contents($libraryFile, $contents);
    }

    /**
     * Removes a given function from the library.
     * NOTE: only core libraries can be extended.
     *
     * @param string $name
     * @return void
     */
    public function removeFunction(string $name)
    {
        // Get library contents
        $coreLibrariesFolder = ROOT_PATH . "models/GameCourse/Views/Dictionary/libraries/";
        $libraryFile = $coreLibrariesFolder . ucfirst($this->id) . "Library.php";
        $contents = file_get_contents($libraryFile);

        // Remove function info from library
        $pattern = "/[\s\n\t\r]*new DFunction\(\"$name\"(.|\n)*?\),*/";
        $contents = preg_replace($pattern, "", $contents);
        $pattern = "/return \[[\s\n\t\r]*];/";
        $contents = preg_replace($pattern, "return [];", $contents);

        // Remove function from library
        $pattern = "/[\s\n\t\r]*public function $name\((.*\s*)*?return new ValueNode(.)*[\s\n\t\r]*}/";
        preg_match($pattern, $contents, $matches);
        $contents = preg_replace($pattern, "", $contents);

        file_put_contents($libraryFile, $contents);
    }

    /**
     * Checks whether a given function is defined on the library.
     *
     * @param string $funcName
     * @return bool
     */
    public function hasFunction(string $funcName): bool
    {
        $libraryMethods = get_class_methods($this);
        return in_array($funcName, $libraryMethods);
    }

    /**
     * Throws errors inside functions.
     *
     * @param string $funcName
     * @param string $errorMsg
     * @return mixed
     * @throws Exception
     */
    public function throwError(string $funcName, string $errorMsg)
    {
        throw new Exception("On '$funcName' function in " . $this->name . " library: $errorMsg.");
    }
}
