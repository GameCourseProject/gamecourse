<?php

namespace API;

use Exception;
use GameCourse\Adaptation\GameElement;
use GameCourse\Module\Module;
use GameCourse\Role\Role;

class AdaptationController
{
    /*** --------------------------------------------- ***/
    /*** --------------- Game Elements --------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Makes GameElement active true/false
     * Also sends notifications to users if desired
     *
     * @return void
     * @throws Exception
     */
    public function setGameElementActive()
    {
        API::requireAdminPermission();
        API::requireValues('courseId', 'moduleId', 'isActive', 'notify');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        $isActive = API::getValue('isActive', "bool");
        $notify = API::getValue('notify', "bool");

        $gameElement = GameElement::getGameElementByModule($courseId, $moduleId);
        $gameElement->setActive($isActive);
        $gameElement->setNotify($notify);

        $gameElementInfo = $gameElement->getData();

        API::response($gameElementInfo);
    }

    /**
     * Gets all GameElements in the system
     * @throws Exception
     */
    public function getGameElements()
    {
        API::requireValues('courseId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $isActive = API::getValue("isActive", "bool") ?? null;
        $onlyNames = API::getValue("onlyNames", "bool") ?? false;
        $gameElements = GameElement::getGameElements($courseId, $isActive, $onlyNames);

        foreach ($gameElements as &$gameElementInfo) {
            $gameElement = GameElement::getGameElementById($gameElementInfo["id"]);
        }

        API::response($gameElements);

    }

    /**
     * Gets all children from a specific GameElement
     *
     * @return void
     * @throws Exception
     */
    public function getChildrenGameElement()
    {
        API::requireValues('courseId', 'moduleId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        $gameElement = GameElement::getGameElementByModule($courseId, $moduleId);
        $children = $gameElement->getGameElementChildren();
        API::response($children);
    }

    /*** -------------------------------------------------------- ***/
    /*** --------------- Adaptation Questionnaire --------------- ***/
    /*** -------------------------------------------------------- ***/

    /**
     * Sees whether a specific user in a course has answered the preferences questionnaire or not
     *
     * @return void
     * @throws Exception
     */
    public function isQuestionnaireAnswered(){
        API::requireValues('courseId', 'userId', 'gameElementId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);
        API::verifyCourseUserExists($course, $userId);

        if ($user->isAStudent()){
            $gameElementId = API::getValue("gameElementId", "int");
            $gameElement = GameElement::getGameElementById($gameElementId);
            $moduleId = $gameElement->getModule();
            $module = Module::getModuleById($moduleId, $course);
            API::verifyModuleExists($module->getId(), $course);

            $response = GameElement::isQuestionnaireAnswered($courseId, $userId, $gameElementId);
            API::response($response);

        } else { throw new Exception('Unable to perform this action. User not a student'); }


    }

    /**
     * Adds a new preference questionnaire answer to the database
     *
     * @return void
     * @throws Exception
     */
    public function submitGameElementQuestionnaire(){
        API::requireValues('course', 'user', 'q1', 'element', 'q2', 'q3', 'q4');

        $courseId = API::getValue('course', "int");
        $course = API::verifyCourseExists($courseId);

        $userId = API::getValue('user', "int");
        $user = API::verifyUserExists($userId);
        API::verifyCourseUserExists($course, $userId);

        if ($user->isAStudent()){
            $q1 = API::getValue('q1');
            $q2 = API::getValue('q2') ?? null;
            $q3 = API::getValue('q3', "int") ?? null;
            $q4 = API::getValue('q4', "int") ?? null;
            $element = API::getValue('element');

            $module = Module::getModuleById($element, $course);
            API::verifyModuleExists($module->getId(), $course);

            $gameElement = GameElement::getGameElementByModule($courseId, $element);
            $gameElementId = $gameElement->getId();

            GameElement::submitGameElementQuestionnaire($courseId, $userId, $q1, $q2, $q3, $q4, $gameElementId);

        } else { throw new Exception('Unable to perform this action. User not a student'); }

    }

    /*** -------------------------------------------------------- ***/
    /*** ---------------- Adaptation Preferences ---------------- ***/
    /*** -------------------------------------------------------- ***/

    /**
     * Gets previous user preference of specific GameElement
     *
     * @return void
     * @throws Exception
     */
    public function getPreviousPreference()
    {
        API::requireValues('courseId', 'userId', 'moduleId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $userId = API::getValue('userId', "int");
        $user = API::verifyUserExists($userId);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        $previousPreference = GameElement::getPreviousUserPreference($courseId, $userId, $module->getName());
        API::response($previousPreference);
    }

    /**
     * Updates user preference of specific GameElement
     *
     * @return void
     * @throws Exception
     */
    public function updateUserPreference()
    {
        API::requireValues('course', 'user', 'moduleId', 'previousPreference', 'newPreference');

        $courseId = API::getValue('course', "int");
        $course = API::verifyCourseExists($courseId);

        $userId = API::getValue('user', "int");
        $user = API::verifyUserExists($userId);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        // Get rest of the values
        $previousPreference = API::getValue('previousPreference');
        $newPreference = API::getValue('newPreference');

        if ($previousPreference) { $previousPreference = Role::getRoleId($previousPreference, $courseId);}
        $newPreferenceArg = Role::getRoleId($newPreference, $courseId);

        GameElement::updateUserPreference($courseId, $userId, $moduleId, $previousPreference, $newPreferenceArg);
    }

    /**
     * Gets questions statistics for data presentation on frontend
     *
     * @return void
     * @throws Exception
     */
    public function getElementStatistics(){
        API::requireValues('courseId', 'gameElementId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $gameElementId = API::getValue('gameElementId', "int");

        $statistics = GameElement::getElementStatistics($course, $gameElementId);
        API::response($statistics);
    }

    /**
     * Gets number of answers in questionnaire given a course and a gameElement
     *
     * @return void
     * @throws Exception
     */
    public function getNrAnswersQuestionnaire(){
        API::requireValues('courseId', 'gameElementId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $gameElementId = API::getValue('gameElementId', "int");

        $statistics = GameElement::getNrAnswersQuestionnaire($course, $gameElementId);
        API::response($statistics);
    }

    /**
     * Export questionnaires answers of a specific game element given a course and game element id into a .csv file.
     *
     * @throws Exception
     */
    public function exportAnswersQuestionnaire(){
        API::requireValues('courseId', 'gameElementId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCourseAdminPermission($course);

        $gameElementId = API::getValue('gameElementId', "int");
        $csv = GameElement::exportAnswersQuestionnaire($courseId, $gameElementId);

        API::response($csv);
    }

}