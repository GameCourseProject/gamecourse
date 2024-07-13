<?php
namespace GameCourse\Views\Dictionary;

/**
 * This is the Dictionary Function model, which implements
 * the necessary methods to interact with dictionary functions.
 */
class DFunction
{
    public function __construct(private string $name, private array $args, private string $description, private string $returnType, private Library $library)
    {
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
