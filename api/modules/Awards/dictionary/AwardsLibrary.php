<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Awards\Awards;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use InvalidArgumentException;

class AwardsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }

    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "awards";    // NOTE: must match the name of the class
    const NAME = "Awards";
    const DESCRIPTION = "Provides access to information regarding awards.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace allows you to access the awards earned by students. An award is the core
        concept on which GameCourse operates: a student, by completing a task, is rewarded with
        an award. Awards can be of different <span class="font-semibold">types</span>, depending on the module from which the activity refers to: 
        for example, a student obtaining a badge will have an award of type 'badge', while completing a skill
        offers an award of type 'skill'.</p>
        <p>An award is characterized by the following:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{
            "id": 4,
            "user": 3,
            "course": 1,
            "description": "Token(s) exchange",
            "type": "bonus",
            "moduleInstance": null,
            "reward": 1000,
            "date": "2024-04-08 09:30:51"
        }</code></pre>
        </div><br>
        <p>You can obtain all the awards of a user using the function:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{awards.getUserAwards(%viewer)}</code></pre>
        </div>
        <p>In this case, since we are using the context variable <span class="text-info">%viewer</span>,
         it returns all awards of the user viewing the page. However, you can use any other user - for this matter,
         it might be interesting to check out the <span class="text-secondary">users</span> namespace.</p><br>
        <p>If instead of all awards, you only wish to obtain awards of a certain type, you can use:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{awards.getUserAwardsByType(%user, 'presentation')}</code></pre>
        </div>
        <p>Where the second argument is the award type.</p>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockAward($userId, $type = null) : array
    {
        return [
            "id" => Core::dictionary()->faker()->numberBetween(0, 100),
            "course" => 0,
            "user" => $userId,
            "description" => Core::dictionary()->faker()->text(20),
            "type" => $type ?: Core::dictionary()->faker()->randomElement(['assignment','badge','bonus','exam','labs','post','presentation','quiz','skill','streak','tokens']),
            "moduleInstance" => null,
            "reward" => Core::dictionary()->faker()->numberBetween(50, 500),
            "date" => Core::dictionary()->faker()->dateTimeThisYear()->format("Y-m-d H:m:s")
        ];
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("id",
                [["name" => "award", "optional" => false, "type" => "any"]],
                "Gets a given award's ID in the system.",
                ReturnType::NUMBER,
                $this,
                "awards.id(%award)\nor (shorthand notation):\n%award.id"
            ),
            new DFunction("description",
                [["name" => "award", "optional" => false, "type" => "any"]],
                "Gets a given award's description.",
                ReturnType::TEXT,
                $this,
                "awards.description(%award)\nor (shorthand notation):\n%award.description"
            ),
            new DFunction("type",
                [["name" => "award", "optional" => false, "type" => "any"]],
                "Gets a given award's type.",
                ReturnType::TEXT,
                $this,
                "awards.type(%award)\nor (shorthand notation):\n%award.type"
            ),
            new DFunction("instance",
                [["name" => "award", "optional" => false, "type" => "any"]],
                "Gets a given award's module instance.",
                ReturnType::NUMBER,
                $this,
                "awards.instance(%award)\nor (shorthand notation):\n%award.instance"
            ),
            new DFunction("reward",
                [["name" => "award", "optional" => false, "type" => "any"]],
                "Gets a given award's reward.",
                ReturnType::NUMBER,
                $this,
                "awards.reward(%award)\nor (shorthand notation):\n%award.reward"
            ),
            new DFunction("date",
                [["name" => "award", "optional" => false, "type" => "any"]],
                "Gets a given award's date.",
                ReturnType::TIME,
                $this,
                "awards.date(%award)\nor (shorthand notation):\n%award.date"
            ),
            new DFunction("icon",
                [["name" => "award", "optional" => false, "type" => "any"]],
                "Gets a given award's icon.",
                ReturnType::TEXT,
                $this,
                "awards.icon(%award)\nor (shorthand notation):\n%award.icon"
            ),
            new DFunction("image",
                [["name" => "award", "optional" => false, "type" => "any"],
                    ["name" => "\"outline\" | \"solid\"", "optional" => true, "type" => "string"],
                    ["name" => "\"jpg\" | \"svg\"", "optional" => true, "type" => "string"]],
                "Gets a given award's image URL.",
                ReturnType::TEXT,
                $this,
                "awards.image(%award)\nor (shorthand notation):\n%award.image"
            ),
            new DFunction("getIconOfType",
                [["name" => "type", "optional" => false, "type" => "string"]],
                "Gets icon for a given type of award.",
                ReturnType::TEXT,
                $this,
                "awards.getIconOfType('streak')"
            ),
            new DFunction("getImageOfType",
                [["name" => "type", "optional" => false, "type" => "string"],
                    ["name" => "\"outline\" | \"solid\"", "optional" => true, "type" => "string"],
                    ["name" => "\"jpg\" | \"svg\"", "optional" => true, "type" => "string"]],
                "Gets image for a given type of award.",
                ReturnType::TEXT,
                $this,
                "awards.getImageOfType('quiz')"
            ),
            new DFunction("getUserAwards",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets awards for a given user.",
                ReturnType::AWARDS_COLLECTION,
                $this,
                "awards.getUserAwards(%user)"
            ),
            new DFunction("getUserAwardsByType",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "type", "optional" => false, "type" => "string"],
                    ["name" => "instance", "optional" => true, "type" => "int"]],
                "Gets awards for a given user of a specific type of award.",
                ReturnType::AWARDS_COLLECTION,
                $this,
                "awards.getUserAwardsByType(%user, 'presentation')"
            ),
            new DFunction("getUserTotalRewardByType",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "type", "optional" => false, "type" => "string"],
                    ["name" => "instance", "optional" => true, "type" => "int"]],
                "Gets total reward for a given user of a specific type of award.",
                ReturnType::NUMBER,
                $this,
                "awards.getUserTotalRewardByType(%user, 'tokens')"
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above


    /*** --------- Getters ---------- ***/

    /**
     * Gets a given award's ID in the system.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function id($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        if (!is_array($award)) throw new InvalidArgumentException("Invalid type for first argument: expected an award.");
        $awardId = $award["id"];
        return new ValueNode($awardId, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given award's description.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function description($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        if (!is_array($award)) throw new InvalidArgumentException("Invalid type for first argument: expected an award.");
        $description = $award["description"];
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given award's type.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function type($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        if (!is_array($award)) throw new InvalidArgumentException("Invalid type for first argument: expected an award.");
        $type = $award["type"];
        return new ValueNode($type, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given award's module instance.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function instance($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        if (!is_array($award)) throw new InvalidArgumentException("Invalid type for first argument: expected an award.");
        $instance = $award["moduleInstance"];
        return new ValueNode($instance, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given award's reward.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function reward($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        if (!is_array($award)) throw new InvalidArgumentException("Invalid type for first argument: expected an award.");
        $reward = $award["reward"];
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given award's date.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function date($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        if (!is_array($award)) throw new InvalidArgumentException("Invalid type for first argument: expected an award.");
        $date = $award["date"];
        return new ValueNode($date, Core::dictionary()->getLibraryById(TimeLibrary::ID));
    }

    /**
     * Gets a given award's icon.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function icon($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        if (!is_array($award)) throw new InvalidArgumentException("Invalid type for first argument: expected an award.");

        $awardsModule = new Awards(Core::dictionary()->getCourse());
        $icon = $awardsModule->getIconOfType($award["type"]);
        return new ValueNode($icon, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given award's image URL.
     *
     * @param $award
     * @param string $style
     * @param string $extension
     * @return ValueNode
     * @throws Exception
     */
    public function image($award, string $style = "outline" | "solid", string $extension = "jpg" | "svg"): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        if (!is_array($award)) throw new InvalidArgumentException("Invalid type for first argument: expected an award.");

        $awardsModule = new Awards(Core::dictionary()->getCourse());
        $image = $awardsModule->getImageOfType($award["type"], $style, $extension);
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }


    /*** ---------- Config ---------- ***/

    /**
     * Gets icon for a given type of award.
     *
     * @param string $type
     * @return ValueNode
     * @throws Exception
     */
    public function getIconOfType(string $type): ValueNode
    {
        $awardsModule = new Awards(Core::dictionary()->getCourse());
        $icon = $awardsModule->getIconOfType($type);
        return new ValueNode($icon, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets image for a given type of award.
     *
     * @param string $type
     * @param string $style
     * @param string $extension
     * @return ValueNode
     * @throws Exception
     */
    public function getImageOfType(string $type, string $style = "outline" | "solid", string $extension = "jpg" | "svg"): ValueNode
    {
        $awardsModule = new Awards(Core::dictionary()->getCourse());
        $image = $awardsModule->getImageOfType($type, $style, $extension);
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }


    /*** ---------- Awards ---------- ***/

    /**
     * Gets awards for a given user.
     *
     * @param int $userId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserAwards(int $userId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $awards = array_map(function () use ($userId) {
                return $this->mockAward($userId);
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 10)));

        } else {
            $awardsModule = new Awards($course);
            $awards = $awardsModule->getUserAwards($userId);
        }
        return new ValueNode($awards, $this);
    }

    /**
     * Gets awards for a given user of a specific type of award.
     *
     * @param int $userId
     * @param string $type
     * @param int|null $instance
     * @return ValueNode
     * @throws Exception
     */
    public function getUserAwardsByType(int $userId, string $type, ?int $instance = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $awards = array_map(function () use ($userId, $type) {
                return $this->mockAward($userId, $type);
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else {
            $awardsModule = new Awards($course);
            $awards = $awardsModule->getUserAwardsByType($userId, $type, $instance);
        }
        return new ValueNode($awards, $this);
    }


    /*** ---------- Rewards ---------- ***/

    /**
     * Gets total reward for a given user of a specific type of award.
     *
     * @param int $userId
     * @param string $type
     * @param int|null $instance
     * @return ValueNode
     * @throws Exception
     */
    public function getUserTotalRewardByType(int $userId, string $type, ?int $instance = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $reward = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $awardsModule = new Awards($course);
            $reward = $awardsModule->getUserTotalRewardByType($userId, $type, $instance);
        }
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
