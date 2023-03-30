<?php

namespace GameCourse\Adaptation;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\Leaderboard\Leaderboard;
use GameCourse\Module\Module;
use GameCourse\Module\Profile\Profile;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\Role\Role;
use GameCourse\User\User;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

class GameElementTest extends TestCase
{
    private $course;
    private $courseUser;

    private $badgesGameElement;
    private $leaderboardGameElement;
    private $profileGameElement;


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
        // Set logged user (admin)
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

        // Enable modules with adaptation roles (and their dependencies)
        $awards = new Awards($course);
        $awards->setEnabled(true);

        $XPAndPoints = new XPLevels($course);
        $XPAndPoints->setEnabled(true);

        Role::addDefaultRolesToCourse($course->getId());

        $badgesModule = new Badges($course);
        $rolesB = Badges::ADAPTATION_BADGES;
        $parent = array_keys($rolesB)[0];
        $children = array_keys($rolesB[$parent]);
        Role::addAdaptationRolesToCourse($this->course->getId(), $badgesModule->getId(), $parent, $children);
        $this->badgesGameElement = GameElement::addGameElement($this->course->getId(), $badgesModule->getId());

        $leaderboardModule = new Leaderboard($course);
        $rolesLB = Leaderboard::ADAPTATION_LEADERBOARD;
        $parent = array_keys($rolesLB)[0];
        $children = array_keys($rolesLB[$parent]);
        Role::addAdaptationRolesToCourse($this->course->getId(), $leaderboardModule->getId(), $parent, $children);
        $this->leaderboardGameElement = GameElement::addGameElement($this->course->getId(), $leaderboardModule->getId());

        $profileModule = new Profile($course);
        $rolesP = Profile::ADAPTATION_PROFILE;
        $parent = array_keys($rolesP)[0];
        $children = array_keys($rolesP[$parent]);
        Role::addAdaptationRolesToCourse($this->course->getId(), $profileModule->getId(), $parent, $children);
        $this->profileGameElement = GameElement::addGameElement($this->course->getId(), $profileModule->getId());
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        TestingUtils::cleanTables([Course::TABLE_COURSE,
            User::TABLE_USER,
            GameElement::TABLE_ADAPTATION_GAME_ELEMENT,
            GameElement::TABLE_ADAPTATION_QUESTIONNAIRE_ANSWERS,
            GameElement::TABLE_ADAPTATION_USER_NOTIFICATION,
            GameElement::TABLE_ADAPTATION_USER_PREFERENCES,
            Role::TABLE_ROLE,
            Role::TABLE_USER_ROLE]);
        TestingUtils::resetAutoIncrement([
            Course::TABLE_COURSE,
            User::TABLE_USER,
            GameElement::TABLE_ADAPTATION_GAME_ELEMENT,
            GameElement::TABLE_ADAPTATION_QUESTIONNAIRE_ANSWERS,
            GameElement::TABLE_ADAPTATION_USER_NOTIFICATION,
            GameElement::TABLE_ADAPTATION_USER_PREFERENCES,
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

    public function gameElementDataSuccessProvider(): array
    {
        return [
            "default" => [false, false],
            "active" => [true, false],
            "notify" => [true, true]
            ];
    }

    public function gameElementDataFailureProvider(): array
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
     */
    public function getGameElementId(){
        $this->assertEquals(1, $this->badgesGameElement->getId());
        $this->assertEquals(2, $this->leaderboardGameElement->getId());
        $this->assertEquals(3, $this->profileGameElement->getId());
    }

    /**
     * @test
     */
    public function getGameElementCourse(){
        $this->assertEquals(1, $this->badgesGameElement->getCourse());
        $this->assertEquals(1, $this->leaderboardGameElement->getCourse());
        $this->assertEquals(1, $this->profileGameElement->getCourse());
    }

    /**
     * @test
     */
    public function getGameElementModule(){
        $this->assertEquals("Badges", $this->badgesGameElement->getModule());
        $this->assertEquals("Leaderboard", $this->leaderboardGameElement->getModule());
        $this->assertEquals("Profile", $this->profileGameElement->getModule());
    }

    /**
     * @test
     */
    public function getGameElementIsActive(){
        $this->assertEquals(false, $this->badgesGameElement->isActive());
        $this->assertEquals(false, $this->leaderboardGameElement->isActive());
        $this->assertEquals(false, $this->profileGameElement->isActive());
    }

    /**
     * @test
     */
    public function getGameElementNotify(){
        $this->assertEquals(false, $this->badgesGameElement->notify());
        $this->assertEquals(false, $this->leaderboardGameElement->notify());
        $this->assertEquals(false, $this->profileGameElement->notify());
    }

    /**
     * @test
     * @dataProvider gameElementDataSuccessProvider
     * @throws Exception
     */
    public function setDataSuccess(bool $active, bool $notify){
        $fieldValues = ["isActive" => $active, "notify" => $notify];

        $this->badgesGameElement->setData($fieldValues);
        $fieldValues["course"] = $this->badgesGameElement->getCourse();
        $fieldValues["id"] = $this->badgesGameElement->getId();
        $fieldValues["module"] = $this->badgesGameElement->getModule();
        $this->assertEquals($this->badgesGameElement->getData(), $fieldValues);

       // sets everything in default values
       $this->badgesGameElement->setData(["isActive" => false, "notify" => false]);
    }

    /**
     * @test
     * @dataProvider gameElementDataFailureProvider
     * @throws Exception
     */
    public function setDataFailure(bool $active, bool $notify){
        $fieldValues = ["isActive" => $active, "notify" => $notify];
        try{
            $this->badgesGameElement->setData($fieldValues);
            $this->fail("Exception should have been thrown on 'SetDataFailure'");
        } catch (Exception $e){
            $this->assertEquals([
                "id" => $this->badgesGameElement->getId(),
                "course" => $this->badgesGameElement->getCourse(),
                "module" => $this->badgesGameElement->getModule(),
                "isActive" => false,
                "notify" => false], $this->badgesGameElement->getData());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActive()
    {
        $this->badgesGameElement->setActive(true);
        $this->leaderboardGameElement->setActive(true);
        $this->profileGameElement->setActive(true);

        $this->assertTrue($this->badgesGameElement->isActive());
        $this->assertTrue($this->leaderboardGameElement->isActive());
        $this->assertTrue($this->profileGameElement->isActive());

        // FIXME --> Incomplete
        // Should also see updateUsers() function
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotActive(){
        $this->badgesGameElement->setActive(false);
        $this->leaderboardGameElement->setActive(false);
        $this->profileGameElement->setActive(false);

        $this->assertFalse($this->badgesGameElement->isActive());
        $this->assertFalse($this->leaderboardGameElement->isActive());
        $this->assertFalse($this->profileGameElement->isActive());

        // FIXME --> Incomplete
        // Should also see updateUsers() function
    }

    /**
     * @test
     * @throws Exception
     */
    // FIXME --> SHOULD HAVE IN CONSIDERATION FAILURE DATA PROVIDER AS WELL (in gameElement.php too)
    public function setNotify(){
        $this->badgesGameElement->setNotify(true);
        $this->leaderboardGameElement->setNotify(true);
        $this->profileGameElement->setNotify(true);

        $this->assertTrue($this->badgesGameElement->notify());
        $this->assertTrue($this->leaderboardGameElement->notify());
        $this->assertTrue($this->profileGameElement->notify());

        // FIXME -- Incomplete
        // Should see sendNotification() function as well
    }

    /**
     * @test
     * @throws Exception
     */
    // FIXME --> SHOULD HAVE IN CONSIDERATION FAILURE DATA PROVIDER AS WELL (in gameElement.php too)
    public function setNotNotify(){
        $this->badgesGameElement->setNotify(false);
        $this->leaderboardGameElement->setNotify(false);
        $this->profileGameElement->setNotify(false);

        $this->assertFalse($this->badgesGameElement->notify());
        $this->assertFalse($this->leaderboardGameElement->notify());
        $this->assertFalse($this->profileGameElement->notify());

        // FIXME -- Incomplete
        // Should see sendNotification() function as well
    }

    

}
