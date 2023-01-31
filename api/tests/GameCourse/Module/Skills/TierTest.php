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
use TypeError;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class TierTest extends TestCase
{
    private $courseId;
    private $skillTreeId;

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

        // Set a Skill Tree
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $this->skillTreeId = $skillTree->getId();
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

    public function tierNameSuccessProvider(): array
    {
        return [
            "simple name" => ["Tier 1"],
            "trimmed" => [" This is some incredibly enormous skill tier nameee "],
            "length limit" => ["This is some incredibly enormous skill tier nameee"]
        ];
    }

    public function tierNameFailureProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "wildcard" => [Tier::WILDCARD],
            "too long" => ["This is some incredibly enormous skill tier nameeee"]
        ];
    }


    public function tierSuccessProvider(): array
    {
        $names = array_map(function ($name) { return $name[0]; }, $this->tierNameSuccessProvider());

        $provider = [];
        foreach ($names as $d1 => $name) {
            $provider["name: " . $d1 . " | reward: 100"] = [$name, 100];
        }
        $provider["with fixed cost"] = ["Tier 1", 100, "fixed", 10, 0];
        $provider["with variable cost"] = ["Tier 1", 100, "variable", 10, 5];
        $provider["with variable cost; not default min. rating"] = ["Tier 1", 100, "variable", 10, 5, 0];

        return $provider;
    }

    public function tierFailureProvider(): array
    {
        $names = array_map(function ($name) { return $name[0]; }, $this->tierNameFailureProvider());

        $provider = [];
        foreach ($names as $d1 => $name) {
            $provider["name: " . $d1 . " | reward: 100"] = [$name, 100];
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
    public function tierConstructor()
    {
        $tier = new Tier(123);
        $this->assertEquals(123, $tier->getId());
    }


    // Getters

    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $id = intval(Core::database()->select(Tier::TABLE_SKILL_TIER, ["reward" => 100], "id"));
        $this->assertEquals($id, $tier->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals($this->courseId, $tier->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSkillTree()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals($this->skillTreeId, $tier->getSkillTree()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTierName()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals("Tier", $tier->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getReward()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals(100, $tier->getReward());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPosition()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals(0, $tier->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCostType()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals("fixed", $tier->getCostType());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCost()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals(0, $tier->getCost());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getIncrement()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals(0, $tier->getIncrement());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getMinRating()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals(3, $tier->getMinRating());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isActive()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertTrue($tier->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isInactive()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setActive(false);
        $this->assertFalse($tier->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isWildcard()
    {
        $wildcardTier = Tier::getWildcard($this->skillTreeId);
        $this->assertTrue($wildcardTier->isWildcard());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotWildcard()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertFalse($tier->isWildcard());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals(["id" => 2, "skillTree" => $this->skillTreeId, "name" => "Tier", "reward" => 100, "position" => 0,
            "isActive" => true, "costType" => "fixed", "cost" => 0, "increment" => 0, "minRating" => 3], $tier->getData());
    }


    // Setters

    /**
     * @test
     * @dataProvider tierNameSuccessProvider
     * @throws Exception
     */
    public function setTierNameSuccess(string $name)
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setName($name);
        $this->assertEquals(trim($name), $tier->getName());
    }

    /**
     * @test
     * @dataProvider tierNameFailureProvider
     * @throws Exception
     */
    public function setTierNameFailure($name)
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        try {
            $tier->setName($name);
            $this->fail("Error should have been thrown on 'setTierNameFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Tier", $tier->getName());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setWildcardTierName()
    {
        $wildcardTier = Tier::getWildcard($this->skillTreeId);
        try {
            $wildcardTier->setName("New Name");
            $this->fail("Error should have been thrown on 'setWildcardTierName'");

        } catch (Exception $e) {
            $this->assertEquals(Tier::WILDCARD, $wildcardTier->getName());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setReward()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setReward(200);
        $this->assertEquals(200, $tier->getReward());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionStart()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 200);
        $tier3 = Tier::addTier($this->skillTreeId, "Tier3", 300);

        $tier3->setPosition(0);
        $this->assertEquals(0, $tier3->getPosition());
        $this->assertEquals(1, $tier1->getPosition());
        $this->assertEquals(2, $tier2->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionMiddle()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 200);
        $tier3 = Tier::addTier($this->skillTreeId, "Tier3", 300);

        $tier3->setPosition(1);
        $this->assertEquals(1, $tier3->getPosition());
        $this->assertEquals(0, $tier1->getPosition());
        $this->assertEquals(2, $tier2->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionEnd()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 200);
        $tier3 = Tier::addTier($this->skillTreeId, "Tier3", 300);

        $tier1->setPosition(2);
        $this->assertEquals(2, $tier1->getPosition());
        $this->assertEquals(0, $tier2->getPosition());
        $this->assertEquals(1, $tier3->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCostType()
    {
        // Variable
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setCostType("variable");
        $this->assertEquals("variable", $tier->getCostType());

        // Fixed
        $tier->setIncrement(10);
        $tier->setMinRating(0);
        $tier->setCostType("fixed");
        $this->assertEquals("fixed", $tier->getCostType());
        $this->assertEquals(0, $tier->getIncrement());
        $this->assertEquals(3, $tier->getMinRating());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCost()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setCost(10);
        $this->assertEquals(10, $tier->getCost());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setIncrement()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setIncrement(10);
        $this->assertEquals(10, $tier->getIncrement());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setMinRating()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setMinRating(0);
        $this->assertEquals(0, $tier->getMinRating());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActive()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setActive(false);
        $tier->setActive(true);
        $this->assertTrue($tier->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactive()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $tier->setActive(false);
        $this->assertFalse($tier->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactiveWithSkills()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $skill1 = Skill::addSkill($tier->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier->getId(), "Skill2", null, null, false, false, []);

        $this->assertTrue($skill1->isActive());
        $this->assertTrue($skill2->isActive());

        $tier->setActive(false);
        $this->assertFalse($tier->isActive());
        $this->assertFalse($skill1->isActive());
        $this->assertFalse($skill2->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactiveWildcard()
    {
        // Given
        $wildcardTier = Tier::getWildcard($this->skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $skill1 = Skill::addSkill($tier1->getId(), "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // When
        $wildcardTier->setActive(false);

        // Then
        $this->assertFalse($wildcardTier->isActive());
        $this->assertFalse($skillWildcard->isActive());
        $this->assertFalse($skill2->hasWildcardDependency());
        $this->assertCount(1, $skill2->getDependencies());
    }


    // General

    /**
     * @test
     * @throws Exception
     */
    public function getTierById()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals($tier, Tier::getTierById($tier->getId()));
    }

    /**
     * @test
     */
    public function getTierByIdTierDoesntExist()
    {
        $this->assertNull(Tier::getTierById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTierByName()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals($tier, Tier::getTierByName($this->skillTreeId, "Tier"));
    }

    /**
     * @test
     */
    public function getTierByNameTierDoesntExist()
    {
        $this->assertNull(Tier::getTierByName($this->skillTreeId, "Tier"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTierByPosition()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertEquals($tier, Tier::getTierByPosition($this->skillTreeId, 0));
    }

    /**
     * @test
     */
    public function getTierByPositionTierDoesntExist()
    {
        $this->assertNull(Tier::getTierByPosition($this->skillTreeId, 1));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getWildcard()
    {
        Tier::addTier($this->skillTreeId, "Tier", 100);
        $wildcardTier = Tier::getWildcard($this->skillTreeId);

        $this->assertEquals(Tier::WILDCARD, $wildcardTier->getName());
        $this->assertEquals(1, $wildcardTier->getPosition());
        $this->assertEquals(0, $wildcardTier->getReward());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllTiers()
    {
        $skillTree2 = SkillTree::addSkillTree($this->courseId, null, 1000);
        $tier4 = Tier::addTier($skillTree2->getId(), "Tier4", 100);
        $tier5 = Tier::getWildcard($skillTree2->getId());

        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 100);
        $tier3 = Tier::getWildcard($this->skillTreeId);

        $tiers = Tier::getTiers($this->courseId);
        $this->assertIsArray($tiers);
        $this->assertCount(5, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($key === "costType") $this->assertEquals("fixed", ${"tier".($i+1)}->getData($key));
                if ($key === "cost" || $key === "increment") $this->assertEquals(0, ${"tier".($i+1)}->getData($key));
                if ($key === "minRating") $this->assertEquals(3, ${"tier".($i+1)}->getData($key));
                $this->assertEquals($tier[$key], ${"tier".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllActiveTiers()
    {
        $skillTree2 = SkillTree::addSkillTree($this->courseId, null, 1000);
        $tier4 = Tier::addTier($skillTree2->getId(), "Tier4", 100);
        $tier5 = Tier::getWildcard($skillTree2->getId());

        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 100);
        $tier3 = Tier::getWildcard($this->skillTreeId);

        $tier1->setActive(false);

        $tiers = Tier::getTiers($this->courseId, true);
        $this->assertIsArray($tiers);
        $this->assertCount(4, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($key === "costType") $this->assertEquals("fixed", ${"tier".($i+1)}->getData($key));
                if ($key === "cost" || $key === "increment") $this->assertEquals(0, ${"tier".($i+1)}->getData($key));
                if ($key === "minRating") $this->assertEquals(3, ${"tier".($i+1)}->getData($key));
                $this->assertEquals($tier[$key], ${"tier".($i+2)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllInactiveTiers()
    {
        $skillTree2 = SkillTree::addSkillTree($this->courseId, null, 1000);
        $tier4 = Tier::addTier($skillTree2->getId(), "Tier4", 100);
        $tier5 = Tier::getWildcard($skillTree2->getId());

        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 100);
        $tier3 = Tier::getWildcard($this->skillTreeId);

        $tier1->setActive(false);
        $tier2->setActive(false);

        $tiers = Tier::getTiers($this->courseId, false);
        $this->assertIsArray($tiers);
        $this->assertCount(2, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($key === "costType") $this->assertEquals("fixed", ${"tier".($i+1)}->getData($key));
                if ($key === "cost" || $key === "increment") $this->assertEquals(0, ${"tier".($i+1)}->getData($key));
                if ($key === "minRating") $this->assertEquals(3, ${"tier".($i+1)}->getData($key));
                $this->assertEquals($tier[$key], ${"tier".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllTiersOrderedByName()
    {
        $skillTree2 = SkillTree::addSkillTree($this->courseId, null, 1000);
        $tier4 = Tier::addTier($skillTree2->getId(), "A", 100);
        $tier5 = Tier::getWildcard($skillTree2->getId());

        $tier2 = Tier::addTier($this->skillTreeId, "C", 100);
        $tier1 = Tier::addTier($this->skillTreeId, "B", 100);
        $tier3 = Tier::getWildcard($this->skillTreeId);

        $tiers = Tier::getTiers($this->courseId, null, "st.id, t.name");
        $this->assertIsArray($tiers);
        $this->assertCount(5, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($key === "costType") $this->assertEquals("fixed", ${"tier".($i+1)}->getData($key));
                if ($key === "cost" || $key === "increment") $this->assertEquals(0, ${"tier".($i+1)}->getData($key));
                if ($key === "minRating") $this->assertEquals(3, ${"tier".($i+1)}->getData($key));
                $this->assertEquals($tier[$key], ${"tier".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllTiersOnlyWildcardTier()
    {
        $skillTree2 = SkillTree::addSkillTree($this->courseId, null, 1000);
        $tier2 = Tier::getWildcard($skillTree2->getId());

        $tier1 = Tier::getWildcard($this->skillTreeId);

        $tiers = Tier::getTiers($this->courseId);
        $this->assertIsArray($tiers);
        $this->assertCount(2, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($key === "costType") $this->assertEquals("fixed", ${"tier".($i+1)}->getData($key));
                if ($key === "cost" || $key === "increment") $this->assertEquals(0, ${"tier".($i+1)}->getData($key));
                if ($key === "minRating") $this->assertEquals(3, ${"tier".($i+1)}->getData($key));
                $this->assertEquals($tier[$key], ${"tier".($i+1)}->getData($key));
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllTiersOfSkillTree()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 100);
        $tier3 = Tier::getWildcard($this->skillTreeId);

        $tiers = Tier::getTiers($this->courseId);
        $this->assertIsArray($tiers);
        $this->assertCount(3, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($key === "costType") $this->assertEquals("fixed", ${"tier".($i+1)}->getData($key));
                if ($key === "cost" || $key === "increment") $this->assertEquals(0, ${"tier".($i+1)}->getData($key));
                if ($key === "minRating") $this->assertEquals(3, ${"tier".($i+1)}->getData($key));
                $this->assertEquals($tier[$key], ${"tier".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllActiveTiersOfSkillTree()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 100);
        $tier3 = Tier::getWildcard($this->skillTreeId);

        $tier1->setActive(false);

        $tiers = Tier::getTiers($this->courseId, true);
        $this->assertIsArray($tiers);
        $this->assertCount(2, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($key === "costType") $this->assertEquals("fixed", ${"tier".($i+1)}->getData($key));
                if ($key === "cost" || $key === "increment") $this->assertEquals(0, ${"tier".($i+1)}->getData($key));
                if ($key === "minRating") $this->assertEquals(3, ${"tier".($i+1)}->getData($key));
                $this->assertEquals($tier[$key], ${"tier".($i+2)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllInactiveTiersOfSkillTree()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 100);
        $tier3 = Tier::getWildcard($this->skillTreeId);

        $tier1->setActive(false);
        $tier2->setActive(false);

        $tiers = Tier::getTiers($this->courseId, false);
        $this->assertIsArray($tiers);
        $this->assertCount(2, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($key === "costType") $this->assertEquals("fixed", ${"tier".($i+1)}->getData($key));
                if ($key === "cost" || $key === "increment") $this->assertEquals(0, ${"tier".($i+1)}->getData($key));
                if ($key === "minRating") $this->assertEquals(3, ${"tier".($i+1)}->getData($key));
                $this->assertEquals($tier[$key], ${"tier".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllTiersOrderedByNameOfSkillTree()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "C", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "B", 100);
        $tier3 = Tier::getWildcard($this->skillTreeId);

        $tiers = Tier::getTiers($this->courseId, null, "name");
        $this->assertIsArray($tiers);
        $this->assertCount(3, $tiers);

        $keys = ["id", "skillTree", "name", "reward", "position", "isActive", "costType", "cost", "increment", "minRating"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tiers as $i => $tier) {
                $this->assertCount($nrKeys, array_keys($tier));
                $this->assertArrayHasKey($key, $tier);
                if ($i == 0) $this->assertEquals($tier2->getId(), $tier["id"]);
                else if ($i == 1) $this->assertEquals($tier1->getId(), $tier["id"]);
                else $this->assertEquals($tier3->getId(), $tier["id"]);
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllTiersOnlyWildcardTierOfSkillTree()
    {
        $tiers = Tier::getTiers($this->courseId);
        $this->assertIsArray($tiers);
        $this->assertCount(1, $tiers);
        $this->assertTrue((new Tier($tiers[0]["id"]))->isWildcard());
    }


    // Tier Manipulation

    /**
     * @test
     * @dataProvider tierSuccessProvider
     * @throws Exception
     */
    public function addTierSuccess(string $name, int $reward, string $costType = null, int $cost = null,
                                   int $increment = null, int $minRating = null)
    {
        $tier = Tier::addTier($this->skillTreeId, $name, $reward, $costType, $cost, $increment, $minRating);

        // Check is added to database
        $tierDB = Tier::getTiers($this->courseId)[0];
        $this->assertEquals($tier->getData(), $tierDB);

        // Check position is correct
        $this->assertEquals(0, $tier->getPosition());
    }

    /**
     * @test
     * @dataProvider tierFailureProvider
     * @throws Exception
     */
    public function addTierFailure($name, $reward)
    {
        try {
            Tier::addTier($this->skillTreeId, $name, $reward);

        } catch (Exception|TypeError $e) {
            $tiers = Tier::getTiersOfSkillTree($this->skillTreeId);
            $this->assertCount(1, $tiers);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addTierDuplicateName()
    {
        Tier::addTier($this->skillTreeId, "Tier", 100);
        try {
            Tier::addTier($this->skillTreeId, "Tier", 200);

        } catch (PDOException $e) {
            $tiers = Tier::getTiersOfSkillTree($this->skillTreeId);
            $this->assertCount(2, $tiers);
        }
    }


    /**
     * @test
     * @dataProvider tierSuccessProvider
     * @throws Exception
     */
    public function editTierSuccess(string $name, int $reward)
    {
        $tier = Tier::addTier($this->skillTreeId, $name, $reward);
        $tier->editTier($name, $reward, $tier->getPosition(), $tier->isActive());
        $this->assertEquals(trim($name), $tier->getName());
        $this->assertEquals($reward, $tier->getReward());
    }

    /**
     * @test
     * @dataProvider tierFailureProvider
     * @throws Exception
     */
    public function editTierFailure($name, $reward)
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        try {
            $tier->editTier($name, $reward, $tier->getPosition(), $tier->isActive());

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Tier", $tier->getName());
            $this->assertEquals(100, $tier->getReward());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editTierDuplicateName()
    {
        Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier = Tier::addTier($this->skillTreeId, "Tier2", 200);
        try {
            $tier->editTier("Tier1", 200, $tier->getPosition(), $tier->isActive());

        } catch (Exception $e) {
            $this->assertEquals("Tier2", $tier->getName());
            $this->assertEquals(200, $tier->getReward());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editTierPositionChanged()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 200);

        $tier1->editTier($tier1->getName(), $tier1->getReward(), 1, $tier1->isActive());
        $this->assertEquals(1, $tier1->getPosition());
        $this->assertEquals(0, $tier2->getPosition());
    }


    /**
     * @test
     * @throws Exception
     */
    public function copyTier()
    {
        // Given
        $copyTo = Course::addCourse("Course Copy", "CPY", "2021-2022", "#ffffff",
            null, null, false, false);

        (new Awards($copyTo))->setEnabled(true);
        (new XPLevels($copyTo))->setEnabled(true);
        (new Skills($copyTo))->setEnabled(true);

        $cpSkillTree = SkillTree::addSkillTree($copyTo->getId(), "Skill Tree", 1000);
        $cpTier1 = Tier::addTier($cpSkillTree->getId(), "Tier 1", 100);

        $cpSkill1 = Skill::addSkill($cpTier1->getId(), "Skill1", null, null, false, false, []);
        $cpSkill2 = Skill::addSkill($cpTier1->getId(), "Skill2", null, null, false, false, []);

        $skillTree = SkillTree::addSkillTree($this->courseId, "Skill Tree", 1000);
        $tier1 = Tier::addTier($skillTree->getId(), "Tier 1", 100);
        $tier2 = Tier::addTier($skillTree->getId(), "Tier 2", 200);

        $skill1 = Skill::addSkill($tier1->getId(), "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($tier1->getId(), "Skill2", null, null, false, false, []);

        $skill = Skill::addSkill($tier2->getId(), "Skill", "#ffffff", null, false, false, [
            [$skill1->getId(), $skill2->getId()]
        ]);
        $courseDataFolder = API_URL . "/" . (new Course($this->courseId))->getDataFolder(false);
        $page = "<img src=\"https://some/random/image.png\"><img src=\"" . $courseDataFolder . "/" . $skill->getDataFolder(false) . "/image.jpg\">";
        $skill->setPage($page);
        file_put_contents($skill->getDataFolder() . "/file.txt", "TEST");

        // When
        $tier2->copyTier($cpSkillTree);

        // Then
        $tiers = $skillTree->getTiers();
        $copiedTiers = $cpSkillTree->getTiers();
        $this->assertSameSize($tiers, $copiedTiers);
        foreach ($tiers as $i => $tier) {
            $this->assertEquals($tier["name"], $copiedTiers[$i]["name"]);
            $this->assertEquals($tier["reward"], $copiedTiers[$i]["reward"]);
            $this->assertEquals($tier["position"], $copiedTiers[$i]["position"]);
            $this->assertEquals($tier["isActive"], $copiedTiers[$i]["isActive"]);
            $this->assertEquals($tier["costType"], $copiedTiers[$i]["costType"]);
            $this->assertEquals($tier["cost"], $copiedTiers[$i]["cost"]);
            $this->assertEquals($tier["increment"], $copiedTiers[$i]["increment"]);
            $this->assertEquals($tier["minRating"], $copiedTiers[$i]["minRating"]);
        }

        $skills = $tier2->getSkills();
        $copiedSkills = Tier::getTierByName($cpSkillTree->getId(), $tier2->getName())->getSkills();
        $this->assertSameSize($skills, $copiedSkills);
        foreach ($skills as $i => $skill) {
            $this->assertEquals($skill["name"], $copiedSkills[$i]["name"]);
            $this->assertEquals($skill["color"], $copiedSkills[$i]["color"]);
            $this->assertEquals($skill["isCollab"], $copiedSkills[$i]["isCollab"]);
            $this->assertEquals($skill["isExtra"], $copiedSkills[$i]["isExtra"]);
            $this->assertEquals($skill["isActive"], $copiedSkills[$i]["isActive"]);
            $this->assertEquals($skill["position"], $copiedSkills[$i]["position"]);

            $copiedSkill = new Skill($copiedSkills[$i]["id"]);
            $courseDataFolder = API_URL . "/" . $copyTo->getDataFolder(false);
            $this->assertEquals("<img src=\"https://some/random/image.png\"><img src=\"" . $courseDataFolder . "/" . $copiedSkill->getDataFolder(false) . "/image.jpg\">", $copiedSkills[$i]["page"]);

            $this->assertTrue(file_exists($copiedSkill->getDataFolder() . "/file.txt"));
            $this->assertEquals(file_get_contents((new Skill($skill["id"]))->getDataFolder() . "/file.txt"), file_get_contents($copiedSkill->getDataFolder() . "/file.txt"));

            $this->assertSameSize($skill["dependencies"], $copiedSkills[$i]["dependencies"]);
            foreach ($skill["dependencies"] as $dependencyId => $combo) {
                foreach ($combo as $j => $s) {
                    $this->assertEquals($s["name"], $copiedSkills[$i]["dependencies"][$dependencyId + 1][$j]["name"]);
                }
            }

            $this->assertEquals((new Rule($skill["rule"]))->getText(), (new Rule($copiedSkills[$i]["rule"]))->getText());
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteEmptyTier()
    {
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 100);
        Tier::deleteTier($tier1->getId());

        $tiers = Tier::getTiers($this->courseId);
        $this->assertCount(2, $tiers);

        $this->assertEquals(0, $tier2->getPosition());
        $this->assertEquals(1, Tier::getWildcard($this->skillTreeId)->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteNotEmptyTier()
    {
        // Given
        $tier1 = Tier::addTier($this->skillTreeId, "Tier1", 100);
        $tier2 = Tier::addTier($this->skillTreeId, "Tier2", 100);
        Skill::addSkill($tier1->getId(), "Skill", null, null,  false, false, []);

        // When
        Tier::deleteTier($tier1->getId());

        // Then
        $tiers = Tier::getTiers($this->courseId);
        $this->assertCount(2, $tiers);
        $this->assertEmpty(Skill::getSkills($this->courseId));

        $this->assertEquals(0, $tier2->getPosition());
        $this->assertEquals(1, Tier::getWildcard($this->skillTreeId)->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteTierInexistentTier()
    {
        Tier::deleteTier(100);
        $this->assertCount(1, Tier::getTiers($this->courseId));
    }

    /**
     * @test
     */
    public function deleteWildcardTier()
    {
        $wildcardTier = Tier::getWildcard($this->skillTreeId);
        try {
            Tier::deleteTier($wildcardTier->getId());
            $this->fail("Error should have been thrown on 'deleteWildcardTier'");

        } catch (Exception $e) {
            $this->assertCount(1, Tier::getTiers($this->courseId));
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function tierExists()
    {
        $tier = Tier::addTier($this->skillTreeId, "Tier", 100);
        $this->assertTrue($tier->exists());
    }

    /**
     * @test
     */
    public function tierDoesntExist()
    {
        $tier = new Tier(100);
        $this->assertFalse($tier->exists());
    }


    // Import / Export
    // TODO
}
