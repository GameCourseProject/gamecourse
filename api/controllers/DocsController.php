<?php
namespace API;

use Event\Event;
use GameCourse\AutoGame\AutoGame;
use Event\EventType;
use GameCourse\Adaptation\GameElement;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Badges\Badge;
use GameCourse\Module\ClassCheck\ClassCheck;
use GameCourse\Module\GoogleSheets\GoogleSheets;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\Fenix\Fenix;
use GameCourse\Module\Leaderboard\Leaderboard;
use GameCourse\Module\Module;
use GameCourse\Module\Moodle\Moodle;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Streaks\Streak;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\Level;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\Module\Profile\Profile;
use GameCourse\NotificationSystem\Notification;
use GameCourse\Role\Role;
use GameCourse\User\User;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Component\CoreComponent;
use GameCourse\Views\CreationMode;
use GameCourse\Views\Dictionary\CollectionLibrary;
use GameCourse\Views\Page\Page;
use GameCourse\Views\ViewHandler;
use OpenApi\Generator;
use PDO;
use Utils\Cache;
use Utils\Utils;

/**
 * This is the Docs controller, which holds API endpoints for
 * documentation related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 */
class DocsController
{
    /**
     * @OA\Get(
     *     path="/?module=docs&request=getAPIDocs",
     *     tags={"Documentation"},
     *     @OA\Response(response="200", description="GameCourse API documentation")
     * )
     */
    public function getAPIDocs()
    {
        API::requireAdminPermission();
        $openAPI = Generator::scan([ROOT_PATH . "controllers"]);
        API::response(json_decode($openAPI->toJSON()));
    }


    // FIXME: everything below should be deleted
    //        (was used for development purposes only)
    public function test()
    {
        $courseId = 1;
        $viewTree = [
            ["type" => "block", "children" => [
                [
                    ["aspect" => ["viewerRole" => "Student"], "type" => "text", "text" => "I'm a student"],
                    ["aspect" => ["viewerRole" => "Teacher"], "type" => "text", "text" => "I'm a teacher"],
                    ["type" => "text", "text" => "I'm neither a student nor a teacher"]
                ]
            ]],
        ];
//        $viewTree = [
//            ["type" => "block", "children" => [
//                [
//                    ["type" => "chart", "chartType" => "progress", "data" => ["value" => 21, "max" => 50]]
//                ]
//            ]]
//        ];
//        $viewTree = [
//            ["type" => "block", "children" => [
//                [
//                    ["type" => "collapse", "icon" => "arrow", "children" => [
//                        [
//                            ["type" => "text", "text" => "Click me to show/hide content"]
//                        ],
//                        [
//                            ["type" => "block", "children" => [
//                                [
//                                    ["type" => "text", "text" => "hello"]
//                                ],
//                                [
//                                    ["type" => "text", "text" => "world"]
//                                ]
//                            ]]
//                        ]
//                    ]]
//                ]
//            ]],
//        ];
//        $viewTree = [
//            ["type" => "block", "children" => [
//                [
//                    ["type" => "table", "children" => [
//                        [
//                            ["type" => "row", "rowType" => "header", "children" => [
//                                [["type" => "text", "text" => "Header1"]],
//                                [["type" => "text", "text" => "Header2"]],
//                                [["type" => "text", "text" => "Header3"]]
//                            ]]
//                        ],
//                        [
//                            ["type" => "row", "rowType" => "body", "children" => [
//                                [["type" => "block", "children" => [
//                                    [["type" => "text", "text" => "oi"]],
//                                    [["type" => "image", "src" => "https://images.unsplash.com/photo-1675667328761-7a373b11ce98"]]
//                                ]]],
//                                [["type" => "text", "text" => "Body2"]],
//                                [["type" => "text", "text" => "Body3"]],
//                            ]]
//                        ]
//                    ]]
//                ]
//            ]],
//        ];

        $leaderboard = [
            ["type" => "block", "children" => [
                [
                    ["type" => "block", "children" => [
                        [["type" => "icon", "icon" => "tabler-books"]],
                        [["type" => "text", "text" => "Leaderboard"]]
                    ]]
                ],
                [
                    ["type" => "table", "children" => [
                        [
                            ["type" => "row", "rowType" => "header", "children" => [
                                [["type" => "text", "text" => "#"]],
                                [["type" => "text", "text" => "Student"]],
                                [["type" => "text", "text" => "Experience"]],
                                [["type" => "text", "text" => "Level"]],
                                [["type" => "text", "text" => "Badges"]]
                            ]]
                        ],
                        [
                            ["type" => "row", "rowType" => "body", "loopData" => "{users.getStudents(true).sort()}", "children" => [
                                    [["type" => "text", "text" => "{%index + 1}"]],
                                    [["type" => "block", "children" => [
                                        [["type" => "image", "src" => "https://images.unsplash.com/photo-1675667328761-7a373b11ce98"]],
                                        [["type" => "block", "children" => [
                                            [["type" => "text", "text" => "Name"]],
                                            [["type" => "text", "text" => "Major"]]
                                        ]]]
                                    ]]],
                                    [["type" => "block", "children" => [
                                        [["type" => "text", "text" => "15000 XP"]],
                                        [["type" => "chart", "chartType" => "line", "data" => "{providers.XPEvolution(%item.id, \"day\")}"]]
                                    ]]],
                                    [["type" => "block", "children" => [
                                        [["type" => "text", "text" => "0-Absent Without Leave (AWOL)"]],
                                        [["type" => "text", "text" => "1000 for L1 at 1000 XP"]]
                                    ]]],
                                    [["type" => "chart", "chartType" => "progress", "data" => ["value" => 31, "max" => 65]]]
                            ]]
                        ]
                    ]]
                ]
            ]]
        ];

        $studentId = 2;
        $teacherId = 1;
        $watcherId = 3;
//        $vr = ViewHandler::insertViewTree($viewTree, 1);
//        var_dump($vr);
        $paramsToPopulate = ["course" => 1, "viewer" => $studentId, "user" => $studentId];
        API::response(ViewHandler::renderView(1758523867455415, Aspect::getAspects($courseId, $studentId, true), $paramsToPopulate));
    }

    public function setupPages()
    {
        $leaderboard = json_decode(file_get_contents(ROOT_PATH . "/temp/leaderboard.txt"), true);
        $awards = json_decode(file_get_contents(MODULES_FOLDER . "/Awards/templates/userAwardsList.txt"), true);
        $spendings = json_decode(file_get_contents(MODULES_FOLDER . "/VirtualCurrency/templates/userSpendingsList.txt"), true);
        $profile = json_decode(file_get_contents(ROOT_PATH . "/temp/profile.txt"), true);
        $badges = json_decode(file_get_contents(ROOT_PATH . "/temp/badges.txt"), true);

//        $leaderboard = json_decode(file_get_contents(MODULES_FOLDER . "/Leaderboard/templates/leaderboard.txt"), true);
//        $awards = json_decode(file_get_contents(MODULES_FOLDER . "/Awards/templates/userAwardsList.txt"), true);
//        $spendings = json_decode(file_get_contents(MODULES_FOLDER . "/VirtualCurrency/templates/userSpendingsList.txt"), true);
//        $profile = json_decode(file_get_contents(MODULES_FOLDER . "/Profile/templates/profile.txt"), true);
//        $badges = json_decode(file_get_contents(MODULES_FOLDER . "/Badges/templates/badges.txt"), true);

        Page::addPage(9, CreationMode::BY_VALUE, "Leaderboard", $leaderboard);
        Page::addPage(9, CreationMode::BY_VALUE, "Awards", $awards);
        Page::addPage(9, CreationMode::BY_VALUE, "Spendings", $spendings);
        Page::addPage(9, CreationMode::BY_VALUE, "Profile", $profile);
        Page::addPage(9, CreationMode::BY_VALUE, "Badges", $badges);
        Page::addPage(9, CreationMode::BY_VALUE, "Skill Tree", [["aspect" => ["viewerRole" => null, "userRole" => "Student"], "type" => "block"]]);
        Page::addPage(9, CreationMode::BY_VALUE, "Streaks", [["aspect" => ["viewerRole" => null, "userRole" => "Student"], "type" => "block"]]);
    }

    public function setupCoreComponents()
    {
        CoreComponent::setupCoreComponents();
    }


    public function validate()
    {
        $errorMsgs = [];

        $courseId = 9;
        $course = Course::getCourseById($courseId);

        $students = $course->getStudents(true);

        $awardsModule = new Awards($course);
        $XPModule = new XPLevels($course);
        $VCModule = new VirtualCurrency($course);

        foreach ($students as $student) {
            $userId = $student["id"];
//            var_dump("User $userId");
            $participations = array_merge(AutoGame::getParticipations($courseId, $userId), AutoGame::getParticipations($courseId, null, "peergraded post", null, $userId));
            $awards = $awardsModule->getUserAwards($userId);

            $totalXP = 0;
            $wallet = 0;

            // VC
            $res = self::validateVC($userId, $participations, $awards, $errorMsgs);
            $totalXP += $res["xp"];
            $wallet += $res["tokens"];
//            var_dump("VC: " . $res["xp"] . " XP / " . $res["tokens"] . " tokens");

            // Streaks
            $res = self::validateStreaks($userId, $participations, $awards, $errorMsgs);
            $totalXP += $res["xp"];
            $wallet += $res["tokens"];
//            var_dump("Streaks: " . $res["xp"] . " XP / " . $res["tokens"] . " tokens");

            // Skills
            $res = self::validateSkills($userId, $participations, $awards, $errorMsgs, $courseId);
            $totalXP += $res["xp"];
            $wallet += $res["tokens"];
//            var_dump("Skills: " . $res["xp"] . " XP / " . $res["tokens"] . " tokens");

            // Badges
            $res = self::validateBadges($userId, $participations, $awards, $errorMsgs);
            $totalXP += $res["xp"];
            $wallet += $res["tokens"];
//            var_dump("Badges: " . $res["xp"] . " XP / " . $res["tokens"] . " tokens");

            // Others
            $res = self::validateOthers($userId, $participations, $awards, $errorMsgs);
            $totalXP += $res["xp"];
            $wallet += $res["tokens"];
//            var_dump("Others: " . $res["xp"] . " XP / " . $res["tokens"] . " tokens");

            // Check total XP & level
            $actualTotalXP = $XPModule->getUserXP($userId);
            $actualLevel = Level::getUserLevel($courseId, $userId);
            $awardsTotalXP = intval(Core::database()->select(Awards::TABLE_AWARD, ["course" => $courseId, "user" => $userId], "SUM(reward)", null, [["type", "tokens"]]));
            if ($totalXP !== $actualTotalXP)
                $errorMsgs[$userId][] = "Incorrect total XP for user with ID = $userId. Total XP was $actualTotalXP XP and should have been $totalXP XP.";
            if ($awardsTotalXP !== $actualTotalXP)
                $errorMsgs[$userId][] = "Incorrect total XP for user with ID = $userId. Total XP awarded was $actualTotalXP XP and should have been $awardsTotalXP XP.";
            if (Level::getLevelByXP($courseId, $awardsTotalXP)->getId() !== $actualLevel->getId())
                $errorMsgs[$userId][] = "Incorrect level for user with ID = $userId. Level was " . $actualLevel->getNumber() . " and should have been " . Level::getLevelByXP($courseId, $awardsTotalXP)->getNumber() . ".";

            // Check wallet
            $actualWallet = $VCModule->getUserTokens($userId);
            $tokensReceived = intval(Core::database()->select(Awards::TABLE_AWARD, ["course" => $courseId, "user" => $userId, "type" => "tokens"], "SUM(reward)"));
            $tokensSpent = intval(Core::database()->select(VirtualCurrency::TABLE_VC_SPENDING, ["course" => $courseId, "user" => $userId], "SUM(amount)"));
            if ($wallet !== $actualWallet)
                $errorMsgs[$userId][] = "Incorrect wallet for user with ID = $userId. Wallet was $actualWallet Gold and should have been $wallet Gold.";
            if (($tokensReceived - $tokensSpent) !== $actualWallet)
                $errorMsgs[$userId][] = "Incorrect wallet for user with ID = $userId. Wallet awarded was $actualWallet Gold and should have been " . ($tokensReceived - $tokensSpent) . "Gold.";
        }

        // Find duplicates (awards)
        $awardsList = array_column(Core::database()->selectMultiple(Awards::TABLE_AWARD, ["course" => $courseId], "DISTINCT description"), "description");
        $awardsListLower = array_map(function ($d) { return strtolower($d); }, $awardsList);
        if (count(array_unique($awardsListLower)) !== count($awardsListLower)) {
            foreach ($awardsListLower as $desc) {
                if (in_array($desc, $awardsListLower))
                    $errorMsgs["X"][] = "Different names for award '$desc'.";
            }
        }
        $ignore = ["Peergraded colleague's post"];
        foreach ($awardsList as $description) {
            $query = "SELECT DISTINCT user, COUNT(*) as dupes
                          FROM award
                          WHERE description = \"$description\"
                          GROUP BY user, type
                          HAVING COUNT(*) > 1";
            $res = Core::database()->executeQuery($query)->fetchAll(PDO::FETCH_ASSOC);
//            var_dump($description . ": " . count($res));
            if (!empty($res) && !in_array($description, $ignore)) {
                foreach ($res as $row) {
                    if (intval($row["dupes"]) > 1)
                        $errorMsgs[$row["user"]][] = "Duplicated award '$description'.";
                }
            }
        }

        // Find duplicates (spending)
        $spendingList = array_column(Core::database()->selectMultiple(VirtualCurrency::TABLE_VC_SPENDING, ["course" => $courseId], "DISTINCT description"), "description");
        $spendingListLower = array_map(function ($d) { return strtolower($d); }, $spendingList);
        if (count(array_unique($spendingListLower)) !== count($spendingListLower)) {
            foreach ($spendingListLower as $desc) {
                if (in_array($desc, $spendingListLower))
                    $errorMsgs["X"][] = "Different names for award '$desc'.";
            }
        }
        foreach ($spendingList as $description) {
            $query = "SELECT DISTINCT user, COUNT(*) as dupes
                          FROM virtual_currency_spending
                          WHERE description = \"$description\"
                          GROUP BY user
                          HAVING COUNT(*) > 1";
            $res = Core::database()->executeQuery($query)->fetchAll(PDO::FETCH_ASSOC);
//            var_dump($description . ": " . count($res));
            if (!empty($res)) {
                foreach ($res as $row) {
                    if (intval($row["dupes"]) > 1)
                        $errorMsgs[$row["user"]][] = "Duplicated award '$description'.";
                }
            }
        }

        API::response(["incorrect" => count($errorMsgs), "errors" => $errorMsgs]);
    }

    private static function validateVC(int $userId, array $participations, array $awards, array &$errorMsgs): array
    {
        $totalXP = 0;
        $wallet = 0;

        // Peergrading
        $nrPeergrades = count(array_filter($participations, function ($p) use ($userId) { return $p["type"] == "peergraded post" && $p["evaluator"] == $userId; }));
        $a = array_filter($awards, function ($a) { return $a["description"] == "Peergraded colleague's post" && $a["type"] == "tokens"; });
        foreach ($a as $pa) {
            if ($pa["reward"] !== 10)
                $errorMsgs[$userId][] = "Didn't receive exactly 10 tokens for a peergrade.";
        }
        $nrAwards = count($a);
        if ($nrAwards < $nrPeergrades)
            $errorMsgs[$userId][] = "Missing " . ($nrPeergrades - $nrAwards) . " peergrading awards.";
        else if ($nrAwards > $nrPeergrades)
            $errorMsgs[$userId][] = "Has " . ($nrAwards - $nrPeergrades) . " invalid peergrading awards.";
        $wallet += ($nrPeergrades * 10);

        // Gold exchange
        $a = array_values(array_filter($awards, function ($a) { return $a["description"] == "Gold exchange"; }));
        $nrAwards = count($a);
        if ($nrAwards > 1)
            $errorMsgs[$userId][] = "Has more than one Gold Exchange award.";
        $s = Core::database()->selectMultiple(VirtualCurrency::TABLE_VC_SPENDING, ["user" => $userId, "description" => "Gold exchange"]);
        $nrSpending = count($s);
        if ($nrSpending > 1)
            $errorMsgs[$userId][] = "Has more than one Gold Exchange spending.";
        if ($nrAwards > $nrSpending)
            $errorMsgs[$userId][] = "Missing Gold Exchange spending.";
        else if ($nrAwards < $nrSpending)
            $errorMsgs[$userId][] = "Missing Gold Exchange award.";
        if ($nrAwards > 0 && $nrSpending > 0) {
            $xpEarned = intval($a[0]["reward"]);
            $goldExchanged = intval($s[0]["amount"]);

            $tokensReceived = (new Awards(new Course(9)))->getUserTotalRewardByType($userId, "tokens");
            $tokensSpent = (new VirtualCurrency(new Course(9)))->getUserTotalSpending($userId) - $goldExchanged;
            $tokensToExchange = min($tokensReceived - $tokensSpent, 1000);

            if ($goldExchanged !== $tokensToExchange)
                $errorMsgs[$userId][] = "Exchanged $goldExchanged Gold and should've exchanged $tokensToExchange Gold";

            if ($goldExchanged > 1000)
                $errorMsgs[$userId][] = "Exchanged more than 1000 Gold.";
            if ($xpEarned > intval(round($goldExchanged / 3)))
                $errorMsgs[$userId][] = "Received more XP than should have by exchanging Gold.";
            else if ($xpEarned < intval(round($goldExchanged / 3)))
                $errorMsgs[$userId][] = "Received less XP than should have by exchanging Gold.";
            else {
                $totalXP += $xpEarned;
                $wallet -= $goldExchanged;
            }
        }

        return ["xp" => $totalXP, "tokens" => $wallet];
    }

    private static function validateStreaks(int $userId, array $participations, array $awards, array &$errorMsgs): array
    {
        $totalXP = 0;
        $wallet = 0;

        // TODO: verify individually

        $awards = array_filter($awards, function ($a) { return ($a["type"] == "streak" || $a["type"] == "tokens") && strpos($a["description"], "time") !== false; });
        foreach ($awards as $a) {
            if ($a["type"] == "streak") $totalXP += intval($a["reward"]);
            else $wallet += intval($a["reward"]);
        }

        return ["xp" => $totalXP, "tokens" => $wallet];
    }

    private static function validateSkills(int $userId, array $participations, array $awards, array &$errorMsgs, int $courseId): array
    {
        $totalXP = 0;
        $wallet = 0;
        $skillsXP = 0;

        // Organize participations by skill
        $skillParticipations = [];
        foreach (array_filter($participations, function ($p) { return $p["type"] == "graded post" && Utils::strStartsWith($p["description"], "Skill Tree, Re: "); }) as $p) {
            $skillName = trim(str_replace("Skill Tree, Re: ", "", $p["description"]));
            $skillParticipations[$skillName][] = ["date" => $p["date"], "rating" => intval($p["rating"])];
        }

        foreach ($skillParticipations as $skillName => $participations) {
            $skill = Skill::getSkillByName($courseId, $skillName);

            $spending = Core::database()->selectMultiple(VirtualCurrency::TABLE_VC_SPENDING, ["course" => $courseId, "user" => $userId], "*", null, [], [], null, ["description" => "$skillName%"]);
            $nrAttempts = count($participations);
            while ($nrAttempts > 0) {
                if (count(array_filter($spending, function ($s) use ($skillName, $nrAttempts) { return strpos($s["description"], $skillName) !== false && str_contains($s["description"], strval($nrAttempts)); })) < 1)
                   $errorMsgs[$userId][] = "Didn't find spending for skill '$skillName' on attempt #$nrAttempts.";
                $wallet -= ($skill->isWildcard() ? 50 : 10) + 10 * ($nrAttempts - 1);
                $nrAttempts--;
            }

            $completed = count(array_filter($participations, function ($p) { return $p["rating"] >= 3; })) > 0;
            if ($completed) {
                $nrAwards = count(array_filter($awards, function ($a) use ($skillName) { return $a["description"] == $skillName && $a["type"] == "skill"; }));
                if ($nrAwards == 0) $errorMsgs[$userId][] = "Missing skill '$skillName' award.";
                else if ($nrAwards > 1) $errorMsgs[$userId][] = "Has more than one skill '$skillName' award.";
                $skillXP = min($skill->getTier()->getReward(), 6500 - $skillsXP);
                $totalXP += $skillXP;
                $skillsXP += $skillXP;
            }
        }

        return ["xp" => $totalXP, "tokens" => $wallet];

    }

    private static function validateBadges(int $userId, array $participations, array $awards, array &$errorMsgs): array
    {
        $totalXP = 0;
        $wallet = 0;

        // TODO: verify individually

        $awards = array_filter($awards, function ($a) { return $a["type"] == "badge"; });
        foreach ($awards as $a) {
            $totalXP += $a["reward"];
        }

        return ["xp" => $totalXP, "tokens" => $wallet];
    }

    private static function validateOthers(int $userId, array $participations, array $awards, array &$errorMsgs): array
    {
        $totalXP = 0;
        $wallet = 0;

        // Initial bonus
        $nrAwards = count(array_filter($awards, function ($a) { return $a["description"] == "Initial Bonus" && $a["type"] == "bonus"; }));
        if ($nrAwards == 0) $errorMsgs[$userId][] = "Missing initial bonus award.";
        else if ($nrAwards > 1) $errorMsgs[$userId][] = "Has more than one initial bonus award.";
        $totalXP += 500;

        // Initial tokens
        $nrAwards = count(array_filter($awards, function ($a) { return $a["description"] == "Initial Gold" && $a["type"] == "tokens"; }));
        if ($nrAwards == 0) $errorMsgs[$userId][] = "Missing initial tokens award.";
        else if ($nrAwards > 1) $errorMsgs[$userId][] = "Has more than one initial tokens award.";
        $wallet += 50;

        // Quiz Grades
        $p = array_filter($participations, function ($p) { return $p["type"] == "quiz grade"; });
        foreach ($p as $pt) {
            if ($pt["description"] == "Dry Run") continue;
            $nrAwards = count(array_filter($awards, function ($a) use ($pt) { return $a["description"] == $pt["description"] && $a["type"] == "quiz"; }));
            if ($nrAwards == 0) $errorMsgs[$userId][] = "Missing quiz '" . $pt["description"] . "' award.";
            if ($nrAwards > 1) $errorMsgs[$userId][] = "Has more than one quiz '" . $pt["description"] . "' award.";
            $totalXP += $pt["rating"];
        }

        // Presentation Grades
        $p = array_filter($participations, function ($p) { return $p["type"] == "presentation grade"; });
        if (count($p) > 1) $errorMsgs[$userId][] = "Has more than one presentation grade.";
        foreach ($p as $pt) {
            $nrAwards = count(array_filter($awards, function ($a) { return $a["description"] == "Presentation" && $a["type"] == "presentation"; }));
            if ($nrAwards == 0) $errorMsgs[$userId][] = "Missing presentation award.";
            if ($nrAwards > 1) $errorMsgs[$userId][] = "Has more than one presentation award.";
            $totalXP += $pt["rating"];
        }

        // Lab Grades
        $p = array_filter($participations, function ($p) { return $p["type"] == "lab grade"; });
        foreach ($p as $pt) {
            $nrAwards = count(array_filter($awards, function ($a) use ($pt) { return $a["description"] == "Lab " . $pt["description"] && $a["type"] == "labs"; }));
            if ($nrAwards == 0) $errorMsgs[$userId][] = "Missing lab '" . $pt["description"] . "' award.";
            if ($nrAwards > 1) $errorMsgs[$userId][] = "Has more than one lab '" . $pt["description"] . "' award.";
            $totalXP += $pt["rating"];
        }

        // Exam Grades
        $nrAwards = count(array_filter($awards, function ($a) { return $a["description"] == "Exam" && $a["type"] == "exam"; }));
        if ($nrAwards > 0) $errorMsgs[$userId][] = "Shouldn't have an exam grade.";

        return ["xp" => $totalXP, "tokens" => $wallet];
    }


    public function addAdaptation(array $roles, int $course, string $moduleId){
        $parent = array_keys($roles)[0];
        $versions = array_keys($roles[$parent]);

        Role::addAdaptationRolesToCourse($course, $moduleId, $parent, $versions);
        foreach ($roles[$parent] as $key => $value){
            $roleId = Role::getRoleId($key, $course);
            GameElement::addGameElementDescription($roleId, $value[0]);
        }
    }

    public function createAdaptationRoles()
    {
        $course = new Course(9);

        $this->addAdaptation(Badges::ADAPTATION_BADGES, $course->getId(), Badges::ID);
        GameElement::addGameElement($course->getId(), Badges::ID);

        $this->addAdaptation(Leaderboard::ADAPTATION_LEADERBOARD, $course->getId(), Leaderboard::ID);
        GameElement::addGameElement($course->getId(), Leaderboard::ID);

        $this->addAdaptation(Profile::ADAPTATION_PROFILE, $course->getId(), Profile::ID);
        GameElement::addGameElement($course->getId(), Profile::ID);


    }


    public function sendReminder(){
        $course = new Course(9);
        $message = "Don't forget to answer your preference questionnaires! Go to 'Adaptation' tab for more";
        $users = $course->getStudents(true);

        foreach ($users as $user){

            $responseBadges = GameElement::isQuestionnaireAnswered($course->getId(), $user["id"], 1);
            $responseLeaderboard = GameElement::isQuestionnaireAnswered($course->getId(), $user["id"], 4);
            $responseProfile = GameElement::isQuestionnaireAnswered($course->getId(), $user["id"], 7);

            if (!$responseBadges || !$responseLeaderboard || !$responseProfile){
                Notification::addNotification($course->getId(), $user["id"], $message);
            }

        }
    }

}
