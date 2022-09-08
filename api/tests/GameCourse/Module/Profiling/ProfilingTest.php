<?php
namespace GameCourse\Module\Profiling;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\Role\Role;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class ProfilingTest extends TestCase
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

        // Set roles
        $course->setRolesHierarchy([
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA"],
                ["name" => "StudentB"]
            ]],
            ["name" => "Watcher"],
        ]);
        $course->addRole("StudentA");
        $course->addRole("StudentB");

        // Set students
        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        $this->course->addUserToCourse($user1->getId(), "Student");
        $this->course->addUserToCourse($user2->getId(), "Student", null, false);

        // Enable Profiling module
        (new Awards($course))->setEnabled(true);
        (new XPLevels($course))->setEnabled(true);
        (new Badges($course))->setEnabled(true);
        (new Skills($course))->setEnabled(true);

        $profiling = new Profiling($course);
        $profiling->setEnabled(true);
        $this->module = $profiling;
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Profiling::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }

        $this->assertEquals([
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA"],
                ["name" => "StudentB"],
                ["name" => $this->module::PROFILING_ROLE]
            ]],
            ["name" => "Watcher"],
        ], $this->course->getRolesHierarchy());
        $roles = $this->course->getRoles();
        $this->assertCount(6, $roles);
        $this->assertContains($this->module::PROFILING_ROLE, $roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function disable()
    {
        // Given
        $this->course->setRolesHierarchy([
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA"],
                ["name" => "StudentB"],
                ["name" => $this->module::PROFILING_ROLE, "children" => [
                    ["name" => "Achiever"],
                    ["name" => "Regular", "children" => [
                        ["name" => "RegularA"],
                        ["name" => "RegularB"],
                    ]],
                    ["name" => "Medium"],
                    ["name" => "Halfhearted"],
                    ["name" => "Underachiever"]
                ]]
            ]],
            ["name" => "Watcher"],
        ]);
        $this->course->addRole("Achiever", null, null, $this->module->getId());
        $this->course->addRole("Regular", null, null, $this->module->getId());
        $this->course->addRole("RegularA", null, null, $this->module->getId());
        $this->course->addRole("RegularB", null, null, $this->module->getId());
        $this->course->addRole("Medium", null, null, $this->module->getId());
        $this->course->addRole("Halfhearted", null, null, $this->module->getId());
        $this->course->addRole("Underachiever", null, null, $this->module->getId());
        file_put_contents($this->module->getPredictorLogsPath(), "");
        file_put_contents($this->module->getProfilerLogsPath(), "");

        // When
        $this->module->setEnabled(false);

        // Then
        $sql = file_get_contents(MODULES_FOLDER . "/" . Profiling::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }

        $this->assertEquals([
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA"],
                ["name" => "StudentB"]
            ]],
            ["name" => "Watcher"],
        ], $this->course->getRolesHierarchy());
        $roles = $this->course->getRoles();
        $this->assertCount(5, $roles);
        $this->assertNotContains($this->module::PROFILING_ROLE, $roles);
        $this->assertNotContains("Achiever", $roles);
        $this->assertNotContains("Regular", $roles);
        $this->assertNotContains("RegularA", $roles);
        $this->assertNotContains("RegularB", $roles);
        $this->assertNotContains("Halfhearted", $roles);
        $this->assertNotContains("Underachiever", $roles);
        $this->assertNotContains("Medium", $roles);

        $this->assertFalse(file_exists($this->module->getPredictorLogsPath()));
        $this->assertFalse(file_exists($this->module->getProfilerLogsPath()));
    }


    // TODO
}
