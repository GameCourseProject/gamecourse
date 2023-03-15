<?php
namespace GameCourse\Module\Leaderboard;

use Exception;
use GameCourse\Adaptation\GameElement;
use GameCourse\Course\Course;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\Views\Dictionary\ReturnType;

/**
 * This is the Leaderboard module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Leaderboard extends Module
{
    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Leaderboard";  // NOTE: must match the name of the class
    const NAME = "Leaderboard";
    const DESCRIPTION = "Provides different leaderboard templates with students' progress on the course.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => Badges::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];

    const ADAPTATION_LEADERBOARD = [ "Leaderboard" =>
        ["LB001" => "Shows entire leaderboard",
         "LB002" => "Leaderboard is snapped and shows 5 people above and below you"]];

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->initTemplates();
        $this->addAdaptationRolesToCourse(self::ADAPTATION_LEADERBOARD);
         //parent::initEvents();  // FIXME: Debug only
        GameElement::addGameElement($this->course->getId(), self::ID);

        $this->initProviders();
    }

    public function providers(): array
    {
        $leaderboardEvolution =  [
            "name" => "leaderboardEvolution",
            "description" => "Provides leaderboard position of a given user over time. Time options: 'day', 'week', 'month'.",
            "returnType" => ReturnType::COLLECTION,
            "function" => "// NOTE: order by XP -> #badges -> name
        \$leaderboardEvolution = [[\"name\" => \"You\", \"data\" => []]];

        if (Core::dictionary()->mockData()) {
            for (\$i = 0; \$i <= 10; \$i++) {
                \$leaderboardEvolution[0][\"data\"][] = [\"x\" => \$i, \"y\" => Core::dictionary()->faker()->numberBetween(1, 20)];
            }

        } else {
            \$course = Core::dictionary()->getCourse();
            if (!\$course) \$this->throwError(\"leaderboardEvolution\", \"no course found\");

            \$courseDates = \$course->getData(\"startDate, endDate\");
            if (!\$courseDates[\"startDate\"]) \$this->throwError(\"leaderboardEvolution\", \"course doesn't have a start date\");
            if (!\$courseDates[\"endDate\"]) \$this->throwError(\"leaderboardEvolution\", \"course doesn't have an end date\");

            \$userIds = array_map(function (\$user) { if (is_array(\$user)) return \$user[\"id\"]; return \$user->getId(); },
                \$course->getStudents(true));

            // Get time passed
            \$baseline = \$courseDates[\"startDate\"];
            \$now = date(\"Y-m-d H:i:s\", time());
            \$timePassed = Time::timeBetween(\$baseline, Time::earliest(\$now, \$courseDates[\"endDate\"]), \$time);

            // Find today position
            \$XPToday = [];
            \$badgesToday = [];
            foreach (\$userIds as \$uId) {
                \$XPModule = new \GameCourse\Module\XPLevels\XPLevels(\$course);
                \$totalUserXP = \$XPModule->getUserXP(\$uId);

                \$awardsModule = new \GameCourse\Module\Awards\Awards(\$course);
                \$userBadges = count(\$awardsModule->getUserBadgesAwards(\$uId));

                \$XPToday[\$uId] = \$totalUserXP;
                \$badgesToday[\$uId] = \$userBadges;
            }

            // Order by XP -> #badges -> name
            usort(\$userIds, function (\$a, \$b) use (\$XPToday, \$badgesToday) {
                \$xpA = \$XPToday[\$a];
                \$xpB = \$XPToday[\$b];

                if (\$xpA == \$xpB) {
                    \$badgesA = \$badgesToday[\$a];
                    \$badgesB = \$badgesToday[\$b];

                    if (\$badgesA == \$badgesB) {
                        \$nameA = \GameCourse\User\User::getUserById(\$a)->getName();
                        \$nameB = \GameCourse\User\User::getUserById(\$b)->getName();

                        return strcmp(\$nameA, \$nameB);

                    } return \$badgesB - \$badgesA;

                } return \$xpB - \$xpA;
            });

            // Find position
            \$position = array_search(\$userId, \$userIds) + 1;

            // Get data from cache if exists and is updated
            \$cacheId = \"leaderboard_evolution_u\$userId\";
            \$cacheValue = Cache::get(\$course->getId(), \$cacheId);

            // Calculate user evolution
            if (!is_null(\$cacheValue) && end(\$cacheValue[0][\"data\"])[\"x\"] === \$timePassed && end(\$cacheValue[0][\"data\"])[\"y\"] === \$position) {
                \$leaderboardEvolution[0][\"data\"] = \$cacheValue[0][\"data\"];

            } else {
                \$course = Core::dictionary()->getCourse();
                if (!\$course) \$this->throwError(\"leaderboardEvolution\", \"no course found\");

                // Calculate elements over time
                \$XPOverTime = [];
                \$badgesOverTime = [];

                foreach (\$userIds as \$uId) {
                    // Get user awards
                    \$awardsModule = new \GameCourse\Module\Awards\Awards(\$course);
                    \$awards = array_filter(\$awardsModule->getUserAwards(\$uId), function (\$award) {
                        return \$award[\"type\"] !== \GameCourse\Module\Awards\AwardType::TOKENS;
                    });

                    \$totalUserXP = 0;
                    \$userXPOverTime = [];

                    \$userBadges = 0;
                    \$userBadgesOverTime = [];

                    \$t = 0;
                    while (\$t <= \$timePassed) {
                        \$awardsOfTime = array_filter(\$awards, function (\$award) use (\$baseline, \$t, \$time) {
                            return Time::timeBetween(\$baseline, \$award[\"date\"], \$time) == \$t;
                        });
                        foreach (\$awardsOfTime as \$award) {
                            \$totalUserXP += \$award[\"reward\"];
                            if (\$award[\"type\"] === \GameCourse\Module\Awards\AwardType::BADGE) \$userBadges++;
                        }
                        \$userXPOverTime[] = [\"x\" => \$t, \"y\" => \$totalUserXP];
                        \$userBadgesOverTime[] = [\"x\" => \$t, \"y\" => \$userBadges];
                        \$t++;
                    }
                    \$XPOverTime[\$uId] = \$userXPOverTime;
                    \$badgesOverTime[\$uId] = \$userBadgesOverTime;
                }

                // Calculate position over time
                \$t = 0;
                while (\$t <= \$timePassed) {
                    // Order by XP, badges count and name
                    usort(\$userIds, function (\$a, \$b) use (\$t, \$XPOverTime, \$badgesOverTime) {
                        \$xpA = \$XPOverTime[\$a][\$t][\"y\"];
                        \$xpB = \$XPOverTime[\$b][\$t][\"y\"];

                        if (\$xpA == \$xpB) {
                            \$badgesA = \$badgesOverTime[\$a][\$t]['y'];
                            \$badgesB = \$badgesOverTime[\$b][\$t]['y'];

                            if (\$badgesA == \$badgesB) {
                                \$nameA = \GameCourse\User\User::getUserById(\$a)->getName();
                                \$nameB = \GameCourse\User\User::getUserById(\$b)->getName();

                                return strcmp(\$nameA, \$nameB);
                            }
                            return \$badgesB - \$badgesA;
                        }
                        return \$xpB - \$xpA;
                    });

                    // Find position
                    \$position = array_search(\$userId, \$userIds) + 1;
                    \$leaderboardEvolution[0][\"data\"][] = [\"x\" => \$t, \"y\" => \$position];
                    \$t++;
                }
            }

            // Store in cache
            \$cacheValue = \$leaderboardEvolution;
            Cache::store(\$course->getId(), \$cacheId, \$cacheValue);
        }

        return new ValueNode(\$leaderboardEvolution, Core::dictionary()->getLibraryById(CollectionLibrary::ID));",
            "args" => ["int \$userId", "string \$time"]
        ];

        return [$leaderboardEvolution];
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->removeAdaptationRolesFromCourse(self::ADAPTATION_LEADERBOARD);
        GameElement::removeGameElement($this->course->getId(), self::ID);
        $this->removeTemplates();
        $this->removeProviders();
    }
}
