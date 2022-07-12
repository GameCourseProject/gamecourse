<?php
namespace GameCourse\Views\Dictionary;

/**
 * This is the Dictionary Function model, which implements
 * the necessary methods to interact with dictionary functions.
 */
class DFunction
{
    private $name;
    private $description;
    private $returnType;
    private $library;

    public function __construct(string $name, string $description, string $returnType, Library $library)
    {
        $this->name = $name;
        $this->description = $description;
        $this->returnType = $returnType;
        $this->library = $library;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function getLibrary(): Library
    {
        return $this->library;
    }
}
