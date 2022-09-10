<?php
namespace GameCourse\Module\Badges;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Awards\AwardType;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class BadgesTest extends TestCase
{
    private $course;
    private $module;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["modules"], ["CronJob"]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->course = $course;

        // Enable Badges module
        (new Awards($course))->setEnabled(true);
        $badges = new Badges($course);
        $badges->setEnabled(true);
        $this->module = $badges;
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER]);
        if (Core::database()->tableExists(Badge::TABLE_BADGE)) TestingUtils::resetAutoIncrement([Badge::TABLE_BADGE]);
        TestingUtils::cleanFileStructure();
        TestingUtils::cleanEvents();
    }

    protected function onNotSuccessfulTest(Throwable $t): void
    {
        $this->tearDown();
        parent::onNotSuccessfulTest($t);
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass(): void
    {
        TestingUtils::tearDownAfterClass();
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // Setup

    /**
     * @test
     * @throws Exception
     */
    public function init()
    {
        // Given
        $this->module->setEnabled(false);

        // When
        $this->module->init();

        // Then
        $sql = file_get_contents(MODULES_FOLDER . "/" . Badges::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }
        $this->assertEquals(0, $this->module->getMaxExtraCredit());
        $this->assertTrue(file_exists($this->module->getDataFolder()));
        $this->assertTrue(Section::getSectionByName($this->course->getId(), $this->module::RULE_SECTION)->exists());
    }

    /**
     * @test
     * @throws Exception
     */
    public function copy()
    {
        // Given
        $copyTo = Course::addCourse("Course Copy", "CPY", "2021-2022", "#ffffff",
            null, null, false, false);

        (new Awards($copyTo))->setEnabled(true);
        $xpLevels = (new XPLevels($copyTo));
        $xpLevels->setEnabled(true);
        $xpLevels->updateMaxExtraCredit(1000);
        $badgesModule = new Badges($copyTo);
        $badgesModule->setEnabled(true);

        $this->module->updateMaxExtraCredit(500);
        $badge1 = Badge::addBadge($this->course->getId(), "Badge1", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badge2 = Badge::addBadge($this->course->getId(), "Badge2", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);

        // When
        $this->module->copyTo($copyTo);

        // Then
        $this->assertEquals($this->module->getMaxExtraCredit(), $badgesModule->getMaxExtraCredit());

        $badges = Badge::getBadges($this->course->getId());
        $copiedBadges = Badge::getBadges($copyTo->getId());
        $this->assertSameSize($badges, $copiedBadges);
        foreach ($badges as $i => $badge) {
            $this->assertEquals($badge["name"], $copiedBadges[$i]["name"]);
            $this->assertEquals($badge["description"], $copiedBadges[$i]["description"]);
            $this->assertEquals($badge["isExtra"], $copiedBadges[$i]["isExtra"]);
            $this->assertEquals($badge["isBragging"], $copiedBadges[$i]["isBragging"]);
            $this->assertEquals($badge["isCount"], $copiedBadges[$i]["isCount"]);
            $this->assertEquals($badge["isPost"], $copiedBadges[$i]["isPost"]);
            $this->assertEquals($badge["isPoint"], $copiedBadges[$i]["isPoint"]);
            $this->assertEquals($badge["isActive"], $copiedBadges[$i]["isActive"]);

            $this->assertEquals(file_get_contents($badge["image"]), file_get_contents($copiedBadges[$i]["image"]));

            $this->assertEquals($badge["nrLevels"], $copiedBadges[$i]["nrLevels"]);
            for ($l = 1; $l <= $badge["nrLevels"]; $l++) {
                $this->assertEquals($badge["desc$l"], $copiedBadges[$i]["desc$l"]);
                $this->assertEquals($badge["goal$l"], $copiedBadges[$i]["goal$l"]);
                $this->assertEquals($badge["reward$l"], $copiedBadges[$i]["reward$l"]);
            }

            $this->assertEquals((new Rule($badge["rule"]))->getText(), (new Rule($copiedBadges[$i]["rule"]))->getText());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function disable()
    {
        // When
        $this->module->setEnabled(false);

        // Then
        $sql = file_get_contents(MODULES_FOLDER . "/" . Badges::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
        $this->assertFalse(file_exists($this->module->getDataFolder()));
        $this->assertNull(Section::getSectionByName($this->course->getId(), $this->module::RULE_SECTION));
    }


    // Config

    /**
     * @test
     */
    public function getMaxExtraCredit()
    {
        $this->assertEquals(0, $this->module->getMaxExtraCredit());
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateMaxExtraCredit()
    {
        $this->module->updateMaxExtraCredit(1000);
        $this->assertEquals(1000, $this->module->getMaxExtraCredit());
    }


    // Bagdges

    /**
     * @test
     * @throws Exception
     */
    public function getUsersWithBadge()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);
        $user3 = User::addUser("Student C", "student_c", AuthService::FENIX, null,
            3, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");
        $this->course->addUserToCourse($user3->getId(), "Student", null, false);

        $badge1 = Badge::addBadge($this->course->getId(), "Badge1", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badge2 = Badge::addBadge($this->course->getId(), "Badge2", "Perform action", false, false, false, false, false, [
            ["description" => "five times", "goal" => 5, "reward" => 500],
            ["description" => "ten times", "goal" => 10, "reward" => 500],
            ["description" => "twelve times", "goal" => 12, "reward" => 500]
        ]);

        $this->insertAward($this->course->getId(), $user1->getId(), $badge1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user2->getId(), $badge1->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $badge2->getId(), "Award 3", 500);
        $this->insertAward($this->course->getId(), $user1->getId(), $badge2->getId(), "Award 4", 500);

        // Level 1
        $this->assertCount(2, $this->module->getUsersWithBadge($badge1->getId(), 1));
        $this->assertCount(1, $this->module->getUsersWithBadge($badge2->getId(), 1));

        // Level 2
        $this->assertEmpty($this->module->getUsersWithBadge($badge1->getId(), 2));
        $this->assertCount(1, $this->module->getUsersWithBadge($badge2->getId(), 2));

        // Level 3
        $this->assertEmpty($this->module->getUsersWithBadge($badge1->getId(), 3));
        $this->assertEmpty($this->module->getUsersWithBadge($badge2->getId(), 3));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadges()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $badge1 = Badge::addBadge($this->course->getId(), "Badge1", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badge2 = Badge::addBadge($this->course->getId(), "Badge2", "Perform action", false, false, false, false, false, [
            ["description" => "five times", "goal" => 5, "reward" => 500],
            ["description" => "ten times", "goal" => 10, "reward" => 500],
            ["description" => "twelve times", "goal" => 12, "reward" => 500]
        ]);

        $this->insertAward($this->course->getId(), $user1->getId(), $badge1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user1->getId(),$badge1->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $badge2->getId(), "Award 3", 500);

        $keys = ["id", "course", "name", "description", "nrLevels", "isExtra", "isBragging", "isCount", "isPost", "isPoint", "isActive", "rule", "level"];
        $nrKeys = count($keys);

        // Has badges
        $badges = $this->module->getUserBadges($user1->getId());
        $this->assertIsArray($badges);
        $this->assertCount(2, $badges);
        foreach ($keys as $key) {
            foreach ($badges as $i => $badge) {
                $this->assertCount($nrKeys, array_keys($badge));
                $this->assertArrayHasKey($key, $badge);
                if ($key == "level") $this->assertEquals($i == 0 ? 2 : 1, $badge[$key]);
                else $this->assertEquals($badge[$key], ${"badge".($i+1)}->getData($key));
            }
        }

        // Doesn't have badges
        $this->assertEmpty($this->module->getUserBadges($user2->getId()));
    }

    // TODO: getUserBadgeProgression

    /**
     * @test
     * @throws Exception
     */
    public function getUserBadgeLevel()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $badge1 = Badge::addBadge($this->course->getId(), "Badge1", "Perform action", false, false, false, false, false, [
            ["description" => "one time", "goal" => 1, "reward" => 100],
            ["description" => "two times", "goal" => 2, "reward" => 100],
            ["description" => "three times", "goal" => 3, "reward" => 100]
        ]);
        $badge2 = Badge::addBadge($this->course->getId(), "Badge2", "Perform action", false, false, false, false, false, [
            ["description" => "five times", "goal" => 5, "reward" => 500],
            ["description" => "ten times", "goal" => 10, "reward" => 500],
            ["description" => "twelve times", "goal" => 12, "reward" => 500]
        ]);

        $this->insertAward($this->course->getId(), $user1->getId(), $badge1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $badge1->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $badge2->getId(), "Award 3", 500);

        // Then
        $this->assertEquals(2, $this->module->getUserBadgeLevel($user1->getId(), $badge1->getId()));
        $this->assertEquals(1, $this->module->getUserBadgeLevel($user1->getId(), $badge2->getId()));
        $this->assertEquals(0, $this->module->getUserBadgeLevel($user2->getId(), $badge1->getId()));
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private function insertAward(int $courseId, int $userId, int $badgeId, string $description, int $reward)
    {
        Core::database()->insert(Awards::TABLE_AWARD, [
            "user" => $userId,
            "course" => $courseId,
            "description" => $description,
            "type" => AwardType::BADGE,
            "moduleInstance" => $badgeId,
            "reward" => $reward
        ]);
    }
}
