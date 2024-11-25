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
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/
    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace provides utilities for interactions on a page. Most of the time, you will want
        to use expressions from this library in the <span class="text-primary">Events</span> field.<p><br>
        <p>For example, you can make a user move to their Profile page when clicking on a button, by having
        a Button component in a page, and on its Events section creating an on Click event with the expression:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{actions.goToPage(pages.getPageByName("Profile").id, %user)}</code></pre>
        </div>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("goToPage",
                [[ "name" => "pageId", "optional" => false, "type" => "int"],
                 ["name" => "userId", "optional" => true, "type" => "int"],
                 ["name" => "isSkill", "optional" => true, "type" => "bool"]],
                "Navigates to a given course page. Option for user param for page. If the page
                corresponds to a Skill, the third option must be set to true.",
                ReturnType::VOID,
                $this,
                "actions.goToPage(10)"
            ),
            new DFunction("showTooltip",
                [[ "name" => "text", "optional" => false, "type" => "string"],
                 ["name" => "position", "optional" => true, "type" => "'top' | 'bottom' | 'left' | 'right'"]],
                "Shows a tooltip with a given text and position.",
                ReturnType::VOID,
                $this,
                "actions.showTooltip('Helper text here', 'right')"
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
