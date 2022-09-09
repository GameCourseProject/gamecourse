<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use PDOException;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use Utils\Utils;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class SkillTreeTest extends TestCase
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

        // Enable Skills module
        (new Awards($course))->setEnabled(true);
        $skills = new Skills($course);
        $skills->setEnabled(true);
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
        if (Core::database()->tableExists(Skill::TABLE_SKILL))
            TestingUtils::resetAutoIncrement([SkillTree::TABLE_SKILL_TREE, Tier::TABLE_SKILL_TIER, Skill::TABLE_SKILL, Skill::TABLE_SKILL_DEPENDENCY]);
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

    public function skillTreeNameSuccessProvider(): array
    {
        return [
            "null" => [null],
            "simple name" => ["Skill Tree"],
            "length limit" => ["This is some incredibly enormous skill tree nameee"]
        ];
    }

    public function skillTreeNameFailureProvider(): array
    {
        return [
            "empty" => [""],
            "too long" => ["This is some incredibly enormous skill tree nameeee"]
        ];
    }

    public function skillTreeSuccessProvider(): array
    {
        $names = array_map(function ($name) { return $name[0]; }, $this->skillTreeNameSuccessProvider());

        $provider = [];
        foreach ($names as $d1 => $name) {
            $provider["name: " . $d1 . " | maxReward: 6000"] = [$name, 6000];
        }
        return $provider;
    }

    public function skillTreeFailureProvider(): array
    {
        $names = array_map(function ($name) { return $name[0]; }, $this->skillTreeNameFailureProvider());

        $provider = [];
        foreach ($names as $d1 => $name) {
            $provider["name: " . $d1 . " | maxReward: 6000"] = [$name, 6000];
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
    public function skillTreeConstructor()
    {
        $skillTree = new SkillTree(123);
        $this->assertEquals(123, $skillTree->getId());
    }


    // Getters

    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $id = intval(Core::database()->select(SkillTree::TABLE_SKILL_TREE, ["maxReward" => 6000], "id"));
        $this->assertEquals($id, $skillTree->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $this->assertEquals($this->courseId, $skillTree->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSkillTreeName()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $this->assertNull($skillTree->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getMaxReward()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $this->assertEquals(6000, $skillTree->getMaxReward());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $this->assertEquals(["id" => 1, "course" => $this->courseId, "name" => null, "maxReward" => 6000], $skillTree->getData());
    }


    // Setters

    /**
     * @test
     * @dataProvider skillTreeNameSuccessProvider
     * @throws Exception
     */
    public function setSkillTreeNameSuccess(?string $name)
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $skillTree->setName($name);
        $this->assertEquals($name, $skillTree->getName());
    }

    /**
     * @test
     * @dataProvider skillTreeNameFailureProvider
     * @throws Exception
     */
    public function setSkillTreeNameFailure($name)
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        try {
            $skillTree->setName($name);
            $this->fail("Error should have been thrown on 'setSkillTreeNameFailure'");

        } catch (Exception $e) {
            $this->assertNull($skillTree->getName());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setMaxReward()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $skillTree->setMaxReward(1000);
        $this->assertEquals(1000, $skillTree->getMaxReward());
    }


    // General

    /**
     * @test
     * @throws Exception
     */
    public function getSkillTreeById()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $this->assertEquals($skillTree, SkillTree::getSkillTreeById($skillTree->getId()));
    }

    /**
     * @test
     */
    public function getSkillTreeByIdSkillTreeDoesntExist()
    {
        $this->assertNull(SkillTree::getSkillTreeById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSkillTreeByName()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, "Skill Tree", 6000);
        $this->assertEquals($skillTree, SkillTree::getSkillTreeByName($this->courseId, "Skill Tree"));
    }

    /**
     * @test
     */
    public function getSkillTreeByNameSkillTreeDoesntExist()
    {
        $this->assertNull(SkillTree::getSkillTreeByName($this->courseId, "Skill Tree"));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllSkillTrees()
    {
        $skillTree1 = SkillTree::addSkillTree($this->courseId, "B", 6000);
        $skillTree2 = SkillTree::addSkillTree($this->courseId, "A", 2000);

        $skillTrees = SkillTree::getSkillTrees($this->courseId);
        $this->assertIsArray($skillTrees);
        $this->assertCount(2, $skillTrees);

        $keys = ["id", "name", "maxReward"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach (array_reverse($skillTrees) as $i => $skillTree) {
                $this->assertCount($nrKeys, array_keys($skillTree));
                $this->assertArrayHasKey($key, $skillTree);
                $this->assertEquals($skillTree[$key], ${"skillTree".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllSkillTreesOrderedById()
    {
        $skillTree1 = SkillTree::addSkillTree($this->courseId, "B", 6000);
        $skillTree2 = SkillTree::addSkillTree($this->courseId, "A", 2000);

        $skillTrees = SkillTree::getSkillTrees($this->courseId, "id");
        $this->assertIsArray($skillTrees);
        $this->assertCount(2, $skillTrees);

        $keys = ["id", "name", "maxReward"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skillTrees as $i => $skillTree) {
                $this->assertCount($nrKeys, array_keys($skillTree));
                $this->assertArrayHasKey($key, $skillTree);
                $this->assertEquals($skillTree[$key], ${"skillTree".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllSkillTreesOrderedByMaxReward()
    {
        $skillTree1 = SkillTree::addSkillTree($this->courseId, "B", 6000);
        $skillTree2 = SkillTree::addSkillTree($this->courseId, "A", 2000);

        $skillTrees = SkillTree::getSkillTrees($this->courseId, "maxReward");
        $this->assertIsArray($skillTrees);
        $this->assertCount(2, $skillTrees);

        $keys = ["id", "name", "maxReward"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach (array_reverse($skillTrees) as $i => $skillTree) {
                $this->assertCount($nrKeys, array_keys($skillTree));
                $this->assertArrayHasKey($key, $skillTree);
                $this->assertEquals($skillTree[$key], ${"skillTree".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     */
    public function getAllSkillTreesNoSkillTrees()
    {
        $skillTrees = SkillTree::getSkillTrees($this->courseId);
        $this->assertIsArray($skillTrees);
        $this->assertEmpty($skillTrees);
    }


    // Skill Tree Manipulation

    /**
     * @test
     * @dataProvider skillTreeSuccessProvider
     * @throws Exception
     */
    public function addSkillTreeSuccess(?string $name, int $maxReward)
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, $name, $maxReward);

        // Check is added to database
        $skillTreeDB = SkillTree::parse(Core::database()->select(SkillTree::TABLE_SKILL_TREE, ["id" => $skillTree->getId()]));
        $this->assertEquals($skillTree->getData(), $skillTreeDB);

        // Check it has wildcard tier
        $tiers = $skillTree->getTiers();
        $this->assertNotEmpty($tiers);
        $this->assertCount(1, $tiers);
        $this->assertTrue((new Tier($tiers[0]["id"]))->isWildcard());
    }

    /**
     * @test
     * @dataProvider skillTreeFailureProvider
     * @throws Exception
     */
    public function addSkillTreeFailure($name, $maxReward)
    {
        try {
            SkillTree::addSkillTree($this->courseId, $name, $maxReward);

        } catch (Exception $e) {
            $this->assertEmpty(SkillTree::getSkillTrees($this->courseId));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addSkillTreeDuplicateName()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, "Skill Tree", 6000);
        try {
            SkillTree::addSkillTree($this->courseId, "Skill Tree", 1000);

        } catch (PDOException $e) {
            $skillTrees = SkillTree::getSkillTrees($this->courseId);
            $this->assertCount(1, $skillTrees);
            $this->assertEquals($skillTree->getId(), $skillTrees[0]["id"]);
        }
    }


    /**
     * @test
     * @dataProvider skillTreeSuccessProvider
     * @throws Exception
     */
    public function editSkillTreeSuccess(?string $name, int $maxReward)
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 1000);
        $skillTree->editSkillTree($name, $maxReward);
        $this->assertEquals($name, $skillTree->getName());
        $this->assertEquals($maxReward, $skillTree->getMaxReward());
    }

    /**
     * @test
     * @dataProvider skillTreeFailureProvider
     * @throws Exception
     */
    public function editSkillTreeFailure($name, $maxReward)
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 1000);
        try {
            $skillTree->editSkillTree($name, $maxReward);
            $this->fail("Exception should have been thrown on 'editSkillTreeFailure'");

        } catch (Exception $e) {
            $this->assertNull($skillTree->getName());
            $this->assertEquals(1000, $skillTree->getMaxReward());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editSkillTreeDuplicateName()
    {
        SkillTree::addSkillTree($this->courseId, "Skill Tree1", 6000);
        $skillTree = SkillTree::addSkillTree($this->courseId, "Skill Tree2", 2000);
        try {
            $skillTree->editSkillTree("Skill Tree1", 2000);

        } catch (PDOException $e) {
            $this->assertEquals("Skill Tree2", $skillTree->getName());
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function copySkillTree()
    {
        // Given
        $copyTo = Course::addCourse("Course Copy", "CPY", "2021-2022", "#ffffff",
            null, null, false, false);

        (new Awards($copyTo))->setEnabled(true);
        (new XPLevels($copyTo))->setEnabled(true);
        (new Skills($copyTo))->setEnabled(true);

        $skillTree = SkillTree::addSkillTree($this->courseId, "Skill Tree", 1000);
        $tier1 = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $tier2 = Tier::addTier($skillTree->getId(), "Tier 2", 200);

        $skill1 = Skill::addSkill($tier1->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier1->getId(), "Skill2", null, null, false, false, []);

        $skill3 = Skill::addSkill($tier2->getId(), "Skill3", "#ffffff", null, false, false, [
            [$skill1->getId(), $skill2->getId()]
        ]);
        $courseDataFolder = API_URL . "/" . (new Course($this->courseId))->getDataFolder(false);
        $page = "<img src=\"https://some/random/image.png\"><img src=\"" . $courseDataFolder . "/" . $skill3->getDataFolder(false) . "/image.jpg\">";
        $skill3->setPage($page);
        file_put_contents($skill3->getDataFolder() . "/file.txt", "TEST");

        // When
        $skillTree->copySkillTree($copyTo);

        // Then
        $skillTrees = SkillTree::getSkillTrees($this->courseId);
        $copiedSkillTrees = SkillTree::getSkillTrees($copyTo->getId());
        $this->assertSameSize($skillTrees, $copiedSkillTrees);
        foreach ($skillTrees as $i => $st) {
            $this->assertEquals($st["name"], $copiedSkillTrees[$i]["name"]);
            $this->assertEquals($st["maxReward"], $copiedSkillTrees[$i]["maxReward"]);

            $tiers = (new SkillTree($st["id"]))->getTiers();
            $copiedTiers = (new SkillTree($copiedSkillTrees[$i]["id"]))->getTiers();
            $this->assertSameSize($tiers, $copiedTiers);
            foreach ($tiers as $j => $t) {
                $this->assertEquals($t["name"], $copiedTiers[$j]["name"]);
                $this->assertEquals($t["reward"], $copiedTiers[$j]["reward"]);
                $this->assertEquals($t["position"], $copiedTiers[$j]["position"]);
                $this->assertEquals($t["isActive"], $copiedTiers[$j]["isActive"]);

                $skills = (new Tier($t["id"]))->getSkills();
                $copiedSkills = (new Tier($copiedTiers[$j]["id"]))->getSkills();
                $this->assertSameSize($skills, $copiedSkills);
                foreach ($skills as $k => $s) {
                    $this->assertEquals($s["name"], $copiedSkills[$k]["name"]);
                    $this->assertEquals($s["color"], $copiedSkills[$k]["color"]);
                    $this->assertEquals($s["isCollab"], $copiedSkills[$k]["isCollab"]);
                    $this->assertEquals($s["isExtra"], $copiedSkills[$k]["isExtra"]);
                    $this->assertEquals($s["isActive"], $copiedSkills[$k]["isActive"]);

                    $copiedSkill = new Skill($copiedSkills[$k]["id"]);
                    $courseDataFolder = API_URL . "/" . $copyTo->getDataFolder(false);
                    if ($s["id"] == $skill3->getId()) {
                        $this->assertEquals("<img src=\"https://some/random/image.png\"><img src=\"" . $courseDataFolder . "/" . $copiedSkill->getDataFolder(false) . "/image.jpg\">", $copiedSkills[$k]["page"]);
                        $this->assertTrue(file_exists($copiedSkill->getDataFolder() . "/file.txt"));
                        $this->assertEquals(file_get_contents((new Skill($s["id"]))->getDataFolder() . "/file.txt"), file_get_contents($copiedSkill->getDataFolder() . "/file.txt"));

                    } else {
                        $this->assertNull($copiedSkills[$k]["page"]);
                        $this->assertEmpty(Utils::getDirectoryContents($copiedSkill->getDataFolder()));
                    }

                    $this->assertSameSize($s["dependencies"], $copiedSkills[$k]["dependencies"]);
                    foreach ($s["dependencies"] as $dependencyId => $combo) {
                        foreach ($combo as $m => $sk) {
                            $this->assertEquals($sk["name"], $copiedSkills[$k]["dependencies"][$dependencyId + 1][$m]["name"]);
                        }
                    }

                    $this->assertEquals((new Rule($s["rule"]))->getText(), (new Rule($copiedSkills[$k]["rule"]))->getText());
                }
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteEmptySkillTree()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 1000);
        SkillTree::deleteSkillTree($skillTree->getId());
        $this->assertEmpty(SkillTree::getSkillTrees($this->courseId));
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteNotEmptySkillTree()
    {
        // Given
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 1000);
        $tier = Tier::addTier($skillTree->getId(), "Tier", 100);
        Skill::addSkill($tier->getId(), "Skill", null, null,  false, false, []);

        // When
        SkillTree::deleteSkillTree($skillTree->getId());

        // Then
        $this->assertEmpty(SkillTree::getSkillTrees($this->courseId));
        $this->assertEmpty(Tier::getTiers($this->courseId));
        $this->assertEmpty(Skill::getSkills($this->courseId));
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteSkillTreeInexistentSkillTree()
    {
        SkillTree::deleteSkillTree(100);
        $this->assertEmpty(SkillTree::getSkillTrees($this->courseId));
    }


    /**
     * @test
     * @throws Exception
     */
    public function skillTreeExists()
    {
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 1000);
        $this->assertTrue($skillTree->exists());
    }

    /**
     * @test
     */
    public function skillTreeDoesntExist()
    {
        $skillTree = new SkillTree(100);
        $this->assertFalse($skillTree->exists());
    }


    // Import / Export
    // TODO
}
