<?php
namespace GameCourse\Course;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\AutoGame\RuleSystem;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Role\Role;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use GameCourse\Views\Page\Page;
use GameCourse\Views\ViewHandler;
use PDOException;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use TypeError;
use Utils\Utils;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class CourseTest extends TestCase
{
    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["roles, modules", "views"], ["CronJob"]);
    }

    protected function setUp(): void
    {
        $user = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($user);
    }

    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER, Role::TABLE_ROLE, ViewHandler::TABLE_VIEW]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Role::TABLE_ROLE, Page::TABLE_PAGE]);
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
    /*** ------------------ Data Providers ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public function setCourseNameSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Multimedia Content Production"],
            "non-ASCII characters" => ["Produção de Conteúdos Multimédia"],
            "numbers" => ["PCM22"],
            "parenthesis" => ["PCM (Copy)"],
            "hyphen" => ["PCM-21"],
            "underscore" => ["PCM_21"],
            "ampersand" => ["PC & M"],
            "length limit" => ["Multimedia Content Production Multimedia Content Production Multimedia Content Production Multimedia"]
        ];
    }

    public function setCourseNameFailureProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "whitespace" => [" "],
            "star" => ["*"],
            "dot" => ["."],
            "apostrophe" => ["'"],
            "double quote" => ["\""],
            "hashtag" => ["#"],
            "at" => ["@"],
            "percent" => ["%"],
            "slash" => ["/"],
            "backslash" => ["\\"],
            "comma" => [","],
            "colon" => [":"],
            "semicolon" => [";"],
            "less than" => ["<"],
            "greater than" => [">"],
            "equal" => ["="],
            "plus" => ["+"],
            "exclamation" => ["!"],
            "question" => ["?"],
            "brackets" => ["[]"],
            "braces" => ["{}"],
            "too long" => ["Multimedia Content Production Multimedia Content Production Multimedia Content Production Multimediaa"]
        ];
    }

    public function setShortSuccessProvider(): array
    {
        return [
            "null" => [null],
            "MEIC" => ["MEIC"],
            "MEIC-A" => ["MEIC-A"],
            "length limit" => ["MEIC-A MEIC-A MEIC-A"]
        ];
    }

    public function setShortFailureProvider(): array
    {
        return [
            "only numbers" => ["123"],
            "not a string" => [123],
            "empty" => [""],
            "whitespace" => [" "],
            "too long" => ["MEIC-A MEIC-A MEIC-AA"]
        ];
    }

    public function setColorSuccessProvider(): array
    {
        return [
            "HEX" => ["#ffffff"],
            "null" => [null]
        ];
    }

    public function setColorFailureProvider(): array
    {
        return [
            "RGB" => ["rgb(255,255,255)"],
            "words" => ["white"],
            "only numbers" => ["123"],
            "not a string" => [123],
            "empty" => [""]
        ];
    }

    public function setYearSuccessProvider(): array
    {
        return [
            "valid format" => ["2021-2022"]
        ];
    }

    public function setYearFailureProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "invalid format" => ["21-22"],
            "only one year" => ["2021"]
        ];
    }

    public function setDataSuccessProvider(): array
    {
        return [
            ["same data" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => null, "endDate" => null, "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["different short" => ["name" => "Produção de Conteúdos Multimédia", "short" => "MCP", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => null, "endDate" => null, "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["different year" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2022-2023",
                "color" => "#ffffff", "startDate" => null, "endDate" => null, "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["different color" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#000000", "startDate" => null, "endDate" => null, "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["different start date" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => "2022-04-20 12:00:00", "endDate" => null, "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["different end date" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => null, "endDate" => "2022-04-20 12:00:00", "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["both start and end date" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => "2022-04-19 12:00:00", "endDate" => "2022-04-20 12:00:00", "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["different theme" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => null, "endDate" => null, "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => "dark"]],
            ["different isAdmin" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => null, "endDate" => null, "landingPage" => null, "isActive" => false,
                "isVisible" => false, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["different isAdmin" => ["name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => null, "endDate" => null, "landingPage" => null, "isActive" => true,
                "isVisible" => true, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => null]],
            ["all different" => ["name" => "Produção de Conteúdos Multimédia", "short" => "MCP", "year" => "2022-2023",
                "color" => "#000000", "startDate" => "2022-04-20 12:00:00", "endDate" => "2022-04-20 12:00:01", "landingPage" => null, "isActive" => false,
                "isVisible" => true, "roleHierarchy" => json_encode([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]]),
                "theme" => "dark"]]
        ];
    }

    public function setDataFailureProvider(): array
    {
        return [
            ["invalid name" => ["name" => "*!"]],
            ["invalid short" => ["short" => ""]],
            ["invalid color" => ["color" => "white"]],
            ["invalid year" => ["color" => "20-21"]],
            ["invalid start date" => ["startDate" => "2022-04-20"]],
            ["invalid end date" => ["endDate" => "2022-04-20"]],
            ["invalid start and end date" => ["startDate" => "2022-04-21", "endDate" => "2022-04-20"]]
        ];
    }

    public function dateTimeSuccessProvider(): array
    {
        return [
            "null" => [null],
            "valid format" => ["2022-04-20 20:43:00"]
        ];
    }

    public function dateTimeFailureProvider(): array
    {
        return [
            "only date" => ["2022-04-20"],
            "only time" => ["20:43:00"],
            "empty" => [""]
        ];
    }

    public function addCourseSuccessProvider(): array
    {
        return [
            "default" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "non-ASCII chars in name" => ["Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "null short" => ["Multimedia Content Production", null, "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "null color" => ["Multimedia Content Production", "MCP", "2021-2022", null, "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "null start date" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", null, "2022-05-01 00:00:00", false, false],
            "null end date" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", null, false, false],
            "valid start and end date" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-04-01 00:00:00", false, false],
            "not active, visible" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, true],
            "active, not visible" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", true, false],
            "active, visible" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", true, true]
        ];
    }

    public function addCourseFailureProvider(): array
    {
        return [
            "special chars in name" => ["Multimedia Content Production!", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "null name" => [null, "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "empty name" => ["", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "null year" => ["Multimedia Content Production", "MCP", null, "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "invalid year format" => ["Multimedia Content Production", "MCP", "21-22", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "empty year" => ["Multimedia Content Production", "MCP", "", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "RGB color" => ["Multimedia Content Production", "MCP", "2021-2022", "rgb(255, 255, 255)", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "named color" => ["Multimedia Content Production", "MCP", "2021-2022", "white", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "empty color" => ["Multimedia Content Production", "MCP", "2021-2022", "", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, false],
            "invalid start date format" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01", "2022-05-01 00:00:00", false, false],
            "empty start date" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "", "2022-05-01 00:00:00", false, false],
            "invalid end date format" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01", false, false],
            "empty end date" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "", false, false],
            "invalid start and end date" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-04-01 00:00:00", "2022-03-01 00:00:00", false, false],
            "null isActive" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", null, false],
            "null is Visible" => ["Multimedia Content Production", "MCP", "2021-2022", "#ffffff", "2022-03-01 00:00:00", "2022-05-01 00:00:00", false, null],
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function courseConstructor()
    {
        $course = new Course(123);
        $this->assertEquals(123, $course->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $id = intval(Core::database()->select(Course::TABLE_COURSE, ["name" => "Multimedia Content Production", "year" => "2021-2022"], "id"));
        $this->assertEquals($id, $course->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseName()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertEquals("Multimedia Content Production", $course->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getShort()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertEquals("MCP", $course->getShort());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getColor()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertEquals("#ffffff", $course->getColor());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getYear()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertEquals("2021-2022", $course->getYear());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getStartDate()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertNull($course->getStartDate());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getEndDate()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertNull($course->getEndDate());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLandingPage()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $page = Page::addPage($course->getId(), "Landing Page");
        $course->setLandingPage($page->getId());
        $this->assertEquals($page, $course->getLandingPage());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLandingPageNoPageDefined()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertNull($course->getLandingPage());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRolesHierarchy()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertEquals([["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]], $course->getRolesHierarchy());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTheme()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertNull($course->getTheme());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isActive()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertTrue($course->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isInactive()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, false, true);
        $this->assertFalse($course->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isVisible()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->assertTrue($course->isVisible());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isInvisible()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->assertFalse($course->isVisible());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->assertEquals(["id" => 1, "name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
            "color" => "#ffffff", "startDate" => null, "endDate" => null, "isActive" => true, "isVisible" => false,
            "landingPage" => null, "roleHierarchy" => [["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]],
            "theme" => null],
            $course->getData());
    }


    /**
     * @test
     * @dataProvider setCourseNameSuccessProvider
     * @throws Exception
     */
    public function setCourseNameSuccess(string $name)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setName($name);
        $this->assertEquals($name, $course->getName());
        $this->assertTrue(file_exists($course->getDataFolder(true, $name)));
    }

    /**
     * @test
     * @dataProvider setCourseNameFailureProvider
     * @throws Exception
     */
    public function setCourseNameFailure($name)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        try {
            $course->setName($name);
            $this->fail("Exception should have been thrown on 'setCourseNameFailure'");

        } catch (Exception|TypeError $error) {
            $this->assertEquals("Produção de Conteúdos Multimédia", $course->getName());
            $this->assertTrue(file_exists($course->getDataFolder(true, "Produção de Conteúdos Multimédia")));
            $this->assertEquals(1, Utils::getDirectorySize(COURSE_DATA_FOLDER));
        }
    }

    /**
     * @test
     * @dataProvider setShortSuccessProvider
     * @throws Exception
     */
    public function setShortSucess(?string $short)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setShort($short);
        $this->assertEquals($short, $course->getShort());
    }

    /**
     * @test
     * @dataProvider setShortFailureProvider
     * @throws Exception
     */
    public function setShortFailure($short)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->expectException(Exception::class);
        $course->setShort($short);
    }

    /**
     * @test
     * @dataProvider setColorSuccessProvider
     * @throws Exception
     */
    public function setColorSuccess(?string $color)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setColor($color);
        $this->assertEquals($color, $course->getColor());
    }

    /**
     * @test
     * @dataProvider setColorFailureProvider
     * @throws Exception
     */
    public function setColorFailure($color)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->expectException(Exception::class);
        $course->setColor($color);
    }

    /**
     * @test
     * @dataProvider setYearSuccessProvider
     * @throws Exception
     */
    public function setYearSuccess(?string $year)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setYear($year);
        $this->assertEquals($year, $course->getYear());
    }

    /**
     * @test
     * @dataProvider setYearFailureProvider
     * @throws Exception
     */
    public function setYearFailure($year)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->expectException(Exception::class);
        $course->setYear($year);
    }

    /**
     * @test
     * @dataProvider dateTimeSuccessProvider
     * @throws Exception
     */
    public function setStartDateSuccess(?string $startDate)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setStartDate($startDate);
        $this->assertEquals($startDate, $course->getStartDate());
    }

    /**
     * @test
     * @dataProvider dateTimeFailureProvider
     * @throws Exception
     */
    public function setStartDateFailure($startDate)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->expectException(Exception::class);
        $course->setStartDate($startDate);
    }

    /**
     * @test
     * @dataProvider dateTimeSuccessProvider
     * @throws Exception
     */
    public function setEndDateSuccess(?string $endDate)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setEndDate($endDate);
        $this->assertEquals($endDate, $course->getEndDate());
    }

    /**
     * @test
     * @dataProvider dateTimeFailureProvider
     * @throws Exception
     */
    public function setEndDateFailure($endDate)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->expectException(Exception::class);
        $course->setEndDate($endDate);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setLandingPageSuccess()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $page = Page::addPage($course->getId(), "Landing Page");

        $course->setLandingPage($page->getId());
        $this->assertEquals($page, $course->getLandingPage());

        $course->setLandingPage(null);
        $this->assertNull($course->getLandingPage());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setLandingPageFailure()
    {
        $course1 = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course2 = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2022-2023", "#ffffff",
            null, null, true, false);

        $page = Page::addPage($course1->getId(), "Landing Page");

        $this->expectException(Exception::class);
        try {
            $course2->setLandingPage($page->getId());
        } catch (Exception $e) {
            $this->expectException(Exception::class);
            $course1->setLandingPage(100);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setRolesHierarchy()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setRolesHierarchy([["name" => "Teacher"]]);
        $hierarchy = $course->getRolesHierarchy();
        $this->assertIsArray($hierarchy);
        $this->assertCount(1, $hierarchy);
        $this->assertEquals([["name" => "Teacher"]], $hierarchy);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setTheme()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setTheme("dark");
        $this->assertEquals("dark", $course->getTheme());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActive()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, false, false);
        $course->setActive(true);
        $this->assertTrue($course->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setVisible()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setVisible(true);
        $this->assertTrue($course->isVisible());
    }

    /**
     * @test
     * @dataProvider setDataSuccessProvider
     * @throws Exception
     */
    public function setDataSuccess(array $fieldValues)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setData($fieldValues);
        $fieldValues["id"] = $course->getId();
        $fieldValues["roleHierarchy"] = $course->getRolesHierarchy();
        $this->assertEquals($course->getData(), array_merge($fieldValues, ["id" => $course->getId()]));
    }

    /**
     * @test
     * @dataProvider setDataFailureProvider
     */
    public function setDataFailure(array $fieldValues)
    {
        try {
            $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
                null, null, true, false);
            $course->setData($fieldValues);
            $this->fail("Exception should have been thrown on 'setDataFailure'");

        } catch (Exception $e) {
            $course = new Course(1);
            $this->assertEquals(["id" => 1, "name" => "Produção de Conteúdos Multimédia", "short" => "PCM", "year" => "2021-2022",
                "color" => "#ffffff", "startDate" => null, "endDate" => null, "landingPage" => null, "isActive" => true,
                "isVisible" => false, "roleHierarchy" => [["name" => "Teacher"],["name" => "Student"],["name" => "Watcher"]],
                "theme" => null], $course->getData());
        }
    }

    /**
     * @test
     * @dataProvider setCourseNameSuccessProvider
     * @throws Exception
     */
    public function setDataNameSuccess(string $name)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course->setData(["name" => $name]);
        $this->assertEquals($name, $course->getName());
        $this->assertTrue(file_exists(COURSE_DATA_FOLDER . "/" . $course->getId() . "-" . Utils::strip($name, "_")));
    }

    /**
     * @test
     * @dataProvider setCourseNameFailureProvider
     * @throws Exception
     */
    public function setDataNameFailure($name)
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        try {
            $course->setData(["name" => $name]);
            $this->fail("Exception should have been thrown on 'setCourseNameFailure'");

        } catch (Exception|TypeError $error) {
            $this->assertEquals("Produção de Conteúdos Multimédia", $course->getName());
            $this->assertTrue(file_exists($course->getDataFolder(true, "Produção de Conteúdos Multimédia")));
            $this->assertEquals(1, Utils::getDirectorySize(COURSE_DATA_FOLDER));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setDataDuplicateNameAndYear()
    {
        Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $course = Course::addCourse("Multimedia Content Production", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        try {
            $course->setData(["name" => "Produção de Conteúdos Multimédia"]);
            $this->fail("Exception should have been thrown on 'setDataDuplicateNameAndYear'");

        } catch (PDOException $e) {
            $this->assertEquals("Multimedia Content Production", $course->getName());
            $this->assertTrue(file_exists($course->getDataFolder(true, "Multimedia Content Production")));
            $this->assertFalse(file_exists($course->getDataFolder(true, "Produção de Conteúdos Multimédia")));
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getCourseById()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->assertEquals($course, Course::getCourseById($course->getId()));
    }

    /**
     * @test
     */
    public function getCourseByIdCourseDoesntExist()
    {
        $this->assertNull(Course::getCourseById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseByNameAndYear()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
        $this->assertEquals($course, Course::getCourseByNameAndYear("Produção de Conteúdos Multimédia", "2021-2022"));
    }

    /**
     * @test
     */
    public function getCourseByNameAndYearCourseDoesntExist()
    {
        $this->assertNull(Course::getCourseByNameAndYear("Produção de Conteúdos Multimédia", "2021-2022"));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllCourses()
    {
        $course1 = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, true);
        $course2 = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#000000",
            null, null, false, false);

        $courses = Course::getCourses();
        $this->assertIsArray($courses);
        $this->assertCount(2, $courses);

        $keys = ["id", "name", "short", "color", "year", "startDate", "endDate", "landingPage", "theme", "roleHierarchy", "isActive", "isVisible"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courses as $i => $course) {
                $this->assertCount($nrKeys, array_keys($course));
                $this->assertArrayHasKey($key, $course);
                $this->assertEquals($course[$key], ${"course".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActiveCourses()
    {
        $course1 = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, true);
        Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#000000",
            null, null, false, false);

        $courses = Course::getCourses(true);
        $this->assertIsArray($courses);
        $this->assertCount(1, $courses);

        $keys = ["id", "name", "short", "color", "year", "startDate", "endDate", "landingPage", "theme", "roleHierarchy", "isActive", "isVisible"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courses as $course) {
                $this->assertCount($nrKeys, array_keys($course));
                $this->assertArrayHasKey($key, $course);
                $this->assertEquals($course[$key], $course1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInactiveCourses()
    {
        Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, true);
        $course2 = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#000000",
            null, null, false, false);

        $courses = Course::getCourses(false);
        $this->assertIsArray($courses);
        $this->assertCount(1, $courses);

        $keys = ["id", "name", "short", "color", "year", "startDate", "endDate", "landingPage", "theme", "roleHierarchy", "isActive", "isVisible"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courses as $course) {
                $this->assertCount($nrKeys, array_keys($course));
                $this->assertArrayHasKey($key, $course);
                $this->assertEquals($course[$key], $course2->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getVisibleCourses()
    {
        $course1 = Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, true);
        Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#000000",
            null, null, false, false);

        $courses = Course::getCourses(null, true);
        $this->assertIsArray($courses);
        $this->assertCount(1, $courses);

        $keys = ["id", "name", "short", "color", "year", "startDate", "endDate", "landingPage", "theme", "roleHierarchy", "isActive", "isVisible"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courses as $course) {
                $this->assertCount($nrKeys, array_keys($course));
                $this->assertArrayHasKey($key, $course);
                $this->assertEquals($course[$key], $course1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInvisibleCourses()
    {
        Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, true);
        $course2 = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#000000",
            null, null, false, false);

        $courses = Course::getCourses(null, false);
        $this->assertIsArray($courses);
        $this->assertCount(1, $courses);

        $keys = ["id", "name", "short", "color", "year", "startDate", "endDate", "landingPage", "theme", "roleHierarchy", "isActive", "isVisible"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courses as $course) {
                $this->assertCount($nrKeys, array_keys($course));
                $this->assertArrayHasKey($key, $course);
                $this->assertEquals($course[$key], $course2->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInactiveAndVisibleCourses()
    {
        Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, true);
        $course2 = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#000000",
            null, null, false, true);

        $courses = Course::getCourses(false, true);
        $this->assertIsArray($courses);
        $this->assertCount(1, $courses);

        $keys = ["id", "name", "short", "color", "year", "startDate", "endDate", "landingPage", "theme", "roleHierarchy", "isActive", "isVisible"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courses as $course) {
                $this->assertCount($nrKeys, array_keys($course));
                $this->assertArrayHasKey($key, $course);
                $this->assertEquals($course[$key], $course2->getData($key));
            }
        }
    }


    /**
     * @test
     * @dataProvider addCourseSuccessProvider
     * @throws Exception
     */
    public function addCourseSuccess(string $name, ?string $short, ?string $year, ?string $color, ?string $startDate,
                                     ?string $endDate, bool $isActive, bool $isVisible)
    {
        $course = Course::addCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible);

        // Check is added to database
        $courseDB = Core::database()->select(Course::TABLE_COURSE, ["id" => $course->getId()]);
        $courseData = ["id" => strval($course->getId()), "name" => $name, "short" => $short, "year" => $year,
            "color" => $color, "startDate" => $startDate, "endDate" => $endDate, "landingPage" => null, "isActive" => strval(+$isActive),
            "isVisible" => strval(+$isVisible), "roleHierarchy" => '[{"name":"Teacher"},{"name":"Student"},{"name":"Watcher"}]',
            "theme" => null];
        $this->assertEquals($courseData, $courseDB);

        // Check course data folder was created
        $dataFolder = COURSE_DATA_FOLDER . "/" . $course->getId() . "-" . Utils::strip($name, "_");
        $this->assertEquals($dataFolder, $course->getDataFolder());
        $this->assertTrue(file_exists($dataFolder));

        // Check current user was added to course and is a teacher
        $courseUsers = $course->getCourseUsers();
        $this->assertIsArray($courseUsers);
        $this->assertCount(1, $courseUsers);
        $this->assertTrue((new CourseUser($courseUsers[0]["id"], $course))->isTeacher());

        // Check modules were initialized in course
        $modules = Module::getModules();
        $courseModules = $course->getModules();
        $this->assertSameSize($modules, $courseModules);
        foreach ($courseModules as $i => $courseModule) {
            $this->assertFalse($courseModule["isEnabled"]);
            $this->assertEquals($modules[$i]["id"], $courseModule["id"]);
            $this->assertEquals($modules[$i]["version"], $courseModule["minModuleVersion"]);
            $this->assertNull($courseModule["maxModuleVersion"]);
        }

        // Check autogame
        $autogame = Core::database()->select(AutoGame::TABLE_AUTOGAME, ["course" => $course->getId()]);
        $this->assertFalse(boolval($autogame["isRunning"]));
        $this->assertEquals(10, $autogame["periodicityNumber"]);
        $this->assertEquals("Minutes", $autogame["periodicityTime"]);

        $this->assertTrue(file_exists(RuleSystem::getDataFolder($course->getId())));
        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/imported-functions/" . $course->getId()));
        $this->assertEquals(file_get_contents(AUTOGAME_FOLDER . "/imported-functions/defaults.py"), file_get_contents(AUTOGAME_FOLDER . "/imported-functions/" . $course->getId() . "/defaults.py"));
        $this->assertTrue(file_exists(AUTOGAME_FOLDER . "/config/config_" . $course->getId() . ".txt"));
        $this->assertEquals("", file_get_contents(AUTOGAME_FOLDER . "/config/config_" . $course->getId() . ".txt"));

        // Check logging
        $this->assertTrue(file_exists(LOGS_FOLDER . "/autogame_" . $course->getId() . ".txt"));
        $this->assertEquals("", file_get_contents(LOGS_FOLDER . "/autogame_" . $course->getId() . ".txt"));
    }

    /**
     * @test
     * @dataProvider addCourseFailureProvider
     */
    public function addCourseFailure($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible)
    {
        try {
            Course::addCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible);
            $this->fail("Exception should have been thrown on 'addCourseFailure'");

        } catch (Exception|TypeError $e) {
            $course = Core::database()->select(Course::TABLE_COURSE, ["name" => $name, "year" => $year]);
            $this->assertEmpty($course);
            $this->assertFalse(file_exists(COURSE_DATA_FOLDER));
            $this->assertFalse(file_exists(AUTOGAME_FOLDER . "/imported-functions/1"));
            $this->assertFalse(file_exists(AUTOGAME_FOLDER . "/config/config_1.txt"));
            $this->assertFalse(file_exists(LOGS_FOLDER));

            $roles = Core::database()->selectMultiple(Role::TABLE_ROLE);
            $this->assertEmpty($roles);

            $courseModules = Core::database()->selectMultiple(Module::TABLE_COURSE_MODULE);
            $this->assertEmpty($courseModules);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addCourseDuplicateNameAndYear()
    {
        Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $this->expectException(PDOException::class);
        Course::addCourse("Produção de Conteúdos Multimédia", "PCM", "2021-2022", "#ffffff",
            null, null, true, false);
    }


    /**
     * @test
     * @dataProvider addCourseSuccessProvider
     * @throws Exception
     */
    public function editCourseSuccess(string $name, ?string $short, ?string $year, ?string $color, ?string $startDate,
                               ?string $endDate, bool $isActive, bool $isVisible)
    {
        $course = Course::addCourse("Computação Móvel e Ubíqua", "CMU", "2020-2021", "#000000",
            null, null, true, false);
        $course->editCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible);

        // Check is updated on database
        $courseDB = Core::database()->select(Course::TABLE_COURSE, ["id" => $course->getId()]);
        $courseData = array("id" => strval($course->getId()), "name" => $name, "short" => $short, "year" => $year, "color" => $color,
            "startDate" => $startDate, "endDate" => $endDate, "landingPage" => null,
            "isActive" => strval(+$isActive), "isVisible" => strval(+$isVisible), "roleHierarchy" => '[{"name":"Teacher"},{"name":"Student"},{"name":"Watcher"}]',
            "theme" => null);
        $this->assertEquals($courseData, $courseDB);

        // Check course data folder was updated
        $dataFolder = COURSE_DATA_FOLDER . "/" . $course->getId() . "-" . Utils::strip($name, "_");
        $this->assertEquals($dataFolder, $course->getDataFolder());
        $this->assertTrue(file_exists($dataFolder));
    }

    /**
     * @test
     * @dataProvider addCourseFailureProvider
     * @throws Exception
     */
    public function editCourseFailure($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible)
    {
        $course = Course::addCourse("Computação Móvel e Ubíqua", "CMU", "2020-2021", "#000000",
            null, null, true, false);
        try {
            $course->editCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible);
            $this->fail("Exception should have been thrown on 'editCourseFailure'");

        } catch (Exception|TypeError $e) {
            // Check course didn't change on database
            $courseDB = Core::database()->select(Course::TABLE_COURSE, ["id" => $course->getId()]);
            $courseData = array("id" => strval($course->getId()), "name" => "Computação Móvel e Ubíqua", "short" => "CMU",
                "year" => "2020-2021", "color" => "#000000", "startDate" => null, "endDate" => null, "landingPage" => null,
                "isActive" => "1", "isVisible" => "0", "roleHierarchy" => '[{"name":"Teacher"},{"name":"Student"},{"name":"Watcher"}]',
                "theme" => null);
            $this->assertEquals($courseData, $courseDB);

            // Check course data folder didn't change
            $dataFolder = COURSE_DATA_FOLDER . "/1-Computacao_Movel_e_Ubiqua";
            $this->assertEquals($dataFolder, $course->getDataFolder());
            $this->assertTrue(file_exists($dataFolder));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editCourseDuplicateNameAndYear()
    {
        Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $course = Course::addCourse("Computação Móvel e Ubíqua", "CMU", "2020-2021", "#000000",
            null, null, true, false);
        $this->expectException(PDOException::class);
        $course->editCourse("Produção de Conteúdos Multimédia", "CMU", "2021-2022", "#000000", null,
        null, true, false);
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteCourse()
    {
        Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $id = Course::addCourse("Computação Móvel e Ubíqua", "CMU", "2020-2021", "#000000",
            null, null, true, false)->getId();
        Course::deleteCourse($id);

        $courses = Course::getCourses();
        $this->assertIsArray($courses);
        $this->assertCount(1, $courses);
        $this->assertEquals("Produção de Conteúdos Multimédia", $courses[0]["name"]);

        $this->assertFalse(file_exists(COURSE_DATA_FOLDER . "/" . $id));
        $this->assertFalse(file_exists(AUTOGAME_FOLDER . "/imported-functions/" . $id));
        $this->assertFalse(file_exists(AUTOGAME_FOLDER . "/config/config_" . $id . ".txt"));
        $this->assertFalse(file_exists(LOGS_FOLDER . "/autogame_" . $id . ".txt"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteCourseInexistentCourse()
    {
        Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        try {
            Course::deleteCourse(2);
            $this->fail("Exception should have been thrown on 'deleteCourseInexistentCourse'");

        } catch (Exception $e) {
            $courses = Course::getCourses();
            $this->assertIsArray($courses);
            $this->assertCount(1, $courses);
            $this->assertEquals("Produção de Conteúdos Multimédia", $courses[0]["name"]);
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function courseExists()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $this->assertTrue($course->exists());
    }

    /**
     * @test
     */
    public function courseDoesntExist()
    {
        $course = new Course(1);
        $this->assertFalse($course->exists());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserById()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user = Core::getLoggedUser();
        $this->assertEquals(new CourseUser($user->getId(), $course), $course->getCourseUserById($user->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByIdUserNotInCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $this->assertNull($course->getCourseUserById($user->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsername()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user = Core::getLoggedUser();
        $this->assertEquals(new CourseUser($user->getId(), $course), $course->getCourseUserByUsername($user->getUsername()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsernameAuthServiceDoesntExist()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user = Core::getLoggedUser();
        $this->expectException(Exception::class);
        $course->getCourseUserByUsername($user->getUsername(), "auth_service");
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsernameUserNotInCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $this->assertNull($course->getCourseUserByUsername("username"));
        $this->assertNull($course->getCourseUserByUsername("username", AuthService::FENIX));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsernameMultipleUsersWithAuthService()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist123456", AuthService::GOOGLE, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $course->addUserToCourse($user2->getId());
        $this->assertEquals(new CourseUser($user1->getId(), $course), $course->getCourseUserByUsername($user1->getUsername(), AuthService::FENIX));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsernameMultipleUsersWithoutAuthService()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist123456", AuthService::GOOGLE, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $course->addUserToCourse($user2->getId());
        $this->expectException(Exception::class);
        $course->getCourseUserByUsername($user1->getUsername());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByEmail()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user = Core::getLoggedUser();
        $this->assertEquals(new CourseUser($user->getId(), $course), $course->getCourseUserByEmail($user->getEmail()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByEmailUserNotInCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $this->assertNull($course->getCourseUserByEmail($user->getEmail()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByStudentNumber()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user = Core::getLoggedUser();
        $this->assertEquals(new CourseUser($user->getId(), $course), $course->getCourseUserByStudentNumber($user->getStudentNumber()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByStudentNumberUserNotInCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $this->assertNull($course->getCourseUserByStudentNumber($user->getStudentNumber()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllCourseUsers()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $courseUser1 = $course->getCourseUserById($user1->getId());
        $courseUser2 = $course->addUserToCourse($user2->getId());
        $courseUser3 = $course->addUserToCourse($user3->getId());
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsers();
        $this->assertIsArray($courseUsers);
        $this->assertCount(3, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $i => $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], ${"user".($i+1)}->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], ${"courseUser".($i+1)}->getData("isActive"));
                else $this->assertEquals($courseUser[$key], ${"courseUser".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActiveCourseUsers()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $courseUser1 = $course->getCourseUserById($user1->getId());
        $courseUser2 = $course->addUserToCourse($user2->getId());
        $courseUser3 = $course->addUserToCourse($user3->getId());
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsers(true);
        $this->assertIsArray($courseUsers);
        $this->assertCount(2, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $i => $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], ${"user".($i+1)}->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], ${"courseUser".($i+1)}->getData("isActive"));
                else $this->assertEquals($courseUser[$key], ${"courseUser".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInactiveCourseUsers()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId());
        $courseUser3 = $course->addUserToCourse($user3->getId());
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsers(false);
        $this->assertIsArray($courseUsers);
        $this->assertCount(1, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], $user3->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], $courseUser3->getData("isActive"));
                else $this->assertEquals($courseUser[$key], $courseUser3->getData($key));
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getCourseUsersWithRoleName()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $courseUser2 = $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsersWithRole(null, "Student");
        $this->assertIsArray($courseUsers);
        $this->assertCount(1, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], $user2->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], $courseUser2->getData("isActive"));
                else $this->assertEquals($courseUser[$key], $courseUser2->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUsersWithRoleID()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsersWithRole(null, null, Role::getRoleId("Watcher", $course->getId()));
        $this->assertIsArray($courseUsers);
        $this->assertCount(1, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], $user3->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], $courseUser3->getData("isActive"));
                else $this->assertEquals($courseUser[$key], $courseUser3->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUsersWithRoleNameRoleDoesntExist()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsersWithRole(null, "role_name");
        $this->assertIsArray($courseUsers);
        $this->assertEmpty($courseUsers);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUsersWithRoleIDRoleDoesntExist()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsersWithRole(null, null, 100);
        $this->assertIsArray($courseUsers);
        $this->assertEmpty($courseUsers);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActiveCourseUsersWithRoleName()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $courseUser2 = $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsersWithRole(true, "Student");
        $this->assertIsArray($courseUsers);
        $this->assertCount(1, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], $user2->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], $courseUser2->getData("isActive"));
                else $this->assertEquals($courseUser[$key], $courseUser2->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActiveCourseUsersWithRoleID()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $courseUser2 = $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsersWithRole(true, null, Role::getRoleId("Student", $course->getId()));
        $this->assertIsArray($courseUsers);
        $this->assertCount(1, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], $user2->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], $courseUser2->getData("isActive"));
                else $this->assertEquals($courseUser[$key], $courseUser2->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInactiveCourseUsersWithRoleName()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsersWithRole(false, "Watcher");
        $this->assertIsArray($courseUsers);
        $this->assertCount(1, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], $user3->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], $courseUser3->getData("isActive"));
                else $this->assertEquals($courseUser[$key], $courseUser3->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInactiveCourseUsersWithRoleID()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $courseUsers = $course->getCourseUsersWithRole(false, null, Role::getRoleId("Watcher", $course->getId()));
        $this->assertIsArray($courseUsers);
        $this->assertCount(1, $courseUsers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($courseUsers as $courseUser) {
                $this->assertCount($nrKeys, array_keys($courseUser));
                $this->assertArrayHasKey($key, $courseUser);
                if ($key === "isActive") $this->assertEquals($courseUser[$key], $user3->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($courseUser[$key], $courseUser3->getData("isActive"));
                else $this->assertEquals($courseUser[$key], $courseUser3->getData($key));
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllStudents()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $courseUser2 = $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $students = $course->getStudents();
        $this->assertIsArray($students);
        $this->assertCount(1, $students);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($students as $student) {
                $this->assertCount($nrKeys, array_keys($student));
                $this->assertArrayHasKey($key, $student);
                if ($key === "isActive") $this->assertEquals($student[$key], $user2->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($student[$key], $courseUser2->getData("isActive"));
                else $this->assertEquals($student[$key], $courseUser2->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActiveStudents()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $courseUser2 = $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $students = $course->getStudents(true);
        $this->assertIsArray($students);
        $this->assertCount(1, $students);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($students as $student) {
                $this->assertCount($nrKeys, array_keys($student));
                $this->assertArrayHasKey($key, $student);
                if ($key === "isActive") $this->assertEquals($student[$key], $user2->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($student[$key], $courseUser2->getData("isActive"));
                else $this->assertEquals($student[$key], $courseUser2->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInactiveStudents()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $students = $course->getStudents(false);
        $this->assertIsArray($students);
        $this->assertEmpty($students);
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllTeachers()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $courseUser1 = $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $teachers = $course->getTeachers();
        $this->assertIsArray($teachers);
        $this->assertCount(1, $teachers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($teachers as $teacher) {
                $this->assertCount($nrKeys, array_keys($teacher));
                $this->assertArrayHasKey($key, $teacher);
                if ($key === "isActive") $this->assertEquals($teacher[$key], $user1->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($teacher[$key], $courseUser1->getData("isActive"));
                else $this->assertEquals($teacher[$key], $courseUser1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActiveTeachers()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $courseUser1 = $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $teachers = $course->getTeachers(true);
        $this->assertIsArray($teachers);
        $this->assertCount(1, $teachers);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive", "lastActivity", "isActiveInCourse"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($teachers as $teacher) {
                $this->assertCount($nrKeys, array_keys($teacher));
                $this->assertArrayHasKey($key, $teacher);
                if ($key === "isActive") $this->assertEquals($teacher[$key], $user1->getData($key));
                else if ($key === "isActiveInCourse") $this->assertEquals($teacher[$key], $courseUser1->getData("isActive"));
                else $this->assertEquals($teacher[$key], $courseUser1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInactiveTeachers()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = Core::getLoggedUser();
        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->getCourseUserById($user1->getId());
        $course->addUserToCourse($user2->getId(), "Student");
        $courseUser3 = $course->addUserToCourse($user3->getId(), "Watcher");
        $courseUser3->setActive(false);

        $teachers = $course->getTeachers(false);
        $this->assertIsArray($teachers);
        $this->assertEmpty($teachers);
    }


    /**
     * @test
     * @throws Exception
     */
    public function getUsersNotInCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $courseUser2 = $course->addUserToCourse($user2->getId(), "Watcher");
        $courseUser2->setActive(false);

        $usersNotInCourse = $course->getUsersNotInCourse();
        $this->assertIsArray($usersNotInCourse);
        $this->assertCount(1, $usersNotInCourse);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($usersNotInCourse as $user) {
                $this->assertCount($nrKeys, array_keys($user));
                $this->assertArrayHasKey($key, $user);
                $this->assertEquals($user[$key], $user1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActiveUsersNotInCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user1 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, false);

        $usersNotInCourse = $course->getUsersNotInCourse(true);
        $this->assertIsArray($usersNotInCourse);
        $this->assertCount(1, $usersNotInCourse);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($usersNotInCourse as $user) {
                $this->assertCount($nrKeys, array_keys($user));
                $this->assertArrayHasKey($key, $user);
                $this->assertEquals($user[$key], $user1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getInactiveUsersNotInCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user2 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, false);

        $usersNotInCourse = $course->getUsersNotInCourse(false);
        $this->assertIsArray($usersNotInCourse);
        $this->assertCount(1, $usersNotInCourse);

        $keys = ["id", "name", "username", "auth_service", "lastLogin", "email", "studentNumber", "nickname", "major", "isAdmin", "isActive"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($usersNotInCourse as $user) {
                $this->assertCount($nrKeys, array_keys($user));
                $this->assertArrayHasKey($key, $user);
                $this->assertEquals($user[$key], $user2->getData($key));
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function addUserToCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);

        $course->addUserToCourse($user2->getId(), "Student");

        $courseUsers = $course->getCourseUsers();
        $this->assertIsArray($courseUsers);
        $this->assertCount(2, $courseUsers);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeUserFromCourse()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);

        $user2 = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user3 = User::addUser("Julia Smith Doe", "ist123", AuthService::FENIX, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);

        $course->addUserToCourse($user2->getId(), "Student");
        $course->addUserToCourse($user3->getId(), "Teacher");

        $courseUsers = $course->getCourseUsers();
        $this->assertIsArray($courseUsers);
        $this->assertCount(3, $courseUsers);

        $course->removeUserFromCourse($user2->getId());

        $courseUsers = $course->getCourseUsers();
        $this->assertIsArray($courseUsers);
        $this->assertCount(2, $courseUsers);
    }


    /**
     * @test
     * @throws Exception
     */
    public function transformURL()
    {
        $course = Course::addCourse("Produção de Conteúdos Multimédia", "MCP", "2021-2022", "#000000",
            null, null, true, false);
        $courseDataFolder = $course->getDataFolder(false);

        // absolute --> relative
        $this->assertEquals("file.txt", Course::transformURL(API_URL . "/" . $courseDataFolder . "/file.txt", "relative", $course->getId()));
        $this->assertEquals("file.txt", Course::transformURL("file.txt", "relative", $course->getId()));
        $this->assertEquals("dir/dir1/file.txt", Course::transformURL(API_URL . "/" . $courseDataFolder . "/dir/dir1/file.txt", "relative", $course->getId()));
        $this->assertEquals("dir/dir1/file.txt", Course::transformURL("dir/dir1/file.txt", "relative", $course->getId()));
        $this->assertEquals("https://www.google.com", Course::transformURL("https://www.google.com", "relative", $course->getId()));

        // relative --> absolute
        $this->assertEquals(API_URL . "/" . $courseDataFolder . "/file.txt", Course::transformURL("file.txt", "absolute", $course->getId()));
        $this->assertEquals(API_URL . "/" . $courseDataFolder . "/file.txt", Course::transformURL(API_URL . "/" . $courseDataFolder . "/file.txt", "absolute", $course->getId()));
        $this->assertEquals(API_URL . "/" . $courseDataFolder . "/dir/dir1/file.txt", Course::transformURL("dir/dir1/file.txt", "absolute", $course->getId()));
        $this->assertEquals(API_URL . "/" . $courseDataFolder . "/dir/dir1/file.txt", Course::transformURL(API_URL . "/" . $courseDataFolder . "/dir/dir1/file.txt", "absolute", $course->getId()));
        $this->assertEquals("https://www.google.com", Course::transformURL("https://www.google.com", "absolute", $course->getId()));
    }
}
