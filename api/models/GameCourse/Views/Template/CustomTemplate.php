<?php
namespace GameCourse\Views\Template;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\User\User;
use GameCourse\Views\Category\Category;
use GameCourse\Views\CreationMode;
use GameCourse\Views\ViewHandler;
use Utils\Utils;

/**
 * This is the Custom Template model, which implements the necessary methods
 * to interact with custom templates (from users) in the MySQL database.
 */
class CustomTemplate extends Template
{
    const TABLE_TEMPLATE = 'template_custom';
    const TABLE_TEMPLATE_SHARED = 'template_custom_shared';


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
     * Sets custom template data on the database.
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
            Core::database()->update(self::TABLE_TEMPLATE, $fieldValues, ["id" => $this->id]);

        $this->refreshUpdateTimestamp();
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets custom templates of a given course.
     *
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public static function getTemplates(int $courseId): array
    {
        $templates = Core::database()->selectMultiple(self::TABLE_TEMPLATE, ["course" => $courseId], "*", "name");
        foreach ($templates as &$template) { $template = self::parse($template); }
        return $templates;
    }

    /**
     * Updates custom template's updateTimestamp to current time.
     *
     * @return void
     * @throws Exception
     */
    public function refreshUpdateTimestamp()
    {
        $this->setUpdateTimestamp(date("Y-m-d H:i:s", time()));
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Template Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a custom template to the database.
     * Returns the newly created template.
     *
     * @param string $creationMode
     * @param string $name
     * @param int $courseId
     * @param int|null $viewRoot
     * @param array|null $viewTree
     * @return CustomTemplate
     * @throws Exception
     */
    public static function addTemplate(int $courseId, string $creationMode, string $name, array $viewTree = null,
                                        int $viewRoot = null): CustomTemplate
    {
        self::trim($name);
        self::validateName($courseId, $name);

        if ($creationMode == CreationMode::BY_VALUE) {
            if (!$viewTree) $viewTree = ViewHandler::ROOT_VIEW;
            $viewRoot = ViewHandler::insertViewTree($viewTree, $courseId);

        } else if ($creationMode == CreationMode::BY_REFERENCE) {
            if (is_null($viewRoot))
                throw new Exception("Can't add custom template by reference: no view root given.");
        }

        // Insert in database
        $id = Core::database()->insert(self::TABLE_TEMPLATE, [
            "viewRoot" => $viewRoot,
            "name" => $name,
            "course" => $courseId
        ]);
        return new CustomTemplate($id);
    }

    /**
     * Copies an existing custom template.
     *
     * @param string $creationMode
     * @return CustomTemplate
     * @throws Exception
     */
    public function copyTemplate(string $creationMode): CustomTemplate
    {
        $templateInfo = $this->getData();

        // Copy template
        $name = $templateInfo["name"] . " (Copy)";
        return self::addTemplate($templateInfo["course"], $creationMode, $name,
            $creationMode === CreationMode::BY_VALUE ? ViewHandler::buildView($templateInfo["viewRoot"]) : null,
            $templateInfo["viewRoot"]);
    }

    /**
     * Edits an existing custom template in the database.
     * Returns the edited custom template.
     *
     * @param string $name
     * @return CustomTemplate
     * @throws Exception
     */
    public function editTemplate(string $name): CustomTemplate
    {
        $this->setName($name);
        return $this;
    }

    /**
     * Deletes a custom template from the database.
     * Option to keep views linked (created by reference) or delete
     * them as well.
     *
     * @param int $id
     * @param bool $keepViewsLinked
     * @return void
     */
    public static function deleteTemplate(int $id, bool $keepViewsLinked = true)
    {
        parent::deleteTemplate($id, $keepViewsLinked);
        Core::database()->delete(self::TABLE_TEMPLATE, ["id" => $id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Sharing --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function isShared(): bool
    {
        return !empty(Core::database()->select(self::TABLE_TEMPLATE_SHARED, ["id" => $this->id]));
    }

    public function getSharedDescription(): string
    {
        return Core::database()->select(self::TABLE_TEMPLATE_SHARED, ["id" => $this->id], "description");
    }

    public function getSharedCategory(): Category
    {
        return Category::getCategoryById(Core::database()->select(self::TABLE_TEMPLATE_SHARED, ["id" => $this->id], "category"));
    }

    public function getSharedBy(): User
    {
        return User::getUserById(Core::database()->select(self::TABLE_TEMPLATE_SHARED, ["id" => $this->id], "sharedBy"));
    }

    public function getSharedTimestamp(): string
    {
        return Core::database()->select(self::TABLE_TEMPLATE_SHARED, ["id" => $this->id], "sharedTimestamp");
    }

    // TODO: share template


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates template name.
     *
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $templateId = null)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Template name can't be null neither empty.");

        if (iconv_strlen($name) > 25)
            throw new Exception("Template name is too long: maximum of 25 characters.");

        $whereNot = [];
        if ($templateId) $whereNot[] = ["id", $templateId];
        $templateNames = array_column(Core::database()->selectMultiple(self::TABLE_TEMPLATE, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $templateNames))
            throw new Exception("Duplicate template name: '$name'");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a custom template coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $template
     * @param $field
     * @param string|null $fieldName
     * @return mixed
     */
    public static function parse(array $template = null, $field = null, string $fieldName = null)
    {
        $intValues = ["viewRoot", "course"];
        return Utils::parse(["int" => $intValues], $template, $field, $fieldName);
    }

    /**
     * Trims custom template parameters' whitespace at start/end.
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
