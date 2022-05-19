<?php
namespace GameCourse\Role;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Views\Page;
use PDOException;

/**
 * This is the Role model, which implements the necessary methods
 * to interact with roles in the MySQL database.
 */
class Role
{
    const TABLE_ROLE = "role";
    const TABLE_USER_ROLE = "user_role";

    const DEFAULT_ROLES = ["Teacher", "Student", "Watcher"];  // default roles for each course


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a role ID of a given course by role name.
     *
     * @param string $roleName
     * @param int $courseId
     * @return int
     */
    public static function getRoleId(string $roleName, int $courseId): int
    {
        $id = intval(Core::database()->select(self::TABLE_ROLE, ["course" => $courseId, "name" => $roleName], "id"));
        if (!$id) throw new PDOException("Role with name '" . $roleName . "' doesn't exist for course with ID = " . $courseId . ".");
        return $id;
    }

    /**
     * Gets a role name by role ID.
     *
     * @param int $roleId
     * @return string
     */
    public static function getRoleName(int $roleId): string
    {
        $roleName = Core::database()->select(self::TABLE_ROLE, ["id" => $roleId], "name");
        if (!$roleName) throw new PDOException("Role with ID = " . $roleId . "' doesn't exist.");
        return $roleName;
    }

    /**
     * Gets roles names in a given hierarchy.
     *
     * @example Hierarchy: [
     *                          ["name" => "Teacher"],
     *                          ["name" => "Student", "children" => [
     *                              ["name" => "StudentA"],
     *                              ["name" => "StudentB"]
     *                          ]],
     *                          ["name" => "Watcher"]
     *                     ]
     *          getRolesNamesByHierarchy($hierarchy) --> ["Teacher", "StudentA", "StudentB", "Student", "Watcher"]
     *
     * @param array $hierarchy
     * @return array
     */
    public static function getRolesNamesInHierarchy(array $hierarchy): array
    {
        $rolesNames = [];
        self::traverseRoles($hierarchy, function ($role, $parent, $key, $hasChildren, $continue, &...$data) {
            if ($hasChildren) $continue(...$data);
            $data[0][] = $role["name"];
        }, $rolesNames);
        return $rolesNames;
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Course related ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds default roles to a given course.
     * Returns ID of Teacher role.
     *
     * @param int $courseId
     * @return int|null
     * @throws Exception
     */
    public static function addDefaultRolesToCourse(int $courseId): ?int
    {
        // Check if default roles already exist
        $rolesNames = array_column(Core::database()->selectMultiple(self::TABLE_ROLE, ["course" => $courseId], "name"), "name");
        foreach ($rolesNames as $roleName) {
            if (in_array($roleName, self::DEFAULT_ROLES))
                throw new Exception("Default role '" . $roleName . "' already exists in course with ID = " . $courseId);
        }

        $teacherId = null;
        foreach (self::DEFAULT_ROLES as $role) {
            $id = Core::database()->insert(self::TABLE_ROLE, ["name" => $role, "course" => $courseId]);
            if ($role === "Teacher") $teacherId = $id;
        }

        $hierarchy = array_map(function ($role) { return ["name" => $role]; }, self::DEFAULT_ROLES);
        Core::database()->update(Course::TABLE_COURSE, ["roleHierarchy" => json_encode($hierarchy)], ["id" => $courseId]);

        return $teacherId;
    }

    /**
     * Gets course's roles. Option to retrieve only roles' names and/or to
     * sort them hierarchly. Sorting works like this:
     *  - if only names --> with the more specific roles first, followed
     *                      by the less specific ones
     *  - else --> retrieve roles' hierarchy
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getCourseRoles(<courseID>) --> ["Watcher", "Student", "StudentB", "StudentA", "Teacher"] (no fixed order)
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getCourseRoles(<courseID>, false) --> [
     *                                                  ["name" => "Watcher", "id" => 3, "landingPage" => null],
     *                                                  ["name" => "Student", "id" => 2, "landingPage" => null],
     *                                                  ["name" => "StudentB", "id" => 5, "landingPage" => null],
     *                                                  ["name" => "StudentA", "id" => 4, "landingPage" => null],
     *                                                  ["name" => "Teacher", "id" => 1, "landingPage" => null]
     *                                                ] (no fixed order)
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getCourseRoles(<courseID>, true, true) --> ["Teacher", "StudentA", "StudentB", "Student", "Watcher"]
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getCourseRoles(<courseID>, false, true) --> [
     *                                                          ["name" => "Teacher", "id" => 1, "landingPage" => null],
     *                                                          ["name" => "Student", "id" => 2, "landingPage" => null, "children" => [
     *                                                              ["name" => "StudentA", "id" => 4, "landingPage" => null],
     *                                                              ["name" => "StudentB", "id" => 5, "landingPage" => null]
     *                                                          ]],
     *                                                          ["name" => "Watcher", "id" => 3, "landingPage" => null]
     *                                                      ]
     *
     * @param int $courseId
     * @param bool $onlyNames
     * @param bool $sortByHierarchy
     * @return array
     */
    public static function getCourseRoles(int $courseId, bool $onlyNames = true, bool $sortByHierarchy = false): array
    {
        if ($onlyNames) {
            $rolesNames = array_column(Core::database()->selectMultiple(self::TABLE_ROLE, ["course" => $courseId], "name"), "name");
            if ($sortByHierarchy) {
                $hierarchy = (new Course($courseId))->getRolesHierarchy();
                return self::sortRolesNamesByMostSpecific($hierarchy, $rolesNames);

            } else return $rolesNames;

        } else {
            if ($sortByHierarchy) {
                $roles = Core::database()->selectMultiple(self::TABLE_ROLE, ["course" => $courseId]);
                foreach ($roles as &$role) { $role = self::parse($role); }
                $rolesByName = array_combine(array_column($roles, "name"), $roles);
                $hierarchy = (new Course($courseId))->getRolesHierarchy();
                self::traverseRoles($hierarchy, function (&$role, &$parent, $key, $hasChildren, $continue) use ($rolesByName, &$hierarchy) {
                    if ($hasChildren) $continue();
                    $role["id"] = $rolesByName[$role["name"]]["id"];
                    $role["landingPage"] = $rolesByName[$role["name"]]["landingPage"];
                });
                return $hierarchy;

            } else {
                $roles = Core::database()->selectMultiple(self::TABLE_ROLE, ["course" => $courseId], "id, name, landingPage");
                foreach ($roles as &$role) { $role = self::parse($role); }
                return $roles;
            }
        }
    }

    /**
     * Replaces course's roles in the database.
     * NOTE: it doesn't update roles hierarchy
     *
     * @param int $courseId
     * @param array|null $rolesNames
     * @param array|null $hierarchy
     * @return void
     * @throws Exception
     */
    public static function setCourseRoles(int $courseId, array $rolesNames = null, array $hierarchy = null)
    {
        if ($rolesNames === null && $hierarchy === null)
            throw new Exception("Need either roles names or hierarchy to set roles in a course.");

        // Remove all course roles
        Core::database()->delete(self::TABLE_ROLE, ["course" => $courseId]);

        // Add new roles
        if ($rolesNames === null) $rolesNames = self::getRolesNamesInHierarchy($hierarchy);
        foreach ($rolesNames as $roleName) {
            self::addRoleToCourse($courseId, $roleName);
        }
    }

    /**
     * Adds a new role to a given course if it isn't already added.
     * Option to pass either landing page name or ID.
     * NOTE: it doesn't update roles hierarchy
     *
     * @param int $courseId
     * @param string|null $roleName
     * @param string|null $landingPageName
     * @param int|null $landingPageId
     * @return void
     * @throws Exception
     */
    public static function addRoleToCourse(int $courseId, string $roleName, string $landingPageName = null, int $landingPageId = null)
    {
        if (!self::courseHasRole($courseId, $roleName)) {
            self::validateRoleName($roleName);
            $data = ["course" => $courseId, "name" => $roleName];
            if ($landingPageName !== null) $landingPageId = Page::getPageId($landingPageName, $courseId);
            if ($landingPageId !== null) $data["landingPage"] = $landingPageId;
            Core::database()->insert(self::TABLE_ROLE, $data);
        }
    }

    /**
     * Removes a given role from a course, including its children.
     * Option to pass either role name or role ID.
     * NOTE: it doesn't update roles hierarchy
     *
     * @param int $courseId
     * @param string|null $roleName
     * @param int|null $roleId
     * @return void
     * @throws Exception
     */
    public static function removeRoleFromCourse(int $courseId, string $roleName = null, int $roleId = null)
    {
        if ($roleName === null && $roleId === null)
            throw new Exception("Need either role name or ID to add new role to a user.");

        if ($roleName === null) $roleName = self::getRoleName($roleId);
        $remove = array_merge([$roleName], self::getChildrenNamesOfRole((new Course($courseId))->getRolesHierarchy(), $roleName));
        foreach ($remove as $roleName) {
            Core::database()->delete(self::TABLE_ROLE, ["course" => $courseId, "name" => $roleName]);
        }
    }

    /**
     * Checks whether a course has a given role.
     * Option to pass either role name or role ID.
     *
     * @param int $courseId
     * @param string|null $roleName
     * @param int|null $roleId
     * @return bool
     * @throws Exception
     */
    public static function courseHasRole(int $courseId, string $roleName = null, int $roleId = null): bool
    {
        if ($roleName === null && $roleId === null)
            throw new Exception("Need either role name or ID to check whether a course has a given role.");

        $where = ["course" => $courseId];
        if ($roleName) $where["name"] = $roleName;
        if ($roleId) $where["id"] = $roleId;
        return !empty(Core::database()->select(self::TABLE_ROLE, $where));
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- User related ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets user's roles. Option to retrieve only roles' names and/or to
     * sort them hierarchly. Sorting works like this:
     *  - if only names --> with the more specific roles first, followed
     *                      by the less specific ones
     *  - else --> retrieve roles' hierarchy
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getUserRoles(<userID>, <courseID>) --> ["Student", "StudentA", "StudentA1", "StudentB"] (no fixed order)
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getUserRoles(<userID>, <courseID>, false) --> [
     *                                                  ["name" => "Student", "id" => 2, "landingPage" => null],
     *                                                  ["name" => "StudentA", "id" => 4, "landingPage" => null],
     *                                                  ["name" => "StudentA1", "id" => 5, "landingPage" => null],
     *                                                  ["name" => "StudentB", "id" => 6, "landingPage" => null]
     *                                                ] (no fixed order)
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getUserRoles(<userID>, <courseID>, true, true) --> ["StudentA1", "StudentA", "StudentB", "Student"]
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getUserRoles(<userID>, <courseID>, false, true) --> [
     *                                                                  ["name" => "Student", "id" => 2, "landingPage" => null, "children" => [
     *                                                                      ["name" => "StudentA", "id" => 4, "landingPage" => null, "children" => [
     *                                                                          ["name" => "StudentA1", "id" => 5, "landingPage" => null]
     *                                                                      ]],
     *                                                                      ["name" => "StudentB", "id" => 5, "landingPage" => null]
     *                                                                  ]]
     *                                                              ]
     *
     * @param int $userId
     * @param int $courseId
     * @param bool $onlyNames
     * @param bool $sortByHierarchy
     * @return array
     */
    public static function getUserRoles(int $userId, int $courseId, bool $onlyNames = true, bool $sortByHierarchy = false): array
    {
        if ($onlyNames) {
            $rolesNames = array_column(Core::database()->selectMultiple(
                Role::TABLE_USER_ROLE . " ur JOIN " . Role::TABLE_ROLE . " r on ur.role=r.id",
                ["ur.course" => $courseId, "ur.id" => $userId], "name"),
                "name");

            if ($sortByHierarchy) {
                $hierarchy = (new Course($courseId))->getRolesHierarchy();
                return self::sortRolesNamesByMostSpecific($hierarchy, $rolesNames);

            } else return $rolesNames;

        } else {
            $roles = Core::database()->selectMultiple(
                Role::TABLE_USER_ROLE . " ur JOIN " . Role::TABLE_ROLE . " r on ur.role=r.id",
                ["ur.course" => $courseId, "ur.id" => $userId],
                "r.id, name, landingPage"
            );
            foreach ($roles as &$role) { $role = self::parse($role); }

            if ($sortByHierarchy) {
                $rolesByName = array_combine(array_column($roles, "name"), $roles);
                $hierarchy = (new Course($courseId))->getRolesHierarchy();

                self::traverseRoles($hierarchy, function (&$role, &$parent, $key, $hasChildren, $continue) use ($rolesByName, &$hierarchy) {
                    if (!isset($rolesByName[$role["name"]])) { // not a user role
                        unset($parent[$key]);
                        $parent = array_values($parent); // NOTE: this forces re-indexing

                    } else { // a user role
                        if ($hasChildren) $continue();
                        $role["id"] = $rolesByName[$role["name"]]["id"];
                        $role["landingPage"] = $rolesByName[$role["name"]]["landingPage"];
                        if (isset($role["children"]) && count($role["children"]) == 0) unset($role["children"]);
                    }
                });
                return $hierarchy;

            } else return $roles;
        }
    }

    /**
     * Replaces user's roles in the database.
     *
     * @param int $userId
     * @param int $courseId
     * @param array $rolesNames
     * @return void
     * @throws Exception
     */
    public static function setUserRoles(int $userId, int $courseId, array $rolesNames)
    {
        // Check if roles exist in course
        foreach ($rolesNames as $roleName) {
            if (!self::courseHasRole($courseId, $roleName))
                throw new PDOException("Role with name '" . $roleName . "' doesn't exist in course with ID = " . $courseId . ".");
        }

        // Remove all user roles
        Core::database()->delete(self::TABLE_USER_ROLE, ["id" => $userId, "course" => $courseId]);

        // Add new roles
        foreach ($rolesNames as $roleName) {
            self::addRoleToUser($userId, $courseId, $roleName);
        }
    }

    /**
     * Adds a new role to a given user in a given course if it isn't
     * already added. Option to pass either role name or role ID.
     *
     * @param int $userId
     * @param int $courseId
     * @param string|null $roleName
     * @param int|null $roleId
     * @return void
     * @throws Exception
     */
    public static function addRoleToUser(int $userId, int $courseId, string $roleName = null, int $roleId = null)
    {
        if ($roleName === null && $roleId === null)
            throw new Exception("Need either role name or ID to add new role to a user.");

        if (!self::courseHasRole($courseId, $roleName, $roleId))
            throw new PDOException("Role with " . ($roleName ? "name '" . $roleName . "'" : "ID = " . $roleId) . " doesn't exist in course with ID = " . $courseId . ".");

        if (!self::userHasRole($userId, $courseId, $roleName, $roleId)) {
            if (!$roleId) $roleId = self::getRoleId($roleName, $courseId);
            Core::database()->insert(self::TABLE_USER_ROLE, [
                "id" => $userId,
                "course" => $courseId,
                "role" => $roleId
            ]);

            // Trigger student added event
            if (!$roleName) $roleName = Role::getRoleName($roleId);
            if ($roleName == "Student")
                Event::trigger(EventType::STUDENT_ADDED_TO_COURSE, $courseId, $userId);
        }
    }

    /**
     * Removes a given role from a user on a course.
     * Option to pass either role name or role ID.
     *
     * @param int $userId
     * @param int $courseId
     * @param string|null $roleName
     * @param int|null $roleId
     * @return void
     * @throws Exception
     */
    public static function removeRoleFromUser(int $userId, int $courseId, string $roleName = null, int $roleId = null)
    {
        if ($roleName === null && $roleId === null)
            throw new Exception("Need either role name or ID to add new role to a user.");

        if (!$roleName) $roleName = self::getRoleName($roleId);
        $remove = array_merge([$roleName], self::getChildrenNamesOfRole((new Course($courseId))->getRolesHierarchy(), $roleName));

        foreach ($remove as $rmRoleName) {
            $roleId = self::getRoleId($rmRoleName, $courseId);
            Core::database()->delete(self::TABLE_USER_ROLE, ["id" => $userId, "course" => $courseId, "role" => $roleId]);
        }

        // Trigger student removed event
        if ($roleName == "Student")
            Event::trigger(EventType::STUDENT_REMOVED_FROM_COURSE, $courseId, $userId);
    }

    /**
     * Checks whether a user has a given role.
     * Option to pass either role name or role ID.
     *
     * @param int $userId
     * @param int $courseId
     * @param string|null $roleName
     * @param int|null $roleId
     * @return bool
     * @throws Exception
     */
    public static function userHasRole(int $userId, int $courseId, string $roleName = null, int $roleId = null): bool
    {
        if ($roleName === null && $roleId === null)
            throw new Exception("Need either role name or ID to check whether a user has a given role.");

        if (!$roleId) $roleId = self::getRoleId($roleName, $courseId);
        $where = ["id" => $userId, "course" => $courseId, "role" => $roleId];
        return !empty(Core::database()->select(self::TABLE_USER_ROLE, $where));
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Validations ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates role name.
     *
     * @param $roleName
     * @return void
     * @throws Exception
     */
    private static function validateRoleName($roleName)
    {
        if (!is_string($roleName) || strpos($roleName, " ") !== false)
            throw new Exception("Role name '" . $roleName . "' is invalid. Role names can't be empty or have white spaces.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Traverse role's hierarchy and perform a given function.
     *
     * @param array $hierarchy
     * @param $func
     * @param ...$data
     * @return void
     */
    public static function traverseRoles(array &$hierarchy, $func, &...$data)
    {
        self::traverseRolesHelper($hierarchy, $func, $hierarchy, ...$data);
    }

    private static function traverseRolesHelper(array &$hierarchy, $func, &$parent, &...$data)
    {
        foreach ($hierarchy as $key => &$role) {
            $hasChildren = array_key_exists("children", $role);
            $continue = function(&...$data) use (&$role, $func, $hasChildren, &$parent) {
                if ($hasChildren)
                    self::traverseRolesHelper($role["children"], $func, $role["children"], ...$data);
            };
            $func($role, $parent, $key, $hasChildren, $continue, ...$data);
        }
    }

    /**
     * Sorts an array of roles' names by most specific.
     * @example ["Teacher", "Student", "StudentA", "StudentB"] --> ["StudentA", "StudentB", "Teacher", "Student"]
     *
     * @param array $hierarchy
     * @param array $rolesNames
     * @return array
     */
    public static function sortRolesNamesByMostSpecific(array $hierarchy, array $rolesNames): array
    {
        $res = [];
        self::traverseRoles($hierarchy, function ($role, $parent, $key, $hasChildren, $continue, &...$data) use ($rolesNames) {
            if ($hasChildren) $continue(...$data);
            if (in_array($role["name"], $rolesNames)) $data[0][] = $role["name"];
        }, $res);
        return $res;
    }

    /**
     * Gets children names of a given role.
     * Option to pass either role name or role ID.
     *
     * @param array $hierarchy
     * @param string|null $roleName
     * @param int|null $roleId
     * @return array
     * @throws Exception
     */
    public static function getChildrenNamesOfRole(array $hierarchy, string $roleName = null, int $roleId = null): array
    {
        if ($roleName === null && $roleId === null)
            throw new Exception("Need either role name or ID to get children of a role.");

        $children = [];
        if ($roleName === null) $roleName = self::getRoleName($roleId);
        self::traverseRoles($hierarchy, function ($role, $parent, $key, $hasChildren, $continue, &...$data) use ($roleName) {
            if ($hasChildren) {
                if ($role["name"] == $roleName || (in_array($role["name"], $data[0]) && array_key_exists("children", $role))) {
                    foreach ($role["children"] as $child) {
                        $data[0][] = $child["name"];
                    }
                }
                $continue(...$data);
            }
        }, $children);
        return $children;
    }

    /**
     * Parses a role coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $role
     * @return array
     */
    public static function parse(array $role): array
    {
        if (isset($role["id"])) $role["id"] = intval($role["id"]);
        if (isset($role["landingPage"])) $role["landingPage"] = intval($role["landingPage"]);
        if (isset($role["course"])) $role["course"] = intval($role["course"]);
        return $role;
    }
}
