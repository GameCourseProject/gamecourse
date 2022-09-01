<?php
namespace GameCourse\Module\XPLevels;

use Event\Event;
use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Badges\Badges;
use GameCourse\Role\Role;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class XPLevelsTest extends TestCase
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

        // Enable XP & Levels module
        (new Awards($course))->setEnabled(true);
        $xpLevels = new XPLevels($course);
        $xpLevels->setEnabled(true);
        $this->module = $xpLevels;
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Role::TABLE_ROLE]);
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . XPLevels::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }
        $this->assertEquals(0, $this->module->getMaxExtraCredit());

        $levels = Level::getLevels($this->course->getId());
        $this->assertIsArray($levels);
        $this->assertCount(1, $levels);
        $this->assertEquals(0, $levels[0]["minXP"]);

        $students = $this->course->getStudents();
        foreach ($students as $student) {
            $this->assertEquals(0, $this->module->getUserXP($student["id"]));
        }

        $this->assertNotEmpty(Event::getEvents());
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . XPLevels::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
        $this->assertEmpty(Event::getEvents());
    }


    // Events

    /**
     * @test
     * @throws Exception
     */
    public function studentAddedEvent()
    {
        // Given
        $activeStudent = User::addUser("Martha", "martha", AuthService::FENIX, null, 1,
            null, null, false, true);
        $inactiveStudent = User::addUser("Paul", "paul", AuthService::FENIX, null, 2,
            null, null, false, true);

        // When
        $this->course->addUserToCourse($activeStudent->getId(), "Student");
        $this->course->addUserToCourse($inactiveStudent->getId(), "Student", null, false);

        // Then
        $this->assertEquals(0, $this->module->getUserXP($activeStudent->getId()));
        $this->assertEquals(0, $this->module->getUserXP($inactiveStudent->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function studentRemovedEvent()
    {
        // Given
        $student = CourseUser::getCourseUserById($this->course->getStudents()[0]["id"], $this->course);

        // When
        $this->course->removeUserFromCourse($student->getId());

        // Then
        $this->assertFalse($this->module->userHasXP($student->getId()));
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
        // No module enabled
        $this->module->updateMaxExtraCredit(1000);
        $this->assertEquals(1000, $this->module->getMaxExtraCredit());

        // Module enabled
        $badgesModule = new Badges($this->course);
        $badgesModule->setEnabled(true);
        $badgesModule->updateMaxExtraCredit(800);
        $this->module->updateMaxExtraCredit(500);
        $this->assertEquals(500, $this->module->getMaxExtraCredit());
        $this->assertEquals(500, $badgesModule->getMaxExtraCredit());
    }


    // XP

    /**
     * @test
     * @throws Exception
     */
    public function getUserXP()
    {
        // Given
        $students = $this->course->getStudents();
        $student1 = $this->course->getCourseUserById($students[0]["id"]);
        $student2 = $this->course->getCourseUserById($students[1]["id"]);
        $this->module->setUserXP($student2->getId(), 1000);

        // Then
        $this->assertEquals(0, $this->module->getUserXP($student1->getId()));
        $this->assertEquals(1000, $this->module->getUserXP($student2->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setUserXP()
    {
        // Given
        $student = $this->course->getCourseUserById($this->course->getStudents()[0]["id"]);

        // XP initialized
        $this->module->setUserXP($student->getId(), 1000);
        $this->assertEquals(1000, $this->module->getUserXP($student->getId()));

        // XP not initialized
        Core::database()->delete(XPLevels::TABLE_XP, ["user" => $student->getId()]);
        try {
            $this->module->setUserXP($student->getId(), 2000);
        } catch (Exception $e) {
            $this->assertFalse($this->module->userHasXP($student->getId()));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateUserXP()
    {
        // Given
        $student = $this->course->getCourseUserById($this->course->getStudents()[0]["id"]);

        // Add XP
        $this->module->updateUserXP($student->getId(), 1000);
        $this->assertEquals(1000, $this->module->getUserXP($student->getId()));

        // Subtract XP
        $this->module->updateUserXP($student->getId(), -500);
        $this->assertEquals(500, $this->module->getUserXP($student->getId()));

        // Do nothing
        $this->module->updateUserXP($student->getId(), 0);
        $this->assertEquals(500, $this->module->getUserXP($student->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function userHasXP()
    {
        // Given
        $student = $this->course->getCourseUserById($this->course->getStudents()[0]["id"]);

        // Has XP
        $this->assertTrue($this->module->userHasXP($student->getId()));

        // Doesn't have XP
        Core::database()->delete(XPLevels::TABLE_XP, ["user" => $student->getId()]);
        $this->assertFalse($this->module->userHasXP($student->getId()));
    }
}
