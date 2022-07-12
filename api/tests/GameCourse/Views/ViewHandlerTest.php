<?php
namespace GameCourse\Views;

use GameCourse\Core\Core;
use GameCourse\Role\Role;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Event\EventType;
use GameCourse\Views\ViewType\Block;
use GameCourse\Views\ViewType\Text;
use GameCourse\Views\Visibility\VisibilityType;
use PDOException;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class ViewHandlerTest extends TestCase
{
    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["roles", "views"]);
    }

    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([ViewHandler::TABLE_VIEW]);
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
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO: setupViews


    /**
     * @test
     */
    public function getViewById()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view, $aspect);

        // When
        $viewInDB = ViewHandler::getViewById($view["id"]);

        // Then
        $this->assertNotEmpty($viewInDB);
        $this->assertCount(11, $viewInDB);
        $this->assertEquals($view["id"], $viewInDB["id"]);
        $this->assertEquals($view["type"], $viewInDB["type"]);
        $this->assertEquals("vertical", $viewInDB["direction"]);
        $this->assertEmpty($viewInDB["variables"]);
        $this->assertEmpty($viewInDB["events"]);
    }

    /**
     * @test
     */
    public function getViewByIdViewDoesntExist()
    {
        $this->assertNull(ViewHandler::getViewById(1));
    }


    /**
     * @test
     */
    public function getAllViews()
    {
        // Given
        $view1 = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view2 = ["id" => 2, "viewRoot" => 2, "type" => Block::ID];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view1, $aspect);
        ViewHandler::insertView($view2, $aspect);

        // When
        $views = ViewHandler::getViews();

        // Then
        $this->assertCount(2, $views);
        foreach ($views as $view) {
            $this->assertCount(11, $view);
            $this->assertEquals($view["type"], Block::ID);
            $this->assertEquals("vertical", $view["direction"]);
            $this->assertEmpty($view["variables"]);
            $this->assertEmpty($view["events"]);
        }
    }


    // TODO: getViewAspect


    /**
     * @test
     */
    public function viewExists()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view, $aspect);

        // Then
        $this->assertTrue(ViewHandler::viewExists($view["id"]));
    }

    /**
     * @test
     */
    public function viewDoesntExist()
    {
        $this->assertFalse(ViewHandler::viewExists(1));
    }


    /**
     * @test
     */
    public function insertViewSimple()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        // When
        ViewHandler::insertView($view, $aspect);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(1, $views);
        $this->assertCount(11, $views[0]);
        foreach (["id", "type", "cssId", "class", "style", "visibilityCondition", "loopData"] as $param) {
            $this->assertEquals($view[$param] ?? null, $views[0][$param]);
        }
        $this->assertEquals(VisibilityType::VISIBLE, $views[0]["visibilityType"]);
        $this->assertEquals("vertical", $views[0]["direction"]);
        $this->assertEmpty($views[0]["variables"]);
        $this->assertEmpty($views[0]["events"]);

        $viewAspects = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_ASPECT);
        $this->assertCount(1, $viewAspects);
        $this->assertCount(3, $viewAspects[0]);
        $this->assertEquals($view["viewRoot"], intval($viewAspects[0]["viewRoot"]));
        $this->assertEquals($aspect->getId(), intval($viewAspects[0]["aspect"]));
        $this->assertEquals($view["id"], intval($viewAspects[0]["view"]));
    }

    /**
     * @test
     */
    public function insertViewComplex()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID,
            "cssId" => "block1",
            "class" => "pretty-block",
            "style" => "background-color: pink",
            "visibilityType" => VisibilityType::CONDITIONAL,
            "visibilityCondition" => "1 + 2 == 3",
            "loopData" => "user.getUsers()",
            "variables" => ["var1" => "1 + 2"],
            "events" => [EventType::CLICK => "toggleView(100)"]
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        // When
        ViewHandler::insertView($view, $aspect);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(1, $views);
        $this->assertCount(11, $views[0]);
        foreach (["id", "type", "cssId", "class", "style", "visibilityType", "visibilityCondition", "loopData"] as $param) {
            $this->assertEquals($view[$param] ?? null, $views[0][$param]);
        }
        $this->assertEquals("vertical", $views[0]["direction"]);

        $viewAspects = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_ASPECT);
        $this->assertCount(1, $viewAspects);
        $this->assertEquals($view["viewRoot"], intval($viewAspects[0]["viewRoot"]));
        $this->assertEquals($aspect->getId(), intval($viewAspects[0]["aspect"]));
        $this->assertEquals($view["id"], intval($viewAspects[0]["view"]));

        $variables = $views[0]["variables"];
        $this->assertCount(1, $variables);
        $this->assertEquals("var1", $variables[0]["name"]);
        $this->assertEquals("1 + 2", $variables[0]["value"]);

        $events = $views[0]["events"];
        $this->assertCount(1, $events);
        $this->assertEquals(EventType::CLICK, $events[0]["type"]);
        $this->assertEquals("toggleView(100)", $events[0]["action"]);
    }

    /**
     * @test
     */
    public function insertViewViewAlreadyExists()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        // When
        $this->expectException(PDOException::class);
        ViewHandler::insertView($view, $aspect);
        ViewHandler::insertView($view, $aspect);
    }

    /**
     * @test
     */
    public function insertViewViewTypeDoesntExist()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => "type_doesnt_exist"
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        // When
        $this->expectException(PDOException::class);
        ViewHandler::insertView($view, $aspect);
    }

    /**
     * @test
     */
    public function insertViewAspectDoesntExist()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID
        ];
        $aspect = new Aspect(100);

        // When
        $this->expectException(PDOException::class);
        ViewHandler::insertView($view, $aspect);
    }


    /**
     * @test
     */
    public function updateViewChangeGeneralParams()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID,
            "cssId" => "block1",
            "class" => "pretty-block",
            "style" => "background-color: pink",
            "visibilityType" => VisibilityType::CONDITIONAL,
            "visibilityCondition" => "1 + 2 == 3",
            "loopData" => "user.getUsers()",
            "variables" => ["var1" => "1 + 2"],
            "events" => [EventType::CLICK => "toggleView(100)"]
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view, $aspect);

        // When
        $newView = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        ViewHandler::updateView($newView, $aspect);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(1, $views);
        $this->assertCount(11, $views[0]);
        foreach (["id", "type", "cssId", "class", "style", "visibilityCondition", "loopData"] as $param) {
            $this->assertEquals($newView[$param] ?? null, $views[0][$param]);
        }
        $this->assertEquals(VisibilityType::VISIBLE, $views[0]["visibilityType"]);
        $this->assertEquals("vertical", $views[0]["direction"]);
        $this->assertEmpty($views[0]["variables"]);
        $this->assertEmpty($views[0]["events"]);

        $viewAspects = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_ASPECT);
        $this->assertCount(1, $viewAspects);
        $this->assertCount(3, $viewAspects[0]);
        $this->assertEquals($newView["viewRoot"], intval($viewAspects[0]["viewRoot"]));
        $this->assertEquals($aspect->getId(), intval($viewAspects[0]["aspect"]));
        $this->assertEquals($newView["id"], intval($viewAspects[0]["view"]));
    }

    /**
     * @test
     */
    public function updateViewChangeViewTypeParams()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view, $aspect);

        // When
        $newView = ["id" => 1, "viewRoot" => 1, "type" => Block::ID, "direction" => "horizontal"];
        ViewHandler::updateView($newView, $aspect);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(1, $views);
        $this->assertCount(11, $views[0]);
        foreach (["id", "type", "cssId", "class", "style", "visibilityCondition", "loopData"] as $param) {
            $this->assertEquals($newView[$param] ?? null, $views[0][$param]);
        }
        $this->assertEquals(VisibilityType::VISIBLE, $views[0]["visibilityType"]);
        $this->assertEmpty($views[0]["variables"]);
        $this->assertEmpty($views[0]["events"]);

        $viewAspects = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_ASPECT);
        $this->assertCount(1, $viewAspects);
        $this->assertCount(3, $viewAspects[0]);
        $this->assertEquals($newView["viewRoot"], intval($viewAspects[0]["viewRoot"]));
        $this->assertEquals($aspect->getId(), intval($viewAspects[0]["aspect"]));
        $this->assertEquals($newView["id"], intval($viewAspects[0]["view"]));
    }

    /**
     * @test
     */
    public function updateViewChangeAspect()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view, $aspect);

        // When
        $newAspect = Aspect::getAspectBySpecs(0, Role::getRoleId("Teacher", 0), null);
        ViewHandler::updateView($view, $newAspect);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(1, $views);
        $this->assertCount(11, $views[0]);
        foreach (["id", "type", "cssId", "class", "style", "visibilityCondition", "loopData"] as $param) {
            $this->assertEquals($view[$param] ?? null, $views[0][$param]);
        }
        $this->assertEquals(VisibilityType::VISIBLE, $views[0]["visibilityType"]);
        $this->assertEquals("vertical", $views[0]["direction"]);
        $this->assertEmpty($views[0]["variables"]);
        $this->assertEmpty($views[0]["events"]);

        $viewAspects = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_ASPECT);
        $this->assertCount(1, $viewAspects);
        $this->assertCount(3, $viewAspects[0]);
        $this->assertEquals($view["viewRoot"], intval($viewAspects[0]["viewRoot"]));
        $this->assertEquals($newAspect->getId(), intval($viewAspects[0]["aspect"]));
        $this->assertEquals($view["id"], intval($viewAspects[0]["view"]));
    }

    /**
     * @test
     */
    public function updateViewChangeVariables()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID,
            "variables" => ["var1" => "1 + 2"],
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view, $aspect);

        // When
        $newView = ["id" => 1, "viewRoot" => 1, "type" => Block::ID,
            "variables" => [
                "var1" => "1 + 2",
                "var2" => "3 + 4",
            ]
        ];
        ViewHandler::updateView($newView, $aspect);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(1, $views);
        $this->assertCount(11, $views[0]);
        foreach (["id", "type", "cssId", "class", "style", "visibilityCondition", "loopData"] as $param) {
            $this->assertEquals($view[$param] ?? null, $views[0][$param]);
        }
        $this->assertEquals(VisibilityType::VISIBLE, $views[0]["visibilityType"]);
        $this->assertEquals("vertical", $views[0]["direction"]);
        $this->assertEmpty($views[0]["events"]);

        $variables = $views[0]["variables"];
        $this->assertCount(2, $variables);
        $this->assertEquals("var1", $variables[0]["name"]);
        $this->assertEquals("1 + 2", $variables[0]["value"]);
        $this->assertEquals("var2", $variables[1]["name"]);
        $this->assertEquals("3 + 4", $variables[1]["value"]);
    }

    /**
     * @test
     */
    public function updateViewChangeEvents()
    {
        // Given
        $view = [
            "id" => 1,
            "viewRoot" => 1,
            "type" => Block::ID,
            "events" => [EventType::CLICK => "toggleView(100)"],
        ];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view, $aspect);

        // When
        $newView = ["id" => 1, "viewRoot" => 1, "type" => Block::ID,
            "events" => [
                EventType::CLICK => "toggleView(100)",
                EventType::WHEEL => "toggleView(200)"
            ]
        ];
        ViewHandler::updateView($newView, $aspect);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(1, $views);
        $this->assertCount(11, $views[0]);
        foreach (["id", "type", "cssId", "class", "style", "visibilityCondition", "loopData"] as $param) {
            $this->assertEquals($view[$param] ?? null, $views[0][$param]);
        }
        $this->assertEquals(VisibilityType::VISIBLE, $views[0]["visibilityType"]);
        $this->assertEquals("vertical", $views[0]["direction"]);
        $this->assertEmpty($views[0]["variables"]);

        $events = $views[0]["events"];
        $this->assertCount(2, $events);
        $this->assertEquals(EventType::CLICK, $events[0]["type"]);
        $this->assertEquals("toggleView(100)", $events[0]["action"]);
        $this->assertEquals(EventType::WHEEL, $events[1]["type"]);
        $this->assertEquals("toggleView(200)", $events[1]["action"]);
    }


    /**
     * @test
     */
    public function deleteView()
    {
        // Given
        $view1 = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view2 = ["id" => 2, "viewRoot" => 2, "type" => Block::ID];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view1, $aspect);
        ViewHandler::insertView($view2, $aspect);

        // When
        ViewHandler::deleteView(1);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(1, $views);
        $this->assertEquals(2, $views[0]["id"]);
    }

    /**
     * @test
     */
    public function deleteViewInexistentView()
    {
        // Given
        $view1 = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view2 = ["id" => 2, "viewRoot" => 2, "type" => Block::ID];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($view1, $aspect);
        ViewHandler::insertView($view2, $aspect);

        // When
        ViewHandler::deleteView(100);

        // Then
        $views = ViewHandler::getViews();
        $this->assertCount(2, $views);
    }


    /**
     * @test
     */
    public function moveViewAddRootView()
    {
        // Given
        $root = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $aspect = Aspect::getAspectBySpecs(0, null, null);
        ViewHandler::insertView($root, $aspect);

        // When
        ViewHandler::moveView($root["viewRoot"], null, null);

        // Then
        $positions = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_PARENT);
        $this->assertEmpty($positions);
    }

    /**
     * @test
     */
    public function moveViewAddViewToParent()
    {
        // Given
        $parent = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view = ["id" => 2, "viewRoot" => 2, "type" => Text::ID, "text" => "Some text"];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        ViewHandler::insertView($parent, $aspect);
        ViewHandler::moveView($parent["viewRoot"], null, null);
        ViewHandler::insertView($view, $aspect);

        // When
        ViewHandler::moveView($view["viewRoot"], null, ["parent" => $parent["id"], "pos" => 0]);

        // Then
        $positions = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_PARENT);
        $this->assertCount(1, $positions);
        $this->assertCount(3, $positions[0]);
        $this->assertEquals($parent["id"], $positions[0]["parent"]);
        $this->assertEquals($view["viewRoot"], $positions[0]["child"]);
        $this->assertEquals(0, $positions[0]["position"]);
    }

    /**
     * @test
     */
    public function moveViewChangePositionInParent()
    {
        // Given
        $parent = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view1 = ["id" => 2, "viewRoot" => 2, "type" => Text::ID, "text" => "Some text"];
        $view2 = ["id" => 3, "viewRoot" => 3, "type" => Text::ID, "text" => "Some other text"];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        ViewHandler::insertView($parent, $aspect);
        ViewHandler::moveView($parent["viewRoot"], null, null);
        ViewHandler::insertView($view1, $aspect);
        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $parent["id"], "pos" => 0]);
        ViewHandler::insertView($view2, $aspect);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $parent["id"], "pos" => 1]);

        // When
        ViewHandler::moveView($view1["viewRoot"], ["parent" => $parent["id"], "pos" => 0], null);
        ViewHandler::moveView($view2["viewRoot"], ["parent" => $parent["id"], "pos" => 1], null);
        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $parent["id"], "pos" => 1]);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $parent["id"], "pos" => 0]);

        // Then
        $positions = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_PARENT, [], "*", "parent, position");
        $this->assertCount(2, $positions);

        $posView2 = $positions[0];
        $this->assertCount(3, $posView2);
        $this->assertEquals($parent["id"], $posView2["parent"]);
        $this->assertEquals($view2["viewRoot"], $posView2["child"]);
        $this->assertEquals(0, $posView2["position"]);

        $posView1 = $positions[1];
        $this->assertCount(3, $posView1);
        $this->assertEquals($parent["id"], $posView1["parent"]);
        $this->assertEquals($view1["viewRoot"], $posView1["child"]);
        $this->assertEquals(1, $posView1["position"]);
    }

    /**
     * @test
     */
    public function moveViewChangePositionInParentWithoutInvalidatingChildren()
    {
        // Given
        $parent = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view1 = ["id" => 2, "viewRoot" => 2, "type" => Text::ID, "text" => "Some text"];
        $view2 = ["id" => 3, "viewRoot" => 3, "type" => Text::ID, "text" => "Some other text"];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        ViewHandler::insertView($parent, $aspect);
        ViewHandler::moveView($parent["viewRoot"], null, null);
        ViewHandler::insertView($view1, $aspect);
        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $parent["id"], "pos" => 0]);
        ViewHandler::insertView($view2, $aspect);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $parent["id"], "pos" => 1]);

        // Then
        $this->expectException(PDOException::class);

        // When
        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $parent["id"], "pos" => 1]);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $parent["id"], "pos" => 0]);
    }

    /**
     * @test
     */
    public function moveViewChangeParent()
    {
        // Given
        $root = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view1 = ["id" => 2, "viewRoot" => 2, "type" => Text::ID, "text" => "Some text"];
        $view2 = ["id" => 3, "viewRoot" => 3, "type" => Text::ID, "text" => "Some other text"];
        $block = ["id" => 4, "viewRoot" => 4, "type" => Block::ID];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        ViewHandler::insertView($root, $aspect);
        ViewHandler::moveView($root["viewRoot"], null, null);
        ViewHandler::insertView($view1, $aspect);
        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $root["id"], "pos" => 0]);
        ViewHandler::insertView($view2, $aspect);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $root["id"], "pos" => 1]);

        ViewHandler::insertView($block, $aspect);
        ViewHandler::moveView($block["viewRoot"], null, ["parent" => $root["id"], "pos" => 2]);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $block["id"], "pos" => 0]);

        // When
        ViewHandler::moveView($view1["viewRoot"], ["parent" => $root["id"], "pos" => 0], null);
        ViewHandler::moveView($view2["viewRoot"], ["parent" => $root["id"], "pos" => 1], ["parent" => $root["id"], "pos" => 0]);
        ViewHandler::moveView($block["viewRoot"], ["parent" => $root["id"], "pos" => 2], ["parent" => $root["id"], "pos" => 1]);

        ViewHandler::moveView($view2["viewRoot"], ["parent" => $block["id"], "pos" => 0], null);
        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $block["id"], "pos" => 0]);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $block["id"], "pos" => 1]);

        // Then
        $positions = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_PARENT, [], "*", "parent, position");
        $this->assertCount(4, $positions);

        $posView21 = $positions[0];
        $this->assertCount(3, $posView21);
        $this->assertEquals($root["id"], $posView21["parent"]);
        $this->assertEquals($view2["viewRoot"], $posView21["child"]);
        $this->assertEquals(0, $posView21["position"]);

        $posViewBlock = $positions[1];
        $this->assertCount(3, $posView21);
        $this->assertEquals($root["id"], $posViewBlock["parent"]);
        $this->assertEquals($block["viewRoot"], $posViewBlock["child"]);
        $this->assertEquals(1, $posViewBlock["position"]);

        $posView1 = $positions[2];
        $this->assertCount(3, $posView1);
        $this->assertEquals($block["id"], $posView1["parent"]);
        $this->assertEquals($view1["viewRoot"], $posView1["child"]);
        $this->assertEquals(0, $posView1["position"]);

        $posView22 = $positions[3];
        $this->assertCount(3, $posView22);
        $this->assertEquals($block["id"], $posView22["parent"]);
        $this->assertEquals($view2["viewRoot"], $posView22["child"]);
        $this->assertEquals(1, $posView22["position"]);
    }

    /**
     * @test
     */
    public function moveViewChangeParentWithoutInvalidatingChildren()
    {
        // Given
        $root = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view1 = ["id" => 2, "viewRoot" => 2, "type" => Text::ID, "text" => "Some text"];
        $view2 = ["id" => 3, "viewRoot" => 3, "type" => Text::ID, "text" => "Some other text"];
        $block = ["id" => 4, "viewRoot" => 4, "type" => Block::ID];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        ViewHandler::insertView($root, $aspect);
        ViewHandler::moveView($root["viewRoot"], null, null);
        ViewHandler::insertView($view1, $aspect);
        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $root["id"], "pos" => 0]);
        ViewHandler::insertView($view2, $aspect);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $root["id"], "pos" => 1]);

        ViewHandler::insertView($block, $aspect);
        ViewHandler::moveView($block["viewRoot"], null, ["parent" => $root["id"], "pos" => 2]);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $block["id"], "pos" => 0]);

        // Then
        $this->expectException(PDOException::class);

        // When
        ViewHandler::moveView($view2["viewRoot"], ["parent" => $root["id"], "pos" => 1], ["parent" => $root["id"], "pos" => 0]);
        ViewHandler::moveView($block["viewRoot"], ["parent" => $root["id"], "pos" => 2], ["parent" => $root["id"], "pos" => 1]);

        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $block["id"], "pos" => 0]);
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $block["id"], "pos" => 1]);
    }

    /**
     * @test
     */
    public function moveViewRemoveFromParent()
    {
        // Given
        $parent = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view = ["id" => 2, "viewRoot" => 2, "type" => Text::ID, "text" => "Some text"];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        ViewHandler::insertView($parent, $aspect);
        ViewHandler::moveView($parent["viewRoot"], null, null);
        ViewHandler::insertView($view, $aspect);
        ViewHandler::moveView($view["viewRoot"], null, ["parent" => $parent["id"], "pos" => 0]);

        // When
        ViewHandler::moveView($view["viewRoot"], ["parent" => $parent["id"], "pos" => 0], null);

        // Then
        $positions = Core::database()->selectMultiple(ViewHandler::TABLE_VIEW_PARENT);
        $this->assertEmpty($positions);
    }

    /**
     * @test
     */
    public function moveViewViewDoesntExist()
    {
        $this->expectException(PDOException::class);
        ViewHandler::moveView(2, null, ["parent" => 1, "pos" => 0]);
    }

    /**
     * @test
     */
    public function moveViewParentDoesntExist()
    {
        // Given
        $parent = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view = ["id" => 2, "viewRoot" => 2, "type" => Text::ID, "text" => "Some text"];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        ViewHandler::insertView($parent, $aspect);
        ViewHandler::moveView($parent["viewRoot"], null, null);
        ViewHandler::insertView($view, $aspect);
        ViewHandler::moveView($view["viewRoot"], null, ["parent" => $parent["id"], "pos" => 0]);

        // Then
        $this->expectException(PDOException::class);

        // When
        ViewHandler::moveView($view["viewRoot"], ["parent" => $parent["id"], "pos" => 0], ["parent" => 100, "pos" => 0]);
    }

    /**
     * @test
     */
    public function moveViewDuplicatePositionInParent()
    {
        // Given
        $root = ["id" => 1, "viewRoot" => 1, "type" => Block::ID];
        $view1 = ["id" => 2, "viewRoot" => 2, "type" => Text::ID, "text" => "Some text"];
        $view2 = ["id" => 3, "viewRoot" => 3, "type" => Text::ID, "text" => "Some other text"];
        $aspect = Aspect::getAspectBySpecs(0, null, null);

        ViewHandler::insertView($root, $aspect);
        ViewHandler::moveView($root["viewRoot"], null, null);
        ViewHandler::insertView($view1, $aspect);
        ViewHandler::moveView($view1["viewRoot"], null, ["parent" => $root["id"], "pos" => 0]);
        ViewHandler::insertView($view2, $aspect);

        // Then
        $this->expectException(PDOException::class);

        // When
        ViewHandler::moveView($view2["viewRoot"], null, ["parent" => $root["id"], "pos" => 0]);
    }
}
