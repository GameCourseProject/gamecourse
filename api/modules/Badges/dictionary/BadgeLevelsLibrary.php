<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Badges\Badge;
use GameCourse\Module\Badges\Badges;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use InvalidArgumentException;

class BadgeLevelsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }

    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "badgeLevels";    // NOTE: must match the name of the class
    const NAME = "Badge Levels";
    const DESCRIPTION = "Provides access to information regarding badge levels.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace allows you to get the information of a level of a badge. For example, the first level of the Amphitheatre Lover badge is characterized by:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{
          "id": "1",
          "number": 1,
          "goal": 7,
          "description": "be there for 50% of lectures",
          "reward": 75,
          "tokens": 0,
          "image": "http://localhost/gamecourse/api/course_data/1-Multimedia_Content_Production/badges/Amphitheatre_Lover/badge.png",
        }</code></pre>
        </div><br>
        <p>To obtain a specific level of a badge, you can use the function</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{badgeLevels.getLevelByNumber(2, %badge.id)}</code></pre>
        </div>
        <p>In this example, the result would be the second level, in the same format as the example above, of the badge saved in the custom varible <span class="text-secondary">%badge</span>.</p>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockBadgeLevel(int $number = null) : array
    {
        return [
            "id" => Core::dictionary()->faker()->numberBetween(0, 100),
            "number" => $number ? $number : Core::dictionary()->faker()->numberBetween(1, 3),
            "goal" => Core::dictionary()->faker()->numberBetween(1, 50),
            "description" => Core::dictionary()->faker()->text(50),
            "reward" => Core::dictionary()->faker()->numberBetween(0, 300),
            "tokens" => Core::dictionary()->faker()->numberBetween(0, 150),
            "image" => null
        ];
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("number",
                [["name" => "level", "optional" => false, "type" => "any"]],
                "Gets a given level's number.",
                ReturnType::NUMBER,
                $this,
            "%badgeLevel.number"
            ),
            new DFunction("goal",
                [["name" => "level", "optional" => false, "type" => "any"]],
                "Gets a given level's goal.",
                ReturnType::NUMBER,
                $this,
                "%badgeLevel.goal"
            ),
            new DFunction("description",
                [["name" => "level", "optional" => false, "type" => "any"]],
                "Gets a given level's description.",
                ReturnType::TEXT,
                $this,
                "%badgeLevel.description"
            ),
            new DFunction("reward",
                [["name" => "level", "optional" => false, "type" => "any"]],
                "Gets a given level's reward.",
                ReturnType::NUMBER,
                $this,
                "%badgeLevel.reward"
            ),
            new DFunction("tokens",
                [["name" => "level", "optional" => false, "type" => "any"]],
                "Gets a given level's tokens.",
                ReturnType::NUMBER,
                $this,
                "%badgeLevel.tokens"
            ),
            new DFunction("image",
                [["name" => "level", "optional" => false, "type" => "any"]],
                "Gets a given level's image URL.",
                ReturnType::TEXT,
                $this,
                "%badgeLevel.image"
            ),
            new DFunction("getLevelByNumber",
                [["name" => "number", "optional" => false, "type" => "int"],
                    ["name" => "badgeId", "optional" => false, "type" => "int"]],
                "Gets a level by its number.",
                ReturnType::OBJECT,
                $this,
            "badgeLevels.getLevelByNumber(2, %badge.id)"
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given level's number.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function number($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        if (!is_array($level)) throw new InvalidArgumentException("Invalid type for first argument: expected a badge level.");
        $number = $level["number"] ?? 0;
        return new ValueNode($number, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's goal.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function goal($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        if (!is_array($level)) throw new InvalidArgumentException("Invalid type for first argument: expected a badge level.");
        $goal = $level["goal"];
        return new ValueNode($goal, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's description.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function description($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        if (!is_array($level)) throw new InvalidArgumentException("Invalid type for first argument: expected a badge level.");
        $description = $level["description"];
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given level's reward.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function reward($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        if (!is_array($level)) throw new InvalidArgumentException("Invalid type for first argument: expected a badge level.");
        $reward = $level["reward"];
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's tokens.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function tokens($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        if (!is_array($level)) throw new InvalidArgumentException("Invalid type for first argument: expected a badge level.");
        $tokens = $level["tokens"];
        return new ValueNode($tokens, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's image URL.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function image($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        if (Core::dictionary()->mockData()) {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $image = $badgesModule->getBlankExtraImage();

        } else {
            $image = $level["image"];
        }
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a level by its number.
     *
     * @param int $number
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getLevelByNumber(int $number, int $badgeId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $level = $this->mockBadgeLevel($number);

        } else {
            $badge = Badge::getBadgeById($badgeId);
            $levels = $badge->getLevels();
            $level = $levels[$number - 1];
        }
        return new ValueNode($level, $this);
    }
}