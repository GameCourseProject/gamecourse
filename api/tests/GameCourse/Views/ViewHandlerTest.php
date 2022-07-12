<?php
namespace GameCourse\Views;

use GameCourse\Core\Core;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Event\EventType;
use GameCourse\Views\ViewType\Block;
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


    // TODO: updateView


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


    // TODO: moveView
}
