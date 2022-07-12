<?php
namespace GameCourse\Views\Dictionary;

/**
 * This is the Variable model, which implements the necessary methods
 * to interact with dictionary variables.
 */
class Variable
{
    private $id;
    private $name;
    private $type;
    private $description;
    private $library;

    public function __construct(string $id, string $name, string $type, string $description, Library $library)
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->library = $library;
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