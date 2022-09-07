<?php
namespace GameCourse\Module\Notifications;

use Exception;
use GameCourse\AutoGame\AutoGame;
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
class NotificationsTest extends TestCase
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

        // Set students
        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student", null, false);

        // Enable Notifications module
        $awards = new Awards($course);
        $awards->setEnabled(true);

        $xpLevels = new XPLevels($course);
        $xpLevels->setEnabled(true);

        $notifications = new Notifications($course);
        $notifications->setEnabled(true);
        $this->module = $notifications;
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, AutoGame::TABLE_PARTICIPATION]);
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Notifications::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }

        $config = $this->module->getProgressReportConfig();
        unset($config["course"]);
        foreach ($config as $value) {
            if (is_bool($value)) $this->assertFalse($value);
            else $this->assertNull($value);
        }

        $this->assertTrue(file_exists($this->module->getLogsPath()));
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Notifications::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
        $this->assertFalse(file_exists($this->module->getLogsPath()));
    }


    // Progress Report

    /**
     * @test
     */
    public function getProgressReportConfig()
    {
        $this->module->saveProgressReportConfig("2022-09-07 01:42:00", "Weekly", 18, 5, true);
        $config = $this->module->getProgressReportConfig();
        $this->assertEquals("2022-09-07 01:42:00", $config["endDate"]);
        $this->assertEquals("Weekly", $config["periodicityTime"]);
        $this->assertEquals(18, $config["periodicityHours"]);
        $this->assertEquals(5, $config["periodicityDay"]);
        $this->assertTrue($config["isEnabled"]);
    }

    /**
     * @test
     */
    public function saveProgressReportConfig()
    {
        $this->module->saveProgressReportConfig("2022-09-07 01:42:00", "Weekly", 18, 5, false);
        $config = $this->module->getProgressReportConfig();
        $this->assertEquals("2022-09-07 01:42:00", $config["endDate"]);
        $this->assertEquals("Weekly", $config["periodicityTime"]);
        $this->assertEquals(18, $config["periodicityHours"]);
        $this->assertEquals(5, $config["periodicityDay"]);
        $this->assertFalse($config["isEnabled"]);
    }


    public function getUserProgressReport()
    {
        // Given
        $student = $this->course->getCourseUserById($this->course->getStudents()[0]["id"]);

        $this->insertAward($this->course->getId(), $student->getId(), AwardType::BONUS, "Initial Bonus", null, 500);
        $this->insertAward($this->course->getId(), $student->getId(), AwardType::PRESENTATION, "Presentation Grade", null, 2500);

        // When
        $report = $this->module->getUserProgressReport($student->getId(), 0);

        // TODO
    }

    public function getUserProgressReportNoAwards()
    {
        // TODO
    }

    public function getUserProgressReportNoNickname()
    {
        // TODO
    }

    public function getUserProgressReportVirtualCurrencyDisabled()
    {
        // TODO
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private function insertAward(int $courseId, int $userId, string $type, string $description, ?int $moduleInstance, int $reward)
    {
        Core::database()->insert(Awards::TABLE_AWARD, [
            "user" => $userId,
            "course" => $courseId,
            "description" => $description,
            "type" => $type,
            "moduleInstance" => $moduleInstance,
            "reward" => $reward
        ]);
    }
}
