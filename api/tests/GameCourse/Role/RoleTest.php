<?php
namespace GameCourse\Role;

use Exception;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\CreationMode;
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
class RoleTest extends TestCase
{
    private $course;
    private $courseUser;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass(["modules", "views"], ["CronJob"]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        // Setup default roles
        Role::setupRoles();

        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", AuthService::FENIX, "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $course->setRolesHierarchy([
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA"],
                ["name" => "StudentB"]
            ]],
            ["name" => "Watcher"]
        ]);
        $course->addRole("StudentA");
        $course->addRole("StudentB");
        $this->course = $course;

        // Set a course user
        $user = User::addUser("Johanna Smith Doe", "ist654321", AuthService::FENIX, "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $courseUser = $course->addUserToCourse($user->getId(), "Student");
        $courseUser->addRole("StudentA");
        $this->courseUser = $courseUser;
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER, Role::TABLE_ROLE, ViewHandler::TABLE_VIEW]);
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
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     * @throws Exception
     */
    public function setupRoles()
    {
        // Given
        Core::database()->delete(Role::TABLE_ROLE, ["course" => 0]);

        // When
        Role::setupRoles();

        // Then
        $defaultRoles = Role::getCourseRoles(0, false);
        $this->assertSameSize(Role::DEFAULT_ROLES, $defaultRoles);
        foreach ($defaultRoles as $role) {
            $this->assertContains($role["name"], Role::DEFAULT_ROLES);
            $this->assertNull($role["landingPage"]);
            $this->assertNull($role["module"]);
        }

        $defaultAspects = Aspect::getAspects(0);
        $this->assertCount((count(Role::DEFAULT_ROLES) + 1) ** 2, $defaultAspects);
    }


    /**
     * @test
     */
    public function getRoleId()
    {
        $this->assertEquals(1, Role::getRoleId(Role::DEFAULT_ROLES[0], 0));
        $this->assertEquals(2, Role::getRoleId(Role::DEFAULT_ROLES[1], 0));
        $this->assertEquals(3, Role::getRoleId(Role::DEFAULT_ROLES[2], 0));
    }

    /**
     * @test
     */
    public function getRoleIdRoleDoesntExist()
    {
        $this->expectException(Exception::class);
        Role::getRoleId("role_doesnt_exist", $this->course->getId());
    }


    /**
     * @test
     */
    public function getRoleName()
    {
        $this->assertEquals("Teacher", Role::getRoleName(1));
        $this->assertEquals("Student", Role::getRoleName(2));
        $this->assertEquals("Watcher", Role::getRoleName(3));
    }

    /**
     * @test
     */
    public function getRoleNameRoleDoesntExist()
    {
        $this->expectException(Exception::class);
        Role::getRoleName(100);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getRoleLandingPage()
    {
        // Given
        $roleId = Role::getRoleId("Teacher", $this->course->getId());
        $page = Page::addPage($this->course->getId(), CreationMode::BY_VALUE, "Landing Page");
        Core::database()->update(Role::TABLE_ROLE, ["landingPage" => $page->getId()], ["id" => $roleId]);

        // Then
        $this->assertEquals($page, Role::getRoleLandingPage($roleId));
    }

    /**
     * @test
     */
    public function getRoleLandingPageNull()
    {
        $roleId = Role::getRoleId("Teacher", $this->course->getId());
        $this->assertNull(Role::getRoleLandingPage($roleId));
    }


    /**
     * @test
     */
    public function getRolesNamesInHierarchy()
    {
        $hierarchy = $this->course->getRolesHierarchy();
        $rolesNames = Role::getRolesNamesInHierarchy($hierarchy);
        $this->assertIsArray($rolesNames);
        $this->assertCount(5, $rolesNames);
        foreach (["Teacher", "StudentA", "StudentB", "Student", "Watcher"] as $roleName) {
            $this->assertContains($roleName, $rolesNames);
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function addDefaultRolesToCourse()
    {
        // Given
        $this->course->setRolesHierarchy([]);
        $this->course->setRoles([]);
        Core::database()->resetAutoIncrement(Role::TABLE_ROLE);

        // When
        Role::addDefaultRolesToCourse($this->course->getId());

        // Then
        $rolesNames = $this->course->getRoles();
        $this->assertIsArray($rolesNames);
        $this->assertCount(count(Role::DEFAULT_ROLES), $rolesNames);
        foreach ($rolesNames as $i => $roleName) {
            $this->assertEquals(Role::DEFAULT_ROLES[$i], $roleName);
        }

        $hierarchy = $this->course->getRolesHierarchy();
        $this->assertEquals([
            ["name" => Role::DEFAULT_ROLES[0]],
            ["name" => Role::DEFAULT_ROLES[1]],
            ["name" => Role::DEFAULT_ROLES[2]]
        ], $hierarchy);
    }


    /**
     * @test
     */
    public function getCourseRolesNames()
    {
        $rolesNames = Role::getCourseRoles($this->course->getId());
        $this->assertIsArray($rolesNames);
        $this->assertCount(5, $rolesNames);
        foreach (["Teacher", "StudentA", "StudentB", "Student", "Watcher"] as $roleName) {
            $this->assertContains($roleName, $rolesNames);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseRolesNamesNoRoles()
    {
        // Given
        $this->course->setRolesHierarchy([]);
        $this->course->setRoles([]);

        // When
        $rolesNames = Role::getCourseRoles($this->course->getId());

        // Then
        $this->assertIsArray($rolesNames);
        $this->assertEmpty($rolesNames);
    }

    /**
     * @test
     */
    public function getCourseRolesInfo()
    {
        $roles = Role::getCourseRoles($this->course->getId(), false);
        $this->assertIsArray($roles);
        $this->assertCount(5, $roles);
        foreach ($roles as $role) {
            $this->assertCount(4, array_keys($role));
            $this->assertArrayHasKey("id", $role);
            $this->assertArrayHasKey("name", $role);
            $this->assertArrayHasKey("landingPage", $role);
            $this->assertArrayHasKey("module", $role);
            $this->assertContains($role["name"], ["Teacher", "StudentA", "StudentB", "Student", "Watcher"]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseRolesInfoNoRoles()
    {
        // Given
        $this->course->setRolesHierarchy([]);
        $this->course->setRoles([]);

        // When
        $roles = Role::getCourseRoles($this->course->getId(), false);

        // Then
        $this->assertIsArray($roles);
        $this->assertEmpty($roles);
    }

    /**
     * @test
     */
    public function getCourseRolesNamesSortedByMostSpecific()
    {
        $rolesNames = Role::getCourseRoles($this->course->getId(), true, true);
        $this->assertIsArray($rolesNames);
        $this->assertEquals(["Teacher", "StudentA", "StudentB", "Student", "Watcher"], $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseRolesNamesSortedByMostSpecificNoRoles()
    {
        // Given
        $this->course->setRolesHierarchy([]);
        $this->course->setRoles([]);

        // When
        $rolesNames = Role::getCourseRoles($this->course->getId(), true, true);

        // Then
        $this->assertIsArray($rolesNames);
        $this->assertEmpty($rolesNames);
    }

    /**
     * @test
     */
    public function getCourseRolesHierarchy()
    {
        $hierarchy = Role::getCourseRoles($this->course->getId(), false, true);
        $this->assertCount(3, $hierarchy);

        $teacherRole = $hierarchy[0];
        $this->assertIsArray($teacherRole);
        $this->assertCount(4, array_keys($teacherRole));
        $this->assertArrayHasKey("id", $teacherRole);
        $this->assertArrayHasKey("name", $teacherRole);
        $this->assertArrayHasKey("landingPage", $teacherRole);
        $this->assertArrayHasKey("module", $teacherRole);
        $this->assertEquals("Teacher", $teacherRole["name"]);

        $studentRole = $hierarchy[1];
        $this->assertIsArray($studentRole);
        $this->assertCount(5, array_keys($studentRole));
        $this->assertArrayHasKey("id", $studentRole);
        $this->assertArrayHasKey("name", $studentRole);
        $this->assertArrayHasKey("landingPage", $studentRole);
        $this->assertArrayHasKey("module", $studentRole);
        $this->assertArrayHasKey("children", $studentRole);
        $this->assertEquals("Student", $studentRole["name"]);
        $this->assertCount(2, $studentRole["children"]);

        $studentARole = $studentRole["children"][0];
        $this->assertIsArray($studentARole);
        $this->assertCount(4, array_keys($studentARole));
        $this->assertArrayHasKey("id", $studentARole);
        $this->assertArrayHasKey("name", $studentARole);
        $this->assertArrayHasKey("landingPage", $studentARole);
        $this->assertArrayHasKey("module", $studentARole);
        $this->assertEquals("StudentA", $studentARole["name"]);

        $studentBRole = $studentRole["children"][1];
        $this->assertIsArray($studentBRole);
        $this->assertCount(4, array_keys($studentBRole));
        $this->assertArrayHasKey("id", $studentBRole);
        $this->assertArrayHasKey("name", $studentBRole);
        $this->assertArrayHasKey("landingPage", $studentBRole);
        $this->assertArrayHasKey("module", $studentBRole);
        $this->assertEquals("StudentB", $studentBRole["name"]);

        $watcherRole = $hierarchy[2];
        $this->assertIsArray($watcherRole);
        $this->assertCount(4, array_keys($watcherRole));
        $this->assertArrayHasKey("id", $watcherRole);
        $this->assertArrayHasKey("name", $watcherRole);
        $this->assertArrayHasKey("landingPage", $watcherRole);
        $this->assertArrayHasKey("module", $watcherRole);
        $this->assertEquals("Watcher", $watcherRole["name"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCourseRolesHierarchyNoRoles()
    {
        // Given
        $this->course->setRolesHierarchy([]);
        $this->course->setRoles([]);

        // When
        $hierarchy = Role::getCourseRoles($this->course->getId(), false, true);

        // Then
        $this->assertIsArray($hierarchy);
        $this->assertEmpty($hierarchy);
    }


    /**
     * @test
     * @throws Exception
     */
    public function setCourseRolesByNames()
    {
        Role::setCourseRoles($this->course->getId(), ["NewRole1", "NewRole2"]);
        $rolesNames = Role::getCourseRoles($this->course->getId());
        $this->assertIsArray($rolesNames);
        $this->assertCount(2, $rolesNames);
        $this->assertContains("NewRole1", $rolesNames);
        $this->assertContains("NewRole2", $rolesNames);

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((2 + 1) ** 2, $aspects);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCourseRolesByHierarchy()
    {
        Role::setCourseRoles($this->course->getId(), null, [
            ["name" => "NewRole1"],
            ["name" => "NewRole2"]
        ]);
        $rolesNames = Role::getCourseRoles($this->course->getId());
        $this->assertIsArray($rolesNames);
        $this->assertCount(2, $rolesNames);
        $this->assertContains("NewRole1", $rolesNames);
        $this->assertContains("NewRole2", $rolesNames);

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((2 + 1) ** 2, $aspects);
    }

    /**
     * @test
     */
    public function setCourseRolesFailure()
    {
        $this->expectException(Exception::class);
        Role::setCourseRoles($this->course->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function addRoleToCourse()
    {
        Role::addRoleToCourse($this->course->getId(), "NewRole");
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(6, $roles);
        $this->assertContains("NewRole", $roles);

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((6 + 1) ** 2, $aspects);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleToCourseRoleAlreadyExists()
    {
        Role::addRoleToCourse($this->course->getId(), "NewRole");
        Role::addRoleToCourse($this->course->getId(), "NewRole");
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(6, $roles);
        $this->assertContains("NewRole", $roles);

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((6 + 1) ** 2, $aspects);
    }

    /**
     * @test
     */
    public function addRoleToCourseRoleInvalidRoleName()
    {
        try {
            Role::addRoleToCourse($this->course->getId(), "New Role");
            $this->fail("Error should have been thrown in 'addRoleToCourseRoleInvalidRoleName'");

        } catch (Exception $e) {
            $roles = Role::getCourseRoles($this->course->getId());
            $this->assertCount(5, $roles);
            $this->assertNotContains("NewRole", $roles);

            $aspects = Aspect::getAspects($this->course->getId());
            $this->assertIsArray($aspects);
            $this->assertCount((5 + 1) ** 2, $aspects);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleToCourseWithLandingPageName()
    {
        $page = Page::addPage($this->course->getId(), CreationMode::BY_VALUE, "Landing Page");
        Role::addRoleToCourse($this->course->getId(), "NewRole", "Landing Page");
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(6, $roles);
        $this->assertContains("NewRole", $roles);
        $this->assertEquals($page, Role::getRoleLandingPage(Role::getRoleId("NewRole", $this->course->getId())));

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((6 + 1) ** 2, $aspects);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleToCourseWithLandingPageId()
    {
        $page = Page::addPage($this->course->getId(), CreationMode::BY_VALUE, "Landing Page");
        Role::addRoleToCourse($this->course->getId(), "NewRole", null, $page->getId());
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(6, $roles);
        $this->assertContains("NewRole", $roles);
        $this->assertEquals($page, Role::getRoleLandingPage(Role::getRoleId("NewRole", $this->course->getId())));

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((6 + 1) ** 2, $aspects);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleToCourseModuleRole()
    {
        Role::addRoleToCourse($this->course->getId(), "NewModuleRole", null, null, XPLevels::ID);
        $roles = Role::getCourseRoles($this->course->getId(), false);
        $this->assertCount(6, $roles);

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((6 + 1) ** 2, $aspects);

        foreach ($roles as $role) {
            if ($role["name"] == "NewModuleRole") {
                $this->assertEquals(XPLevels::ID, $role["module"]);
                return;
            }
        }
        $this->fail("Role 'NewModuleRole' was not added to course.");
    }


    /**
     * @test
     * @throws Exception
     */
    public function updateCourseRoles()
    {
        // Given
        $studentRoleId = Role::getRoleId("Student", $this->course->getId());
        $studentARoleId = Role::getRoleId("StudentA", $this->course->getId());
        $roles = [
            ["id" => $studentARoleId, "name" => "StudentAAA", "landingPage" => null],
            ["id" => $studentRoleId, "name" => "Student"],
            ["name" => "NewRole"]
        ];

        // When
        Role::updateCourseRoles($this->course->getId(), $roles);

        // Then
        $roles = Role::getCourseRoles($this->course->getId(), false);
        $this->assertIsArray($roles);
        $this->assertCount(3, $roles);
        foreach ($roles as $role) {
            if ($role["id"] === $studentRoleId) $this->assertEquals("Student", $role["name"]);
            else if ($role["id"] === $studentARoleId) $this->assertEquals("StudentAAA", $role["name"]);
            else $this->assertEquals("NewRole", $role["name"]);
            $this->assertNull($role["landingPage"]);
        }

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((3 + 1) ** 2, $aspects);
    }

    /**
     * @test
     */
    public function updateCourseRolesFailure()
    {
        $roles = [["name" => "New Role"]];
        $this->expectException(Exception::class);
        Role::updateCourseRoles($this->course->getId(), $roles);
    }


    /**
     * @test
     * @throws Exception
     */
    public function removeRoleFromCourseByRoleName()
    {
        Role::removeRoleFromCourse($this->course->getId(), "Student");
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(2, $roles);
        $this->assertContains("Teacher", $roles);
        $this->assertContains("Watcher", $roles);
        $this->assertNotContains("Student", $roles);
        $this->assertNotContains("StudentA", $roles);
        $this->assertNotContains("StudentB", $roles);

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((2 + 1) ** 2, $aspects);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeRoleFromCourseByRoleId()
    {
        Role::removeRoleFromCourse($this->course->getId(), null, Role::getRoleId("Student", $this->course->getId()));
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(2, $roles);
        $this->assertContains("Teacher", $roles);
        $this->assertContains("Watcher", $roles);
        $this->assertNotContains("Student", $roles);
        $this->assertNotContains("StudentA", $roles);
        $this->assertNotContains("StudentB", $roles);

        $aspects = Aspect::getAspects($this->course->getId());
        $this->assertIsArray($aspects);
        $this->assertCount((2 + 1) ** 2, $aspects);
    }

    /**
     * @test
     */
    public function removeRoleFromCourseFailure()
    {
        $this->expectException(Exception::class);
        Role::removeRoleFromCourse($this->course->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function courseHasRoleByName()
    {
        $this->assertTrue(Role::courseHasRole($this->course->getId(), "Student"));
        $this->assertTrue(Role::courseHasRole($this->course->getId(), "StudentA"));
        $this->assertFalse(Role::courseHasRole($this->course->getId(), "role_doesnt_exist"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function courseHasRoleById()
    {
        $this->assertTrue(Role::courseHasRole(0, null, 2));
        $this->assertTrue(Role::courseHasRole($this->course->getId(), null, 4));
        $this->assertFalse(Role::courseHasRole($this->course->getId(), null, 100));
    }

    /**
     * @test
     */
    public function courseHasRoleFailure()
    {
        $this->expectException(Exception::class);
        Role::courseHasRole($this->course->getId());
    }


    /**
     * @test
     */
    public function getUserRolesNames()
    {
        $rolesNames = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertIsArray($rolesNames);
        $this->assertCount(2, $rolesNames);
        $this->assertContains("Student", $rolesNames);
        $this->assertContains("StudentA", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserRolesNamesNoRoles()
    {
        // Given
        $this->courseUser->setRoles([]);

        // When
        $rolesNames = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());

        // Then
        $this->assertIsArray($rolesNames);
        $this->assertEmpty($rolesNames);
    }

    /**
     * @test
     */
    public function getUserRolesInfo()
    {
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId(), false);
        $this->assertIsArray($roles);
        $this->assertCount(2, $roles);
        foreach ($roles as $role) {
            $this->assertCount(4, array_keys($role));
            $this->assertArrayHasKey("id", $role);
            $this->assertArrayHasKey("name", $role);
            $this->assertArrayHasKey("landingPage", $role);
            $this->assertArrayHasKey("module", $role);
            $this->assertContains($role["name"], ["StudentA", "Student"]);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserRolesInfoNoRoles()
    {
        // Given
        $this->courseUser->setRoles([]);

        // When
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId(), false);

        // Then
        $this->assertIsArray($roles);
        $this->assertEmpty($roles);
    }

    /**
     * @test
     */
    public function getUserRolesNamesSortedByMostSpecific()
    {
        $rolesNames = Role::getUserRoles($this->courseUser->getId(), $this->course->getId(), true, true);
        $this->assertIsArray($rolesNames);
        $this->assertEquals(["StudentA", "Student"], $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserRolesNamesSortedByMostSpecificNoRoles()
    {
        // Given
        $this->courseUser->setRoles([]);

        // When
        $rolesNames = Role::getUserRoles($this->courseUser->getId(), $this->course->getId(), true, true);

        // Then
        $this->assertIsArray($rolesNames);
        $this->assertEmpty($rolesNames);
    }

    /**
     * @test
     */
    public function getUserRolesHierarchy()
    {
        $hierarchy = Role::getUserRoles($this->courseUser->getId(), $this->course->getId(), false, true);
        $this->assertCount(1, $hierarchy);

        $studentRole = $hierarchy[0];
        $this->assertIsArray($studentRole);
        $this->assertCount(5, array_keys($studentRole));
        $this->assertArrayHasKey("id", $studentRole);
        $this->assertArrayHasKey("name", $studentRole);
        $this->assertArrayHasKey("landingPage", $studentRole);
        $this->assertArrayHasKey("module", $studentRole);
        $this->assertArrayHasKey("children", $studentRole);
        $this->assertEquals("Student", $studentRole["name"]);
        $this->assertCount(1, $studentRole["children"]);

        $studentARole = $studentRole["children"][0];
        $this->assertIsArray($studentARole);
        $this->assertCount(4, array_keys($studentARole));
        $this->assertArrayHasKey("id", $studentARole);
        $this->assertArrayHasKey("name", $studentARole);
        $this->assertArrayHasKey("landingPage", $studentARole);
        $this->assertArrayHasKey("module", $studentARole);
        $this->assertEquals("StudentA", $studentARole["name"]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getUserRolesHierarchyNoRoles()
    {
        // Given
        $this->courseUser->setRoles([]);

        // When
        $hierarchy = Role::getUserRoles($this->courseUser->getId(), $this->course->getId(), false, true);

        // Then
        $this->assertIsArray($hierarchy);
        $this->assertEmpty($hierarchy);
    }


    /**
     * @test
     * @throws Exception
     */
    public function setUserRolesByNames()
    {
        Role::setUserRoles($this->courseUser->getId(), $this->course->getId(), ["Student", "StudentB"]);
        $rolesNames = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertIsArray($rolesNames);
        $this->assertCount(2, $rolesNames);
        $this->assertContains("Student", $rolesNames);
        $this->assertContains("StudentB", $rolesNames);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setUserRolesByNamesRolesNotInCourse()
    {
        try {
            Role::setUserRoles($this->courseUser->getId(), $this->course->getId(), ["Student", "StudentC"]);
            $this->fail("Exception should have been thrown in 'setUserRolesByNamesRolesDontExist'");

        } catch (Exception $e) {
            $rolesNames = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
            $this->assertIsArray($rolesNames);
            $this->assertCount(2, $rolesNames);
            $this->assertContains("Student", $rolesNames);
            $this->assertContains("StudentA", $rolesNames);
        }
    }


    /**
     * @test
     * @throws Exception
     */
    public function addRoleToUserByName()
    {
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId(), "StudentB");
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertCount(3, $roles);
        $this->assertContains("StudentB", $roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleToUserById()
    {
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId(), null, 8);
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertCount(3, $roles);
        $this->assertContains("StudentB", $roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleToUserRoleAlreadyExists()
    {
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId(), "StudentB");
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId(), "StudentB");
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertCount(3, $roles);
        $this->assertContains("StudentB", $roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function addRoleToUserRoleNotInCourse()
    {
        $this->expectException(Exception::class);
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId(), "NewRole");
    }

    /**
     * @test
     */
    public function addRoleToUserFailure()
    {
        $this->expectException(Exception::class);
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function removeRoleFromUserByRoleName()
    {
        Role::removeRoleFromUser($this->courseUser->getId(), $this->course->getId(), "Student");
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertIsArray($roles);
        $this->assertEmpty($roles);
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeRoleFromUserByRoleId()
    {
        Role::removeRoleFromUser($this->courseUser->getId(), $this->course->getId(), null, 7);
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertCount(1, $roles);
        $this->assertContains("Student", $roles);
    }

    /**
     * @test
     */
    public function removeRoleFromUserFailure()
    {
        $this->expectException(Exception::class);
        Role::removeRoleFromUser($this->courseUser->getId(), $this->course->getId());
    }


    /**
     * @test
     * @throws Exception
     */
    public function userHasRoleByName()
    {
        $this->assertTrue(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), "Student"));
        $this->assertTrue(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), "StudentA"));
        $this->assertFalse(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), "StudentB"));
    }

    /**
     * @test
     * @throws Exception
     */
    public function userHasRoleById()
    {
        $this->assertTrue(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), null, 5));
        $this->assertTrue(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), null, 7));
        $this->assertFalse(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), null, 8));
        $this->assertFalse(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), null, 100));
    }

    /**
     * @test
     */
    public function userHasRoleFailure()
    {
        $this->expectException(Exception::class);
        Role::userHasRole($this->courseUser->getId(), $this->course->getId());
    }


    /**
     * @test
     */
    public function sortRolesNameByMostSpecificSameLevel()
    {
        $sorted = Role::sortRolesNamesByMostSpecific($this->course->getRolesHierarchy(), ["Teacher", "Student", "Watcher"]);
        $this->assertEquals(["Teacher", "Student", "Watcher"], $sorted);
    }

    /**
     * @test
     */
    public function sortRolesNameByMostSpecificDifferentLevels()
    {
        $sorted = Role::sortRolesNamesByMostSpecific($this->course->getRolesHierarchy(), ["Teacher", "Student", "Watcher", "StudentA"]);
        $this->assertEquals(["Teacher", "StudentA", "Student", "Watcher"], $sorted);
    }


    /**
     * @test
     * @throws Exception
     */
    public function getChildrenNamesOfRole()
    {
        // Given
        $this->course->setRolesHierarchy([
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"]
                ]],
                ["name" => "StudentB"]
            ]],
            ["name" => "Watcher"]
        ]);
        $this->course->addRole("StudentA1");

        $children = Role::getChildrenNamesOfRole($this->course->getRolesHierarchy(), "Student");
        $this->assertIsArray($children);
        $this->assertCount(3, $children);
        $this->assertContains("StudentA", $children);
        $this->assertContains("StudentA1", $children);
        $this->assertContains("StudentB", $children);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getChildrenNamesOfRoleOnlyDirectChildren()
    {
        // Given
        $this->course->setRolesHierarchy([
            ["name" => "Teacher"],
            ["name" => "Student", "children" => [
                ["name" => "StudentA", "children" => [
                    ["name" => "StudentA1"]
                ]],
                ["name" => "StudentB"]
            ]],
            ["name" => "Watcher"]
        ]);
        $this->course->addRole("StudentA1");

        $children = Role::getChildrenNamesOfRole($this->course->getRolesHierarchy(), "Student", null, true);
        $this->assertIsArray($children);
        $this->assertCount(2, $children);
        $this->assertContains("StudentA", $children);
        $this->assertContains("StudentB", $children);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getChildrenNamesOfRoleNoChildren()
    {
        $children = Role::getChildrenNamesOfRole($this->course->getRolesHierarchy(), "Teacher");
        $this->assertIsArray($children);
        $this->assertEmpty($children);
    }
}
