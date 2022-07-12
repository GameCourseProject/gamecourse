<?php
namespace GameCourse\Views\Dictionary;

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


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

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
}
