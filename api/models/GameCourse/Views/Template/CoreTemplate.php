<?php
namespace GameCourse\Views\Template;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Category\Category;
use GameCourse\Views\ViewHandler;
use Utils\Utils;

/**
 * This is the Core Template model, which implements the necessary methods
 * to interact with core templates (from system + modules) in the
 * MySQL database.
 */
class CoreTemplate extends Template
{
    const TABLE_TEMPLATE = 'template_core';


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getCategory(): Category
    {
        return Category::getCategoryById($this->getData("category"));
    }

    public function getPosition(): int
    {
        return $this->getData("position");
    }

    public function getModule(): ?Module
    {
        $moduleId = $this->getData("module");
        if ($moduleId) return Module::getModuleById($moduleId, null);
        return null;
    }

    public function getImage(): ?string
    {
        return $this->hasImage() ? API_URL . "/" . $this->getDataFolder(false) . "/screenshot.png" : null;
    }

    public function hasImage(): bool
    {
        return file_exists($this->getDataFolder() . "/screenshot.png");
    }

    /**
     * Gets template data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param bool $fullPath
     * @return string
     */
    public function getDataFolder(bool $fullPath = true): string
    {
        if ($fullPath) return CORE_TEMPLATES_DATA_FOLDER . "/" . $this->getId();
        else return Utils::getDirectoryName(CORE_TEMPLATES_DATA_FOLDER) . "/" . $this->getId();
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    public function setCategory(Category $category)
    {
        $this->setData(["category" => $category->getId()]);
    }

    public function setPosition(int $position)
    {
        $this->setData(["position" => $position]);
    }

    public function setModule(?Module $module)
    {
        $this->setData(["module" => $module ? $module->getId() : null]);
    }

    /**
     * Sets core template data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "category" => 1])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        // Trim values
        self::trim($fieldValues);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_TEMPLATE, $fieldValues, ["id" => $this->id]);
    }

    /**
     * @throws Exception
     */
    public function setImage(string $base64)
    {
        Utils::uploadFile($this->getDataFolder(), $base64, "screenshot.png");
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets core templates in the system.
     * Options for a specific category or module.
     *
     * @param int|null $categoryId
     * @param string|null $moduleId
     * @return array
     */
    public static function getTemplates(int $categoryId = null, string $moduleId = null): array
    {
        $where = [];
        if ($categoryId) $where[] = ["category" => $categoryId];
        if ($moduleId) $where[] = ["module" => $moduleId];

        $templates = Core::database()->selectMultiple(self::TABLE_TEMPLATE, $where, "*", "category, position");

        foreach ($templates as &$template) { 
            $template = self::parse($template); 
            // Get image
            $templateForImage = new CoreTemplate($template["id"]);
            $template["image"] = $templateForImage->getImage();
        }
        return $templates;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------- Template Manipulation -------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a core template to the database.
     * Returns the newly created template.
     *
     * @param array $viewTree
     * @param string $name
     * @param int $categoryId
     * @param int $position
     * @param string|null $moduleId
     * @return CoreTemplate
     * @throws Exception
     */
    public static function addTemplate(array $viewTree, string $name, int $categoryId, int $position, string $moduleId = null): CoreTemplate
    {
        // Verify view tree only has system and/or module aspects
        try {
            // NOTE: will throw an exception if aspect not found
            Aspect::getAspectsInViewTree(null, $viewTree, 0);

        } catch (Exception $e) {
            $error = $e->getMessage();
            preg_match("/Role with name '(.+)' doesn't exist/", $error, $matches);
            if (!empty($matches)) {
                $roleName = $matches[1];
                throw new Exception("Cannot use role with name '" . $roleName . "' as an aspect in a template. 
                Only system and/or module aspects are allowed.");
            }
        }

        // Add view tree of template
        $viewRoot = ViewHandler::insertViewTree($viewTree, 0);

        // Create new template
        self::trim($name);
        $id = Core::database()->insert(self::TABLE_TEMPLATE, [
            "viewRoot" => $viewRoot,
            "name" => $name,
            "category" => $categoryId,
            "module" => $moduleId
        ]);
        Utils::updateItemPosition(null, $position, self::TABLE_TEMPLATE, "position", $id, self::getTemplates($categoryId));

        return new CoreTemplate($viewRoot);
    }

    /**
     * Deletes a core template from the database.
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
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a core temaplte coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $template
     * @param $field
     * @param string|null $fieldName
     * @return mixed
     */
    public static function parse(array $template = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "viewRoot", "category", "position"];
        return Utils::parse(["int" => $intValues], $template, $field, $fieldName);
    }

    /**
     * Trims core template parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name"];
        Utils::trim($params, ...$values);
    }
}
