<?php
namespace GameCourse\AutoGame;

use Exception;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class AutoGameTest extends TestCase
{
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

    protected function setUp(): void
    {
        Core::database()->setForeignKeyChecks(false);
        Core::database()->insert(AutoGame::TABLE_AUTOGAME, ["course" => 0, "frequency" => null]);
        Core::database()->setForeignKeyChecks(true);
    }

    /**
     * @throws Exception
     */
    public function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([AutoGame::TABLE_AUTOGAME, Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([AutoGame::TABLE_AUTOGAME, AutoGame::TABLE_PARTICIPATION,  Course::TABLE_COURSE, User::TABLE_USER, Role::TABLE_ROLE]);
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
     */
    public function autoGameEntryExists() {
        $entries = Core::database()->selectMultiple(AutoGame::TABLE_AUTOGAME);
        $this->assertCount(1, $entries);
        $autogame = $entries[0];
        $this->assertEquals(0, $autogame["course"]);
        $this->assertNull($autogame["frequency"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function initAutoGame()
    {
        $courseId = 1;

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId);
        Core::database()->setForeignKeyChecks(true);

        $courseAutoGame = Core::database()->select(AutoGame::TABLE_AUTOGAME, ["course" => $courseId]);
        $this->assertFalse(boolval($courseAutoGame["isRunning"]));
        $this->assertEquals("*/10 * * * *", $courseAutoGame["frequency"]);

        $this->assertTrue(file_exists(RuleSystem::getDataFolder($courseId)));
        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/imported-functions/" . $courseId));
        $this->assertEquals(file_get_contents(AUTOGAME_FOLDER . "/imported-functions/defaults.py"), file_get_contents(AUTOGAME_FOLDER . "/imported-functions/" . $courseId . "/defaults.py"));
        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt"));
        $this->assertEquals("", file_get_contents(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt"));

        $this->assertTrue(file_exists(LOGS_FOLDER . "/" . AutoGame::LOGS_FOLDER . "/autogame_" . $courseId . ".txt"));
        $this->assertEquals("", file_get_contents(LOGS_FOLDER . "/" . AutoGame::LOGS_FOLDER . "/autogame_" . $courseId . ".txt"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function copyAutoGameInfo()
    {
        $courseId1 = 1;
        $courseId2 = 2;

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId1);
        Core::database()->setForeignKeyChecks(true);

        file_put_contents(AUTOGAME_FOLDER . "/imported-functions/" . $courseId1 . "/file.txt", "SOME TEXT");
        file_put_contents(AUTOGAME_FOLDER . "/config/config_" . $courseId1 . ".txt", "SOME TEXT");

        AutoGame::copyAutoGameInfo($courseId2, $courseId1);

        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/imported-functions/" . $courseId2));
        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/imported-functions/" . $courseId2 . "/file.txt"));
        $this->assertEquals(
            file_get_contents(AUTOGAME_FOLDER . "/imported-functions/" . $courseId1 . "/file.txt"),
            file_get_contents(AUTOGAME_FOLDER . "/imported-functions/" . $courseId2 . "/file.txt")
        );

        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/config/config_" . $courseId2 . ".txt"));
        $this->assertEquals(
            file_get_contents(AUTOGAME_FOLDER . "/config/config_" . $courseId1 . ".txt"),
            file_get_contents(AUTOGAME_FOLDER . "/config/config_" . $courseId2 . ".txt")
        );
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteAutoGameInfo()
    {
        $courseId = 1;

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId);
        Core::database()->setForeignKeyChecks(true);

        AutoGame::deleteAutoGameInfo($courseId);

        $this->assertFalse(file_exists(AUTOGAME_FOLDER . "/imported-functions/" . $courseId));
        $this->assertFalse(file_exists(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt"));
        $this->assertFalse(file_exists(LOGS_FOLDER . "/" . AutoGame::LOGS_FOLDER . "/autogame_" . $courseId . ".txt"));
    }


    // Status

    /**
     * @test
     * @throws Exception
     */
    public function enableAutoGame()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);

        // When
        AutoGame::setAutoGame($course->getId(), true);

        // Then
        $this->assertTrue(AutoGame::isEnabled($course->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function disableAutoGame()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);

        // When
        AutoGame::setAutoGame($course->getId(), false);

        // Then
        $this->assertFalse(AutoGame::isEnabled($course->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function enableAutoGameInactiveCourse()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, false, true);

        $this->expectException(Exception::class);
        AutoGame::setAutoGame($course->getId(), true);
    }


    // Running

    /**
     * @test
     * @throws Exception
     */
    public function getLastRun()
    {
        $courseId = 1;

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId);
        Core::database()->setForeignKeyChecks(true);

        $this->assertNull(AutoGame::getLastRun($courseId));

        $date = "2022-07-30 19:20:00";
        Core::database()->update(AutoGame::TABLE_AUTOGAME, ["finishedRunning" => $date], ["course" => $courseId]);
        $this->assertEquals($date, AutoGame::getLastRun($courseId));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setToRun()
    {
        $courseId = 1;

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId);
        Core::database()->setForeignKeyChecks(true);

        $this->assertFalse(boolval(Core::database()->select(AutoGame::TABLE_AUTOGAME, ["course" => $courseId], "runNext")));
        AutoGame::setToRun($courseId, "2023-03-07 19:59:00");
        $this->assertTrue(boolval(Core::database()->select(AutoGame::TABLE_AUTOGAME, ["course" => $courseId], "runNext")));
        $this->assertEquals("2023-03-07 19:59:00", Core::database()->select(AutoGame::TABLE_AUTOGAME, ["course" => $courseId], "checkpoint"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function isRunning()
    {
        $courseId = 1;

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId);
        Core::database()->setForeignKeyChecks(true);

        $this->assertFalse(AutoGame::isRunning($courseId));
        Core::database()->update(AutoGame::TABLE_AUTOGAME, ["isRunning" => true], ["course" => $courseId]);
        $this->assertTrue(AutoGame::isRunning($courseId));
    }

    /**
     * @test
     * @throws Exception
     */
    public function runInexistentCourse()
    {
        $courseId = 1;

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId);
        Core::database()->setForeignKeyChecks(true);

        try {
            AutoGame::run($courseId);
            $this->fail("Exception should have been thrown on 'runInexistentCourse'.");

        } catch (Exception $e) {
            $logsFile = LOGS_FOLDER . "/" . AutoGame::LOGS_FOLDER . "/autogame_$courseId.txt";
            $this->assertNotEmpty(file_get_contents($logsFile));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function runAutoGameAlreadyRunning()
    {
        $courseId = 1;

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId);
        Core::database()->setForeignKeyChecks(true);
        Core::database()->update(AutoGame::TABLE_AUTOGAME, ["isRunning" => true], ["course" => $courseId]);

        try {
            AutoGame::run($courseId);
            $this->fail("Exception should have been thrown on 'runAutoGameAlreadyRunning'.");

        } catch (Exception $e) {
            $logsFile = LOGS_FOLDER . "/" . AutoGame::LOGS_FOLDER . "/autogame_$courseId.txt";
            $this->assertNotEmpty(file_get_contents($logsFile));
        }
    }


    // Logging

    /**
     * @test
     * @throws Exception
     */
    public function getLogs()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $courseId = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true)->getId();

        // Empty
        $this->assertEmpty(AutoGame::getLogs($courseId));

        // Not empty
        AutoGame::log($courseId, "Testing 1");
        AutoGame::log($courseId, "Testing 2", "WARNING");
        $this->assertEquals($this->trim("
================================================================================
[" . date("Y/m/d H:i:s", time()) . "] [ERROR] : Testing 1
================================================================================


================================================================================
[" . date("Y/m/d H:i:s", time()) . "] [WARNING] : Testing 2
================================================================================

"), $this->trim(AutoGame::getLogs($courseId)));
    }

    /**
     * @test
     * @throws Exception
     */
    public function log()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $courseId = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true)->getId();

        AutoGame::log($courseId, "Testing 1");
        AutoGame::log($courseId, "Testing 2", "WARNING");
        $this->assertEquals($this->trim("
================================================================================
[" . date("Y/m/d H:i:s", time()) . "] [ERROR] : Testing 1
================================================================================


================================================================================
[" . date("Y/m/d H:i:s", time()) . "] [WARNING] : Testing 2
================================================================================

"), $this->trim(AutoGame::getLogs($courseId)));
    }


    // Participations

    /**
     * @test
     * @throws Exception
     */
    public function getParticipations()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);

        // Set students
        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student", null, false);

        AutoGame::addParticipation($course->getId(), $user1->getId(), "Participation 1", "testing");
        AutoGame::addParticipation($course->getId(), $user2->getId(), "Participation 2", "testing");

        $participations = AutoGame::getParticipations($course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(2, $participations);

        $keys = ["id", "user", "course", "source", "description", "type", "post", "date", "rating", "evaluator"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($participations as $participation) {
                $this->assertCount($nrKeys, array_keys($participation));
                $this->assertArrayHasKey($key, $participation);
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getParticipationsEmpty()
    {
        $this->assertEmpty(AutoGame::getParticipations(1));
    }

    /**
     * @test
     * @throws Exception
     */
    public function addParticipation()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);

        // Set students
        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student", null, false);

        AutoGame::addParticipation($course->getId(), $user1->getId(), "Participation", "testing", null,
            "2022-09-02 19:23:00", null, 3.4);

        $participations = AutoGame::getParticipations($course->getId());
        $this->assertCount(1, $participations);
        $this->assertEquals(["id" => 1, "user" => $user1->getId(), "course" => $course->getId(), "source" => "GameCourse",
            "description" => "Participation", "type" => "testing", "post" => null, "date" => "2022-09-02 19:23:00", "rating" => 3,
            "evaluator" => null], $participations[0]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateParticipation()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);

        // Set students
        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student", null, false);

        $id = AutoGame::addParticipation($course->getId(), $user1->getId(), "DESCRIPTION", "TYPE", "QR",
            "2022-08-02 19:23:00", "URL", 10, $loggedUser->getId());
        AutoGame::updateParticipation($id, "Participation", "testing", "2022-09-02 19:23:00", null,
            null, 3.4);

        $participations = AutoGame::getParticipations($course->getId());
        $this->assertCount(1, $participations);
        $this->assertEquals(["id" => 1, "user" => $user1->getId(), "course" => $course->getId(), "source" => "GameCourse",
            "description" => "Participation", "type" => "testing", "post" => null, "date" => "2022-09-02 19:23:00", "rating" => 3,
            "evaluator" => null], $participations[0]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeParticipation()
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);

        // Set students
        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student", null, false);

        $id = AutoGame::addParticipation($course->getId(), $user1->getId(), "Participation", "testing", null,
            "2022-09-02 19:23:00", null, 3.4);
        AutoGame::removeParticipation($id);

        $this->assertEmpty(AutoGame::getParticipations($course->getId()));
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private function trim(string $str)
    {
        return str_replace("\r", "", $str);
    }
}
