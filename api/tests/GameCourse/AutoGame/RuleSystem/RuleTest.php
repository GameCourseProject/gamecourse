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

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class RuleTest extends TestCase
{
    private $courseId;
    private $sectionId;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass([], ["CronJob"]);
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

        // Set a rule section
        $section = Section::addSection($this->courseId, "Section Name");
        $this->sectionId = $section->getId();
    }

    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Rule::TABLE_RULE]);
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

    public function ruleNameSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Rule Name"],
            "non-ASCII characters" => ["Rulé Name"],
            "numbers" => ["Rule123"],
            "parenthesis" => ["Rule Name (Copy)"],
            "hyphen" => ["Rule-Name"],
            "underscore" => ["Rule_Name"],
            "ampersand" => ["Rule & Name"],
            "length limit" => ["This is some incredibly humongous rule name This is some incredibly humongous rule name This is some"]
        ];
    }

    public function ruleNameFailureProvider(): array
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
            "too long" => ["This is some incredibly humongous rule name This is some incredibly humongous rule name This is somee"]
        ];
    }


    public function ruleSuccessProvider(): array
    {
        return [
            "default" => ["Rule Name", "Some description", "when", "then", 0, true, []],
            "no description" => ["Rule Name", null, "when", "then", 0, true, []],
            "multiple lines: description" => ["Rule Name", "Some description:\n\t- line1\n\t- line2", "when", "then", 0, true, []],
            "multiple lines: when" => ["Rule Name", "Some description", "line1\nline2\nline3", "then", 0, true, []],
            "multiple lines: then" => ["Rule Name", "Some description", "when", "line1\nline2\nline3", 0, true, []],
            "inactive" => ["Rule Name", "Some description", "when", "then", 0, false, []]
        ];
    }

    public function ruleFailureProvider(): array
    {
        return [
            "invalid name" => [null, "Some description", "when", "then", 0, true, []],
            "invalid when clause" => ["Rule Name", "Some description", null, "then", 0, true, []],
            "invalid then clause" => ["Rule Name", "Some description", "when", null, 0, true, []]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function ruleConstructor()
    {
        $rule = new Rule(123);
        $this->assertEquals(123, $rule->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $id = intval(Core::database()->select(Rule::TABLE_RULE, ["name" => "Rule Name"], "id"));
        $this->assertEquals($id, $rule->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals($this->courseId, $rule->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSection()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals($this->sectionId, $rule->getSection()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRuleName()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals("Rule Name", $rule->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDescription()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertNull($rule->getDescription());
        $rule->setDescription("Some description");
        $this->assertEquals("Some description", $rule->getDescription());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getWhen()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals("when", $rule->getWhen());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getThen()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals("then", $rule->getThen());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPosition()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals(0, $rule->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getText()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals("rule: Rule Name
tags: 

	when:
		when

	then:
		then", $rule->getText());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isActive()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertTrue($rule->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isInactive()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0, false);
        $this->assertFalse($rule->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $ruleData = ["id" => 1, "course" => $this->courseId, "section" => $this->sectionId, "name" => "Rule Name",
            "description" => null, "whenClause" => "when", "thenClause" => "then", "isActive" => true, "position" => 0];
        $this->assertEquals($ruleData, $rule->getData());
    }


    /**
     * @test
     * @dataProvider ruleNameSuccessProvider
     * @throws Exception
     */
    public function setNameSuccess(string $name)
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "NAME", null, "when", "then", 0);
        $rule->setName($name);
        $this->assertEquals($name, $rule->getName());

        $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
        $this->assertEquals("rule: $name
tags: 

	when:
		when

	then:
		then", $rulesText);
    }

    /**
     * @test
     * @dataProvider ruleNameFailureProvider
     * @throws Exception
     */
    public function setNameFailure($name)
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "NAME", null, "when", "then", 0);
        try {
            $rule->setName($name);
            $this->fail("Exception should have been thrown on 'setNameFailure'");

        } catch (Exception|TypeError $error) {
            $this->assertEquals("NAME", $rule->getName());

            $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
            $this->assertEquals("rule: NAME
tags: 

	when:
		when

	then:
		then", $rulesText);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setDescription()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", "DESCRIPTION", "when", "then", 0);
        $rule->setDescription("Some description");
        $this->assertEquals("Some description", $rule->getDescription());

        $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
        $this->assertEquals("rule: Rule Name
tags: 
# Some description

	when:
		when

	then:
		then", $rulesText);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setDescriptionNull()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", "Some description", "when", "then", 0);
        $rule->setDescription(null);
        $this->assertNull($rule->getDescription());

        $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
        $this->assertEquals("rule: Rule Name
tags: 

	when:
		when

	then:
		then", $rulesText);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setWhen()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "WHEN", "then", 0);
        $rule->setWhen("line1\nline2\nline3");
        $this->assertEquals("line1\nline2\nline3", $rule->getWhen());

        $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
        $this->assertEquals($this->trim("rule: Rule Name
tags: 

	when:
		line1
		line2
		line3

	then:
		then"), $this->trim($rulesText));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setThen()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "THEN", 0);
        $rule->setThen("line1\nline2\nline3");
        $this->assertEquals("line1\nline2\nline3", $rule->getThen());

        $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
        $this->assertEquals($this->trim("rule: Rule Name
tags: 

	when:
		when

	then:
		line1
		line2
		line3"), $this->trim($rulesText));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionStart()
    {
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);
        $rule3 = Rule::addRule($this->courseId, $this->sectionId, "Rule3", null, "when", "then", 2);

        $rule3->setPosition(0);
        $this->assertEquals(0, $rule3->getPosition());
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule3->getText(), $rule1->getText(), $rule2->getText()]);
        $this->assertEquals($rulesText, file_get_contents(Section::getSectionById($this->sectionId)->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionMiddle()
    {
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);
        $rule3 = Rule::addRule($this->courseId, $this->sectionId, "Rule3", null, "when", "then", 2);

        $rule3->setPosition(1);
        $this->assertEquals(1, $rule3->getPosition());
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule1->getText(), $rule3->getText(), $rule2->getText()]);
        $this->assertEquals($rulesText, file_get_contents(Section::getSectionById($this->sectionId)->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionEnd()
    {
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);
        $rule3 = Rule::addRule($this->courseId, $this->sectionId, "Rule3", null, "when", "then", 2);

        $rule1->setPosition(2);
        $this->assertEquals(2, $rule1->getPosition());
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule2->getText(), $rule3->getText(), $rule1->getText()]);
        $this->assertEquals($rulesText, file_get_contents(Section::getSectionById($this->sectionId)->getFile()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setPositionNull()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);

        try {
            $rule->setPosition(null);
            $this->fail("Exception should have been thrown on 'setPositionNull'");

        } catch (TypeError $e) {
            $this->assertEquals(0, $rule->getPosition());
            $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [$rule->getText()]);
            $this->assertEquals($rulesText, file_get_contents(Section::getSectionById($this->sectionId)->getFile()));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setText()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $rule->setText("TEXT");

        $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
        $this->assertEquals($this->trim("TEXT"), $this->trim($rulesText));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActive()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0, false);
        $rule->setActive(true);
        $this->assertTrue($rule->isActive());

        $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
        $this->assertEquals($this->trim("rule: Rule Name
tags: 

	when:
		when

	then:
		then"), $this->trim($rulesText));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactive()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $rule->setActive(false);
        $this->assertFalse($rule->isActive());

        $rulesText = file_get_contents(Section::getSectionById($this->sectionId)->getFile());
        $this->assertEquals($this->trim("rule: Rule Name
INACTIVE
tags: 

	when:
		when

	then:
		then"), $this->trim($rulesText));
    }

    /**
     * @test
     * @dataProvider ruleSuccessProvider
     * @throws Exception
     */
    public function setDataSuccess(string $name, ?string $description, string $when, string $then, int $position,
                                   bool $isActive, array $tags)
    {
        $fieldValues = ["name" => $name, "description" => $description, "whenClause" => $when, "thenClause" => $then,
            "position" => $position, "isActive" => $isActive];
        $rule = Rule::addRule($this->courseId, $this->sectionId, "NAME", "DESCRIPTION", "WHEN", "THEN", 0);
        $rule->setData($fieldValues);
        $fieldValues["id"] = $rule->getId();
        $fieldValues["course"] = $this->courseId;
        $fieldValues["section"] = $this->sectionId;
        $this->assertEquals($rule->getData(), $fieldValues);
        $this->assertEquals($tags, $rule->getTags());
    }

    /**
     * @test
     * @dataProvider ruleFailureProvider
     * @throws Exception
     */
    public function setDataFailure($name, $description, $when, $then, $position, $isActive, $tags)
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "NAME", "DESCRIPTION", "WHEN", "THEN", 0);
        try {
            $rule->setData(["name" => $name, "description" => $description, "whenClause" => $when, "thenClause" => $then,
                "position" => $position, "isActive" => $isActive]);
            $this->fail("Exception should have been thrown on 'setDataFailure'");

        } catch (Exception $e) {
            $this->assertEquals(["id" => 1, "course" => $this->courseId, "section" => $this->sectionId, "name" => "NAME",
                "description" => "DESCRIPTION", "whenClause" => "WHEN", "thenClause" => "THEN", "isActive" => true, "position" => 0],
                $rule->getData());
            $this->assertEquals($tags, $rule->getTags());
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getRuleById()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals($rule, Rule::getRuleById($rule->getId()));
    }

    /**
     * @test
     */
    public function getRuleByIdInexistentRule()
    {
        $this->assertNull(Rule::getRuleById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRuleByName()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals($rule, Rule::getRuleByName($this->courseId, "Rule Name"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRuleByNameInexistentRule()
    {
        $this->assertNull(Rule::getRuleByName($this->courseId, "Rule Name"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRuleByPosition()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertEquals($rule, Rule::getRuleByPosition($this->sectionId, 0));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRuleByPositionInexistentRule()
    {
        $this->assertNull(Rule::getRuleByPosition($this->sectionId, 0));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllRulesOfCourse()
    {
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);

        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        $section2 = Section::addSection($course->getId(), "Section2");
        Rule::addRule($course->getId(), $section2->getId(), "Rule1", null, "when", "then", 0);

        $rules = Rule::getRulesOfCourse($this->courseId);
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
    public function getAllRulesOfCourseNoRules()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        $section2 = Section::addSection($course->getId(), "Section2");
        Rule::addRule($course->getId(), $section2->getId(), "Rule1", null, "when", "then", 0);

        $rules = Rule::getRulesOfCourse($this->courseId);
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllRulesOfSection()
    {
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);

        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        $section2 = Section::addSection($course->getId(), "Section2");
        Rule::addRule($course->getId(), $section2->getId(), "Rule1", null, "when", "then", 0);

        $rules = Rule::getRulesOfSection($this->sectionId);
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
    public function getAllRulesOfSectionNoRules()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        $section2 = Section::addSection($course->getId(), "Section2");
        Rule::addRule($course->getId(), $section2->getId(), "Rule1", null, "when", "then", 0);

        $rules = Rule::getRulesOfSection($this->sectionId);
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllRulesWithTag()
    {
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);

        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");

        $rule1->addTag($tag1->getId());
        $rule1->addTag($tag2->getId());
        $rule2->addTag($tag1->getId());

        $rules = Rule::getRulesWithTag($tag1->getId());
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
        $this->assertEquals($rule1->getId(), Rule::getRulesWithTag($tag2->getId())[0]["id"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllRulesWithTagNoRules()
    {
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);

        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");

        $rule1->addTag($tag1->getId());
        $rule2->addTag($tag1->getId());

        $rules = Rule::getRulesWithTag($tag2->getId());
        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }


    /**
     * @test
     * @dataProvider ruleSuccessProvider
     * @throws Exception
     */
    public function addRuleSuccess(string $name, ?string $description, string $when, string $then, int $position,
                                   bool $isActive, array $tags)
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, $name, $description, $when, $then, $position, $isActive, $tags);

        $rules = Rule::getRulesOfCourse($this->courseId);
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule->getId(), $rules[0]["id"]);

        $ruleData = ["id" => 1, "course" => $this->courseId, "section" => $this->sectionId, "name" => $name, "description" => $description,
            "whenClause" => $when, "thenClause" => $then, "isActive" => $isActive, "position" => $position];
        $this->assertEquals($ruleData, $rule->getData());
        $this->assertEquals($ruleData, $rules[0]);

        $section = Section::getSectionById($this->sectionId);
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule->getId(), $rules[0]["id"]);

        $rulesText = file_get_contents($section->getFile());
        $this->assertEquals(1, substr_count($rulesText, "rule:"));
    }

    /**
     * @test
     * @dataProvider ruleFailureProvider
     * @throws Exception
     */
    public function addRuleFailure($name, $description, $when, $then, $position, $isActive, $tags)
    {
        try {
            Rule::addRule($this->courseId, $this->sectionId, $name, $description, $when, $then, $position, $isActive, $tags);
            $this->fail("Exception should have been thrown on 'addRuleFailure'");

        } catch (Exception|TypeError $e) {
            $rules = Rule::getRulesOfCourse($this->courseId);
            $this->assertIsArray($rules);
            $this->assertEmpty($rules);

            $section = Section::getSectionById($this->sectionId);
            $rules = $section->getRules();
            $this->assertIsArray($rules);
            $this->assertEmpty($rules);

            $rulesText = file_get_contents($section->getFile());
            $this->assertEmpty($rulesText);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRuleWithTags()
    {
        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");
        Tag::addTag($this->courseId, "tag3", "#ffffff");
        $tags = [$tag1->getData(), $tag2->getData()];

        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when",
            "then", 0, true, $tags);
        $this->assertEquals($tags, $rule->getTags());

        $rules = Rule::getRulesOfCourse($this->courseId);
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule->getId(), $rules[0]["id"]);

        $section = Section::getSectionById($this->sectionId);
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule->getId(), $rules[0]["id"]);

        $rulesText = file_get_contents($section->getFile());
        $this->assertEquals(1, substr_count($rulesText, "tags: tag1, tag2"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRuleDuplicateName()
    {
        Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        try {
            Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
            $this->fail("Exception should have been thrown on 'addRuleDuplicateName'");

        } catch (PDOException $e) {
            $rules = Rule::getRulesOfCourse($this->courseId);
            $this->assertCount(1, $rules);
        }
    }


    /**
     * @test
     * @dataProvider ruleSuccessProvider
     * @throws Exception
     */
    public function editRuleSuccess(string $name, ?string $description, string $when, string $then, int $position,
                                    bool $isActive, array $tags)
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "NAME", null, "WHEN", "THEN", 0);
        $rule->editRule($name, $description, $when, $then, $position, $isActive, $tags);

        $rules = Rule::getRulesOfCourse($this->courseId);
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule->getId(), $rules[0]["id"]);

        $ruleData = ["id" => 1, "course" => $this->courseId, "section" => $this->sectionId, "name" => $name, "description" => $description,
            "whenClause" => $when, "thenClause" => $then, "isActive" => $isActive, "position" => $position];
        $this->assertEquals($ruleData, $rule->getData());
        $this->assertEquals($ruleData, $rules[0]);

        $section = Section::getSectionById($this->sectionId);
        $rules = $section->getRules();
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule->getId(), $rules[0]["id"]);
    }

    /**
     * @test
     * @dataProvider ruleFailureProvider
     * @throws Exception
     */
    public function editRuleFailure($name, $description, $when, $then, $position, $isActive, $tags)
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "NAME", null, "WHEN", "THEN", 0);
        try {
            $rule->editRule($name, $description, $when, $then, $position, $isActive, $tags);
            $this->fail("Exception should have been thrown on 'editRuleFailure'");

        } catch (Exception|TypeError $e) {
            $rules = Rule::getRulesOfCourse($this->courseId);
            $this->assertIsArray($rules);
            $this->assertCount(1, $rules);
            $this->assertEquals($rule->getId(), $rules[0]["id"]);

            $ruleData = ["id" => 1, "course" => $this->courseId, "section" => $this->sectionId, "name" => "NAME", "description" => null,
                "whenClause" => "WHEN", "thenClause" => "THEN", "isActive" => true, "position" => 0];
            $this->assertEquals($ruleData, $rule->getData());
            $this->assertEquals($ruleData, $rules[0]);

            $section = Section::getSectionById($this->sectionId);
            $rules = $section->getRules();
            $this->assertIsArray($rules);
            $this->assertCount(1, $rules);
            $this->assertEquals($rule->getId(), $rules[0]["id"]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editRulePositionChanged()
    {
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);
        $rule1->editRule("Rule1", null, "when", "then", 1, true, $rule1->getTags());

        $this->assertEquals(1, $rule1->getPosition());
        $this->assertEquals(0, $rule2->getPosition());
    }

    /**
     * @test
     * @throws Exception
     */
    public function editRuleTagsChanged()
    {
        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");
        $tag3 = Tag::addTag($this->courseId, "tag3", "#ffffff");
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then",
            0, true, [$tag1->getData(), $tag2->getData()]);
        $rule->editRule("Rule1", null, "when", "then", 0, true, [$tag1->getData(), $tag3->getData()]);

        $this->assertEquals([$tag1->getData(), $tag3->getData()], $rule->getTags());
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteRule()
    {
        // Given
        $rule1 = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);
        $rule2 = Rule::addRule($this->courseId, $this->sectionId, "Rule2", null, "when", "then", 1);
        $rule3 = Rule::addRule($this->courseId, $this->sectionId, "Rule3", null, "when", "then", 2);

        // When
        Rule::deleteRule($rule2->getId());

        // Then
        $rules = Rule::getRulesOfCourse($this->courseId);
        $this->assertIsArray($rules);
        $this->assertCount(2, $rules);

        $r1 = $rules[0];
        $this->assertIsArray($r1);
        $this->assertEquals($rule1->getId(), $r1["id"]);
        $this->assertEquals(0, $r1["position"]);

        $r2 = $rules[1];
        $this->assertIsArray($r2);
        $this->assertEquals($rule3->getId(), $r2["id"]);
        $this->assertEquals(1, $r2["position"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteRuleRuleDoesntExist()
    {
        // Given
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule1", null, "when", "then", 0);

        // When
        Rule::deleteRule(100);

        // Then
        $rules = Rule::getRulesOfCourse($this->courseId);
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertEquals($rule->getId(), $rules[0]["id"]);
    }


    /**
     * @test
     * @throws Exception
     */
    public function ruleExists()
    {
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $this->assertTrue($rule->exists());
    }

    /**
     * @test
     */
    public function ruleDoesntExist()
    {
        $rule = new Rule(1);
        $this->assertFalse($rule->exists());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getTags()
    {
        // Given
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");
        Tag::addTag($this->courseId, "tag3", "#ffffff");
        $rule->addTag($tag2->getId());
        $rule->addTag($tag1->getId());

        // When
        $tags = $rule->getTags();

        // Then
        $this->assertIsArray($tags);
        $this->assertCount(2, $tags);
        $this->assertEquals([$tag1->getData(), $tag2->getData()], $tags);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTagsNoTags()
    {
        // Given
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);

        // When
        $tags = $rule->getTags();

        // Then
        $this->assertIsArray($tags);
        $this->assertEmpty($tags);
    }


    /**
     * @test
     * @throws Exception
     */
    public function addTag()
    {
        // Given
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");
        $rule->addTag($tag2->getId());

        // When
        $rule->addTag($tag1->getId());

        // Then
        $tags = $rule->getTags();
        $this->assertIsArray($tags);
        $this->assertCount(2, $tags);
        $this->assertEquals([$tag1->getData(), $tag2->getData()], $tags);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addTagNoTags()
    {
        // Given
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");

        // When
        $rule->addTag($tag->getId());

        // Then
        $tags = $rule->getTags();
        $this->assertIsArray($tags);
        $this->assertCount(1, $tags);
        $this->assertEquals([$tag->getData()], $tags);
    }


    /**
     * @test
     * @throws Exception
     */
    public function removeTag()
    {
        // Given
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");
        $rule->addTag($tag2->getId());
        $rule->addTag($tag1->getId());

        // When
        $rule->removeTag($tag2->getId());

        // Then
        $tags = $rule->getTags();
        $this->assertIsArray($tags);
        $this->assertCount(1, $tags);
        $this->assertEquals([$tag1->getData()], $tags);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeTagNoTags()
    {
        // Given
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);

        // When
        $rule->removeTag(100);

        // Then
        $tags = $rule->getTags();
        $this->assertIsArray($tags);
        $this->assertEmpty($tags);
    }


    /**
     * @test
     * @throws Exception
     */
    public function hasTag()
    {
        // Given
        $rule = Rule::addRule($this->courseId, $this->sectionId, "Rule Name", null, "when", "then", 0);
        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");
        $tag3 = Tag::addTag($this->courseId, "tag3", "#ffffff");
        $rule->addTag($tag2->getId());
        $rule->addTag($tag1->getId());

        // Then
        $this->assertTrue($rule->hasTag($tag1->getId()));
        $this->assertTrue($rule->hasTag($tag2->getId()));
        $this->assertFalse($rule->hasTag($tag3->getId()));
    }


    /**
     * @test
     */
    public function generateText()
    {
        $text = "rule: Rule Name
tags: 
# Some description:
	- line1
	- line2

	when:
		line1
		line2
		line3

	then:
		line1
		line2";
        $this->assertEquals($this->trim($text), $this->trim(Rule::generateText("Rule Name", "Some description:\n\t- line1\n\t- line2",
            "line1\r\nline2\r\nline3", "line1\nline2", true)));
    }

    /**
     * @test
     */
    public function generateTextActive()
    {
        $text = "rule: Rule Name
tags: 
# Some description

	when:
		when

	then:
		then";
        $this->assertEquals($this->trim($text), $this->trim(Rule::generateText("Rule Name", "Some description",
            "when", "then", true)));
    }

    /**
     * @test
     */
    public function generateTextInactive()
    {
        $text = "rule: Rule Name
INACTIVE
tags: 
# Some description

	when:
		when

	then:
		then";
        $this->assertEquals($this->trim($text), $this->trim(Rule::generateText("Rule Name", "Some description",
            "when", "then", false)));
    }

    /**
     * @test
     */
    public function generateTextWithTags()
    {
        $text = "rule: Rule Name
tags: tag1, tag2, tag3
# Some description

	when:
		when

	then:
		then";
        $this->assertEquals($this->trim($text), $this->trim(Rule::generateText("Rule Name", "Some description",
            "when", "then", true, [
                ["name" => "tag1"],
                ["name" => "tag2"],
                ["name" => "tag3"]
            ])));
    }

    /**
     * @test
     */
    public function generateTextNoTags()
    {
        $text = "rule: Rule Name
tags: 
# Some description

	when:
		when

	then:
		then";
        $this->assertEquals($this->trim($text), $this->trim(Rule::generateText("Rule Name", "Some description",
            "when", "then", true)));
    }

    /**
     * @test
     */
    public function generateTextWithDescription()
    {
        $text = "rule: Rule Name
tags: 
# Some description:
	- line1
	- line2

	when:
		when

	then:
		then";
        $this->assertEquals($this->trim($text), $this->trim(Rule::generateText("Rule Name", "Some description:\n\t- line1\n\t- line2",
            "when", "then", true)));
    }

    /**
     * @test
     */
    public function generateTextNoDescription()
    {
        $text = "rule: Rule Name
tags: 

	when:
		when

	then:
		then";
        $this->assertEquals($this->trim($text), $this->trim(Rule::generateText("Rule Name", null,
            "when", "then", true)));
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private function trim(string $str)
    {
        return str_replace("\r", "", $str);
    }
}