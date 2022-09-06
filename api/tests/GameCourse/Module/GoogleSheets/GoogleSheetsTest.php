<?php
namespace GameCourse\Module\GoogleSheets;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class GoogleSheetsTest extends TestCase
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

        // Enable GoogleSheets module
        $googleSheets = new GoogleSheets($course);
        $googleSheets->setEnabled(true);
        $this->module = $googleSheets;
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . GoogleSheets::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }

        $config = Core::database()->select(GoogleSheets::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->course->getId()]);
        unset($config["course"]);
        foreach ($config as $value) {
            $this->assertNull($value);
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . GoogleSheets::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
    }


    // Config

    /**
     * @test
     */
    public function getGoogleSheetsConfig()
    {
        $config = $this->module->getGoogleSheetsConfig();
        $this->assertNull($config["spreadsheetId"]);
        $this->assertIsArray($config["sheetNames"]);
        $this->assertEmpty($config["sheetNames"]);
        $this->assertIsArray($config["ownerNames"]);
        $this->assertEmpty($config["ownerNames"]);
    }


    // Status

    /**
     * @test
     * @throws Exception
     */
    public function getStartedRunning()
    {
        $this->assertNull($this->module->getStartedRunning());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setStartedRunning()
    {
        $datetime = "2022-09-01 19:59:00";
        $this->module->setStartedRunning($datetime);
        $this->assertEquals($datetime, $this->module->getStartedRunning());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getFinishedRunning()
    {
        $this->assertNull($this->module->getFinishedRunning());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setFinishedRunning()
    {
        $datetime = "2022-09-01 19:59:00";
        $this->module->setFinishedRunning($datetime);
        $this->assertEquals($datetime, $this->module->getFinishedRunning());
    }


    /**
     * @test
     * @throws Exception
     */
    public function isRunning()
    {
        $this->module->setIsRunning(true);
        $this->assertTrue($this->module->isRunning());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotRunning()
    {
        $this->assertFalse($this->module->isRunning());
    }

    public function setIsRunning()
    {
        $this->module->setIsRunning(true);
        $this->assertTrue($this->module->isRunning());
    }


    // Importing data

    /**
     * @test
     * @throws Exception
     */
    public function saveSheetData()
    {
        // Given
        $students = $this->course->getStudents();
        $student1 = $this->course->getCourseUserById($students[0]["id"]);
        $student2 = $this->course->getCourseUserById($students[1]["id"]);
        $teacher = $this->course->getCourseUserById($this->course->getTeachers()[0]["id"]);

        $data = [
            [strval($student1->getStudentNumber()), $student1->getName(), "A", "initial bonus", "500", ""],
            [strval($student2->getStudentNumber()), $student2->getName(), "T", "replied to questionnaires", "", "2"],
        ];

        // When
        $newData = $this->module->saveSheetData($data, $teacher->getId());

        // Then
        $this->assertTrue($newData);
        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertCount(2, $participations);

        $p1 = $participations[0];
        $this->assertEquals(1, $p1["id"]);
        $this->assertEquals((new User($student1->getId()))->getData(), $p1["user"]);
        $this->assertEquals($this->course->getId(), $p1["course"]);
        $this->assertEquals($this->module->getId(), $p1["source"]);
        $this->assertEquals("", $p1["description"]);
        $this->assertEquals("initial bonus", $p1["type"]);
        $this->assertNull($p1["post"]);
        $this->assertNotNull($p1["date"]);
        $this->assertEquals(500, $p1["rating"]);
        $this->assertEquals($teacher->getId(), $p1["evaluator"]);

        $p2 = $participations[1];
        $this->assertEquals(2, $p2["id"]);
        $this->assertEquals((new User($student2->getId()))->getData(), $p2["user"]);
        $this->assertEquals($this->course->getId(), $p2["course"]);
        $this->assertEquals($this->module->getId(), $p2["source"]);
        $this->assertEquals("2", $p2["description"]);
        $this->assertEquals("replied to questionnaires", $p2["type"]);
        $this->assertNull($p2["post"]);
        $this->assertNotNull($p2["date"]);
        $this->assertEquals(0, $p2["rating"]);
        $this->assertEquals($teacher->getId(), $p2["evaluator"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function saveSheetDataUpdateData()
    {
        // Given
        $students = $this->course->getStudents();
        $student1 = $this->course->getCourseUserById($students[0]["id"]);
        $student2 = $this->course->getCourseUserById($students[1]["id"]);
        $teacher = $this->course->getCourseUserById($this->course->getTeachers()[0]["id"]);
        AutoGame::addParticipation($this->course->getId(), $student1->getId(), "", "initial bonus", $this->module->getId(), null, null, 1000, $teacher->getId());

        $data = [
            [strval($student1->getStudentNumber()), $student1->getName(), "A", "initial bonus", "500", ""],
            [strval($student2->getStudentNumber()), $student2->getName(), "T", "replied to questionnaires", "", "2"],
        ];

        // When
        $newData = $this->module->saveSheetData($data, $teacher->getId());

        // Then
        $this->assertTrue($newData);
        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertCount(2, $participations);

        $p1 = $participations[0];
        $this->assertEquals(1, $p1["id"]);
        $this->assertEquals((new User($student1->getId()))->getData(), $p1["user"]);
        $this->assertEquals($this->course->getId(), $p1["course"]);
        $this->assertEquals($this->module->getId(), $p1["source"]);
        $this->assertEquals("", $p1["description"]);
        $this->assertEquals("initial bonus", $p1["type"]);
        $this->assertNull($p1["post"]);
        $this->assertNotNull($p1["date"]);
        $this->assertEquals(500, $p1["rating"]);
        $this->assertEquals($teacher->getId(), $p1["evaluator"]);

        $p2 = $participations[1];
        $this->assertEquals(2, $p2["id"]);
        $this->assertEquals((new User($student2->getId()))->getData(), $p2["user"]);
        $this->assertEquals($this->course->getId(), $p2["course"]);
        $this->assertEquals($this->module->getId(), $p2["source"]);
        $this->assertEquals("2", $p2["description"]);
        $this->assertEquals("replied to questionnaires", $p2["type"]);
        $this->assertNull($p2["post"]);
        $this->assertNotNull($p2["date"]);
        $this->assertEquals(0, $p2["rating"]);
        $this->assertEquals($teacher->getId(), $p2["evaluator"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function saveSheetDataNoData()
    {
        // Given
        $students = $this->course->getStudents();
        $student1 = $this->course->getCourseUserById($students[0]["id"]);
        $student2 = $this->course->getCourseUserById($students[1]["id"]);
        $teacher = $this->course->getCourseUserById($this->course->getTeachers()[0]["id"]);

        $data = [
            ["", "", "", "", "", "", "attended lab", "", "num"]
        ];

        // When
        $newData = $this->module->saveSheetData($data, $teacher->getId());

        // Then
        $this->assertFalse($newData);
        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertEmpty($participations);
    }
}
