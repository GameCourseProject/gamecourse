<?php
namespace GameCourse\Views\Dictionary;

/**
 * This is the Dictionary Function model, which implements
 * the necessary methods to interact with dictionary functions.
 */
class DFunction
{

    private $name;
    private $args;           // [[ name => string, optional => boolean, type => any ]]
    private $description;
    private $returnType;
    private $library;
    private $example;

    public function __construct(string $name, array $args, string $description, string $returnType, Library $library, string $example = null)
    {
        $this->name = $name;
        $this->args = $args;
        $this->description = $description;
        $this->returnType = $returnType;
        $this->library = $library;
        $this->example = $example;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getName(): string
    {
        return $this->name;
    }

    public function getArgs(): array
    {
        return $this->args;
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

    public function getExample(): ?string
    {
        return $this->example;
    }
}
