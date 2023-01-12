<?php
namespace GameCourse\Module\Streaks;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Awards\AwardType;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
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
class StreakTest extends TestCase
{
    private $courseId;

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
        $this->courseId = $course->getId();

        // Enable Streaks module
        (new Awards($course))->setEnabled(true);
        $streaks = new Streaks($course);
        $streaks->setEnabled(true);
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
        if (Core::database()->tableExists(Streak::TABLE_STREAK)) TestingUtils::resetAutoIncrement([Streak::TABLE_STREAK]);
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

    public function streakNameSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Streak Name"],
            "non-ASCII characters" => ["Stréak Name"],
            "numbers" => ["Streak123"],
            "parenthesis" => ["Streak Name (Copy)"],
            "hyphen" => ["Streak-Name"],
            "underscore" => ["Streak_Name"],
            "ampersand" => ["Streak & Name"],
            "trimmed" => [" This is some incredibly humongous streak nameeeeee "],
            "length limit" => ["This is some incredibly humongous streak nameeeeee"]
        ];
    }

    public function streakNameFailureProvider(): array
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
            "too long" => ["This is some incredibly humongous streak nameeeeeee"]
        ];
    }


    public function streakDescriptionSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Streak Description"],
            "non-ASCII characters" => ["Stréak Description"],
            "numbers" => ["Streak Description 123"],
            "parenthesis" => ["Streak Description (Copy)"],
            "hyphen" => ["Streak-Description"],
            "underscore" => ["Streak_Description"],
            "ampersand" => ["Streak & Description"],
            "trimmed" => [" This is some incredibly humongous streak description This is some incredibly humongous badge description This is some incredibly humongous badge descr "],
            "length limit" => ["This is some incredibly humongous streak description This is some incredibly humongous badge description This is some incredibly humongous badge descr"]
        ];
    }

    public function streakDescriptionFailureProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "whitespace" => [" "],
            "only numbers" => ["123"],
            "too long" => ["This is some incredibly humongous streak description This is some incredibly humongous badge description This is some incredibly humongous badge descri"]
        ];
    }


    public function streakColorSuccessProvider(): array
    {
        return [
            "null" => [null],
            "HEX" => ["#ffffff"],
            "trimmed" => [" #ffffff "]
        ];
    }

    public function streakColorFailureProvider(): array
    {
        return [
            "empty" => [""],
            "whitespace" => [" "],
            "RGB" => ["rgb(255,255,255)"]
        ];
    }


    public function streakSuccessProvider(): array
    {
        return [
            "default" => ["Streak Name", "Perform action", null, 10, null, null, 100, null, false, true, false, false, false],
            "with color" => ["Streak Name", "Perform action", "#ffffff", 10, null, null, 100, null, false, true, false, false, false],
            "tokens" => ["Streak Name", "Perform action", null, 10, null, null, 100, 50, false, true, false, false, false],
            "periodic" => ["Streak Name", "Perform action", null, 10, 5, "Days", 100, null, false, false, true, false, false],
            "at most" => ["Streak Name", "Perform action", null, 10, 5, "Days", 100, null, false, false, true, true, false],
            "repeatable" => ["Streak Name", "Perform action", null, 10, null, null, 100, null, true, true, false, false, false]
        ];
    }

    public function streakFailureProvider(): array
    {
        return [
            "invalid name" => [null, "Perform action", null, 10, null, null, 100, null, false, true, false, false, false],
            "invalid description" => ["Streak Name", null, null, 10, null, null, 100, null, false, true, false, false, false],
            "invalid color"  => ["Streak Name", "Perform action", "white", 10, null, null, 100, null, false, true, false, false, false]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // Constructor

    /**
     * @test
     */
    public function streakConstructor()
    {
        $streak = new Streak(123);
        $this->assertEquals(123, $streak->getId());
    }


    // Getters

    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $id = intval(Core::database()->select(Streak::TABLE_STREAK, ["name" => "Streak"], "id"));
        $this->assertEquals($id, $streak->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals($this->courseId, $streak->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getStreakName()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals("Streak", $streak->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDescription()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals("Perform action", $streak->getDescription());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getColor()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertNull($streak->getColor());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getStreakCount()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals(10, $streak->getCount());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPeriodicity()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertNull($streak->getPeriodicity());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPeriodicityTime()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertNull($streak->getPeriodicityTime());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getReward()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals(100, $streak->getReward());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTokens()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertNull($streak->getTokens());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getImage()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals("<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"currentColor\">
    <path fill-rule=\"evenodd\" d=\"M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152-.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z\" clip-rule=\"evenodd\" />
</svg>", base64_decode(preg_replace('/^data:.*\/.*?;base64,/i', '', $streak->getImage())));
    }

    /**
     * @test
     * @throws Exception
     */
    public function isRepeatable()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, true, true, false,
            false, false);
        $this->assertTrue($streak->isRepeatable());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotRepeatable()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertFalse($streak->isRepeatable());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isCount()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertTrue($streak->isCount());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotCount()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, false, false,
            false, false);
        $this->assertFalse($streak->isCount());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isPeriodic()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, true,
            false, false);
        $this->assertTrue($streak->isPeriodic());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotPeriodic()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertFalse($streak->isPeriodic());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isAtMost()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            true, false);
        $this->assertTrue($streak->isAtMost());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotAtMost()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertFalse($streak->isAtMost());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isExtra()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, true);
        $this->assertTrue($streak->isExtra());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotExtra()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertFalse($streak->isExtra());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isActive()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, true, true, false,
            false, false);
        $this->assertTrue($streak->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isInactive()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setActive(false);
        $this->assertFalse($streak->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals(["id" => 1, "course" => $this->courseId, "name" => "Streak", "description" => "Perform action",
            "color" => null, "count" => 10, "periodicity" => null, "periodicityTime" => null, "reward" => 100, "tokens" => null,
            "isRepeatable" => false, "isCount" => true, "isPeriodic" => false, "isAtMost" => false, "isExtra" => false,
            "isActive" => true, "rule" => $streak->getRule()->getId()], $streak->getData());
    }


    // Setters

    /**
     * @test
     * @dataProvider streakNameSuccessProvider
     * @throws Exception
     */
    public function setStreakNameSuccess(string $name)
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setName($name);
        $name = trim($name);
        $this->assertEquals($name, $streak->getName());

        $this->assertTrue(file_exists($streak->getDataFolder(true, $name)));
        $this->assertFalse(file_exists($streak->getDataFolder(true, "Streak")));

        $this->assertEquals($name, $streak->getRule()->getName());
        $this->assertEquals("award_streak(target, \"" . $name . "\", progress)", $streak->getRule()->getThen());
    }

    /**
     * @test
     * @dataProvider streakNameFailureProvider
     * @throws Exception
     */
    public function setStreakNameFailure($name)
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->setName($name);
            $this->fail("Error should have been thrown on 'setStreakNameFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Streak", $streak->getName());
            $this->assertTrue(file_exists($streak->getDataFolder(true, "Streak")));
            $this->assertEquals("Streak", $streak->getRule()->getName());
            $this->assertEquals("award_streak(target, \"Streak\", progress)", $streak->getRule()->getThen());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setStreakNameDuplicateName()
    {
        $streak1 = Streak::addStreak($this->courseId, "Streak1", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak2 = Streak::addStreak($this->courseId, "Streak2", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak2->setName($streak1->getName());
            $this->fail("Error should have been thrown on 'setStreakNameDuplicateName'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Streak2", $streak2->getName());
            $this->assertTrue(file_exists($streak2->getDataFolder(true, "Streak2")));
            $this->assertEquals("Streak2", $streak2->getRule()->getName());
            $this->assertEquals("award_streak(target, \"Streak2\", progress)", $streak2->getRule()->getThen());
        }
    }

    /**
     * @test
     * @dataProvider streakDescriptionSuccessProvider
     * @throws Exception
     */
    public function setDescriptionSuccess(string $description)
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setDescription($description);
        $description = trim($description);
        $this->assertEquals($description, $streak->getDescription());

        $this->assertEquals($description, $streak->getRule()->getDescription());
    }

    /**
     * @test
     * @dataProvider streakDescriptionFailureProvider
     * @throws Exception
     */
    public function setDescriptionFailure($description)
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->setDescription($description);
            $this->fail("Error should have been thrown on 'setDescriptionFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Perform action", $streak->getDescription());
            $this->assertEquals("Perform action", $streak->getRule()->getDescription());
        }
    }

    /**
     * @test
     * @dataProvider streakColorSuccessProvider
     * @throws Exception
     */
    public function setColorSuccess(?string $color)
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setColor($color);
        $this->assertEquals(trim($color), $streak->getColor());
    }

    /**
     * @test
     * @dataProvider streakColorFailureProvider
     * @throws Exception
     */
    public function setColorFailure($color)
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->setColor($color);
            $this->fail("Error should have been thrown on 'streakColorFailureProvider'");

        } catch (Exception|TypeError $e) {
            $this->assertNull($streak->getColor());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCountSuccess()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setCount(100);
        $this->assertEquals(100, $streak->getCount());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCountFailure()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->setCount(-10);
            $this->fail("Error should have been thrown on 'setCountFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals(10, $streak->getCount());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPeriodicitySuccess()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setPeriodicity(5);
        $this->assertEquals(5, $streak->getPeriodicity());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPeriodicityFailure()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->setPeriodicity(-10);
            $this->fail("Error should have been thrown on 'setPeriodicityFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertNull($streak->getPeriodicity());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPeriodicityTime()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setPeriodicityTime("Days");
        $this->assertEquals("Days", $streak->getPeriodicityTime());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setRewardSuccess()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setReward(500);
        $this->assertEquals(500, $streak->getReward());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setRewardFailure()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->setReward(-100);
            $this->fail("Error should have been thrown on 'setRewardFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals(100, $streak->getReward());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setTokensSuccess()
    {
        $virtualCurrency = (new Course($this->courseId))->getModuleById(VirtualCurrency::ID);
        $virtualCurrency->setEnabled(true);
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setTokens(50);
        $this->assertEquals(50, $streak->getTokens());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setTokensFailure()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->setTokens(-50);
            $this->fail("Error should have been thrown on 'setTokensFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertNull($streak->getTokens());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setRepeatable()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setRepeatable(true);
        $this->assertTrue($streak->isRepeatable());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotRepeatable()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, true, true, false,
            false, false);
        $streak->setRepeatable(false);
        $this->assertFalse($streak->isRepeatable());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setIsCount()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, false, false,
            false, false);
        $streak->setIsCount(true);
        $this->assertTrue($streak->isCount());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotCount()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setIsCount(false);
        $this->assertFalse($streak->isCount());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPeriodic()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, false, false,
            false, false);
        $streak->setPeriodic(true);
        $this->assertTrue($streak->isPeriodic());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotPeriodic()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, false, true,
            false, false);
        $streak->setPeriodic(false);
        $this->assertFalse($streak->isPeriodic());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setAtMost()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, false, false,
            false, false);
        $streak->setIsAtMost(true);
        $this->assertTrue($streak->isAtMost());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotAtNotMost()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, false, false,
            true, false);
        $streak->setIsAtMost(false);
        $this->assertFalse($streak->isAtMost());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setExtra()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);

        $xpLevels = new XPLevels(new Course($this->courseId));
        $xpLevels->setEnabled(true);
        $xpLevels->updateMaxExtraCredit(1000);

        $streaksModule = new Streaks(new Course($this->courseId));
        $streaksModule->updateMaxExtraCredit(1000);

        $streak->setExtra(true);
        $this->assertTrue($streak->isExtra());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotExtra()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, true);
        $streak->setExtra(false);
        $this->assertFalse($streak->isExtra());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setExtraNoExtraCreditAvailable()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);

        // No extra credit available
        try {
            $streak->setExtra(true);
            $this->fail("Error should have been thrown on 'setExtraNoExtraCreditAvailable'");

        } catch (Exception $e) {
            $this->assertFalse($streak->isExtra());

            // No streak extra credit available
            $xpLevels = new XPLevels(new Course($this->courseId));
            $xpLevels->setEnabled(true);
            $xpLevels->updateMaxExtraCredit(1000);

            try {
                $streak->setExtra(true);
                $this->fail("Error should have been thrown on 'setExtraNoExtraCreditAvailable'");

            } catch (Exception $e) {
                $this->assertFalse($streak->isExtra());
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActive()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setActive(false);
        $streak->setActive(true);
        $this->assertTrue($streak->isActive());
        $this->assertTrue($streak->getRule()->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactive()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->setActive(false);
        $this->assertFalse($streak->isActive());
        $this->assertFalse($streak->getRule()->isActive());
    }


    // General

    /**
     * @test
     * @throws Exception
     */
    public function getStreakById()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals($streak, Streak::getStreakById($streak->getId()));
    }

    /**
     * @test
     */
    public function getStreakByIdStreakDoesntExist()
    {
        $this->assertNull(Streak::getStreakById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getStreakByName()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertEquals($streak, Streak::getStreakByName($this->courseId, "Streak"));
    }

    /**
     * @test
     */
    public function getStreakByNameStreakDoesntExist()
    {
        $this->assertNull(Streak::getStreakByName($this->courseId, "Streak"));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllStreaks()
    {
        $streak1 = Streak::addStreak($this->courseId, "Streak1", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak2 = Streak::addStreak($this->courseId, "Streak2", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);

        $streaks = Streak::getStreaks($this->courseId);
        $this->assertIsArray($streaks);
        $this->assertCount(2, $streaks);

        $keys = ["id", "course", "name", "description", "color", "count", "periodicity", "periodicityTime", "reward",
            "tokens", "isRepeatable", "isCount", "isPeriodic", "isAtMost", "isExtra", "isActive", "rule", "image"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($streaks as $i => $streak) {
                $this->assertCount($nrKeys, array_keys($streak));
                $this->assertArrayHasKey($key, $streak);
                if ($key == "image") $this->assertEquals($streak[$key], ${"streak".($i+1)}->getImage());
                else $this->assertEquals($streak[$key], ${"streak".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllActiveStreaks()
    {
        $streak1 = Streak::addStreak($this->courseId, "Streak1", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak2 = Streak::addStreak($this->courseId, "Streak2", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak2->setActive(false);

        $streaks = Streak::getStreaks($this->courseId, true);
        $this->assertIsArray($streaks);
        $this->assertCount(1, $streaks);

        $keys = ["id", "course", "name", "description", "color", "count", "periodicity", "periodicityTime", "reward",
            "tokens", "isRepeatable", "isCount", "isPeriodic", "isAtMost", "isExtra", "isActive", "rule", "image"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($streaks as $streak) {
                $this->assertCount($nrKeys, array_keys($streak));
                $this->assertArrayHasKey($key, $streak);
                if ($key == "image") $this->assertEquals($streak[$key], $streak1->getImage());
                else $this->assertEquals($streak[$key], $streak1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllInactiveStreaks()
    {
        $streak1 = Streak::addStreak($this->courseId, "Streak1", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak2 = Streak::addStreak($this->courseId, "Streak2", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak2->setActive(false);

        $streaks = Streak::getStreaks($this->courseId, false);
        $this->assertIsArray($streaks);
        $this->assertCount(1, $streaks);

        $keys = ["id", "course", "name", "description", "color", "count", "periodicity", "periodicityTime", "reward",
            "tokens", "isRepeatable", "isCount", "isPeriodic", "isAtMost", "isExtra", "isActive", "rule", "image"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($streaks as $streak) {
                $this->assertCount($nrKeys, array_keys($streak));
                $this->assertArrayHasKey($key, $streak);
                if ($key == "image") $this->assertEquals($streak[$key], $streak2->getImage());
                else $this->assertEquals($streak[$key], $streak2->getData($key));
            }
        }
    }


    // Streak Manipulation

    /**
     * @test
     * @dataProvider streakSuccessProvider
     * @throws Exception
     */
    public function addStreakSuccess(string $name, string $description, ?string $color, int $count, ?int $periodicity,
                                     ?string $periodicityTime, int $reward, ?int $tokens, bool $isRepeatable, bool $isCount,
                                     bool $isPeriodic, bool $isAtMost, bool $isExtra)
    {
        $streak = Streak::addStreak($this->courseId, $name, $description, $color, $count, $periodicity, $periodicityTime,
            $reward, $tokens, $isRepeatable, $isCount, $isPeriodic, $isAtMost, $isExtra);

        // Check is added to database
        $streakDB = Streak::getStreaks($this->courseId)[0];
        $streakInfo = $streak->getData();
        $streakInfo["image"] = $streak->getImage();
        $this->assertEquals($streakInfo, $streakDB);

        // Check data folder was created
        $this->assertTrue(file_exists($streak->getDataFolder()));

        // Check rule was created
        $rule = $streak->getRule();
        $this->assertTrue($rule->exists());
        $this->assertEquals($this->trim("rule: $name
tags: 
# $description

	when:
		progress = None # COMPLETE THIS: get student progress in streak
		len(progress) > 0

	then:
		award_streak(target, \"$name\", progress)" . (!is_null($tokens) ? "\n\t\taward_tokens_type(target, \"streak\", $tokens, \"$name\", progress)" : "")), $this->trim($rule->getText()));
    }

    /**
     * @test
     * @dataProvider streakFailureProvider
     * @throws Exception
     */
    public function addStreakFailure($name, $description, $color, $count, $periodicity, $periodicityTime, $reward, $tokens,
                                     $isRepeatable, $isCount, $isPeriodic, $isAtMost, $isExtra)
    {
        try {
            Streak::addStreak($this->courseId, $name, $description, $color, $count, $periodicity, $periodicityTime,
                $reward, $tokens, $isRepeatable, $isCount, $isPeriodic, $isAtMost, $isExtra);
            $this->fail("Error should have been thrown on 'addStreakFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEmpty(Streak::getStreaks($this->courseId));
            $this->assertEquals(0, Utils::getDirectorySize((new Streaks(new Course($this->courseId)))->getDataFolder()));
            $this->assertEmpty(Section::getSectionByName($this->courseId, Streaks::RULE_SECTION)->getRules());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addStreakDuplicateName()
    {
        Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
                null, null, 100, null, false, true, false,
                false, false);
            $this->fail("Error should have been thrown on 'addStreakDuplicateName'");


        } catch (PDOException $e) {
            $this->assertCount(1, Streak::getStreaks($this->courseId));
            $this->assertEquals(1, Utils::getDirectorySize((new Streaks(new Course($this->courseId)))->getDataFolder()));
            $this->assertCount(1, Section::getSectionByName($this->courseId, Streaks::RULE_SECTION)->getRules());
        }
    }


    /**
     * @test
     * @dataProvider streakSuccessProvider
     * @throws Exception
     */
    public function editStreakSuccess(string $name, string $description, ?string $color, int $count, ?int $periodicity,
                                      ?string $periodicityTime, int $reward, ?int $tokens, bool $isRepeatable, bool $isCount,
                                      bool $isPeriodic, bool $isAtMost, bool $isExtra)
    {
        $virtualCurrency = (new Course($this->courseId))->getModuleById(VirtualCurrency::ID);
        $virtualCurrency->setEnabled(true);

        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak->editStreak($name, $description, $color, $count, $periodicity, $periodicityTime, $reward, $tokens, $isRepeatable,
            $isCount, $isPeriodic, $isAtMost, $isExtra, true);

        // Check is updated
        $this->assertEquals($name, $streak->getName());
        $this->assertEquals($description, $streak->getDescription());
        $this->assertEquals($color, $streak->getColor());
        $this->assertEquals($count, $streak->getCount());
        $this->assertEquals($periodicity, $streak->getPeriodicity());
        $this->assertEquals($periodicityTime, $streak->getPeriodicityTime());
        $this->assertEquals($reward, $streak->getReward());
        $this->assertEquals($tokens, $streak->getTokens());
        $this->assertEquals($isRepeatable, $streak->isRepeatable());
        $this->assertEquals($isCount, $streak->isCount());
        $this->assertEquals($isPeriodic, $streak->isPeriodic());
        $this->assertEquals($isAtMost, $streak->isAtMost());
        $this->assertEquals($isExtra, $streak->isExtra());
        $this->assertTrue($streak->isActive());

        // Check data folder
        $this->assertTrue(file_exists($streak->getDataFolder(true, $name)));

        // Check rule
        $this->assertEquals($this->trim("rule: $name
tags: 
# $description

	when:
		progress = None # COMPLETE THIS: get student progress in streak
		len(progress) > 0

	then:
		award_streak(target, \"$name\", progress)" . (!is_null($tokens) ? "\n\t\taward_tokens_type(target, \"streak\", $tokens, \"$name\", progress)" : "")), $this->trim($streak->getRule()->getText()));
    }

    /**
     * @test
     * @dataProvider streakFailureProvider
     * @throws Exception
     */
    public function editStreakFailure($name, $description, $color, $count, $periodicity, $periodicityTime, $reward, $tokens,
                                      $isRepeatable, $isCount, $isPeriodic, $isAtMost, $isExtra)
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->editStreak($name, $description, $color, $count, $periodicity, $periodicityTime, $reward, $tokens,
                $isRepeatable, $isCount, $isPeriodic, $isAtMost, $isExtra, true);
            $this->fail("Error should have been thrown on 'editStreakFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertCount(1, Streak::getStreaks($this->courseId));
            $this->assertEquals(1, Utils::getDirectorySize((new Streaks(new Course($this->courseId)))->getDataFolder()));
            $this->assertCount(1, Section::getSectionByName($this->courseId, Streaks::RULE_SECTION)->getRules());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editStreakDuplicateName()
    {
        $virtualCurrency = (new Course($this->courseId))->getModuleById(VirtualCurrency::ID);
        $virtualCurrency->setEnabled(true);

        Streak::addStreak($this->courseId, "Streak1", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak = Streak::addStreak($this->courseId, "Streak2", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        try {
            $streak->editStreak("Streak1", "Perform action", null, 10, null,
                null, 100, null, false, true, false,
                false, false, true);
            $this->fail("Error should have been thrown on 'editStreakDuplicateName'");


        } catch (PDOException $e) {
            $this->assertEquals("Streak2", $streak->getName());
            $this->assertTrue(file_exists($streak->getDataFolder(true, "Streak2")));
            $this->assertEquals($this->trim("rule: Streak2
tags: 
# Perform action

	when:
		progress = None # COMPLETE THIS: get student progress in streak
		len(progress) > 0

	then:
		award_streak(target, \"Streak2\", progress)"), $this->trim($streak->getRule()->getText()));
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function copyStreak()
    {
        // Given
        $copyTo = Course::addCourse("Course Copy", "CPY", "2021-2022", "#ffffff",
            null, null, false, false);

        (new Awards($copyTo))->setEnabled(true);
        (new XPLevels($copyTo))->setEnabled(true);
        (new Streaks($copyTo))->setEnabled(true);

        $streak1 = Streak::addStreak($this->courseId, "Streak1", "Perform action", "#ffffff", 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak2 = Streak::addStreak($this->courseId, "Streak2", "Perform action", null, 5,
            null, null, 200, null, false, true, false,
            false, true);

        // When
        $streak1->copyStreak($copyTo);
        $streak2->copyStreak($copyTo);

        // Then
        $streaks = Streak::getStreaks($this->courseId);
        $copiedStreaks = Streak::getStreaks($copyTo->getId());
        $this->assertSameSize($streaks, $copiedStreaks);
        foreach ($streaks as $i => $streak) {
            $this->assertEquals($streak["name"], $copiedStreaks[$i]["name"]);
            $this->assertEquals($streak["description"], $copiedStreaks[$i]["description"]);
            $this->assertEquals($streak["color"], $copiedStreaks[$i]["color"]);
            $this->assertEquals($streak["count"], $copiedStreaks[$i]["count"]);
            $this->assertEquals($streak["periodicity"], $copiedStreaks[$i]["periodicity"]);
            $this->assertEquals($streak["periodicityTime"], $copiedStreaks[$i]["periodicityTime"]);
            $this->assertEquals($streak["reward"], $copiedStreaks[$i]["reward"]);
            $this->assertEquals($streak["tokens"], $copiedStreaks[$i]["tokens"]);
            $this->assertEquals($streak["isRepeatable"], $copiedStreaks[$i]["isRepeatable"]);
            $this->assertEquals($streak["isCount"], $copiedStreaks[$i]["isCount"]);
            $this->assertEquals($streak["isPeriodic"], $copiedStreaks[$i]["isPeriodic"]);
            $this->assertEquals($streak["isAtMost"], $copiedStreaks[$i]["isAtMost"]);
            $this->assertEquals($streak["isExtra"], $copiedStreaks[$i]["isExtra"]);
            $this->assertEquals($streak["isActive"], $copiedStreaks[$i]["isActive"]);

            $this->assertEquals($streak["image"], $copiedStreaks[$i]["image"]);

            $this->assertEquals((new Rule($streak["rule"]))->getText(), (new Rule($copiedStreaks[$i]["rule"]))->getText());
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteStreak()
    {
        $streak1 = Streak::addStreak($this->courseId, "Streak1", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streak2 = Streak::addStreak($this->courseId, "Streak2", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);

        // Not empty
        Streak::deleteStreak($streak2->getId());
        $this->assertCount(1, Streak::getStreaks($this->courseId));
        $this->assertFalse(file_exists($streak2->getDataFolder(true, "Streak2")));
        $this->assertCount(1, Section::getSectionByName($this->courseId, Streaks::RULE_SECTION)->getRules());

        // Empty
        Streak::deleteStreak($streak1->getId());
        $this->assertEmpty(Streak::getStreaks($this->courseId));
        $this->assertFalse(file_exists($streak1->getDataFolder(true, "Streak1")));
        $this->assertEmpty(Section::getSectionByName($this->courseId, Streaks::RULE_SECTION)->getRules());
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteStreakInexistentStreak()
    {
        Streak::deleteStreak(100);
        $this->assertEmpty(Streak::getStreaks($this->courseId));
    }


    /**
     * @test
     * @throws Exception
     */
    public function streakExists()
    {
        $streak = Streak::addStreak($this->courseId, "Streak", "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $this->assertTrue($streak->exists());
    }

    /**
     * @test
     */
    public function streakDoesntExist()
    {
        $streak = new Streak(100);
        $this->assertFalse($streak->exists());
    }


    // Rules

    /**
     * @test
     * @dataProvider streakSuccessProvider
     * @throws Exception
     */
    public function generateRuleParamsFresh(string $name, string $description, ?string $color, int $count, ?int $periodicity,
                                            ?string $periodicityTime, int $reward, ?int $tokens)
    {
        $params = Streak::generateRuleParams($name, $description, $tokens);

        // Name
        $this->assertTrue(isset($params["name"]));
        $this->assertEquals($name, $params["name"]);

        // Description
        $this->assertTrue(isset($params["description"]));
        $this->assertEquals($description, $params["description"]);

        // When
        $this->assertTrue(isset($params["when"]));
        $this->assertEquals("progress = None # COMPLETE THIS: get student progress in streak
len(progress) > 0", $params["when"]);

        // Then
        $this->assertTrue(isset($params["then"]));
        $this->assertEquals("award_streak(target, \"$name\", progress)" . (!is_null($tokens) ? "\naward_tokens_type(target, \"streak\", $tokens, \"$name\", progress)" : ""), $params["then"]);
    }

    /**
     * @test
     * @dataProvider streakSuccessProvider
     * @throws Exception
     */
    public function generateRuleParamsNotFresh(string $name, string $description)
    {
        // Given
        $when = "progress = None # Some changes";
        $then = "# Some changes\naward_streak(target, \"$name\", progress)";

        $rule = Section::getSectionByName($this->courseId, Streaks::RULE_SECTION)->addRule($name, $description, $when, $then);

        // When
        $params = Streak::generateRuleParams("New Name", "New Description", null, false, $rule->getId());

        // Then

        // Name
        $this->assertTrue(isset($params["name"]));
        $this->assertEquals("New Name", $params["name"]);

        // Description
        $this->assertTrue(isset($params["description"]));
        $this->assertEquals("New Description", $params["description"]);

        // When
        $this->assertTrue(isset($params["when"]));
        $this->assertEquals("progress = None # Some changes", $params["when"]);

        // Then
        $this->assertTrue(isset($params["then"]));
        $this->assertEquals("# Some changes\naward_streak(target, \"New Name\", progress)", $params["then"]);
    }


    // Streak Data

    /**
     * @test
     * @dataProvider streakNameSuccessProvider
     * @throws Exception
     */
    public function getDataFolder(string $name)
    {
        $streak = Streak::addStreak($this->courseId, $name, "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $streaksDataFolder = (new Streaks(new Course($this->courseId)))->getDataFolder();
        $this->assertEquals($streaksDataFolder . "/" . Utils::strip($name, "_"), $streak->getDataFolder(true, $name));
    }

    /**
     * @test
     * @dataProvider streakNameSuccessProvider
     * @throws Exception
     */
    public function createDataFolder(string $name)
    {
        $streak = Streak::addStreak($this->courseId, $name, "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $name = trim($name);
        Streak::createDataFolder($this->courseId, $name);
        $this->assertTrue(file_exists($streak->getDataFolder(true, $name)));
    }

    /**
     * @test
     * @dataProvider streakNameSuccessProvider
     * @throws Exception
     */
    public function removeDataFolder(string $name)
    {
        $streak = Streak::addStreak($this->courseId, $name, "Perform action", null, 10,
            null, null, 100, null, false, true, false,
            false, false);
        $name = trim($name);
        Streak::removeDataFolder($this->courseId, $name);
        $this->assertFalse(file_exists($streak->getDataFolder(true, $name)));
    }


    // Import / Export
    // TODO


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private function trim(string $str)
    {
        return str_replace("\r", "", $str);
    }
}
