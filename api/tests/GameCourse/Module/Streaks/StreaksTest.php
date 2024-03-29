<?php
namespace GameCourse\Module\Streaks;

use Exception;
use GameCourse\AutoGame\AutoGame;
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
class StreaksTest extends TestCase
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

        // Enable Streaks module
        (new Awards($course))->setEnabled(true);
        $streaks = new Streaks($course);
        $streaks->setEnabled(true);
        $this->module = $streaks;
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
        if (Core::database()->tableExists(Streak::TABLE_STREAK)) TestingUtils::resetAutoIncrement([Streak::TABLE_STREAK]);
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Streaks::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }
        $this->assertEquals(0, $this->module->getMaxXP());
        $this->assertEquals(0, $this->module->getMaxExtraCredit());
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
        (new XPLevels($copyTo))->setEnabled(true);
        $streaksModule = new Streaks($copyTo);
        $streaksModule->setEnabled(true);

        $this->module->updateMaxXP(1000);
        $this->module->updateMaxExtraCredit(500);
        $streak1 = Streak::addStreak($this->course->getId(), "Streak1", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streak2 = Streak::addStreak($this->course->getId(), "Streak2", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        // When
        $this->module->copyTo($copyTo);

        // Then
        $this->assertEquals($this->module->getMaxXP(), $streaksModule->getMaxXP());
        $this->assertEquals($this->module->getMaxExtraCredit(), $streaksModule->getMaxExtraCredit());

        $streaks = Streak::getStreaks($this->course->getId());
        $copiedStreaks = Streak::getStreaks($copyTo->getId());
        $this->assertSameSize($streaks, $copiedStreaks);
        foreach ($streaks as $i => $streak) {
            $this->assertEquals($streak["name"], $copiedStreaks[$i]["name"]);
            $this->assertEquals($streak["description"], $copiedStreaks[$i]["description"]);
            $this->assertEquals($streak["color"], $copiedStreaks[$i]["color"]);
            $this->assertEquals($streak["goal"], $copiedStreaks[$i]["goal"]);
            $this->assertEquals($streak["periodicityGoal"], $copiedStreaks[$i]["periodicityGoal"]);
            $this->assertEquals($streak["periodicityNumber"], $copiedStreaks[$i]["periodicityNumber"]);
            $this->assertEquals($streak["periodicityTime"], $copiedStreaks[$i]["periodicityTime"]);
            $this->assertEquals($streak["periodicityType"], $copiedStreaks[$i]["periodicityType"]);
            $this->assertEquals($streak["reward"], $copiedStreaks[$i]["reward"]);
            $this->assertEquals($streak["tokens"], $copiedStreaks[$i]["tokens"]);
            $this->assertEquals($streak["isExtra"], $copiedStreaks[$i]["isExtra"]);
            $this->assertEquals($streak["isRepeatable"], $copiedStreaks[$i]["isRepeatable"]);
            $this->assertEquals($streak["isActive"], $copiedStreaks[$i]["isActive"]);

            $this->assertEquals($streak["image"], $copiedStreaks[$i]["image"]);

            $this->assertEquals((new Rule($streak["rule"]))->getText(), (new Rule($copiedStreaks[$i]["rule"]))->getText());
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Streaks::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
        $this->assertNull(Section::getSectionByName($this->course->getId(), $this->module::RULE_SECTION));
    }


    // Config

    /**
     * @test
     */
    public function getMaxXP()
    {
        $this->assertNull($this->module->getMaxXP());
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateMaxXP()
    {
        $this->module->updateMaxXP(1000);
        $this->assertEquals(1000, $this->module->getMaxXP());

        $this->module->updateMaxXP(null);
        $this->assertNull($this->module->getMaxXP());
    }

    /**
     * @test
     */
    public function getMaxExtraCredit()
    {
        $this->assertNull($this->module->getMaxExtraCredit());
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateMaxExtraCredit()
    {
        $this->module->updateMaxExtraCredit(1000);
        $this->assertEquals(1000, $this->module->getMaxExtraCredit());

        $this->module->updateMaxExtraCredit(null);
        $this->assertNull($this->module->getMaxExtraCredit());
    }


    // Streaks

    /**
     * @test
     * @throws Exception
     */
    public function getUsersWithStreak()
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

        $streak1 = Streak::addStreak($this->course->getId(), "Streak1", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streak2 = Streak::addStreak($this->course->getId(), "Streak2", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streak3 = Streak::addStreak($this->course->getId(), "Streak3", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, true);

        $this->insertAward($this->course->getId(), $user1->getId(), $streak1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $streak1->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user2->getId(), $streak1->getId(), "Award 3", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $streak2->getId(), "Award 4", 100);
        $this->insertAward($this->course->getId(), $user3->getId(), $streak2->getId(), "Award 5", 100);

        // Has streak
        $this->assertCount(2, $this->module->getUsersWithStreak($streak1->getId()));
        $this->assertCount(2, $this->module->getUsersWithStreak($streak2->getId()));

        // Doesn't have streak
        $this->assertEmpty($this->module->getUsersWithStreak($streak3->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreaks()
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

        $streak1 = Streak::addStreak($this->course->getId(), "Streak1", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streak2 = Streak::addStreak($this->course->getId(), "Streak2", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $this->insertAward($this->course->getId(), $user1->getId(), $streak1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user1->getId(),$streak1->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $streak2->getId(), "Award 3", 500);
        $this->insertAward($this->course->getId(), $user3->getId(), $streak2->getId(), "Award 4", 500);

        $keys = ["id", "course", "name", "description", "color", "goal", "periodicityGoal", "periodicityNumber",
            "periodicityTime", "periodicityType", "reward", "tokens", "isExtra", "isRepeatable", "isActive", "rule", "nrCompletions", "progress"];
        $nrKeys = count($keys);

        // Has streaks
        $streaks = $this->module->getUserStreaks($user1->getId());
        $this->assertIsArray($streaks);
        $this->assertCount(2, $streaks);
        foreach ($keys as $key) {
            foreach ($streaks as $i => $streak) {
                $this->assertCount($nrKeys, array_keys($streak));
                $this->assertArrayHasKey($key, $streak);
                if ($key == "nrCompletions") $this->assertEquals($i == 0 ? 2 : 1, $streak[$key]);
                else if ($key == "progress") $this->assertEquals(null, $streak[$key]); // FIXME
                else $this->assertEquals($streak[$key], ${"streak".($i+1)}->getData($key));
            }
        }

        // Doesn't have streaks
        $this->assertEmpty($this->module->getUserStreaks($user2->getId()));
    }

    // TODO: getUserStreakProgression

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreakCompletions()
    {
        // Given
        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student");

        $streak1 = Streak::addStreak($this->course->getId(), "Streak1", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);
        $streak2 = Streak::addStreak($this->course->getId(), "Streak2", "Perform action", null,
            10, null, null, null, null, 100, 0,
            false, false);

        $this->insertAward($this->course->getId(), $user1->getId(), $streak1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $streak1->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $streak2->getId(), "Award 3", 500);

        // Then
        $this->assertEquals(2, $this->module->getUserStreakCompletions($user1->getId(), $streak1->getId()));
        $this->assertEquals(1, $this->module->getUserStreakCompletions($user1->getId(), $streak2->getId()));
        $this->assertEquals(0, $this->module->getUserStreakCompletions($user2->getId(), $streak1->getId()));
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private function insertAward(int $courseId, int $userId, int $streakId, string $description, int $reward)
    {
        Core::database()->insert(Awards::TABLE_AWARD, [
            "user" => $userId,
            "course" => $courseId,
            "description" => $description,
            "type" => AwardType::STREAK,
            "moduleInstance" => $streakId,
            "reward" => $reward
        ]);
    }

    private function insertProgression(int $courseId, int $userId, int $streakId, int $number, string $description)
    {
        $id = Core::database()->insert(AutoGame::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $courseId,
            "source" => $this->module->getId(),
            "description" => $description,
            "type" => AwardType::STREAK
        ]);

        Core::database()->insert(Streak::TABLE_STREAK_PROGRESSION, [
            "course" => $courseId,
            "user" => $userId,
            "streak" => $streakId,
            "number" => $number,
            "participation" => $id
        ]);
    }
}
