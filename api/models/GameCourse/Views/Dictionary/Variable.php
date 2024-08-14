<?php
namespace GameCourse\Views\Dictionary;

/**
 * This is the Variable model, which implements the necessary methods
 * to interact with dictionary variables.
 */
class Variable
{
    public function __construct(private string $id, private string $name, private string $type, private string $description, private Library $library)
    {
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLibrary(): Library
    {
        return $this->library;
    }
}