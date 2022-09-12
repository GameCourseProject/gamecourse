<?php
namespace GameCourse\AutoGame\RuleSystem;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
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
class TagTest extends TestCase
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
        TestingUtils::setUpBeforeClass([], ["CronJob"]);
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
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Tag::TABLE_RULE_TAG]);
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

    public function tagNameSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["tag"],
            "non-ASCII characters" => ["tãg"],
            "numbers" => ["tag123"],
            "only numbers" => ["123"],
            "parenthesis" => ["tag (Copy)"],
            "hyphen" => ["tag-123"],
            "underscore" => ["tag_123"],
            "ampersand" => ["tag & 123"],
            "trimmed" => [" This is some incredibly humongous tag rule namee "],
            "length limit" => ["This is some incredibly humongous tag rule nameeee"]
        ];
    }

    public function tagNameFailureProvider(): array
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
            "too long" => ["This is some incredibly humongous tag rule nameeeee"]
        ];
    }


    public function tagColorSuccessProvider(): array
    {
        return [
            "HEX" => ["#ffffff"],
            "trimmed" => [" #ffffff "],
        ];
    }

    public function tagColorFailureProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "whitespace" => [" "],
            "RGB" => ["rgb(255,255,255)"],
            "words" => ["white"],
            "only numbers" => ["123"],
            "not a string" => [123],
        ];
    }


    public function tagSuccessProvider(): array
    {
        $names = array_map(function ($name) { return $name[0]; }, $this->tagNameSuccessProvider());
        $colors = array_map(function ($color) { return $color[0]; }, $this->tagColorSuccessProvider());

        $provider = [];
        foreach ($names as $d1 => $name) {
            foreach ($colors as $d2 => $color) {
                $provider["name: " . $d1 . " | color: " . $d2] = [$name, $color];
            }
        }
        return $provider;
    }

    public function tagFailureProvider(): array
    {
        $names = array_map(function ($name) { return $name[0]; }, $this->tagNameFailureProvider());
        $colors = array_map(function ($color) { return$color[0]; }, $this->tagColorFailureProvider());

        $provider = [];
        foreach ($names as $d1 => $name) {
            foreach ($colors as $d2 => $color) {
                $provider["name: " . $d1 . " | color: " . $d2] = [$name, $color];
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
    public function tagConstructor()
    {
        $tag = new Tag(123);
        $this->assertEquals(123, $tag->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $id = intval(Core::database()->select(Tag::TABLE_RULE_TAG, ["name" => "tag"], "id"));
        $this->assertEquals($id, $tag->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $this->assertEquals($this->courseId, $tag->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTagName()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $this->assertEquals("tag", $tag->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getColor()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $this->assertEquals("#ffffff", $tag->getColor());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $this->assertEquals(["id" => 1, "course" => $this->courseId, "name" => "tag", "color" => "#ffffff"], $tag->getData());
    }


    /**
     * @test
     * @dataProvider tagNameSuccessProvider
     * @throws Exception
     */
    public function setNameSuccess(string $name)
    {
        $tag = Tag::addTag($this->courseId, "NAME", "#ffffff");
        $tag->setName($name);

        $name = trim($name);
        $this->assertEquals($name, $tag->getName());
    }

    /**
     * @test
     * @dataProvider tagNameFailureProvider
     * @throws Exception
     */
    public function setNameFailure($name)
    {
        $tag = Tag::addTag($this->courseId, "NAME", "#ffffff");
        try {
            $tag->setName($name);
            $this->fail("Exception should have been thrown on 'setTagNameFailure'");

        } catch (Exception|TypeError $error) {
            $this->assertEquals("NAME", $tag->getName());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNameTagAlreadyInRules()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);

        $tag1 = Tag::addTag($this->courseId, "NAME", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");

        $rule1->addTag($tag1->getId());
        $rule2->addTag($tag1->getId());
        $rule2->addTag($tag2->getId());

        // When
        $tag1->setName("tag1");

        // Then
        $this->assertEquals("tag1", $tag1->getName());
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [
            "rule: Rule1
tags: tag1

	when:
		when

	then:
		then",
            "rule: Rule2
tags: tag1, tag2

	when:
		when

	then:
		then"]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }

    /**
     * @test
     * @dataProvider tagColorSuccessProvider
     * @throws Exception
     */
    public function setColorSuccess(string $color)
    {
        $tag = Tag::addTag($this->courseId, "NAME", "#000000");
        $tag->setColor($color);

        $color = trim($color);
        $this->assertEquals($color, $tag->getColor());
    }

    /**
     * @test
     * @dataProvider tagColorFailureProvider
     * @throws Exception
     */
    public function setColorFailure($color)
    {
        $tag = Tag::addTag($this->courseId, "NAME", "#000000");
        try {
            $tag->setColor($color);
            $this->fail("Exception should have been thrown on 'setTagColorFailure'");

        } catch (Exception|TypeError $error) {
            $this->assertEquals("#000000", $tag->getColor());
        }
    }

    /**
     * @test
     * @dataProvider tagSuccessProvider
     * @throws Exception
     */
    public function setDataSuccess(string $name, string $color)
    {
        $fieldValues = ["name" => $name, "color" => $color];
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $tag->setData($fieldValues);
        $fieldValues["id"] = $tag->getId();
        $fieldValues["course"] = $this->courseId;
        Utils::trim(["name", "color"], $fieldValues);
        $this->assertEquals($tag->getData(), $fieldValues);
    }

    /**
     * @test
     * @dataProvider tagFailureProvider
     * @throws Exception
     */
    public function setDataFailure($name, $color)
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        try {
            $tag->setData(["name" => $name, "color" => $color]);
            $this->fail("Exception should have been thrown on 'setDataFailure'");

        } catch (Exception $e) {
            $this->assertEquals(["id" => 1, "course" => $this->courseId, "name" => "tag", "color" => "#ffffff"],
                $tag->getData());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setDataTagAlreadyInRules()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);

        $tag1 = Tag::addTag($this->courseId, "NAME", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");

        $rule1->addTag($tag1->getId());
        $rule2->addTag($tag1->getId());
        $rule2->addTag($tag2->getId());

        // When
        $tag1->setData(["name" => "tag1", "color" => "#000000"]);

        // Then
        $this->assertEquals("tag1", $tag1->getName());
        $this->assertEquals("#000000", $tag1->getColor());
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [
            "rule: Rule1
tags: tag1

	when:
		when

	then:
		then",
            "rule: Rule2
tags: tag1, tag2

	when:
		when

	then:
		then"]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getTagById()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $this->assertEquals($tag, Tag::getTagById($tag->getId()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTagByIdTagDoesntExist()
    {
        $this->assertNull(Tag::getTagById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTagByName()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $this->assertEquals($tag, Tag::getTagByName($this->courseId, $tag->getName()));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getTagByNameTagDoesntExist()
    {
        $this->assertNull(Tag::getTagByName($this->courseId, "tag"));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllTags()
    {
        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");

        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        Tag::addTag($course->getId(), "tag3", "#ffffff");

        $tags = Tag::getTags($this->courseId);
        $this->assertIsArray($tags);
        $this->assertCount(2, $tags);

        $keys = ["id", "course", "name", "color"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($tags as $i => $tag) {
                $this->assertCount($nrKeys, array_keys($tag));
                $this->assertArrayHasKey($key, $tag);
                $this->assertEquals($tag[$key], ${"tag".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllTagsNoTags()
    {
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2022-2023", "#ffffff",
            null, null, true, true);
        Tag::addTag($course->getId(), "tag3", "#ffffff");

        $tags = Tag::getTags($this->courseId);
        $this->assertIsArray($tags);
        $this->assertEmpty($tags);
    }


    /**
     * @test
     * @dataProvider tagSuccessProvider
     * @throws Exception
     */
    public function addTagSuccess(string $name, string $color)
    {
        $tag = Tag::addTag($this->courseId, $name, $color);

        // Check is added to database
        $tagDB = Tag::getTags($this->courseId)[0];
        $this->assertEquals($tag->getData(), $tagDB);
    }

    /**
     * @test
     * @dataProvider tagFailureProvider
     * @throws Exception
     */
    public function addTagFailure($name, $color)
    {
        try {
            Tag::addTag($this->courseId, $name, $color);

        } catch (Exception|TypeError $e) {
            $this->assertEmpty(Tag::getTags($this->courseId));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addTagDuplicateName()
    {
        $id = Tag::addTag($this->courseId, "tag", "#ffffff")->getId();
        try {
            Tag::addTag($this->courseId, "tag", "#ffffff");

        } catch (Exception|TypeError $e) {
            $tags = Tag::getTags($this->courseId);
            $this->assertIsArray($tags);
            $this->assertCount(1, $tags);
            $this->assertEquals($id, $tags[0]["id"]);
        }
    }


    /**
     * @test
     * @dataProvider tagSuccessProvider
     * @throws Exception
     */
    public function editTagSuccess(string $name, string $color)
    {
        $tag = Tag::addTag($this->courseId, "NAME", "#ffffff");
        $tag->editTag($name, $color);
        $this->assertEquals(trim($name), $tag->getName());
        $this->assertEquals(trim($color), $tag->getColor());
    }

    /**
     * @test
     * @dataProvider tagFailureProvider
     * @throws Exception
     */
    public function editTagFailure($name, $color)
    {
        $tag = Tag::addTag($this->courseId, "NAME", "#ffffff");
        try {
            $tag->editTag($name, $color);

        } catch (Exception|TypeError $e) {
            $this->assertEquals("NAME", $tag->getName());
            $this->assertEquals("#ffffff", $tag->getColor());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editTagTagAlreadyInRules()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);

        $tag1 = Tag::addTag($this->courseId, "NAME", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");

        $rule1->addTag($tag1->getId());
        $rule2->addTag($tag1->getId());
        $rule2->addTag($tag2->getId());

        // When
        $tag1->editTag("tag1", "#000000");

        // Then
        $this->assertEquals("tag1", $tag1->getName());
        $this->assertEquals("#000000", $tag1->getColor());
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [
            "rule: Rule1
tags: tag1

	when:
		when

	then:
		then",
            "rule: Rule2
tags: tag1, tag2

	when:
		when

	then:
		then"]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function copyTag()
    {
        // Given
        $tag = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $course2 = Course::addCourse("Course2", "C2", "2021-2022", "#ffffff",
            null, null, true, true);

        // When
        $t = $tag->copyTag($course2);

        // Then
        $this->assertTrue(RuleSystem::hasTag($course2->getId(), $tag->getName()));
        $copiedTag = Tag::getTagByName($course2->getId(), $tag->getName());
        $this->assertEquals($tag->getName(), $copiedTag->getName());
        $this->assertEquals($tag->getColor(), $copiedTag->getColor());

        $this->assertEquals($tag->getName(), $t->getName());
        $this->assertEquals($tag->getColor(), $t->getColor());
    }

    /**
     * @test
     * @throws Exception
     */
    public function copyTagSameCourse()
    {
        // Given
        $tag = Tag::addTag($this->courseId, "tag1", "#ffffff");

        // When
        $t = $tag->copyTag(new Course($this->courseId));

        // Then
        $this->assertCount(1, RuleSystem::getTags($this->courseId));
        $this->assertTrue(RuleSystem::hasTag($this->courseId, $tag->getName()));

        $copiedTag = Tag::getTagByName($this->courseId, $tag->getName());
        $this->assertEquals($tag->getName(), $copiedTag->getName());
        $this->assertEquals($tag->getColor(), $copiedTag->getColor());

        $this->assertEquals($tag->getName(), $t->getName());
        $this->assertEquals($tag->getColor(), $t->getColor());
    }

    /**
     * @test
     * @throws Exception
     */
    public function copyTagDifferentCoursesTagAlreadyExists()
    {
        // Given
        $tag = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $course2 = Course::addCourse("Course2", "C2", "2021-2022", "#ffffff",
            null, null, true, true);
        Tag::addTag($course2->getId(), $tag->getName(), $tag->getColor());

        // When
        $t = $tag->copyTag($course2);

        // Then
        $this->assertCount(1, RuleSystem::getTags($course2->getId()));
        $this->assertTrue(RuleSystem::hasTag($course2->getId(), $tag->getName()));

        $copiedTag = Tag::getTagByName($course2->getId(), $tag->getName());
        $this->assertEquals($tag->getName(), $copiedTag->getName());
        $this->assertEquals($tag->getColor(), $copiedTag->getColor());

        $this->assertEquals($tag->getName(), $t->getName());
        $this->assertEquals($tag->getColor(), $t->getColor());
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteTag()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        Tag::deleteTag($tag->getId());
        $this->assertEmpty(Tag::getTags($this->courseId));
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteTagInexistentTag()
    {
        Tag::deleteTag(100);
        $this->assertEmpty(Tag::getTags($this->courseId));
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteTagTagAlreadyInRules()
    {
        // Given
        $section = Section::addSection($this->courseId, "Section Name");
        $rule1 = $section->addRule("Rule1", null, "when", "then", 0);
        $rule2 = $section->addRule("Rule2", null, "when", "then", 1);

        $tag1 = Tag::addTag($this->courseId, "tag1", "#ffffff");
        $tag2 = Tag::addTag($this->courseId, "tag2", "#ffffff");

        $rule1->addTag($tag1->getId());
        $rule2->addTag($tag1->getId());
        $rule2->addTag($tag2->getId());

        // When
        Tag::deleteTag($tag1->getId());

        // Then
        $tags = Tag::getTags($this->courseId);
        $this->assertIsArray($tags);
        $this->assertCount(1, $tags);
        $this->assertEquals($tag2->getId(), $tags[0]["id"]);
        $rulesText = implode("\n\n" . Section::RULE_DIVIDER . "\n\n", [
            "rule: Rule1
tags: 

	when:
		when

	then:
		then",
            "rule: Rule2
tags: tag2

	when:
		when

	then:
		then"]);
        $this->assertEquals($rulesText, file_get_contents($section->getFile()));
    }


    /**
     * @test
     * @throws Exception
     */
    public function tagExists()
    {
        $tag = Tag::addTag($this->courseId, "tag", "#ffffff");
        $this->assertTrue($tag->exists());
    }

    /**
     * @test
     */
    public function tagDoesntExist()
    {
        $tag = new Tag(1);
        $this->assertFalse($tag->exists());
    }
}
