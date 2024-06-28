<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use InvalidArgumentException;

class BadgeProgressionLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "badgeProgression";    // NOTE: must match the name of the class
    const NAME = "Badge Progression";
    const DESCRIPTION = "Provides access to information regarding badge progression.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace allows you to get the information regarding a user's progress towards a badge. Each item of this namespace follows this structure:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{
          "description": "Re: Looking for group for L04 Shift (Tuesday 15h30, Thursday 16h30)",
          "link": "https://pcm.rnl.tecnico.ulisboa.pt/moodle/mod/forum/discuss.php?d=3379&parent=22381",
        }</code></pre>
        </div><br>
        <p>The function</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{badges.getUserBadgeProgressionInfo(%user, %badge.id)}</code></pre>
        </div>
        <p>of the <span class="text-secondary">badges</span> library, for example, returns a collection of items of this type. </p><br>
        <p>Thanks to this namespace, you can, given an item,
        access its attribute. Assuming for example that you have a custom auxiliary variable, <span class="text-secondary">progress</span> with value 
        <span class="text-secondary">{badges.getUserBadgeProgressionInfo(%user, %badge.id).item(0)}</span>, you can do:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{%progress.link}</code></pre>
        </div>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("description",
                [["name" => "progression", "optional" => false, "type" => "any"]],
                "Gets a given progression's description.",
                ReturnType::TEXT,
                $this,
                "badgeProgression.description(%badgeProgression)\nor (shorthand notation):\n%badgeProgression.description"
            ),
            new DFunction("link",
                [["name" => "progression", "optional" => false, "type" => "any"]],
                "Gets a given progression's link.",
                ReturnType::TEXT,
                $this,
                "badgeProgression.link(%badgeProgression)\nor (shorthand notation):\n%badgeProgression.link"
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given progression's description.
     *
     * @param $progression
     * @return ValueNode
     * @throws Exception
     */
    public function description($progression): ValueNode
    {
        // NOTE: on mock data, badge progression will be mocked
        if (!is_array($progression)) throw new InvalidArgumentException("Invalid type for first argument: expected a badge progression.");
        $description = $progression["description"];
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given progression's link.
     *
     * @param $progression
     * @return ValueNode
     * @throws Exception
     */
    public function link($progression): ValueNode
    {
        // NOTE: on mock data, badge progression will be mocked
        if (!is_array($progression)) throw new InvalidArgumentException("Invalid type for first argument: expected a badge progression.");
        $link = $progression["link"] ?? null;
        return new ValueNode($link, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }
}
