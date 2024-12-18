<?php
namespace API;

use Exception;

use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\Module;
use GameCourse\Module\Skills\Skills;
use GameCourse\Role\Role;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Module\Awards\Awards;

/**
 * This is the Course controller, which holds API endpoints for
 * course related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Course",
 *     description="API endpoints for course related actions"
 * )
 */
class CourseController
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Get course by its ID.
     *
     * @return void
     * @throws Exception
     */
    public function getCourseById()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $loggedUser = Core::getLoggedUser();
        $courseUser = $course->getCourseUserById($loggedUser->getId());

        $courseInfo = $course->getData();
        $courseInfo['roleHierarchy'] = $course->getRoles(false, true);
        $courseInfo["folder"] = $course->getDataFolder(false, $courseInfo["name"]);
        if ($loggedUser->isAdmin() || $courseUser->isTeacher())
            $courseInfo["nrStudents"] = count($course->getStudents());

        API::response($courseInfo);
    }

    /**
     * Get courses on the system.
     * Option for 'active' and/or 'visible'.
     *
     * @param bool $isActive (optional)
     * @param bool $isVisible (optional)
     * @throws Exception
     */
    public function getCourses()
    {
        API::requireAdminPermission();
        $isActive = API::getValue("isActive", "bool");
        $isVisible = API::getValue("isVisible", "bool");

        $courses = Course::getCourses($isActive, $isVisible);
        foreach ($courses as &$courseInfo) {
            $course = Course::getCourseById($courseInfo["id"]);
            $courseInfo["nrStudents"] = count($course->getStudents());
        }

        API::response($courses);
    }


    /*** --------------------------------------------- ***/
    /*** ------------ Course Manipulation ------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function createCourse()
    {
        API::requireAdminPermission();
        API::requireValues('name', 'short', 'year', 'color', 'startDate', 'endDate');

        // Get values
        $name = API::getValue("name");
        $short = API::getValue("short");
        $year = API::getValue("year");
        $color = API::getValue("color");
        $startDate = API::getValue("startDate");
        $endDate = API::getValue("endDate");

        // Add new course
        $course = Course::addCourse($name, $short, $year, $color, $startDate, $endDate, false, false);

        $courseInfo = $course->getData();
        if (Core::getLoggedUser()->isAdmin())
            $courseInfo["nrStudents"] = count($course->getStudents());
        API::response($courseInfo);
    }

    /**
     * @throws Exception
     */
    public function duplicateCourse()
    {
        API::requireAdminPermission();
        API::requireValues('courseId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        // Duplicate course
        $course = Course::copyCourse($courseId);

        $courseInfo = $course->getData();
        if (Core::getLoggedUser()->isAdmin())
            $courseInfo["nrStudents"] = count($course->getStudents());
        API::response($courseInfo);
    }

    /**
     * @throws Exception
     */
    public function editCourse()
    {
        API::requireAdminPermission();
        API::requireValues('courseId', 'name', 'short', 'year', 'color', 'startDate', 'endDate', 'theme');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        // Get values
        $name = API::getValue("name");
        $short = API::getValue("short");
        $year = API::getValue("year");
        $color = API::getValue("color");
        $startDate = API::getValue("startDate");
        $endDate = API::getValue("endDate");
        $isActive = $course->isActive();
        $isVisible = $course->isVisible();
        $avatars = API::getValue("avatars");
        $nicknames = API::getValue("nicknames");
        $theme = API::getValue("theme");

        // Edit course
        $course->editCourse($name, $short, $year, $color, $startDate, $endDate, $isActive, $isVisible, $avatars, $nicknames, $theme);

        $courseInfo = $course->getData();
        if (Core::getLoggedUser()->isAdmin())
            $courseInfo["nrStudents"] = count($course->getStudents());
        API::response($courseInfo);
    }

    /**
     * @throws Exception
     */
    public function deleteCourse()
    {
        API::requireAdminPermission();
        API::requireValues('courseId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        Course::deleteCourse($courseId);
    }

    /**
     * @throws Exception
     */
    public function setActive()
    {
        API::requireAdminPermission();
        API::requireValues('courseId', 'isActive');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $isActive = API::getValue("isActive", "bool");
        $course->setActive($isActive);
    }

    /**
     * @throws Exception
     */
    public function setVisible()
    {
        API::requireAdminPermission();
        API::requireValues('courseId', 'isVisible');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $isVisible = API::getValue("isVisible", "bool");
        $course->setVisible($isVisible);
    }

    /*** --------------------------------------------- ***/
    /*** --------------- Course Users ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function getCourseUsers()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $active = API::getValue("active", "bool");

        $courseUsers = $course->getCourseUsers($active);
        foreach ($courseUsers as &$courseUserInfo) {
            $courseUser = $course->getCourseUserById($courseUserInfo["id"]);
            $courseUserInfo["roles"] = $courseUser->getRoles(false);
        }
        API::response($courseUsers);
    }

    /**
     * @throws Exception
     */
    public function getCourseUsersWithRole()
    {
        API::requireValues("courseId", "role");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $roleName = API::getValue("role");
        $active = API::getValue("active", "bool");

        $courseUsers = $course->getCourseUsersWithRole($active, $roleName);
        foreach ($courseUsers as &$courseUserInfo) {
            $courseUser = $course->getCourseUserById($courseUserInfo["id"]);
            $courseUserInfo["image"] = $courseUser->getImage();
            $courseUserInfo["avatar"] = $courseUser->getAvatar();
            $courseUserInfo["roles"] = $courseUser->getRoles(false);
        }
        API::response($courseUsers);
    }

    /**
     * @throws Exception
     */
    public function getUsersNotInCourse()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        $active = API::getValue("active", "bool");

        $usersNotInCourse = $course->getUsersNotInCourse($active);
        foreach ($usersNotInCourse as &$userInfo) {
            $user = User::getUserById($userInfo["id"]);
            $userInfo["image"] = $user->getImage();
        }
        API::response($usersNotInCourse);
    }

    /**
     * Create a new user in the system and add it to the course.
     *
     * @throws Exception
     */
    public function createCourseUser()
    {
        API::requireValues('courseId', 'name', 'authService', 'studentNumber', 'email', 'nickname', 'username', 'major', 'image', 'roles');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        // Get values
        $name = API::getValue("name");
        $username = API::getValue("username");
        $authService = API::getValue("authService");
        $email = API::getValue("email");
        $studentNumber = API::getValue("studentNumber", "int");
        $nickname = API::getValue("nickname");
        $major = API::getValue("major");
        $image = API::getValue("image");
        $rolesNames = API::getValue("roles");

        // Add user to system
        $user = User::addUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, false, true);
        if ($image) $user->setImage($image);

        // Add user to course
        $courseUser = $course->addUserToCourse($user->getId());
        $courseUser->setRoles($rolesNames);

        $courseUserInfo = $courseUser->getData();
        $courseUserInfo["image"] = $courseUser->getImage();
        $courseUserInfo["roles"] = $courseUser->getRoles(false);
        API::response($courseUserInfo);
    }

    /**
     * Add en existing user to the course.
     *
     * @throws Exception
     */
    public function addUsersToCourse()
    {
        API::requireValues('courseId', 'users', 'roles');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $userIds = API::getValue("users");
        $roleNames = API::getValue("roles");

        $courseUsers = [];
        foreach ($userIds as $userId) {
            $courseUser = $course->addUserToCourse($userId);
            $courseUser->setRoles($roleNames);

            $courseUserInfo = $courseUser->getData();
            $courseUserInfo["image"] = $courseUser->getImage();
            $courseUserInfo["roles"] = $courseUser->getRoles(false);
            $courseUsers[] = $courseUserInfo;
        }

        API::response($courseUsers);
    }

    /**
     * @throws Exception
     */
    public function editCourseUser()
    {
        API::requireValues('userId', 'courseId', 'name', 'authService', 'studentNumber', 'email', 'nickname', 'username', 'major', 'image', 'roles');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        // Get values
        $name = API::getValue("name");
        $username = API::getValue("username");
        $authService = API::getValue("authService");
        $email = API::getValue("email");
        $studentNumber = API::getValue("studentNumber", "int");
        $nickname = API::getValue("nickname");
        $major = API::getValue("major");
        $image = API::getValue("image");
        $rolesNames = API::getValue("roles", "array");

        // Edit user
        $user->editUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $user->isAdmin(), $user->isActive());
        if ($image) $user->setImage($image);

        // Edit user roles
        $courseUser = $course->getCourseUserById($userId);
        $courseUser->updateCourseUserRoles($rolesNames);

        $courseUserInfo = $courseUser->getData();
        $courseUserInfo["image"] = $courseUser->getImage();
        $courseUserInfo["roles"] = $courseUser->getRoles(false);
        API::response($courseUserInfo);
    }

    /**
     * @throws Exception
     */
    public function removeUserFromCourse()
    {
        API::requireValues('courseId', 'userId');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $userId = API::getValue("userId", "int");
        $course->removeUserFromCourse($userId);
    }

    /**
     * @throws Exception
     */
    public function setCourseUserActive()
    {
        API::requireValues('courseId', 'userId', 'isActive');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $userId = API::getValue("userId", "int");
        $courseUser = API::verifyCourseUserExists($course, $userId);

        $isActive = API::getValue("isActive", "bool");
        $courseUser->setActive($isActive);
    }

    /**
     * Checks whether a user is a user of a course.
     *
     * @return void
     * @throws Exception
     */
    public function isCourseUser()
    {
        API::requireValues("userId", "courseId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        // Only course admins can access other users' information
        if (Core::getLoggedUser()->getId() != $userId)
            API::requireCourseAdminPermission($course);

        $courseUser = $course->getCourseUserById($userId);
        API::response(!!$courseUser);
    }

    /**
     * Checks whether a user is a teacher of a course.
     *
     * @return void
     * @throws Exception
     */
    public function isTeacher()
    {
        API::requireValues("userId", "courseId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        // Only course admins can access other users' information
        if (Core::getLoggedUser()->getId() != $userId)
            API::requireCourseAdminPermission($course);

        $courseUser = API::verifyCourseUserExists($course, $userId);
        API::response($courseUser->isTeacher());
    }

    /**
     * Checks whether a user is a student of a course.
     *
     * @return void
     * @throws Exception
     */
    public function isStudent()
    {
        API::requireValues("userId", "courseId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        // Only course admins can access other users' information
        if (Core::getLoggedUser()->getId() != $userId)
            API::requireCourseAdminPermission($course);

        $courseUser = API::verifyCourseUserExists($course, $userId);
        API::response($courseUser->isStudent());
    }

    /**
     * Gets number of active students in a specific course
     *
     * @return void
     * @throws Exception
     */
    public function getActiveStudents(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $response = $course->getStudents(true);
        API::response($response);
    }

    /**
     * @throws Exception
     */
    public function refreshCourseUserActivity()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $courseUser = $course->getCourseUserById(Core::getLoggedUser()->getId());
        $courseUser->refreshActivity();
        API::response($courseUser->getLastActivity());
    }

    /**
     * Import users into a given course.
     *
     * @param int $courseId
     * @param $file
     * @param bool $replace
     * @throws Exception
     */
    public function importCourseUsers()
    {
        API::requireValues("courseId", "file", "replace");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $file = API::getValue("file");
        $replace = API::getValue("replace", "bool");

        $nrUsersImported = CourseUser::importCourseUsers($courseId, $file, $replace);
        API::response($nrUsersImported);
    }

    /**
     * Export users from a given course into a .csv file.
     *
     * @param $courseId
     * @param $userIds
     * @throws Exception
     */
    public function exportCourseUsers()
    {
        API::requireValues("courseId", "userIds");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $userIds = API::getValue("userIds", "array");
        $csv = CourseUser::exportCourseUsers($courseId, $userIds);

        API::response($csv);
    }


    /*** --------------------------------------------- ***/
    /*** ------------------- Roles ------------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function getDefaultRoles()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        API::response(Role::DEFAULT_ROLES);
    }

    /**
     * @throws Exception
     */
    public function getAdaptationRoles()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $onlyParents = API::getValue("onlyParents", "bool") ?? false;

        $roles = Role::getAdaptationCourseRoles($courseId, $onlyParents);
        API::response($roles);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function getAdaptationGeneralParent(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $response = Role::getAdaptationGeneralParent($courseId);
        API::response($response);
    }

    /**
     * @throws Exception
     */
    public function getRoles()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $onlyNames = API::getValue("onlyNames", "bool") ?? true;
        $sortByHierarchy = API::getValue("sortByHierarchy", "bool") ?? false;

        API::response($course->getRoles($onlyNames, $sortByHierarchy));
    }

    /**
     * @throws Exception
     */
    public function updateRoles()
    {
        API::requireValues("courseId", "hierarchy", "roles");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $hierarchy = API::getValue("hierarchy");
        $roles = API::getValue("roles");

        $course->updateCourseRoles($roles);
        $course->setRolesHierarchy($hierarchy);
    }

    /*** --------------------------------------------- ***/
    /*** ------------------ Modules ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Get course module by its ID.
     *
     * @return void
     * @throws Exception
     */
    public function getModuleById()
    {
        API::requireValues("courseId", "moduleId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $moduleId = API::getValue("moduleId");
        $module = API::verifyModuleExists($moduleId, $course);

        $moduleInfo = $module->getData();
        $moduleInfo = Module::getExtraInfo($moduleInfo, $course);
        API::response($moduleInfo);
    }

    /**
     * @throws Exception
     */
    public function getModules()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $enabled = API::getValue("enabled");
        API::response($course->getModules($enabled));
    }

    public function getModulesResources()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $enabled = API::getValue("enabled", "bool");
        API::response($course->getModulesResources($enabled));
    }

    /**
     * @throws Exception
     */
    public function setModuleState()
    {
        API::requireValues("courseId", "moduleId", "state");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $moduleId = API::getValue("moduleId");
        $module = API::verifyModuleExists($moduleId, $course);

        $state = API::getValue("state", "bool");
        $course->setModuleState($moduleId, $state);
    }


    /*** --------------------------------------------- ***/
    /*** ---------------- Course Data ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Get contents of course data folder.
     *
     * @throws Exception
     */
    public function getCourseDataFolderContents()
    {
        API::requireValues("courseId", "moduleId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $module = API::getValue("moduleId") ?? null;

        if ($module == "skills"){
            $skillsModule = new Skills(new Course($courseId));
            API::response($skillsModule->getDataFolderContents());

        } else if ($module == "badges") {
            $badgesModule = new Badges(new Course($courseId));
            API::response($badgesModule->getDataFolderContents());

        } // complete here with more modules if needed

        else {
            API::response($course->getDataFolderContents());
        }

    }

    /**
     * Uploads file to a given location on course data folder.
     *
     * @param int $courseId
     * @param string $file
     * @param string $folder
     * @param string $filename
     * @throws Exception
     */
    public function uploadFile()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        API::requireValues("file", "folder", "fileName");
        $base64 = API::getValue("file");
        $to = API::getValue("folder");
        $filename = API::getValue("fileName");

        API::response($course->uploadFile($to, $base64, $filename));
    }

    /**
     * Uploads file to a given location on course data folder.
     *
     * @param int $courseId
     * @param string $file
     * @param string $folder
     * @param string $filename
     * @throws Exception
     */
    public function deleteFile()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        API::requireValues("folder", "fileName", "deleteIfEmpty");
        $from = API::getValue("folder");
        $filename = API::getValue("fileName");
        $deleteIfEmpty = API::getValue("deleteIfEmpty", "bool");

        $course->deleteFile($from, $filename, $deleteIfEmpty);
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Styling ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Get course styles.
     *
     * @throws Exception
     */
    public function getStyles()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        API::response($course->getStyles());
    }

    /**
     * Update course styles.
     *
     * @throws Exception
     */
    public function updateStyles()
    {
        API::requireValues("courseId", "styles");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $styles = API::getValue("styles");
        $course->updateStyles($styles);
    }


    /*** --------------------------------------------- ***/
    /*** -------------- Import / Export -------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Import courses into the system.
     *
     * @param $file
     * @param bool $replace
     * @throws Exception
     */
    public function importCourses()
    {
        API::requireAdminPermission();
        API::requireValues("file", "replace");

        $file = API::getValue("file");
        $replace = API::getValue("replace", "bool");

        $nrCoursesImported = Course::importCourses($file, $replace);
        API::response($nrCoursesImported);
    }

    
    /*** --------------------------------------------- ***/
    /*** ---------------- DB Explorer ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Returns participations in a course.
     *
     * @throws Exception
     */
    public function getParticipations()
    {
        API::requireValues('courseId');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $participations = AutoGame::getParticipations($courseId);

        $userIds = array_unique(array_column($participations, 'user'));
        $evaluatorIds = array_filter(array_unique(array_column($participations, 'evaluator')));
        $userIds = array_unique(array_merge($userIds, $evaluatorIds));

        $users = [];
        foreach ($userIds as $userId) {
            $users[$userId] = User::getUserById($userId)->getName();
        }

        foreach ($participations as &$participation) {
            $participation["user"] = $users[$participation["user"]];
            $participation["evaluatorName"] = $participation["evaluator"] ? $users[$participation["evaluator"]] : "";
        }

        API::response($participations);
    }

    /**
     * Returns awards in a course.
     *
     * @throws Exception
     */
    public function getAwards()
    {
        API::requireValues('courseId');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $awards = (new Awards($course))->getCourseAwards();

        $userIds = array_unique(array_column($awards, "user"));
        $users = [];
        foreach ($userIds as $userId) {
            $users[$userId] = (new User($userId))->getName();
        }

        foreach ($awards as &$award) {
            $award["user"] = $users[$award["user"]];
        }

        API::response($awards);
    }

    /**
     * Deletes a participation in a course given its id.
     *
     * @throws Exception
     */
    public function deleteParticipation()
    {
        API::requireValues('courseId', 'participationId');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $participationId = API::getValue("participationId", "int");
        AutoGame::removeParticipation($participationId);
    }

    /**
     * Deletes an award in a course given its id.
     *
     * @throws Exception
     */
    public function deleteAward()
    {
        API::requireValues('courseId', 'awardId');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $awardId = API::getValue("awardId", "int");
        Awards::removeAward($awardId);
    }

    /**
     * Edits a participation in a course.
     *
     * @throws Exception
     */
    public function editParticipation()
    {
        API::requireValues('courseId', 'participation');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $participation = API::getValue("participation", "array");
        AutoGame::updateParticipation($participation["id"], $participation["description"],
            $participation["type"], $participation["date"], $participation["source"],
            $participation["post"], $participation["rating"], $participation["evaluator"]);
    }

    /**
     * Edits a participation in a course.
     *
     * @throws Exception
     */
    public function editAward()
    {
        API::requireValues('courseId', 'award');

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $award = API::getValue("award", "array");
        Awards::updateAward($award["id"], $award["description"], $award["type"],
            $award["reward"], $award["date"], $award["moduleInstance"]);
    }
}
