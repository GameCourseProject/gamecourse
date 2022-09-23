<?php
namespace GameCourse\Views\Page\Template;

use Exception;
use GameCourse\Views\ViewHandler;
use ReflectionClass;

/**
 * This is the Template model, which implements the necessary methods
 * to interact with page templates in the MySQL database.
 */
abstract class Template
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
     * Gets a template of a given type by its view root.
     * Returns null if template doesn't exist.
     *
     * @param string $type
     * @param int $viewRoot
     * @return Template|null
     */
    public static function getTemplateByViewRoot(string $type, int $viewRoot): ?Template
    {
        $templateClass = "\\GameCourse\\Views\\Page\\Template\\" . ucfirst($type) . "Template";
        $template = new $templateClass($viewRoot);
        if ($template->exists()) return $template;
        else return null;
    }

    /**
     * Gets templates of a specific type.
     *
     * @return array
     */
    public static abstract function getTemplates(): array;


    /*** ---------------------------------------------------- ***/
    /*** --------------- Template Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Deletes a template of a specific type from the database.
     *
     * @param int $viewRoot
     * @return void
     */
    public static abstract function deleteTemplate(int $viewRoot);

    /**
     * Checks whether template exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("viewRoot"));
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
            if (self::getTemplateByViewRoot($type, $viewRoot)) $isTemplate = true;
        }
        return $isTemplate;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Rendering -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Renders a template for a specific set of aspects.
     * If no aspects given, it will render the template for all
     * its aspects.
     * Option to populate view with data:
     *  - false --> do not populate
     *  - true --> populate with mocked data
     *  - array with params --> populate with actual data (e.g. ["course" => 1, "viewer" => 10, "user" => 20])
     *
     * @param array|null $sortedAspects
     * @param bool|array $populate
     * @return array
     * @throws Exception
     */
    public function renderTemplate(array $sortedAspects = null, $populate = false): array
    {
        return ViewHandler::renderView($this->viewRoot, $sortedAspects, $populate);
        // FIXME: render complete?
    }
}