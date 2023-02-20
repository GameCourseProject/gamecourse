<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\User\CourseUser;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class UsersLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
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
            // TODO
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
     * Gets a given user's last activity date in the course.
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
     */
    public function getUserById(int $userId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock user
            $user = [];

        } else {
            $user = CourseUser::getUserById($userId);
        }
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
        if (Core::dictionary()->mockData()) {
            // TODO: mock user
            $user = [];

        } else {
            $user = CourseUser::getUserByUsername($username, $authService);
        }
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
        if (Core::dictionary()->mockData()) {
            // TODO: mock user
            $user = [];

        } else {
            $user = CourseUser::getUserByEmail($email);
        }
        return new ValueNode($user, $this);
    }

    /**
     * Gets a user by its student number.
     *
     * @param int $studentNumber
     * @return ValueNode
     */
    public function getUserByStudentNumber(int $studentNumber): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock user
            $user = [];

        } else {
            $user = CourseUser::getUserByStudentNumber($studentNumber);
        }
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
        if (Core::dictionary()->mockData()) {
            // TODO: mock users
            $users = [];

        } else {
            $course = Core::dictionary()->getCourse();
            $users = $course->getCourseUsers($active);
        }
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
        if (Core::dictionary()->mockData()) {
            // TODO: mock users
            $users = [];

        } else {
            $course = Core::dictionary()->getCourse();
            $users = $course->getCourseUsersWithRole($active, $roleName);
        }
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
        if (Core::dictionary()->mockData()) {
            // TODO: mock users
            $users = [];

        } else {
            $course = Core::dictionary()->getCourse();
            $users = $course->getStudents($active);
        }
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
        if (Core::dictionary()->mockData()) {
            // TODO: mock users
            $users = [];

        } else {
            $course = Core::dictionary()->getCourse();
            $users = $course->getTeachers($active);
        }
        return new ValueNode($users, $this);
    }


    /*** ------- Verifications ------ ***/

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

    /**
     * Checks whether a given user is a teacher.
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
}
