<?php
namespace GameCourse\Module\Moodle;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use Utils\Time;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class MoodleTest extends TestCase
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

        // Enable Moodle module
        $moodle = new Moodle($course);
        $moodle->setEnabled(true);
        $this->module = $moodle;
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Moodle::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
        }

        $config = $this->module->getMoodleConfig();
        $this->assertEquals(Moodle::DEFAULT_DB_SERVER, $config["dbServer"]);
        $this->assertEquals(Moodle::DEFAULT_DB_USER, $config["dbUser"]);
        $this->assertNull($config["dbPass"]);
        $this->assertEquals(Moodle::DEFAULT_DB_NAME, $config["dbName"]);
        $this->assertEquals(Moodle::DEFAULT_DB_PORT, $config["dbPort"]);
        $this->assertEquals(Moodle::DEFAULT_TABLES_PREFIX, $config["tablesPrefix"]);
        $this->assertNull($config["moodleCourse"]);
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

        $moodlesModule = new Moodle($copyTo);
        $moodlesModule->setEnabled(true);

        $this->module->saveSchedule("* * * * *");

        // When
        $this->module->copyTo($copyTo);

        // Then
        $this->assertEquals($this->module->getSchedule(), $moodlesModule->getSchedule());
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . Moodle::ID . "/sql/create.sql");
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
    public function getMoodleConfig()
    {
        $config = $this->module->getMoodleConfig();
        $this->assertEquals(Moodle::DEFAULT_DB_SERVER, $config["dbServer"]);
        $this->assertEquals(Moodle::DEFAULT_DB_USER, $config["dbUser"]);
        $this->assertNull($config["dbPass"]);
        $this->assertEquals(Moodle::DEFAULT_DB_NAME, $config["dbName"]);
        $this->assertEquals(Moodle::DEFAULT_DB_PORT, $config["dbPort"]);
        $this->assertEquals(Moodle::DEFAULT_TABLES_PREFIX, $config["tablesPrefix"]);
        $this->assertNull($config["moodleCourse"]);
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

    public function setIsRunning()
    {
        $this->module->setIsRunning(true);
        $this->assertTrue($this->module->isRunning());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getCheckpoint()
    {
        $this->assertNull($this->module->getCheckpoint());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCheckpoint()
    {
        $datetime = "2022-09-01 19:59:00";
        $this->module->setCheckpoint($datetime);
        $this->assertEquals($datetime, $this->module->getCheckpoint());
    }


    // Importing data

    /**
     * @test
     * @throws Exception
     */
    public function saveAssignmentGrades()
    {
        // Given
        $student1 = $this->course->getCourseUserById($this->course->getStudents()[0]["id"]);
        $student2 = $this->course->getCourseUserById($this->course->getStudents()[1]["id"]);
        AutoGame::addParticipation($this->course->getId(), $student2->getId(), "ASSIGNMENT", "assignment grade", $this->module->getId(),
            "2020-05-12 12:34:22", "mod/assign/view.php?id=1", 10, 1);

        $assignmentGrades = [
            ["assignmentId" => 1, "assignmentName" => "Assignment 1", "username" => $student1->getUsername(), "grade" => 3.4, "grader" => "ist123456", "submissionTimestamp" => 1662138980, "gradeTimestamp" => 1662138988],
            ["assignmentId" => 1, "assignmentName" => "Assignment 1", "username" => $student2->getUsername(), "grade" => 5.5, "grader" => "ist123456", "submissionTimestamp" => 1662138960, "gradeTimestamp" => 1662138968],
        ];

        // When
        $info = $this->module->saveAssignmentGrades($assignmentGrades);

        // Then
        $this->assertIsArray($info);
        $this->assertArrayHasKey("oldestRecordTimestamp", $info);
        $this->assertArrayHasKey("lastRecordTimestamp", $info);
        $this->assertIsInt($info["oldestRecordTimestamp"]);
        $this->assertIsInt($info["lastRecordTimestamp"]);
        $this->assertEquals(1662138960, $info["oldestRecordTimestamp"]);
        $this->assertEquals(1662138980, $info["lastRecordTimestamp"]);

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(2, $participations);

        $p1 = $participations[0];
        $this->assertEquals(["id" => 2, "user" => $student1->getId(), "course" => $this->course->getId(),
            "source" => $this->module->getId(), "description" => "Assignment 1", "type" => "assignment grade", "post" => "mod/assign/view.php?id=1",
            "date" => date("Y-m-d H:i:s", 1662138980), "rating" => 3, "evaluator" => 1], $p1);

        $p2 = $participations[1];
        $this->assertEquals(["id" => 1, "user" => $student2->getId(), "course" => $this->course->getId(),
            "source" => $this->module->getId(), "description" => "Assignment 1", "type" => "assignment grade", "post" => "mod/assign/view.php?id=1",
            "date" => date("Y-m-d H:i:s", 1662138960), "rating" => 6, "evaluator" => 1], $p2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function saveForumGrades()
    {
        // Given
        $student1 = $this->course->getCourseUserById($this->course->getStudents()[0]["id"]);
        $student2 = $this->course->getCourseUserById($this->course->getStudents()[1]["id"]);
        AutoGame::addParticipation($this->course->getId(), $student1->getId(), "GRADE", "graded post", $this->module->getId(),
            "2020-05-12 12:34:22", "mod/forum/discuss.php?d=123#p6418", 10, 1);

        $forumGrades = [
            ["forumName" => "Skill Tree", "discussionId" => 123, "subject" => "Re: Looping GIF", "gradeId" => 6418, "username" => $student1->getUsername(), "grade" => 3, "grader" => "ist123456", "submissionTimestamp" => 1662138980, "gradeTimestamp" => 1662138988],
        ];
        $peerForumGrades = [
            ["forumName" => "Bugs Forum", "discussionId" => 245, "subject" => "Re: Pixel Art", "gradeId" => 6537, "username" => $student2->getUsername(), "grade" => 5, "grader" => "ist123456", "submissionTimestamp" => 1662138960, "gradeTimestamp" => 1662138968],
        ];

        // When
        $info = $this->module->saveForumGrades($forumGrades);

        // Then
        $this->assertIsArray($info);
        $this->assertArrayHasKey("oldestRecordTimestamp", $info);
        $this->assertArrayHasKey("lastRecordTimestamp", $info);
        $this->assertIsInt($info["oldestRecordTimestamp"]);
        $this->assertIsInt($info["lastRecordTimestamp"]);
        $this->assertEquals(1662138980, $info["oldestRecordTimestamp"]);
        $this->assertEquals(1662138980, $info["lastRecordTimestamp"]);

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(1, $participations);

        $p1 = $participations[0];
        $this->assertEquals(["id" => 1, "user" => $student1->getId(), "course" => $this->course->getId(),
            "source" => $this->module->getId(), "description" => "Skill Tree, Re: Looping GIF", "type" => "graded post", "post" => "mod/forum/discuss.php?d=123#p6418",
            "date" => date("Y-m-d H:i:s", 1662138980), "rating" => 3, "evaluator" => 1], $p1);

        // When
        $info = $this->module->saveForumGrades($peerForumGrades, true);

        // Then
        $this->assertIsArray($info);
        $this->assertArrayHasKey("oldestRecordTimestamp", $info);
        $this->assertArrayHasKey("lastRecordTimestamp", $info);
        $this->assertIsInt($info["oldestRecordTimestamp"]);
        $this->assertIsInt($info["lastRecordTimestamp"]);
        $this->assertEquals(1662138960, $info["oldestRecordTimestamp"]);
        $this->assertEquals(1662138960, $info["lastRecordTimestamp"]);

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(2, $participations);

        $p2 = $participations[1];
        $this->assertEquals(["id" => 2, "user" => $student2->getId(), "course" => $this->course->getId(),
            "source" => $this->module->getId(), "description" => "Bugs Forum, Re: Pixel Art", "type" => "graded post", "post" => "mod/peerforum/discuss.php?d=245#p6537",
            "date" => date("Y-m-d H:i:s", 1662138960), "rating" => 5, "evaluator" => 1], $p2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function savePeergrades()
    {
        // Given
        $student1 = $this->course->getCourseUserById($this->course->getStudents()[0]["id"]);
        $student2 = $this->course->getCourseUserById($this->course->getStudents()[1]["id"]);
        AutoGame::addParticipation($this->course->getId(), $student2->getId(), "PEERGRADE", "peergraded post", $this->module->getId(),
            "2020-05-12 12:34:22", "mod/peerforum/discuss.php?d=245#p6537", 10, 1);

        $peergrades = [
            ["forumName" => "Skill Tree", "discussionId" => 123, "subject" => "Re: Looping GIF", "peergradeId" => 6418, "username" => $student1->getUsername(), "grade" => 3, "grader" => "ist123456", "timestamp" => 1662138988],
            ["forumName" => "Skill Tree", "discussionId" => 245, "subject" => "Re: Pixel Art", "peergradeId" => 6537, "username" => $student2->getUsername(), "grade" => 5, "grader" => "ist123456", "timestamp" => 1662138968],
        ];

        // When
        $info = $this->module->savePeergrades($peergrades);

        // Then
        $this->assertIsArray($info);
        $this->assertArrayHasKey("oldestRecordTimestamp", $info);
        $this->assertArrayHasKey("lastRecordTimestamp", $info);
        $this->assertIsInt($info["oldestRecordTimestamp"]);
        $this->assertIsInt($info["lastRecordTimestamp"]);
        $this->assertEquals(1662138968, $info["oldestRecordTimestamp"]);
        $this->assertEquals(1662138988, $info["lastRecordTimestamp"]);

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(2, $participations);

        $p1 = $participations[0];
        $this->assertEquals(["id" => 2, "user" => $student1->getId(), "course" => $this->course->getId(),
            "source" => $this->module->getId(), "description" => "Skill Tree, Re: Looping GIF", "type" => "peergraded post", "post" => "mod/peerforum/discuss.php?d=123#p6418",
            "date" => date("Y-m-d H:i:s", 1662138988), "rating" => 3, "evaluator" => 1], $p1);

        $p2 = $participations[1];
        $this->assertEquals(["id" => 1, "user" => $student2->getId(), "course" => $this->course->getId(),
            "source" => $this->module->getId(), "description" => "Skill Tree, Re: Pixel Art", "type" => "peergraded post", "post" => "mod/peerforum/discuss.php?d=245#p6537",
            "date" => date("Y-m-d H:i:s", 1662138968), "rating" => 5, "evaluator" => 1], $p2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function saveQuizGrades()
    {
        // Given
        $student1 = $this->course->getCourseUserById($this->course->getStudents()[0]["id"]);
        $student2 = $this->course->getCourseUserById($this->course->getStudents()[1]["id"]);
        AutoGame::addParticipation($this->course->getId(), $student2->getId(), "QUIZ", "quiz grade", $this->module->getId(),
            "2020-05-12 12:34:22", "mod/quiz/view.php?id=1", 10);

        $quizGrades = [
            ["quizzId" => 1, "quizName" => "Quiz 1", "username" => $student1->getUsername(), "grade" => 3.4, "timestamp" => 1662138988],
            ["quizzId" => 1, "quizName" => "Quiz 1", "username" => $student2->getUsername(), "grade" => 5.5, "timestamp" => 1662138968],
        ];

        // When
        $info = $this->module->saveQuizGrades($quizGrades);

        // Then
        $this->assertIsArray($info);
        $this->assertArrayHasKey("oldestRecordTimestamp", $info);
        $this->assertArrayHasKey("lastRecordTimestamp", $info);
        $this->assertIsInt($info["oldestRecordTimestamp"]);
        $this->assertIsInt($info["lastRecordTimestamp"]);
        $this->assertEquals(1662138968, $info["oldestRecordTimestamp"]);
        $this->assertEquals(1662138988, $info["lastRecordTimestamp"]);

        $participations = AutoGame::getParticipations($this->course->getId());
        $this->assertIsArray($participations);
        $this->assertCount(2, $participations);

        $p1 = $participations[0];
        $this->assertEquals(["id" => 2, "user" => $student1->getId(), "course" => $this->course->getId(),
            "source" => $this->module->getId(), "description" => "Quiz 1", "type" => "quiz grade", "post" => "mod/quiz/view.php?id=1",
            "date" => date("Y-m-d H:i:s", 1662138988), "rating" => 3, "evaluator" => null], $p1);

        $p2 = $participations[1];
        $this->assertEquals(["id" => 1, "user" => $student2->getId(), "course" => $this->course->getId(),
            "source" => $this->module->getId(), "description" => "Quiz 1", "type" => "quiz grade", "post" => "mod/quiz/view.php?id=1",
            "date" => date("Y-m-d H:i:s", 1662138968), "rating" => 6, "evaluator" => null], $p2);
    }
}
