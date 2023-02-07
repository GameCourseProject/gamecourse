<?php

namespace GameCourse\NotificationSystem;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use Mockery\Matcher\Not;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use GameCourse\Course\Course;
use GameCourse\User\User;
use TypeError;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class NotificationTest extends TestCase
{

    private $courseId;
    private $userId;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass([], ["CronJob"]);
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
        $this->userId = $loggedUser->getId();

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->courseId = $course->getId();
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Notification::TABLE_NOTIFICATION]);
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
    /*** ------------------ Data Providers ------------------ ***/
    /*** ---------------------------------------------------- ***/

    // TODO: Complete with functions add, edit, delete -> Even if not used, makes code more generic and modular for future changes

    public function notificationMessageSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Notification Message"],
            "non-ASCII characters" => ["Notificatión Name"],
            "numbers" => ["Notification123"],
            "parenthesis" => ["Notification Message (Copy)"],
            "hyphen" => ["Notification-Message"],
            "underscore" => ["Notification_Message"],
            "ampersand" => ["Notification & Message"],
            "trimmed" => [" This is some incredibly big notification This i "],
            "length limit" => ["This is some incredibly big notification This i"]
        ];
    }

    public function notificationMessageFailureProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "too long" => ["This is some incredibly humongous notification This is some incredibly humongous notification This is some incredibly humongous notification This is so"]
        ];
    }

    public function notificationSuccessProvider(): array
    {
        return [
            "default" => ["Notification Message", false],
            "multiple lines: message" => ["Some message:\n-line1", false],
            "showed" => ["Notification Message", true]
        ];
    }

    public function notificationFailureProvider(): array
    {
        return [
            "null message" => [null, false],
            "empty message" => ["", false],
        ];
    }

    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function notificationConstructor()
    {
        $notification = new Notification(123);
        $this->assertEquals(123, $notification->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message", false);
        $id = intval(Core::database()->select(Notification::TABLE_NOTIFICATION, ["course" => $this->courseId, "user" => $this->userId, "message" => "Notification Message"], "id"));
        $this->assertEquals($id, $notification->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message", false);
        $this->assertEquals($this->courseId, $notification->getCourse());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUser()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message", false);
        $this->assertEquals($this->userId, $notification->getUser());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getMessage()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message", false);
        $this->assertEquals("Notification Message", $notification->getMessage());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isShowed()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message");
        $this->assertFalse($notification->isShowed());
    }

    /**
     * @test
     * @dataProvider notificationMessageSuccessProvider
     * @throws Exception
     */
    public function setMessageSuccess(string $message)
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "MESSAGE");
        $notification->setMessage($message);

        $message = trim($message);
        $this->assertEquals($message, $notification->getMessage());

    }

    /**
     * @test
     * @dataProvider notificationMessageFailureProvider
     * @throws Exception
     */
    public function setMessageFailure($message)
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "MESSAGE");

        try {
            $notification->setMessage($message);
            $this->fail("Exception should have been thrown on 'setMessageFailure'");

        } catch (Exception|TypeError $error) {
            $this->assertEquals("MESSAGE", $notification->getMessage());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setShowed()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message");
        $notification->setShowed(true);
        $this->assertTrue($notification->isShowed());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotShowed()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message");
        $notification->setShowed(false);
        $this->assertFalse($notification->isShowed());
    }

    /**
     * @test
     * @dataProvider notificationSuccessProvider
     * @throws Exception
     */
    public function setDataSuccess(string $message, bool $isShowed)
    {
        $fieldValues = ["message" => $message, "isShowed" => $isShowed];
        $notification = Notification::addNotification(
            $this->courseId, $this->userId, "MESSAGE");
        $notification->setData($fieldValues);
        $fieldValues["id"] = $notification->getId();
        $fieldValues["course"] = $notification->getCourse();
        $fieldValues["user"] = $notification->getUser();
        $this->assertEquals($notification->getData(), $fieldValues);
    }

    /**
     * @test
     * @dataProvider notificationFailureProvider
     * @throws Exception
     */
    public function setDataFailure($message, $isShowed)
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "NOTIFICATION MESSAGE");
        try {
            $notification->setData(["message" => $message, "isShowed" => $isShowed]);
            $this->fail("Exception should have been thrown on 'setDataFailure");

        } catch (Exception $e) {
            $this->assertEquals(["id" => 1, "course" => $this->courseId, "user" => $this->userId, "message" => "NOTIFICATION MESSAGE",
                "isShowed" => 0], $notification->getData());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getNotifications()
    {
        $notification1 = Notification::addNotification($this->courseId, $this->userId, "Notification1");
        $notification2 = Notification::addNotification($this->courseId, $this->userId, "Notification2");

        $notifications = Notification::getNotifications();
        $this->assertIsArray($notifications);
        $this->assertCount(2, $notifications);

        $keys = ["id", "course", "user", "message", "isShowed"];
        $nrKeys = count($keys);
        foreach ($keys as $key){
            foreach ($notifications as $i => $notification) {
                $this->assertCount($nrKeys, array_keys($notification));
                $this->assertArrayHasKey($key, $notification);
                $this->assertEquals($notification[$key], ${"notification".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getNotificationById()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message");
        $this->assertEquals($notification, Notification::getNotificationById($notification->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getNotificationsByUser()
    {
        $notification1 = Notification::addNotification($this->courseId, $this->userId, "Notification1");
        $notification2 = Notification::addNotification($this->courseId, $this->userId, "Notification2");

        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        $user = User::addUser("Ana Gonçalves", "ist100000", AuthService::FENIX, "ana.goncalves@gmail.com",
            10000, "Ana G", "MEIC-A", 0, 0);
        Notification::addNotification($course->getId(), $user->getId(), "Notification1");

        $notifications = Notification::getNotificationsByUser($this->userId);
        $this->assertIsArray($notifications);
        $this->assertCount(2, $notifications);

        $keys = ["id", "course", "user", "message", "isShowed"];
        $nrKeys = count($keys);
        foreach ($keys as $key){
            foreach ($notifications as $i => $notification) {
                $this->assertCount($nrKeys, array_keys($notification));
                $this->assertArrayHasKey($key, $notification);
                $this->assertEquals($notification[$key], ${"notification".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getNotificationsByCourse()
    {
        $notification1 = Notification::addNotification($this->courseId, $this->userId, "Notification1");
        $notification2 = Notification::addNotification($this->courseId, $this->userId, "Notification2");

        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        $user = User::addUser("Ana Gonçalves", "ist100000", AuthService::FENIX, "ana.goncalves@gmail.com",
            10000, "Ana G", "MEIC-A", 0, 0);
        Notification::addNotification($course->getId(), $user->getId(), "Notification1");

        $notifications = Notification::getNotificationsByCourse($this->courseId);
        $this->assertIsArray($notifications);
        $this->assertCount(2, $notifications);

        $keys = ["id", "course", "user", "message", "isShowed"];
        $nrKeys = count($keys);
        foreach ($keys as $key){
            foreach ($notifications as $i => $notification) {
                $this->assertCount($nrKeys, array_keys($notification));
                $this->assertArrayHasKey($key, $notification);
                $this->assertEquals($notification[$key], ${"notification".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @dataProvider notificationSuccessProvider
     * @throws Exception
     */
    public function addNotificationSuccess(string $message, bool $isShowed)
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, $message, $isShowed);

        $notifications = Notification::getNotifications();
        $this->assertIsArray($notifications);
        $this->assertCount(1, $notifications);
        $this->assertEquals($notification->getId(), $notifications[0]["id"]);

        $notificationData = ["id" => 1, "course" => $this->courseId, "user" => $this->userId, "message" => $message, "isShowed" => $isShowed];
        $this->assertEquals($notificationData, $notification->getData());
        $this->assertEquals($notificationData, $notifications[0]);

    }

    /**
     * @test
     * @dataProvider notificationFailureProvider
     * @throws Exception
     */
    public function addNotificationFailure($message, bool $isShowed)
    {
        try {
            Notification::addNotification($this->courseId, $this->userId, $message, $isShowed);
            $this->fail("Exception should have been thrown on 'addNotificationFailure'");

        } catch (Exception | TypeError $e){
            $notifications = Notification::getNotifications();
            $this->assertIsArray($notifications);
            $this->assertEmpty($notifications);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function notificationExists()
    {
        $notification = Notification::addNotification($this->courseId, $this->userId, "Notification Message");
        $this->assertTrue($notification->exists());
    }

    /**
     * @test
     */
    public function notificationDoesntExist()
    {
        $notification = new Notification(1);
        $this->assertFalse($notification->exists());
    }

}