<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Views\ExpressionLanguage\ValueNode;

class ActionsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "actions";    // NOTE: must match the name of the class
    const NAME = "Actions";
    const DESCRIPTION = "Library to be used only on EVENTS. These functions define the response action to event triggers.";

    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("goToPage",
                [[ "name" => "pageId", "optional" => false, "type" => "int"],
                 ["name" => "userId", "optional" => true, "type" => "int"]],
                "Navigates to a given course page. Option for user param for page.",
                ReturnType::VOID,
                $this
            ),
            new DFunction("showTooltip",
                [[ "name" => "text", "optional" => false, "type" => "string"],
                 ["name" => "position", "optional" => true, "type" => "string"]],
                "Shows a tooltip with a given text and position.",
                ReturnType::VOID,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Navigates to a given course page. Option for user param for page.
     *
     * @param int $pageId
     * @param int|null $userId
     * @return ValueNode
     */
    public function goToPage(int $pageId, ?int $userId = null, ?bool $isSkill = false): ValueNode
    {
        $args = [$pageId];
        if (!is_null($userId)) $args[] = $userId;
        if ($isSkill) $args = [$pageId, null, $isSkill];
        return new ValueNode("goToPage(" . implode(", ", $args) . ")");
    }

    /**
     * Shows a tooltip with a given text and position.
     *
     * @param string $text
     * @param string $position
     * @return ValueNode
     */
    public function showTooltip(string $text, string $position = "top"): ValueNode
    {
        $args = [$text, $position];
        return new ValueNode("showTooltip(" . implode(", ", $args) . ")");
    }
}
