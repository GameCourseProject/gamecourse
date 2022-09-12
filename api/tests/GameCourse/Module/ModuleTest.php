<?php
namespace GameCourse\Module;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use Utils\Utils;

class ModuleTest extends TestCase
{
    private $course;

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
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER]);
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

    /**
     * @test
     */
    public function courseConstructor()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals(XPLevels::ID, $module->getId());
    }


    /**
     * @test
     */
    public function getId()
    {
        $module = new XPLevels($this->course);
        $id = Core::database()->select(Module::TABLE_MODULE, ["name" => XPLevels::NAME], "id");
        $this->assertEquals($id, $module->getId());
    }

    /**
     * @test
     */
    public function getModuleName()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals(XPLevels::NAME, $module->getName());
    }

    /**
     * @testn
     */
    public function getDescription()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals(XPLevels::DESCRIPTION, $module->getDescription());
    }

    /**
     * @test
     */
    public function getIcon()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals(API_URL . "/" . Utils::getDirectoryName(MODULES_FOLDER) . "/" . XPLevels::ID . "/icon.svg", $module->getIcon());
    }

    /**
     * @test
     */
    public function getType()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals(XPLevels::TYPE, $module->getType());
    }

    /**
     * @test
     */
    public function getVersion()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals(XPLevels::VERSION, $module->getVersion());
    }

    /**
     * @test
     */
    public function getCompatibleVersions()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals([
            "project" => XPLevels::PROJECT_VERSION,
            "api" => XPLevels::API_VERSION
        ], $module->getCompatibleVersions());
    }

    /**
     * @test
     */
    public function getCourse()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals($this->course, $module->getCourse());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isEnabled()
    {
        $module = new Awards($this->course);
        $module->setEnabled(true);
        $this->assertTrue($module->isEnabled());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isDisabled()
    {
        $module = new XPLevels($this->course);
        $this->assertFalse($module->isEnabled());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals(["id" => XPLevels::ID, "name" => XPLevels::NAME, "description" => XPLevels::DESCRIPTION,
            "type" => XPLevels::TYPE, "version" => XPLevels::VERSION, "minProjectVersion" => XPLevels::PROJECT_VERSION["min"],
            "maxProjectVersion" => XPLevels::PROJECT_VERSION["max"], "minAPIVersion" => XPLevels::API_VERSION["min"],
            "maxAPIVersion" => XPLevels::API_VERSION["max"]], $module->getData());
    }


    /**
     * @test
     * @throws Exception
     */
    public function enable()
    {
        $module = new Awards($this->course);
        $module->setEnabled(true);
        $this->assertTrue($module->isEnabled());
    }

    /**
     * @test
     * @throws Exception
     */
    public function enableCantEnable()
    {
        $module = new XPLevels($this->course);
        try {
            $module->setEnabled(true);
            $this->fail("Exception should have been thrown on 'enableCantEnable'.");

        } catch (Exception $e) {
            $this->assertFalse($module->isEnabled());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function disable()
    {
        $module = new Awards($this->course);
        $module->setEnabled(true);
        $this->assertTrue($module->isEnabled());

        $module->setEnabled(false);
        $this->assertFalse($module->isEnabled());
    }

    /**
     * @test
     * @throws Exception
     */
    public function disableCantDisable()
    {
        $module = new Awards($this->course);
        $module->setEnabled(true);
        $this->assertTrue($module->isEnabled());

        (new XPLevels($this->course))->setEnabled(true);
        try {
            $module->setEnabled(false);
            $this->fail("Exception should have been thrown on 'disableCantDisable'.");

        } catch (Exception $e) {
            $this->assertTrue($module->isEnabled());
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function setupModules()
    {
        Core::database()->deleteAll(Module::TABLE_MODULE);
        $this->assertEmpty(Module::getModules(true));

        Module::setupModules();
        $this->assertNotEmpty(Module::getModules(true));
    }


    /**
     * @test
     */
    public function getModuleById()
    {
        $module = new XPLevels($this->course);
        $this->assertEquals($module, Module::getModuleById(XPLevels::ID, $this->course));
    }

    /**
     * @test
     */
    public function getModuleByIdModuleDoesntExist()
    {
        $this->assertNull(Module::getModuleById("MODULE", $this->course));
    }


    // TODO: more testing
}
