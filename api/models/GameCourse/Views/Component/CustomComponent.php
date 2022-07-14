<?php
namespace GameCourse\Views\Component;


use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Views\ViewHandler;

/**
 * This is the Custom Component model, which implements the necessary methods
 * to interact with custom view components (from users) in the MySQL database.
 */
class CustomComponent extends Component
{
    const TABLE_CUSTOM_COMPONENT = 'component_custom';


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getCreationTimestamp(): string
    {
        return $this->getData("creationTimestamp");
    }

    public function getUpdateTimestamp(): string
    {
        return $this->getData("updateTimestamp");
    }

    public function getCourse(): Course
    {
        return Course::getCourseById($this->getData("course"));
    }

    public function getModule(): ?Module
    {
        return Module::getModuleById($this->getData("module"), $this->getCourse());
    }

    /**
     * Gets custom component data from the database.
     *
     * @example getData() --> gets all custom component data
     * @example getData("name") --> gets custom component name
     * @example getData("name, course") --> gets custom component name & course
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select(self::TABLE_CUSTOM_COMPONENT, ["viewRoot" => $this->viewRoot], $field);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    public function setCreationTimestamp(string $timestamp)
    {
        $this->setData(["creationTimestamp" => $timestamp]);
    }

    public function setUpdateTimestamp(string $timestamp)
    {
        $this->setData(["updateTimestamp" => $timestamp]);
    }

    public function setCourse(Course $course)
    {
        $this->setData(["course" => $course->getId()]);
    }

    public function setModule(?Module $module)
    {
        $this->setData(["module" => $module ? $module->getId() : null]);
    }

    /**
     * Sets custom component data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "course" => 1])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_CUSTOM_COMPONENT, $fieldValues, ["viewRoot" => $this->viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets custom components of a given course.
     *
     * @param int $courseId
     * @return array
     */
    public static function getComponentsOfCourse(int $courseId): array
    {
        $components = Core::database()->selectMultiple(self::TABLE_CUSTOM_COMPONENT, ["course" => $courseId]);
        foreach ($components as &$component) { $component = self::parse($component); }
        return $components;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------- Component Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a custom component to the database.
     * Returns the newly created component.
     *
     * @param array $viewTree
     * @param string $name
     * @param int $courseId
     * @param string|null $moduleId
     * @return CustomComponent
     * @throws Exception
     */
    public static function addComponent(array $viewTree, string $name, int $courseId, string $moduleId = null): CustomComponent
    {
        // Add view tree of component
        $viewRoot = ViewHandler::insertViewTree($viewTree, $courseId);

        // Create new component
        Core::database()->insert(self::TABLE_CUSTOM_COMPONENT, [
            "viewRoot" => $viewRoot,
            "name" => $name,
            "course" => $courseId,
            "module" => $moduleId
        ]);
        return new CustomComponent($viewRoot);
    }

    /**
     * Deletes a custom component from the database.
     *
     * @param int $viewRoot
     * @return void
     */
    public static function deleteComponen(int $viewRoot) {
        Core::database()->delete(self::TABLE_CUSTOM_COMPONENT, ["viewRoot" => $viewRoot]);
    }

    /**
     * Checks whether custom component exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("viewRoot"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a custom component coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $component
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    public static function parse(array $component = null, $field = null, string $fieldName = null)
    {
        if ($component) {
            if (isset($component["viewRoot"])) $component["viewRoot"] = intval($component["viewRoot"]);
            if (isset($component["course"])) $component["course"] = intval($component["course"]);
            return $component;

        } else {
            if ($fieldName == "viewRoot" || $fieldName == "course") return intval($field);
            return $field;
        }
    }
}
