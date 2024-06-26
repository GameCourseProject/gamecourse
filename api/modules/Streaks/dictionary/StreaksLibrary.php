<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use Faker\Factory;
use GameCourse\Core\Core;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Streaks\Streak;
use GameCourse\Module\Streaks\Streaks;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use InvalidArgumentException;

class StreaksLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }

    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "streaks";    // NOTE: must match the name of the class
    const NAME = "Streaks";
    const DESCRIPTION = "Provides access to information regarding streaks.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace allows you to obtain Streaks and their information. 
        Each streak has the following attributes:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{
          "id": 1,
          "course": 1,
          "name": "Constant Gardener",
          "description": "Do five skills with no more than five days between them",
          "color": "#36987B",
          "goal": 5,
          "periodicityGoal": 1,
          "periodicityNumber": 5,
          "periodicityTime": "day",
          "periodicityType": "relative",
          "reward": 150,
          "tokens": 100,
          "isExtra": true,
          "isRepeatable": true,
          "isActive": true,
          "rule": 138,
          "image": "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzM2OTg3QiI+DQogICAgPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBkPSJNMTIuOTYzIDIuMjg2YS43NS43NSAwIDAwLTEuMDcxLS4xMzYgOS43NDIgOS43NDIgMCAwMC0zLjUzOSA2LjE3N0E3LjU0NyA3LjU0NyAwIDAxNi42NDggNi42MWEuNzUuNzUgMCAwMC0xLjE1Mi0uMDgyQTkgOSAwIDEwMTUuNjggNC41MzRhNy40NiA3LjQ2IDAgMDEtMi43MTctMi4yNDh6TTE1Ljc1IDE0LjI1YTMuNzUgMy43NSAwIDExLTcuMzEzLTEuMTcyYy42MjguNDY1IDEuMzUuODEgMi4xMzMgMWE1Ljk5IDUuOTkgMCAwMTEuOTI1LTMuNTQ1IDMuNzUgMy43NSAwIDAxMy4yNTUgMy43MTd6IiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIC8+DQo8L3N2Zz4NCg==",
          "isPeriodic": true
        }</code></pre>
        </div><br>
        <p>With this library, you can:</p>
        <ul>
            <li>Obtain all Streaks
                <div class="bg-base-100 rounded-box p-4 my-2">
                  <pre><code>{streaks.getStreaks()}</code></pre>
                </div>
            </li>
            <li>Obtain the Streaks obtained by a user
                <div class="bg-base-100 rounded-box p-4 my-2">
                  <pre><code>{streaks.getUserStreaks(%user)}</code></pre>
                </div>
            </li>
            <li>Or obtain the users who have earned a certain Streak
                <div class="bg-base-100 rounded-box p-4 my-2">
                  <pre><code>{streaks.getUsersWithStreak(%streak.id)}</code></pre>
                </div>
            </li>
        </ul><br>
        <p>As with other namespaces, after obtaining a streak, you can access its attributes like this:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{%streak.id}</code></pre>
        </div>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockStreak() : array
    {
        return [
            "id" => Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => Core::dictionary()->faker()->text(20),
            "description" => Core::dictionary()->faker()->text(50),
            "color" =>  Core::dictionary()->faker()->hexColor(),
            "goal" => Core::dictionary()->faker()->numberBetween(3, 5),
            "reward" => Core::dictionary()->faker()->randomElement([50, 100, 150, 200]),
            "tokens" => Core::dictionary()->faker()->randomElement([10, 40, 100]),
            "isExtra" => Core::dictionary()->faker()->randomElement([0, 1]),
            "isRepeatable" => Core::dictionary()->faker()->randomElement([0, 1]),
            "isActive" => Core::dictionary()->faker()->randomElement([0, 1])
        ];
    }

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
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Gets a given streak's ID in the system.",
                ReturnType::NUMBER,
                $this,
                "streaks.id(%streak)\nor (shorthand notation):\n%streak.id"
            ),
            new DFunction("name",
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Gets a given streak's name.",
                ReturnType::TEXT,
                $this,
                "streaks.name(%streak)\nor (shorthand notation):\n%streak.name"
            ),
            new DFunction("goal",
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Gets a given streak's goal.",
                ReturnType::NUMBER,
                $this,
                "streaks.goal(%streak)\nor (shorthand notation):\n%streak.goal"
            ),
            new DFunction("description",
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Gets a given streak's description.",
                ReturnType::TEXT,
                $this,
                "streaks.description(%streak)\nor (shorthand notation):\n%streak.description"
            ),
            new DFunction("color",
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Gets a given streak's color.",
                ReturnType::TEXT,
                $this,
                "streaks.color(%streak)\nor (shorthand notation):\n%streak.color"
            ),
            new DFunction("reward",
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Gets a given streak's reward.",
                ReturnType::NUMBER,
                $this,
                "streaks.reward(%streak)\nor (shorthand notation):\n%streak.reward"
            ),
            new DFunction("tokens",
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Gets a given streak's tokens.",
                ReturnType::NUMBER,
                $this,
                "streaks.tokens(%streak)\nor (shorthand notation):\n%streak.tokens"
            ),
            new DFunction("isRepeatable",
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Returns whether a streak is repeatable or not.",
                ReturnType::BOOLEAN,
                $this,
                "streaks.isRepeatable(%streak)\nor (shorthand notation):\n%streak.isRepeatable"
            ),
            new DFunction("isExtra",
                [["name" => "streak", "optional" => false, "type" => "Streak"]],
                "Returns whether a streak is extra or not.",
                ReturnType::BOOLEAN,
                $this,
                "streaks.isExtra(%streak)\nor (shorthand notation):\n%streak.isExtra"
            ),
            new DFunction("getMaxXP",
                [],
                "Gets maximum XP each student can earn with streaks.",
                ReturnType::NUMBER,
                $this,
                "streaks.getMaxXP()"
            ),
            new DFunction("getMaxExtraCredit",
                [],
                "Gets maximum extra credit each student can earn with streaks.",
                ReturnType::NUMBER,
                $this,
                "streaks.getMaxExtraCredit()"
            ),
            new DFunction("getStreaks",
                [["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets streaks of course.",
                ReturnType::STREAKS_COLLECTION,
                $this,
                "streaks.getStreaks(true)"
            ),
            new DFunction("getUsersWithStreak",
                [["name" => "streakId", "optional" => false, "type" => "int"]],
                "Gets users who have earned a given streak at least once.",
                ReturnType::USERS_COLLECTION,
                $this,
                "streaks.getUsersWithStreak(%streak.id)"
            ),
            new DFunction("getUserStreaks",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "isExtra", "optional" => true, "type" => "bool"],
                    ["name" => "isRepeatable", "optional" => true, "type" => "bool"]],
                "Gets streaks earned by a given user.",
                ReturnType::STREAKS_COLLECTION,
                $this,
                "streaks.getUserStreaks(%user)"
            ),
            new DFunction("getUserStreakProgression",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "streakId", "optional" => false, "type" => "int"]],
                "Gets user progression on a given streak.",
                ReturnType::NUMBER,
                $this,
                "streaks.getUserStreakProgression(%user, %streak.id)"
            ),
            new DFunction("getUserStreakCompletions",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "streakId", "optional" => false, "type" => "int"]],
                "Gets how many times a given user has completed a specific streak.",
                ReturnType::NUMBER,
                $this,
                "streaks.getUserStreakCompletions(%user, %streak.id)"
            ),
            new DFunction("getUserStreakDeadline",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "streakId", "optional" => false, "type" => "int"]],
                "Gets streak deadline for a given user.",
                ReturnType::TIME,
                $this,
                "streaks.getUserStreakDeadline(%user, %streak.id)"
            ),
            new DFunction("getUserStreaksAwards",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "repeatable", "optional" => true, "type" => "bool"],
                    ["name" => "extra", "optional" => true, "type" => "bool"],
                    ["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets awards of type 'streak' obtained by a given user. Some options available.",
                ReturnType::AWARDS_COLLECTION,
                $this,
                "streaks.getUserStreaksAwards(%user)"
            ),
            new DFunction("getUserStreaksTotalReward",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "repeatable", "optional" => true, "type" => "bool"],
                    ["name" => "extra", "optional" => true, "type" => "bool"],
                    ["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets total streaks reward for a given user. Some options available.",
                ReturnType::NUMBER,
                $this,
                "streaks.getUserStreaksTotalReward(%user, false, false, true)"
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given streak's ID in the system.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function id($streak): ValueNode
    {
        // NOTE: on mock data, badge will be mocked
        if (is_array($streak)) $streakId = $streak["id"];
        elseif (is_object($streak) && method_exists($streak, 'getId')) $streakId = $streak->getId();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($streakId, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given streak's name.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function name($streak): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($streak)) $name = $streak["name"];
        elseif (is_object($streak) && method_exists($streak, 'getName')) $name = $streak->getName();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given streak's goal.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function goal($streak): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($streak)) $goal = $streak["goal"];
        elseif (is_object($streak) && method_exists($streak, 'getGoal')) $goal = $streak->getGoal();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($goal, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given streak's description.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function description($streak): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($streak)) $description = $streak["description"];
        elseif (is_object($streak) && method_exists($streak, 'getDescription')) $description = $streak->getDescription();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given streak's color.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function color($streak): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($streak)) $color = $streak["color"];
        elseif (is_object($streak) && method_exists($streak, 'getColor')) $color = $streak->getColor();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given streak's reward.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function reward($streak): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($streak)) $reward = $streak["reward"];
        elseif (is_object($streak) && method_exists($streak, 'getReward')) $reward = $streak->getReward();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given streak's tokens.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function tokens($streak): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($streak)) $tokens = $streak["tokens"];
        elseif (is_object($streak) && method_exists($streak, 'getTokens')) $tokens = $streak->getTokens();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($tokens, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given streak's isRepeatable.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function isRepeatable($streak): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($streak)) $repeatable = $streak["isRepeatable"];
        elseif (is_object($streak) && method_exists($streak, 'isRepeatable')) $repeatable = $streak->isRepeatable();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($repeatable, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Gets a given streak's isExtra.
     *
     * @param $streak
     * @return ValueNode
     * @throws Exception
     */
    public function isExtra($streak): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($streak)) $extra = $streak["isExtra"];
        elseif (is_object($streak) && method_exists($streak, 'isExtra')) $extra = $streak->isExtra();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a streak.");
        return new ValueNode($extra, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }


    /*** ---------- Config ---------- ***/

    /**
     * Gets maximum XP each student can earn with streaks.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxXP(): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $maxXP = Core::dictionary()->faker()->numberBetween(20000, 22000);

        } else {
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $maxXP = $streaksModule->getMaxXP();
        }
        return new ValueNode($maxXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets maximum extra credit each student can earn with streaks.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxExtraCredit(): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $maxExtraCredit = Core::dictionary()->faker()->numberBetween(1000, 5000);

        } else {
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $maxExtraCredit = $streaksModule->getMaxExtraCredit();
        }
        return new ValueNode($maxExtraCredit, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** ---------- Streaks ---------- ***/

    /**
     * Gets streaks of course.
     *
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getStreaks(bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $streaks = array_map(function () {
                return $this->mockStreak();
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else $streaks = Streak::getStreaks($courseId, $active);

        return new ValueNode($streaks, $this);
    }

    /**
     * Gets users who have earned a given streak at least once.
     *
     * @param int $streakId
     * @return ValueNode
     * @throws Exception
     */
    public function getUsersWithStreak(int $streakId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock users
            $users = [];

        } else {
            $streaksModule = new Streaks($course);
            $users = $streaksModule->getUsersWithStreak($streakId);
        }
        return new ValueNode($users, Core::dictionary()->getLibraryById(UsersLibrary::ID));
    }

    /**
     * Gets streaks earned by a given user.
     *
     * @param int $userId
     * @param bool|null $isExtra
     * @param bool|null $isRepeatable
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreaks(int $userId, bool $isExtra = null, bool $isRepeatable = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock streaks
            $streaks = [];

        } else {
            $streaksModule = new Streaks($course);
            $streaks = $streaksModule->getUserStreaks($userId, $isExtra, $isRepeatable);
        }
        return new ValueNode($streaks, $this);
    }

    /**
     * Gets user progression on a given streak.
     *
     * @param int $userId
     * @param int $streakId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreakProgression(int $userId, int $streakId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $progression = Core::dictionary()->faker()->numberBetween(0, 2);

        } else {
            $streaksModule = new Streaks($course);
            $progression = $streaksModule->getUserStreakProgression($userId, $streakId);
        }
        return new ValueNode($progression, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets how many times a given user has completed a specific streak.
     *
     * @param int $userId
     * @param int $streakId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreakCompletions(int $userId, int $streakId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $completions = Core::dictionary()->faker()->numberBetween(0, 5);

        } else {
            $streaksModule = new Streaks($course);
            $completions = $streaksModule->getUserStreakCompletions($userId, $streakId);
        }
        return new ValueNode($completions, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets streak deadline for a given user.
     *
     * @param int $userId
     * @param int $streakId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreakDeadline(int $userId, int $streakId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $deadline = Core::dictionary()->faker()->dateTimeBetween("now", "+1 week")->format("Y-m-d H:i:s");

        } else {
            $streaksModule = new Streaks($course);
            $deadline = $streaksModule->getUserStreakDeadline($userId, $streakId);
        }
        return new ValueNode($deadline, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets streaks awards for a given user.
     * Option for extra credit:
     *  - if null --> gets awards for all streaks
     *  - if false --> gets awards only for streaks that are not extra credit
     *  - if true --> gets awards only for streaks that are extra credit
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $repeatable
     * @param bool|null $extra
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreaksAwards(int $userId, bool $repeatable = null, bool $extra = null, bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $awards = array_map(function () use ($userId) {
                return $this->mockAward($userId, "streak");
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else {
            $awardsModule = new Awards($course);
            $awards = $awardsModule->getUserStreaksAwards($userId, $repeatable, $extra, $active);
        }
        return new ValueNode($awards, Core::dictionary()->getLibraryById(AwardsLibrary::ID));
    }

    /**
     * Gets total streaks reward for a given user.
     * Option for extra credit:
     *  - if null --> gets total reward for all streaks
     *  - if false --> gets total reward only for streaks that are not extra credit
     *  - if true --> gets total reward only for streaks that are extra credit
     *
     * @param int $userId
     * @param bool|null $repeatable
     * @param bool|null $extra
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreaksTotalReward(int $userId, bool $repeatable = null, bool $extra = null, bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $reward = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $awardsModule = new Awards($course);
            $reward = $awardsModule->getUserStreaksTotalReward($userId, $repeatable, $active, $active);
        }
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
