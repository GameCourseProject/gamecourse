<?php
namespace GameCourse\Views\Component;


/**
 * This is the Component model, which implements the necessary methods
 * to interact with view components in the MySQL database.
 */
abstract class Component
{
    protected $viewRoot;

    public function __construct(int $viewRoot)
    {
        $this->viewRoot = $viewRoot;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getViewRoot(): int
    {
        return $this->viewRoot;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a component of a given type by its view root.
     * Returns null if component doesn't exist.
     *
     * @param string $type
     * @param int $viewRoot
     * @return Component|null
     */
    public static function getComponentByViewRoot(string $type, int $viewRoot): ?Component
    {
        $componentClass = "\\GameCourse\\Views\\Component\\" . ucfirst($type) . "Component";
        $component = new $componentClass($viewRoot);
        if ($component->exists()) return $component;
        else return null;
    }

    /**
     * Gets components of a given type.
     *
     * @param int|null $courseId
     * @return array
     */
    public static abstract function getComponents(int $courseId = null): array;


    /*** ---------------------------------------------------- ***/
    /*** -------------- Component Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    public function render(): array
    {
        // TODO
    }

    /**
     * Checks whether component exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("viewRoot"));
    }
}