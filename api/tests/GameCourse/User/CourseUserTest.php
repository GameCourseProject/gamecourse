<?php
namespace GameCourse\User;

use DateTime;
use Error;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use PDOException;
use PHPUnit\Framework\TestCase;
use Utils\Utils;

/**
 * NOTE: only run tests outside the production environment
 *       as it will change the database directly
 */
class CourseUserTest extends TestCase
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

        if (file_exists(ROOT_PATH . "logs")) Utils::deleteDirectory(ROOT_PATH . "logs");
        Utils::deleteDirectory(ROOT_PATH . "course_data", false, ["defaultData"]);
        Utils::deleteDirectory(ROOT_PATH . "autogame/imported-functions", false, ["defaults.py"]);
        Utils::deleteDirectory(ROOT_PATH . "autogame/config", false, ["samples"]);
    }

    public static function tearDownAfterClass(): void
    {
        Core::database()->cleanDatabase();
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Data Providers ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public function addCourseUserSuccessProvider(): array
    {
        $userId = 2;
        $courseId = 1;
        return [
            "no role" => [$userId, $courseId, null, null],
            "with teacher role name" => [$userId, $courseId, "Teacher", null],
            "with student role name" => [$userId, $courseId, "Student", null],
            "with teacher role ID" => [$userId, $courseId, null, 1],
            "with student role ID" => [$userId, $courseId, null, 2],
            "with role name and role ID" => [$userId, $courseId, "Student", 2]
        ];
    }

    public function addCourseUserFailureProvider(): array
    {
        $userId = 2;
        $courseId = 1;
        return [
            "course doesn't exist" => [$userId, 10, null, null],
            "user doesn't exist" => [10, $courseId, null, null],
            "role name doesn't exist" => [$userId, $courseId, "role_doesnt_exist", null],
            "role ID doesn't exist" => [$userId, $courseId, null, 10],
        ];
    }

    public function setActivitySuccessProvider(): array
    {
        return [
            "null" => [null],
            "datetime" => [date("Y-m-d H:i:s", time())]
        ];
    }

    public function setActivityFailureProvider(): array
    {
        return [
            "only date" => [date("Y-m-d", time())],
            "only time" => [date("H:i:s", time())]
        ];
    }

    public function setDataSuccessProvider(): array
    {
        return [
            "same data" => [["lastActivity" => null, "previousActivity" => null, "isActive" => "1"]],
            "different lastActivity" => [["lastActivity" => date("Y-m-d H:i:s", time())]],
            "different previousActivity" => [["previousActivity" => date("Y-m-d H:i:s", time())]],
            "different isActive" => [["isActive" => "0"]],
            "all different" => [["lastActivity" => date("Y-m-d H:i:s", time()), "previousActivity" => date("Y-m-d H:i:s", time()), "isActive" => "0"]]
        ];
    }

    public function setDataFailureProvider(): array
    {
        return [
            "incorrect lastActivity format" => [["lastActivity" => date("Y-m-d", time())]],
            "incorrect previousActivity format" => [["previousActivity" => date("Y-m-d", time())]]
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
     */
    public function getId()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals($this->user->getId(), $courseUser->getId());
    }

    /**
     * @test
     */
    public function getCourse()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals($this->course, $courseUser->getCourse());
    }

    /**
     * @test
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
     */
    public function getLastActivityNull()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertNull($courseUser->getLastActivity());
    }

    /**
     * @test
     */
    public function getPreviousActivity()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $datetime = date("Y-m-d H:i:s", time());
        $courseUser->setPreviousActivity($datetime);
        $this->assertEquals($datetime, $courseUser->getPreviousActivity());
    }

    /**
     * @test
     */
    public function getPreviousActivityNull()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertNull($courseUser->getPreviousActivity());
    }

    /**
     * @test
     */
    public function isActive()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertTrue($courseUser->isActive());
    }

    /**
     * @test
     */
    public function isInactive()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setActive(false);
        $this->assertFalse($courseUser->isActive());
    }


    /**
     * @test
     */
    public function getData()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals(["id" => 2, "name" => "Johanna Smith Doe", "username" => "ist654321", "authentication_service" => "fenix",
            "email" => "johannadoe@email.com", "studentNumber" => 654321, "nickname" => "Johanna Doe", "major" => "MEIC-A",
            "isAdmin" => false, "isActive" => true, "course" => 1, "lastActivity" => null, "previousActivity" => null,
            "isActiveInCourse" => true], $courseUser->getData());
    }

    /**
     * @test
     */
    public function getDataOnlyUserFields()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals(["id" => 2, "name" => "Johanna Smith Doe", "username" => "ist654321", "authentication_service" => "fenix",
            "email" => "johannadoe@email.com", "studentNumber" => 654321, "nickname" => "Johanna Doe", "major" => "MEIC-A",
            "isAdmin" => false],
            $courseUser->getData("id, name, username, authentication_service, email, studentNumber, nickname, major, isAdmin"));
    }

    /**
     * @test
     */
    public function getDataOnlyCourseUserFields()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals(["course" => 1, "lastActivity" => null, "previousActivity" => null, "isActive" => true],
            $courseUser->getData("course, lastActivity, previousActivity, isActive"));
    }

    /**
     * @test
     */
    public function getDataMixedFields()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertEquals(["name" => "Johanna Smith Doe", "lastActivity" => null, "isActive" => true],
            $courseUser->getData("name, lastActivity, isActive"));
    }

    /**
     * @test
     */
    public function getDataCourseUserDoesntExist()
    {
        $courseUser = new CourseUser(10, $this->course);
        $this->assertFalse($courseUser->getData());
        $this->assertNull($courseUser->getData("id"));
        $this->assertFalse($courseUser->getData("lastActivity"));
    }


    /**
     * @test
     * @dataProvider setActivitySuccessProvider
     */
    public function setLastActivitySuccess(?string $lastActivity)
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setLastActivity($lastActivity);
        $this->assertEquals($lastActivity, $courseUser->getLastActivity());
    }

    /**
     * @test
     * @dataProvider setActivityFailureProvider
     */
    public function setLastActivityFailure(string $lastActivity)
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->expectException(Error::class);
        $courseUser->setLastActivity($lastActivity);
    }

    /**
     * @test
     * @dataProvider setActivitySuccessProvider
     */
    public function setPreviousActivitySuccess(?string $previousActivity)
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setPreviousActivity($previousActivity);
        $this->assertEquals($previousActivity, $courseUser->getPreviousActivity());
    }

    /**
     * @test
     * @dataProvider setActivityFailureProvider
     */
    public function setPreviousActivityFailure(string $previousActivity)
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->expectException(Error::class);
        $courseUser->setPreviousActivity($previousActivity);
    }

    /**
     * @test
     */
    public function setActive()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setActive(true);
        $this->assertTrue($courseUser->isActive());
    }

    /**
     * @test
     * @dataProvider setDataSuccessProvider
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

        } catch (Error $e) {
            $courseUser = new CourseUser($this->user->getId(), $this->course);
            $this->assertEquals(["id" => $this->user->getId(), "course" => $this->course->getId(), "lastActivity" => null,
                "previousActivity" => null, "isActive" => true], $courseUser->getData("id, course, lastActivity, previousActivity, isActive"));
        }
    }


    /**
     * @test
     */
    public function refreshActivity()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->refreshActivity();

        $lastActivity = DateTime::createFromFormat("Y-m-d H:i:s", $courseUser->getLastActivity());
        $this->assertEquals(date("Y-m-d H:i", time()), $lastActivity->format("Y-m-d H:i"));
        $this->assertNull($courseUser->getPreviousActivity());
    }

    /**
     * @test
     */
    public function isTeacher()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), "Teacher");
        $this->assertTrue($courseUser->isTeacher());
    }

    /**
     * @test
     */
    public function isNotTeacher()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertFalse($courseUser->isTeacher());
    }

    /**
     * @test
     */
    public function isStudent()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId(), "Student");
        $this->assertTrue($courseUser->isStudent());
    }

    /**
     * @test
     */
    public function isNotStudent()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertFalse($courseUser->isStudent());
    }


    /**
     * @test
     * @dataProvider addCourseUserSuccessProvider
     */
    public function addCourseUserSuccess(int $userId, int $courseId, ?string $roleName, ?int $roleId)
    {
        CourseUser::addCourseUser($userId, $courseId, $roleName, $roleId);
        $this->assertCount(2, Course::getUsers($courseId));

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
     */
    public function addCourseUserFailure(int $userId, int $courseId, ?string $roleName, ?int $roleId)
    {
        $this->expectException(PDOException::class);
        CourseUser::addCourseUser($userId, $courseId, $roleName, $roleId);
        $this->assertCount(1, (new Course($courseId))->getUsers());
        $this->assertFalse((new CourseUser($userId, new Course($courseId)))->exists());
    }

    /**
     * @test
     */
    public function addCourseUserAlreadyInCourse()
    {
        CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(2, Course::getUsers($this->course->getId()));
        $this->assertTrue((new CourseUser($this->user->getId(), $this->course))->exists());

        $this->expectException(PDOException::class);
        CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(2, (new Course($this->course->getId()))->getUsers());
        $this->assertTrue((new CourseUser($this->user->getId(), $this->course))->exists());
    }


    /**
     * @test
     */
    public function deleteCourseUser()
    {
        CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(2, Course::getUsers($this->course->getId()));
        $this->assertTrue((new CourseUser($this->user->getId(), $this->course))->exists());

        CourseUser::deleteCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(1, Course::getUsers($this->course->getId()));
        $this->assertFalse((new CourseUser($this->user->getId(), $this->course))->exists());
    }

    /**
     * @test
     */
    public function deleteCourseUserInexistentCourseUser()
    {
        CourseUser::deleteCourseUser($this->user->getId(), $this->course->getId());
        $this->assertCount(1, Course::getUsers($this->course->getId()));
        $this->assertFalse((new CourseUser($this->user->getId(), $this->course))->exists());
    }


    /**
     * @test
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
            $this->assertCount(3, array_keys($role));
            $this->assertArrayHasKey("id", $role);
            $this->assertArrayHasKey("name", $role);
            $this->assertArrayHasKey("landingPage", $role);
        }

        $rolesNames = array_column($roles, "name");
        $this->assertContains("Student", $rolesNames);
        $this->assertContains("StudentA", $rolesNames);
        $this->assertContains("StudentA1", $rolesNames);
        $this->assertContains("StudentB", $rolesNames);
    }

    /**
     * @test
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
        $this->assertCount(4, array_keys($student));
        $this->assertArrayHasKey("id", $student);
        $this->assertArrayHasKey("name", $student);
        $this->assertArrayHasKey("landingPage", $student);
        $this->assertArrayHasKey("children", $student);
        $this->assertEquals("Student", $student["name"]);
        $this->assertCount(2, $student["children"]);

        $studentA = $student["children"][0];
        $this->assertCount(4, array_keys($studentA));
        $this->assertArrayHasKey("id", $studentA);
        $this->assertArrayHasKey("name", $studentA);
        $this->assertArrayHasKey("landingPage", $studentA);
        $this->assertArrayHasKey("children", $studentA);
        $this->assertEquals("StudentA", $studentA["name"]);
        $this->assertCount(1, $studentA["children"]);

        $studentA1 = $studentA["children"][0];
        $this->assertCount(3, array_keys($studentA1));
        $this->assertArrayHasKey("id", $studentA1);
        $this->assertArrayHasKey("name", $studentA1);
        $this->assertArrayHasKey("landingPage", $studentA1);
        $this->assertEquals("StudentA1", $studentA1["name"]);


        $studentB = $student["children"][1];
        $this->assertCount(3, array_keys($studentB));
        $this->assertArrayHasKey("id", $studentB);
        $this->assertArrayHasKey("name", $studentB);
        $this->assertArrayHasKey("landingPage", $studentB);
        $this->assertEquals("StudentB", $studentB["name"]);
    }

    /**
     * @test
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
     */
    public function addRoleNoRoleGiven()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->expectException(Error::class);
        $courseUser->addRole();
    }

    /**
     * @test
     */
    public function addRoleRolesDoesntExist()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $this->expectException(PDOException::class);
        $courseUser->addRole("role_doesnt_exist");
    }

    /**
     * @test
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
     */
    public function removeRoleNoRoleGiven()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);

        $this->expectException(Error::class);
        $courseUser->removeRole();
    }


    /**
     * @test
     */
    public function hasRoleByName()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $this->assertTrue($courseUser->hasRole("Teacher"));
    }

    /**
     * @test
     */
    public function hasRoleById()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $this->assertTrue($courseUser->hasRole(null, Role::getRoleId("Teacher", $this->course->getId())));
    }

    /**
     * @test
     */
    public function hasRoleNoRoleGiven()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);

        $this->expectException(Error::class);
        $courseUser->hasRole();
    }

    /**
     * @test
     */
    public function doesntHaveRole()
    {
        $courseUser = CourseUser::addCourseUser($this->user->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $this->assertFalse($courseUser->hasRole("Watcher"));
    }


    /**
     * @test
     */
    public function importCourseUsersWithHeaderUniqueCourseUsersNoReplace()
    {
        // Given
        $file = "name,email,major,nickname,studentNumber,username,authentication_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file, false);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(4, $nrUsersImported);

        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user4 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser1 = ["id" => 3, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser2 = ["id" => 4, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "authentication_service" => "linkedin", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser3 = ["id" => 5, "name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "major" => "LEIC-T",
            "nickname" => "", "studentNumber" => 84715, "username" => "ist426015",
            "authentication_service" => "google", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];
        $expectedUser4 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "authentication_service" => "facebook", "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];

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
     */
    public function importCourseUsersWithHeaderNonUniqueCourseUsersNoReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", "fenix", "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);
        CourseUser::addCourseUser($user->getId(), $this->course->getId(), "Teacher");

        $file = "name,email,major,nickname,studentNumber,username,authentication_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file, false);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(3, $nrUsersImported);

        $user0 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser0 = ["id" => 3, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "major" => "MEIC-A",
            "nickname" => "Ana G", "studentNumber" => 84715, "username" => "ist426015",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser1 = ["id" => 4, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser2 = ["id" => 5, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "authentication_service" => "linkedin", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser3 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "authentication_service" => "facebook", "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];

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
     */
    public function importCourseUsersWithHeaderNonUniqueCourseUsersReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", "fenix", "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);
        CourseUser::addCourseUser($user->getId(), $this->course->getId(), "Teacher");

        $file = "name,email,major,nickname,studentNumber,username,authentication_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $file .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(3, $nrUsersImported);

        $user0 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser0 = ["id" => 3, "name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "major" => "LEIC-T",
            "nickname" => "", "studentNumber" => 84715, "username" => "ist426015",
            "authentication_service" => "google", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];
        $expectedUser1 = ["id" => 4, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser2 = ["id" => 5, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "authentication_service" => "linkedin", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser3 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "authentication_service" => "facebook", "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];

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

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(4, $nrUsersImported);

        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user4 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser1 = ["id" => 3, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser2 = ["id" => 4, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "authentication_service" => "linkedin", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser3 = ["id" => 5, "name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "major" => "LEIC-T",
            "nickname" => "", "studentNumber" => 84715, "username" => "ist426015",
            "authentication_service" => "google", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];
        $expectedUser4 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "authentication_service" => "facebook", "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];

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
     */
    public function importCourseUsersWithNoHeaderNonUniqueCourseUsersReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", "fenix", "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);
        CourseUser::addCourseUser($user->getId(), $this->course->getId(), "Teacher");

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(3, $nrUsersImported);

        $user0 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser0 = ["id" => 3, "name" => "Filipe José Zillo Colaço", "email" => "fijozico@hotmail.com", "major" => "LEIC-T",
            "nickname" => "", "studentNumber" => 84715, "username" => "ist426015",
            "authentication_service" => "google", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];
        $expectedUser1 = ["id" => 4, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser2 = ["id" => 5, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "authentication_service" => "linkedin", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser3 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "authentication_service" => "facebook", "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];

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
     */
    public function importCourseUsersWithNoHeaderNonUniqueCourseUsersNoReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", "fenix", "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);
        CourseUser::addCourseUser($user->getId(), $this->course->getId(), "Teacher");

        $file = "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $file .= "Inês Albano,ines.albano@tecnico.ulisboa.pt,MEIC-A,,87664,ist187664,linkedin,0,1,1,Student\n";
        $file .= "Filipe José Zillo Colaço,fijozico@hotmail.com,LEIC-T,,84715,ist426015,google,0,1,0,Student\n";
        $file .= "Mariana Wong Brandão,marianawbrandao@icloud.com,MEMec,Mariana Brandão,86893,ist186893,facebook,0,0,0,Teacher Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file, false);

        // Then
        $users = User::getUsers();
        $this->assertCount(6, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(5, $courseUsers);
        $this->assertEquals(3, $nrUsersImported);

        $user0 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $user1 = new CourseUser(User::getUserByStudentNumber(100956)->getId(), $this->course);
        $user2 = new CourseUser(User::getUserByStudentNumber(87664)->getId(), $this->course);
        $user3 = new CourseUser(User::getUserByStudentNumber(86893)->getId(), $this->course);

        $expectedUser0 = ["id" => 3, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "major" => "MEIC-A",
            "nickname" => "Ana G", "studentNumber" => 84715, "username" => "ist426015",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser1 = ["id" => 4, "name" => "Sabri M'Barki", "email" => "sabri.m.barki@efrei.net", "major" => "MEIC-T",
            "nickname" => "Sabri M'Barki", "studentNumber" => 100956, "username" => "ist1100956",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser2 = ["id" => 5, "name" => "Inês Albano", "email" => "ines.albano@tecnico.ulisboa.pt", "major" => "MEIC-A",
            "nickname" => "", "studentNumber" => 87664, "username" => "ist187664",
            "authentication_service" => "linkedin", "isAdmin" => false, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $expectedUser3 = ["id" => 6, "name" => "Mariana Wong Brandão", "email" => "marianawbrandao@icloud.com", "major" => "MEMec",
            "nickname" => "Mariana Brandão", "studentNumber" => 86893, "username" => "ist186893",
            "authentication_service" => "facebook", "isAdmin" => false, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => false];

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
     */
    public function importCourseUsersIsAlreadyUserOfSystemNoReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", "fenix", "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);

        $file = "Ana Rita Gonçalves,ana.goncalves@hotmail.com,MEIC-A,Ana G,84715,ist426015,fenix,1,1,1,Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file, false);

        // Then
        $users = User::getUsers();
        $this->assertCount(3, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(2, $courseUsers);
        $this->assertEquals(1, $nrUsersImported);

        $user1 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $expectedUser1 = ["id" => 3, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "major" => "MEIC-A",
            "nickname" => "Ana G", "studentNumber" => 84715, "username" => "ist426015",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => false, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $this->assertEquals($expectedUser1, $user1->getData());

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);
    }

    /**
     * @test
     */
    public function importCourseUsersIsAlreadyUserOfSystemReplace()
    {
        // Given
        $user = User::addUser("Ana Rita Gonçalves", "ist426015", "fenix", "ana.goncalves@hotmail.com",
            84715, "Ana G", "MEIC-A", true, false);

        $file = "Ana Rita Gonçalves,ana.goncalves@hotmail.com,MEIC-A,Ana G,84715,ist426015,fenix,1,1,1,Student";

        // When
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        // Then
        $users = User::getUsers();
        $this->assertCount(3, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(2, $courseUsers);
        $this->assertEquals(1, $nrUsersImported);

        $user1 = new CourseUser(User::getUserByStudentNumber(84715)->getId(), $this->course);
        $expectedUser1 = ["id" => 3, "name" => "Ana Rita Gonçalves", "email" => "ana.goncalves@hotmail.com", "major" => "MEIC-A",
            "nickname" => "Ana G", "studentNumber" => 84715, "username" => "ist426015",
            "authentication_service" => "fenix", "isAdmin" => true, "isActive" => true, "course" => $this->course->getId(),
            "lastActivity" => null, "previousActivity" => null, "isActiveInCourse" => true];
        $this->assertEquals($expectedUser1, $user1->getData());

        $user1Roles = $user1->getRoles();
        $this->assertIsArray($user1Roles);
        $this->assertCount(1, $user1Roles);
        $this->assertContains("Student", $user1Roles);
    }

    /**
     * @test
     */
    public function importCourseUsersEmptyFileNoHeaderNoCourseUsers()
    {
        $file = "";
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        $users = User::getUsers();
        $this->assertCount(2, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(1, $courseUsers);
        $this->assertEquals(0, $nrUsersImported);
    }

    /**
     * @test
     */
    public function importCourseUsersEmptyFileNoHeaderWithCourseUsers()
    {
        $user1 = User::addUser("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com",
            10000, "Ana G", "MEIC-A", true, false);
        $user2 = User::addUser("João Carlos Sousa", "ist1234567", "fenix", "joao@gmail.com",
            1234567, "João Sousa", "MEIC-A", false, true);
        $user3 = User::addUser("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net",
            100956, "Sabri M'Barki", "MEIC-T", true, true);

        CourseUser::addCourseUser($user1->getId(), $this->course->getId(), "Teacher");
        CourseUser::addCourseUser($user2->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user3->getId(), $this->course->getId(), "Student");

        $file = "";
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        $users = User::getUsers();
        $this->assertCount(5, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(4, $courseUsers);
        $this->assertEquals(0, $nrUsersImported);
    }

    /**
     * @test
     */
    public function importCourseUsersEmptyFileWithHeaderWithCourseUsers()
    {
        $user1 = User::addUser("Ana Gonçalves", "ist100000", "fenix", "ana.goncalves@gmail.com",
            10000, "Ana G", "MEIC-A", true, false);
        $user2 = User::addUser("João Carlos Sousa", "ist1234567", "fenix", "joao@gmail.com",
            1234567, "João Sousa", "MEIC-A", false, true);
        $user3 = User::addUser("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net",
            100956, "Sabri M'Barki", "MEIC-T", true, true);

        CourseUser::addCourseUser($user1->getId(), $this->course->getId(), "Teacher");
        CourseUser::addCourseUser($user2->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user3->getId(), $this->course->getId(), "Student");

        $file = "name,email,major,nickname,studentNumber,username,authentication_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $nrUsersImported = CourseUser::importCourseUsers($this->course->getId(), $file);

        $users = User::getUsers();
        $this->assertCount(5, $users);

        $courseUsers = Course::getUsers($this->course->getId());
        $this->assertCount(4, $courseUsers);
        $this->assertEquals(0, $nrUsersImported);
    }

    /**
     * @test
     */
    public function importCourseUsersCourseDoesntExist()
    {
        $this->expectException(Error::class);
        CourseUser::importCourseUsers(10, "");
    }


    /**
     * @test
     */
    public function exportCourseUsers()
    {
        $user1 = User::addUser("Sabri M'Barki", "ist1100956", "fenix", "sabri.m.barki@efrei.net",
            100956, "Sabri M'Barki", "MEIC-T", true, true);
        $user2 = User::addUser("Marcus Notø", "ist1101036", "fenix", "marcus.n.hansen@gmail.com",
            1101036, "Marcus Notø", "MEEC", true, false);
        $user3 = User::addUser("Inês Albano", "ist187664", "fenix", "ines.albano@tecnico.ulisboa.pt",
            87664, null, "MEIC-A", false, true);
        $user4 = User::addUser("Filipe José Zillo Colaço", "ist426015", "fenix", "fijozico@hotmail.com",
            84715, null, "LEIC-T", false, true);
        $user5 = User::addUser("Mariana Wong Brandão", "ist186893", "fenix", "marianawbrandao@icloud.com",
            86893, "Mariana Brandão", "MEMec", false, false);

        CourseUser::addCourseUser($user1->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user2->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user3->getId(), $this->course->getId(), "Student");
        CourseUser::addCourseUser($user4->getId(), $this->course->getId(), "Teacher");
        $courseUser = CourseUser::addCourseUser($user5->getId(), $this->course->getId());
        $courseUser->setRoles(["Teacher", "Student"]);
        $courseUser->setActive(false);

        $expectedFile = "name,email,major,nickname,studentNumber,username,authentication_service,isAdmin,isActive,isActiveInCourse,roles\n";
        $expectedFile .= "John Smith Doe,johndoe@email.com,MEIC-A,John Doe,123456,ist123456,fenix,1,1,1,Teacher\n";
        $expectedFile .= "Sabri M'Barki,sabri.m.barki@efrei.net,MEIC-T,Sabri M'Barki,100956,ist1100956,fenix,1,1,1,Student\n";
        $expectedFile .= "Marcus Notø,marcus.n.hansen@gmail.com,MEEC,Marcus Notø,1101036,ist1101036,fenix,1,0,1,Student\n";
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
        $this->expectException(Error::class);
        CourseUser::exportCourseUsers(10);
    }
}
