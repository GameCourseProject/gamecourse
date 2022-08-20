<?php
namespace GameCourse\XPLevels;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\XPLevels\Level;
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
class LevelTest extends TestCase
{
    private $courseId;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["modules"], ["CronJob"]);
    }

    protected function setUp(): void
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->courseId = $course->getId();

        // Enable XP & Levels module
        (new Awards($course))->setEnabled(true);
        $xpLevels = new XPLevels($course);
        $xpLevels->setEnabled(true);
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

    // Constructor

    /**
     * @test
     */
    public function levelConstructor()
    {
        $level = new Level(123);
        $this->assertEquals(123, $level->getId());
    }


    // Getters

    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $id = intval(Core::database()->select(Level::TABLE_LEVEL, ["minXP" => 1000], "id"));
        $this->assertEquals($id, $level->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $this->assertEquals($this->courseId, $level->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getMinXP()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $this->assertEquals(1000, $level->getMinXP());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDescription()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $this->assertNull($level->getDescription());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $this->assertEquals(["id" => 2, "course" => $this->courseId, "minXP" => 1000, "description" => null], $level->getData());
    }


    // Setters
    // TODO

    // TODO
}