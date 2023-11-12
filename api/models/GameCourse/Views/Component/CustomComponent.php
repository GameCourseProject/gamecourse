<?php
namespace GameCourse\Views\Component;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\User\User;
use GameCourse\Views\Category\Category;
use GameCourse\Views\CreationMode;
use GameCourse\Views\Logging\Logging;
use GameCourse\Views\ViewHandler;
use Utils\Utils;

/**
 * This is the Custom Component model, which implements the necessary methods
 * to interact with custom view components (from users) in the MySQL database.
 */
class CustomComponent extends Component
{
    const TABLE_COMPONENT = 'component_custom';
    const TABLE_COMPONENT_SHARED = 'component_custom_shared';


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


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    /**
     * @throws Exception
     */
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
        $courseId = $this->getCourse()->getId();

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($courseId, $fieldValues["name"], $this->id);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_COMPONENT, $fieldValues, ["id" => $this->id]);

        $this->refreshUpdateTimestamp();
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets custom components of a given course.
     *
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public static function getComponents(int $courseId): array
    {
        $components = Core::database()->selectMultiple(self::TABLE_COMPONENT, ["course" => $courseId], "*", "name");
        foreach ($components as &$component) { $component = self::parse($component); }
        return $components;
    }

    /**
     * Gets shared components.
     *
     * @return array
     * @throws Exception
     */
    public static function getSharedComponents(): array
    {
        $table = self::TABLE_COMPONENT_SHARED . " s JOIN " . self::TABLE_COMPONENT . " c on s.id = c.id";
        $components = Core::database()->selectMultiple($table, [], "*", "s.sharedTimestamp");
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
     * @return CustomComponent
     * @throws Exception
     */
    public static function addComponent(int $courseId, string $creationMode, string $name, array $viewTree = null,
                                        int $viewRoot = null): CustomComponent
    {
        self::trim($name);
        self::validateName($courseId, $name);

        if ($creationMode == CreationMode::BY_VALUE) {
            if (!$viewTree) $viewTree = ViewHandler::ROOT_VIEW;
            $viewRoot = ViewHandler::insertViewTree($viewTree, $courseId);

        } else if ($creationMode == CreationMode::BY_REFERENCE) {
            if (is_null($viewRoot))
                throw new Exception("Can't add custom component by reference: no view root given.");
        }

        // Insert in database
        $id = Core::database()->insert(self::TABLE_COMPONENT, [
            "viewRoot" => $viewRoot,
            "name" => $name,
            "course" => $courseId
        ]);
        return new CustomComponent($id);
    }

    /**
     * Copies an existing custom component.
     *
     * @param string $creationMode
     * @return CustomComponent
     * @throws Exception
     */
    public function copyComponent(string $creationMode): CustomComponent
    {
        $componentInfo = $this->getData();

        // Copy component
        $name = $componentInfo["name"] . " (Copy)";
        $viewTree = $creationMode === CreationMode::BY_VALUE ? ViewHandler::buildView($componentInfo["viewRoot"], null, true) : null;
        $viewRoot = $creationMode === CreationMode::BY_REFERENCE ? $componentInfo["viewRoot"] : null;
        return self::addComponent($componentInfo["course"], $creationMode, $name, $viewTree, $viewRoot);
    }

    /**
     * Edits an existing custom component in the database.
     * Returns the edited custom component.
     *
     * @param string $name
     * @param array|null $viewTreeChanges
     * @return CustomComponent
     * @throws Exception
     */
    public function editComponent(string $name, ?array $viewTreeChanges = null): CustomComponent
    {
        $this->setName($name);

        // Update view tree, if changes were made
        if ($viewTreeChanges) {
            $logs = $viewTreeChanges["logs"];
            $views = $viewTreeChanges["views"];
            Logging::processLogs($logs, $views, $this->getCourse()->getId());
        }

        return $this;
    }

    /**
     * Deletes a custom component from the database and removes all its views.
     * Option to keep views linked to component (created by reference)
     * intact or to replace them by a placeholder view.
     *
     * @param int $id
     * @param bool $keepLinked
     * @return void
     * @throws Exception
     */
    public static function deleteComponent(int $id, bool $keepLinked = true)
    {
        $component = self::getComponentById($id);
        if ($component) {
            parent::deleteComponent($id, $keepLinked);
            Core::database()->delete(self::TABLE_COMPONENT, ["id" => $id]);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Sharing --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function isShared(): bool
    {
        return !empty(Core::database()->select(self::TABLE_COMPONENT_SHARED, ["id" => $this->id]));
    }

    public function getSharedDescription(): string
    {
        return Core::database()->select(self::TABLE_COMPONENT_SHARED, ["id" => $this->id], "description");
    }

    public function getSharedCategory(): Category
    {
        return Category::getCategoryById(Core::database()->select(self::TABLE_COMPONENT_SHARED, ["id" => $this->id], "category"));
    }

    public function getSharedBy(): User
    {
        return User::getUserById(Core::database()->select(self::TABLE_COMPONENT_SHARED, ["id" => $this->id], "sharedBy"));
    }

    public function getSharedTimestamp(): string
    {
        return Core::database()->select(self::TABLE_COMPONENT_SHARED, ["id" => $this->id], "sharedTimestamp");
    }

    public static function shareComponent(int $componentId, int $userId, int $categoryId, string $description)
    {
        Core::database()->insert(self::TABLE_COMPONENT_SHARED, [
            "id" => $componentId,
            "description" => $description,
            "category" => $categoryId,
            "sharedBy" => $userId,
            "sharedTimestamp" => date("Y-m-d H:i:s", time())
        ]);
    }

    public static function stopShareComponent(int $componentId, int $userId)
    {
        Core::database()->delete(self::TABLE_COMPONENT_SHARED, [
            "id" => $componentId,
            "sharedBy" => $userId
        ]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates component name.
     *
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $componentId = null)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Component name can't be null neither empty.");

        if (iconv_strlen($name) > 25)
            throw new Exception("Component name is too long: maximum of 25 characters.");

        $whereNot = [];
        if ($componentId) $whereNot[] = ["id", $componentId];
        $componentNames = array_column(Core::database()->selectMultiple(self::TABLE_COMPONENT, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $componentNames))
            throw new Exception("Duplicate component name: '$name'");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a custom component coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $component
     * @param $field
     * @param string|null $fieldName
     * @return mixed
     */
    public static function parse(array $component = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "viewRoot", "course", "sharedBy"];
        return Utils::parse(["int" => $intValues], $component, $field, $fieldName);
    }

    /**
     * Trims custom component parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    protected static function trim(&...$values)
    {
        $params = ["name", "creationTimestamp", "updateTimestamp"];
        Utils::trim($params, ...$values);
    }
}
