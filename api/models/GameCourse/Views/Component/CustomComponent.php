<?php
namespace GameCourse\Views\Component;


use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Views\CreationMode;
use GameCourse\Views\ViewHandler;
use PDOException;

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
     * @param array $fieldValues
     * @return void
     * @throws Exception
     * @example setData(["name" => "New name", "course" => 1])
     *
     * @example setData(["name" => "New name"])
     */
    public function setData(array $fieldValues)
    {
        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($fieldValues["name"]);

        // Update data
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_CUSTOM_COMPONENT, $fieldValues, ["viewRoot" => $this->viewRoot]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets custom components of a given course.
     *
     * @param int|null $courseId
     * @return array
     * @throws Exception
     */
    public static function getComponents(int $courseId = null): array
    {
        if ($courseId === null)
            throw new Exception("Can't get custom components of course: no course given.");

        $components = Core::database()->selectMultiple(self::TABLE_CUSTOM_COMPONENT, ["course" => $courseId]);
        foreach ($components as &$component) { $component = self::parse($component); }
        return $components;
    }

    /**
     * Updates custom component's updateTimestamp to current time.
     *
     * @return void
     * @throws Exception
     */
    public function refreshUpdateTimestamp()
    {
        $this->setUpdateTimestamp(date("Y-m-d H:i:s", time()));
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------- Component Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a custom component to the database.
     * Returns the newly created component.
     *
     * @param string $creationMode
     * @param string $name
     * @param int $courseId
     * @param int|null $viewRoot
     * @param array|null $viewTree
     * @param string|null $moduleId
     * @return CustomComponent
     * @throws Exception
     */
    public static function addComponent(string $creationMode, string $name, int $courseId, int $viewRoot = null,
                                        ?array $viewTree = null, string $moduleId = null): CustomComponent
    {
        self::validateName($name);

        if ($creationMode == CreationMode::BY_VALUE) {
            if ($viewTree) {
                // Verify view tree only has course aspects
                try {
                    ViewHandler::getAspectsInViewTree(null, $viewTree, $courseId);

                } catch (PDOException $e) {
                    $error = $e->getMessage();
                    preg_match("/Role with name '(.+)' doesn't exist/", $error, $matches);
                    if (!empty($matches)) {
                        $roleName = $matches[1];
                        throw new Exception("Role with name '" . $roleName . "' not found in course with ID = " . $courseId . "." .
                            "Add this role to the course first before adding this custom component.");
                    }
                }
            } else $viewTree = ViewHandler::ROOT_VIEW;

            // Add view tree of component
            $viewRoot = ViewHandler::insertViewTree($viewTree, $courseId);

        } else if ($creationMode == CreationMode::BY_REFERENCE) {
            if ($viewRoot === null)
                throw new Exception("Can't add custom component by reference: no view root given.");
        }

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
     * Adds a custom component to the database by copying from another
     * existing custom component.
     *
     * @param int $copyFrom
     * @return CustomComponent
     * @throws Exception
     */
    public static function copyComponent(int $copyFrom): CustomComponent
    {
        $componentToCopy = self::getComponentByViewRoot(ComponentType::CORE, $copyFrom);
        if (!$componentToCopy) throw new Exception("Component to copy from with view root = " . $copyFrom . " doesn't exist.");
        $componentInfo = $componentToCopy->getData();

        // Create copy
        $name = $componentInfo["name"] . " (Copy)";
        $viewTree = ViewHandler::buildView($componentInfo["viewRoot"]);
        return self::addComponent(CreationMode::BY_VALUE, $name, $componentInfo["course"], null, $viewTree);
    }

    /**
     * Edits an existing custom components in database.
     * Returns the edited custom component.
     *
     * @param string $name
     * @return $this
     * @throws Exception
     */
    public function editComponent(string $name): CustomComponent
    {
        self::validateName($name);
        $this->setName($name);
        $this->refreshUpdateTimestamp();
        return $this;
    }

    /**
     * Deletes a custom component from the database.
     *
     * @param int $viewRoot
     * @return void
     */
    public static function deleteComponent(int $viewRoot) {
        ViewHandler::deleteViewTree($viewRoot);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates component name.
     *
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Component name can't be null neither empty.");

        if (iconv_strlen($name) > 25)
            throw new Exception("Component name is too long: maximum of 25 characters.");
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
            if ($fieldName == "viewRoot" || $fieldName == "course")
                return is_numeric($field) ? intval($field) : $field;
            return $field;
        }
    }
}
