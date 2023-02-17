<?php
namespace GameCourse\Views\Component;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\ViewHandler;
use ReflectionClass;

/**
 * This is the Component model, which implements the necessary methods
 * to interact with view components in the MySQL database.
 */
abstract class Component
{
    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): int
    {
        return $this->id;
    }

    public function getViewRoot(): int
    {
        return intval(Core::database()->select($this::TABLE_COMPONENT, ["id" => $this->id]));
    }

    /**
     * Gets component data from the database.
     *
     * @example getData() --> gets all component data
     * @example getData("field") --> gets component field
     * @example getData("field1, field2") --> gets component fields
     *
     * @param string $field
     * @return mixed
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select($this::TABLE_COMPONENT, ["id" => $this->id], $field);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setViewRoot(int $viewRoot)
    {
        Core::database()->update($this::TABLE_COMPONENT, ["viewRoot" => $viewRoot, ["id" => $this->id]]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a component by its ID.
     * Returns null if component doesn't exist.
     *
     * @param int $id
     * @return Component|null
     */
    public static function getComponentById(int $id): ?Component
    {
        $componentClass = "\\" . get_called_class();
        $component = new $componentClass($id);
        if ($component->exists()) return $component;
        else return null;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------- Component Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Deletes a component from the database.
     * Option to keep views linked (created by reference) or delete
     * them as well.
     *
     * @param int $id
     * @param bool $keepViewsLinked
     * @return void
     */
    protected static function deleteComponent(int $id, bool $keepViewsLinked = true) {
        $component = self::getComponentById($id);
        if ($component) {
            // TODO: go through each view linked to this component and either
            //        replace by a copy (keep = true) or a default view

            // Delete view tree
            ViewHandler::deleteViewTree($component->getViewRoot());
        }
    }

    /**
     * Checks whether component exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty(Core::database()->select($this::TABLE_COMPONENT, ["id" => $this->id]));
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
            $componentClass = "\\GameCourse\\Views\\Component\\" . ucfirst($type) . "Component";
            if (!empty(Core::database()->select($componentClass::TABLE_COMPONENT, ["viewRoot" => $viewRoot]))) {
                $isComponent = true;
                break;
            }
        }
        return $isComponent;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Rendering -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Renders a component.
     * Always renders its default aspect.
     *
     * @return array
     * @throws Exception
     */
    public function renderComponent(): array
    {
        $defaultAspect = Aspect::getAspectBySpecs(0, null, null);
        $sortedAspects = [$defaultAspect->getData("id, viewerRole, userRole")];
        return ViewHandler::renderView($this->getViewRoot(), $sortedAspects, true);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a component coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $component
     * @param $field
     * @param string|null $fieldName
     * @return mixed
     */
    public abstract static function parse(array $component = null, $field = null, string $fieldName = null);
}