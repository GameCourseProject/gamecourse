<?php
namespace GameCourse\AutoGame\RuleSystem;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
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
class SectionTest extends TestCase
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
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Section::TABLE_RULE_SECTION]);
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

    public function sectionNameSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Section Name"],
            "non-ASCII characters" => ["Sectiön Name"],
            "numbers" => ["Section123"],
            "parenthesis" => ["Section Name (Copy)"],
            "hyphen" => ["Section-Name"],
            "underscore" => ["Section_Name"],
            "ampersand" => ["Section & Name"],
            "trimmed" => [" This is some incredibly humongous section nameee "],
            "length limit" => ["This is some incredibly humongous section nameeeee"]
        ];
    }

    public function sectionNameFailureProvider(): array
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
            "too long" => ["This is some incredibly humongous section nameeeeee"]
        ];
    }


    public function sectionSuccessProvider(): array
    {
        $names = array_map(function ($name) { return $name[0]; }, $this->sectionNameSuccessProvider());
        $positions = ["first" => 0, "second" => 1, "null" => null];

        $provider = [];
        foreach ($names as $d1 => $name) {
            foreach ($positions as $d2 => $position) {
                $provider["name: " . $d1 . " | position: " . $d2] = [$name, $position];
            }
        }
        return $provider;
    }

    public function sectionFailureProvider(): array
    {
        $names = array_map(function ($name) { return $name[0]; }, $this->sectionNameFailureProvider());
        $positions = ["negative" => -1, "not a number" => "abc"];

        $provider = [];
        foreach ($names as $d1 => $name) {
            foreach ($positions as $d2 => $position) {
                $provider["name: " . $d1 . " | position: " . $d2] = [$name, $position];
            }
        }
        return $provider;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function sectionConstructor()
    {
        $section = new Section(123);
        $this->assertEquals(123, $section->getId());
    }

    /**
     * @test
     * @return void
     */
    public function getExistingSections()
    {
        $sections = Section::getSections($this->courseId);
        $this->assertEquals([
            [
                "id" => 2,
                "course" => 1,
                "name" => "Graveyard",
                "position" => 0,
                "module" => null,
                "isActive" => true
            ],
            [
                "id" => 1,
                "course" => 1,
                "name" => "Miscellaneous",
                "position" => 1,
                "module" => null,
                "isActive" => true
            ]
        ], $sections);
    }


    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $id = intval(Core::database()->select(Section::TABLE_RULE_SECTION, ["name" => "Section Name"], "id"));
        $this->assertEquals($id, $section->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertEquals($this->courseId, $section->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSectionName()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertEquals("Section Name", $section->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPosition()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertEquals(0, $section->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getModule()
    {
        $course = Course::getCourseById($this->courseId);
        $moduleId = $course->getModules(null, true)[0];
        $section = Section::addSection($this->courseId, "Section Name", null, $moduleId);
        $this->assertEquals($course->getModuleById($moduleId), $section->getModule());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getModuleNull()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertNull($section->getModule());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getFile()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertEquals(RuleSystem::getDataFolder($this->courseId, false) . "/1-Section_Name.txt",
            $section->getFile(false));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getFileFullPath()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertEquals(RuleSystem::getDataFolder($this->courseId) . "/1-Section_Name.txt",
            $section->getFile());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function isActive(){
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertTrue($section->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        // Graveyard and Miscellaneous sections are created before this section, hence id = 1 and id = 2 being already taken
        $this->assertEquals(
            ["id" => 3, "course" => $this->courseId, "name" => "Section Name", "position" => 0, "module" => null, 'isActive' => true],
            $section->getData());
    }


    /**
     * @test
     * @dataProvider sectionNameSuccessProvider
     * @throws Exception
     */
    public function setNameSuccess(string $name)
    {
        $section = Section::addSection($this->courseId, "NAME");
        $section->setName($name);

        $name = trim($name);
        $this->assertEquals($name, $section->getName());

        $this->assertEquals(RuleSystem::getDataFolder($this->courseId) . "/1-" . Utils::strip($name, "_") . ".txt", $section->getFile());
        $this->assertTrue(file_exists($section->getFile()));
        $this->assertFalse(file_exists(RuleSystem::getDataFolder($this->courseId) . "/1-NAME.txt"));
    }

    /**
     * @test
     * @dataProvider sectionNameFailureProvider
     * @throws Exception
     */
    public function setNameFailure($name)
    {
        $section = Section::addSection($this->courseId, "NAME");
        try {
            $section->setName($name);
            $this->fail("Exception should have been thrown on 'setSectionNameFailure'");

        } catch (Exception|TypeError $error) {
            $this->assertEquals("NAME", $section->getName());

            $this->assertEquals(RuleSystem::getDataFolder($this->courseId) . "/1-NAME.txt", $section->getFile());
            $this->assertTrue(file_exists($section->getFile()));
            // Already counting w/ previous sections (miscellaneous & graveyard)
            $this->assertEquals(3, Utils::getDirectorySize(RuleSystem::getDataFolder($this->courseId)));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionSuccess()
    {
        Section::addSection($this->courseId, "Section1", 0);
        $section = Section::addSection($this->courseId, "Section2", 1);

        $section->setPosition(1);
        $this->assertEquals(1, $section->getPosition());
        $this->assertEquals(RuleSystem::getDataFolder($this->courseId) . "/2-Section2.txt", $section->getFile());
        $this->assertTrue(file_exists($section->getFile()));
        $this->assertFalse(file_exists(RuleSystem::getDataFolder($this->courseId) . "/0-Section2.txt"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setModule()
    {
        $course = Course::getCourseById($this->courseId);
        $moduleIds = $course->getModules(null, true);
        $section = Section::addSection($this->courseId, "Section Name", null, $moduleIds[0]);
        $section->setModule($course->getModuleById($moduleIds[1]));
        $this->assertEquals($course->getModuleById($moduleIds[1]), $section->getModule());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setModuleNull()
    {
        $course = Course::getCourseById($this->courseId);
        $moduleId = $course->getModules(null, true)[0];
        $section = Section::addSection($this->courseId, "Section Name", null, $moduleId);
        $section->setModule(null);
        $this->assertNull($section->getModule());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function setActive(){
        $section = Section::addSection($this->courseId, "Section Name");
        $section->setActive(false);
        $this->assertFalse($section->isActive());
    }

    /**
     * @test
     * @dataProvider sectionSuccessProvider
     * @throws Exception
     */
    public function setDataSuccess(string $name, ?int $position)
    {
        $fieldValues = ["name" => $name, "position" => $position];
        $section = Section::addSection($this->courseId, "Section Name");
        $section->setData($fieldValues);
        $fieldValues["id"] = $section->getId();
        $fieldValues["course"] = $this->courseId;
        $fieldValues["module"] = null;
        $fieldValues["isActive"] = true;
        Utils::trim(["name"], $fieldValues);
        $this->assertEquals($section->getData(), $fieldValues);
    }

    /**
     * @test
     * @dataProvider sectionFailureProvider
     * @throws Exception
     */
    public function setDataFailure($name, $position)
    {
        $section = Section::addSection($this->courseId, "Section Name");
        try {
            $section->setData(["name" => $name, "position" => $position]);
            $this->fail("Exception should have been thrown on 'setDataFailure'");

        } catch (Exception $e) {
            // Id = 3 because its already counting w/ previous sections (miscellaneous & graveyard)
            $this->assertEquals(["id" => 3, "course" => $this->courseId, "name" => "Section Name", "position" => 0, "module" => null, "isActive" => true],
                $section->getData());
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getSectionById()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertEquals($section, Section::getSectionById($section->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSectionByIdSectionDoesntExist()
    {
        $this->assertNull(Section::getSectionById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSectionByName()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertEquals($section, Section::getSectionByName($this->courseId, $section->getName()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSectionByNameSectionDoesntExist()
    {
        $this->assertNull(Section::getSectionByName($this->courseId, "NAME"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSectionByPosition()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertEquals($section, Section::getSectionByPosition($this->courseId, 0));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSectionByPositionSectionDoesntExist()
    {
        $this->assertNull(Section::getSectionByPosition($this->courseId, 4));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllSections()
    {
        // Miscellaneous and Graveyard already exist
        $miscellaneous = new Section(1);
        $graveyard = new Section(2);

        $section3 = Section::addSection($this->courseId, "Section1", 3);
        $section4 = Section::addSection($this->courseId, "Section2", 4);

        $sections = Section::getSections($this->courseId);
        $this->assertIsArray($sections);
        $this->assertCount(4, $sections);

        $keys = ["id", "course", "name", "position", "module", "isActive"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($sections as $i => $section) {
                $this->assertCount($nrKeys, array_keys($section));
                $this->assertArrayHasKey($key, $section);
                if ($i+1 == 1) $name = "graveyard";
                else if ($i+1 == 2) $name = "miscellaneous";
                else $name = "section".($i+1);
                $this->assertEquals($section[$key], ${$name}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllSectionsNoSections()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        Section::addSection($course->getId(), "Section3");

        $sections = Section::getSections($this->courseId);
        $this->assertIsArray($sections);
        $this->assertEquals([
            [
                "id" => 2,
                "course" => 1,
                "name" => "Graveyard",
                "position" => 0,
                "module" => null,
                "isActive" => true
            ],
            [
                "id" => 1,
                "course" => 1,
                "name" => "Miscellaneous",
                "position" => 1,
                "module" => null,
                "isActive" => true
            ]
        ], $sections);
    }


    /**
     * @test
     * @dataProvider sectionNameSuccessProvider
     * @throws Exception
     */
    public function addSectionSuccess(string $name)
    {
        $section = Section::addSection($this->courseId, $name);
        $name = trim($name);

        // Check is added to database
        $sectionDB = Core::database()->select(Section::TABLE_RULE_SECTION, ["id" => $section->getId()]);
        $sectionData = [
            "id" => strval($section->getId()),
            "course" => strval($this->courseId),
            "name" => $name,
            "position" => "0",
            "module" => null,
            "isActive" => true];
        $this->assertEquals($sectionData, $sectionDB);

        // Check section file was created
        $filePath = RuleSystem::getDataFolder($this->courseId) . "/1-" . Utils::strip($name, "_") . ".txt";
        $this->assertEquals($filePath, $section->getFile());
        $this->assertTrue(file_exists($filePath));
    }

    /**
     * @test
     * @dataProvider sectionNameFailureProvider
     * @throws Exception
     */
    public function addSectionFailure($name)
    {
        try {
            Section::addSection($this->courseId, $name);
            $this->fail("Exception should have been thrown on 'addSectionFailure'");

        } catch (Exception|TypeError $e) {
            $section = Core::database()->select(Section::TABLE_RULE_SECTION, ["course" => $this->courseId, "name" => $name]);
            $this->assertEmpty($section);

            // Already counting w/ previous sections (miscellaneous & graveyard)
            $this->assertEquals(2, Utils::getDirectorySize(RuleSystem::getDataFolder($this->courseId)));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addSectionDuplicateName()
    {
        Section::addSection($this->courseId, "Section Name");
        try {
            Section::addSection($this->courseId, "Section Name");
            $this->fail("Exception should have been thrown on 'addSectionDuplicateName'");

        } catch (Exception $e) {
            $sections = Section::getSections($this->courseId);
            $this->assertCount(3, $sections); // Already counting w/ previous sections (miscellaneous & graveyard)
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addSectionModule()
    {
        $course = Course::getCourseById($this->courseId);
        $moduleId = $course->getModules(null, true)[0];
        $section = Section::addSection($this->courseId, "Section Name", null, $moduleId);

        // Check is added to database
        $sectionDB = Core::database()->select(Section::TABLE_RULE_SECTION, ["id" => $section->getId()]);
        $sectionData = ["id" => strval($section->getId()), "course" => strval($this->courseId), "name" => "Section Name",
            "position" => "0", "module" => $moduleId, "isActive" => "1"];
        $this->assertEquals($sectionData, $sectionDB);

        // Check section file was created
        $filePath = RuleSystem::getDataFolder($this->courseId) . "/1-Section_Name.txt";
        $this->assertEquals($filePath, $section->getFile());
        $this->assertTrue(file_exists($filePath));
    }


    /**
     * @test
     * @throws Exception
     */
    public function editSectionSuccess()
    {
        $section1 = Section::addSection($this->courseId, "Section1", 0);
        $section2 = Section::addSection($this->courseId, "Section2", 1);

        $section2->editSection("New Name", 0, true);

        // Check is updated on database
        $this->assertEquals("New Name", $section2->getName());
        $this->assertEquals(0, $section2->getPosition());
        $this->assertEquals(1, $section1->getPosition());

        // Check section file was updated
        // Already counting w/ previous sections (miscellaneous & graveyard)
        $this->assertEquals(4, Utils::getDirectorySize(RuleSystem::getDataFolder($this->courseId)));
        $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/1-New_Name.txt"));
        $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/2-Section1.txt"));
    }

    /**
     * @test
     * @dataProvider sectionNameFailureProvider
     * @throws Exception
     */
    public function editSectionFailure($name)
    {
        Section::addSection($this->courseId, "Section1", 0);
        $section2 = Section::addSection($this->courseId, "Section2", 1);

        try {
            $section2->editSection($name, 1, true);

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Section2", $section2->getName());
            // Already counting w/ previous sections (miscellaneous & graveyard)
            $this->assertEquals(4, Utils::getDirectorySize(RuleSystem::getDataFolder($this->courseId)));
            $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/1-Section1.txt"));
            $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/2-Section2.txt"));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editSectionDuplicateName()
    {
        Section::addSection($this->courseId, "Section1", 0);
        $section2 = Section::addSection($this->courseId, "Section2", 1);

        try {
            $section2->editSection("Section1", 1, true);

        } catch (Exception $e) {
            $this->assertEquals("Section2", $section2->getName());
            $this->assertEquals(4, Utils::getDirectorySize(RuleSystem::getDataFolder($this->courseId)));
            $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/1-Section1.txt"));
            $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/2-Section2.txt"));
            $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/3-Graveyard.txt"));
            $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/4-Miscellaneous.txt"));
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function copySection()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section");
        $rule = Rule::addRule($this->courseId, $section->getId(), "Rule", null, "when", "then", 0);

        $course2 = Course::addCourse("Course2", "C2", "2021-2022", "#ffffff",
            null, null, true, true);

        // When
        $section->copySection($course2);

        // Then
        // Already counting w/ previous sections (miscellaneous & graveyard)
        $this->assertCount(3, RuleSystem::getSections($course2->getId()));
        $this->assertCount(1, RuleSystem::getRules($course2->getId()));

        $copiedSection = Section::getSectionByName($course2->getId(), $section->getName());
        $this->assertEquals($section->getPosition(), $copiedSection->getPosition());
        $this->assertEquals($section->getModule(), $copiedSection->getModule());
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteSection()
    {
        $section1 = Section::addSection($this->courseId, "Section1", 2);
        $section2 = Section::addSection($this->courseId, "Section2", 3);
        Section::deleteSection($section1->getId());

        $this->assertFalse($section1->exists());
        $this->assertTrue($section2->exists());
        $this->assertEquals(2, $section2->getPosition());

        // Already counting w/ previous sections (miscellaneous & graveyard)
        $sections = Section::getSections($this->courseId);
        $this->assertIsArray($sections);
        $this->assertCount(3, $sections);
        $this->assertEquals("Graveyard", $sections[0]["name"]);
        $this->assertEquals("Miscellaneous", $sections[1]["name"]);
        $this->assertEquals("Section2", $sections[2]["name"]);

        $this->assertEquals(3, Utils::getDirectorySize(RuleSystem::getDataFolder($this->courseId)));
        $this->assertFalse(file_exists(RuleSystem::getDataFolder($this->courseId) . "/3-Section1.txt"));
        $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/1-Graveyard.txt"));
        $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/2-Miscellaneous.txt"));
        $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/3-Section2.txt"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteSectionInexistentSection()
    {
        Section::addSection($this->courseId, "Section1", 2);
        Section::deleteSection(4); // Already counting w/ previous sections (miscellaneous & graveyard)

        $sections = Section::getSections($this->courseId);
        $this->assertIsArray($sections);
        $this->assertCount(3, $sections);

        $this->assertEquals("Graveyard", $sections[0]["name"]);
        $this->assertEquals("Miscellaneous", $sections[1]["name"]);
        $this->assertEquals("Section1", $sections[2]["name"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteSectionWithRules()
    {
        // Given
        $section1 = Section::addSection($this->courseId, "Section1", 2);
        $section2 = Section::addSection($this->courseId, "Section2", 3);

        $rule1 = $section1->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section1->addRule("Rule2", null, "when", "then", 1);
        $rule3 = $section2->addRule("Rule3", null, "when", "then", 0);

        // When
        Section::deleteSection($section1->getId());

        // Then
        $this->assertFalse($section1->exists());
        $this->assertTrue($section2->exists());
        $this->assertEquals(2, $section2->getPosition());

        $sections = Section::getSections($this->courseId);
        $this->assertIsArray($sections);
        $this->assertCount(3, $sections);
        $this->assertEquals("Graveyard", $sections[0]["name"]);
        $this->assertEquals("Miscellaneous", $sections[1]["name"]);
        $this->assertEquals("Section2", $sections[2]["name"]);

        // Already counting w/ previous sections (miscellaneous & graveyard)
        $this->assertEquals(3, Utils::getDirectorySize(RuleSystem::getDataFolder($this->courseId)));
        $this->assertFalse(file_exists(RuleSystem::getDataFolder($this->courseId) . "/1-Section1.txt"));
        $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/1-Graveyard.txt"));
        $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/2-Miscellaneous.txt"));
        $this->assertTrue(file_exists(RuleSystem::getDataFolder($this->courseId) . "/3-Section2.txt"));

        $this->assertFalse($rule1->exists());
        $this->assertFalse($rule2->exists());
        $this->assertTrue($rule3->exists());

        $rules = RuleSystem::getRules($this->courseId);
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals("Rule3", $rules[0]["name"]);
    }


    /**
     * @test
     * @throws Exception
     */
    public function sectionExists()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $this->assertTrue($section->exists());
    }

    /**
     * @test
     */
    public function sectionDoesntExist()
    {
        $section = new Section(3);
        $this->assertFalse($section->exists());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getRules()
    {
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = Rule::addRule($this->courseId, $section->getId(), "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $section->getId(), "Rule2", null, "when", "then", 1);

        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        $section2 = Section::addSection($course->getId(), "Section2");
        Rule::addRule($course->getId(), $section2->getId(), "Rule1", null, "when", "then", 0);

        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(2, $rules);

        $keys = ["id", "course", "section", "name", "description", "whenClause", "thenClause", "isActive", "position"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($rules as $i => $rule) {
                $this->assertCount($nrKeys, array_keys($rule));
                $this->assertArrayHasKey($key, $rule);
                $this->assertEquals($rule[$key], ${"rule".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRulesNoRules()
    {
        $section = Section::addSection($this->courseId, "Section Name");

        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        $section2 = Section::addSection($course->getId(), "Section2");
        Rule::addRule($course->getId(), $section2->getId(), "Rule1", null, "when", "then", 0);

        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }


    /**
     * @test
     * @throws Exception
     */
    public function addRule()
    {
        $section = Section::addSection($this->courseId, "Section Name");

        // No rules yet
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule1->getId(), $rules[0]["id"]);
        $this->assertEquals($rule1->getText(), file_get_contents($section->getFile()));

        // Add rule to start
        $rule2 = $section->addRule("Rule2", null, "when", "then", 0);
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(2, $rules);
        $this->assertEquals($rule2->getId(), $rules[0]["id"]);
        $this->assertEquals($rule1->getId(), $rules[1]["id"]);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule2->getText(), $rule1->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));

        // Add rule to middle
        $rule3 = $section->addRule("Rule3", null, "when", "then", 1);
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(3, $rules);
        $this->assertEquals($rule2->getId(), $rules[0]["id"]);
        $this->assertEquals($rule3->getId(), $rules[1]["id"]);
        $this->assertEquals($rule1->getId(), $rules[2]["id"]);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule2->getText(), $rule3->getText(), $rule1->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));

        // Add rule to end
        $rule4 = $section->addRule("Rule4", null, "when", "then", 3);
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(4, $rules);
        $this->assertEquals($rule2->getId(), $rules[0]["id"]);
        $this->assertEquals($rule3->getId(), $rules[1]["id"]);
        $this->assertEquals($rule1->getId(), $rules[2]["id"]);
        $this->assertEquals($rule4->getId(), $rules[3]["id"]);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule2->getText(), $rule3->getText(), $rule1->getText(), $rule4->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeRule()
    {
        $section = Section::addSection($this->courseId, "Section Name");

        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);
        $rule3 = $section->addRule("Rule3", null, "when", "then", 2);
        $rule4 = $section->addRule("Rule4", null, "when", "then", 3);

        // Remove from end
        $section->removeRule($rule4->getId());
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(3, $rules);
        $this->assertEquals($rule1->getId(), $rules[0]["id"]);
        $this->assertEquals($rule2->getId(), $rules[1]["id"]);
        $this->assertEquals($rule3->getId(), $rules[2]["id"]);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule1->getText(), $rule2->getText(), $rule3->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));

        // Remove from middle
        $section->removeRule($rule2->getId());
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(2, $rules);
        $this->assertEquals($rule1->getId(), $rules[0]["id"]);
        $this->assertEquals($rule3->getId(), $rules[1]["id"]);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule1->getText(), $rule3->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));

        // Remove from start
        $section->removeRule($rule1->getId());
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule3->getId(), $rules[0]["id"]);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule3->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));

        // Remove all
        $section->removeRule($rule3->getId());
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
        $this->assertEmpty(file_get_contents($section->getFile()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function addRuleTextStart()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);

        $section->addRuleText("TEXT", 0);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", ["TEXT", $rule1->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRuleTextMiddle()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);

        $section->addRuleText("TEXT", 1);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule1->getText(), "TEXT", $rule2->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRuleTextEnd()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);

        $section->addRuleText("TEXT", 1);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule1->getText(), "TEXT"]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRuleTextNoRulesYet()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");

        $section->addRuleText("TEXT", 0);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", ["TEXT"]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function updateRuleTextStart()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);

        $section->updateRuleText("TEXT", 0);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", ["TEXT", $rule2->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateRuleTextMiddle()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);
        $rule3 = $section->addRule("Rule3", null, "when", "then", 2);

        $section->updateRuleText("TEXT", 1);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule1->getText(), "TEXT", $rule3->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateRuleTextEnd()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);

        $section->updateRuleText("TEXT", 1);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule1->getText(), "TEXT"]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function removeRuleTextStart()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);

        $section->removeRuleText(0);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule2->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeRuleTextMiddle()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);
        $rule3 = $section->addRule("Rule3", null, "when", "then", 2);

        $section->removeRuleText(1);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule1->getText(), $rule3->getText()]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeRuleTextEnd()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $section->addRule("Rule1", null, "when", "then", 0);

        $section->removeRuleText(0);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", []);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }
}
