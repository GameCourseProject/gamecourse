<?php
namespace GameCourse\Views\Component;


use Exception;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\ViewHandler;

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

    /**
     * Renders a component by getting its entire view tree, as well
     * as its view trees for each of its aspects.
     * Option to populate component with mocked data.
     *
     * @param bool $populate
     * @return array
     * @throws Exception
     */
    public function render(bool $populate = false): array
    {
        // Get entire view tree
        $viewTree = ViewHandler::buildView($this->viewRoot, null, $populate); // FIXME: populate with mocks

        // Get default aspect
        $courseId = method_exists($this, "getCourse") ? $this->getCourse()->getId() : 0;
        $defaultAspect = Aspect::getAspectBySpecs($courseId, null, null);

        // Get view tree for each aspect of component
        $viewTreeByAspect = [];
        $aspects = ViewHandler::getAspectsInViewTree($this->viewRoot);
        foreach ($aspects as $aspect) {
            $viewTreeOfAspect = ViewHandler::buildView($this->viewRoot, [$aspect, $defaultAspect], $populate); // FIXME: populate with mocks
            $viewTreeByAspect[$aspect->getId()] = $viewTreeOfAspect;
        }

        return ["viewTree" => $viewTree, "viewTreeByAspect" => $viewTreeByAspect];
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