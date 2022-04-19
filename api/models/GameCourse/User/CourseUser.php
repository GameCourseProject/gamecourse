<?php
namespace GameCourse\User;

use Error;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
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

    public function getPreviousActivity(): ?string {
        return $this->getData("previousActivity");
    } // FIXME: not really being used for anything

    public function getLandingPage(): array
    {
        // TODO
    }

    public function isActive(): bool
    {
        return boolval($this->getData("isActive"));
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
     * @return mixed|void
     */
    public function getData(string $field = "*")
    {
        // Split data accordingly
        $userFields = "";
        $courseUserFields = "";
        if ($field == "*") {
            $userFields = "*";
            $courseUserFields = "course, lastActivity, previousActivity, isActive as isActiveInCourse";

        } else {
            $fields = explode(",", $field);
            foreach ($fields as $f) {
                $f = trim($f);
                if (in_array($f, ["course", "lastActivity", "previousActivity", "isActive"])) {
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

        // Parse to appropriate types
        if (isset($res["course"])) $res["course"] = intval($res["course"]);
        if (isset($res["isActiveInCourse"])) $res["isActiveInCourse"] = boolval($res["isActiveInCourse"]);
        return $res;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setLastActivity(?string $lastActivity)
    {
        self::validateDateTime($lastActivity);
        $this->setData(["lastActivity" => $lastActivity]);
    }

    public function setPreviousActivity(?string $previousActivity)
    {
        self::validateDateTime($previousActivity);
        $this->setData(["previousActivity" => $previousActivity]);
    }

    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets course user data on the database.
     * @example setData(["course" => 1])
     * @example setData(["course" => 1, "isActive" => true])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        if (key_exists("lastActivity", $fieldValues)) self::validateDateTime($fieldValues["lastActivity"]);
        if (key_exists("previousActivity", $fieldValues)) self::validateDateTime($fieldValues["previousActivity"]);

        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_COURSE_USER, $fieldValues, ["id" => $this->id, "course" => $this->course->getId()]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Updates course user's lastActivity to current time and
     * previousActivity to previous value of lastActivity.
     *
     * @return void
     */
    public function refreshActivity()
    {
        $lastActivity = $this->getLastActivity();
        $this->setPreviousActivity($lastActivity);
        $this->setLastActivity(date("Y-m-d H:i:s", time()));
    }

    /**
     * Checks whether course user is a teacher of the course.
     * @return bool
     */
    public function isTeacher(): bool
    {
        return $this->hasRole("Teacher");
    }

    /**
     * Checks whether course user is a student of the course.
     * @return bool
     */
    public function isStudent(): bool
    {
        return $this->hasRole("Student");
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- User Manipulation ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a course user to the database. Option to pass a role
     * name or ID to be added as well.
     * Returns the newly created course user.
     *
     * @param int $userId
     * @param int $courseId
     * @param string|null $roleName
     * @param int|null $roleId
     * @return CourseUser
     */
    public static function addCourseUser(int $userId, int $courseId, string $roleName = null, int $roleId = null): CourseUser
    {
        // Create new course user
        Core::database()->insert(self::TABLE_COURSE_USER, ["id" => $userId, "course" => $courseId]);
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
     * sort them hierarchly, i.e. with the more specific roles first, followed
     * by the less specific ones.
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
     */
    public static function importCourseUsers(int $courseId, string $file, bool $replace = true): int
    {
        $course = new Course($courseId);
        if (!$course->exists())
            throw new Error("Course with ID = " . $courseId . " doesn't exist.");

        $nrUsersImported = 0;
        if (empty($file)) return $nrUsersImported;
        $separator = Utils::detectSeparator($file);

        // NOTE: this order must match the order in the file
        $headers = array_merge(parent::HEADERS, self::HEADERS);

        $nameIndex = array_search("name", $headers);
        $emailIndex = array_search("email", $headers);
        $majorIndex = array_search("major", $headers);
        $nicknameIndex = array_search("nickname", $headers);
        $studentNumberIndex = array_search("studentNumber", $headers);
        $usernameIndex = array_search("username", $headers);
        $authServiceIndex = array_search("authentication_service", $headers);
        $isAdminIndex = array_search("isAdmin", $headers);
        $isActiveIndex = array_search("isActive", $headers);
        $isActiveInCourseIndex = array_search("isActiveInCourse", $headers);
        $rolesIndex = array_search("roles", $headers);

        // Filter empty lines
        $lines = array_filter(explode("\n", $file), function ($line) { return !empty($line); });

        if (count($lines) > 0) {
            // Check whether 1st line holds headers and ignore them
            $firstLine = array_map('trim', explode($separator, trim($lines[0])));
            if (in_array($headers[0], $firstLine)) array_shift($lines);

            // Import each user
            foreach ($lines as $line) {
                $user = array_map('trim', explode($separator, trim($line)));

                $name = $user[$nameIndex];
                $email = $user[$emailIndex];
                $major = $user[$majorIndex];
                $nickname = $user[$nicknameIndex];
                $studentNumber = $user[$studentNumberIndex];
                $username = $user[$usernameIndex];
                $authService = $user[$authServiceIndex];
                $isAdmin = $user[$isAdminIndex];
                $isActive = $user[$isActiveIndex];
                $isActiveInCourse = $user[$isActiveInCourseIndex];
                $roles = $user[$rolesIndex];

                // Add/update user in the system
                $user = self::getUserByUsername($username) ?? self::getUserByStudentNumber($studentNumber);
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
                        $courseUser->setPreviousActivity(null);
                        $courseUser->setActive($isActiveInCourse);
                        if ($roles) // set user roles in course
                            $courseUser->setRoles(array_map("trim", preg_split("/\s+/", $roles)));
                    }

                } else { // user not yet added to course
                    $courseUser = CourseUser::addCourseUser($user->getId(), $courseId);
                    $courseUser->setActive($isActiveInCourse);
                    if ($roles) // set user roles in course
                        $courseUser->setRoles(array_map("trim", preg_split("/\s+/", $roles)));
                    $nrUsersImported++;
                }
            }
        }

        return $nrUsersImported;
    }

    /**
     * Exports users from a given course into a .csv file.
     *
     * @param int $courseId
     * @return string
     */
    public static function exportCourseUsers(int $courseId): string
    {
        $course = new Course($courseId);
        if (!$course->exists())
            throw new Error("Course with ID = " . $courseId . " doesn't exist.");

        $users = Course::getUsers($courseId);
        $len = count($users);
        $separator = ",";

        // Add headers
        $file = join($separator, array_merge(parent::HEADERS, self::HEADERS)) . "\n";

        // Add each student
        foreach ($users as $i => $user) {
            // NOTE: this order must match the headers order
            $userInfo = [$user["name"], $user["email"], $user["major"], $user["nickname"], $user["studentNumber"],
                $user["username"], $user["authentication_service"], $user["isAdmin"], $user["isActive"],
                $user["isActiveInCourse"], implode(" ", (new CourseUser($user["id"], $course))->getRoles())];
            $file .= join($separator, $userInfo);
            if ($i != $len - 1) $file .= "\n";
        }
        return $file;
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    private static function validateDateTime($dateTime)
    {
        if (is_null($dateTime)) return;
        if (!is_string($dateTime) || !Utils::validateDate($dateTime, "Y-m-d H:i:s"))
            throw new Error("Datetime '" . $dateTime . "' should be in format 'yyyy-mm-dd HH:mm:ss'");
    }
}
