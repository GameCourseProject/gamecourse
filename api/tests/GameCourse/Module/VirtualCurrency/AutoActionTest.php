<?php
namespace GameCourse\Module\VirtualCurrency;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
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
class AutoActionTest extends TestCase
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

        // Enable Virtual Currency module
        (new Awards($course))->setEnabled(true);
        $virtualCurrency = new VirtualCurrency($course);
        $virtualCurrency->setEnabled(true);
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, VirtualCurrency::TABLE_VC_AUTO_ACTION, Rule::TABLE_RULE]);
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

    public function actionNameSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Action Name"],
            "non-ASCII characters" => ["Âction Name"],
            "numbers" => ["Action123"],
            "parenthesis" => ["Action Name (Copy)"],
            "hyphen" => ["Action-Name"],
            "underscore" => ["Action_Name"],
            "ampersand" => ["Action & Name"],
            "trimmed" => [" This is some incredibly humongous action nameeeeee "],
            "length limit" => ["This is some incredibly humongous action nameeeeee"]
        ];
    }

    public function actionNameFailureProvider(): array
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
            "too long" => ["This is some incredibly humongous action nameeeeeee"]
        ];
    }


    public function actionDescriptionSuccessProvider(): array
    {
        return [
            "ASCII characters" => ["Action Description"],
            "non-ASCII characters" => ["Âction Description"],
            "numbers" => ["Action Description 123"],
            "parenthesis" => ["Action Description (Copy)"],
            "hyphen" => ["Action-Description"],
            "underscore" => ["Action_Description"],
            "ampersand" => ["Action & Description"],
            "trimmed" => [" This is some incredibly humongous action description This is some incredibly humongous action description This is some incredibly humongous action des "],
            "length limit" => ["This is some incredibly humongous action description This is some incredibly humongous action description This is some incredibly humongous action des"]
        ];
    }

    public function actionDescriptionFailureProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "only numbers" => ["123"],
            "too long" => ["This is some incredibly humongous action description This is some incredibly humongous action description This is some incredibly humongous action desc"]
        ];
    }


    public function actionTypeSuccessProvider(): array
    {
        return [
            "type" => ["attended lecture"],
            "trimmed" => [" This is some incredibly humongous type This is som "],
            "length limit" => ["This is some incredibly humongous type This is som"]
        ];
    }

    public function actionTypeFailureProvider(): array
    {
        return [
            "null" => [null],
            "empty" => [""],
            "too long" => ["This is some incredibly humongous type This is some"]
        ];
    }


    public function actionSuccessProvider(): array
    {
        return [
            "positive amount" => ["Action Name", "Perform action", "type", 10],
            "negative amount" => ["Action Name", "Perform action", "type", -10]
        ];
    }

    public function actionFailureProvider(): array
    {
        return [
            "invalid name" => [null, "Perform action", "type", 10],
            "invalid description" => ["Action Name", null, "type", 10],
            "invalid type" => ["Action Name", "Perform action", null, 10]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // Constructor

    /**
     * @test
     */
    public function actionConstructor()
    {
        $action = new AutoAction(123);
        $this->assertEquals(123, $action->getId());
    }


    // Getters

    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $id = intval(Core::database()->select(AutoAction::TABLE_VC_AUTO_ACTION, ["name" => "Action"], "id"));
        $this->assertEquals($id, $action->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertEquals($this->courseId, $action->getCourse()->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActionName()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertEquals("Action", $action->getName());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDescription()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertEquals("Perform action", $action->getDescription());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getType()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertEquals("type", $action->getType());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAmount()
    {
        // Positive
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertEquals(10, $action->getAmount());

        // Negative
        $action->editAction("Action", "Perform action", "type", -10, $action->isActive());
        $this->assertEquals(-10, $action->getAmount());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isActive()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertTrue($action->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isInactive()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $action->setActive(false);
        $this->assertFalse($action->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertEquals(["id" => 1, "course" => $this->courseId, "name" => "Action", "description" => "Perform action",
            "type" => "type", "amount" => 10, "isActive" => true, "rule" => 1], $action->getData());
    }


    // Setters

    /**
     * @test
     * @dataProvider actionNameSuccessProvider
     * @throws Exception
     */
    public function setActionNameSuccess(string $name)
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $action->setName($name);
        $this->assertEquals(trim($name), $action->getName());
    }

    /**
     * @test
     * @dataProvider actionNameFailureProvider
     * @throws Exception
     */
    public function setActionNameFailure($name)
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        try {
            $action->setName($name);
            $this->fail("Error should have been thrown on 'setActionNameFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Action", $action->getName());
            $this->assertEquals("Action", $action->getRule()->getName());
        }
    }

    /**
     * @test
     * @dataProvider actionDescriptionSuccessProvider
     * @throws Exception
     */
    public function setActionDescriptionSuccess(string $decription)
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $action->setDescription($decription);
        $this->assertEquals(trim($decription), $action->getDescription());
    }

    /**
     * @test
     * @dataProvider actionDescriptionFailureProvider
     * @throws Exception
     */
    public function setActionDescriptionFailure($decription)
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        try {
            $action->setDescription($decription);
            $this->fail("Error should have been thrown on 'setActionDescriptionFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("Perform action", $action->getDescription());
            $this->assertEquals("Perform action", $action->getRule()->getDescription());
        }
    }

    /**
     * @test
     * @dataProvider actionTypeSuccessProvider
     * @throws Exception
     */
    public function setActionTypeSuccess(string $type)
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $action->setType($type);
        $this->assertEquals(trim($type), $action->getType());
    }

    /**
     * @test
     * @dataProvider actionTypeFailureProvider
     * @throws Exception
     */
    public function setActionTypeFailure($type)
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        try {
            $action->setType($type);
            $this->fail("Error should have been thrown on 'setActionTypeFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEquals("type", $action->getType());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActionAmount()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);

        // Positive
        $action->setAmount(100);
        $this->assertEquals(100, $action->getAmount());

        // Negative
        $action->setAmount(-100);
        $this->assertEquals(-100, $action->getAmount());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActive()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $action->setActive(false);
        $action->setActive(true);
        $this->assertTrue($action->isActive());
        $this->assertTrue($action->getRule()->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setInactive()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $action->setActive(false);
        $this->assertFalse($action->isActive());
        $this->assertFalse($action->getRule()->isActive());
    }


    // General

    /**
     * @test
     * @throws Exception
     */
    public function getActionById()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertEquals($action, AutoAction::getActionById($action->getId()));
    }

    /**
     * @test
     */
    public function getActionByIdActionDoesntExist()
    {
        $this->assertNull(AutoAction::getActionById(100));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getActionByName()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertEquals($action, AutoAction::getActionByName($this->courseId, $action->getName()));
    }

    /**
     * @test
     */
    public function getActionByNameActionDoesntExist()
    {
        $this->assertNull(AutoAction::getActionByName($this->courseId, "Name"));
    }


    /**
     * @test
     * @throws Exception
     */
    public function getAllActions()
    {
        $action1 = AutoAction::addAction($this->courseId, "Action1", "Perform action", "type", 10);
        $action2 = AutoAction::addAction($this->courseId, "Action2", "Perform action", "type", 20);
        $action3 = AutoAction::addAction($this->courseId, "Action3", "Perform action", "type", 30);

        $actions = AutoAction::getActions($this->courseId);
        $this->assertIsArray($actions);
        $this->assertCount(3, $actions);

        $keys = ["id", "name", "description", "type", "amount", "isActive", "rule"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($actions as $i => $action) {
                $this->assertCount($nrKeys, array_keys($action));
                $this->assertArrayHasKey($key, $action);
                $this->assertEquals($action[$key], ${"action".($i+1)}->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllActiveActions()
    {
        $action1 = AutoAction::addAction($this->courseId, "Action1", "Perform action", "type", 10);
        $action2 = AutoAction::addAction($this->courseId, "Action2", "Perform action", "type", 20);
        $action2->setActive(false);

        $actions = AutoAction::getActions($this->courseId, true);
        $this->assertIsArray($actions);
        $this->assertCount(1, $actions);

        $keys = ["id", "name", "description", "type", "amount", "isActive", "rule"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($actions as $action) {
                $this->assertCount($nrKeys, array_keys($action));
                $this->assertArrayHasKey($key, $action);
                $this->assertEquals($action[$key], $action1->getData($key));
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllInactiveActions()
    {
        $action1 = AutoAction::addAction($this->courseId, "Action1", "Perform action", "type", 10);
        $action2 = AutoAction::addAction($this->courseId, "Action2", "Perform action", "type", 20);
        $action2->setActive(false);

        $actions = AutoAction::getActions($this->courseId, false);
        $this->assertIsArray($actions);
        $this->assertCount(1, $actions);

        $keys = ["id", "name", "description", "type", "amount", "isActive", "rule"];
        $nrKeys = count($keys);
        foreach ($keys as $key) {
            foreach ($actions as $action) {
                $this->assertCount($nrKeys, array_keys($action));
                $this->assertArrayHasKey($key, $action);
                $this->assertEquals($action[$key], $action2->getData($key));
            }
        }
    }


    // Action Manipulation

    /**
     * @test
     * @dataProvider actionSuccessProvider
     * @throws Exception
     */
    public function addActionSuccess(string $name, string $description, string $type, int $amount)
    {
        $action = AutoAction::addAction($this->courseId, $name, $description, $type, $amount);

        // Check is added to database
        $actionDB = AutoAction::getActions($this->courseId)[0];
        $actionDB["course"] = $this->courseId;
        $actionInfo = $action->getData();
        $this->assertEquals($actionInfo, $actionDB);

        // Check rule was created
        $rule = $action->getRule();
        $this->assertTrue($rule->exists());
        $this->assertEquals($this->trim("rule: $name
tags: 
# $description

	when:
		logs = get_logs(target, \"$type\")
		repetitions = len(logs)
		repetitions > 0

	then:
		" . ($amount > 0 ? "award" : "spend") . "_tokens(target, \"$description\", " . abs($amount) . ", repetitions)"), $this->trim($rule->getText()));
    }

    /**
     * @test
     * @dataProvider actionFailureProvider
     * @throws Exception
     */
    public function addActionFailure($name, $description, $type, $amount)
    {
        try {
            $action = AutoAction::addAction($this->courseId, $name, $description, $type, $amount);
            $this->fail("Error should have been thrown on 'addActionFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertEmpty(AutoAction::getActions($this->courseId));
            $this->assertEmpty(Section::getSectionByName($this->courseId, VirtualCurrency::RULE_SECTION)->getRules());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addActionDuplicateName()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        try {
            $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
            $this->fail("Error should have been thrown on 'addActionDuplicateName'");


        } catch (PDOException $e) {
            $this->assertCount(1, AutoAction::getActions($this->courseId));
            $this->assertCount(1, Section::getSectionByName($this->courseId, VirtualCurrency::RULE_SECTION)->getRules());
        }
    }


    /**
     * @test
     * @dataProvider actionSuccessProvider
     * @throws Exception
     */
    public function editActionSuccess(string $name, string $description, string $type, int $amount)
    {
        $action = AutoAction::addAction($this->courseId, "NAME", "DESCRIPTION", "TYPE", 0);
        $action->editAction($name, $description, $type, $amount, true);

        // Check is updated
        $this->assertEquals($name, $action->getName());
        $this->assertEquals($description, $action->getDescription());
        $this->assertEquals($type, $action->getType());
        $this->assertEquals($amount, $action->getAmount());
        $this->assertTrue($action->isActive());

        // Check rule
        $this->assertEquals($this->trim("rule: $name
tags: 
# $description

	when:
		logs = get_logs(target, \"$type\")
		repetitions = len(logs)
		repetitions > 0

	then:
		" . ($amount > 0 ? "award" : "spend") . "_tokens(target, \"$description\", " . abs($amount) . ", repetitions)"), $this->trim($action->getRule()->getText()));
    }

    /**
     * @test
     * @dataProvider actionFailureProvider
     * @throws Exception
     */
    public function editActionFailure($name, $description, $type, $amount)
    {
        $action = AutoAction::addAction($this->courseId, "NAME", "DESCRIPTION", "TYPE", 0);
        try {
            $action->editAction($name, $description, $type, $amount, true);
            $this->fail("Error should have been thrown on 'editActionFailure'");

        } catch (Exception|TypeError $e) {
            $this->assertCount(1, AutoAction::getActions($this->courseId));
            $this->assertCount(1, Section::getSectionByName($this->courseId, VirtualCurrency::RULE_SECTION)->getRules());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function editActionDuplicateName()
    {
        AutoAction::addAction($this->courseId, "Action1", "Perform action", "type", 10);
        $action = AutoAction::addAction($this->courseId, "Action2", "Perform action", "type", 10);
        try {
            $action->editAction("Action1", "Perform action", "type", 10, true);
            $this->fail("Error should have been thrown on 'editActionDuplicateName'");


        } catch (PDOException $e) {
            $this->assertEquals("Action2", $action->getName());
            $this->assertEquals("Action2", $action->getRule()->getName());
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function copyAction()
    {
        // Given
        $copyTo = Course::addCourse("Course Copy", "CPY", "2021-2022", "#ffffff",
            null, null, false, false);

        (new Awards($copyTo))->setEnabled(true);
        (new VirtualCurrency($copyTo))->setEnabled(true);

        $action1 = AutoAction::addAction($this->courseId, "Action1", "Perform action", "type", 10);
        $action2 = AutoAction::addAction($this->courseId, "Action2", "Perform action", "type", 10);

        // When
        $action1->copyAction($copyTo);
        $action2->copyAction($copyTo);

        // Then
        $actions = AutoAction::getActions($this->courseId);
        $copiedActions = AutoAction::getActions($copyTo->getId());
        $this->assertSameSize($actions, $copiedActions);
        foreach ($actions as $i => $action) {
            $this->assertEquals($action["name"], $copiedActions[$i]["name"]);
            $this->assertEquals($action["description"], $copiedActions[$i]["description"]);
            $this->assertEquals($action["type"], $copiedActions[$i]["type"]);
            $this->assertEquals($action["amount"], $copiedActions[$i]["amount"]);
            $this->assertEquals($action["isActive"], $copiedActions[$i]["isActive"]);

            $this->assertEquals((new Rule($action["rule"]))->getText(), (new Rule($copiedActions[$i]["rule"]))->getText());

        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteAction()
    {
        $action1 = AutoAction::addAction($this->courseId, "Action1", "Perform action", "type", 10);
        $action2 = AutoAction::addAction($this->courseId, "Action2", "Perform action", "type", 10);

        // Not empty
        AutoAction::deleteAction($action2->getId());
        $this->assertCount(1, AutoAction::getActions($this->courseId));
        $this->assertCount(1, Section::getSectionByName($this->courseId, VirtualCurrency::RULE_SECTION)->getRules());

        // Empty
        AutoAction::deleteAction($action1->getId());
        $this->assertEmpty(AutoAction::getActions($this->courseId));
        $this->assertEmpty(Section::getSectionByName($this->courseId, VirtualCurrency::RULE_SECTION)->getRules());
    }

    /**
     * @test
     * @throws Exception
     */
    public function deleteBadgeInexistentBadge()
    {
        AutoAction::deleteAction(100);
        $this->assertEmpty(AutoAction::getActions($this->courseId));
    }


    /**
     * @test
     * @throws Exception
     */
    public function actionExists()
    {
        $action = AutoAction::addAction($this->courseId, "Action", "Perform action", "type", 10);
        $this->assertTrue($action->exists());
    }

    /**
     * @test
     */
    public function actionDoesntExist()
    {
        $action = new AutoAction(100);
        $this->assertFalse($action->exists());
    }


    /**
     * @test
     * @dataProvider actionSuccessProvider
     * @throws Exception
     */
    public function generateRuleParams(string $name, string $description, string $type, int $amount)
    {
        $params = AutoAction::generateRuleParams($name, $description, $type, $amount);

        // Name
        $this->assertTrue(isset($params["name"]));
        $this->assertEquals($name, $params["name"]);

        // Description
        $this->assertTrue(isset($params["description"]));
        $this->assertEquals($description, $params["description"]);

        // When
        $this->assertTrue(isset($params["when"]));
        $this->assertEquals("logs = get_logs(target, \"$type\")
repetitions = len(logs)
repetitions > 0", $params["when"]);

        // Then
        $this->assertTrue(isset($params["then"]));
        $this->assertEquals(($amount > 0 ? "award" : "spend") . "_tokens(target, \"$description\", " . abs($amount) . ", repetitions)", $params["then"]);
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
