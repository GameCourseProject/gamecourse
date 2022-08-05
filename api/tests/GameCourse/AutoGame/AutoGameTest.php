<?php
namespace GameCourse\AutoGame;

use Exception;
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

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["modules"], ["CronJob"]);
    }

    protected function setUp(): void
    {
        Core::database()->setForeignKeyChecks(false);
        Core::database()->insert(AutoGame::TABLE_AUTOGAME, [
           "course" => 0,
           "periodicityNumber" => null,
           "periodicityTime" => null
        ]);
        Core::database()->setForeignKeyChecks(true);
    }

    public function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([AutoGame::TABLE_AUTOGAME, Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([AutoGame::TABLE_AUTOGAME, Course::TABLE_COURSE, User::TABLE_USER, Role::TABLE_ROLE]);
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

    /**
     * @test
     */
    public function autoGameEntryExists() {
        $entries = Core::database()->selectMultiple(AutoGame::TABLE_AUTOGAME);
        $this->assertCount(1, $entries);
        $autogame = $entries[0];
        $this->assertEquals(0, $autogame["course"]);
        $this->assertNull($autogame["periodicityNumber"]);
        $this->assertNull($autogame["periodicityTime"]);
    }

    /**
     * @test
     */
    public function initAutoGame()
    {
        $courseId = 1;
        $dataFolder = (new Course($courseId))->getDataFolder();

        Core::database()->setForeignKeyChecks(false);
        AutoGame::initAutoGame($courseId);
        Core::database()->setForeignKeyChecks(true);

        $courseAutoGame = Core::database()->select(AutoGame::TABLE_AUTOGAME, ["course" => $courseId]);
        $this->assertFalse(boolval($courseAutoGame["isRunning"]));
        $this->assertEquals(10, $courseAutoGame["periodicityNumber"]);
        $this->assertEquals("Minutes", $courseAutoGame["periodicityTime"]);

        $this->assertTrue(file_exists(RuleSystem::getDataFolder($courseId)));
        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/imported-functions/" . $courseId));
        $this->assertEquals(file_get_contents(AUTOGAME_FOLDER . "/imported-functions/defaults.py"), file_get_contents(AUTOGAME_FOLDER . "/imported-functions/" . $courseId . "/defaults.py"));
        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt"));
        $this->assertEquals("", file_get_contents(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt"));
        $this->assertTrue(file_exists(LOGS_FOLDER . "/autogame_" . $courseId . ".txt"));
        $this->assertEquals("", file_get_contents(LOGS_FOLDER . "/autogame_" . $courseId . ".txt"));
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
        $this->assertFalse(file_exists(LOGS_FOLDER . "/autogame_" . $courseId . ".txt"));
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
}
