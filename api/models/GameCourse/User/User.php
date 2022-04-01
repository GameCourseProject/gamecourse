<?php
namespace GameCourse\User;

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
    const TABLE_AUTH = "auth";

    const HEADERS = [   // headers for import/export functionality
        "name", "email", "major", "nickname", "studentNumber", "username", "authentication_service",
        "isAdmin", "isActive"
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

    public function getEmail(): string
    {
        return $this->getData("email");
    }

    public function getMajor(): string
    {
        return $this->getData("major");
    }

    public function getNickname(): string
    {
        return $this->getData("nickname");
    }

    public function getStudentNumber(): int
    {
        return intval($this->getData("studentNumber"));
    }

    public function getUsername(): string
    {
        return $this->getData("username");
    }

    public function getAuthService(): string
    {
        return $this->getData("authentication_service");
    }

    public function getImage(): string
    {
        // FIXME: add image column on database and allow other image file extensions
        //        the way it is now, all user images must have a very specific name and be PNG
        return API_URL . "/photos/" . $this->getUsername() . ".png";
    }

    public function isAdmin(): bool
    {
        return boolval($this->getData("isAdmin"));
    }

    public function isActive(): bool
    {
        return boolval($this->getData("isActive"));
    }

    /**
     * Gets user data from the database.
     * @example getData() --> gets all user data
     * @example getData("name") --> gets user name
     * @example getData("name, username") --> gets user name & username
     *
     * @param string $field
     * @return mixed|void
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_USER . " u left join " . self::TABLE_AUTH . " a on a.game_course_user_id=u.id";
        $where = ["u.id" => $this->id];
        if ($field == "*") $fields = "u.*, a.username, a.authentication_service";
        else $fields = $field;
        return Core::database()->select($table, $where, $fields);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    public function setEmail(string $email)
    {
        $this->setData(["email" => $email]);
    }

    public function setMajor(string $major)
    {
        $this->setData(["major" => $major]);
    }

    public function setNickname(string $nickname)
    {
        $this->setData(["nickname" => $nickname]);
    }

    public function setStudentNumber(int $studentNumber)
    {
        $this->setData(["studentNumber" => $studentNumber]);
    }

    public function setUsername(string $username)
    {
        $this->setData(["username" => $username]);
    }

    public function setAuthService(string $authService)
    {
        $this->setData(["authentication_service" => $authService]);
    }

    public function setImage(string $base64, string $name, string $extension)
    {
        $img = base64_decode($base64);
        file_put_contents(ROOT_PATH . "photos/" . $name . "." . $extension, $img);
    }

    public function setAdmin(bool $isAdmin)
    {
        $this->setData(["isAdmin" => +$isAdmin]);
    }

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
     */
    public function setData(array $fieldValues)
    {
        $authValues = []; // values that need to go to 'auth' table
        if (isset($fieldValues["username"])) {
            $authValues["username"] = $fieldValues["username"];
            unset($fieldValues["username"]);
        }
        if (isset($fieldValues["authentication_service"])) {
            $authValues["authentication_service"] = $fieldValues["authentication_service"];
            unset($fieldValues["authentication_service"]);
        }

        if (count($authValues) != 0) Core::database()->update(self::TABLE_AUTH, $authValues, ["game_course_user_id" => $this->id]);
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
        $userId = intval(Core::database()->select(self::TABLE_AUTH, ["username" => $username], "game_course_user_id"));
        if ($userId == null) return null;
        else return new User($userId);
    }

    public static function getUserByEmail(string $email): ?User
    {
        $userId = intval(Core::database()->select(self::TABLE_USER, ["email" => $email], "id"));
        if ($userId == null) return null;
        else return new User($userId);
    }

    public static function getUserByStudentNumber(int $studentNumber): ?User
    {
        $userId = intval(Core::database()->select(self::TABLE_USER, ["studentNumber" => $studentNumber], "id"));
        if ($userId == null) return null;
        else return new User($userId);
    }

    public static function getAllUsers(): array
    {
        return Core::database()->selectMultiple(
            self::TABLE_USER . " u join " . self::TABLE_AUTH . " a on u.id = a.id",
            [],
            "u.*, a.username, a.authentication_service"
        );
    }

    public static function getAdmins(): array
    {
        return Core::database()->selectMultiple(
            self::TABLE_USER . " u join " . self::TABLE_AUTH . " a on u.id = a.id",
            ["isAdmin" => true],
            "u.*, a.username, a.authentication_service"
        );
    }

    public function getCourses($active = null)
    {
        $where = ["cu.id" => $this->getId()];
        if ($active != null) $where["c.isActive"] = $active;
        return Core::database()->selectMultiple(
            CourseUser::TABLE_COURSE_USER . " cu join " . Course::TABLE_COURSE . " c on cu.course = c.id",
            $where,
            "c.*"
        );
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
     * @param string $email
     * @param int $studentNumber
     * @param string $nickname
     * @param string $major
     * @param bool $isAdmin
     * @param bool $isActive
     * @return User
     */
    public static function addUser(string $name, string $username, string $authService, string $email, int $studentNumber,
                                   string $nickname, string $major, bool $isAdmin, bool $isActive): User
    {
        $id = Core::database()->insert(self::TABLE_USER, [
            "name" => $name,
            "email" => $email,
            "studentNumber" => $studentNumber,
            "nickname" => $nickname,
            "major" => $major,
            "isAdmin" => +$isAdmin,
            "isActive" => +$isActive
        ]);
        Core::database()->insert(self::TABLE_AUTH, [
            "game_course_user_id" => $id,
            "username" => $username,
            "authentication_service" => $authService
        ]);
        return new User($id);
    }

    /**
     * Edits an existing user in database.
     * Returns the edited user.
     *
     * @param string $name
     * @param string $username
     * @param string $authService
     * @param string $email
     * @param int $studentNumber
     * @param string $nickname
     * @param string $major
     * @param bool $isAdmin
     * @param bool $isActive
     * @return User
     */
    public function editUser(string $name, string $username, string $authService, string $email, int $studentNumber,
                             string $nickname, string $major, bool $isAdmin, bool $isActive): User
    {
        Core::database()->update(self::TABLE_USER, [
            "name" => $name,
            "email" => $email,
            "studentNumber" => $studentNumber,
            "nickname" => $nickname,
            "major" => $major,
            "isAdmin" => +$isAdmin,
            "isActive" => +$isActive
        ], ["id" => $this->id]);
        Core::database()->update(self::TABLE_AUTH, [
            "username" => $username,
            "authentication_service" => $authService
        ], ["game_course_user_id" => $this->id]);
        return $this;
    }

    /**
     * Deletes a user from the database.
     *
     * @param int $userId
     * @return void
     */
    public static function deleteUser(int $userId) {
        Core::database()->delete(self::TABLE_USER, ["id" => $userId]);
        Core::database()->delete(self::TABLE_AUTH, ["game_course_user_id" => $userId]);
    }

    /**
     * Checks whether user exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return (!empty($this->getData("id")));
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports users into the system from a .csv file.
     * Encodes file to UTF-8 for accented/special characters to work.
     *
     * Returns the nr. of users imported.
     *
     * @param string $file
     * @param bool $replace
     * @return int
     */
    public static function importUsers(string $file, bool $replace = true): int
    {
        // UTF-8 encoding
        $file = utf8_encode($file);

        $nrUsersImported = 0;
        $separator = Utils::detectSeparator($file);

        // NOTE: this order must match the order in the file
        $headers = self::HEADERS;

        $nameIndex = array_search("name", $headers);
        $emailIndex = array_search("email", $headers);
        $majorIndex = array_search("major", $headers);
        $nicknameIndex = array_search("nickname", $headers);
        $studentNumberIndex = array_search("studentNumber", $headers);
        $usernameIndex = array_search("username", $headers);
        $authServiceIndex = array_search("authentication_service", $headers);
        $isAdminIndex = array_search("isAdmin", $headers);
        $isActiveIndex = array_search("isActive", $headers);

        // Filter empty lines
        $lines = array_filter(explode("\n", $file), function ($line) { return !empty($line); });

        if (count($lines) > 0) {
            // Check whether 1st line holds headers and ignores them
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

                $authId = intval(Core::database()->select(self::TABLE_AUTH, ["username"=> $username, "authentication_service"=> $authService], "id"));
                $userId = intval(Core::database()->select(self::TABLE_USER, ["studentNumber"=> $studentNumber], "id"));
                if ($userId || $authId) {  // user already exists
                    if ($replace) {  // replace
                        $userToUpdate = User::getUserByUsername($username);
                        $userToUpdate->editUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
                    }

                } else {  // user doesn't exist
                    User::addUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
                    $nrUsersImported++;
                }
            }
        }

        return $nrUsersImported;
    }

    /**
     * Exports users from the system into a .csv file.
     *
     * @return string
     */
    public static function exportUsers(): string
    {
        $users = User::getAllUsers();
        $len = count($users);
        $separator = ",";

        // Add headers
        $file = join($separator, self::HEADERS) . "\n";

        // Add each student
        foreach ($users as $i => $user) {
            // NOTE: this order must match the headers order
            $userInfo = [$user["name"], $user["email"], $user["major"], $user["nickname"], $user["studentNumber"], $user["username"],
                $user["authentication_service"], $user["isAdmin"], $user["isActive"]];
            $file .= join($separator, $userInfo);
            if ($i != $len - 1) $file .= "\n";
        }
        return $file;
    }
}
