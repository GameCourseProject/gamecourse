<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class TiersLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "tiers";    // NOTE: must match the name of the class
    const NAME = "Tiers";
    const DESCRIPTION = "Provides access to information regarding tiers.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("name",
                [["name" => "tier", "optional" => false, "type" => "tier"]],
                "Gets tier's name.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("reward",
                [["name" => "tier", "optional" => false, "type" => "tier"]],
                "Gets tier's reward.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("skills",
                [["name" => "tier", "optional" => false, "type" => "tier"]],
                "Gets tier's skills.",
                ReturnType::COLLECTION,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets tier's name.
     *
     * @param $tier
     * @return ValueNode
     * @throws Exception
     */
    public function name($tier): ValueNode
    {
        // NOTE: on mock data, tier will be mocked
        if (is_array($tier)) $name = $tier["name"];
        else $name = $tier->getName();
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets tier's name.
     *
     * @param $tier
     * @return ValueNode
     * @throws Exception
     */
    public function reward($tier): ValueNode
    {
        // NOTE: on mock data, tier will be mocked
        if (is_array($tier)) $name = $tier["reward"];
        else $name = $tier->getReward();
        return new ValueNode($name, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets tier's skills.
     *
     * @param $tier
     * @return ValueNode
     * @throws Exception
     */
    public function skills($tier): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $skills = $tier["skills"];

        } else {
            $skills = Skill::getSkillsOfTier($tier["id"]);
        };
        return new ValueNode($skills, Core::dictionary()->getLibraryById(SkillsLibrary::ID));
    }
}
