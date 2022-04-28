<?php
namespace GameCourse\Role;

use Error;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\User\Auth;
use GameCourse\User\User;
use PDOException;
use PHPUnit\Framework\TestCase;
use TestingUtils;
use Utils\Utils;

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

    public static function setUpBeforeClass(): void
    {
        TestingUtils::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", "fenix", "johndoe@email.com",
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
        $user = User::addUser("Johanna Smith Doe", "ist654321", "fenix", "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $courseUser = $course->addUserToCourse($user->getId(), "Student");
        $courseUser->addRole("StudentA");
        $this->courseUser = $courseUser;
    }

    protected function tearDown(): void
    {
        // NOTE: try to only clean tables used during tests to improve efficiency;
        //       don't forget tables with foreign keys will be automatically deleted on cascade

        TestingUtils::cleanTables([Course::TABLE_COURSE, User::TABLE_USER]);
        TestingUtils::resetAutoIncrement([Course::TABLE_COURSE, User::TABLE_USER, Auth::TABLE_AUTH, Role::TABLE_ROLE]);
        TestingUtils::cleanFileStructure();
    }

    public static function tearDownAfterClass(): void
    {
        TestingUtils::tearDownAfterClass();
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tests ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @test
     */
    public function getRoleId()
    {
        $this->assertEquals(1, Role::getRoleId(Role::DEFAULT_ROLES[0], $this->course->getId()));
        $this->assertEquals(2, Role::getRoleId(Role::DEFAULT_ROLES[1], $this->course->getId()));
        $this->assertEquals(3, Role::getRoleId(Role::DEFAULT_ROLES[2], $this->course->getId()));
    }

    /**
     * @test
     */
    public function getRoleIdRoleDoesntExist()
    {
        $this->expectException(PDOException::class);
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
        $this->expectException(PDOException::class);
        Role::getRoleName(100);
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
     */
    public function addDefaultRolesToCourse()
    {
        // Given
        $this->course->setRolesHierarchy([]);
        $this->course->setRoles([]);
        Core::database()->resetAutoIncrement(Role::TABLE_ROLE);

        // When
        $teacherRoleId = Role::addDefaultRolesToCourse($this->course->getId());

        // Then
        $this->assertEquals(1, $teacherRoleId);

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
    public function addDefaultRolesToCourseRolesAlreadyExist()
    {
        $this->expectException(Error::class);
        Role::addDefaultRolesToCourse($this->course->getId());
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
            $this->assertCount(3, array_keys($role));
            $this->assertArrayHasKey("id", $role);
            $this->assertArrayHasKey("name", $role);
            $this->assertArrayHasKey("landingPage", $role);
            $this->assertContains($role["name"], ["Teacher", "StudentA", "StudentB", "Student", "Watcher"]);
        }
    }

    /**
     * @test
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
        $this->assertCount(3, array_keys($teacherRole));
        $this->assertArrayHasKey("id", $teacherRole);
        $this->assertArrayHasKey("name", $teacherRole);
        $this->assertArrayHasKey("landingPage", $teacherRole);
        $this->assertEquals("Teacher", $teacherRole["name"]);

        $studentRole = $hierarchy[1];
        $this->assertIsArray($studentRole);
        $this->assertCount(4, array_keys($studentRole));
        $this->assertArrayHasKey("id", $studentRole);
        $this->assertArrayHasKey("name", $studentRole);
        $this->assertArrayHasKey("landingPage", $studentRole);
        $this->assertArrayHasKey("children", $studentRole);
        $this->assertEquals("Student", $studentRole["name"]);
        $this->assertCount(2, $studentRole["children"]);

        $studentARole = $studentRole["children"][0];
        $this->assertIsArray($studentARole);
        $this->assertCount(3, array_keys($studentARole));
        $this->assertArrayHasKey("id", $studentARole);
        $this->assertArrayHasKey("name", $studentARole);
        $this->assertArrayHasKey("landingPage", $studentARole);
        $this->assertEquals("StudentA", $studentARole["name"]);

        $studentBRole = $studentRole["children"][1];
        $this->assertIsArray($studentBRole);
        $this->assertCount(3, array_keys($studentBRole));
        $this->assertArrayHasKey("id", $studentBRole);
        $this->assertArrayHasKey("name", $studentBRole);
        $this->assertArrayHasKey("landingPage", $studentBRole);
        $this->assertEquals("StudentB", $studentBRole["name"]);

        $watcherRole = $hierarchy[2];
        $this->assertIsArray($watcherRole);
        $this->assertCount(3, array_keys($watcherRole));
        $this->assertArrayHasKey("id", $watcherRole);
        $this->assertArrayHasKey("name", $watcherRole);
        $this->assertArrayHasKey("landingPage", $watcherRole);
        $this->assertEquals("Watcher", $watcherRole["name"]);
    }

    /**
     * @test
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
     */
    public function setCourseRolesByNames()
    {
        Role::setCourseRoles($this->course->getId(), ["NewRole1", "NewRole2"]);
        $rolesNames = Role::getCourseRoles($this->course->getId());
        $this->assertIsArray($rolesNames);
        $this->assertCount(2, $rolesNames);
        $this->assertContains("NewRole1", $rolesNames);
        $this->assertContains("NewRole2", $rolesNames);
    }

    /**
     * @test
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
    }

    /**
     * @test
     */
    public function setCourseRolesFailure()
    {
        $this->expectException(Error::class);
        Role::setCourseRoles($this->course->getId());
    }


    /**
     * @test
     */
    public function addRoleToCourse()
    {
        Role::addRoleToCourse($this->course->getId(), "NewRole");
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(6, $roles);
        $this->assertContains("NewRole", $roles);
    }

    /**
     * @test
     */
    public function addRoleToCourseRoleAlreadyExists()
    {
        Role::addRoleToCourse($this->course->getId(), "NewRole");
        Role::addRoleToCourse($this->course->getId(), "NewRole");
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(6, $roles);
        $this->assertContains("NewRole", $roles);
    }

    /**
     * @test
     */
    public function addRoleToCourseRoleInvalidRoleName()
    {
        try {
            Role::addRoleToCourse($this->course->getId(), "New Role");
            $this->fail("Error should have been thrown in 'addRoleToCourseRoleInvalidRoleName'");

        } catch (Error $e) {
            $roles = Role::getCourseRoles($this->course->getId());
            $this->assertCount(5, $roles);
            $this->assertNotContains("NewRole", $roles);
        }
    }

    // TODO
    public function addRoleToCourseWithLandingPageName()
    {
    }

    // TODO
    public function addRoleToCourseWithLandingPageId()
    {
    }


    /**
     * @test
     */
    public function removeRoleFromCourseByRoleName()
    {
        Role::removeRoleFromCourse($this->course->getId(), "Student");
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(2, $roles);
        $this->assertNotContains("Student", $roles);
        $this->assertNotContains("StudentA", $roles);
        $this->assertNotContains("StudentB", $roles);
    }

    /**
     * @test
     */
    public function removeRoleFromCourseByRoleId()
    {
        Role::removeRoleFromCourse($this->course->getId(), null, 2);
        $roles = Role::getCourseRoles($this->course->getId());
        $this->assertCount(2, $roles);
        $this->assertNotContains("Student", $roles);
        $this->assertNotContains("StudentA", $roles);
        $this->assertNotContains("StudentB", $roles);
    }

    /**
     * @test
     */
    public function removeRoleFromCourseFailure()
    {
        $this->expectException(Error::class);
        Role::removeRoleFromCourse($this->course->getId());
    }


    /**
     * @test
     */
    public function courseHasRoleByName()
    {
        $this->assertTrue(Role::courseHasRole($this->course->getId(), "Student"));
        $this->assertTrue(Role::courseHasRole($this->course->getId(), "StudentA"));
        $this->assertFalse(Role::courseHasRole($this->course->getId(), "role_doesnt_exist"));
    }

    /**
     * @test
     */
    public function courseHasRoleById()
    {
        $this->assertTrue(Role::courseHasRole($this->course->getId(), null, 2));
        $this->assertTrue(Role::courseHasRole($this->course->getId(), null, 4));
        $this->assertFalse(Role::courseHasRole($this->course->getId(), null, 100));
    }

    /**
     * @test
     */
    public function courseHasRoleFailure()
    {
        $this->expectException(Error::class);
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
            $this->assertCount(3, array_keys($role));
            $this->assertArrayHasKey("id", $role);
            $this->assertArrayHasKey("name", $role);
            $this->assertArrayHasKey("landingPage", $role);
            $this->assertContains($role["name"], ["StudentA", "Student"]);
        }
    }

    /**
     * @test
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
        $this->assertCount(4, array_keys($studentRole));
        $this->assertArrayHasKey("id", $studentRole);
        $this->assertArrayHasKey("name", $studentRole);
        $this->assertArrayHasKey("landingPage", $studentRole);
        $this->assertArrayHasKey("children", $studentRole);
        $this->assertEquals("Student", $studentRole["name"]);
        $this->assertCount(1, $studentRole["children"]);

        $studentARole = $studentRole["children"][0];
        $this->assertIsArray($studentARole);
        $this->assertCount(3, array_keys($studentARole));
        $this->assertArrayHasKey("id", $studentARole);
        $this->assertArrayHasKey("name", $studentARole);
        $this->assertArrayHasKey("landingPage", $studentARole);
        $this->assertEquals("StudentA", $studentARole["name"]);
    }

    /**
     * @test
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
     */
    public function setUserRolesByNamesRolesNotInCourse()
    {
        try {
            Role::setUserRoles($this->courseUser->getId(), $this->course->getId(), ["Student", "StudentC"]);
            $this->fail("Exception should have been thrown in 'setUserRolesByNamesRolesDontExist'");

        } catch (PDOException $e) {
            $rolesNames = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
            $this->assertIsArray($rolesNames);
            $this->assertCount(2, $rolesNames);
            $this->assertContains("Student", $rolesNames);
            $this->assertContains("StudentA", $rolesNames);
        }
    }


    /**
     * @test
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
     */
    public function addRoleToUserById()
    {
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId(), null, 5);
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertCount(3, $roles);
        $this->assertContains("StudentB", $roles);
    }

    /**
     * @test
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
     */
    public function addRoleToUserRoleNotInCourse()
    {
        $this->expectException(PDOException::class);
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId(), "NewRole");
    }

    /**
     * @test
     */
    public function addRoleToUserFailure()
    {
        $this->expectException(Error::class);
        Role::addRoleToUser($this->courseUser->getId(), $this->course->getId());
    }


    /**
     * @test
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
     */
    public function removeRoleFromUserByRoleId()
    {
        Role::removeRoleFromUser($this->courseUser->getId(), $this->course->getId(), null, 4);
        $roles = Role::getUserRoles($this->courseUser->getId(), $this->course->getId());
        $this->assertCount(1, $roles);
        $this->assertContains("Student", $roles);
    }

    /**
     * @test
     */
    public function removeRoleFromUserFailure()
    {
        $this->expectException(Error::class);
        Role::removeRoleFromUser($this->courseUser->getId(), $this->course->getId());
    }


    /**
     * @test
     */
    public function userHasRoleByName()
    {
        $this->assertTrue(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), "Student"));
        $this->assertTrue(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), "StudentA"));
        $this->assertFalse(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), "StudentB"));
    }

    /**
     * @test
     */
    public function userHasRoleById()
    {
        $this->assertTrue(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), null, 2));
        $this->assertTrue(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), null, 4));
        $this->assertFalse(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), null, 5));
        $this->assertFalse(Role::userHasRole($this->courseUser->getId(), $this->course->getId(), null, 100));
    }

    /**
     * @test
     */
    public function userHasRoleFailure()
    {
        $this->expectException(Error::class);
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
     */
    public function getChildrenNamesOfRole()
    {
        $children = Role::getChildrenNamesOfRole($this->course->getRolesHierarchy(), "Student");
        $this->assertIsArray($children);
        $this->assertCount(2, $children);
        $this->assertContains("StudentA", $children);
        $this->assertContains("StudentB", $children);
    }

    /**
     * @test
     */
    public function getChildrenNamesOfRoleNoChildren()
    {
        $children = Role::getChildrenNamesOfRole($this->course->getRolesHierarchy(), "Teacher");
        $this->assertIsArray($children);
        $this->assertEmpty($children);
    }
}
