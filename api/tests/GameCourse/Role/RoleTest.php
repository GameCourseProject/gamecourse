<?php
namespace GameCourse\Role;

use Error;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\User\Auth;
use GameCourse\User\User;
use PDOException;
use PHPUnit\Framework\TestCase;
use Utils\Utils;

/**
 * NOTE: only run tests outside the production environment
 *       as it will change the database directly
 */
class RoleTest extends TestCase
{
    private $loggedUser;
    private $user;
    private $course;

    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function setUpBeforeClass(): void
    {
        Core::database()->cleanDatabase();

        if (file_exists(LOGS_FOLDER)) Utils::deleteDirectory(LOGS_FOLDER);
        if (file_exists(COURSE_DATA_FOLDER)) Utils::deleteDirectory(COURSE_DATA_FOLDER);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions", false, ["defaults.py"]);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/config", false, ["samples"]);
    }

    protected function setUp(): void
    {
        // Set logged user
        $loggedUser = User::addUser("John Smith Doe", "ist123456", "fenix", "johndoe@email.com",
            123456, "John Doe", "MEIC-A", true, true);
        Core::setLoggedUser($loggedUser);
        $this->loggedUser = $loggedUser;

        // Set a course
        $course = Course::addCourse("Multimedia Content Production", "MCP", "2021-2022", "#ffffff",
            null, null, true, true);
        $this->course = $course;

        // Set a user
        $user = User::addUser("Johanna Smith Doe", "ist654321", "fenix", "johannadoe@email.com",
            654321, "Johanna Doe", "MEIC-A", false, true);
        $this->user = $user;
    }

    protected function tearDown(): void
    {
        Core::database()->deleteAll(Course::TABLE_COURSE);
        Core::database()->deleteAll(User::TABLE_USER);
        Core::database()->resetAutoIncrement(Course::TABLE_COURSE);
        Core::database()->resetAutoIncrement(User::TABLE_USER);
        Core::database()->resetAutoIncrement(Auth::TABLE_AUTH);
        Core::database()->resetAutoIncrement(Role::TABLE_ROLE);

        if (file_exists(LOGS_FOLDER)) Utils::deleteDirectory(LOGS_FOLDER);
        if (file_exists(COURSE_DATA_FOLDER)) Utils::deleteDirectory(COURSE_DATA_FOLDER);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions", false, ["defaults.py"]);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/config", false, ["samples"]);
    }

    public static function tearDownAfterClass(): void
    {
        Core::database()->cleanDatabase();

        if (file_exists(LOGS_FOLDER)) Utils::deleteDirectory(LOGS_FOLDER);
        if (file_exists(COURSE_DATA_FOLDER)) Utils::deleteDirectory(COURSE_DATA_FOLDER);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions", false, ["defaults.py"]);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/config", false, ["samples"]);
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
    public function addDefaultRolesToCourse()
    {
        // Given
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


    public function getCourseRolesNames()
    {

    }

    public function getCourseRolesNamesNoRoles()
    {

    }

    public function getCourseRolesInfo()
    {

    }

    public function getCourseRolesInfoNoRoles()
    {

    }

    public function getCourseRolesNamesSortedByMostSpecific()
    {

    }

    public function getCourseRolesNamesSortedByMostSpecificNoRoles()
    {

    }

    public function getCourseRolesHierarchy()
    {

    }

    public function getCourseRolesHierarchyNoRoles()
    {

    }
}
