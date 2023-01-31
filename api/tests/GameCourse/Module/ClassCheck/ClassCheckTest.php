<?php
namespace GameCourse\Module\ClassCheck;

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
class ClassCheckTest extends TestCase
{
    private $course;
    private $module;

    private static $TSV_CODE = "https://classcheck.pcm.rnl.tecnico.ulisboa.pt/tsv/course?s=ab18f71e36ee57380e45f7a8268f98ae";

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

        // Enable ClassCheck module
        $classCheck = new ClassCheck($course);
        $classCheck->setEnabled(true);
        $this->module = $classCheck;
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . ClassCheck::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }
        $this->assertNull($this->module->getTSVCode());
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

        $classcheckModule = new ClassCheck($copyTo);
        $classcheckModule->setEnabled(true);

        $this->module->saveSchedule("* * * * *");

        // When
        $this->module->copyTo($copyTo);

        // Then
        $this->assertEquals($this->module->getSchedule(), $classcheckModule->getSchedule());
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . ClassCheck::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
    }


    // Config

    /**
     * @test
     * @throws Exception
     */
    public function getTSVCode()
    {
        $this->assertNull($this->module->getTSVCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function saveTSVCode()
    {
        $this->module->saveTSVCode(self::$TSV_CODE);
        $this->assertEquals(self::$TSV_CODE, $this->module->getTSVCode());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getSchedule()
    {
        $schedule = $this->module->getSchedule();
        $this->assertEquals("*/10 * * * *", $schedule);
    }

    /**
     * @test
     * @throws Exception
     */
    public function saveSchedule()
    {
        $this->module->saveSchedule("0 */5 * * *");
        $schedule = $this->module->getSchedule();
        $this->assertEquals("0 */5 * * *", $schedule);
    }


    // Status

    /**
     * @test
     * @throws Exception
     */
    public function isAutoImporting()
    {
        $this->module->setAutoImporting(true);
        $this->assertTrue($this->module->isAutoImporting());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotAutoImporting()
    {
        $this->module->setAutoImporting(false);
        $this->assertFalse($this->module->isAutoImporting());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setAutoImporting()
    {
        $this->module->setAutoImporting(true);
        $this->assertTrue($this->module->isAutoImporting());
    }


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

    /**
     * @test
     * @throws Exception
     */
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
    public function importData()
    {
        // Given
        $students = $this->course->getStudents();
        $student1 = $this->course->getCourseUserById($students[0]["id"]);
        $student2 = $this->course->getCourseUserById($students[1]["id"]);
        $teacher = $this->course->getCourseUserById($this->course->getTeachers()[0]["id"]);

        $file = $teacher->getUsername() . "	-	" . $student1->getUsername() . "	" . $student1->getName() . "	attended lecture		1	PCM2646T01\n";
        $file .= $teacher->getUsername() . "	-	" . $student2->getUsername() . "	" . $student2->getName() . "	attended lecture		1	PCM2646T01\n";
        $file .= $teacher->getUsername() . "	-	" . $student1->getUsername() . "	" . $student1->getName() . "	attended lecture		2	PCM2646T01";

        $attendanceFile = __DIR__ . "/attendance.txt";
        file_put_contents($attendanceFile, $file);
        $this->module->saveTSVCode($attendanceFile);

        // When
        $newData = $this->module->importData();

        // Then
        $this->assertTrue($newData);

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(3, $participations);

        $this->assertFalse($this->module->isRunning());
        $this->assertNotNull($this->module->getStartedRunning());
        $this->assertNotNull($this->module->getFinishedRunning());

        // Clean up
        unlink($attendanceFile);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importDataIsAlreadyRunning()
    {
        // Given
        $students = $this->course->getStudents();
        $student1 = $this->course->getCourseUserById($students[0]["id"]);
        $student2 = $this->course->getCourseUserById($students[1]["id"]);
        $teacher = $this->course->getCourseUserById($this->course->getTeachers()[0]["id"]);

        $file = $teacher->getUsername() . "	-	" . $student1->getUsername() . "	" . $student1->getName() . "	attended lecture		1	PCM2646T01\n";
        $file .= $teacher->getUsername() . "	-	" . $student2->getUsername() . "	" . $student2->getName() . "	attended lecture		1	PCM2646T01\n";
        $file .= $teacher->getUsername() . "	-	" . $student1->getUsername() . "	" . $student1->getName() . "	attended lecture		2	PCM2646T01";

        $attendanceFile = __DIR__ . "/attendance.txt";
        file_put_contents($attendanceFile, $file);
        $this->module->saveTSVCode($attendanceFile);
        $this->module->setIsRunning(true);

        // Then
        $this->expectException(Exception::class);
        $this->module->importData();

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertEmpty($participations);

        $this->assertFalse($this->module->isRunning());
        $this->assertNull($this->module->getStartedRunning());
        $this->assertNull($this->module->getFinishedRunning());

        // Clean up
        unlink($attendanceFile);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importDataCaughtError()
    {
        // Given
        $attendanceFile = __DIR__ . "/attendance.txt";
        file_put_contents($attendanceFile, "INVALID FILE");
        $this->module->saveTSVCode($attendanceFile);

        // Then
        $this->expectException(Exception::class);
        $this->module->importData();

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertEmpty($participations);

        $this->assertFalse($this->module->isRunning());
        $this->assertNull($this->module->getStartedRunning());
        $this->assertNull($this->module->getFinishedRunning());

        // Clean up
        unlink($attendanceFile);
    }


    /**
     * @test
     * @throws Exception
     */
    public function saveAttendance()
    {
        // Given
        $students = $this->course->getStudents();
        $student1 = $this->course->getCourseUserById($students[0]["id"]);
        $student2 = $this->course->getCourseUserById($students[1]["id"]);
        $teacher = $this->course->getCourseUserById($this->course->getTeachers()[0]["id"]);

        $file = $teacher->getUsername() . "	-	" . $student1->getUsername() . "	" . $student1->getName() . "	attended lecture		1	PCM2646T01\n";
        $file .= $teacher->getUsername() . "	-	" . $student2->getUsername() . "	" . $student2->getName() . "	attended lecture		1	PCM2646T01\n";
        $file .= $teacher->getUsername() . "	-	" . $student1->getUsername() . "	" . $student1->getName() . "	attended lecture		2	PCM2646T01";

        $attendanceFile = __DIR__ . "/attendance.txt";
        file_put_contents($attendanceFile, $file);

        // When
        $newData = $this->module->saveAttendance($attendanceFile);

        // Then
        $this->assertTrue($newData);

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(3, $participations);

        $p1 = $participations[0];
        $this->assertEquals(1, $p1["id"]);
        $this->assertEquals((new User($student1->getId()))->getData(), $p1["user"]);
        $this->assertEquals($this->course->getId(), $p1["course"]);
        $this->assertEquals($this->module->getId(), $p1["source"]);
        $this->assertEquals("1", $p1["description"]);
        $this->assertEquals("attended lecture", $p1["type"]);
        $this->assertNull($p1["post"]);
        $this->assertNotNull($p1["date"]);
        $this->assertNull($p1["rating"]);
        $this->assertNull($p1["evaluator"]);

        $p2 = $participations[1];
        $this->assertEquals(2, $p2["id"]);
        $this->assertEquals((new User($student2->getId()))->getData(), $p2["user"]);
        $this->assertEquals($this->course->getId(), $p2["course"]);
        $this->assertEquals($this->module->getId(), $p2["source"]);
        $this->assertEquals("1", $p2["description"]);
        $this->assertEquals("attended lecture", $p2["type"]);
        $this->assertNull($p2["post"]);
        $this->assertNotNull($p2["date"]);
        $this->assertNull($p2["rating"]);
        $this->assertNull($p2["evaluator"]);

        $p3 = $participations[2];
        $this->assertEquals(3, $p3["id"]);
        $this->assertEquals((new User($student1->getId()))->getData(), $p3["user"]);
        $this->assertEquals($this->course->getId(), $p3["course"]);
        $this->assertEquals($this->module->getId(), $p3["source"]);
        $this->assertEquals("2", $p3["description"]);
        $this->assertEquals("attended lecture", $p3["type"]);
        $this->assertNull($p3["post"]);
        $this->assertNotNull($p3["date"]);
        $this->assertNull($p3["rating"]);
        $this->assertNull($p3["evaluator"]);

        // Clean up
        unlink($attendanceFile);
    }

    /**
     * @test
     * @throws Exception
     */
    public function saveAttendanceNoNewData()
    {
        // Given
        $students = $this->course->getStudents();
        $student1 = $this->course->getCourseUserById($students[0]["id"]);
        $student2 = $this->course->getCourseUserById($students[1]["id"]);
        $teacher = $this->course->getCourseUserById($this->course->getTeachers()[0]["id"]);

        $file = $teacher->getUsername() . "	-	" . $student1->getUsername() . "	" . $student1->getName() . "	attended lecture		1	PCM2646T01\n";
        $file .= $teacher->getUsername() . "	-	" . $student2->getUsername() . "	" . $student2->getName() . "	attended lecture		1	PCM2646T01\n";
        $file .= $teacher->getUsername() . "	-	" . $student1->getUsername() . "	" . $student1->getName() . "	attended lecture		2	PCM2646T01";

        $attendanceFile = __DIR__ . "/attendance.txt";
        file_put_contents($attendanceFile, $file);

        // When
        $this->module->saveAttendance($attendanceFile);
        $newData = $this->module->saveAttendance($attendanceFile);

        // Then
        $this->assertFalse($newData);

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(3, $participations);

        // Clean up
        unlink($attendanceFile);
    }
}
