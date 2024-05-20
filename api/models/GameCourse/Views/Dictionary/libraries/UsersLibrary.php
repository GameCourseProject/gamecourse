<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use Faker\Factory;
use GameCourse\Core\Core;
use GameCourse\User\CourseUser;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class UsersLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }

    private function mockUser(int $id = null, string $email = null, string $studentNumber = null) : array
    {
        return [
            "id" => $id ? $id : Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => Core::dictionary()->faker()->name(),
            "email" => $email ? $email : Core::dictionary()->faker()->email(),
            "major" => Core::dictionary()->faker()->text(5),
            "nickname" => Core::dictionary()->faker()->text(10),
            "studentNumber" => $studentNumber ? $studentNumber : Core::dictionary()->faker()->numberBetween(11111, 99999),
            "theme" => null,
            "username" => $email ? $email : Core::dictionary()->faker()->email(),
            "image" => null,
            "lastActivity" => Core::dictionary()->faker()->dateTimeThisYear(),
            "landingPage" => null,
            "isActive" => true,
            "avatar" => null
        ];
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "users";    // NOTE: must match the name of the class
    const NAME = "Users";
    const DESCRIPTION = "Provides access to information regarding users.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("id",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's ID in the system.",
                ReturnType::NUMBER,
                $this,
                "%someUser.id"
            ),
            new DFunction("name",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's name.",
                ReturnType::TEXT,
                $this,
                "%someUser.name"
            ),
            new DFunction("email",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's email.",
                ReturnType::TEXT,
                $this,
                "%someUser.email"
            ),
            new DFunction("major",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's major.",
                ReturnType::TEXT,
                $this,
                "%someUser.major"
            ),
            new DFunction("nickname",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's major",
                ReturnType::TEXT,
                $this,
                "%someUser.nickname"
            ),
            new DFunction("studentNumber",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's student number.",
                ReturnType::NUMBER,
                $this,
                "%someUser.studentNumber"
            ),
            new DFunction("theme",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's student theme.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("username",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's student username.",
                ReturnType::TEXT,
                $this,
                "%someUser.username"
            ),
            new DFunction("image",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's student image.",
                ReturnType::TEXT,
                $this,
                "%someUser.image"
            ),
            new DFunction("avatar",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's student avatar. If the course doesn't
                allow avatars, will return the image instead.",
                ReturnType::TEXT,
                $this,
                "%someUser.avatar"
            ),
            new DFunction("lastActivity",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's last activity datetime in the course.",
                ReturnType::TIME,
                $this,
                "%someUser.lastActivity"
            ),
            new DFunction("landingPage",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Gets a given user's course landing page.",
                ReturnType::OBJECT,
                $this,
                "%someUser.landingPage"
            ),
            new DFunction("isActive",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Checks if a given user is active in the course.",
                ReturnType::BOOLEAN,
                $this,
                "%someUser.isActive"
            ),
            new DFunction("getUserById",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets a user by its ID.",
                ReturnType::OBJECT,
                $this,
                "users.getUserById(3)"
            ),
            new DFunction("getUserByUsername",
                [["name" => "username", "optional" => false, "type" => "string"],
                 ["name" => "authService", "optional" => true, "type" => "string"]],
                "Gets a user by its username.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getUserByEmail",
                [["name" => "email", "optional" => false, "type" => "string"]],
                "Gets a user by its e-mail.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getUserByStudentNumber",
                [["name" => "studentNumber", "optional" => false, "type" => "int"]],
                "Gets a user by its student number.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getUsers",
                [["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets users of course. Option to filter by user state.",
                ReturnType::COLLECTION,
                $this,
                "users.getUsers()"
            ),
            new DFunction("getUsersWithRole",
                [["name" => "roleName", "optional" => false, "type" => "string"],
                 ["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets users with a given role. Option to filter by user state.",
                ReturnType::COLLECTION,
                $this
            ),
            new DFunction("getStudents",
                [["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets students of course. Option to filter by user state.",
                ReturnType::COLLECTION,
                $this,
                "users.getStudents()"
            ),
            new DFunction("getTeachers",
                [["name" => "active", "optional" => true, "type" => "bool"]],
                "Gets teachers of course. Option to filter by user state.",
                ReturnType::COLLECTION,
                $this,
                "users.getTeachers()"
            ),
            new DFunction("isStudent",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Checks whether a given user is a student.",
                ReturnType::BOOLEAN,
                $this
            ),
            new DFunction("isTeacher",
                [["name" => "user", "optional" => false, "type" => "User"]],
                "Checks whether a given user is a teacher.",
                ReturnType::BOOLEAN,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given user's ID in the system.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function id($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $userId = $user["id"];
        else $userId = $user->getId();
        return new ValueNode($userId, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given user's name.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function name($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $name = $user["name"];
        else $name = $user->getName();
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given user's email.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function email($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $email = $user["email"];
        else $email = $user->getEmail();
        return new ValueNode($email, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given user's major.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function major($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $major = $user["major"];
        else $major = $user->getMajor();
        return new ValueNode($major, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given user's major.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function nickname($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $nickname = $user["nickname"];
        else $nickname = $user->getNickname();
        return new ValueNode($nickname, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given user's student number.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function studentNumber($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $studentNumber = $user["studentNumber"];
        else $studentNumber = $user->getStudentNumber();
        return new ValueNode($studentNumber, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given user's theme.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function theme($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $theme = $user["theme"];
        else $theme = $user->getTheme();
        return new ValueNode($theme, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given user's username.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function username($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $username = $user["username"];
        else $username = $user->getUsername();
        return new ValueNode($username, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given user's image URL.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function image($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $image = $user["image"];
        else $image = $user->getImage();
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given user's avatar URL.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function avatar($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $avatar = $user["avatar"];
        else {
            if (Core::dictionary()->getCourse()->avatars() === true)
                $avatar = $user->getAvatar();
            else $avatar = null;
        }
        return new ValueNode($avatar, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given user's last activity datetime in the course.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function lastActivity($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $lastActivity = $user["lastActivity"];
        else $lastActivity = $user->getLastActivity();
        return new ValueNode($lastActivity, Core::dictionary()->getLibraryById(TimeLibrary::ID));
    }

    /**
     * Gets a given user's course landing page.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function landingPage($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $landingPage = $user["landingPage"];
        else $landingPage = $user->getLandingPage();
        return new ValueNode($landingPage, Core::dictionary()->getLibraryById(PagesLibrary::ID));
    }

    /**
     * Checks if a given user is active in the course.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function isActive($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $isActive = $user["isActiveInCourse"];
        else $isActive = $user->isActive();
        return new ValueNode($isActive, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a user by its ID.
     *
     * @param int $userId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserById(int $userId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getUserById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $user = $this->mockUser($userId);

        } else $user = CourseUser::getUserById($userId);
        return new ValueNode($user, $this);
    }

    /**
     * Gets a user by its username.
     *
     * @param string $username
     * @param string|null $authService
     * @return ValueNode
     * @throws Exception
     */
    public function getUserByUsername(string $username, string $authService = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getUserByUsername", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $user = $this->mockUser();

        } else $user = CourseUser::getUserByUsername($username, $authService);
        return new ValueNode($user, $this);
    }

    /**
     * Gets a user by its e-mail.
     *
     * @param string $email
     * @return ValueNode
     * @throws Exception
     */
    public function getUserByEmail(string $email): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getUserByEmail", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $user = $this->mockUser(null, $email);

        } else $user = CourseUser::getUserByEmail($email);
        return new ValueNode($user, $this);
    }

    /**
     * Gets a user by its student number.
     *
     * @param int $studentNumber
     * @return ValueNode
     * @throws Exception
     */
    public function getUserByStudentNumber(int $studentNumber): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getUserByStudentNumber", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $user = $this->mockUser(null, null, $studentNumber);

        } else $user = CourseUser::getUserByStudentNumber($studentNumber);
        return new ValueNode($user, $this);
    }

    /**
     * Gets users of course. Option to filter by user state.
     *
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUsers(?bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getUsers", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $users = array_map(function () {
                return $this->mockUser();
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 10)));

        } else $users = $course->getCourseUsers($active);
        return new ValueNode($users, $this);
    }

    /**
     * Gets users with a given role. Option to filter by user state.
     *
     * @param string $roleName
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUsersWithRole(string $roleName, ?bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getUsers", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $users = array_map(function () {
                return $this->mockUser();
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 10)));

        } else $users = $course->getCourseUsersWithRole($active, $roleName);
        return new ValueNode($users, $this);
    }

    /**
     * Gets students of course. Option to filter by user state.
     *
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getStudents(?bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getUsers", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $users = array_map(function () {
                return $this->mockUser();
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 5)));

        } else $users = $course->getStudents($active);
        return new ValueNode($users, $this);
    }

    /**
     * Gets teachers of course. Option to filter by user state.
     *
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getTeachers(?bool $active = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getUsers", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $users = array_map(function () {
                return $this->mockUser();
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 10)));

        } else $users = $course->getTeachers($active);
        return new ValueNode($users, $this);
    }


    /*** ------- Verifications ------ ***/

    /**
     * Checks whether a given user is a student.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function isStudent($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $user = CourseUser::getUserById($user["id"]);
        $isStudent = $user->isStudent();
        return new ValueNode($isStudent, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Checks whether a given user is a teacher.
     *
     * @param $user
     * @return ValueNode
     * @throws Exception
     */
    public function isTeacher($user): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($user)) $user = CourseUser::getUserById($user["id"]);
        $isTeacher = $user->isTeacher();
        return new ValueNode($isTeacher, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }
}
