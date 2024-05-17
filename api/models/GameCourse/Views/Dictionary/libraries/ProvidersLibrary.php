<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use Utils\Cache;
use Utils\Time;

class ProvidersLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "providers";    // NOTE: must match the name of the class
    const NAME = "Data Providers";
    const DESCRIPTION = "Gives access to a set of data providers that can be injected into charts.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
			new DFunction("badgeDistribution",
                [["name" => "users", "optional" => false, "type" => "array"],
                    ["name" => "interval", "optional" => true, "type" => "int"],
                    ["name" => "max", "optional" => true, "type" => "int"],
                    ["name" => "showAverage", "optional" => true, "type" => "bool"]
                ],
            	"Provides a distribution of the total number of badges of given users. Option for interval to group badges, max. number of badges and whether to show an average of each interval group.",
            	"collection",
            	$this
        	),
			new DFunction("XPEvolution",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "time", "optional" => false, "type" => "string"],
                    ["name" => "compareWith", "optional" => true, "type" => "array"],
                    ["name" => "compareWithLabel", "optional" => true, "type" => "string"]
                ],
            	"Provides total XP of a given user over time. Time options: 'day', 'week', 'month'. Option to compare evolution with other users.",
            	"collection",
            	$this
        	),
			new DFunction("XPDistribution",
                [["name" => "users", "optional" => false, "type" => "array"],
                    ["name" => "interval", "optional" => true, "type" => "int"],
                    ["name" => "max", "optional" => true, "type" => "int"],
                ],
            	"Provides a distribution of the total XP of given users. Option for interval to group XP, max. XP and whether to show an average of each interval group.",
            	"collection",
            	$this
        	),
			new DFunction("XPOverview",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "awardTypes", "optional" => true, "type" => "string"],
                    ["name" => "compareWith", "optional" => true, "type" => "array"],
                    ["name" => "compareWithLabel", "optional" => true, "type" => "string"]
                ],
            	"Provides an XP overview for a given user. Award types must follow the format: 'type: label'. Option to compare overview with other users.",
            	"collection",
            	$this
        	),
			new DFunction("leaderboardEvolution",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "time", "optional" => false, "type" => "string"]
                ],
            	"Provides leaderboard position of a given user over time. Time options: 'day', 'week', 'month'.",
            	"collection",
            	$this
        	)
		];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

	public function badgeDistribution(array $users, int $interval = 1, int $max = null, bool $showAverage = false): ValueNode
	{
		$badgeDistribution = [["name" => "Badge Distribution", "type" => "column", "data" => []]];
        if ($showAverage) $badgeDistribution[] = ["name" => "Average", "type" => "line", "data" => []];
        if ($interval > $max) $interval = 1;

        if (Core::dictionary()->mockData()) {
            if (is_null($max)) $max = 60;
            for ($i = ($interval === 1 ? 0 : $interval); $i <= $max; $i += $interval) {
                $badgeDistribution[0]["data"][] = ["x" => $i, "y" => Core::dictionary()->faker()->numberBetween(0, 50)];
                if ($showAverage) $badgeDistribution[1]["data"][] = ["x" => $i, "y" => Core::dictionary()->faker()->numberBetween(0, 50)];
            }

        } else {
            $course = Core::dictionary()->getCourse();
            if (!$course) $this->throwError("badgeDistribution", "no course found");

            $userIds = array_map(function ($user) { if (is_array($user)) return $user["id"]; return $user->getId(); }, $users);
            $nrUsers = count($userIds);

            if ($nrUsers !== 0) {
                // Get each user #badges
                $badgesByUser = [];
                foreach ($userIds as $userId) {
                    $badgesModules = new \GameCourse\Module\Badges\Badges($course);
                    $badgesByUser[] = count($badgesModules->getUserBadges($userId));
                }

                // Initialize data
                if (is_null($max)) $max = ceil(max($badgesByUser) / $interval) * $interval;
                for ($i = ($interval === 1 ? 0 : $interval); $i <= $max; $i += $interval) {
                    $badgeDistribution[0]["data"][] = ["x" => $i, "y" => 0];
                    if ($showAverage) $badgeDistribution[1]["data"][] = ["x" => $i, "y" => 0];
                }

                // Process data
                foreach ($badgesByUser as $userBadges) {
                    $i = $interval === 1 ? $userBadges : ($userBadges === $interval ? floor($userBadges / $interval) - 1 : floor($userBadges / $interval));
                    $badgeDistribution[0]["data"][$i]["y"] += 1;
                    if ($showAverage) {
                        if ($badgeDistribution[1]["data"][$i]["y"] === 0) $badgeDistribution[1]["data"][$i]["y"] = round($userBadges / $nrUsers);
                        else $badgeDistribution[1]["data"][$i]["y"] += round($userBadges / $nrUsers);
                    }
                }
            }
        }

        return new ValueNode($badgeDistribution, Core::dictionary()->getLibraryById(CollectionLibrary::ID));
	}

	public function XPEvolution(int $userId, string $time, array $compareWith = [], string $compareWithLabel = "Others"): ValueNode
	{
		$XPEvolution = [["name" => "You", "data" => []]];

        if (Core::dictionary()->mockData()) {
            for ($i = 0; $i <= 10; $i++) {
                $XPEvolution[0]["data"][] = ["x" => $i, "y" => Core::dictionary()->faker()->numberBetween($i * 500, ($i + 1) * 500)];
            }
            if (!empty($compareWith)) {
                $XPEvolution[] = ["name" => $compareWithLabel, "data" => []];
                for ($i = 0; $i <= 10; $i++) {
                    $XPEvolution[1]["data"][] = ["x" => $i, "y" => Core::dictionary()->faker()->numberBetween($i * 500, ($i + 1) * 500)];
                }
            }

        } else {
            $course = Core::dictionary()->getCourse();
            if (!$course) $this->throwError("XPEvolution", "no course found");

            $courseDates = $course->getData("startDate, endDate");
            if (!$courseDates["startDate"]) $this->throwError("XPEvolution", "course doesn't have a start date");
            if (!$courseDates["endDate"]) $this->throwError("XPEvolution", "course doesn't have an end date");

            $XPModule = new \GameCourse\Module\XPLevels\XPLevels($course);
            $userXP = $XPModule->getUserXP($userId);

            // Get time passed
            $baseline = $courseDates["startDate"];
            $now = date("Y-m-d H:i:s", time());
            $timePassed = Time::timeBetween($baseline, Time::earliest($now, $courseDates["endDate"]), $time);

            // Get data from cache if exists and is updated
            $cacheId = "xp_evolution_u$userId";
            $cacheValue = Cache::get($course->getId(), $cacheId);

            // Calculate user evolution
            if (!is_null($cacheValue) && !empty($cacheValue[0]["data"]) && end($cacheValue[0]["data"])["x"] === $timePassed) {
                $XPEvolution[0]["data"] = $cacheValue[0]["data"];

            } else {
                // Get user awards
                $awardsModule = new \GameCourse\Module\Awards\Awards($course);
                $awards = array_filter($awardsModule->getUserAwards($userId), function ($award) {
                    return $award["type"] !== \GameCourse\Module\Awards\AwardType::TOKENS;
                });

                $totalXP = 0;
                $t = 0;

                // Calculate XP over time
                while ($t <= $timePassed) {
                    $awardsOfTime = array_filter($awards, function ($award) use ($baseline, $t, $time) {
                        return Time::timeBetween($baseline, $award["date"], $time) == $t;
                    });
                    foreach ($awardsOfTime as $award) { $totalXP += $award["reward"]; }
                    $XPEvolution[0]["data"][] = ["x" => $t, "y" => $totalXP];
                    $t++;
                }
            }

            // Calculate others' avg. evolution
            if (!empty($compareWith)) {
                $userIds = array_values(array_filter(
                    array_map(function ($user) { if (is_array($user)) return $user["id"]; return $user->getId(); }, $compareWith),
                    function ($uId) use ($userId) { return $uId !== $userId; } // NOTE: ignore user to compare with
                ));
                $nrUsers = count($userIds);

                if ($nrUsers !== 0) {
                    $XPEvolution[] = ["name" => $compareWithLabel, "data" => []];
                    $totalXP = intval(Core::database()->executeQuery("SELECT SUM(xp) FROM " . \GameCourse\Module\XPLevels\XPLevels::TABLE_XP .
                        " WHERE course = " . Core::dictionary()->getCourse()->getId() . " AND user IN (" . implode(", ", $userIds) . ");")->fetch()[0]);

                    if (!is_null($cacheValue) && !empty($cacheValue[1]["data"]) && $totalXP === end($cacheValue[1]["data"])["y"]) {
                        $XPEvolution[1]["data"] = $cacheValue[1]["data"];

                    } else {
                        foreach ($userIds as $i => $uId) {
                            // Get user awards
                            $awardsModule = new \GameCourse\Module\Awards\Awards($course);
                            $awards = array_filter($awardsModule->getUserAwards($uId), function ($award) {
                                return $award["type"] !== \GameCourse\Module\Awards\AwardType::TOKENS;
                            });

                            $totalUserXP = 0;
                            $t = 0;

                            // Calculate XP over time
                            while ($t <= $timePassed) {
                                $awardsOfTime = array_filter($awards, function ($award) use ($baseline, $t, $time) {
                                    return Time::timeBetween($baseline, $award["date"], $time) == $t;
                                });
                                foreach ($awardsOfTime as $award) { $totalUserXP += $award["reward"]; }
                                if ($i == 0) $XPEvolution[1]["data"][$t] = ["x" => $t, "y" => round($totalUserXP / $nrUsers)];
                                else $XPEvolution[1]["data"][$t]["y"] += round($totalUserXP / $nrUsers);
                                $t++;
                            }
                        }
                    }
                }
            }

            // Store in cache
            $cacheValue = $XPEvolution;
            Cache::store($course->getId(), $cacheId, $cacheValue);
        }

        return new ValueNode($XPEvolution, Core::dictionary()->getLibraryById(CollectionLibrary::ID));
	}

	public function XPDistribution(array $users, int $interval = 1, int $max = null): ValueNode
	{
		$XPDistribution = [["name" => "XP Distribution", "type" => "column", "data" => []]];
        if ($interval > $max) $interval = 1;

        if (Core::dictionary()->mockData()) {
            if (is_null($max)) $max = 20000;
            for ($i = ($interval === 1 ? 0 : $interval); $i <= $max; $i += $interval) {
                $XPDistribution[0]["data"][] = ["x" => $i, "y" => Core::dictionary()->faker()->numberBetween(0, 50)];
            }

        } else {
            $course = Core::dictionary()->getCourse();
            if (!$course) $this->throwError("XPDistribution", "no course found");

            $userIds = array_map(function ($user) { if (is_array($user)) return $user["id"]; return $user->getId(); }, $users);
            $nrUsers = count($userIds);

            if ($nrUsers !== 0) {
                // Get each user XP
                $XPByUser = [];
                foreach ($userIds as $userId) {
                    $XPModule = new \GameCourse\Module\XPLevels\XPLevels($course);
                    $XPByUser[] = $XPModule->getUserXP($userId);
                }

                // Initialize data
                if (is_null($max)) $max = ceil(max($XPByUser) / $interval) * $interval;
                for ($i = ($interval === 1 ? 0 : $interval); $i <= $max; $i += $interval) {
                    $XPDistribution[0]["data"][] = ["x" => $i, "y" => 0];
                }

                // Process data
                foreach ($XPByUser as $userXP) {
                    $i = $interval === 1 ? $userXP : ($userXP === $interval ? floor($userXP / $interval) - 1 : floor($userXP / $interval));
                    $XPDistribution[0]["data"][$i]["y"] += 1;
                }
            }
        }

        return new ValueNode($XPDistribution, Core::dictionary()->getLibraryById(CollectionLibrary::ID));
	}

	public function XPOverview(int $userId, string $awardTypes = "", array $compareWith = [], string $compareWithLabel = "Others"): ValueNode
	{
		$XPOverview = [["name" => "You", "data" => []]];

        // Parse award types
        $awardTypesParsed = array_map("trim", explode(",", $awardTypes));
        $awardTypes = [];
        foreach ($awardTypesParsed as $awardType) {
            $awardTypeParsed = array_map("trim", explode(":", $awardType));
            $awardTypes[$awardTypeParsed[0]] = $awardTypeParsed[1];
        }

        if (Core::dictionary()->mockData()) {
            foreach($awardTypes as $type => $label) {
                $XPOverview[0]["data"][] = ["x" => $label, "y" => Core::dictionary()->faker()->numberBetween(0, 3000)];
            }
            if (!empty($compareWith)) {
                $XPOverview[] = ["name" => $compareWithLabel, "data" => []];
                foreach($awardTypes as $type => $label) {
                    $XPOverview[1]["data"][] = ["x" => $label, "y" => Core::dictionary()->faker()->numberBetween(0, 3000)];
                }
            }

        } else {
            $course = Core::dictionary()->getCourse();
            if (!$course) $this->throwError("XPOverview", "no course found");

            // Get user awards
            $awardsModule = new \GameCourse\Module\Awards\Awards($course);
            $awards = $awardsModule->getUserAwards($userId);

            // Calculate XP for each award type
            foreach($awardTypes as $type => $label) {
                $totalXP = array_sum(array_column(array_filter($awards, function ($award) use ($type) {
                    return $award["type"] === $type;
                }), "reward"));
                $XPOverview[0]["data"][] = ["x" => $label, "y" => $totalXP];
            }

            // Calculate others' avg. overview
            if (!empty($compareWith)) {
                $userIds = array_values(array_filter(
                    array_map(function ($user) { if (is_array($user)) return $user["id"]; return $user->getId(); }, $compareWith),
                    function ($uId) use ($userId) { return $uId !== $userId; } // NOTE: ignore user to compare with
                ));
                $nrUsers = count($userIds);

                if ($nrUsers !== 0) {
                    $XPOverview[] = ["name" => $compareWithLabel, "data" => []];

                    foreach ($userIds as $i => $uId) {
                        // Get user awards
                        $awards = $awardsModule->getUserAwards($uId);

                        // Calculate XP for each award type
                        $t = 0;
                        foreach($awardTypes as $type => $label) {
                            $totalUserXP = array_sum(array_column(array_filter($awards, function ($award) use ($type) {
                                return $award["type"] === $type;
                            }), "reward"));
                            if ($i == 0) $XPOverview[1]["data"][$t] = ["x" => $label, "y" => round($totalUserXP / $nrUsers)];
                            else $XPOverview[1]["data"][$t]["y"] += round($totalUserXP / $nrUsers);
                            $t++;
                        }
                    }
                }
            }
        }

        return new ValueNode($XPOverview, Core::dictionary()->getLibraryById(CollectionLibrary::ID));
	}

	public function leaderboardEvolution(int $userId, string $time): ValueNode
	{
		// NOTE: order by XP -> #badges -> name
        $leaderboardEvolution = [["name" => "You", "data" => []]];

        if (Core::dictionary()->mockData()) {
            for ($i = 0; $i <= 10; $i++) {
                $leaderboardEvolution[0]["data"][] = ["x" => $i, "y" => Core::dictionary()->faker()->numberBetween(1, 20)];
            }

        } else {
            $course = Core::dictionary()->getCourse();
            if (!$course) $this->throwError("leaderboardEvolution", "no course found");

            $courseDates = $course->getData("startDate, endDate");
            if (!$courseDates["startDate"]) $this->throwError("leaderboardEvolution", "course doesn't have a start date");
            if (!$courseDates["endDate"]) $this->throwError("leaderboardEvolution", "course doesn't have an end date");

            $userIds = array_map(function ($user) { if (is_array($user)) return $user["id"]; return $user->getId(); },
                $course->getStudents(true));

            // Get time passed
            $baseline = $courseDates["startDate"];
            $now = date("Y-m-d H:i:s", time());
            $timePassed = Time::timeBetween($baseline, Time::earliest($now, $courseDates["endDate"]), $time);

            // Find today position
            $XPToday = [];
            $badgesToday = [];
            foreach ($userIds as $uId) {
                $XPModule = new \GameCourse\Module\XPLevels\XPLevels($course);
                $totalUserXP = $XPModule->getUserXP($uId);

                $awardsModule = new \GameCourse\Module\Awards\Awards($course);
                $userBadges = count($awardsModule->getUserBadgesAwards($uId));

                $XPToday[$uId] = $totalUserXP;
                $badgesToday[$uId] = $userBadges;
            }

            // Order by XP -> #badges -> name
            usort($userIds, function ($a, $b) use ($XPToday, $badgesToday) {
                $xpA = $XPToday[$a];
                $xpB = $XPToday[$b];

                if ($xpA == $xpB) {
                    $badgesA = $badgesToday[$a];
                    $badgesB = $badgesToday[$b];

                    if ($badgesA == $badgesB) {
                        $nameA = \GameCourse\User\User::getUserById($a)->getName();
                        $nameB = \GameCourse\User\User::getUserById($b)->getName();

                        return strcmp($nameA, $nameB);

                    } return $badgesB - $badgesA;

                } return $xpB - $xpA;
            });

            // Find position
            $position = array_search($userId, $userIds) + 1;

            // Get data from cache if exists and is updated
            $cacheId = "leaderboard_evolution_u$userId";
            $cacheValue = Cache::get($course->getId(), $cacheId);

            // Calculate user evolution
            if (!is_null($cacheValue) && end($cacheValue[0]["data"])["x"] === $timePassed && end($cacheValue[0]["data"])["y"] === $position) {
                $leaderboardEvolution[0]["data"] = $cacheValue[0]["data"];

            } else {
                $course = Core::dictionary()->getCourse();
                if (!$course) $this->throwError("leaderboardEvolution", "no course found");

                // Calculate elements over time
                $XPOverTime = [];
                $badgesOverTime = [];

                foreach ($userIds as $uId) {
                    // Get user awards
                    $awardsModule = new \GameCourse\Module\Awards\Awards($course);
                    $awards = array_filter($awardsModule->getUserAwards($uId), function ($award) {
                        return $award["type"] !== \GameCourse\Module\Awards\AwardType::TOKENS;
                    });

                    $totalUserXP = 0;
                    $userXPOverTime = [];

                    $userBadges = 0;
                    $userBadgesOverTime = [];

                    $t = 0;
                    while ($t <= $timePassed) {
                        $awardsOfTime = array_filter($awards, function ($award) use ($baseline, $t, $time) {
                            return Time::timeBetween($baseline, $award["date"], $time) == $t;
                        });
                        foreach ($awardsOfTime as $award) {
                            $totalUserXP += $award["reward"];
                            if ($award["type"] === \GameCourse\Module\Awards\AwardType::BADGE) $userBadges++;
                        }
                        $userXPOverTime[] = ["x" => $t, "y" => $totalUserXP];
                        $userBadgesOverTime[] = ["x" => $t, "y" => $userBadges];
                        $t++;
                    }
                    $XPOverTime[$uId] = $userXPOverTime;
                    $badgesOverTime[$uId] = $userBadgesOverTime;
                }

                // Calculate position over time
                $t = 0;
                while ($t <= $timePassed) {
                    // Order by XP, badges count and name
                    usort($userIds, function ($a, $b) use ($t, $XPOverTime, $badgesOverTime) {
                        $xpA = $XPOverTime[$a][$t]["y"];
                        $xpB = $XPOverTime[$b][$t]["y"];

                        if ($xpA == $xpB) {
                            $badgesA = $badgesOverTime[$a][$t]['y'];
                            $badgesB = $badgesOverTime[$b][$t]['y'];

                            if ($badgesA == $badgesB) {
                                $nameA = \GameCourse\User\User::getUserById($a)->getName();
                                $nameB = \GameCourse\User\User::getUserById($b)->getName();

                                return strcmp($nameA, $nameB);
                            }
                            return $badgesB - $badgesA;
                        }
                        return $xpB - $xpA;
                    });

                    // Find position
                    $position = array_search($userId, $userIds) + 1;
                    $leaderboardEvolution[0]["data"][] = ["x" => $t, "y" => $position];
                    $t++;
                }
            }

            // Store in cache
            $cacheValue = $leaderboardEvolution;
            Cache::store($course->getId(), $cacheId, $cacheValue);
        }

        return new ValueNode($leaderboardEvolution, Core::dictionary()->getLibraryById(CollectionLibrary::ID));
	}
}
