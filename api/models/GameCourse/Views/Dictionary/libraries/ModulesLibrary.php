<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class ModulesLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "modules";    // NOTE: must match the name of the class
    const NAME = "Modules";
    const DESCRIPTION = "Provides access to information regarding modules.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("isEnabled",
                [["name" => "moduleId", "optional" => false, "type" => "string"]],
                "Checks whether a given module is enabled.",
                ReturnType::BOOLEAN,
                $this
            ),
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Checks whether a given module is enabled.
     *
     * @param string $moduleId
     * @return ValueNode
     * @throws Exception
     */
    public function isEnabled(string $moduleId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            return new ValueNode(true, Core::dictionary()->getLibraryById(BoolLibrary::ID));
        }
        else {
            $course = Core::dictionary()->getCourse();
            $isEnabled = $course->isModuleEnabled($moduleId);
            return new ValueNode($isEnabled, Core::dictionary()->getLibraryById(BoolLibrary::ID));
        }
    }
}
