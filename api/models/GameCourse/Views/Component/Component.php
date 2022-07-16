<?php
namespace GameCourse\Views\Component;


use Exception;
use GameCourse\Views\ViewHandler;
use ReflectionClass;

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
     * Gets components of a specific type.
     *
     * @param int|null $courseId
     * @return array
     */
    public static abstract function getComponents(int $courseId = null): array;


    /*** ---------------------------------------------------- ***/
    /*** -------------- Component Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Deletes a component of a specific type from the database.
     *
     * @param int $viewRoot
     * @return void
     */
    public static abstract function deleteComponent(int $viewRoot);

    /**
     * Renders a component by getting its entire view tree, as well
     * as its view trees for each of its aspects.
     * Option to populate component with mocked data.
     *
     * @param bool|array $populate
     * @return array
     * @throws Exception
     */
    public function render($populate = false): array
    {
        $courseId = method_exists($this, "getCourse") ? $this->getCourse()->getId() : 0;
        return ViewHandler::renderView($this->viewRoot, $courseId, $populate);
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

    /**
     * Checks whether a view root is a component.
     *
     * @param int $viewRoot
     * @return bool
     */
    public static function isComponent(int $viewRoot): bool
    {
        $typeClass = new ReflectionClass(ComponentType::class);
        $types = array_values($typeClass->getConstants());

        $isComponent = false;
        foreach ($types as $type) {
            if (self::getComponentByViewRoot($type, $viewRoot)) $isComponent = true;
        }
        return $isComponent;
    }
}