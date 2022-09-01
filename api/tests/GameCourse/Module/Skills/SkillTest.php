<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;
use TypeError;
use Utils\Utils;

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

        // Set a tier
        $skillTree = SkillTree::addSkillTree($this->courseId, null, 6000);
        $tier = Tier::addTier($skillTree->getId(), "Tier", 100);
        $this->tierId = $tier->getId();
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


    public function skillSuccessProvider(): array
    {
        return [
            "default" => ["Skill Name", null, null, false, false, []],
            "with color" => ["Skill Name", "#ffffff", null, false, false, []],
            "with page" => ["Skill Name", null, "<h1>Hello World</h1>", false, false, []],
            "collab" => ["Skill Name", null, null, true, false, []]
        ];
    }

    public function skillFailureProvider(): array
    {
        return [
            "invalid name" => [null, null, null, false, false, []],
            "invalid color" => ["Skill Name", "RGB(255, 255, 255)", null, false, false, []],
            "invalid page" => ["Skill Name", null, "", false, false, []]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // Constructor

    /**
     * @test
     */
    public function skillConstructor()
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

        $tier2 = Tier::addTier((new Tier($this->tierId))->getSkillTree()->getId(), "Tier2", 200);
        $skill3 = Skill::addSkill($tier2->getId(), "Skill3", null, null, false, false, [
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
        $skill = Skill::addSkill($this->tierId, "Skill Name", null, "PAGE", false, false, []);
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
        // Given
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [[1]]);

        // When
        $skill1->setActive(false);

        // Then
        $this->assertFalse($skill1->isActive());
        $this->assertEmpty($skill2->getDependencies());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactiveLastWildcardSkill()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // When
        $skillWildcard->setActive(false);

        // Then
        $this->assertTrue($wildcardTier->isActive());
        $this->assertFalse($skillWildcard->isActive());
        $this->assertFalse($skill2->hasWildcardDependency());
        $this->assertCount(1, $skill2->getDependencies());
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
            foreach ($skills as $skill) {
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
            foreach ($skills as $skill) {
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
            foreach ($skills as $skill) {
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

    /**
     * @test
     * @dataProvider skillSuccessProvider
     * @throws Exception
     */
    public function addSkillSuccess(string $name, ?string $color, ?string $page, bool $isCollab, bool $isExtra,
                                    array $dependencies)
    {
        $skill = Skill::addSkill($this->tierId, $name, $color, $page, $isCollab, $isExtra, $dependencies);

        // Check is added to database
        $skillDB = Skill::parse(Core::database()->select(Skill::TABLE_SKILL, ["id" => $skill->getId()]));
        $this->assertEquals($skill->getData(), $skillDB);

        // Check dependencies
        $this->assertSameSize($dependencies, $skill->getDependencies());

        // Check data folder was created
        $this->assertTrue(file_exists($skill->getDataFolder()));

        // Check rule was created
        $rule = $skill->getRule();
        $this->assertTrue($rule->exists());
        $this->assertEquals($this->trim("rule: $name
tags: 

	when:
		logs = GC.participations.getSkillParticipations(target, \"$name\")
		rating = get_rating(logs)
		rating >= 3

	then:
		award_skill(target, \"$name\", rating, logs)"), $this->trim($rule->getText()));
    }

    /**
     * @test
     * @dataProvider skillFailureProvider
     * @throws Exception
     */
    public function addSkillFailure($name, $color, $page, $isCollab, $isExtra, $dependencies)
    {
        try {
            Skill::addSkill($this->tierId, $name, $color, $page, $isCollab, $isExtra, $dependencies);
            $this->fail("Error should have been thrown on 'addSkillFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEmpty(Skill::getSkills($this->courseId));
            $this->assertEquals(0, Utils::getDirectorySize((new Skills(new Course($this->courseId)))->getDataFolder()));
            $this->assertEmpty(Section::getSectionByName($this->courseId, Skills::RULE_SECTION)->getRules());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addSkillDuplicateName()
    {
        $skill = Skill::addSkill($this->tierId, "Skill Name", null, null, false, false, []);

        try {
            Skill::addSkill($this->tierId, $skill->getName(), null, null, false, false, []);
            $this->fail("Error should have been thrown on 'addSkillDuplicateName'");

        } catch (Exception|TypeError $e) {
            $this->assertCount(1, Skill::getSkills($this->courseId));
            $this->assertEquals(1, Utils::getDirectorySize((new Skills(new Course($this->courseId)))->getDataFolder()));
            $this->assertCount(1, Section::getSectionByName($this->courseId, Skills::RULE_SECTION)->getRules());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addSkillWithDependencies()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);

        // When
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // Then
        $this->assertCount(3, Skill::getSkills($this->courseId));
        $this->assertCount(2, $skill2->getDependencies());

        $rule = $skill2->getRule();
        $this->assertTrue($rule->exists());
        $this->assertEquals($this->trim("rule: Skill2
tags: 

	when:
		user = GC.users.getUser(target)
		wildcard = user.hasWildcardAvailable($skillTreeId)
		
		combo1 = rule_unlocked(\"Skill1\", target)
		combo2 = wildcard
		combo1 or combo2
		
		skill_based = combo1
		use_wildcard = False if skill_based else True
		
		logs = GC.participations.getSkillParticipations(target, \"Skill2\")
		rating = get_rating(logs)
		rating >= 3

	then:
		award_skill(target, \"Skill2\", rating, logs, use_wildcard, \"" . Tier::WILDCARD . "\")"), $this->trim($rule->getText()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function addWildcardSkillWithDependencies()
    {
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);

        try {
            $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, [
                [0]
            ]);
            $this->fail("Error should have been thrown on 'addWildcardSkillWithDependencies'");

        } catch (Exception $e) {
            $this->assertEmpty(Skill::getSkills($this->courseId));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addFirstSkillWithDependencies()
    {
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        try {
            Skill::addSkill($this->tierId, "Skill Name", null, null, false, false, [[0]]);
            $this->fail("Error should have been thrown on 'addFirstSkillWithDependencies'");

        } catch (Exception $e) {
            $this->assertCount(1, Skill::getSkills($this->courseId));
        }
    }


    /**
     * @test
     * @dataProvider skillSuccessProvider
     * @throws Exception
     */
    public function editSkillSuccess(string $name, ?string $color, ?string $page, bool $isCollab, bool $isExtra,
                                     array $dependencies)
    {
        $skill = Skill::addSkill($this->tierId, "NAME", "#ffffff", "PAGE", false, false, []);
        $skill->editSkill($this->tierId, $name, $color, $page, $isCollab, $isExtra, true, $skill->getPosition(), $dependencies);

        $this->assertEquals($name, $skill->getName());
        $this->assertEquals($color, $skill->getColor());
        $this->assertEquals($page, $skill->getPage());
        $this->assertEquals($isCollab, $skill->isCollab());
        $this->assertEquals($isExtra, $skill->isExtra());
        $this->assertTrue($skill->isActive());
        $this->assertSameSize($dependencies, $skill->getDependencies());
    }

    /**
     * @test
     * @dataProvider skillFailureProvider
     * @throws Exception
     */
    public function editSkillFailure($name, $color, $page, $isCollab, $isExtra, $dependencies)
    {
        $skill = Skill::addSkill($this->tierId, "NAME", "#ffffff", "PAGE", false, false, []);
        try {
            $skill->editSkill($this->tierId, $name, $color, $page, $isCollab, $isExtra, true, $skill->getPosition(), $dependencies);
            $this->fail("Error should have been thrown on 'editSkillFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("NAME", $skill->getName());
            $this->assertEquals("#ffffff", $skill->getColor());
            $this->assertEquals("PAGE", $skill->getPage());
            $this->assertFalse($skill->isCollab());
            $this->assertFalse($skill->isExtra());
            $this->assertTrue($skill->isActive());
            $this->assertEquals($dependencies, $skill->getDependencies());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editSkillDuplicateName()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);

        try {
            $skill2->editSkill($this->tierId, "Skill1", null, null, false, false, true, 1, []);
            $this->fail("Error should have been thrown on 'editSkillDuplicateName'");

        } catch (Exception $e) {
            $this->assertEquals("Skill2", $skill2->getName());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editSkillTierChanged()
    {
        // Given
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);

        $wildcardTier = Tier::getWildcard((new Tier($this->tierId))->getSkillTree()->getId());
        $skillWildard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        // When
        $skill1->editSkill($wildcardTier->getId(), "Skill1", null, null, false, false, true, 1, []);

        // Then
        $this->assertEquals($wildcardTier, $skill1->getTier());
        $this->assertEquals(1, $skill1->getPosition());
        $this->assertEquals(0, $skill2->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function editSkillPositionChanged()
    {
        // Given
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);

        // When
        $skill1->editSkill($this->tierId, "Skill1", null, null, false, false, true, 1, []);

        // Then
        $this->assertEquals(1, $skill1->getPosition());
        $this->assertEquals(0, $skill2->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function editSkillWithDependencies()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // When
        $skill2->editSkill($tier2->getId(), "Skill2", null, null, false, false, true, $skill2->getPosition(), []);

        // Then
        $this->assertEmpty($skill2->getDependencies());
    }

    /**
     * @test
     * @throws Exception
     */
    public function editWildcardSkillWithDependencies()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        try {
            $skillWildcard->editSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, true, $skillWildcard->getPosition(), [
                [$skill1->getId()]
            ]);
            $this->fail("Error should have been thrown on 'editWildcardSkillWithDependencies'");


        } catch (Exception $e) {
            $this->assertEmpty($skillWildcard->getDependencies());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editFirstSkillWithDependencies()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        try {
            $skill1->editSkill($this->tierId, "Skill1", null, null, false, false, true, $skill1->getPosition(), [
                [0]
            ]);
            $this->fail("Error should have been thrown on 'editFirstSkillWithDependencies'");


        } catch (Exception $e) {
            $this->assertEmpty($skill1->getDependencies());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editFirstSkillToNotFirstWithDependencies()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // When
        $skill1->editSkill($tier2->getId(), "Skill1", null, null, false, false, true, 1, [
            [$skill2->getId()],
            [0]
        ]);

        // Then
        $this->assertEquals($tier2, $skill1->getTier());
        $this->assertEquals(1, $skill1->getPosition());
        $this->assertCount(2, $skill1->getDependencies());
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteSkill()
    {
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);

        // Not empty
        Skill::deleteSkill($skill1->getId());
        $this->assertCount(1, Skill::getSkills($this->courseId));
        $this->assertFalse(file_exists($skill1->getDataFolder(true, "Skill1")));
        $this->assertCount(1, Section::getSectionByName($this->courseId, Skills::RULE_SECTION)->getRules());
        $this->assertEquals(0, $skill2->getPosition());

        // Empty
        Skill::deleteSkill($skill2->getId());
        $this->assertEmpty(Skill::getSkills($this->courseId));
        $this->assertFalse(file_exists($skill2->getDataFolder(true, "Skill2")));
        $this->assertEmpty(Section::getSectionByName($this->courseId, Skills::RULE_SECTION)->getRules());
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteSkillInexistentSkill()
    {
        Skill::deleteSkill(100);
        $this->assertEmpty(Skill::getSkills($this->courseId));
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteSkillWithDependants()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // When
        Skill::deleteSkill($skill1->getId());

        // Then
        $this->assertFalse($skill1->exists());
        $this->assertCount(1, $skill2->getDependencies());
        $this->assertTrue($skill2->hasWildcardDependency());
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteSkillLastWildcardSkill()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // When
        Skill::deleteSkill($skillWildcard->getId());

        // Then
        $this->assertTrue($wildcardTier->isActive());
        $this->assertEmpty($wildcardTier->getSkills());
        $this->assertFalse($skill2->hasWildcardDependency());
        $this->assertCount(1, $skill2->getDependencies());
    }


    /**
     * @test
     * @throws Exception
     */
    public function skillExists()
    {
        $skill = Skill::addSkill($this->tierId, "Skill", null, null, false, false, []);
        $this->assertTrue($skill->exists());
    }

    /**
     * @test
     */
    public function skillDoesntExist()
    {
        $skill = new Skill(100);
        $this->assertFalse($skill->exists());
    }


    // Dependencies

    /**
     * @test
     * @throws Exception
     */
    public function getDependencies()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // Has dependencies
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(2, $dependencies);

        $this->assertIsArray($dependencies[1]);
        $this->assertCount(1, $dependencies[1]);
        $this->assertIsArray($dependencies[1][0]);
        $this->assertArrayHasKey("id", $dependencies[1][0]);
        $this->assertArrayHasKey("name", $dependencies[1][0]);
        $this->assertEquals($skill1->getId(), $dependencies[1][0]["id"]);
        $this->assertEquals($skill1->getName(), $dependencies[1][0]["name"]);

        // Has wildcard dependency
        $this->assertIsArray($dependencies[2]);
        $this->assertCount(1, $dependencies[2]);
        $this->assertIsArray($dependencies[2][0]);
        $this->assertArrayHasKey("id", $dependencies[2][0]);
        $this->assertArrayHasKey("name", $dependencies[2][0]);
        $this->assertEquals(0, $dependencies[2][0]["id"]);
        $this->assertEquals(Tier::WILDCARD, $dependencies[2][0]["name"]);

        // Doesn't have dependencies
        $this->assertEmpty($skill1->getDependencies());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDependants()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // Has dependants
        $dependants = $skill1->getDependants();
        $this->assertIsArray($dependants);
        $this->assertCount(1, $dependants);
        $this->assertIsArray($dependants[0]);
        $this->assertArrayHasKey("id", $dependants[0]);
        $this->assertArrayHasKey("name", $dependants[0]);
        $this->assertEquals($skill2->getId(), $dependants[0]["id"]);
        $this->assertEquals($skill2->getName(), $dependants[0]["name"]);

        // Doesn't have dependants
        $this->assertEmpty($skill2->getDependants());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setSkillDependencies()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, []);

        // Empty
        $skill2->setDependencies([
            [$skill1->getId()]
        ]);
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(1, $dependencies);

        $this->assertIsArray($dependencies[1]);
        $this->assertCount(1, $dependencies[1]);
        $this->assertIsArray($dependencies[1][0]);
        $this->assertArrayHasKey("id", $dependencies[1][0]);
        $this->assertArrayHasKey("name", $dependencies[1][0]);
        $this->assertEquals($skill1->getId(), $dependencies[1][0]["id"]);
        $this->assertEquals($skill1->getName(), $dependencies[1][0]["name"]);

        // Wildcard
        $skill2->setDependencies([
            [$skill1->getId()],
            [0]
        ]);
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(2, $dependencies);

        $this->assertIsArray($dependencies[2]);
        $this->assertCount(1, $dependencies[2]);
        $this->assertIsArray($dependencies[2][0]);
        $this->assertArrayHasKey("id", $dependencies[2][0]);
        $this->assertArrayHasKey("name", $dependencies[2][0]);
        $this->assertEquals($skill1->getId(), $dependencies[2][0]["id"]);
        $this->assertEquals($skill1->getName(), $dependencies[2][0]["name"]);

        $this->assertIsArray($dependencies[3]);
        $this->assertCount(1, $dependencies[3]);
        $this->assertIsArray($dependencies[3][0]);
        $this->assertArrayHasKey("id", $dependencies[3][0]);
        $this->assertArrayHasKey("name", $dependencies[3][0]);
        $this->assertEquals(0, $dependencies[3][0]["id"]);
        $this->assertEquals(Tier::WILDCARD, $dependencies[3][0]["name"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addDependency()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, []);

        // Empty
        $skill2->addDependency([$skill1->getId()]);
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(1, $dependencies);

        $this->assertIsArray($dependencies[1]);
        $this->assertCount(1, $dependencies[1]);
        $this->assertIsArray($dependencies[1][0]);
        $this->assertArrayHasKey("id", $dependencies[1][0]);
        $this->assertArrayHasKey("name", $dependencies[1][0]);
        $this->assertEquals($skill1->getId(), $dependencies[1][0]["id"]);
        $this->assertEquals($skill1->getName(), $dependencies[1][0]["name"]);

        // Wildcard
        $skill2->addDependency([0]);
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(2, $dependencies);

        $this->assertIsArray($dependencies[1]);
        $this->assertCount(1, $dependencies[1]);
        $this->assertIsArray($dependencies[1][0]);
        $this->assertArrayHasKey("id", $dependencies[1][0]);
        $this->assertArrayHasKey("name", $dependencies[1][0]);
        $this->assertEquals($skill1->getId(), $dependencies[1][0]["id"]);
        $this->assertEquals($skill1->getName(), $dependencies[1][0]["name"]);

        $this->assertIsArray($dependencies[2]);
        $this->assertCount(1, $dependencies[2]);
        $this->assertIsArray($dependencies[2][0]);
        $this->assertArrayHasKey("id", $dependencies[2][0]);
        $this->assertArrayHasKey("name", $dependencies[2][0]);
        $this->assertEquals(0, $dependencies[2][0]["id"]);
        $this->assertEquals(Tier::WILDCARD, $dependencies[2][0]["name"]);

        // Wildcard skill
        $skill2->addDependency([$skillWildcard->getId()]);
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(3, $dependencies);

        $this->assertIsArray($dependencies[1]);
        $this->assertCount(1, $dependencies[1]);
        $this->assertIsArray($dependencies[1][0]);
        $this->assertArrayHasKey("id", $dependencies[1][0]);
        $this->assertArrayHasKey("name", $dependencies[1][0]);
        $this->assertEquals($skill1->getId(), $dependencies[1][0]["id"]);
        $this->assertEquals($skill1->getName(), $dependencies[1][0]["name"]);

        $this->assertIsArray($dependencies[2]);
        $this->assertCount(1, $dependencies[2]);
        $this->assertIsArray($dependencies[2][0]);
        $this->assertArrayHasKey("id", $dependencies[2][0]);
        $this->assertArrayHasKey("name", $dependencies[2][0]);
        $this->assertEquals(0, $dependencies[2][0]["id"]);
        $this->assertEquals(Tier::WILDCARD, $dependencies[2][0]["name"]);

        $this->assertIsArray($dependencies[3]);
        $this->assertCount(1, $dependencies[3]);
        $this->assertIsArray($dependencies[3][0]);
        $this->assertArrayHasKey("id", $dependencies[3][0]);
        $this->assertArrayHasKey("name", $dependencies[3][0]);
        $this->assertEquals($skillWildcard->getId(), $dependencies[3][0]["id"]);
        $this->assertEquals($skillWildcard->getName(), $dependencies[3][0]["name"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeDependency()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0],
            [$skillWildcard->getId()]
        ]);

        // Not empty
        $skill2->removeDependency(1);
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(2, $dependencies);

        $this->assertIsArray($dependencies[2]);
        $this->assertCount(1, $dependencies[2]);
        $this->assertIsArray($dependencies[2][0]);
        $this->assertArrayHasKey("id", $dependencies[2][0]);
        $this->assertArrayHasKey("name", $dependencies[2][0]);
        $this->assertEquals(0, $dependencies[2][0]["id"]);
        $this->assertEquals(Tier::WILDCARD, $dependencies[2][0]["name"]);

        $this->assertIsArray($dependencies[3]);
        $this->assertCount(1, $dependencies[3]);
        $this->assertIsArray($dependencies[3][0]);
        $this->assertArrayHasKey("id", $dependencies[3][0]);
        $this->assertArrayHasKey("name", $dependencies[3][0]);
        $this->assertEquals($skillWildcard->getId(), $dependencies[3][0]["id"]);
        $this->assertEquals($skillWildcard->getName(), $dependencies[3][0]["name"]);

        // Wildcard
        $skill2->removeDependency(2);
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(1, $dependencies);

        $this->assertIsArray($dependencies[3]);
        $this->assertCount(1, $dependencies[3]);
        $this->assertIsArray($dependencies[3][0]);
        $this->assertArrayHasKey("id", $dependencies[3][0]);
        $this->assertArrayHasKey("name", $dependencies[3][0]);
        $this->assertEquals($skillWildcard->getId(), $dependencies[3][0]["id"]);
        $this->assertEquals($skillWildcard->getName(), $dependencies[3][0]["name"]);

        // Wildcard skill
        $skill2->removeDependency(3);
        $this->assertEmpty($skill2->getDependencies());
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeAsDependency()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0]
        ]);

        // Is dependency
        $skill1->removeAsDependency();
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(1, $dependencies);

        $this->assertIsArray($dependencies[2]);
        $this->assertCount(1, $dependencies[2]);
        $this->assertIsArray($dependencies[2][0]);
        $this->assertArrayHasKey("id", $dependencies[2][0]);
        $this->assertArrayHasKey("name", $dependencies[2][0]);
        $this->assertEquals(0, $dependencies[2][0]["id"]);
        $this->assertEquals(Tier::WILDCARD, $dependencies[2][0]["name"]);

        // Is not dependency
        $skill1->removeAsDependency();
        $dependencies = $skill2->getDependencies();
        $this->assertIsArray($dependencies);
        $this->assertCount(1, $dependencies);

        $this->assertIsArray($dependencies[2]);
        $this->assertCount(1, $dependencies[2]);
        $this->assertIsArray($dependencies[2][0]);
        $this->assertArrayHasKey("id", $dependencies[2][0]);
        $this->assertArrayHasKey("name", $dependencies[2][0]);
        $this->assertEquals(0, $dependencies[2][0]["id"]);
        $this->assertEquals(Tier::WILDCARD, $dependencies[2][0]["name"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeWildcardDependencies()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0],
            [$skillWildcard->getId()]
        ]);

        // When
        $skill2->removeWildcardDependencies();

        // Then
        $dependencies = $skill2->getDependencies();
        $this->assertCount(2, $dependencies);
        $this->assertCount(1, $dependencies[1]);
        $this->assertEquals($skill1->getId(), $dependencies[1][0]["id"]);
        $this->assertCount(1, $dependencies[3]);
        $this->assertEquals($skillWildcard->getId(), $dependencies[3][0]["id"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function hasDependencyWithId()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0],
            [$skillWildcard->getId()]
        ]);

        $this->assertTrue($skill2->hasDependency(1));
        $this->assertTrue($skill2->hasDependency(2));
        $this->assertTrue($skill2->hasDependency(3));
        $this->assertFalse($skill1->hasDependency(1));
        $this->assertFalse($skillWildcard->hasDependency(1));
    }

    /**
     * @test
     * @throws Exception
     */
    public function hasDependencyWithCombo()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0],
            [$skillWildcard->getId()]
        ]);

        $this->assertTrue($skill2->hasDependency(null, [$skill1->getId()]));
        $this->assertTrue($skill2->hasDependency(null, [0]));
        $this->assertTrue($skill2->hasDependency(null, [$skillWildcard->getId()]));
        $this->assertFalse($skill1->hasDependency(null, [0]));
        $this->assertFalse($skillWildcard->hasDependency(null, [0]));
    }

    /**
     * @test
     * @throws Exception
     */
    public function hasWildcardDependency()
    {
        // Given
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);

        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);

        $tier2 = Tier::addTier($skillTreeId, "Tier2", 200);
        $skill2 = Skill::addSkill($tier2->getId(), "Skill2", null, null, false, false, [
            [$skill1->getId()],
            [0],
            [$skillWildcard->getId()]
        ]);

        $this->assertTrue($skill2->hasWildcardDependency());
        $this->assertFalse($skill1->hasWildcardDependency());
        $this->assertFalse($skillWildcard->hasWildcardDependency());
    }


    // Rules

    /**
     * @test
     */
    public function generateRuleParamsBasicSkillWithoutDependencies()
    {
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $skillName = "Skill Name";

        $params = Skill::generateRuleParams(false, $skillTreeId, $skillName, []);

        // Name
        $this->assertTrue(isset($params["name"]));
        $this->assertEquals($skillName, $params["name"]);

        // When
        $this->assertTrue(isset($params["when"]));
        $this->assertEquals("logs = GC.participations.getSkillParticipations(target, \"$skillName\")
rating = get_rating(logs)
rating >= 3", $params["when"]);

        // Then
        $this->assertTrue(isset($params["then"]));
        $this->assertEquals("award_skill(target, \"$skillName\", rating, logs)", $params["then"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function generateRuleParamsBasicSkillWithDependencies()
    {
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skillName = "Skill Name";

        $params = Skill::generateRuleParams(false, $skillTreeId, $skillName, [
            [$skill1->getId()],
            [$skill2->getId()]
        ]);

        // Name
        $this->assertTrue(isset($params["name"]));
        $this->assertEquals($skillName, $params["name"]);

        // When
        $this->assertTrue(isset($params["when"]));
        $this->assertEquals($this->trim("combo1 = rule_unlocked(\"Skill1\", target)
combo2 = rule_unlocked(\"Skill2\", target)
combo1 or combo2

logs = GC.participations.getSkillParticipations(target, \"$skillName\")
rating = get_rating(logs)
rating >= 3"), $this->trim($params["when"]));

        // Then
        $this->assertTrue(isset($params["then"]));
        $this->assertEquals("award_skill(target, \"$skillName\", rating, logs)", $params["then"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function generateRuleParamsWildcardSkill()
    {
        $skillTreeId = (new Tier($this->tierId))->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);
        $skillWildcard = Skill::addSkill($wildcardTier->getId(), "Skill Wildcard", null, null, false, false, []);
        $skill1 = Skill::addSkill($this->tierId, "Skill1", null, null, false, false, []);
        $skill2 = Skill::addSkill($this->tierId, "Skill2", null, null, false, false, []);
        $skillName = "Skill Name";

        $params = Skill::generateRuleParams(true, $skillTreeId, $skillName, [
            [$skill1->getId(), $skill2->getId()],
            [$skill1->getId(), 0],
            [$skill2->getId(), 0]
        ]);

        // Name
        $this->assertTrue(isset($params["name"]));
        $this->assertEquals($skillName, $params["name"]);

        // When
        $this->assertTrue(isset($params["when"]));
        $this->assertEquals($this->trim("user = GC.users.getUser(target)
wildcard = user.hasWildcardAvailable($skillTreeId)

combo1 = rule_unlocked(\"Skill1\", target) and rule_unlocked(\"Skill2\", target)
combo2 = rule_unlocked(\"Skill1\", target) and wildcard
combo3 = rule_unlocked(\"Skill2\", target) and wildcard
combo1 or combo2 or combo3

skill_based = combo1
use_wildcard = False if skill_based else True

logs = GC.participations.getSkillParticipations(target, \"$skillName\")
rating = get_rating(logs)
rating >= 3"), $this->trim($params["when"]));

        // Then
        $this->assertTrue(isset($params["then"]));
        $this->assertEquals("award_skill(target, \"$skillName\", rating, logs, use_wildcard, \"" . Tier::WILDCARD . "\")", $params["then"]);
    }


    // Skill Data

    /**
     * @test
     * @dataProvider skillNameSuccessProvider
     * @throws Exception
     */
    public function getDataFolder(string $name)
    {
        $skill = Skill::addSkill($this->tierId, $name, null, null, false, false, []);
        $skillsDataFolder = (new Skills(new Course($this->courseId)))->getDataFolder();
        $this->assertEquals($skillsDataFolder . "/" . Utils::strip($name, "_"), $skill->getDataFolder(true, $name));
    }

    /**
     * @test
     * @dataProvider skillNameSuccessProvider
     * @throws Exception
     */
    public function createDataFolder(string $name)
    {
        $skill = Skill::addSkill($this->tierId, $name, null, null, false, false, []);
        Skill::createDataFolder($this->courseId, $name);
        $this->assertTrue(file_exists($skill->getDataFolder(true, $name)));
    }

    /**
     * @test
     * @dataProvider skillNameSuccessProvider
     * @throws Exception
     */
    public function removeDataFolder(string $name)
    {
        $skill = Skill::addSkill($this->tierId, $name, null, null, false, false, []);
        Skill::removeDataFolder($this->courseId, $name);
        $this->assertFalse(file_exists($skill->getDataFolder(true, $name)));
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
