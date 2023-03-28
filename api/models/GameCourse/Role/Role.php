<?php
namespace GameCourse\Role;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\Adaptation\GameElement;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Page\Page;
use Utils\Utils;

/**
 * This is the Role model, which implements the necessary methods
 * to interact with roles in the MySQL database.
 */
class Role
{
    const TABLE_ROLE = "role";
    const TABLE_USER_ROLE = "user_role";


    const DEFAULT_ROLES = ["Teacher", "Student", "Watcher"];  // default roles for each course

    const ADAPTATION_ROLE = "Adaptation";

    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Setup ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Registers default roles in the system.
     *
     * @return void
     * @throws Exception
     */
    public static function setupRoles()
    {
        Core::database()->setForeignKeyChecks(false);
        self::setCourseRoles(0, self::DEFAULT_ROLES);
        Core::database()->setForeignKeyChecks(true);
    }

    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a role ID of a given course by role name.
     *
     * @param string $roleName
     * @param int $courseId
     * @return int
     * @throws Exception
     */
    public static function getRoleId(string $roleName, int $courseId): int
    {
        $id = intval(Core::database()->select(self::TABLE_ROLE, ["course" => $courseId, "name" => $roleName], "id"));
        if (!$id) throw new Exception("Role with name '" . $roleName . "' doesn't exist for course with ID = " . $courseId . ".");
        return $id;
    }

    /**
     * Gets a role name by role ID.
     *
     * @param int $roleId
     * @return string
     * @throws Exception
     */
    public static function getRoleName(int $roleId): string
    {
        $roleName = Core::database()->select(self::TABLE_ROLE, ["id" => $roleId], "name");
        if (!$roleName) throw new Exception("Role with ID = " . $roleId . " doesn't exist.");
        return $roleName;
    }

    /**
     * Gets a role landing page by role ID.
     *
     * @param int|null $roleId
     * @return Page
     * @throws Exception
     */
    public static function getRoleLandingPage(int $roleId = null): ?Page
    {
        $pageId = Core::database()->select(self::TABLE_ROLE, ["id" => $roleId], "landingPage");
        if ($pageId === false) throw new Exception("Role with ID = " . $roleId . " doesn't exist.");
        return $pageId ? Page::getPageById($pageId) : null;
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
     *          getRolesNamesInHierarchy($hierarchy) --> ["Teacher", "StudentA", "StudentB", "Student", "Watcher"]
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
     *
     * @param int $courseId
     * @throws Exception
     */
    public static function addDefaultRolesToCourse(int $courseId)
    {
        // Add default roles
        self::setCourseRoles($courseId, self::DEFAULT_ROLES);

        // Update roles hierarchy
        $hierarchy = array_map(function ($role) { return ["name" => $role]; }, self::DEFAULT_ROLES);
        (new Course($courseId))->setRolesHierarchy($hierarchy);
    }

    /**
     * Adds adaptation roles to a given course - comes from each module
     * Notice: array $roles should only have 1 parent!
     *
     * @param int $courseId
     * @param string $moduleId
     * @param string $parent
     * @param array|null $children
     * @throws Exception
     */
    public static function addAdaptationRolesToCourse(int $courseId, string $moduleId, string $parent, array $children = null)
    {
        $course = new Course($courseId);
        $rolesNames = self::getCourseRoles($courseId);

        $hierarchy = $course->getRolesHierarchy();
        $studentIndex = array_search("Student", self::DEFAULT_ROLES);

        if (!in_array(self::ADAPTATION_ROLE, $rolesNames)){
            self::addRoleToCourse($courseId, self::ADAPTATION_ROLE); // Add adaptation role to course

            // Update hierarchy
            $hierarchy[$studentIndex]["children"][] = ["name" => self::ADAPTATION_ROLE];
            $course->setRolesHierarchy($hierarchy);
        }

        // Add parent and update hierarchy
        $adaptationIndex = array_search(self::ADAPTATION_ROLE, $hierarchy[$studentIndex]["children"]);
        if (!$course->hasRole($parent)) {
            self::addRoleToCourse($courseId, $parent, null, null, $moduleId);

            // Update hierarchy
            $hierarchy = $course->getRolesHierarchy();
            $hierarchy[$studentIndex]["children"][$adaptationIndex]["children"][] = ["name" => $parent];
            $course->setRolesHierarchy($hierarchy);
        }

        // Add children and update hierarchy
        $hierarchy = $course->getRolesHierarchy();
        $parentIndex = array_search($parent, $hierarchy[$studentIndex]["children"][$adaptationIndex]["children"]);
        foreach ($children as $child){
            if (!$course->hasRole($child)){
                self::addRoleToCourse($courseId, $child, null, null, $moduleId);

                $hierarchy[$studentIndex]["children"][$adaptationIndex]["children"][$parentIndex]["children"][] = ["name" => $child];
            }
        }
        $course->setRolesHierarchy($hierarchy);

        /* FIXME: Delete later
        $course->setRolesHierarchy($hierarchy);

        // Update roles hierarchy
        $hierarchy = $course->getRolesHierarchy();

        foreach ($hierarchy as $key => $value) {
            if ($value["name"] == Role::ADAPTATION_ROLE){
                $hierarchy[$key]["children"][] = ["name" => $parent,
                    "children" => array_map(function ($child) {return ["name" => $child]; }, $children)];
                break;
            }
        }
        $course->setRolesHierarchy($hierarchy);*/

    }

    /**
     * Removes adaptation roles from a course, including its children.
     * NOTE: it doesn't update roles hierarchy
     *
     * @param int $courseId
     * @param string|null $moduleId
     * @param string $parent
     * @return void
     * @throws Exception
     */
    public static function removeAdaptationRolesFromCourse(int $courseId, string $moduleId, string $parent){

        self::removeRoleFromCourse($courseId, null, null, $moduleId);

        $course = new Course($courseId);

        // Update hierarchy
        $hierarchy = $course->getRolesHierarchy();
        $studentIndex = array_search("Student", self::DEFAULT_ROLES);
        $adaptationIndex = array_search(self::ADAPTATION_ROLE, $hierarchy[$studentIndex]["children"]);

        // sees if adaptation roles has children
        if ($adaptationIndex && in_array("children", $hierarchy[$studentIndex]["children"][$adaptationIndex])){
            foreach($hierarchy[$studentIndex]["children"][$adaptationIndex]["children"] as $i => $child){
                if ($child["name"] == $parent){
                    array_splice($hierarchy[$studentIndex]["children"][$adaptationIndex]["children"], $i, 1);
                    break;
                }
            }
            $course->setRolesHierarchy($hierarchy);
        } else if ($adaptationIndex) {
            // if there are no children inside Adaptation role then remove children array
            array_splice($hierarchy[$studentIndex]["children"][$adaptationIndex], 1, 1);
        }

        /*
        foreach ($hierarchy as $k => $value){
            // sees if adaptation roles has children
            if ($value["name"] == self::ADAPTATION_ROLE && in_array("children", array_keys($value))){

                // iterates through children (at this point will be game elements "badges", "leaderboard" etc
                foreach ($value["children"] as $key => $item){
                    // if item is the desired adaptation role to remove then splice
                    if ($item["name"] == $parent){
                        array_splice($hierarchy[$k]["children"], $key, 1);
                        break;
                    }
                }

                // if there are no children inside Adaptation role then remove children array
                if (count($hierarchy[$k]["children"]) == 0){
                    array_splice($hierarchy[$k], 1, 1);
                }
            }
        }

        $course->setRolesHierarchy($hierarchy);*/
    }

    /**
     * Gets all adaptation roles.
     * If $onlyParents is true then it only returns parents of adaptation roles ["Badges", "Leaderboard"]
     * Default: Gets all adaptation roles (First parents then children)
     * ["Badges", "Leaderboard", "B001", "B002", "LB001", "LB002"]
     *
     * @param int $courseId
     * @param bool $onlyParents (optional)
     * @return array
     * @throws Exception
     */
    public static function getAdaptationCourseRoles(int $courseId, bool $onlyParents = false): array {
        $response = GameElement::getGameElements($courseId);

        if (!$onlyParents) {
           //$roles = self::getCourseRoles($courseId, false, true);

           $course = new Course($courseId);
           $hierarchy = $course->getRolesHierarchy();
           $studentIndex = array_search("Student", self::DEFAULT_ROLES);
           $adaptationIndex = array_search(self::ADAPTATION_ROLE, $hierarchy[$studentIndex]["children"]);

           if ($adaptationIndex && in_array("children", $hierarchy[$studentIndex]["children"][$adaptationIndex])){
               foreach ($hierarchy[$studentIndex]["children"][$adaptationIndex]["children"] as $parents){
                   foreach ($parents["children"] as $child){
                       array_push($response, $child["name"]);
                   }
               }
           }

           /*
            foreach ($roles as $role) {
               $personalizationIndex = array_search(self::ADAPTATION_ROLE, $role);

               if ($personalizationIndex && in_array("children", array_keys($role))) {
                   foreach ($role["children"] as $value) {

                       if ($value["module"] && $value["children"]) {
                           foreach ($value["children"] as $child){
                               array_push($response, $child["name"]);
                           }
                       }
                   }
                   break;
               }
           }*/
        }
        return $response;
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
     *                                                  ["name" => "Watcher", "id" => 3, "landingPage" => null, "module" => null],
     *                                                  ["name" => "Student", "id" => 2, "landingPage" => null, "module" => null],
     *                                                  ["name" => "StudentB", "id" => 5, "landingPage" => null, "module" => null],
     *                                                  ["name" => "StudentA", "id" => 4, "landingPage" => null, "module" => null],
     *                                                  ["name" => "Teacher", "id" => 1, "landingPage" => null, "module" => null]
     *                                                ] (no fixed order)
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getCourseRoles(<courseID>, true, true) --> ["Teacher", "StudentA", "StudentB", "Student", "Watcher"]
     *
     * @example Course Roles: Teacher, Student, StudentA, StudentB, Watcher
     *          getCourseRoles(<courseID>, false, true) --> [
     *                                                          ["name" => "Teacher", "id" => 1, "landingPage" => null, "module" => null],
     *                                                          ["name" => "Student", "id" => 2, "landingPage" => null, "module" => null, "children" => [
     *                                                              ["name" => "StudentA", "id" => 4, "landingPage" => null, "module" => null],
     *                                                              ["name" => "StudentB", "id" => 5, "landingPage" => null, "module" => null]
     *                                                          ]],
     *                                                          ["name" => "Watcher", "id" => 3, "landingPage" => null, "module" => null]
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
            $rolesNames = array_column(Core::database()->selectMultiple(self::TABLE_ROLE, ["course" => $courseId], "name", "id"), "name");
            if ($sortByHierarchy) {
                $hierarchy = (new Course($courseId))->getRolesHierarchy();
                return self::sortRolesNamesByMostSpecific($hierarchy, $rolesNames);

            } else return $rolesNames;

        } else {
            if ($sortByHierarchy) {
                $roles = Core::database()->selectMultiple(self::TABLE_ROLE, ["course" => $courseId], "*", "id");
                foreach ($roles as &$role) { $role = self::parse($role); }
                $rolesByName = array_combine(array_column($roles, "name"), $roles);
                $hierarchy = (new Course($courseId))->getRolesHierarchy();
                self::traverseRoles($hierarchy, function (&$role, &$parent, $key, $hasChildren, $continue) use ($rolesByName, &$hierarchy) {
                    if ($hasChildren) $continue();
                    $role["id"] = $rolesByName[$role["name"]]["id"];
                    $role["landingPage"] = $rolesByName[$role["name"]]["landingPage"];
                    $role["module"] = $rolesByName[$role["name"]]["module"];
                });
                return $hierarchy;

            } else {
                $roles = Core::database()->selectMultiple(self::TABLE_ROLE, ["course" => $courseId], "id, name, landingPage, module", "id");
                foreach ($roles as &$role) { $role = self::parse($role); }
                return $roles;
            }
        }
    }

    /**
     * Replaces course's roles in the database.
     * NOTE: it doesn't update roles hierarchy
     *
     * WARNING: be careful using this function as it will delete all
     *          roles in the course, and subsequently every data that
     *          depends on it on the database; consider using function
     *          'updateCourseRoles' declared also on this class
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

        // Remove all course roles & aspects
        Core::database()->delete(self::TABLE_ROLE, ["course" => $courseId]);
        Aspect::deleteAllAspectsInCourse($courseId);

        // Add default aspect
        Aspect::addAspect($courseId, null, null);

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
     * @param string|null $moduleId
     * @return void
     * @throws Exception
     */
    public static function addRoleToCourse(int $courseId, string $roleName, string $landingPageName = null, int $landingPageId = null, string $moduleId = null)
    {
        self::trim($roleName);

        if (!self::courseHasRole($courseId, $roleName)) {
            self::validateRoleName($courseId, $roleName);

            // Add role
            $data = ["course" => $courseId, "name" => $roleName];
            if ($landingPageName !== null) $landingPageId = Page::getPageByName($courseId, $landingPageName)->getId();
            if ($landingPageId !== null) $data["landingPage"] = $landingPageId;
            if ($moduleId !== null) $data["module"] = $moduleId;
            $roleId = Core::database()->insert(self::TABLE_ROLE, $data);

            // Add default aspects of role
            Aspect::addAspect($courseId, $roleId, null);
            Aspect::addAspect($courseId, null, $roleId);
            Aspect::addAspect($courseId, $roleId, $roleId);

            // Add combinations of aspects with other roles
            $courseRoles = self::getCourseRoles($courseId, false);
            foreach ($courseRoles as $role) {
                if ($role["id"] != $roleId) {
                    Aspect::addAspect($courseId, $role["id"], $roleId);
                    Aspect::addAspect($courseId, $roleId, $role["id"]);
                }
            }
        }
    }

    /**
     * Updates course's roles in the database, without fully replacing them.
     * NOTE: it doesn't update roles hierarchy
     *
     * @param int $courseId
     * @param array $roles
     * @return void
     * @throws Exception
     */
    public static function updateCourseRoles(int $courseId, array $roles)
    {
        // Remove roles that got deleted
        $oldRoles = Role::getCourseRoles($courseId, false);
        foreach ($oldRoles as $oldRole) {
            $exists = !empty(array_filter($roles, function ($role) use ($oldRole) {
                return isset($role["id"]) && $role["id"] == $oldRole["id"];
            }));
            if (!$exists) self::removeRoleFromCourse($courseId, $oldRole["name"], $oldRole["id"]);
        }

        // Update roles
        foreach ($roles as $role) {
            if (isset($role["id"])) { // update
                self::validateRoleName($courseId, trim($role["name"]), $role["id"]);
                Core::database()->update(self::TABLE_ROLE, [
                    "name" => trim($role["name"]),
                    "landingPage" => $role["landingPage"] ?? null
                ], ["course" => $courseId, "id" => $role["id"]]);

            } else { // add
                self::addRoleToCourse($courseId, $role["name"], null, $role["landingPage"] ?? null);
            }
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
     * @param string|null $moduleId
     * @return void
     * @throws Exception
     */
    public static function removeRoleFromCourse(int $courseId, string $roleName = null, int $roleId = null, string $moduleId = null)
    {
        if ($roleName === null && $roleId === null && $moduleId === null)
            throw new Exception("Need either role name, role ID or module ID to remove roles from course.");

        if ($moduleId) {
            Core::database()->delete(self::TABLE_ROLE, ["course" => $courseId, "module" => $moduleId]);
            return;
        }

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
     *                                                  ["name" => "Student", "id" => 2, "landingPage" => null, "module" => null],
     *                                                  ["name" => "StudentA", "id" => 4, "landingPage" => null, "module" => null],
     *                                                  ["name" => "StudentA1", "id" => 5, "landingPage" => null, "module" => null],
     *                                                  ["name" => "StudentB", "id" => 6, "landingPage" => null, "module" => null]
     *                                                ] (no fixed order)
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getUserRoles(<userID>, <courseID>, true, true) --> ["StudentA1", "StudentA", "StudentB", "Student"]
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getUserRoles(<userID>, <courseID>, false, true) --> [
     *                                                                  ["name" => "Student", "id" => 2, "landingPage" => null, "module" => null, "children" => [
     *                                                                      ["name" => "StudentA", "id" => 4, "landingPage" => null, "module" => null, "children" => [
     *                                                                          ["name" => "StudentA1", "id" => 5, "landingPage" => null, "module" => null]
     *                                                                      ]],
     *                                                                      ["name" => "StudentB", "id" => 5, "landingPage" => null, "module" => null]
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
                ["ur.course" => $courseId, "ur.user" => $userId], "name"),
                "name");

            if ($sortByHierarchy) {
                $hierarchy = (new Course($courseId))->getRolesHierarchy();
                return self::sortRolesNamesByMostSpecific($hierarchy, $rolesNames);

            } else return $rolesNames;

        } else {
            $roles = Core::database()->selectMultiple(
                Role::TABLE_USER_ROLE . " ur JOIN " . Role::TABLE_ROLE . " r on ur.role=r.id",
                ["ur.course" => $courseId, "ur.user" => $userId],
                "r.id, name, landingPage, module"
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
                        $role["module"] = $rolesByName[$role["name"]]["module"];
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
                throw new Exception("Role with name '" . $roleName . "' doesn't exist in course with ID = " . $courseId . ".");
        }

        // Remove all user roles
        Core::database()->delete(self::TABLE_USER_ROLE, ["user" => $userId, "course" => $courseId]);

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
            throw new Exception("Role with " . ($roleName ? "name '" . $roleName . "'" : "ID = " . $roleId) . " doesn't exist in course with ID = " . $courseId . ".");

        if (!self::userHasRole($userId, $courseId, $roleName, $roleId)) {
            if (!$roleId) $roleId = self::getRoleId($roleName, $courseId);
            Core::database()->insert(self::TABLE_USER_ROLE, [
                "user" => $userId,
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
            Core::database()->delete(self::TABLE_USER_ROLE, ["user" => $userId, "course" => $courseId, "role" => $roleId]);
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
        $where = ["user" => $userId, "course" => $courseId, "role" => $roleId];
        return !empty(Core::database()->select(self::TABLE_USER_ROLE, $where));
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Validations ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates role name.
     *
     * @param int $courseId
     * @param $roleName
     * @param int|null $roleId
     * @return void
     * @throws Exception
     */
    private static function validateRoleName(int $courseId, $roleName, int $roleId = null)
    {
        if (!is_string($roleName) || strpos($roleName, " ") !== false)
            throw new Exception("Role name '" . $roleName . "' is invalid. Role names can't be empty or have white spaces.");

        if (iconv_strlen($roleName) > 50)
            throw new Exception("Role name is too long: maximum of 50 characters.");

        $whereNot = [];
        if ($roleId) $whereNot[] = ["id", $roleId];
        $roleNames = array_column(Core::database()->selectMultiple(self::TABLE_ROLE, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($roleName, $roleNames))
            throw new Exception("Duplicate role name: '$roleName'");
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
                    self::traverseRoles($role["children"], $func, ...$data);
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
     * Option to pass either role name or role ID, and to only
     * retrieve direct children of role.
     *
     * @param array $hierarchy
     * @param string|null $roleName
     * @param int|null $roleId
     * @param bool $onlyDirectChildren
     * @return array
     * @throws Exception
     */
    public static function getChildrenNamesOfRole(array $hierarchy, string $roleName = null, int $roleId = null, bool $onlyDirectChildren = false): array
    {
        if ($roleName === null && $roleId === null)
            throw new Exception("Need either role name or ID to get children of a role.");

        $children = [];
        if ($roleName === null) $roleName = self::getRoleName($roleId);
        self::traverseRoles($hierarchy, function ($role, $parent, $key, $hasChildren, $continue, &...$data) use ($roleName, $onlyDirectChildren) {
            if ($hasChildren) {
                if ($role["name"] == $roleName || (in_array($role["name"], $data[0]) && array_key_exists("children", $role))) {
                    foreach ($role["children"] as $child) {
                        $data[0][] = $child["name"];
                    }
                }
                if (!$onlyDirectChildren) $continue(...$data);
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
    private static function parse(array $role = null): array
    {
        $intValues = ["id", "landingPage", "course"];

        return Utils::parse(["int" => $intValues], $role);
    }

    /**
     * Trims rule parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name"];
        Utils::trim($params, ...$values);
    }
}
