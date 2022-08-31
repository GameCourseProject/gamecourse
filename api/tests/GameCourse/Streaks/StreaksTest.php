<?php
namespace GameCourse\Streaks;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Awards\AwardType;
use GameCourse\Module\Streaks\Streak;
use GameCourse\Module\Streaks\Streaks;
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

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["modules"], ["CronJob"]);
    }

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
        preg_match_all("/CREATE TABLE IF NOT EXISTS (.*)\(/i", $sql, $matches);
        $tables = $matches[1];
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
    public function disable()
    {
        // When
        $this->module->setEnabled(false);

        // Then
        $sql = file_get_contents(MODULES_FOLDER . "/" . Streaks::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE IF NOT EXISTS (.*)\(/i", $sql, $matches);
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
        // XP & Levels not enabled
        $this->module->updateMaxExtraCredit(1000);
        $this->assertEquals(1000, $this->module->getMaxExtraCredit());

        // XP & Levels enabled; smaller value
        $xpLevels = new XPLevels($this->course);
        $xpLevels->setEnabled(true);
        try {
            $this->module->updateMaxExtraCredit(2000);
        } catch (Exception $e) {
            $this->assertEquals(0, $this->module->getMaxExtraCredit());
        }

        // XP & Levels enabled; bigger value
        $xpLevels->updateMaxExtraCredit(2000);
        $this->module->updateMaxExtraCredit(1000);
        $this->assertEquals(1000, $this->module->getMaxExtraCredit());
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
            10, null, null, 100, null, false, true,
            false, false, false);
        $streak2 = Streak::addStreak($this->course->getId(), "Streak2", "Perform action", null,
            10, null, null, 100, null, false, true,
            false, false, false);
        $streak3 = Streak::addStreak($this->course->getId(), "Streak3", "Perform action", null,
            10, null, null, 100, null, false, true,
            false, false, false);

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
            10, null, null, 100, null, false, true,
            false, false, false);
        $streak2 = Streak::addStreak($this->course->getId(), "Streak2", "Perform action", null,
            10, null, null, 100, null, false, true,
            false, false, false);

        $this->insertAward($this->course->getId(), $user1->getId(), $streak1->getId(), "Award 1", 100);
        $this->insertAward($this->course->getId(), $user1->getId(),$streak1->getId(), "Award 2", 100);
        $this->insertAward($this->course->getId(), $user1->getId(), $streak2->getId(), "Award 3", 500);
        $this->insertAward($this->course->getId(), $user3->getId(), $streak2->getId(), "Award 4", 500);

        $keys = ["id", "course", "name", "description", "color", "count", "periodicity", "periodicityTime", "reward", "tokens",
            "isRepeatable", "isCount", "isPeriodic", "isAtMost", "isExtra", "isActive", "rule", "nrCompletions"];
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
                else $this->assertEquals($streak[$key], ${"streak".($i+1)}->getData($key));
            }
        }

        // Doesn't have streaks
        $this->assertEmpty($this->module->getUserStreaks($user2->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserStreakProgression()
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
            5, null, null, 100, null, false, true,
            false, false, false);
        $streak2 = Streak::addStreak($this->course->getId(), "Streak2", "Perform action", null,
            2, null, null, 100, null, false, true,
            false, false, false);

        $this->insertProgression($this->course->getId(), $user1->getId(), $streak1->getId(), "Participation 1");
        $this->insertProgression($this->course->getId(), $user1->getId(), $streak1->getId(), "Participation 2");
        $this->insertProgression($this->course->getId(), $user1->getId(), $streak1->getId(), "Participation 3");
        $this->insertProgression($this->course->getId(), $user1->getId(), $streak2->getId(), "Participation 4");
        $this->insertProgression($this->course->getId(), $user1->getId(), $streak2->getId(), "Participation 5");
        $this->insertProgression($this->course->getId(), $user1->getId(), $streak2->getId(), "Participation 6");
        $this->insertProgression($this->course->getId(), $user2->getId(), $streak2->getId(), "Participation 7");
        $this->insertProgression($this->course->getId(), $user2->getId(), $streak2->getId(), "Participation 8");

        // Then
        $this->assertEquals(3, $this->module->getUserStreakProgression($user1->getId(), $streak1->getId()));
        $this->assertEquals(1, $this->module->getUserStreakProgression($user1->getId(), $streak2->getId()));
        $this->assertEquals(0, $this->module->getUserStreakProgression($user2->getId(), $streak2->getId()));
        $this->assertEquals(0, $this->module->getUserStreakProgression($user3->getId(), $streak1->getId()));
    }

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
            10, null, null, 100, null, false, true,
            false, false, false);
        $streak2 = Streak::addStreak($this->course->getId(), "Streak2", "Perform action", null,
            10, null, null, 100, null, false, true,
            false, false, false);

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

    private function insertProgression(int $courseId, int $userId, int $streakId, string $description)
    {
        $id = Core::database()->insert(AutoGame::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $courseId,
            "description" => $description,
            "type" => AwardType::STREAK
        ]);

        Core::database()->insert(Streak::TABLE_STREAK_PROGRESSION, [
            "course" => $courseId,
            "user" => $userId,
            "streak" => $streakId,
            "participation" => $id
        ]);
    }
}
