<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Badges\Badge;
use GameCourse\Module\Badges\Badges;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use InvalidArgumentException;

class BadgesLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }

    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "badges";    // NOTE: must match the name of the class
    const NAME = "Badges";
    const DESCRIPTION = "Provides access to information regarding badges.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace allows you to create pages displaying the Badges configured for this course. A badge has the following structure:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{
            "id": 12,
            "course": 1,
            "name": "Lab King",
            "description": "Attend the labs, be the best",
            "nrLevels": 1,
            "isExtra": true,
            "isBragging": false,
            "isCount": true,
            "isPoint": false,
            "isActive": true,
            "image": "http://localhost/gamecourse/api/course_data/1-Multimedia_Content_Production/badges/Lab_King/badge.png"
        }</code></pre>
        </div><br>
        <p>To obtain all badges of a course, you can simply use:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{badges.getBadges()}</code></pre>
        </div>
        <p>This might be useful if you want, for example, to create a page with a list of badges. You can do so by plugging the expression above in the 
        <span class="text-secondary">Repeat this component for every item in</span> field on a Block component.</p>
        <p>When using the Repetition field, the variable <span class="text-info">%item</span> becomes available for the component. We recommend also creating your own
          Auxiliary Variable, named <span class="text-secondary">badge</span>, with the value <span class="text-secondary">{%item}</span>. 
          You will notice that if you add components inside the repeated block, your custom variable will show up in the Inherited Variables section of those components.
        </p><br>
        <p>This means that you can now use the variable to access the badge's attributes. For example, to show the name of the Badge, it's as simple as adding a Text component,
          with the following value in the text field:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{%badge.name}</code></pre>
        </div><br>
        <br>
        <p>It might be interesting to show, for example, which users have obtained level 1 of the Badge. To do so, we can use the same logic of creating a new component and
          filling its Repetition field, now with the expression</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{badges.getUsersWithBadge(%badge.id, 1)}</code></pre>
        </div>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockUser(int $id = null, string $email = null, string $studentNumber = null) : array
    {
        return [
            "id" => $id ? $id : Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => Core::dictionary()->faker()->name(),
            "email" => $email ? $email : Core::dictionary()->faker()->email(),
            "major" => Core::dictionary()->faker()->text(5),
            "nickname" => Core::dictionary()->faker()->text(10),
            "studentNumber" => $studentNumber ? $studentNumber : Core::dictionary()->faker()->numberBetween(11111, 99999),
            "theme" => null,
            "username" => $email ? $email : Core::dictionary()->faker()->email(),
            "image" => null,
            "lastActivity" => Core::dictionary()->faker()->dateTimeThisYear(),
            "landingPage" => null,
            "isActive" => true
        ];
    }

    private function mockBadge(int $id = null, string $name = null) : array
    {
        return [
            "id" => $id ? $id : Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => $name ? $name : Core::dictionary()->faker()->text(20),
            "description" => Core::dictionary()->faker()->text(50),
            "nrLevels" => Core::dictionary()->faker()->numberBetween(1, 3),
            "isExtra" => Core::dictionary()->faker()->boolean(),
            "isBragging" => Core::dictionary()->faker()->boolean(),
            "isCount" => Core::dictionary()->faker()->boolean(),
            "isPoint" => Core::dictionary()->faker()->boolean(),
            "isActive" => true,
            "levels" => array_map(function () {
                return $this->mockBadgeLevel();
            }, range(1, Core::dictionary()->faker()->numberBetween(1, 3)))
        ];
    }

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

    private function mockProgression(int $userId = null) : array
    {
        return [
            "id" => Core::dictionary()->faker()->numberBetween(0, 100),
            "user" => $userId,
            "course" => 0,
            "description" => Core::dictionary()->faker()->text(30),
            "type" => Core::dictionary()->faker()->text(15),
            "date" => Core::dictionary()->faker()->dateTimeThisYear(),
            "rating" => Core::dictionary()->faker()->numberBetween(0, 5),
            "post" => null,
            "evaluator" => null,
            "link" => null
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
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Gets a given badge's ID in the system.",
                ReturnType::NUMBER,
                $this,
            "%badge.id"
            ),
            new DFunction("name",
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Gets a given badge's name.",
                ReturnType::TEXT,
                $this,
                "%badge.name"
            ),
            new DFunction("description",
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Gets a given badge's description.",
                ReturnType::TEXT,
                $this,
                "%badge.description"
            ),
            new DFunction("image",
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Gets a given badge's image.",
                ReturnType::TEXT,
                $this,
                "%badge.image"
            ),
            new DFunction("levels",
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Gets a given badge's levels.",
                ReturnType::BADGE_LEVELS_COLLECTION,
                $this,
                "%badge.levels"
            ),
            new DFunction("isExtra",
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Checks whether a given badge is extra credit.",
                ReturnType::BOOLEAN,
                $this,
                "%badge.isExtra"
            ),
            new DFunction("isBragging",
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Checks whether a given badge is bragging.",
                ReturnType::BOOLEAN,
                $this,
                "%badge.isBragging"
            ),
            new DFunction("isBasedOnCounts",
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Checks whether a given badge is based on counting occurrences of a given type.",
                ReturnType::BOOLEAN,
                $this,
                "%badge.isBasedOnCounts"
            ),
            new DFunction("isBasedOnPoints",
                [["name" => "badge", "optional" => false, "type" => "Badge"]],
                "Checks whether a given badge is based on earning a certain amount of points.",
                ReturnType::BOOLEAN,
                $this,
                "%badge.isBasedOnPoints"
            ),
            new DFunction("getMaxXP",
                [],
                "Gets maximum XP each student can earn with badges.",
                ReturnType::NUMBER,
                $this,
                "badges.getMaxXP()"
            ),
            new DFunction("getMaxExtraCredit",
                [],
                "Gets maximum extra credit each student can earn with badges.",
                ReturnType::NUMBER,
                $this,
                "badges.getMaxExtraCredit()"
            ),
            new DFunction("getBlankImage",
                [],
                "Gets blank badge image.",
                ReturnType::TEXT,
                $this,
                "badges.getBlankImage()"
            ),
            new DFunction("getBadgeById",
                [["name" => "badgeId", "optional" => false, "type" => "int"]],
                "Gets a badge by its ID.",
                ReturnType::OBJECT,
                $this,
            "badges.getBadgeById(%badge.id)"
            ),
            new DFunction("getBadgeByName",
                [["name" => "name", "optional" => false, "type" => "string"]],
                "Gets a badge by its name.",
                ReturnType::OBJECT,
                $this,
                "badges.getBadgeByName('Amphitheatre Lover')"
            ),
            new DFunction("getBadges",
                [["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets badges of course. Option to filter by badge state.",
                ReturnType::BADGES_COLLECTION,
                $this,
                "badges.getBadges(true)"
            ),
            new DFunction("getUsersWithBadge",
                [["name" => "badgeId", "optional" => false, "type" => "int"],
                    ["name" => "level", "optional" => false, "type" => "int"],
                    ["name" => "orderByDate", "optional" => true, "type" => "bool"]],
                "Gets users who have earned a given badge up to a certain level. Option to order users by the date they acquired badge level.",
                ReturnType::USERS_COLLECTION,
                $this,
            "badges.getUsersWithBadge(%badge.id, %level.number, true)"
            ),
            new DFunction("getUserBadges",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "isExtra", "optional" => true, "type" => "bool"],
                    ["name" => "isBragging", "optional" => true, "type" => "bool"],
                    ["name" => "isCount", "optional" => true, "type" => "bool"],
                    ["name" => "isPoint", "optional" => true, "type" => "bool"]],
                "Gets badges earned by a given user.",
                ReturnType::BADGES_COLLECTION,
                $this,
            "badges.getUserBadges(%user, false, false, true)"
            ),
            new DFunction("getUserBadgeProgression",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "badgeId", "optional" => false, "type" => "int"]],
                "Gets user progression on a given badge.",
                ReturnType::NUMBER,
                $this,
                "badges.getUserBadgeProgression(%user, %badge.id)"
            ),
            new DFunction("getUserBadgeProgressionInfo",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "badgeId", "optional" => false, "type" => "int"]],
                "Gets user progression information on a given badge, e.g. description and links to posts.",
                ReturnType::BADGE_PROGRESSION_COLLECTION,
                $this,
                "badges.getUserBadgeProgressionInfo(%user, %badge.id)"
            ),
            new DFunction("getUserBadgeLevel",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "badgeId", "optional" => false, "type" => "int"]],
                "Gets level earned by a given user on a specific badge.",
                ReturnType::NUMBER,
                $this,
                "badges.getUserBadgeLevel(%user, %badge.id)"
            ),
            new DFunction("getUserBadgeNextLevel",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "badgeId", "optional" => false, "type" => "int"]],
                "Gets the next level user can earn on a specific badge.",
                ReturnType::NUMBER,
                $this,
                "badges.getUserBadgeNextLevel(%user, %badge.id)"
            ),
            new DFunction("getUserBadgesAwards",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "extra", "optional" => true, "type" => "bool"],
                    ["name" => "bragging", "optional" => true, "type" => "bool"],
                    ["name" => "count", "optional" => true, "type" => "bool"],
                    ["name" => "point", "optional" => true, "type" => "bool"],
                    ["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets awards of type 'badge' obtained by a given user. Some options available.",
                ReturnType::AWARDS_COLLECTION,
                $this,
                "badges.getUserBadgesAwards(%user, true, false)"
            ),
            new DFunction("getUserBadgesTotalReward",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "extra", "optional" => true, "type" => "bool"],
                    ["name" => "bragging", "optional" => true, "type" => "bool"],
                    ["name" => "count", "optional" => true, "type" => "bool"],
                    ["name" => "point", "optional" => true, "type" => "bool"],
                    ["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets the total badges reward value obtained by a given user. Some options available.",
                ReturnType::NUMBER,
                $this,
                "badges.getUserBadgesTotalReward(%user, false, false, true, true, true)"
            ),
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given badge's ID in the system.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function id($badge): ValueNode
    {
        // NOTE: on mock data, badge will be mocked
        if (is_array($badge)) $badgeId = $badge["id"];
        elseif (is_object($badge) && method_exists($badge, 'getId')) $badgeId = $badge->getId();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        return new ValueNode($badgeId, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given badge's name.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function name($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $name = $badge["name"];
        elseif (is_object($badge) && method_exists($badge, 'getName')) $name = $badge->getName();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given badge's description.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function description($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $description = $badge["description"];
        elseif (is_object($badge) && method_exists($badge, 'getDescription')) $description = $badge->getDescription();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given badge's image.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function image($badge): ValueNode
    {
        // NOTE: on mock data, badge will be mocked
        if (is_array($badge)) $image = $badge["image"];
        elseif (is_object($badge) && method_exists($badge, 'getImage')) $image = $badge->getImage();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets badge levels.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function levels($badge): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $levels = $badge["levels"];
        } elseif (is_array($badge)) {
            $badge = Badge::getBadgeById($badge["id"]);
            $levels = $badge->getLevels();
        } elseif (is_object($badge) && method_exists($badge, 'getLevels')) {
            $levels = $badge->getLevels();
        } else {
            throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        }
        return new ValueNode($levels, Core::dictionary()->getLibraryById(BadgeLevelsLibrary::ID));
    }

    /**
     * Checks whether a given badge is extra credit.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isExtra($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isExtra = $badge["isExtra"];
        elseif (is_object($badge) && method_exists($badge, 'isExtra')) $isExtra = $badge->isExtra();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        return new ValueNode($isExtra, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Checks whether a given badge is bragging.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isBragging($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isBragging = $badge["isBragging"];
        elseif (is_object($badge) && method_exists($badge, 'isBragging')) $isBragging = $badge->isBragging();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        return new ValueNode($isBragging, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Checks whether a given badge is based on counting ocurrences of a given type.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isBasedOnCounts($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isCount = $badge["isCount"];
        elseif (is_object($badge) && method_exists($badge, 'isCount')) $isCount = $badge->isCount();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        return new ValueNode($isCount, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Checks whether a given badge is based on earning a certain amount of points.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isBasedOnPoints($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isPoint= $badge["isPoint"];
        elseif (is_object($badge) && method_exists($badge, 'isPoint')) $isPoint = $badge->isPoint();
        else throw new InvalidArgumentException("Invalid type for first argument: expected a badge.");
        return new ValueNode($isPoint, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }


    /*** ---------- Config ---------- ***/

    /**
     * Gets maximum XP each student can earn with badges.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxXP(): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $maxXP = Core::dictionary()->faker()->numberBetween(20000, 22000);

        } else {
            $badgesModule = new Badges($course);
            $maxXP = $badgesModule->getMaxXP();
        }
        return new ValueNode($maxXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets maximum extra credit each student can earn with badges.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxExtraCredit(): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $maxExtraCredit = Core::dictionary()->faker()->numberBetween(1000, 5000);

        } else {
            $badgesModule = new Badges($course);
            $maxExtraCredit = $badgesModule->getMaxExtraCredit();
        }
        return new ValueNode($maxExtraCredit, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets blank badge image.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getBlankImage(): ValueNode
    {
        $badgesModule = new Badges(Core::dictionary()->getCourse());
        $image = $badgesModule->getBlankImage();
        return new ValueNode($image, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a badge by its ID.
     *
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getBadgeById(int $badgeId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $badge = $this->mockBadge($badgeId);

        } else $badge = Badge::getBadgeById($badgeId);
        return new ValueNode($badge, $this);
    }

    /**
     * Gets a badge by its name.
     *
     * @param string $name
     * @return ValueNode
     * @throws Exception
     */
    public function getBadgeByName(string $name): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $badge = $this->mockBadge(null, $name);

        } else $badge = Badge::getBadgeByName($courseId, $name);
        return new ValueNode($badge, $this);
    }

    /**
     * Gets badges of course.
     *
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getBadges(bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $badges = array_map(function () {
                return $this->mockBadge();
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else $badges = Badge::getBadges($courseId, $active);

        return new ValueNode($badges, $this);
    }

    /**
     * Gets users who have earned a given badge up to a certain level.
     * Option to order users by the date they acquired badge level.
     *
     * @param int $badgeId
     * @param int $level
     * @param bool $orderByDate
     * @return ValueNode
     * @throws Exception
     */
    public function getUsersWithBadge(int $badgeId, int $level, bool $orderByDate = true): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $users = array_map(function () {
                return $this->mockUser();
            }, range(1, Core::dictionary()->faker()->numberBetween(0, 5)));

        } else {
            $badgesModule = new Badges($course);
            $users = $badgesModule->getUsersWithBadge($badgeId, $level, $orderByDate);
        }
        return new ValueNode($users, Core::dictionary()->getLibraryById(UsersLibrary::ID));
    }

    /**
     * Gets badges earned by a given user.
     *
     * @param int $userId
     * @param bool|null $isExtra
     * @param bool|null $isBragging
     * @param bool|null $isCount
     * @param bool|null $isPoint
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadges(int $userId, bool $isExtra = null, bool $isBragging = null, bool $isCount = null,
                                  bool $isPoint = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $badges = array_map(function () {
                return $this->mockBadge();
            }, range(1, Core::dictionary()->faker()->numberBetween(1, 3)));

        } else {
            $badgesModule = new Badges($course);
            $badges = $badgesModule->getUserBadges($userId, $isExtra, $isBragging, $isCount, $isPoint);
        }
        return new ValueNode($badges, $this);
    }

    /**
     * Gets user progression on a given badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgeProgression(int $userId, int $badgeId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $progression = Core::dictionary()->faker()->numberBetween(0, 10);

        } else {
            $badgesModule = new Badges($course);
            $progression = $badgesModule->getUserBadgeProgression($userId, $badgeId);
        }
        return new ValueNode($progression, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets user progression information on a given badge,
     * e.g. description and links to posts.
     *
     * @param int $userId
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgeProgressionInfo(int $userId, int $badgeId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $progression = array_map(function () {
                return $this->mockProgression();
            }, range(1, Core::dictionary()->faker()->numberBetween(1, 3)));

        } else {
            $badgesModule = new Badges($course);
            $progression = $badgesModule->getUserBadgeProgressionInfo($userId, $badgeId);
        }
        return new ValueNode($progression, Core::dictionary()->getLibraryById(BadgeProgressionLibrary::ID));
    }

    /**
     * Gets level earned by a given user on a specific badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgeLevel(int $userId, int $badgeId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $level = Core::dictionary()->faker()->numberBetween(0, 3);

        } else {
            $badgesModule = new Badges($course);
            $level = $badgesModule->getUserBadgeLevel($userId, $badgeId);
        }

        return new ValueNode($level, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets the next level user can earn on a specific badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgeNextLevel(int $userId, int $badgeId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $userLevel = Core::dictionary()->faker()->numberBetween(0, 3);
            $nrLevels = Core::dictionary()->faker()->numberBetween(1, 3);

        } else {
            $badgesModule = new Badges($course);
            $userLevel = $badgesModule->getUserBadgeLevel($userId, $badgeId);
            $nrLevels = Badge::getBadgeById($badgeId)->getNrLevels();
        }
        $nextLevel = $userLevel < $nrLevels ? $userLevel + 1 : null;
        return new ValueNode($nextLevel, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets badges awards for a given user.
     * Option for extra credit:
     *  - if null --> gets awards for all badges
     *  - if false --> gets awards only for badges that are not extra credit
     *  - if true --> gets awards only for badges that are extra credit
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $extra
     * @param bool|null $bragging
     * @param bool|null $count
     * @param bool|null $point
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgesAwards(int $userId, bool $extra = null, bool $bragging = null, bool $count = null,
                                        bool $point = null, bool $active = null): ValueNode
    {
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $awards = array_map(function () use ($userId) {
                return $this->mockAward($userId, "badge");
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else {
            $awardsModule = new Awards($course);
            $awards = $awardsModule->getUserBadgesAwards($userId, $extra, $bragging, $count, $point, $active);
        }
        return new ValueNode($awards, Core::dictionary()->getLibraryById(AwardsLibrary::ID));
    }

    /**
     * Gets total badges reward for a given user.
     * Option for extra credit:
     *  - if null --> gets total reward for all badges
     *  - if false --> gets total reward only for badges that are not extra credit
     *  - if true --> gets total reward only for badges that are extra credit
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $extra
     * @param bool|null $bragging
     * @param bool|null $count
     * @param bool|null $point
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgesTotalReward(int $userId, bool $extra = null, bool $bragging = null, bool $count = null,
                                             bool $point = null, bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $reward = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $awardsModule = new Awards($course);
            $reward = $awardsModule->getUserBadgesTotalReward($userId, $extra, $bragging, $count, $point, $active);
        }
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}