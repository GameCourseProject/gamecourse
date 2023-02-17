<?php
namespace GameCourse\Views\Template;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\ViewHandler;
use ReflectionClass;

/**
 * This is the Template model, which implements the necessary methods
 * to interact with templates in the MySQL database.
 */
abstract class Template
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
        return intval(Core::database()->select($this::TABLE_TEMPLATE, ["id" => $this->id]));
    }

    /**
     * Gets template data from the database.
     *
     * @example getData() --> gets all template data
     * @example getData("field") --> gets template field
     * @example getData("field1, field2") --> gets template fields
     *
     * @param string $field
     * @return mixed
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select($this::TABLE_TEMPLATE, ["id" => $this->id], $field);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setViewRoot(int $viewRoot)
    {
        Core::database()->update($this::TABLE_TEMPLATE, ["viewRoot" => $viewRoot, ["id" => $this->id]]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a template by its ID.
     * Returns null if template doesn't exist.
     *
     * @param int $id
     * @return Template|null
     */
    public static function getTemplateById(int $id): ?Template
    {
        $templateClass = "\\" . get_called_class();
        $template = new $templateClass($id);
        if ($template->exists()) return $template;
        else return null;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Template Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Deletes a template from the database.
     * Option to keep views linked (created by reference) or delete
     * them as well.
     *
     * @param int $id
     * @param bool $keepViewsLinked
     * @return void
     */
    protected static function deleteTemplate(int $id, bool $keepViewsLinked = true) {
        $template = self::getTemplateById($id);
        if ($template) {
            // TODO: go through each view linked to this template and either
            //        replace by a copy (keep = true) or a default view

            // Delete view tree
            ViewHandler::deleteViewTree($template->getViewRoot());
        }
    }

    /**
     * Checks whether template exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty(Core::database()->select($this::TABLE_TEMPLATE, ["id" => $this->id]));
    }

    /**
     * Checks whether a view root is a template.
     *
     * @param int $viewRoot
     * @return bool
     */
    public static function isTemplate(int $viewRoot): bool
    {
        $typeClass = new ReflectionClass(TemplateType::class);
        $types = array_values($typeClass->getConstants());

        $isTemplate = false;
        foreach ($types as $type) {
            $templateClass = "\\GameCourse\\Views\\Template\\" . ucfirst($type) . "Template";
            if (!empty(Core::database()->select($templateClass::TABLE_TEMPLATE, ["viewRoot" => $viewRoot]))) {
                $isTemplate = true;
                break;
            }
        }
        return $isTemplate;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Rendering -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Renders a template.
     * Always renders its default aspect.
     *
     * @return array
     * @throws Exception
     */
    public function renderTemplate(): array
    {
        $defaultAspect = Aspect::getAspectBySpecs(0, null, null);
        $sortedAspects = [$defaultAspect->getData("id, viewerRole, userRole")];
        return ViewHandler::renderView($this->getViewRoot(), $sortedAspects, true);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a template coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $template
     * @param $field
     * @param string|null $fieldName
     * @return mixed
     */
    public abstract static function parse(array $template = null, $field = null, string $fieldName = null);
}
