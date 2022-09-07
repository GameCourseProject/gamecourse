<?php
namespace Event;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use Utils\Cache;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class EventTest extends TestCase
{
    private $user;
    private $course;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["events"], ["CronJob"]);
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

        // Set a user
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $this->user = $user;
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
     */
    public function setupEvents()
    {
        $events = Event::getEvents();
        $this->assertNotEmpty($events);

        // System events
        $systemEvents = [EventType::PAGE_VIEWED];
        foreach ($systemEvents as $event) {
            $this->assertArrayHasKey($event, $events);
        }

        // Page viewed
        // TODO: test event when page is viewed
    }


    // Getters

    /**
     * @test
     */
    public function getEvents()
    {
        $events = Event::getEvents();
        $this->assertNotEmpty($events);
    }


    // General

    /**
     * @test
     * @throws Exception
     */
    public function listenAndTrigger()
    {
        // Given
        $triggered = false;
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggered) {
            $triggered = true;
            $this->assertEquals($this->course->getId(), $courseId);
            $this->assertEquals($this->user->getId(), $studentId);
            return true;
        });

        // When
        $this->course->addUserToCourse($this->user->getId(), "Student");

        // Then
        $this->assertTrue($triggered);

        $cache = Cache::get(null, "events");
        $this->assertIsArray($cache);
        $this->assertCount(1, $cache);
        $this->assertEquals(EventType::STUDENT_ADDED_TO_COURSE, array_keys($cache)[0]);
        $this->assertIsArray($cache[EventType::STUDENT_ADDED_TO_COURSE]);
        $this->assertCount(1, $cache[EventType::STUDENT_ADDED_TO_COURSE]);

        $func = $cache[0][array_keys($cache[0])[0]];
        $this->assertIsObject($func);
        $this->assertTrue($func($this->course->getId(), $this->user->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function stopListeningToAllEvents()
    {
        // Given
        $triggeredWithoutPrefix = false;
        $triggeredWithPrefix = false;
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggeredWithoutPrefix) {
            $triggeredWithoutPrefix = true;
            return true;
        });
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggeredWithPrefix) {
            $triggeredWithPrefix = true;
            return true;
        }, "test");

        // When
        Event::stopAll();
        $this->course->addUserToCourse($this->user->getId(), "Student");

        // Then
        $this->assertFalse($triggeredWithoutPrefix);
        $this->assertFalse($triggeredWithPrefix);
        $this->assertEmpty(Cache::get(null, "events"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function stopListeningToASpecificEvent()
    {
        // Given
        $triggered1stEvent = false;
        $triggered2ndEvent = false;
        $eventId = Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggered1stEvent) {
            $triggered1stEvent = true;
            return true;
        });
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) use (&$triggered2ndEvent) {
            $triggered2ndEvent = true;
            return true;
        });

        // When
        Event::stop(EventType::STUDENT_ADDED_TO_COURSE, $eventId);
        $this->course->addUserToCourse($this->user->getId(), "Student");

        // Then
        $this->assertFalse($triggered1stEvent);
        $this->assertTrue($triggered2ndEvent);

        $cache = Cache::get(null, "events");
        $this->assertNotEmpty($cache);
        $this->assertCount(1, $cache);
        $this->assertCount(1, $cache[EventType::STUDENT_ADDED_TO_COURSE]);
    }

    /**
     * @test
     * @throws Exception
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

        $cache = Cache::get(null, "events");
        $this->assertNotEmpty($cache);
        $this->assertCount(1, $cache);
        $this->assertCount(1, $cache[EventType::STUDENT_ADDED_TO_COURSE]);
    }
}
