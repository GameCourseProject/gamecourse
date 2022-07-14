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
        $componentClass = self::getComponentClassOfType($type);
        $component = new $componentClass($viewRoot);
        if ($component->exists()) return $component;
        else return null;
    }

    /**
     * Gets components of a given type.
     *
     * @param string $type
     * @return array
     */
    public static function getComponentsOfType(string $type): array
    {
        $componentClass = self::getComponentClassOfType($type);
        return $componentClass::{"getComponents"}();
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Helpers --------------------- ***/
    /*** ---------------------------------------------------- ***/

    private static function getComponentClassOfType(string $type): string
    {
        return "\\GameCourse\\Views\\Component\\" . ucfirst($type) . "Component";
    }
}