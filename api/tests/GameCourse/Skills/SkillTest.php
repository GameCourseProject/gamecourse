<?php
namespace GameCourse\Skills;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\Skills\SkillTree;
use GameCourse\Module\Skills\Tier;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use TypeError;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class SkillTest extends TestCase
{
    private $courseId;
    private $tierId;

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

        // Enable Skills module
        (new Awards($course))->setEnabled(true);
        $skills = new Skills($course);
        $skills->setEnabled(true);

        // Set a tier
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier", 100);
        $this->tierId = $tier->getId();
    }

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

    public static function tearDownAfterClass(): void
    {
        TestingUtils::tearDownAfterClass();
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Data Providers ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public function skillNameSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Skill Name"],
            "non-ASCII characters" => ["Skíll Name"],
            "numbers" => ["Skill123"],
            "parenthesis" => ["Skill Name (Copy)"],
            "hyphen" => ["Skill-Name"],
            "underscore" => ["Skill_Name"],
            "ampersand" => ["Skill & Name"],
            "length limit" => ["This is some incredibly humongous skill nameeeeeee"]
        ];
    }

    public function skillNameFailureProvider(): array
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
            "too long" => ["This is some incredibly humongous skill nameeeeeeee"]
        ];
    }


    public function skillColorSuccessProvider(): array
    {
        return [
            "null" => [null],
            "HEX" => ["#ffffff"]
        ];
    }

    public function skillColorFailureProvider(): array
    {
        return [
            "empty" => [""],
            "whitespace" => [" "],
            "RGB" => ["rgb(255,255,255)"]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // Constructor

    /**
     * @test
     */
    public function badgeConstructor()
    {
        $skill = new Skill(123);
        $this->assertEquals(123, $skill->getId());
    }


    // Getters

    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $id = intval(Core::database()->select(Skill::TABLE_SKILL, ["name" => "Skill"], "id"));
        $this->assertEquals($id, $skill->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals($this->courseId, $skill->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTier()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals($this->tierId, $skill->getTier()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSkillName()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals("Skill", $skill->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getColor()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertNull($skill->getColor());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPage()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertNull($skill->getPage());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPosition()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals(0, $skill->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isCollab()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, true, false, []);
        $this->assertTrue($skill->isCollab());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotCollab()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertFalse($skill->isCollab());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isExtra()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, true, []);
        $this->assertTrue($skill->isExtra());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotExtra()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertFalse($skill->isExtra());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isActive()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertTrue($skill->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isInactive()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $skill->setActive(false);
        $this->assertFalse($skill->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isWildcard()
    {
        $skill = Skill::addSkill(Tier::getWildcard((new Tier($this->tierId))->getSkillTree()->getId())->getId(), "Skill", null, null, false, false, []);
        $this->assertTrue($skill->isWildcard());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotWildcard()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertFalse($skill->isWildcard());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals(["id" => 1, "course" => $this->courseId, "tier" => $this->tierId, "name" => "Skill",
            "color" => null, "page" => null, "isCollab" => false, "isExtra" => false, "isActive" => true,
            "position" => 0, "rule" => $skill->getRule()->getId()], $skill->getData());
    }


    // Setters

    /**
     * @test
     * @throws Exception
     */
    public function setTier()
    {
        $wildcardTier = Tier::getWildcard((new Tier($this->tierId))->getSkillTree()->getId());
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($wildcardTier->getId(), "Skill3", null, null, false, false, []);

        $skill1RuleText = $skill1->getRule()->getText();
        $skill2RuleText = $skill2->getRule()->getText();
        $skill3RuleText = $skill3->getRule()->getText();

        $skill1->setTier($wildcardTier->getId());
        $this->assertEquals($wildcardTier, $skill1->getTier());
        $this->assertEquals(1, $skill1->getPosition());
        $this->assertEquals(0, $skill2->getPosition());
        $this->assertEquals(0, $skill3->getPosition());

        $this->assertEquals(1, $skill1->getRule()->getPosition());
        $this->assertEquals($skill1RuleText, $skill1->getRule()->getText());
        $this->assertEquals(2, $skill2->getRule()->getPosition());
        $this->assertEquals($skill2RuleText, $skill2->getRule()->getText());
        $this->assertEquals(0, $skill3->getRule()->getPosition());
        $this->assertEquals($skill3RuleText, $skill3->getRule()->getText());
    }

    /**
     * @test
     * @dataProvider skillNameSuccessProvider
     * @throws Exception
     */
    public function setSkillNameSuccess(string $name)
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $skill->setName($name);
        $this->assertEquals($name, $skill->getName());

        $this->assertTrue(file_exists($skill->getDataFolder(true, $name)));
        $this->assertFalse(file_exists($skill->getDataFolder(true, "Skill")));

        $this->assertEquals($name, $skill->getRule()->getName());
    }

    /**
     * @test
     * @dataProvider skillNameFailureProvider
     * @throws Exception
     */
    public function setSkillNameFailure($name) {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        try {
            $skill->setName($name);
            $this->fail("Error should have been thrown on 'setSkillNameFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Skill", $skill->getName());
            $this->assertTrue(file_exists($skill->getDataFolder(true, "Skill")));
            $this->assertEquals("Skill", $skill->getRule()->getName());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setSkillNameWithDependants()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($this->tierId, "Skill3", null, null, false, false, [
            [$skill1->getId()],
            [$skill2->getId()]
        ]);

        $skill1->setName("Skill Name");
        $this->assertEquals("Skill Name", $skill1->getName());
        $this->assertEquals($this->trim("rule: Skill3
tags: 

	when:
		combo1 = rule_unlocked(\"Skill Name\", target)
		combo2 = rule_unlocked(\"Skill2\", target)
		combo1 or combo2
		
		logs = GC.participations.getSkillParticipations(target, \"Skill3\")
		rating = get_rating(logs)
		rating >= 3

	then:
		award_skill(target, \"Skill3\", rating, logs)"), $this->trim($skill3->getRule()->getText()));
    }

    /**
     * @test
     * @dataProvider skillColorSuccessProvider
     * @throws Exception
     */
    public function setColorSuccess(?string $color)
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $skill->setColor($color);
        $this->assertEquals($color, $skill->getColor());
    }

    /**
     * @test
     * @dataProvider skillColorFailureProvider
     * @throws Exception
     */
    public function setColorFailure($color)
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        try {
            $skill->setColor($color);
            $this->fail("Error should have been thrown on 'setColorFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertNull($skill->getColor());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPage()
    {
        $skill = Skill::addSkill($this->tierId, "Skill Name", null, null, false, false, []);

        $courseDataFolder = API_URL . "/" . (new Course($this->courseId))->getDataFolder(false);
        $page = "<img src=\"https://some/random/image.png\"><img src=\"" . $courseDataFolder . "/" . $skill->getDataFolder(false) . "/image.jpg\">";
        $skill->setPage($page);

        $this->assertEquals($page, $skill->getPage());
        $this->assertEquals("<img src=\"https://some/random/image.png\"><img src=\"" . $skill->getDataFolder(false) . "/image.jpg\">",
            Core::database()->select(Skill::TABLE_SKILL, ["id" => $skill->getId()], "page"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPageNull()
    {
        $skill = Skill::addSkill($this->tierId, "Skill Name", null, "", false, false, []);
        $skill->setPage(null);
        $this->assertNull($skill->getPage());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionStart()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($this->tierId, "Skill3", null, null, false, false, []);

        $skill3->setPosition(0);
        $this->assertEquals(0, $skill3->getPosition());
        $this->assertEquals(0, $skill3->getRule()->getPosition());
        $this->assertEquals(1, $skill1->getPosition());
        $this->assertEquals(1, $skill1->getRule()->getPosition());
        $this->assertEquals(2, $skill2->getPosition());
        $this->assertEquals(2, $skill2->getRule()->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionMiddle()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($this->tierId, "Skill3", null, null, false, false, []);

        $skill3->setPosition(1);
        $this->assertEquals(1, $skill3->getPosition());
        $this->assertEquals(1, $skill3->getRule()->getPosition());
        $this->assertEquals(0, $skill1->getPosition());
        $this->assertEquals(0, $skill1->getRule()->getPosition());
        $this->assertEquals(2, $skill2->getPosition());
        $this->assertEquals(2, $skill2->getRule()->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionEnd()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($this->tierId, "Skill3", null, null, false, false, []);

        $skill1->setPosition(2);
        $this->assertEquals(2, $skill1->getPosition());
        $this->assertEquals(2, $skill1->getRule()->getPosition());
        $this->assertEquals(0, $skill2->getPosition());
        $this->assertEquals(0, $skill2->getRule()->getPosition());
        $this->assertEquals(1, $skill3->getPosition());
        $this->assertEquals(1, $skill3->getRule()->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCollab()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $skill->setCollab(true);
        $this->assertTrue($skill->isCollab());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotCollab()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, true, false, []);
        $skill->setCollab(false);
        $this->assertFalse($skill->isCollab());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setExtra()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);

        $xpLevels = new XPLevels(new Course($this->courseId));
        $xpLevels->setEnabled(true);
        $xpLevels->updateMaxExtraCredit(1000);

        $skillsModule = new Skills(new Course($this->courseId));
        $skillsModule->updateMaxExtraCredit(1000);

        $skill->setExtra(true);
        $this->assertTrue($skill->isExtra());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotExtra()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, true, []);
        $skill->setExtra(false);
        $this->assertFalse($skill->isExtra());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActive()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $skill->setActive(false);
        $skill->setActive(true);
        $this->assertTrue($skill->isActive());
        $this->assertTrue($skill->getRule()->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactive()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $skill->setActive(false);
        $this->assertFalse($skill->isActive());
        $this->assertFalse($skill->getRule()->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactiveWithDependants()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill($this->tierId, "Skill3", null, null, false, false, [
            [$skill1->getId()],
            [$skill2->getId()]
        ]);

        $skill1->setActive(false);
        $this->assertFalse($skill1->isActive());

        $dependencies = $skill3->getDependencies();
        $this->assertCount(1, $dependencies);
        $this->assertEquals($skill2->getId(), $dependencies[2][0]["id"]);
    }


    // General

    /**
     * @test
     * @throws Exception
     */
    public function getSkillById()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals($skill, Skill::getSkillById($skill->getId()));
    }

    /**
     * @test
     */
    public function getSkillByIdSkillDoesntExist()
    {
        $this->assertNull(Skill::getSkillById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSkillByName()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals($skill, Skill::getSkillByName($this->courseId, "Skill"));
    }

    /**
     * @test
     */
    public function getSkillByNameSkillDoesntExist()
    {
        $this->assertNull(Skill::getSkillByName($this->courseId, "Skill"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSkillByPosition()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals($skill, Skill::getSkillByPosition($this->tierId, 0));
    }

    /**
     * @test
     */
    public function getSkillByPositionSkillDoesntExist()
    {
        $this->assertNull(Skill::getSkillByPosition($this->tierId, 0));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSkillByRule()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertEquals($skill, Skill::getSkillByRule($skill->getRule()->getId()));
    }

    /**
     * @test
     */
    public function getSkillByRuleSkillDoesntExist()
    {
        $this->assertNull(Skill::getSkillByRule(100));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllSkills()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);

        $skillTree2 = SkillTree::addSkillTree($this->courseId, null, 1000);
        $skill3 = Skill::addSkill(Tier::getWildcard($skillTree2->getId())->getId(), "Skill3", null, null, false, false, []);

        $skills = Skill::getSkills($this->courseId);
        $this->assertIsArray($skills);
        $this->assertCount(3, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], ${"skill".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllActiveSkills()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill1->setActive(false);

        $skillTree2 = SkillTree::addSkillTree($this->courseId, null, 1000);
        $skill3 = Skill::addSkill(Tier::getWildcard($skillTree2->getId())->getId(), "Skill3", null, null, false, false, []);

        $skills = Skill::getSkills($this->courseId, true);
        $this->assertIsArray($skills);
        $this->assertCount(2, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], ${"skill".($i+2)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllInactiveSkills()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill1->setActive(false);

        $skillTree2 = SkillTree::addSkillTree($this->courseId, null, 1000);
        $skill3 = Skill::addSkill(Tier::getWildcard($skillTree2->getId())->getId(), "Skill3", null, null, false, false, []);

        $skills = Skill::getSkills($this->courseId, false);
        $this->assertIsArray($skills);
        $this->assertCount(1, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], $skill1->getData($key));
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllSkillsOfSkillTree()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);

        $skillTreeId = $skill1->getTier()->getSkillTree()->getId();
        $skills = Skill::getSkillsOfSkillTree($skillTreeId);
        $this->assertIsArray($skills);
        $this->assertCount(2, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], ${"skill".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllActiveSkillsOfSkillTree()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill2->setActive(false);

        $skillTreeId = $skill1->getTier()->getSkillTree()->getId();
        $skills = Skill::getSkillsOfSkillTree($skillTreeId, true);
        $this->assertIsArray($skills);
        $this->assertCount(1, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], $skill1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllInactiveSkillsOfSkillTree()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill2->setActive(false);

        $skillTreeId = $skill1->getTier()->getSkillTree()->getId();
        $skills = Skill::getSkills($skillTreeId, false);
        $this->assertIsArray($skills);
        $this->assertCount(1, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], $skill2->getData($key));
            }
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllSkillsOfTier()
    {
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill(Tier::getWildcard($skillTreeId)->getId(), "Skill3", null, null, false, false, []);

        $skills = Skill::getSkillsOfTier($this->tierId);
        $this->assertIsArray($skills);
        $this->assertCount(2, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], ${"skill".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllActiveSkillsOfTier()
    {
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill(Tier::getWildcard($skillTreeId)->getId(), "Skill3", null, null, false, false, []);
        $skill2->setActive(false);

        $skills = Skill::getSkillsOfTier($this->tierId, true);
        $this->assertIsArray($skills);
        $this->assertCount(1, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], ${"skill".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllInactiveSkillsOfTier()
    {
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skill3 = Skill::addSkill(Tier::getWildcard($skillTreeId)->getId(), "Skill3", null, null, false, false, []);
        $skill1->setActive(false);

        $skills = Skill::getSkillsOfTier($this->tierId, false);
        $this->assertIsArray($skills);
        $this->assertCount(1, $skills);

        $keys = ["id", "course", "tier", "name", "color", "page", "isCollab", "isExtra", "isActive", "position", "rule", "dependencies"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($skills as $i => $skill) {
                $this->assertCount($nrKeys, array_keys($skill));
                $this->assertArrayHasKey($key, $skill);
                if ($key == "dependencies") $this->assertEmpty($skill[$key]);
                else $this->assertEquals($skill[$key], ${"skill".($i+1)}->getData($key));
            }
        }
    }


    // Skill Manipulation
    // TODO


    // Dependencies
    // TODO


    // Rules
    // TODO


    // Skill Data
    // TODO


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
