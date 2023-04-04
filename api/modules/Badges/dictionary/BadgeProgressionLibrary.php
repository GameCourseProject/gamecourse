<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\ValueNode;

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
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("description",
                "Gets a given progression's description.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("link",
                "Gets a given progression's link.",
                ReturnType::TEXT,
                $this
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
        $link = $progression["link"] ?? null;
        return new ValueNode($link, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }
}
