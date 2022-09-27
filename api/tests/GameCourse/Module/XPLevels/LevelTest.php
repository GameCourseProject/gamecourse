<?php
namespace GameCourse\Module\XPLevels;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\User\User;
use PDOException;
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

        // Enable XP & Levels module
        (new Awards($course))->setEnabled(true);
        $xpLevels = new XPLevels($course);
        $xpLevels->setEnabled(true);
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, XPLevels::TABLE_LEVEL]);
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

    public function levelDescriptionSuccessProvider(): array
    {
        return [
            "null" => [null],
            "trimmed" => [" This is some incredibly enormous level description "],
            "length limit" => ["This is some incredibly enormous level description"]
        ];
    }

    public function levelDescriptionFailureProvider(): array
    {
        return [
            "empty" => [""],
            "too long" => ["This is some incredibly enormous level descriptionn"]
        ];
    }


    public function levelSuccessProvider(): array
    {
        $descriptions = array_map(function ($description) { return $description[0]; }, $this->levelDescriptionSuccessProvider());

        $provider = [];
        foreach ($descriptions as $d1 => $description) {
            $provider["minXP: 1000 | description: " . $d1] = [1000, $description];
        }
        return $provider;
    }

    public function levelFailureProvider(): array
    {
        $descriptions = array_map(function ($description) { return $description[0]; }, $this->levelDescriptionFailureProvider());

        $provider = [];
        foreach ($descriptions as $d1 => $description) {
            $provider["minXP: 1000 | description: " . $d1] = [1000, $description];
        }
        return $provider;
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

    /**
     * @test
     * @throws Exception
     */
    public function setMinXP()
    {
        // Success
        $level = Level::addLevel($this->courseId, 1000, null);
        $level->setMinXP(2000);
        $this->assertEquals(2000, $level->getMinXP());

        // Negative
        try {
            $level->setMinXP(-1000);

        } catch (Exception $e) {
            $this->assertEquals(2000, $level->getMinXP());
        }

        // Level 0
        $level0 = Level::getLevelZero($this->courseId);
        try {
            $level0->setMinXP(1000);

        } catch (Exception $e) {
            $this->assertEquals(0, $level0->getMinXP());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setMinXPUsersWithXP()
    {
        // Given
        $level0 = Level::getLevelZero($this->courseId);
        $level1 = Level::addLevel($this->courseId, 1000, null);
        $level2 = Level::addLevel($this->courseId, 2000, null);

        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $course = new Course($this->courseId);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student");

        $xpLevelsModule = new XPLevels($course);
        $xpLevelsModule->setUserXP($user1->getId(), 1000);
        $xpLevelsModule->setUserXP($user2->getId(), 5000);

        $this->assertEquals($level1, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level2, Level::getUserLevel($this->courseId, $user2->getId()));

        // When
        $level1->setMinXP(1500);

        // Then
        $this->assertEquals(1500, $level1->getMinXP());
        $this->assertEquals($level0, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level2, Level::getUserLevel($this->courseId, $user2->getId()));
    }

    /**
     * @test
     * @dataProvider levelDescriptionSuccessProvider
     * @throws Exception
     */
    public function setDescriptionSuccess(?string $description)
    {
        $level = Level::addLevel($this->courseId, 1000, "Some description");
        $level->setDescription($description);
        $this->assertEquals(trim($description), $level->getDescription());
    }

    /**
     * @test
     * @dataProvider levelDescriptionFailureProvider
     * @throws Exception
     */
    public function setDescriptionFailure($description)
    {
        $level = Level::addLevel($this->courseId, 1000, "Some description");
        try {
            $level->setDescription($description);

        } catch (Exception $e) {
            $this->assertEquals("Some description", $level->getDescription());
        }
    }


    // General

    /**
     * @test
     * @throws Exception
     */
    public function getLevelById()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $this->assertEquals($level, Level::getLevelById($level->getId()));
    }

    /**
     * @test
     */
    public function getLevelByIdLevelDoesntExist()
    {
        $this->assertNull(Level::getLevelById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLevelByMinXP()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $this->assertEquals($level, Level::getLevelByMinXP($this->courseId, $level->getMinXP()));
    }

    /**
     * @test
     */
    public function getLevelByMinXPLevelDoesntExist()
    {
        $this->assertNull(Level::getLevelByMinXP($this->courseId, 1000));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLevelByXP()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $this->assertEquals($level, Level::getLevelByXP($this->courseId, 2000));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLevelByXPFailure()
    {
        Level::addLevel($this->courseId, 1000, null);
        Core::database()->delete(Level::TABLE_LEVEL, ["course" => $this->courseId, "minXP" => 0]);
        $this->expectException(Exception::class);
        Level::getLevelByXP($this->courseId, 500);
    }


    /**
     * @test
     * @throws Exception
     */
    public function getLevelZero()
    {
        $level0 = Level::getLevelByMinXP($this->courseId, 0);
        $this->assertEquals($level0, Level::getLevelZero($this->courseId));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLevelZeroLevelNotFound()
    {
        Core::database()->delete(Level::TABLE_LEVEL, ["course" => $this->courseId, "minXP" => 0]);
        $this->expectException(Exception::class);
        Level::getLevelZero($this->courseId);
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllLevels()
    {
        $level0 = Level::getLevelZero($this->courseId);
        $level2 = Level::addLevel($this->courseId, 2000, null);
        $level1 = Level::addLevel($this->courseId, 1000, null);

        $levels = Level::getLevels($this->courseId);
        $this->assertIsArray($levels);
        $this->assertCount(3, $levels);

        $keys = ["id", "minXP", "description", "number"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($levels as $i => $level) {
                $this->assertCount($nrKeys, array_keys($level));
                $this->assertArrayHasKey($key, $level);
                if ($key == "number") $this->assertEquals($level["number"], $i);
                else $this->assertEquals($level[$key], ${"level".$i}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllLevelsOnlyLevel0()
    {
        $level0 = Level::getLevelZero($this->courseId);

        $levels = Level::getLevels($this->courseId);
        $this->assertIsArray($levels);
        $this->assertCount(1, $levels);

        $keys = ["id", "minXP", "description", "number"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            $this->assertCount($nrKeys, array_keys($levels[0]));
            $this->assertArrayHasKey($key, $levels[0]);
            if ($key == "number") $this->assertEquals(0, $levels[0]["number"]);
            else $this->assertEquals($levels[0][$key], $level0->getData($key));
        }
    }


    // Level Manipulation

    /**
     * @test
     * @dataProvider levelSuccessProvider
     * @throws Exception
     */
    public function addLevelSuccess(int $minXP, ?string $description)
    {
        $level = Level::addLevel($this->courseId, $minXP, $description);

        // Check is added to database
        $levelDB = Level::getLevels($this->courseId)[1];
        $levelDB["course"] = $this->courseId;
        $levelInfo = $level->getData();
        $levelInfo["number"] = 1;
        $this->assertEquals($levelInfo, $levelDB);
    }

    /**
     * @test
     * @dataProvider levelFailureProvider
     * @throws Exception
     */
    public function addLevelFailure($minXP, $description)
    {
        try {
            Level::addLevel($this->courseId, $minXP, $description);

        } catch (Exception $e) {
            $this->assertCount(1, Level::getLevels($this->courseId));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addLevelDuplicateMinXP()
    {
        try {
            Level::addLevel($this->courseId, 0, null);

        } catch (PDOException $e) {
            $this->assertCount(1, Level::getLevels($this->courseId));
        }
    }


    /**
     * @test
     * @dataProvider levelSuccessProvider
     * @throws Exception
     */
    public function editLevelSuccess(int $minXP, ?string $description)
    {
        $level = Level::addLevel($this->courseId, 2000, "Some description");
        $level->editLevel($minXP, $description);
        $this->assertEquals($minXP, $level->getMinXP());
        $this->assertEquals(trim($description), $level->getDescription());
    }

    /**
     * @test
     * @dataProvider levelFailureProvider
     * @throws Exception
     */
    public function editLevelFailure($minXP, $description)
    {
        $level = Level::addLevel($this->courseId, 2000, "Some description");
        try {
            $level->editLevel($minXP, $description);

        } catch (Exception $e) {
            $this->assertEquals(2000, $level->getMinXP());
            $this->assertEquals("Some description", $level->getDescription());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editLevelDuplicateMinXP()
    {
        Level::addLevel($this->courseId, 2000, "Some description");
        $level = Level::addLevel($this->courseId, 5000, "Some description");
        try {
            $level->editLevel(2000, "Some description");

        } catch (PDOException $e) {
            $this->assertEquals(5000, $level->getMinXP());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editLevelUsersWithXP()
    {
        // Given
        $level0 = Level::getLevelZero($this->courseId);
        $level1 = Level::addLevel($this->courseId, 1000, null);
        $level2 = Level::addLevel($this->courseId, 2000, null);

        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $course = new Course($this->courseId);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student");

        $xpLevelsModule = new XPLevels($course);
        $xpLevelsModule->setUserXP($user1->getId(), 1000);
        $xpLevelsModule->setUserXP($user2->getId(), 5000);

        $this->assertEquals($level1, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level2, Level::getUserLevel($this->courseId, $user2->getId()));

        // When
        $level1->editLevel(1500, null);

        // Then
        $this->assertEquals(1500, $level1->getMinXP());
        $this->assertEquals($level0, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level2, Level::getUserLevel($this->courseId, $user2->getId()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function copyLevel()
    {
        // Given
        $copyTo = Course::addCourse("Course Copy", "CPY", "2021-2022", "#ffffff",
            null, null, false, false);

        (new Awards($copyTo))->setEnabled(true);
        (new XPLevels($copyTo))->setEnabled(true);

        $level0 = Level::getLevelZero($this->courseId);
        $level0->setDescription("Level 0");

        $level1 = Level::addLevel($this->courseId, 1000, "Level 1");

        // When
        $level0->copyLevel($copyTo);
        $level1->copyLevel($copyTo);

        // Then
        $levels = Level::getLevels($this->courseId);
        $copiedLevels = Level::getLevels($copyTo->getId());
        $this->assertSameSize($levels, $copiedLevels);
        foreach ($levels as $i => $level) {
            $this->assertEquals($level["minXP"], $copiedLevels[$i]["minXP"]);
            $this->assertEquals($level["description"], $copiedLevels[$i]["description"]);
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteLevel()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        Level::deleteLevel($level->getId());
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(1, $levels);
        $this->assertEquals(0, $levels[0]["minXP"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteLevelInexistentLevel()
    {
        Level::deleteLevel(100);
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(1, $levels);
        $this->assertEquals(0, $levels[0]["minXP"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteLevelZero()
    {
        $this->expectException(Exception::class);
        Level::deleteLevel(Level::getLevelZero($this->courseId)->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteLevelUsersWithXP()
    {
        // Given
        $level0 = Level::getLevelZero($this->courseId);
        $level1 = Level::addLevel($this->courseId, 1000, null);
        $level2 = Level::addLevel($this->courseId, 2000, null);

        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);

        $course = new Course($this->courseId);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student");

        $xpLevelsModule = new XPLevels($course);
        $xpLevelsModule->setUserXP($user1->getId(), 1000);
        $xpLevelsModule->setUserXP($user2->getId(), 5000);

        $this->assertEquals($level1, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level2, Level::getUserLevel($this->courseId, $user2->getId()));

        // When
        Level::deleteLevel($level1->getId());

        // Then
        $levels = Level::getLevels($this->courseId);
        $this->assertIsArray($levels);
        $this->assertCount(2, $levels);
        $this->assertEquals(0, $levels[0]["minXP"]);
        $this->assertEquals(2000, $levels[1]["minXP"]);

        $this->assertEquals($level0, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level2, Level::getUserLevel($this->courseId, $user2->getId()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function levelExists()
    {
        $level = Level::addLevel($this->courseId, 1000, null);
        $this->assertTrue($level->exists());
    }

    /**
     * @test
     */
    public function levelDoesntExist()
    {
        $level = new Level(100);
        $this->assertFalse($level->exists());
    }


    // Users

    /**
     * @test
     * @throws Exception
     */
    public function getUserLevel()
    {
        // Given
        $level0 = Level::getLevelZero($this->courseId);
        $level1 = Level::addLevel($this->courseId, 1000, null);
        $level2 = Level::addLevel($this->courseId, 2000, null);

        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);
        $user3 = User::addUser("Student C", "student_c", AuthService::FENIX, null,
            3, null, null, false, true);

        $course = new Course($this->courseId);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student");
        $course->addUserToCourse($user3->getId(), "Student");

        $xpLevelsModule = new XPLevels($course);
        $xpLevelsModule->setUserXP($user1->getId(), 1000);
        $xpLevelsModule->setUserXP($user2->getId(), 5000);
        $xpLevelsModule->setUserXP($user3->getId(), 500);

        // Then
        $this->assertEquals($level1, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level2, Level::getUserLevel($this->courseId, $user2->getId()));
        $this->assertEquals($level0, Level::getUserLevel($this->courseId, $user3->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateUsersLevel()
    {
        // Given
        $level0 = Level::getLevelZero($this->courseId);
        $level1 = Level::addLevel($this->courseId, 1000, null);
        $level2 = Level::addLevel($this->courseId, 2000, null);

        $user1 = User::addUser("Student A", "student_a", AuthService::FENIX, null,
            1, null, null, false, true);
        $user2 = User::addUser("Student B", "student_b", AuthService::FENIX, null,
            2, null, null, false, true);
        $user3 = User::addUser("Student C", "student_c", AuthService::FENIX, null,
            3, null, null, false, true);

        $course = new Course($this->courseId);
        $course->addUserToCourse($user1->getId(), "Student");
        $course->addUserToCourse($user2->getId(), "Student");
        $course->addUserToCourse($user3->getId(), "Student");

        $xpLevelsModule = new XPLevels($course);
        $xpLevelsModule->setUserXP($user1->getId(), 1000);
        $xpLevelsModule->setUserXP($user2->getId(), 5000);
        $xpLevelsModule->setUserXP($user3->getId(), 500);

        Core::database()->update(XPLevels::TABLE_XP, ["level" => $level0->getId()], ["course" => $this->courseId, "user" => $user1->getId()]);
        Core::database()->update(XPLevels::TABLE_XP, ["level" => $level0->getId()], ["course" => $this->courseId, "user" => $user2->getId()]);
        Core::database()->update(XPLevels::TABLE_XP, ["level" => $level0->getId()], ["course" => $this->courseId, "user" => $user3->getId()]);

        $this->assertEquals($level0, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level0, Level::getUserLevel($this->courseId, $user2->getId()));
        $this->assertEquals($level0, Level::getUserLevel($this->courseId, $user3->getId()));

        // When
        Level::updateUsersLevel($this->courseId);

        // Then
        $this->assertEquals($level1, Level::getUserLevel($this->courseId, $user1->getId()));
        $this->assertEquals($level2, Level::getUserLevel($this->courseId, $user2->getId()));
        $this->assertEquals($level0, Level::getUserLevel($this->courseId, $user3->getId()));
    }


    // Import / Export

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsWithHeaderUniqueLevelsNoReplace()
    {
        // Given
        $file = "title,minimum XP\n";
        $file .= "Level 1,1000\n";
        $file .= "Level 2,2000";

        // When
        $nrLevelsImported = Level::importLevels($this->courseId, $file, false);

        // Then
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(3, $levels);
        $this->assertEquals(2, $nrLevelsImported);

        $level1 = Level::getLevelByMinXP($this->courseId, 1000)->getData();
        $level2 = Level::getLevelByMinXP($this->courseId, 2000)->getData();

        $expectedLevel1 = ["id" => 2, "course" => $this->courseId, "minXP" => 1000, "description" => "Level 1"];
        $expectedLevel2 = ["id" => 3, "course" => $this->courseId, "minXP" => 2000, "description" => "Level 2"];

        $this->assertEquals($expectedLevel1, $level1);
        $this->assertEquals($expectedLevel2, $level2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsWithHeaderNonUniqueLevelsNoReplace()
    {
        // Given
        $file = "title,minimum XP\n";
        $file .= "Level 0,0\n";
        $file .= "Level 1,1000\n";
        $file .= "Level 2,2000";

        // When
        $nrLevelsImported = Level::importLevels($this->courseId, $file, false);

        // Then
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(3, $levels);
        $this->assertEquals(2, $nrLevelsImported);

        $level0 = Level::getLevelZero($this->courseId)->getData();
        $level1 = Level::getLevelByMinXP($this->courseId, 1000)->getData();
        $level2 = Level::getLevelByMinXP($this->courseId, 2000)->getData();

        $expectedLevel0 = ["id" => 1, "course" => $this->courseId, "minXP" => 0, "description" => "AWOL"];
        $expectedLevel1 = ["id" => 2, "course" => $this->courseId, "minXP" => 1000, "description" => "Level 1"];
        $expectedLevel2 = ["id" => 3, "course" => $this->courseId, "minXP" => 2000, "description" => "Level 2"];

        $this->assertEquals($expectedLevel0, $level0);
        $this->assertEquals($expectedLevel1, $level1);
        $this->assertEquals($expectedLevel2, $level2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsWithHeaderNonUniqueLevelsReplace()
    {
        // Given
        $file = "title,minimum XP\n";
        $file .= "Level 0,0\n";
        $file .= "Level 1,1000\n";
        $file .= "Level 2,2000";

        // When
        $nrLevelsImported = Level::importLevels($this->courseId, $file);

        // Then
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(3, $levels);
        $this->assertEquals(2, $nrLevelsImported);

        $level0 = Level::getLevelZero($this->courseId)->getData();
        $level1 = Level::getLevelByMinXP($this->courseId, 1000)->getData();
        $level2 = Level::getLevelByMinXP($this->courseId, 2000)->getData();

        $expectedLevel0 = ["id" => 1, "course" => $this->courseId, "minXP" => 0, "description" => "Level 0"];
        $expectedLevel1 = ["id" => 2, "course" => $this->courseId, "minXP" => 1000, "description" => "Level 1"];
        $expectedLevel2 = ["id" => 3, "course" => $this->courseId, "minXP" => 2000, "description" => "Level 2"];

        $this->assertEquals($expectedLevel0, $level0);
        $this->assertEquals($expectedLevel1, $level1);
        $this->assertEquals($expectedLevel2, $level2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsWithNoHeaderUniqueLevelsReplace()
    {
        // Given
        $file = "Level 1,1000\n";
        $file .= "Level 2,2000";

        // When
        $nrLevelsImported = Level::importLevels($this->courseId, $file);

        // Then
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(3, $levels);
        $this->assertEquals(2, $nrLevelsImported);

        $level1 = Level::getLevelByMinXP($this->courseId, 1000)->getData();
        $level2 = Level::getLevelByMinXP($this->courseId, 2000)->getData();

        $expectedLevel1 = ["id" => 2, "course" => $this->courseId, "minXP" => 1000, "description" => "Level 1"];
        $expectedLevel2 = ["id" => 3, "course" => $this->courseId, "minXP" => 2000, "description" => "Level 2"];

        $this->assertEquals($expectedLevel1, $level1);
        $this->assertEquals($expectedLevel2, $level2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsWithNoHeaderNonUniqueLevelsReplace()
    {
        // Given
        $file = "Level 0,0\n";
        $file .= "Level 1,1000\n";
        $file .= "Level 2,2000";

        // When
        $nrLevelsImported = Level::importLevels($this->courseId, $file);

        // Then
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(3, $levels);
        $this->assertEquals(2, $nrLevelsImported);

        $level0 = Level::getLevelZero($this->courseId)->getData();
        $level1 = Level::getLevelByMinXP($this->courseId, 1000)->getData();
        $level2 = Level::getLevelByMinXP($this->courseId, 2000)->getData();

        $expectedLevel0 = ["id" => 1, "course" => $this->courseId, "minXP" => 0, "description" => "Level 0"];
        $expectedLevel1 = ["id" => 2, "course" => $this->courseId, "minXP" => 1000, "description" => "Level 1"];
        $expectedLevel2 = ["id" => 3, "course" => $this->courseId, "minXP" => 2000, "description" => "Level 2"];

        $this->assertEquals($expectedLevel0, $level0);
        $this->assertEquals($expectedLevel1, $level1);
        $this->assertEquals($expectedLevel2, $level2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsWithNoHeaderNonUniqueLevelsNoReplace()
    {
        // Given
        $file = "Level 0,0\n";
        $file .= "Level 1,1000\n";
        $file .= "Level 2,2000";

        // When
        $nrLevelsImported = Level::importLevels($this->courseId, $file, false);

        // Then
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(3, $levels);
        $this->assertEquals(2, $nrLevelsImported);

        $level0 = Level::getLevelZero($this->courseId)->getData();
        $level1 = Level::getLevelByMinXP($this->courseId, 1000)->getData();
        $level2 = Level::getLevelByMinXP($this->courseId, 2000)->getData();

        $expectedLevel0 = ["id" => 1, "course" => $this->courseId, "minXP" => 0, "description" => "AWOL"];
        $expectedLevel1 = ["id" => 2, "course" => $this->courseId, "minXP" => 1000, "description" => "Level 1"];
        $expectedLevel2 = ["id" => 3, "course" => $this->courseId, "minXP" => 2000, "description" => "Level 2"];

        $this->assertEquals($expectedLevel0, $level0);
        $this->assertEquals($expectedLevel1, $level1);
        $this->assertEquals($expectedLevel2, $level2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsEmptyFileNoHeaderNoLevels()
    {
        $file = "";
        $nrLevelsImported = Level::importLevels($this->courseId, $file);
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(1, $levels);
        $this->assertEquals(0, $levels[0]["minXP"]);
        $this->assertEquals(0, $nrLevelsImported);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsEmptyFileNoHeaderWithLevels()
    {
        Level::addLevel($this->courseId, 1000, "Level 1");
        Level::addLevel($this->courseId, 2000, "Level 2");

        $file = "";
        $nrLevelsImported = Level::importLevels($this->courseId, $file);
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(3, $levels);
        $this->assertEquals(0, $nrLevelsImported);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importLevelsEmptyFileWithHeaderWithLevels()
    {
        Level::addLevel($this->courseId, 1000, "Level 1");
        Level::addLevel($this->courseId, 2000, "Level 2");

        $file = "title,minimum XP\n";
        $nrLevelsImportes = Level::importLevels($this->courseId, $file);
        $levels = Level::getLevels($this->courseId);
        $this->assertCount(3, $levels);
        $this->assertEquals(0, $nrLevelsImportes);
    }


    /**
     * @test
     * @throws Exception
     */
    public function exportAllLevels()
    {
        Level::addLevel($this->courseId, 1000, "Level 1");
        Level::addLevel($this->courseId, 2000, "Level 2");

        $expectedFile = "title,minimum XP\n";
        $expectedFile .= "AWOL,0\n";
        $expectedFile .= "Level 1,1000\n";
        $expectedFile .= "Level 2,2000";

        $export = Level::exportLevels($this->courseId, [1, 2, 3]);
        $this->assertEquals(".csv", $export["extension"]);
        $this->assertEquals($expectedFile, $export["file"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function exportSomeLevels()
    {
        Level::addLevel($this->courseId, 1000, "Level 1");
        Level::addLevel($this->courseId, 2000, "Level 2");

        $expectedFile = "title,minimum XP\n";
        $expectedFile .= "Level 1,1000\n";
        $expectedFile .= "Level 2,2000";

        $export = Level::exportLevels($this->courseId, [2, 3]);
        $this->assertEquals(".csv", $export["extension"]);
        $this->assertEquals($expectedFile, $export["file"]);
    }
}