<?php
namespace GameCourse\User;

use DateTime;
use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use GameCourse\Views\Page\Page;
use GameCourse\Views\ViewHandler;
use PDOException;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Throwable;

/**
 * NOTE: only run tests outside the production environment as
 *       it might change the database and/or important data
 */
class CourseUserTest extends TestCase
{
    private $loggedUser;
    private $user;
    private $course;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["roles", "views"], ["CronJob"]);
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
        $this->loggedUser = $loggedUser;

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->course = $course;

        // Set a user
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $this->user = $user;
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER, ViewHandler::TABLE_VIEW]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Role::TABLE_ROLE, Page::TABLE_PAGE]);
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

    public function addCourseUserSuccessProvider(): array
    {
        $userId = 2;
        $courseId = 1;
        return [
            "no role" => [$userId, $courseId, null, null, true],
            "with teacher role name" => [$userId, $courseId, "Teacher", null, true],
            "with student role name" => [$userId, $courseId, "Student", null, true],
            "with teacher role ID" => [$userId, $courseId, null, 4, true],
            "with student role ID" => [$userId, $courseId, null, 5, true],
            "with role name and role ID" => [$userId, $courseId, "Student", 5, true],
            "active" => [$userId, $courseId, null, null, true],
            "inactive" => [$userId, $courseId, null, null, false]
        ];
    }

    public function addCourseUserFailureProvider(): array
    {
        $userId = 2;
        $courseId = 1;
        return [
            "course doesn't exist" => [$userId, 10, null, null, true],
            "user doesn't exist" => [10, $courseId, null, null, true],
            "role name doesn't exist" => [$userId, $courseId, "role_doesnt_exist", null, true],
            "role ID doesn't exist" => [$userId, $courseId, null, 10, true]
        ];
    }


    public function setActivitySuccessProvider(): array
    {
        return [
            "null" => [null],
            "datetime" => [date("Y-m-d H:i:s", time())],
            "trimmed" => [" " . date("Y-m-d H:i:s", time()) . " "]
        ];
    }

    public function setActivityFailureProvider(): array
    {
        return [
            "only date" => [date("Y-m-d", time())],
            "only time" => [date("H:i:s", time())],
            "empty" => [""]
        ];
    }


    public function setDataSuccessProvider(): array
    {
        return [
            "same data" => [["lastActivity" => null, "isActive" => true]],
            "different lastActivity" => [["lastActivity" => date("Y-m-d H:i:s", time())]],
            "different isActive" => [["isActive" => false]],
            "all different" => [["lastActivity" => date("Y-m-d H:i:s", time()), "isActive" => false]]
        ];
    }

    public function setDataFailureProvider(): array
    {
        return [
            "incorrect lastActivity format" => [["lastActivity" => date("Y-m-d", time())]]
        ];
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function courseUserConstructor()
    {
        $courseUser = new CourseUser($this->loggedUser->getId(), $this->course);
        $this->assertEquals($this->loggedUser->getId(), $courseUser->getId());
        $this->assertEquals($this->course, $courseUser->getCourse());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getId()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals($this->user->getId(), $courseUser->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourse()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals($this->course, $courseUser->getCourse());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLastActivity()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $datetime = date("Y-m-d H:i:s", time());
        $courseUser->setLastActivity($datetime);
        $this->assertEquals($datetime, $courseUser->getLastActivity());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLastActivityNull()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertNull($courseUser->getLastActivity());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLandingPage()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $page = Page::addPage($this->course->getId(), "Landing Page");
        $roles = Role::getCourseRoles($this->course->getId(), false);
        $roles[0]["landingPage"] = $page->getId();
        Role::updateCourseRoles($this->course->getId(), $roles);
        $courseUser->addRole(null, $roles[0]["id"]);
        $this->assertEquals($page, $courseUser->getLandingPage());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getLandingPageNull()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertNull($courseUser->getLandingPage());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isActive()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertTrue($courseUser->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isInactive()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setActive(false);
        $this->assertFalse($courseUser->isActive());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getData()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals(["id" => 2, "name" => "Johanna Smith Doe", "username" => "ist654321", "auth_service" => AuthService::FENIX,
            "email" => "johannadoe@email.com", "studentNumber" => 654321, "nickname" => "Johanna Doe", "major" => "MEIC-A",
            "isAdmin" => false, "isActive" => true, "course" => 1, "lastActivity" => null, "isActiveInCourse" => true,
            "lastLogin" => null], $courseUser->getData());
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDataOnlyUserFields()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals(["id" => 2, "name" => "Johanna Smith Doe", "username" => "ist654321", "auth_service" => AuthService::FENIX,
            "email" => "johannadoe@email.com", "studentNumber" => 654321, "nickname" => "Johanna Doe", "major" => "MEIC-A",
            "isAdmin" => false],
            $courseUser->getData("id, name, username, auth_service, email, studentNumber, nickname, major, isAdmin"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDataOnlyCourseUserFields()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals(["course" => 1, "lastActivity" => null, "isActive" => true],
            $courseUser->getData("course, lastActivity, isActive"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDataMixedFields()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals(["name" => "Johanna Smith Doe", "lastActivity" => null, "isActive" => true],
            $courseUser->getData("name, lastActivity, isActive"));
    }


    /**
     * @test
     * @dataProvider setActivitySuccessProvider
     * @throws Exception
     */
    public function setLastActivitySuccess(?string $lastActivity)
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setLastActivity($lastActivity);
        $this->assertEquals(trim($lastActivity), $courseUser->getLastActivity());
    }

    /**
     * @test
     * @dataProvider setActivityFailureProvider
     * @throws Exception
     */
    public function setLastActivityFailure(string $lastActivity)
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->expectException(Exception::class);
        $courseUser->setLastActivity($lastActivity);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActive()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setActive(true);
        $this->assertTrue($courseUser->isActive());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setActiveFailure()
    {
        $this->user->setActive(false);
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), null, null, false);
        $this->expectException(Exception::class);
        $courseUser->setActive(true);
    }

    /**
     * @test
     * @dataProvider setDataSuccessProvider
     * @throws Exception
     */
    public function setDataSuccess(array $fieldValues)
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setData($fieldValues);

        $fields = implode(",", array_keys($fieldValues));
        $this->assertEquals($courseUser->getData($fields),
            count($fieldValues) == 1 ? $fieldValues[$fields] : $fieldValues);
    }

    /**
     * @test
     * @dataProvider setDataFailureProvider
     */
    public function setDataFailure(array $fieldValues)
    {
        try {
            $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
            $courseUser->setData($fieldValues);
            $this->fail("Error should have been thrown on 'setDataFailure'");

        } catch (Exception $e) {
            $courseUser = new CourseUser($this->user->getId(), $this->course);
            $this->assertEquals(["id" => $this->user->getId(), "course" => $this->course->getId(), "lastActivity" => null,
                "isActive" => true], $courseUser->getData("id, course, lastActivity, isActive"));
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserById()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals($courseUser, CourseUser::getCourseUserById($courseUser->getId(), $courseUser->getCourse()));
    }

    /**
     * @test
     */
    public function getCourseUserByIdUserDoesntExist()
    {
        $this->assertNull(CourseUser::getCourseUserById(100, $this->course));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsername()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals($courseUser, CourseUser::getCourseUserByUsername($courseUser->getUsername(), $courseUser->getCourse(), AuthService::FENIX));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsernameAuthServiceDoesntExist()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->expectException(Exception::class);
        CourseUser::getCourseUserByUsername($courseUser->getUsername(), $courseUser->getCourse(), "auth_service");
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsernameUserDoesntExist()
    {
        $this->assertNull(CourseUser::getCourseUserByUsername("username", $this->course));
        $this->assertNull(CourseUser::getCourseUserByUsername("username", $this->course, AuthService::FENIX));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsernameMultipleUsersWithAuthService()
    {
        $courseUser1 = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $user2 = User::addUser("Julia Smith Doe", $courseUser1->getUsername(), AuthService::GOOGLE, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        CourseUser::addCourseUser($user2->getId(), $this->course->getId());
        $this->assertEquals($courseUser1, CourseUser::getCourseUserByUsername($courseUser1->getUsername(), $this->course, AuthService::FENIX));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByUsernameMultipleUsersWithoutAuthService()
    {
        $courseUser1 = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $user2 = User::addUser("Julia Smith Doe", $courseUser1->getUsername(), AuthService::GOOGLE, "juliadoe@email.com",
            123, "Julia Doe", "MEIC-A", false, true);
        CourseUser::addCourseUser($user2->getId(), $this->course->getId());
        $this->expectException(Exception::class);
        CourseUser::getCourseUserByUsername($courseUser1->getUsername(), $this->course);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByEmail()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals($courseUser, CourseUser::getCourseUserByEmail($courseUser->getEmail(), $courseUser->getCourse()));
    }

    /**
     * @test
     */
    public function getCourseUserByEmailUserDoesntExist()
    {
        $this->assertNull(CourseUser::getCourseUserByEmail("email", $this->course));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseUserByStudentNumber()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals($courseUser, CourseUser::getCourseUserByStudentNumber($courseUser->getStudentNumber(), $courseUser->getCourse()));
    }

    /**
     * @test
     */
    public function getCourseUserByStudentNumberUserDoesntExist()
    {
        $this->assertNull(CourseUser::getCourseUserByStudentNumber(123, $this->course));
    }


    /**
     * @test
     * @throws Exception
     */
    public function refreshActivity()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->refreshActivity();

        $lastActivity = DateTime::createFromFormat("Y-m-d H:i:s", $courseUser->getLastActivity());
        $this->assertEquals(date("Y-m-d H:i", time()), $lastActivity->format("Y-m-d H:i"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function isTeacher()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), "Teacher");
        $this->assertTrue($courseUser->isTeacher());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotTeacher()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertFalse($courseUser->isTeacher());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isStudent()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), "Student");
        $this->assertTrue($courseUser->isStudent());
    }

    /**
     * @test
     * @throws Exception
     */
    public function isNotStudent()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertFalse($courseUser->isStudent());
    }


    /**
     * @test
     * @dataProvider addCourseUserSuccessProvider
     * @throws Exception
     */
    public function addCourseUserSuccess(int $userId, int $courseId, ?string $roleName, ?int $roleId, ?bool $isActive)
    {
        CourseUser::addCourseUser($userId, $courseId, $roleName, $roleId, $isActive);
        $this->assertCount(2, (new Course($courseId))->getCourseUsers());

        $courseUser = new CourseUser($userId, new Course($courseId));
        $this->assertEquals($userId, $courseUser->getId());
        $this->assertEquals($courseId, $courseUser->getCourse()->getId());
        $this->assertTrue($courseUser->exists());

        if ($roleName) $this->assertTrue($courseUser->hasRole($roleName));
        if ($roleId) $this->assertTrue($courseUser->hasRole(null, $roleId));
    }

    /**
     * @test
     * @dataProvider addCourseUserFailureProvider
     * @throws Exception
     */
    public function addCourseUserFailure(int $userId, int $courseId, ?string $roleName, ?int $roleId, ?bool $isActive)
    {
        $this->expectException(PDOException::class);
        CourseUser::addCourseUser($userId, $courseId, $roleName, $roleId, $isActive);
        $this->assertCount(1, $this->course->getCourseUsers());
        $this->assertFalse((new CourseUser($userId, new Course($courseId)))->exists());
    }

    /**
     * @test
     * @throws Exception
     */
    public function addCourseUserAlreadyInCourse()
    {
        CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(2, $this->course->getCourseUsers());
        $this->assertTrue((new CourseUser($this->user->getId(), $this->course))->exists());

        $this->expectException(PDOException::class);
        CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(2, $this->course->getCourseUsers());
        $this->assertTrue((new CourseUser($this->user->getId(), $this->course))->exists());
    }

    /**
     * @test
     * @throws Exception
     */
    public function addCourseUserInactiveUserActiveInCourse()
    {
        $this->user->setActive(false);
        $this->expectException(Exception::class);
        CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
    }

    /**
     * @test
     * @throws Exception
     */
    public function addCourseUserInactiveUserInactiveInCourse()
    {
        $this->user->setActive(false);
        CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), null, null, false);

        $this->assertCount(2, (new Course($this->course->getId()))->getCourseUsers());

        $courseUser = new CourseUser($this->user->getId(), new Course($this->course->getId()));
        $this->assertEquals($this->user->getId(), $courseUser->getId());
        $this->assertEquals($this->course->getId(), $courseUser->getCourse()->getId());
        $this->assertTrue($courseUser->exists());
    }


    /**
     * @test
     * @throws Exception
     */
    public function deleteCourseUser()
    {
        CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(2, $this->course->getCourseUsers());
        $this->assertTrue((new CourseUser($this->user->getId(), $this->course))->exists());

        CourseUser::deleteCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(1, $this->course->getCourseUsers());
        $this->assertFalse((new CourseUser($this->user->getId(), $this->course))->exists());
    }

    /**
     * @test
     */
    public function deleteCourseUserInexistentCourseUser()
    {
        CourseUser::deleteCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(1, $this->course->getCourseUsers());
        $this->assertFalse((new CourseUser($this->user->getId(), $this->course))->exists());
    }


    /**
     * @test
     * @throws Exception
     */
    public function courseUserExists()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertTrue($courseUser->exists());
    }

    /**
     * @test
     */
    public function courseUserDoesntExist()
    {
        $courseUser = new CourseUser($this->user->getId(), $this->course);
        $this->assertFalse($courseUser->exists());
    }


    /**
     * @test
     * @throws Exception
     */
    public function getRolesNames()
    {
        // Given
        $hierarchy = [
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"],
                    ["name" => "StudentA2"]
                ]],
                ["name" => "StudentB", "children" => [
                    ["name" => "StudentB1"],
                    ["name" => "StudentB2"]
                ]]
            ]],
            ["name" => "Watcher"]
        ];
        $this->course->setRolesHierarchy($hierarchy);
        Role::setCourseRoles($this->course->getId(), [
            "Teacher", "Student", "Watcher",
            "StudentA", "StudentA1", "StudentA2",
            "StudentB", "StudentB1", "StudentB2"
        ]);

        // When
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Student", "StudentA", "StudentA1", "StudentB"]);
        $rolesNames = $courseUser->getRoles();

        // Then
        $this->assertIsArray($rolesNames);
        $this->assertCount(4, $rolesNames);
        $this->assertContains("Student", $rolesNames);
        $this->assertContains("StudentA", $rolesNames);
        $this->assertContains("StudentA1", $rolesNames);
        $this->assertContains("StudentB", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRolesNamesNoRoles()
    {
        // Given
        $hierarchy = [
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"],
                    ["name" => "StudentA2"]
                ]],
                ["name" => "StudentB", "children" => [
                    ["name" => "StudentB1"],
                    ["name" => "StudentB2"]
                ]]
            ]],
            ["name" => "Watcher"]
        ];
        $this->course->setRolesHierarchy($hierarchy);
        Role::setCourseRoles($this->course->getId(), [
            "Teacher", "Student", "Watcher",
            "StudentA", "StudentA1", "StudentA2",
            "StudentB", "StudentB1", "StudentB2"
        ]);

        // When
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $rolesNames = $courseUser->getRoles();

        // Then
        $this->assertIsArray($rolesNames);
        $this->assertCount(0, $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRolesInfo()
    {
        // Given
        $hierarchy = [
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"],
                    ["name" => "StudentA2"]
                ]],
                ["name" => "StudentB", "children" => [
                    ["name" => "StudentB1"],
                    ["name" => "StudentB2"]
                ]]
            ]],
            ["name" => "Watcher"]
        ];
        $this->course->setRolesHierarchy($hierarchy);
        Role::setCourseRoles($this->course->getId(), [
            "Teacher", "Student", "Watcher",
            "StudentA", "StudentA1", "StudentA2",
            "StudentB", "StudentB1", "StudentB2"
        ]);

        // When
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Student", "StudentA", "StudentA1", "StudentB"]);
        $roles = $courseUser->getRoles(false);

        // Then
        $this->assertIsArray($roles);
        $this->assertCount(4, $roles);

        foreach ($roles as $role) {
            $this->assertCount(4, array_keys($role));
            $this->assertArrayHasKey("id", $role);
            $this->assertArrayHasKey("name", $role);
            $this->assertArrayHasKey("landingPage", $role);
            $this->assertArrayHasKey("module", $role);
        }

        $rolesNames = array_column($roles, "name");
        $this->assertContains("Student", $rolesNames);
        $this->assertContains("StudentA", $rolesNames);
        $this->assertContains("StudentA1", $rolesNames);
        $this->assertContains("StudentB", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRolesInfoNoRoles()
    {
        // Given
        $hierarchy = [
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"],
                    ["name" => "StudentA2"]
                ]],
                ["name" => "StudentB", "children" => [
                    ["name" => "StudentB1"],
                    ["name" => "StudentB2"]
                ]]
            ]],
            ["name" => "Watcher"]
        ];
        $this->course->setRolesHierarchy($hierarchy);
        Role::setCourseRoles($this->course->getId(), [
            "Teacher", "Student", "Watcher",
            "StudentA", "StudentA1", "StudentA2",
            "StudentB", "StudentB1", "StudentB2"
        ]);

        // When
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $roles = $courseUser->getRoles(false);

        // Then
        $this->assertIsArray($roles);
        $this->assertCount(0, $roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRolesNamesSortedByMostSpecific()
    {
        // Given
        $hierarchy = [
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"],
                    ["name" => "StudentA2"]
                ]],
                ["name" => "StudentB", "children" => [
                    ["name" => "StudentB1"],
                    ["name" => "StudentB2"]
                ]]
            ]],
            ["name" => "Watcher"]
        ];
        $this->course->setRolesHierarchy($hierarchy);
        Role::setCourseRoles($this->course->getId(), [
            "Teacher", "Student", "Watcher",
            "StudentA", "StudentA1", "StudentA2",
            "StudentB", "StudentB1", "StudentB2"
        ]);

        // When
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Student", "StudentA", "StudentA1", "StudentB"]);
        $rolesNames = $courseUser->getRoles(true, true);

        // Then
        $this->assertIsArray($rolesNames);
        $this->assertCount(4, $rolesNames);
        $this->assertContains("Student", $rolesNames);
        $this->assertContains("StudentA", $rolesNames);
        $this->assertContains("StudentA1", $rolesNames);
        $this->assertContains("StudentB", $rolesNames);
        $this->assertEquals("StudentA1", $rolesNames[0]);
        $this->assertEquals("StudentA", $rolesNames[1]);
        $this->assertEquals("StudentB", $rolesNames[2]);
        $this->assertEquals("Student", $rolesNames[3]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRolesNamesSortedByMostSpecificNoRoles()
    {
        // Given
        $hierarchy = [
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"],
                    ["name" => "StudentA2"]
                ]],
                ["name" => "StudentB", "children" => [
                    ["name" => "StudentB1"],
                    ["name" => "StudentB2"]
                ]]
            ]],
            ["name" => "Watcher"]
        ];
        $this->course->setRolesHierarchy($hierarchy);
        Role::setCourseRoles($this->course->getId(), [
            "Teacher", "Student", "Watcher",
            "StudentA", "StudentA1", "StudentA2",
            "StudentB", "StudentB1", "StudentB2"
        ]);

        // When
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $rolesNames = $courseUser->getRoles(true, true);

        // Then
        $this->assertIsArray($rolesNames);
        $this->assertCount(0, $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRolesHierarchy()
    {
        // Given
        $hierarchy = [
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"],
                    ["name" => "StudentA2"]
                ]],
                ["name" => "StudentB", "children" => [
                    ["name" => "StudentB1"],
                    ["name" => "StudentB2"]
                ]]
            ]],
            ["name" => "Watcher"]
        ];
        $this->course->setRolesHierarchy($hierarchy);
        Role::setCourseRoles($this->course->getId(), [
            "Teacher", "Student", "Watcher",
            "StudentA", "StudentA1", "StudentA2",
            "StudentB", "StudentB1", "StudentB2"
        ]);

        // When
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Student", "StudentA", "StudentA1", "StudentB"]);
        $roles = $courseUser->getRoles(false, true);

        // Then
        $this->assertIsArray($roles);
        $this->assertCount(1, $roles);

        $student = $roles[0];
        $this->assertIsArray($student);
        $this->assertCount(5, array_keys($student));
        $this->assertArrayHasKey("id", $student);
        $this->assertArrayHasKey("name", $student);
        $this->assertArrayHasKey("landingPage", $student);
        $this->assertArrayHasKey("module", $student);
        $this->assertArrayHasKey("children", $student);
        $this->assertEquals("Student", $student["name"]);
        $this->assertCount(2, $student["children"]);

        $studentA = $student["children"][0];
        $this->assertCount(5, array_keys($studentA));
        $this->assertArrayHasKey("id", $studentA);
        $this->assertArrayHasKey("name", $studentA);
        $this->assertArrayHasKey("landingPage", $studentA);
        $this->assertArrayHasKey("module", $studentA);
        $this->assertArrayHasKey("children", $studentA);
        $this->assertEquals("StudentA", $studentA["name"]);
        $this->assertCount(1, $studentA["children"]);

        $studentA1 = $studentA["children"][0];
        $this->assertCount(4, array_keys($studentA1));
        $this->assertArrayHasKey("id", $studentA1);
        $this->assertArrayHasKey("name", $studentA1);
        $this->assertArrayHasKey("landingPage", $studentA1);
        $this->assertArrayHasKey("module", $studentA1);
        $this->assertEquals("StudentA1", $studentA1["name"]);


        $studentB = $student["children"][1];
        $this->assertCount(4, array_keys($studentB));
        $this->assertArrayHasKey("id", $studentB);
        $this->assertArrayHasKey("name", $studentB);
        $this->assertArrayHasKey("landingPage", $studentB);
        $this->assertArrayHasKey("module", $studentB);
        $this->assertEquals("StudentB", $studentB["name"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRolesHierarchyNoRoles()
    {
        // Given
        $hierarchy = [
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"],
                    ["name" => "StudentA2"]
                ]],
                ["name" => "StudentB", "children" => [
                    ["name" => "StudentB1"],
                    ["name" => "StudentB2"]
                ]]
            ]],
            ["name" => "Watcher"]
        ];
        $this->course->setRolesHierarchy($hierarchy);
        Role::setCourseRoles($this->course->getId(), [
            "Teacher", "Student", "Watcher",
            "StudentA", "StudentA1", "StudentA2",
            "StudentB", "StudentB1", "StudentB2"
        ]);

        // When
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $roles = $courseUser->getRoles(false, true);

        // Then
        $this->assertIsArray($roles);
        $this->assertCount(0, $roles);
    }


    /**
     * @test
     * @throws Exception
     */
    public function setRoles()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), "Student");
        $courseUser->setRoles(["Teacher", "Watcher"]);

        $rolesNames = $courseUser->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(2, $rolesNames);
        $this->assertContains("Teacher", $rolesNames);
        $this->assertContains("Watcher", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setRolesNoPreviousRoles()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);

        $rolesNames = $courseUser->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(2, $rolesNames);
        $this->assertContains("Teacher", $rolesNames);
        $this->assertContains("Student", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setRolesEmpty()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), "Student");
        $courseUser->setRoles([]);

        $rolesNames = $courseUser->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(0, $rolesNames);
    }


    /**
     * @test
     * @throws Exception
     */
    public function addRoleByName()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->addRole("Student");

        $rolesNames = $courseUser->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(1, $rolesNames);
        $this->assertContains("Student", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleById()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->addRole(null, Role::getRoleId("Student", $this->course->getId()));

        $rolesNames = $courseUser->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(1, $rolesNames);
        $this->assertContains("Student", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleNoRoleGiven()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->expectException(Exception::class);
        $courseUser->addRole();
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleRolesDoesntExist()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->expectException(PDOException::class);
        $courseUser->addRole("role_doesnt_exist");
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleDuplicateRole()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), "Student");
        $courseUser->addRole("Student");

        $rolesNames = $courseUser->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(1, $rolesNames);
        $this->assertContains("Student", $rolesNames);
    }


    /**
     * @test
     * @throws Exception
     */
    public function removeRoleByName()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $courseUser->removeRole("Student");

        $rolesNames = $courseUser->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(1, $rolesNames);
        $this->assertContains("Teacher", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeRoleById()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $courseUser->removeRole(null, Role::getRoleId("Student", $this->course->getId()));

        $rolesNames = $courseUser->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(1, $rolesNames);
        $this->assertContains("Teacher", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeRoleNoRoleGiven()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);

        $this->expectException(Exception::class);
        $courseUser->removeRole();
    }


    /**
     * @test
     * @throws Exception
     */
    public function hasRoleByName()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $this->assertTrue($courseUser->hasRole("Teacher"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function hasRoleById()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $this->assertTrue($courseUser->hasRole(null, Role::getRoleId("Teacher", $this->course->getId())));
    }

    /**
     * @test
     * @throws Exception
     */
    public function hasRoleNoRoleGiven()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);

        $this->expectException(Exception::class);
        $courseUser->hasRole();
    }

    /**
     * @test
     * @throws Exception
     */
    public function doesntHaveRole()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $this->assertFalse($courseUser->hasRole("Watcher"));
    }


    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersWithHeaderUniqueCourseUsersNoReplace()
    {
        // Given
        $file = "name,email,major,nickname,studentNumber,username,auth_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file, false);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(4, $nrUsersImported);

        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user4 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser1 = ["id" => 3, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "auth_service" => AuthService::FENIX, "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser2 = ["id" => 4, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "auth_service" => AuthService::LINKEDIN, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser3 = ["id" => 5, "name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "major" => "LEIC-T",
            "nickname" => "", "studentNumber" => 84715, "username" => "ist426015",
            "auth_service" => AuthService::GOOGLE, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];
        $expectedUser4 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "auth_service" => AuthService::FACEBOOK, "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];

        $this->assertEquals($expectedUser1, $user1->getData());
        $this->assertEquals($expectedUser2, $user2->getData());
        $this->assertEquals($expectedUser3, $user3->getData());
        $this->assertEquals($expectedUser4, $user4->getData());

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);

        $user2Roles = $user2->getRoles();
        $this->assertIsArray($user2Roles);
        $this->assertCount(1, $user2Roles);
        $this->assertContains("Student", $user2Roles);

        $user3Roles = $user3->getRoles();
        $this->assertIsArray($user3Roles);
        $this->assertCount(1, $user3Roles);
        $this->assertContains("Student", $user3Roles);

        $user4Roles = $user4->getRoles();
        $this->assertIsArray($user4Roles);
        $this->assertCount(2, $user4Roles);
        $this->assertContains("Teacher", $user4Roles);
        $this->assertContains("Student", $user4Roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersWithHeaderNonUniqueCourseUsersNoReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", AuthService::FENIX, "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", false, true);
        CourseUser::addCourseUser($user->getId(), $this->course->getId(), "Teacher", null, false);

        $file = "name,email,major,nickname,studentNumber,username,auth_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file, false);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(3, $nrUsersImported);

        $user0 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser0 = ["id" => 3, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "major" => "MEIC-A",
            "nickname" => "Ana G", "studentNumber" => 84715, "username" => "ist426015",
            "auth_service" => AuthService::FENIX, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];
        $expectedUser1 = ["id" => 4, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "auth_service" => AuthService::FENIX, "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser2 = ["id" => 5, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "auth_service" => AuthService::LINKEDIN, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser3 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "auth_service" => AuthService::FACEBOOK, "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];

        $this->assertEquals($expectedUser0, $user0->getData());
        $this->assertEquals($expectedUser1, $user1->getData());
        $this->assertEquals($expectedUser2, $user2->getData());
        $this->assertEquals($expectedUser3, $user3->getData());

        $user0Roles = $user0->getRoles();
        $this->assertIsArray($user0Roles);
        $this->assertCount(1, $user0Roles);
        $this->assertContains("Teacher", $user0Roles);

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);

        $user2Roles = $user2->getRoles();
        $this->assertIsArray($user2Roles);
        $this->assertCount(1, $user2Roles);
        $this->assertContains("Student", $user2Roles);

        $user3Roles = $user3->getRoles();
        $this->assertIsArray($user3Roles);
        $this->assertCount(2, $user3Roles);
        $this->assertContains("Teacher", $user3Roles);
        $this->assertContains("Student", $user3Roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersWithHeaderNonUniqueCourseUsersReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", AuthService::FENIX, "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);
        CourseUser::addCourseUser($user->getId(), $this->course->getId(), "Teacher", null, false);

        $file = "name,email,major,nickname,studentNumber,username,auth_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(3, $nrUsersImported);

        $user0 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser0 = ["id" => 3, "name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "major" => "LEIC-T",
            "nickname" => "", "studentNumber" => 84715, "username" => "ist426015",
            "auth_service" => AuthService::GOOGLE, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];
        $expectedUser1 = ["id" => 4, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "auth_service" => AuthService::FENIX, "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser2 = ["id" => 5, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "auth_service" => AuthService::LINKEDIN, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser3 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "auth_service" => AuthService::FACEBOOK, "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];

        $this->assertEquals($expectedUser0, $user0->getData());
        $this->assertEquals($expectedUser1, $user1->getData());
        $this->assertEquals($expectedUser2, $user2->getData());
        $this->assertEquals($expectedUser3, $user3->getData());

        $user0Roles = $user0->getRoles();
        $this->assertIsArray($user0Roles);
        $this->assertCount(1, $user0Roles);
        $this->assertContains("Student", $user0Roles);

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);

        $user2Roles = $user2->getRoles();
        $this->assertIsArray($user2Roles);
        $this->assertCount(1, $user2Roles);
        $this->assertContains("Student", $user2Roles);

        $user3Roles = $user3->getRoles();
        $this->assertIsArray($user3Roles);
        $this->assertCount(2, $user3Roles);
        $this->assertContains("Teacher", $user3Roles);
        $this->assertContains("Student", $user3Roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersWithNoHeaderUniqueCourseUsersReplace()
    {
        // Given
        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(4, $nrUsersImported);

        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user4 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser1 = ["id" => 3, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "auth_service" => AuthService::FENIX, "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser2 = ["id" => 4, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "auth_service" => AuthService::LINKEDIN, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser3 = ["id" => 5, "name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "major" => "LEIC-T",
            "nickname" => "", "studentNumber" => 84715, "username" => "ist426015",
            "auth_service" => AuthService::GOOGLE, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];
        $expectedUser4 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "auth_service" => AuthService::FACEBOOK, "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];

        $this->assertEquals($expectedUser1, $user1->getData());
        $this->assertEquals($expectedUser2, $user2->getData());
        $this->assertEquals($expectedUser3, $user3->getData());
        $this->assertEquals($expectedUser4, $user4->getData());

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);

        $user2Roles = $user2->getRoles();
        $this->assertIsArray($user2Roles);
        $this->assertCount(1, $user2Roles);
        $this->assertContains("Student", $user2Roles);

        $user3Roles = $user3->getRoles();
        $this->assertIsArray($user3Roles);
        $this->assertCount(1, $user3Roles);
        $this->assertContains("Student", $user3Roles);

        $user4Roles = $user4->getRoles();
        $this->assertIsArray($user4Roles);
        $this->assertCount(2, $user4Roles);
        $this->assertContains("Teacher", $user4Roles);
        $this->assertContains("Student", $user4Roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersWithNoHeaderNonUniqueCourseUsersReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", AuthService::FENIX, "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);
        CourseUser::addCourseUser($user->getId(), $this->course->getId(), "Teacher", null, false);

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(3, $nrUsersImported);

        $user0 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser0 = ["id" => 3, "name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "major" => "LEIC-T",
            "nickname" => "", "studentNumber" => 84715, "username" => "ist426015",
            "auth_service" => AuthService::GOOGLE, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];
        $expectedUser1 = ["id" => 4, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "auth_service" => AuthService::FENIX, "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser2 = ["id" => 5, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "auth_service" => AuthService::LINKEDIN, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser3 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "auth_service" => AuthService::FACEBOOK, "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];

        $this->assertEquals($expectedUser0, $user0->getData());
        $this->assertEquals($expectedUser1, $user1->getData());
        $this->assertEquals($expectedUser2, $user2->getData());
        $this->assertEquals($expectedUser3, $user3->getData());

        $user0Roles = $user0->getRoles();
        $this->assertIsArray($user0Roles);
        $this->assertCount(1, $user0Roles);
        $this->assertContains("Student", $user0Roles);

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);

        $user2Roles = $user2->getRoles();
        $this->assertIsArray($user2Roles);
        $this->assertCount(1, $user2Roles);
        $this->assertContains("Student", $user2Roles);

        $user3Roles = $user3->getRoles();
        $this->assertIsArray($user3Roles);
        $this->assertCount(2, $user3Roles);
        $this->assertContains("Teacher", $user3Roles);
        $this->assertContains("Student", $user3Roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersWithNoHeaderNonUniqueCourseUsersNoReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", AuthService::FENIX, "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", false, true);
        CourseUser::addCourseUser($user->getId(), $this->course->getId(), "Teacher", null, false);

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file, false);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(3, $nrUsersImported);

        $user0 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser0 = ["id" => 3, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "major" => "MEIC-A",
            "nickname" => "Ana G", "studentNumber" => 84715, "username" => "ist426015",
            "auth_service" => AuthService::FENIX, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];
        $expectedUser1 = ["id" => 4, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "auth_service" => AuthService::FENIX, "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser2 = ["id" => 5, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "auth_service" => AuthService::LINKEDIN, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $expectedUser3 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "auth_service" => AuthService::FACEBOOK, "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];

        $this->assertEquals($expectedUser0, $user0->getData());
        $this->assertEquals($expectedUser1, $user1->getData());
        $this->assertEquals($expectedUser2, $user2->getData());
        $this->assertEquals($expectedUser3, $user3->getData());

        $user0Roles = $user0->getRoles();
        $this->assertIsArray($user0Roles);
        $this->assertCount(1, $user0Roles);
        $this->assertContains("Teacher", $user0Roles);

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);

        $user2Roles = $user2->getRoles();
        $this->assertIsArray($user2Roles);
        $this->assertCount(1, $user2Roles);
        $this->assertContains("Student", $user2Roles);

        $user3Roles = $user3->getRoles();
        $this->assertIsArray($user3Roles);
        $this->assertCount(2, $user3Roles);
        $this->assertContains("Teacher", $user3Roles);
        $this->assertContains("Student", $user3Roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersIsAlreadyUserOfSystemNoReplace()
    {
        // Given
        User::addUser("Ana Rita Gonçalves", "ist426015", AuthService::FENIX, "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", false, true);

        $file = "Ana Rita Gonçalves,ana.goncalves@hotmail.com,MEIC-A,Ana G,84715,ist426015,fenix,1,1,0,Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file, false);

        // Then
        $users = User::getUsers();
        $this->assertCount(3, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(2, $courseUsers);
        $this->assertEquals(1, $nrUsersImported);

        $user1 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $expectedUser1 = ["id" => 3, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "major" => "MEIC-A",
            "nickname" => "Ana G", "studentNumber" => 84715, "username" => "ist426015",
            "auth_service" => AuthService::FENIX, "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => false, "lastLogin" => null];
        $this->assertEquals($expectedUser1, $user1->getData());

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersIsAlreadyUserOfSystemReplace()
    {
        // Given
        User::addUser("Ana Rita Gonçalves", "ist426015", AuthService::FENIX, "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);

        $file = "Ana Rita Gonçalves,ana.goncalves@hotmail.com,MEIC-A,Ana G,84715,ist426015,fenix,1,1,1,Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        // Then
        $users = User::getUsers();
        $this->assertCount(3, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(2, $courseUsers);
        $this->assertEquals(1, $nrUsersImported);

        $user1 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $expectedUser1 = ["id" => 3, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "major" => "MEIC-A",
            "nickname" => "Ana G", "studentNumber" => 84715, "username" => "ist426015",
            "auth_service" => AuthService::FENIX, "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "isActiveInCourse" => true, "lastLogin" => null];
        $this->assertEquals($expectedUser1, $user1->getData());

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersEmptyFileNoHeaderNoCourseUsers()
    {
        $file = "";
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        $users = User::getUsers();
        $this->assertCount(2, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(1, $courseUsers);
        $this->assertEquals(0, $nrUsersImported);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersEmptyFileNoHeaderWithCourseUsers()
    {
        $user1 = User::addUser("Ana Gonçalves", "ist100000", AuthService::FENIX, "ana.goncalves@gmail.com",
            10000, "Ana G", "MEIC-A", true, false);
        $user2 = User::addUser("João Carlos Sousa", "ist1234567", AuthService::FENIX, "joao@gmail.com",
            1234567, "João Sousa", "MEIC-A", false, true);
        $user3 = User::addUser("Sabri M'Barki", "ist1100956", AuthService::FENIX, "sabri.m.barki@efrei.net",
            100956, "Sabri M'Barki", "MEIC-T", true, true);

        CourseUser::addCourseUser($user1->getId(), $this->course->getId(), "Teacher", null, false);
        CourseUser::addCourseUser($user2->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user3->getId(), $this->course->getId(), "Student");

        $file = "";
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        $users = User::getUsers();
        $this->assertCount(5, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(4, $courseUsers);
        $this->assertEquals(0, $nrUsersImported);
    }

    /**
     * @test
     * @throws Exception
     */
    public function importCourseUsersEmptyFileWithHeaderWithCourseUsers()
    {
        $user1 = User::addUser("Ana Gonçalves", "ist100000", AuthService::FENIX, "ana.goncalves@gmail.com",
            10000, "Ana G", "MEIC-A", true, false);
        $user2 = User::addUser("João Carlos Sousa", "ist1234567", AuthService::FENIX, "joao@gmail.com",
            1234567, "João Sousa", "MEIC-A", false, true);
        $user3 = User::addUser("Sabri M'Barki", "ist1100956", AuthService::FENIX, "sabri.m.barki@efrei.net",
            100956, "Sabri M'Barki", "MEIC-T", true, true);

        CourseUser::addCourseUser($user1->getId(), $this->course->getId(), "Teacher", null, false);
        CourseUser::addCourseUser($user2->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user3->getId(), $this->course->getId(), "Student");

        $file = "name,email,major,nickname,studentNumber,username,auth_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        $users = User::getUsers();
        $this->assertCount(5, $users);

        $courseUsers = $this->course->getCourseUsers();
        $this->assertCount(4, $courseUsers);
        $this->assertEquals(0, $nrUsersImported);
    }

    /**
     * @test
     */
    public function importCourseUsersCourseDoesntExist()
    {
        $this->expectException(Exception::class);
        CourseUser::importCourseUsers(10, "");
    }


    /**
     * @test
     * @throws Exception
     */
    public function exportCourseUsers()
    {
        $user1 = User::addUser("Sabri M'Barki", "ist1100956", AuthService::FENIX, "sabri.m.barki@efrei.net",
            100956, "Sabri M'Barki", "MEIC-T", true, true);
        $user2 = User::addUser("Marcus Notø", "ist1101036", AuthService::FENIX, "marcus.n.hansen@gmail.com",
            1101036, "Marcus Notø", "MEEC", true, false);
        $user3 = User::addUser("Inês Albano", "ist187664", AuthService::FENIX, "ines.albano@tecnico.ulisboa.pt",
            87664, null, "MEIC-A", false, true);
        $user4 = User::addUser("Filipe José Zillo Colaço", "ist426015", AuthService::FENIX, "fijozico@hotmail.com",
            84715, null, "LEIC-T", false, true);
        $user5 = User::addUser("Mariana Wong Brandão", "ist186893", AuthService::FENIX, "marianawbrandao@icloud.com",
            86893, "Mariana Brandão", "MEMec", false, false);

        CourseUser::addCourseUser($user1->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user2->getId(), $this->course->getId(), "Student", null, false);
        CourseUser::addCourseUser($user3->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user4->getId(), $this->course->getId(), "Teacher");
        $courseUser = CourseUser::addCourseUser($user5->getId(), $this->course->getId(), null, null, false);
        $courseUser->setRoles(["Teacher", "Student"]);
        $courseUser->setActive(false);

        $expectedFile = "name,email,major,nickname,studentNumber,username,auth_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $expectedFile .= "John Smith Doe,johndoe@email.com,MEIC-A,John Doe,123456,ist123456,fenix,1,1,1,Teacher\n";
        $expectedFile .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $expectedFile .= "Marcus Notø,marcus.n.hansen@gmail.com,MEEC,Marcus Notø,1101036,ist1101036,fenix,1,0,0,Student\n";
        $expectedFile .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,fenix,0,1,1,Student\n";
        $expectedFile .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,fenix,0,1,1,Teacher\n";
        $expectedFile .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,fenix,0,0,0,Teacher Student";

        $file = CourseUser::exportCourseUsers($this->course->getId());
        $this->assertEquals($expectedFile, $file);
    }

    /**
     * @test
     */
    public function exportCourseUsersCourseDoesntExist()
    {
        $this->expectException(Exception::class);
        CourseUser::exportCourseUsers(10);
    }
}
