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
        foreach ($rolesB[$parent] as $key => $value){
            $roleId = Role::getRoleId($key, $this->course->getId());
            GameElement::addGameElementDescription($roleId, $value[0]);
        }
        $this->badgesGameElement = GameElement::addGameElement($this->course->getId(), $badgesModule->getId());

        $leaderboardModule = new Leaderboard($course);
        $rolesLB = Leaderboard::ADAPTATION_LEADERBOARD;
        $parent = array_keys($rolesLB)[0];
        $children = array_keys($rolesLB[$parent]);
        Role::addAdaptationRolesToCourse($this->course->getId(), $leaderboardModule->getId(), $parent, $children);
        foreach ($rolesLB[$parent] as $key => $value){
            $roleId = Role::getRoleId($key, $this->course->getId());
            GameElement::addGameElementDescription($roleId, $value[0]);
        }
        $this->leaderboardGameElement = GameElement::addGameElement($this->course->getId(), $leaderboardModule->getId());

        $profileModule = new Profile($course);
        $rolesP = Profile::ADAPTATION_PROFILE;
        $parent = array_keys($rolesP)[0];
        $children = array_keys($rolesP[$parent]);
        Role::addAdaptationRolesToCourse($this->course->getId(), $profileModule->getId(), $parent, $children);
        foreach ($rolesP[$parent] as $key => $value){
            $roleId = Role::getRoleId($key, $this->course->getId());
            GameElement::addGameElementDescription($roleId, $value[0]);
        }
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
        // Given
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user = $this->course->addUserToCourse($user->getId());
        $user->addRole("Student");

        // When
        $this->badgesGameElement->setActive(true);
        $this->leaderboardGameElement->setActive(true);
        $this->profileGameElement->setActive(true);

        // Then
        $this->assertTrue($this->badgesGameElement->isActive());
        $this->assertTrue($this->leaderboardGameElement->isActive());
        $this->assertTrue($this->profileGameElement->isActive());

        // See if users with notification are updated or not
        $users = Core::database()->selectMultiple(GameElement::TABLE_ADAPTATION_USER_NOTIFICATION);
        $this->assertIsArray($users);
        $this->assertCount(3, $users);
        $this->assertEquals([
            [ "element" => $this->badgesGameElement->getId(), "user" => $user->getId()],
            [ "element" => $this->leaderboardGameElement->getId(), "user" => $user->getId()],
            [ "element" => $this->profileGameElement->getId(), "user" => $user->getId()],
        ], $users);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotActive()
    {
        // Given
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user = $this->course->addUserToCourse($user->getId());
        $user->addRole("Student");

        $this->badgesGameElement->setActive(true);
        $this->leaderboardGameElement->setActive(true);
        $this->profileGameElement->setActive(true);

        // When
        $this->badgesGameElement->setActive(false);
        $this->leaderboardGameElement->setActive(false);
        $this->profileGameElement->setActive(false);

        $this->assertFalse($this->badgesGameElement->isActive());
        $this->assertFalse($this->leaderboardGameElement->isActive());
        $this->assertFalse($this->profileGameElement->isActive());

        // See if users with notification are updated or not
        $users = Core::database()->selectMultiple(GameElement::TABLE_ADAPTATION_USER_NOTIFICATION);
        $this->assertIsArray($users);
        $this->assertCount(0, $users);
        $this->assertEquals([], $users);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotifySuccess()
    {
        // Given
        $this->badgesGameElement->setActive(true);
        $this->leaderboardGameElement->setActive(true);
        $this->profileGameElement->setActive(true);

        // When
        $this->badgesGameElement->setNotify(true);
        $this->leaderboardGameElement->setNotify(true);
        $this->profileGameElement->setNotify(true);

        // Then
        $this->assertTrue($this->badgesGameElement->notify());
        $this->assertTrue($this->leaderboardGameElement->notify());
        $this->assertTrue($this->profileGameElement->notify());

    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotifyFailure()
    {
        // Given
        $this->badgesGameElement->setActive(false);
        $this->leaderboardGameElement->setActive(false);
        $this->profileGameElement->setActive(false);

        try {
            $this->badgesGameElement->setNotify(true);
            $this->leaderboardGameElement->setNotify(true);
            $this->profileGameElement->setNotify(true);

        } catch (Exception $e){
            $this->assertEquals([
                "id" => $this->badgesGameElement->getId(),
                "course" => $this->badgesGameElement->getCourse(),
                "module" => $this->badgesGameElement->getModule(),
                "isActive" => false,
                "notify" => false], $this->badgesGameElement->getData());

            $this->assertEquals([
                "id" => $this->leaderboardGameElement->getId(),
                "course" => $this->leaderboardGameElement->getCourse(),
                "module" => $this->leaderboardGameElement->getModule(),
                "isActive" => false,
                "notify" => false], $this->leaderboardGameElement->getData());

            $this->assertEquals([
                "id" => $this->profileGameElement->getId(),
                "course" => $this->profileGameElement->getCourse(),
                "module" => $this->profileGameElement->getModule(),
                "isActive" => false,
                "notify" => false], $this->profileGameElement->getData());
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function setNotNotify(){
        $this->badgesGameElement->setNotify(false);
        $this->leaderboardGameElement->setNotify(false);
        $this->profileGameElement->setNotify(false);

        $this->assertFalse($this->badgesGameElement->notify());
        $this->assertFalse($this->leaderboardGameElement->notify());
        $this->assertFalse($this->profileGameElement->notify());

    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementById(){
        $gameElement = GameElement::getGameElementById(1);
        $this->assertEquals($gameElement, $this->badgesGameElement);

        $gameElement = GameElement::getGameElementById(2);
        $this->assertEquals($gameElement, $this->leaderboardGameElement);

        $gameElement = GameElement::getGameElementById(3);
        $this->assertEquals($gameElement, $this->profileGameElement);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementsActive(){
        // Given
        $this->badgesGameElement->setActive(true);
        $this->leaderboardGameElement->setActive(true);
        $this->profileGameElement->setActive(true);

        // When
        $elements = GameElement::getGameElements($this->course->getId(), true);

        // Then
        $this->assertIsArray($elements);
        $this->assertCount(3, $elements);
        $this->assertEquals(["Badges", "Leaderboard", "Profile"], $elements);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementsNotActive(){
        // Given
        $this->badgesGameElement->setActive(false);
        $this->leaderboardGameElement->setActive(false);
        $this->profileGameElement->setActive(false);

        // When
        $elements = GameElement::getGameElements($this->course->getId(), false);

        // Then
        $this->assertIsArray($elements);
        $this->assertCount(3, $elements);
        $this->assertEquals(["Badges", "Leaderboard", "Profile"], $elements);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementsWithNoActive(){
        // When
        $elements = GameElement::getGameElements($this->course->getId());

        // Then
        $this->assertIsArray($elements);
        $this->assertCount(3, $elements);
        $this->assertEquals(["Badges", "Leaderboard", "Profile"], $elements);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementsAllActive(){
        // Given
        $this->badgesGameElement->setActive(true);
        $this->leaderboardGameElement->setActive(true);
        $this->profileGameElement->setActive(true);

        // When
        $elements = GameElement::getGameElements($this->course->getId(), true, false);

        // Then
        $this->assertIsArray($elements);
        $this->assertCount(3, $elements);
        $this->assertEquals([
            ["id" => 1, "course" => $this->course->getId(), "module" => "Badges", "isActive" => true, "notify" => false],
            ["id" => 2, "course" => $this->course->getId(), "module" => "Leaderboard", "isActive" => true, "notify" => false],
            ["id" => 3, "course" => $this->course->getId(), "module" => "Profile", "isActive" => true, "notify" => false]],
            $elements);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementsAllNotActive(){
        // Given
        $this->badgesGameElement->setActive(false);
        $this->leaderboardGameElement->setActive(false);
        $this->profileGameElement->setActive(false);

        // When
        $elements = GameElement::getGameElements($this->course->getId(), false, false);

        // Then
        $this->assertIsArray($elements);
        $this->assertCount(3, $elements);
        $this->assertEquals([
            ["id" => 1, "course" => $this->course->getId(), "module" => "Badges", "isActive" => false, "notify" => false],
            ["id" => 2, "course" => $this->course->getId(), "module" => "Leaderboard", "isActive" => false, "notify" => false],
            ["id" => 3, "course" => $this->course->getId(), "module" => "Profile", "isActive" => false, "notify" => false]],
            $elements);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementsAllWithNoActive(){ // FIXME -> return array is all strings?
        // When
        $elements = GameElement::getGameElements($this->course->getId(), null, false);

        // Then
        $this->assertIsArray($elements);
        $this->assertCount(3, $elements);
        $this->assertEquals([
            ["id" => "1", "course" => "1", "module" => "Badges", "isActive" => "0", "notify" => "0"],
            ["id" => "2", "course" => "1", "module" => "Leaderboard", "isActive" => "0", "notify" => "0"],
            ["id" => "3", "course" => "1", "module" => "Profile", "isActive" => "0", "notify" => "0"]],
            $elements);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementByModule(){
        // Given
        $moduleBadges = "Badges";
        $moduleLeaderboard = "Leaderboard";
        $moduleProfile = "Profile";

        // When
        $gameElement1 = GameElement::getGameElementByModule($this->course->getId(), $moduleBadges);
        $gameElement2 = GameElement::getGameElementByModule($this->course->getId(), $moduleLeaderboard);
        $gameElement3 = GameElement::getGameElementByModule($this->course->getId(), $moduleProfile);
        $gameElement4 = GameElement::getGameElementByModule($this->course->getId(), "whatever");

        // Then
        $this->assertEquals($gameElement1, $this->badgesGameElement);
        $this->assertEquals($gameElement2, $this->leaderboardGameElement);
        $this->assertEquals($gameElement3, $this->profileGameElement);
        $this->assertEquals(null, $gameElement4);

    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementChildren(){
        // Given
        $this->badgesGameElement->setActive(true);
        $this->leaderboardGameElement->setActive(true);
        $this->profileGameElement->setActive(true);

        // When
        $badgesChildren = $this->badgesGameElement->getGameElementChildren();
        $leaderboardChildren = $this->leaderboardGameElement->getGameElementChildren();
        $profileChildren = $this->profileGameElement->getGameElementChildren();

        // Then
        $this->assertIsArray($badgesChildren);
        $this->assertIsArray($leaderboardChildren);
        $this->assertIsArray($profileChildren);

        $this->assertCount(2, $badgesChildren);
        $this->assertCount(2, $leaderboardChildren);
        $this->assertCount(3, $profileChildren);

        $this->assertEquals([
            "B001" => "Badges displayed in alphabetic order",
            "B002" => "Badges displayed with achieved first"], $badgesChildren);

        $this->assertEquals([
            "LB001" => "Shows entire leaderboard",
            "LB002" => "Leaderboard is snapped and shows 5 people above and below you"], $leaderboardChildren);
        $this->assertEquals([
            "P001" => "Profile displays graphs comparing yourself vs. everyone else",
            "P002" => "Profile displays graphs comparing yourself vs. people with similar progress as you",
            "P003" => "Profile displays graphs with your progress (not comparing with anyone else)"], $profileChildren);

    }

    /**
     * @test
     * @throws Exception
     */
    public function submitGameElementQuestionnaire(){
        // Given
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user = $this->course->addUserToCourse($user->getId());
        $user->addRole("Student");

        $q1 = false;
        $element = $this->badgesGameElement->getId();

        // When
        GameElement::submitGameElementQuestionnaire($this->course->getId(), $user->getId(), $q1, null, null, $element);

        // Then
        $entry = GameElement::isQuestionnaireAnswered($this->course->getId(), $user->getId(), $this->badgesGameElement->getId());
        $this->assertTrue($entry);

    }

    /**
     * @test
     * @throws Exception
     */
    public function getGameElementStatistics(){
        // Given
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $user = $this->course->addUserToCourse($user->getId());
        $user->addRole("Student");

        $q1 = false;
        $element = $this->badgesGameElement->getId();
        GameElement::submitGameElementQuestionnaire($this->course->getId(), $user->getId(), $q1, null, null, $element);

        // When
        $statistics = GameElement::getElementStatistics($this->course, $this->badgesGameElement->getId());

        // Then
        $this->assertIsArray($statistics);
        $this->assertCount(3, $statistics);
        $this->assertEquals([
            "question1" => ["false" => "1", "true" => "0"],
            "question2" => [null],
            "question3" => [1 => "0", 2 => "0", 3 => "0", 4 => "0", 5 => "0", 6 => "0", 7 => "0", 8 => "0", 9 => "0", 10 => "0"]
        ], $statistics);
    }

}
