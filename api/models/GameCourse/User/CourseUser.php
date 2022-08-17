<?php
namespace GameCourse\User;

use Exception;
use GameCourse\Core\Auth;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use GameCourse\Views\Page\Page;
use Utils\Utils;

/**
 * This is the CourseUser model, which implements the necessary methods
 * to interact with course users in the MySQL database.
 */
class CourseUser extends User
{
    const TABLE_COURSE_USER = "course_user";

    const HEADERS = [   // headers for import/export functionality (+ User headers)
        "isActiveInCourse", "roles"
    ];

    protected $course;

    public function __construct(int $id, Course $course)
    {
        parent::__construct($id);
        $this->course = $course;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getCourse(): Course {
        return $this->course;
    }

    public function getLastActivity(): ?string {
        return $this->getData("lastActivity");
    }

    public function getLandingPage(): ?Page
    {
        $roleNames = Role::getUserRoles($this->id, $this->course->getId(), true, true);
        foreach ($roleNames as $roleName) {
            $landingPage = Role::getRoleLandingPage(Role::getRoleId($roleName, $this->course->getId()));
            if ($landingPage) return $landingPage;
        }
        return null;
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Gets course user data from the database.
     *
     * @example getData() --> gets all course user data
     * @example getData("lastActivity") --> gets course user last time active in course
     * @example getData("name, lastActivity") --> gets course user name and last time active in course
     *
     * NOTE: field isActive is related to 'course_user' table field;
     *       when "*" passed, isActive of 'course_user' table will become isActiveInCourse
     *
     * @param string $field
     * @return array|bool|int|null
     */
    public function getData(string $field = "*")
    {
        // Split data accordingly
        $userFields = "";
        $courseUserFields = "";
        if ($field == "*") {
            $userFields = "*";
            $courseUserFields = "course, lastActivity, isActive as isActiveInCourse";

        } else {
            $fields = explode(",", $field);
            foreach ($fields as $f) {
                $f = trim($f);
                if (in_array($f, ["course", "lastActivity", "isActive"])) {
                    if ($courseUserFields != "") $courseUserFields .= ",";
                    $courseUserFields .= $f;

                } else {
                    if ($userFields != "") $userFields .= ",";
                    $userFields .= $f;
                }
            }
        }

        // Get data
        $res = [];
        if ($userFields != "") $res = parent::getData($userFields);
        if ($courseUserFields != "") {
            $table = self::TABLE_COURSE_USER;
            $where = ["id" => $this->id, "course" => $this->course->getId()];
            $res2 = Core::database()->select($table, $where, $courseUserFields);

            if ($res == []) $res = $res2;   // only got course user data
            else $res = array_merge(        // got user data as well
                is_array($res) ? $res : [$userFields => $res],
                is_array($res2) ? $res2 : [$courseUserFields => $res2]);
        }

        return is_array($res) ? self::parse($res) : self::parse(null, $res, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setLastActivity(?string $lastActivity)
    {
        $this->setData(["lastActivity" => $lastActivity]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets course user data on the database.
     *
     * @example setData(["course" => 1])
     * @example setData(["course" => 1, "isActive" => true])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        // Validate data
        if (key_exists("lastActivity", $fieldValues)) self::validateDateTime($fieldValues["lastActivity"]);
        if (key_exists("isActive", $fieldValues)) self::validateState($this->id, $fieldValues["isActive"]);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_COURSE_USER, $fieldValues, ["id" => $this->id, "course" => $this->course->getId()]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a course user by its ID.
     * Returns null if course user doesn't exist.
     *
     * @param int $userId
     * @param Course $course
     * @return CourseUser|null
     */
    public static function getCourseUserById(int $userId, Course $course): ?CourseUser
    {
        $courseUser = new CourseUser($userId, $course);
        if ($courseUser->exists()) return $courseUser;
        else return null;
    }

    /**
     * Gets a course user by its username.
     * Returns null if course user doesn't exist.
     *
     * @param string $username
     * @param Course $course
     * @param string|null $authService
     * @return CourseUser|null
     * @throws Exception
     */
    public static function getCourseUserByUsername(string $username, Course $course, string $authService = null): ?CourseUser
    {
        $table = self::TABLE_COURSE_USER . " cu LEFT JOIN " . Auth::TABLE_AUTH . " a on cu.id=a.user";
        if (!$authService) {
            $userIds = Core::database()->selectMultiple($table, ["username" => $username], "a.user");
            $nrCourseUsersWithUsername = count($userIds);

            if ($nrCourseUsersWithUsername > 1)
                throw new Exception("Cannot get course user by username: there's multiple users with username '" . $username . "' in course with ID = " . $course->getId() . ".");

            $userId = $nrCourseUsersWithUsername < 1 ? null : intval($userIds[0]);

        } else {
            self::validateAuthService($authService);
            $userId = intval(Core::database()->select($table, ["username" => $username, "auth_service" => $authService], "user"));
        }

        if (!$userId) return null;
        else return new CourseUser($userId, $course);
    }

    /**
     * Gets a course user by its e-mail.
     * Returns null if course user doesn't exist.
     *
     * @param string $email
     * @param Course $course
     * @return CourseUser|null
     */
    public static function getCourseUserByEmail(string $email, Course $course): ?CourseUser
    {
        $table = self::TABLE_COURSE_USER . " cu LEFT JOIN " . self::TABLE_USER . " u on cu.id=u.id";
        $userId = intval(Core::database()->select($table, ["email" => $email], "u.id"));
        if (!$userId) return null;
        else return new CourseUser($userId, $course);
    }

    /**
     * Gets a course user by its student number.
     * Returns null if course user doesn't exist.
     *
     * @param int $studentNumber
     * @param Course $course
     * @return CourseUser|null
     */
    public static function getCourseUserByStudentNumber(int $studentNumber, Course $course): ?CourseUser
    {
        $table = self::TABLE_COURSE_USER . " cu LEFT JOIN " . self::TABLE_USER . " u on cu.id=u.id";
        $userId = intval(Core::database()->select($table, ["studentNumber" => $studentNumber], "u.id"));
        if (!$userId) return null;
        else return new CourseUser($userId, $course);
    }

    /**
     * Updates course user's lastActivity to current time.
     *
     * @return void
     * @throws Exception
     */
    public function refreshActivity()
    {
        $this->setLastActivity(date("Y-m-d H:i:s", time()));
    }

    /**
     * Checks whether course user is a teacher of the course.
     * @return bool
     * @throws Exception
     */
    public function isTeacher(): bool
    {
        return $this->hasRole("Teacher");
    }

    /**
     * Checks whether course user is a student of the course.
     * @return bool
     * @throws Exception
     */
    public function isStudent(): bool
    {
        return $this->hasRole("Student");
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- User Manipulation ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a course user to the database.
     * Option to pass a role name or ID to be added as well.
     * Returns the newly created course user.
     *
     * @param int $userId
     * @param int $courseId
     * @param string|null $roleName
     * @param int|null $roleId
     * @param bool $isActive
     * @return CourseUser
     * @throws Exception
     */
    public static function addCourseUser(int $userId, int $courseId, string $roleName = null, int $roleId = null, bool $isActive = true): CourseUser
    {
        // Create new course user
        self::validateState($userId, $isActive);
        Core::database()->insert(self::TABLE_COURSE_USER, [
            "id" => $userId,
            "course" => $courseId,
            "isActive" => +$isActive
        ]);
        $courseUser = new CourseUser($userId, new Course($courseId));

        // Add role
        if ($roleName !== null || $roleId !== null)
            $courseUser->addRole($roleName, $roleId);

        return $courseUser;
    }

    /**
     * Deletes a course user from the database.
     *
     * @param int $userId
     * @param int $courseId
     * @return void
     */
    public static function deleteCourseUser(int $userId, int $courseId)
    {
        Core::database()->delete(self::TABLE_COURSE_USER, ["id" => $userId, "course" => $courseId]);
    }

    /**
     * Checks whether course user exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("course"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Roles ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets course user's roles. Option to retrieve only roles' names and/or to
     * sort them hierarchly. Sorting works like this:
     *  - if only names --> with the more specific roles first, followed
     *                      by the less specific ones
     *  - else --> retrieve roles' hierarchy
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getRoles() --> ["Student", "StudentA", "StudentA1", "StudentB"] (no fixed order)
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getRoles(false) --> [
     *                                  ["name" => "Student", "id" => 2, "landingPage" => null],
     *                                  ["name" => "StudentA", "id" => 4, "landingPage" => null],
     *                                  ["name" => "StudentA1", "id" => 5, "landingPage" => null],
     *                                  ["name" => "StudentB", "id" => 6, "landingPage" => null]
     *                              ] (no fixed order)
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getRoles(true, true) --> ["StudentA1", "StudentA", "StudentB", "Student"]
     *
     * @example User Roles: Student, StudentA, StudentA1, StudentB
     *          getRoles(false, true) --> [
     *                                      ["name" => "Student", "id" => 2, "landingPage" => null, "children" => [
     *                                          ["name" => "StudentA", "id" => 4, "landingPage" => null, "children" => [
     *                                              ["name" => "StudentA1", "id" => 5, "landingPage" => null]
     *                                          ]],
     *                                          ["name" => "StudentB", "id" => 5, "landingPage" => null]
     *                                      ]]
     *                                    ]
     *
     * @param bool $onlyNames
     * @param bool $sortByHierarchy
     * @return array
     */
    public function getRoles(bool $onlyNames = true, bool $sortByHierarchy = false): array
    {
        return Role::getUserRoles($this->id, $this->course->getId(), $onlyNames, $sortByHierarchy);
    }

    /**
     * Replaces user's roles in the database.
     *
     * @param array $rolesNames
     * @return void
     * @throws Exception
     */
    public function setRoles(array $rolesNames)
    {
        Role::setUserRoles($this->id, $this->course->getId(), $rolesNames);
    }

    /**
     * Adds a new role to user if it isn't already added.
     * Option to pass either role name or role ID.
     *
     * @param string|null $roleName
     * @param int|null $roleId
     * @return void
     * @throws Exception
     */
    public function addRole(string $roleName = null, int $roleId = null)
    {
        Role::addRoleToUser($this->id, $this->course->getId(), $roleName, $roleId);
    }

    /**
     * Removes a given role from user.
     * Option to pass either role name or role ID.
     *
     * @param string|null $roleName
     * @param int|null $roleId
     * @return void
     * @throws Exception
     */
    public function removeRole(string $roleName = null, int $roleId = null)
    {
        Role::removeRoleFromUser($this->id, $this->course->getId(), $roleName, $roleId);
    }

    /**
     * Checks whether course user has a given role.
     *
     * @param string|null $roleName
     * @param int|null $roleId
     * @return bool
     * @throws Exception
     */
    public function hasRole(string $roleName = null, int $roleId = null): bool
    {
        return Role::userHasRole($this->id, $this->course->getId(), $roleName, $roleId);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports users into a given course from a .csv file.
     * If a user is not yet in the system, it will add it first.
     * Returns the nr. of users imported.
     *
     * @param int $courseId
     * @param string $file
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importCourseUsers(int $courseId, string $file, bool $replace = true): int
    {
        $course = new Course($courseId);
        if (!$course->exists())
            throw new Exception("Course with ID = " . $courseId . " doesn't exist.");

        return Utils::importFromCSV(array_merge(parent::HEADERS, self::HEADERS), function ($user, $indexes) use ($course, $replace) {
            $name = $user[$indexes["name"]];
            $email = $user[$indexes["email"]];
            $major = $user[$indexes["major"]];
            $nickname = $user[$indexes["nickname"]];
            $studentNumber = $user[$indexes["studentNumber"]];
            $username = $user[$indexes["username"]];
            $authService = $user[$indexes["auth_service"]];
            $isAdmin = $user[$indexes["isAdmin"]];
            $isActive = $user[$indexes["isActive"]];
            $isActiveInCourse = $user[$indexes["isActiveInCourse"]];
            $roles = $user[$indexes["roles"]];

            // Add/update user in the system
            $user = self::getUserByUsername($username, $authService) ?? self::getUserByStudentNumber($studentNumber);
            if ($user) {  // user already exists
                if ($replace)  // replace
                    $user = $user->editUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);

            } else {  // user doesn't exist
                $user = User::addUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
            }

            // Add/update user in the course
            $courseUser = new CourseUser($user->getId(), $course);
            if ($courseUser->exists()) { // user already added to course
                if ($replace) { // replace
                    $courseUser->setLastActivity(null);
                    $courseUser->setActive($isActiveInCourse);
                    if ($roles) // set user roles in course
                        $courseUser->setRoles(array_map("trim", preg_split("/\s+/", $roles)));
                }

            } else { // user not yet added to course
                $courseUser = $course->addUserToCourse($user->getId(), null, null, $isActiveInCourse);
                if ($roles) // set user roles in course
                    $courseUser->setRoles(array_map("trim", preg_split("/\s+/", $roles)));
                return 1;
            }
            return 0;
        }, $file);
    }

    /**
     * Exports users from a given course into a .csv file.
     *
     * @param int $courseId
     * @return string
     * @throws Exception
     */
    public static function exportCourseUsers(int $courseId): string
    {
        $course = new Course($courseId);
        if (!$course->exists())
            throw new Exception("Course with ID = " . $courseId . " doesn't exist.");

        return Utils::exportToCSV($course->getCourseUsers(), function ($user) use ($course) {
            return [$user["name"], $user["email"], $user["major"], $user["nickname"], $user["studentNumber"],
                $user["username"], $user["auth_service"], +$user["isAdmin"], +$user["isActive"],
                +$user["isActiveInCourse"], implode(" ", (new CourseUser($user["id"], $course))->getRoles())];
        }, array_merge(parent::HEADERS, self::HEADERS));
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validate datetime.
     *
     * @param $dateTime
     * @return void
     * @throws Exception
     */
    private static function validateDateTime($dateTime)
    {
        if (is_null($dateTime)) return;
        if (!is_string($dateTime) || !Utils::isValidDate($dateTime, "Y-m-d H:i:s"))
            throw new Exception("Datetime '" . $dateTime . "' should be in format 'yyyy-mm-dd HH:mm:ss'");
    }

    /**
     * Validate user is active in the system when trying to
     * set course user as active.
     *
     * @param int $userId
     * @param bool $isActive
     * @return void
     * @throws Exception
     */
    private static function validateState(int $userId, bool $isActive)
    {
        // If active, check that user is active in the system
        if ($isActive) {
            $user = User::getUserById($userId);
            if ($user && !$user->isActive())
                throw new Exception("User with ID = " . $userId . " must be active in the system.");
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a course user coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $user
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|null
     */
    public static function parse(array $user = null, $field = null, string $fieldName = null)
    {
        if ($user) {
            $user = parent::parse($user);
            if (isset($user["course"])) $user["course"] = intval($user["course"]);
            if (isset($user["isActive"])) $user["isActive"] = boolval($user["isActive"]);
            if (isset($user["isActiveInCourse"])) $user["isActiveInCourse"] = boolval($user["isActiveInCourse"]);
            return $user;

        } else {
            if ($fieldName == "course") return intval($field);
            if ($fieldName == "isActive") return boolval($field);
            if ($fieldName == "isActiveInCourse") return boolval($field);
            return $field;
        }
    }
}
