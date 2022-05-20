<?php
namespace Event;

use GameCourse\Core\Auth;
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
class EventTest extends TestCase
{
    private $loggedUser;
    private $user;
    private $course;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(false, ["CronJob"]);
    }

    protected function setUp(): void
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);
        $this->loggedUser = $loggedUser;

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->course = $course;

        // Set a user
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $this->user = $user;
    }

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
    public function listenAndTrigger()
    {
        // Given
        $triggered = false;
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggered) {
            $triggered = true;
            $this->assertEquals($this->course->getId(), $courseId);
            $this->assertEquals($this->user->getId(), $studentId);
        });

        // When
        $this->course->addUserToCourse($this->user->getId(), "Student");

        // Then
        $this->assertTrue($triggered);
    }

    /**
     * @test
     */
    public function stopListeningToASpecificEvent()
    {
        // Given
        $triggered = false;
        $eventId = Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggered) {
            $triggered = true;
        });

        // When
        Event::stop(EventType::STUDENT_ADDED_TO_COURSE, $eventId);
        $this->course->addUserToCourse($this->user->getId(), "Student");

        // Then
        $this->assertFalse($triggered);
    }

    /**
     * @test
     */
    public function stopListeningToAllEventsWithSamePrefix()
    {
        // Given
        $triggeredWithoutPrefix = false;
        $triggeredWithPrefix = false;
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggeredWithoutPrefix) {
            $triggeredWithoutPrefix = true;
        });
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggeredWithPrefix) {
            $triggeredWithPrefix = true;
        }, "test");

        // When
        Event::stopAll("test");
        $this->course->addUserToCourse($this->user->getId(), "Student");

        // Then
        $this->assertTrue($triggeredWithoutPrefix);
        $this->assertFalse($triggeredWithPrefix);
    }

    /**
     * @test
     */
    public function stopListeningToAllEvents()
    {
        // Given
        $triggeredWithoutPrefix = false;
        $triggeredWithPrefix = false;
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggeredWithoutPrefix) {
            $triggeredWithoutPrefix = true;
        });
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggeredWithPrefix) {
            $triggeredWithPrefix = true;
        }, "test");

        // When
        Event::stopAll();
        $this->course->addUserToCourse($this->user->getId(), "Student");

        // Then
        $this->assertFalse($triggeredWithoutPrefix);
        $this->assertFalse($triggeredWithPrefix);
    }
}
