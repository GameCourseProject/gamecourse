<?php
namespace GameCourse\User;

use API\API;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the User model, which implements the necessary methods
 * to interact with users in the MySQL database.
 */
class User
{
    const TABLE_USER = "game_course_user";

    const HEADERS = [   // headers for import/export functionality
        "name", "email", "major", "nickname", "studentNumber", "username", "authentication_service", "isAdmin", "isActive"
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

    public function getUsername(): string
    {
        return $this->getData("username");
    }

    public function getAuthService(): string
    {
        return $this->getData("authentication_service");
    }

    public function getImage(): ?string
    {
        return $this->hasImage() ? API_URL . "/" . $this->getDataFolder(false) . "/profile.png" : null;
    }

    public function hasImage(): bool
    {
        return file_exists($this->getDataFolder() . "/profile.png");
    }

    public function getLastLogin(): ?string
    {
        return Core::database()->select(CourseUser::TABLE_COURSE_USER, ["id" => $this->getId()], "max(lastActivity)");
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
        $table = self::TABLE_USER . " u LEFT JOIN " . Auth::TABLE_AUTH . " a on a.game_course_user_id=u.id";
        $where = ["u.id" => $this->id];
        if ($field == "*") $fields = "u.*, a.username, a.authentication_service";
        else $fields = str_replace("id", "u.id", $field);
        $res = Core::database()->select($table, $where, $fields);
        return is_array($res) ? self::parse($res) : self::parse(null, $res, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setName(string $name)
    {
        self::validateName($name);
        $this->setData(["name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function setEmail(?string $email)
    {
        self::validateEmail($email);
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
    public function setUsername(string $username)
    {
        $this->setData(["username" => $username]);
    }

    /**
     * @throws Exception
     */
    public function setAuthService(string $authService)
    {
        self::validateAuthService($authService);
        $this->setData(["authentication_service" => $authService]);
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
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "username" => "New username"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        $authValues = []; // values that need to go to 'auth' table
        if (key_exists("username", $fieldValues)) {
            $authValues["username"] = $fieldValues["username"];
            unset($fieldValues["username"]);
        }
        if (key_exists("authentication_service", $fieldValues)) {
            self::validateAuthService($fieldValues["authentication_service"]);
            $authValues["authentication_service"] = $fieldValues["authentication_service"];
            unset($fieldValues["authentication_service"]);
        }

        if (key_exists("name", $fieldValues)) self::validateName($fieldValues["name"]);
        if (key_exists("email", $fieldValues)) self::validateEmail($fieldValues["email"]);

        if (count($authValues) != 0) Core::database()->update(Auth::TABLE_AUTH, $authValues, ["game_course_user_id" => $this->id]);
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_USER, $fieldValues, ["id" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function getUserById(int $id): ?User
    {
        $user = new User($id);
        if ($user->exists()) return $user;
        else return null;
    }

    public static function getUserByUsername(string $username): ?User
    {
        $userId = intval(Core::database()->select(Auth::TABLE_AUTH, ["username" => $username], "game_course_user_id"));
        if (!$userId) return null;
        else return new User($userId);
    }

    public static function getUserByEmail(string $email): ?User
    {
        $userId = intval(Core::database()->select(self::TABLE_USER, ["email" => $email], "id"));
        if (!$userId) return null;
        else return new User($userId);
    }

    public static function getUserByStudentNumber(int $studentNumber): ?User
    {
        $userId = intval(Core::database()->select(self::TABLE_USER, ["studentNumber" => $studentNumber], "id"));
        if (!$userId) return null;
        else return new User($userId);
    }

    public static function getUsers(?bool $active = null): array
    {
        $where = [];
        if ($active !== null) $where["u.isActive"] = $active;
        $users = Core::database()->selectMultiple(
            self::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on u.id = a.game_course_user_id",
            $where,
            "u.*, a.username, a.authentication_service"
        );
        foreach ($users as &$user) {
            $user["image"] = (new User($user["id"]))->getImage();
            $user = self::parse($user);
        }
        return $users;
    }

    public static function getAdmins(): array
    {
        $admins = Core::database()->selectMultiple(
            self::TABLE_USER . " u JOIN " . Auth::TABLE_AUTH . " a on u.id = a.id",
            ["isAdmin" => true],
            "u.*, a.username, a.authentication_service"
        );
        foreach ($admins as &$admin) { $admin = self::parse($admin); }
        return $admins;
    }

    public function getCourses(?bool $active = null, ?bool $visible = null): array
    {
        $where = ["cu.id" => $this->id];
        if ($active !== null) $where["c.isActive"] = $active;
        if ($visible !== null) $where["c.isVisible"] = $visible;
        $courses = Core::database()->selectMultiple(
            CourseUser::TABLE_COURSE_USER . " cu JOIN " . Course::TABLE_COURSE . " c on cu.course=c.id",
            $where,
            "c.*"
        );
        foreach ($courses as &$course) { $course = Course::parse($course); }
        return $courses;
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
            "game_course_user_id" => $id,
            "username" => $username,
            "authentication_service" => $authService
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
        self::validateUser($name, $email, $authService, $isAdmin, $isActive);
        $this->setData([
            "name" => $name,
            "username" => $username,
            "authentication_service" => $authService,
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
        Core::database()->delete(self::TABLE_USER, ["id" => $userId]);
        Core::database()->delete(Auth::TABLE_AUTH, ["game_course_user_id" => $userId]);
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
    /*** --------------------- User Data -------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getDataFolder(bool $fullPath = true): string
    {
        if ($fullPath) return USER_DATA_FOLDER . "/" . $this->getId();
        else {
            $parts = explode("/", USER_DATA_FOLDER);
            return end($parts) . "/" . $this->getId();
        }
    }

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
            $name = $user[$indexes["name"]];
            $email = $user[$indexes["email"]];
            $major = $user[$indexes["major"]];
            $nickname = $user[$indexes["nickname"]];
            $studentNumber = self::parse(null, $user[$indexes["studentNumber"]], "studentNumber");
            $username = $user[$indexes["username"]];
            $authService = $user[$indexes["authentication_service"]];
            $isAdmin = self::parse(null, $user[$indexes["isAdmin"]], "isAdmin");
            $isActive = self::parse(null, $user[$indexes["isActive"]], "isActive");

            $user = self::getUserByUsername($username) ?? self::getUserByStudentNumber($studentNumber);
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
     * @return string
     */
    public static function exportUsers(): string
    {
        return Utils::exportToCSV(
            self::getUsers(),
            function ($user) {
                return [$user["name"], $user["email"], $user["major"], $user["nickname"], $user["studentNumber"], $user["username"],
                    $user["authentication_service"], +$user["isAdmin"], +$user["isActive"]];
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

        if (iconv_strlen($name) > 50)
            throw new Exception("User name is too long: maximum of 50 characters.");
    }

    /** Validates user authentication service.
     *
     * @param $authService
     * @return void
     * @throws Exception
     */
    private static function validateAuthService($authService)
    {
        if (!is_string($authService) || empty($authService))
            throw new Exception("Authentication service can't be null neither empty.");

        if (!Auth::exists($authService))
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

        if (iconv_strlen($email) > 50)
            throw new Exception("E-mail is too long: maximum of 50 characters.");
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
     * @return array|bool|int|null
     */
    public static function parse(array $user = null, $field = null, string $fieldName = null)
    {
        if ($user) {
            if (isset($user["id"])) $user["id"] = intval($user["id"]);
            if (isset($user["studentNumber"])) $user["studentNumber"] = intval($user["studentNumber"]);
            if (isset($user["isAdmin"])) $user["isAdmin"] = boolval($user["isAdmin"]);
            if (isset($user["isActive"])) $user["isActive"] = boolval($user["isActive"]);
            return $user;

        } else {
            if ($fieldName == "id" || $fieldName == "studentNumber") return intval($field);
            if ($fieldName == "isAdmin" || $fieldName == "isActive") return boolval($field);
            return $field;
        }
    }
}
