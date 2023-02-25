<?php

namespace GameCourse\Adaptation;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Role\Role;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

class GameElementTest extends TestCase
{
    private $course;
    private $courseUser;
    private $module;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["roles", "modules"], ["CronJob"]);
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

        // Set course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->course = $course;

        // Set a course user student
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $courseUser = $course->addUserToCourse($user->getId(), "Student");
        $this->courseUser = $courseUser;

        // Enable module
        // TODO

    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        TestingUtils::cleanTables([Course::TABLE_COURSE,
            User::TABLE_USER,
            GameElement::TABLE_GAME_ELEMENT,
            GameElement::TABLE_PREFERENCES_QUESTIONNAIRE_ANSWERS,
            GameElement::TABLE_ELEMENT_USER,
            GameElement::TABLE_USER_GAME_ELEMENT_PREFERENCES,
            Role::TABLE_ROLE,
            Role::TABLE_USER_ROLE]);
        TestingUtils::resetAutoIncrement([
            Course::TABLE_COURSE,
            User::TABLE_USER,
            GameElement::TABLE_GAME_ELEMENT,
            GameElement::TABLE_PREFERENCES_QUESTIONNAIRE_ANSWERS,
            GameElement::TABLE_ELEMENT_USER,
            GameElement::TABLE_USER_GAME_ELEMENT_PREFERENCES,
            Role::TABLE_ROLE,
            Role::TABLE_USER_ROLE]);
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

    public function gameElementSuccessProvider(): array
    {
        return [
            "default" => [false, false],
            "active" => [true, false],
            "notify" => [true, true]
            ];
    }

    public function gameElementFailureProvider(): array
    {
        return [
            "not active but notify" => [false, true]
        ];
    }

    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function gameElementConstructor(){
        $gameElement = new GameElement(123);
        $this->assertEquals(123, $gameElement->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getId(){
        // TODO
        // $gameElement = GameElement::addGameElement($this->course->getId(), );
    }

}
