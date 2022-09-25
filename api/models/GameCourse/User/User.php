<?php
namespace GameCourse\User;

use Exception;
use GameCourse\Core\Auth;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the User model, which implements the necessary methods
 * to interact with users in the MySQL database.
 */
class User
{
    const TABLE_USER = "user";

    const HEADERS = [   // headers for import/export functionality
        "name", "email", "major", "nickname", "studentNumber", "username", "auth_service", "isAdmin", "isActive"
    ];

    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getEmail(): ?string
    {
        return $this->getData("email");
    }

    public function getMajor(): ?string
    {
        return $this->getData("major");
    }

    public function getNickname(): ?string
    {
        return $this->getData("nickname");
    }

    public function getStudentNumber(): int
    {
        return $this->getData("studentNumber");
    }

    public function getTheme(): ?string
    {
        return $this->getData("theme");
    }

    public function getUsername(): string
    {
        return $this->getData("username");
    }

    public function getAuthService(): string
    {
        return $this->getData("auth_service");
    }

    public function getLastLogin(): ?string
    {
        return $this->getData("lastLogin");
    }

    public function getImage(): ?string
    {
        return $this->hasImage() ? API_URL . "/" . $this->getDataFolder(false) . "/profile.png" : null;
    }

    public function hasImage(): bool
    {
        return file_exists($this->getDataFolder() . "/profile.png");
    }

    public function isAdmin(): bool
    {
        return $this->getData("isAdmin");
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Gets user data from the database.
     *
     * @example getData() --> gets all user data
     * @example getData("name") --> gets user name
     * @example getData("name, username") --> gets user name & username
     *
     * @param string $field
     * @return array|bool|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_USER . " u LEFT JOIN " . Auth::TABLE_AUTH . " a on a.user=u.id";
        $where = ["u.id" => $this->id];
        if ($field == "*") $fields = "u.*, a.username, a.auth_service, a.lastLogin";
        else $fields = str_replace("id", "u.id", $field);
        $data = Core::database()->select($table, $where, $fields);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function setEmail(?string $email)
    {
        $this->setData(["email" => $email]);
    }

    /**
     * @throws Exception
     */
    public function setMajor(?string $major)
    {
        $this->setData(["major" => $major]);
    }

    /**
     * @throws Exception
     */
    public function setNickname(?string $nickname)
    {
        $this->setData(["nickname" => $nickname]);
    }

    /**
     * @throws Exception
     */
    public function setStudentNumber(int $studentNumber)
    {
        $this->setData(["studentNumber" => $studentNumber]);
    }

    /**
     * @throws Exception
     */
    public function setTheme(?string $theme)
    {
        $this->setData(["theme" => $theme]);
    }

    /**
     * @throws Exception
     */
    public function setUsername(string $username)
    {
        $this->setData(["username" => $username]);
    }

    /**
     * @throws Exception
     */
    public function setAuthService(string $authService)
    {
        $this->setData(["auth_service" => $authService]);
    }

    /**
     * @throws Exception
     */
    public function setLastLogin(?string $lastLogin)
    {
        $this->setData(["lastLogin" => $lastLogin]);
    }

    /**
     * @throws Exception
     */
    public function setImage(string $base64)
    {
        Utils::uploadFile($this->getDataFolder(), $base64, "profile.png");
    }

    /**
     * @throws Exception
     */
    public function setAdmin(bool $isAdmin)
    {
        $this->setData(["isAdmin" => +$isAdmin]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets user data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "username" => "New username"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        // Trim values
        self::trim($fieldValues);

        $authValues = []; // values that need to go to 'auth' table
        if (key_exists("username", $fieldValues)) {
            $authValues["username"] = $fieldValues["username"];
            unset($fieldValues["username"]);
        }
        if (key_exists("auth_service", $fieldValues)) {
            self::validateAuthService($fieldValues["auth_service"]);
            $authValues["auth_service"] = $fieldValues["auth_service"];
            unset($fieldValues["auth_service"]);
        }
        if (key_exists("lastLogin", $fieldValues)) {
            self::validateDateTime($fieldValues["lastLogin"]);
            $authValues["lastLogin"] = $fieldValues["lastLogin"];
            unset($fieldValues["lastLogin"]);
        }

        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($fieldValues["name"]);
        if (key_exists("email", $fieldValues)) self::validateEmail($fieldValues["email"]);
        if (key_exists("isActive", $fieldValues) && !$fieldValues["isActive"]) {
            $loggedUser = Core::getLoggedUser();
            if ($loggedUser && $loggedUser->getId() == $this->id)
                throw new Exception("You attempted to remove your access to the system. This was flagged as an error and had no effect.");
        }

        // Update data
        if (count($authValues) != 0) Core::database()->update(Auth::TABLE_AUTH, $authValues, ["user" => $this->id]);
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_USER, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("isActive", $fieldValues) && !$fieldValues["isActive"]) {
            // Disable user in their courses when disabling from system
            $userCourses = $this->getCourses();
            foreach ($userCourses as $userCourse) {
                $course = Course::getCourseById($userCourse["id"]);
                $courseUser = $course->getCourseUserById($this->id);
                $courseUser->setActive(false);
            }
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a user by its ID.
     * Returns null if user doesn't exist.
     *
     * @param int $id
     * @return User|null
     */
    public static function getUserById(int $id): ?User
    {
        $user = new User($id);
        if ($user->exists()) return $user;
        else return null;
    }

    /**
     * Gets a user by its username.
     * Returns null if user doesn't exist.
     *
     * @param string $username
     * @param string|null $authService
     * @return User|null
     * @throws Exception
     */
    public static function getUserByUsername(string $username, string $authService = null): ?User
    {
        if (!$authService) {
            $userIds = Core::database()->selectMultiple(Auth::TABLE_AUTH, ["username" => $username], "user");
            $nrUsersWithUsername = count($userIds);

            if ($nrUsersWithUsername > 1)
                throw new Exception("Cannot get user by username: there's multiple users with username '" . $username . "'.");

            $userId = $nrUsersWithUsername < 1 ? null : intval($userIds[0]["user"]);

        } else {
            self::validateAuthService($authService);
            $userId = intval(Core::database()->select(Auth::TABLE_AUTH, ["username" => $username, "auth_service" => $authService], "user"));
        }

        if (!$userId) return null;
        else return new User($userId);
    }

    /**
     * Gets a user by its e-mail.
     * Returns null if user doesn't exist.
     *
     * @param string $email
     * @return User|null
     */
    public static function getUserByEmail(string $email): ?User
    {
        $userId = intval(Core::database()->select(self::TABLE_USER, ["email" => $email], "id"));
        if (!$userId) return null;
        else return new User($userId);
    }

    /**
     * Gets a user by its student number.
     * Returns null if user doesn't exist.
     *
     * @param int $studentNumber
     * @return User|null
     */
    public static function getUserByStudentNumber(int $studentNumber): ?User
    {
        $userId = intval(Core::database()->select(self::TABLE_USER, ["studentNumber" => $studentNumber], "id"));
        if (!$userId) return null;
        else return new User($userId);
    }

    /**
     * Gets users in the system.
     * Option for 'active' and/or 'admin'.
     *
     * @param bool|null $active
     * @param bool|null $admin
     * @return array
     */
    public static function getUsers(?bool $active = null, ?bool $admin = null): array
    {
        $where = [];
        if ($active !== null) $where["u.isActive"] = $active;
        if ($admin !== null) $where["u.isAdmin"] = $admin;
        $users = Core::database()->selectMultiple(
            self::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on u.id = a.user",
            $where,
            "u.*, a.username, a.auth_service, a.lastLogin",
            "id"
        );
        foreach ($users as &$user) { $user = self::parse($user); }
        return $users;
    }

    /**
     * Updates user's lastLogin to current time.
     *
     * @return void
     * @throws Exception
     */
    public function refreshLastLogin()
    {
        $this->setLastLogin(date("Y-m-d H:i:s", time()));
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- User Manipulation ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a user to the database.
     * Returns the newly created user.
     *
     * @param string $name
     * @param string $username
     * @param string $authService
     * @param string|null $email
     * @param int $studentNumber
     * @param string|null $nickname
     * @param string|null $major
     * @param bool $isAdmin
     * @param bool $isActive
     * @return User
     * @throws Exception
     */
    public static function addUser(string $name, string $username, string $authService, ?string $email, int $studentNumber,
                                   ?string $nickname, ?string $major, bool $isAdmin, bool $isActive): User
    {
        self::trim($name, $username, $authService, $email, $nickname, $major);
        self::validateUser($name, $email, $authService, $isAdmin, $isActive);
        $id = Core::database()->insert(self::TABLE_USER, [
            "name" => $name,
            "email" => $email,
            "studentNumber" => $studentNumber,
            "nickname" => $nickname,
            "major" => $major,
            "isAdmin" => +$isAdmin,
            "isActive" => +$isActive
        ]);
        Core::database()->insert(Auth::TABLE_AUTH, [
            "user" => $id,
            "username" => $username,
            "auth_service" => $authService
        ]);
        self::createDataFolder($id);
        return new User($id);
    }

    /**
     * Edits an existing user in database.
     * Returns the edited user.
     *
     * @param string $name
     * @param string $username
     * @param string $authService
     * @param string|null $email
     * @param int $studentNumber
     * @param string|null $nickname
     * @param string|null $major
     * @param bool $isAdmin
     * @param bool $isActive
     * @return User
     * @throws Exception
     */
    public function editUser(string $name, string $username, string $authService, ?string $email, int $studentNumber,
                             ?string $nickname, ?string $major, bool $isAdmin, bool $isActive): User
    {
        $this->setData([
            "name" => $name,
            "username" => $username,
            "auth_service" => $authService,
            "email" => $email,
            "studentNumber" => $studentNumber,
            "nickname" => $nickname,
            "major" => $major,
            "isAdmin" => +$isAdmin,
            "isActive" => +$isActive
        ]);
        return $this;
    }

    /**
     * Deletes a user from the database.
     *
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public static function deleteUser(int $userId) {
        $loggedUser = Core::getLoggedUser();
        if ($loggedUser && $loggedUser->getId() == $userId)
            throw new Exception("You attempted to remove yourself from the system. This was flagged as an error and had no effect.");

        Core::database()->delete(self::TABLE_USER, ["id" => $userId]);
        Core::database()->delete(Auth::TABLE_AUTH, ["user" => $userId]);
        self::removeDataFolder($userId);
    }

    /**
     * Checks whether user exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Courses --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets user courses.
     * Option for 'active' and/or 'visible'.
     *
     * @param bool|null $active
     * @param bool|null $visible
     * @return array
     */
    public function getCourses(?bool $active = null, ?bool $visible = null): array
    {
        return Course::getCoursesOfUser($this->id, $active, $visible);
    }

    /**
     * Checks whether user is a teacher of any course.
     * @return bool
     * @throws Exception
     */
    public function isATeacher(): bool
    {
        $courses = Course::getCourses();
        foreach ($courses as $c) {
            $course = new Course($c["id"]);
            $courseUser = $course->getCourseUserById($this->id);
            if (!$courseUser) continue;
            if ($courseUser->isTeacher()) return true;
        }
        return false;
    }

    /**
     * Checks whether user is a student of any course.
     * @return bool
     * @throws Exception
     */
    public function isAStudent(): bool
    {
        $courses = Course::getCourses();
        foreach ($courses as $c) {
            $course = new Course($c["id"]);
            $courseUser = $course->getCourseUserById($this->id);
            if (!$courseUser) continue;
            if ($courseUser->isStudent()) return true;
        }
        return false;
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- User Data -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets user data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param bool $fullPath
     * @return string
     */
    public function getDataFolder(bool $fullPath = true): string
    {
        if ($fullPath) return USER_DATA_FOLDER . "/" . $this->getId();
        else return Utils::getDirectoryName(USER_DATA_FOLDER) . "/" . $this->getId();
    }

    /**
     * Gets user data folder contents.
     *
     * @return array
     * @throws Exception
     */
    public function getDataFolderContents(): array
    {
        return Utils::getDirectoryContents($this->getDataFolder());
    }

    /**
     * Creates a data folder for a given user. If folder exists, it
     * will delete its contents.
     *
     * @param int $userId
     * @return string
     * @throws Exception
     */
    public static function createDataFolder(int $userId): string
    {
        $dataFolder = (new User($userId))->getDataFolder();
        if (file_exists($dataFolder)) self::removeDataFolder($userId);
        mkdir($dataFolder, 0777, true);
        return $dataFolder;
    }

    /**
     * Deletes a given user's data folder.
     *
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public static function removeDataFolder(int $userId)
    {
        $dataFolder = (new User($userId))->getDataFolder();
        if (file_exists($dataFolder)) Utils::deleteDirectory($dataFolder);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports users into the system from a .csv file.
     * Returns the nr. of users imported.
     *
     * @param string $file
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importUsers(string $file, bool $replace = true): int
    {
        return Utils::importFromCSV(self::HEADERS, function ($user, $indexes) use ($replace) {
            $name = Utils::nullify($user[$indexes["name"]]);
            $email = Utils::nullify($user[$indexes["email"]]);
            $major = Utils::nullify($user[$indexes["major"]]);
            $nickname = Utils::nullify($user[$indexes["nickname"]]);
            $studentNumber = self::parse(null, Utils::nullify($user[$indexes["studentNumber"]]), "studentNumber");
            $username = Utils::nullify($user[$indexes["username"]]);
            $authService = Utils::nullify($user[$indexes["auth_service"]]);
            $isAdmin = self::parse(null, Utils::nullify($user[$indexes["isAdmin"]]), "isAdmin");
            $isActive = self::parse(null, Utils::nullify($user[$indexes["isActive"]]), "isActive");

            $user = self::getUserByUsername($username, $authService) ?? self::getUserByStudentNumber($studentNumber);
            if ($user) {  // user already exists
                if ($replace)  // replace
                    $user->editUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);

            } else {  // user doesn't exist
                User::addUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
                return 1;
            }
            return 0;
        }, $file);
    }

    /**
     * Exports users from the system into a .csv file.
     *
     * @param array $userIds
     * @return string
     */
    public static function exportUsers(array $userIds): string
    {
        $usersToExport = array_filter(self::getUsers(), function ($user) use ($userIds) { return in_array($user["id"], $userIds); });
        return Utils::exportToCSV(
            $usersToExport,
            function ($user) {
                return [$user["name"], $user["email"], $user["major"], $user["nickname"], $user["studentNumber"], $user["username"],
                    $user["auth_service"], +$user["isAdmin"], +$user["isActive"]];
            },
            self::HEADERS);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates user parameters.
     *
     * @param $name
     * @param $email
     * @param $authService
     * @param $isAdmin
     * @param $isActive
     * @return void
     * @throws Exception
     */
    private static function validateUser($name, $email, $authService, $isAdmin, $isActive)
    {
        self::validateName($name);
        self::validateEmail($email);
        self::validateAuthService($authService);
        if (!is_bool($isAdmin)) throw new Exception("'isAdmin' must be either true or false.");
        if (!is_bool($isActive)) throw new Exception("'isActive' must be either true or false.");
    }

    /**
     * Validates user name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("User name can't be null neither empty.");

        if (is_numeric($name))
            throw new Exception("User name can't be composed of only numbers.");

        if (iconv_strlen($name) > 60)
            throw new Exception("User name is too long: maximum of 60 characters.");
    }

    /**
     * Validates user authentication service.
     *
     * @param $authService
     * @return void
     * @throws Exception
     */
    protected static function validateAuthService($authService)
    {
        if (!is_string($authService) || empty($authService))
            throw new Exception("Authentication service can't be null neither empty.");

        if (!AuthService::exists($authService))
            throw new Exception("Authentication service '" . $authService . "' is not available.");
    }

    /**
     * Validates user e-mail.
     *
     * @param $email
     * @return void
     * @throws Exception
     */
    private static function validateEmail($email)
    {
        if (is_null($email)) return;

        if (!is_string($email) || !Utils::isValidEmail($email))
            throw new Exception("E-mail '" . $email . "' is invalid.");

        if (iconv_strlen($email) > 60)
            throw new Exception("E-mail is too long: maximum of 60 characters.");
    }

    /**
     * Validates a datetime.
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


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a user coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $user
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    public static function parse(array $user = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "studentNumber"];
        $boolValues = ["isAdmin", "isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $user, $field, $fieldName);
    }

    /**
     * Trims user parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    protected static function trim(&...$values)
    {
        $params = ["name", "email", "major", "nickname", "username", "auth_service", "lastLogin"];
        Utils::trim($params, ...$values);
    }
}
