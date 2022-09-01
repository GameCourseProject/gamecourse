<?php
namespace GameCourse\Module\QR;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
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
class QRTest extends TestCase
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

        // Enable QR module
        $QRModule = new QR($course);
        $QRModule->setEnabled(true);
        $this->module = $QRModule;
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . QR::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[2];
        foreach ($tables as $table) {
            $this->assertTrue(Core::database()->tableExists($table));
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
        $sql = file_get_contents(MODULES_FOLDER . "/" . QR::ID . "/sql/create.sql");
        preg_match_all("/CREATE TABLE (IF NOT EXISTS )*(.*)\(/i", $sql, $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            $this->assertFalse(Core::database()->tableExists($table));
        }
    }


    // QR codes

    /**
     * @test
     * @throws Exception
     */
    public function getQRParticipations()
    {
        // Given
        $student = new CourseUser($this->course->getStudents(true)[0]["id"], $this->course);
        $this->module->submitQRParticipation($student->getId(), 1, ClassType::LECTURE);
        $this->module->submitQRParticipation($student->getId(), 2, ClassType::LECTURE);

        // When
        $participations = $this->module->getQRParticipations();

        // Then
        $this->assertIsArray($participations);
        $this->assertCount(2, $participations);

        $keys = ["qrkey", "user", "classNumber", "classType", "date"];
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
     */
    public function getQRParticipationsEmpty()
    {
        $this->assertEmpty($this->module->getQRParticipations());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserQRParticipations()
    {
        // Given
        $student1 = new CourseUser($this->course->getStudents()[0]["id"], $this->course);
        $student2 = new CourseUser($this->course->getStudents()[1]["id"], $this->course);

        $this->module->submitQRParticipation($student1->getId(), 1, ClassType::LECTURE);
        $this->module->submitQRParticipation($student1->getId(), 2, ClassType::LECTURE);
        $this->module->submitQRParticipation($student2->getId(), 2, ClassType::LECTURE);

        // When
        $userParticipations = $this->module->getUserQRParticipations($student1->getId());

        // Then
        $this->assertIsArray($userParticipations);
        $this->assertCount(2, $userParticipations);

        $keys = ["qrkey", "classNumber", "classType", "date"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($userParticipations as $participation) {
                $this->assertCount($nrKeys, array_keys($participation));
                $this->assertArrayHasKey($key, $participation);
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getQRErrors()
    {
        // Given
        $student = new CourseUser($this->course->getStudents(true)[0]["id"], $this->course);
        try {
            $this->module->submitQRParticipation($student->getId(), 1, ClassType::LECTURE, 1);

        } catch (Exception $e) {
            // When
            $errors = $this->module->getQRErrors();

            // Then
            $this->assertIsArray($errors);
            $this->assertCount(1, $errors);

            $keys = ["qrkey", "user", "msg", "date"];
            $nrKeys = count($keys);
            foreach ($keys as $key) {
                foreach ($errors as $error) {
                    $this->assertCount($nrKeys, array_keys($error));
                    $this->assertArrayHasKey($key, $error);
                }
            }
        }
    }

    /**
     * @test
     */
    public function getQRErrorsEmpty()
    {
        $this->assertEmpty($this->module->getQRErrors());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserQRErrors()
    {
        // Given
        $student1 = new CourseUser($this->course->getStudents()[0]["id"], $this->course);
        $student2 = new CourseUser($this->course->getStudents()[1]["id"], $this->course);

        try {
            $this->module->submitQRParticipation($student1->getId(), 1, ClassType::LECTURE, 1);

        } catch (Exception $e) {
            try {
                $this->module->submitQRParticipation($student2->getId(), 2, ClassType::LECTURE, 1);

            } catch (Exception $e) {
                // When
                $userErrors = $this->module->getUserQRErrors($student1->getId());

                // Then
                $this->assertIsArray($userErrors);
                $this->assertCount(1, $userErrors);

                $keys = ["qrkey", "msg", "date"];
                $nrKeys = count($keys);
                foreach ($keys as $key) {
                    foreach ($userErrors as $error) {
                        $this->assertCount($nrKeys, array_keys($error));
                        $this->assertArrayHasKey($key, $error);
                    }
                }
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function generateQRCodes()
    {
        $QRCodes = $this->module->generateQRCodes(3);

        $this->assertIsArray($QRCodes);
        $this->assertCount(3, $QRCodes);

        foreach ($QRCodes as $QRCode) {
            $this->assertArrayHasKey("key", $QRCode);
            $this->assertArrayHasKey("qr", $QRCode);
            $this->assertStringContainsString("data:image/png;base64,", $QRCode["qr"]);
            $this->assertArrayHasKey("url", $QRCode);
            $this->assertStringContainsString("tinyurl.com", $QRCode["url"]);
        }

        $QRCodesInDB = Core::database()->selectMultiple(QR::TABLE_QR_CODE);
        $this->assertCount(3, $QRCodesInDB);
    }

    /**
     * @test
     * @throws Exception
     */
    public function generateQRCodesFailure()
    {
        $this->expectException(Exception::class);
        $this->module->generateQRCodes(-1);
    }


    /**
     * @test
     * @throws Exception
     */
    public function submitParticipation()
    {
        // Given
        $student = new CourseUser($this->course->getStudents(true)[0]["id"], $this->course);

        // When
        $this->module->submitQRParticipation($student->getId(), 1, ClassType::LECTURE);

        // Then
        $this->assertCount(1, $this->module->getUserQRParticipations($student->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function submitParticipationWithQRKey()
    {
        // Given
        $student = new CourseUser($this->course->getStudents(true)[0]["id"], $this->course);
        $QRCode = $this->module->generateQRCodes(1)[0];

        // When
        $this->module->submitQRParticipation($student->getId(), 1, ClassType::LECTURE, $QRCode["key"]);

        // Then
        $this->assertCount(1, $this->module->getUserQRParticipations($student->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function submitParticipationQRCodeNotRegistered()
    {
        // Given
        $student = new CourseUser($this->course->getStudents(true)[0]["id"], $this->course);

        try {
            $this->module->submitQRParticipation($student->getId(), 1, ClassType::LECTURE, 1);
            $this->fail("Exception should have been thrown on 'submitParticipationQRCodeNotRegistered'");

        } catch (Exception $e) {
            $this->assertEmpty($this->module->getUserQRParticipations($student->getId()));
            $this->assertCount(1, $this->module->getUserQRErrors($student->getId()));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function submitParticipationQRCodeAlreadyRedeemed()
    {
        // Given
        $student = new CourseUser($this->course->getStudents(true)[0]["id"], $this->course);
        $QRCode = $this->module->generateQRCodes()[0];
        $this->module->submitQRParticipation($student->getId(), 1, ClassType::LECTURE, $QRCode["key"]);

        try {
            $this->module->submitQRParticipation($student->getId(), 1, ClassType::LECTURE, $QRCode["key"]);
            $this->fail("Exception should have been thrown on 'submitParticipationQRCodeAlreadyRedeemed'");

        } catch (Exception $e) {
            $this->assertCount(1, $this->module->getUserQRParticipations($student->getId()));
            $this->assertCount(1, $this->module->getUserQRErrors($student->getId()));
        }
    }
}
