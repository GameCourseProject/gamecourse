<?php

namespace API;

use Exception;
use GameCourse\Adaptation\GameElement;
use GameCourse\Role\Role;

class AdaptationController
{
    /*** --------------------------------------------- ***/
    /*** --------------- Game Elements --------------- ***/
    /*** --------------------------------------------- ***/


    public function gameElementsUserCanEdit(){
        API::requireValues('courseId', 'userId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $userId = API::getValue("userId", "int");

        API::verifyUserExists($userId);
        API::verifyCourseUserExists($course, $userId);

        $gameElements = GameElement::gameElementsUserCanEdit($courseId, $userId);

        foreach ($gameElements as &$gameElementInfo) {
            $gameElement = GameElement::getGameElementById($gameElementInfo["id"]);
        }

        API::response($gameElements);

    }

    /**
     * Gets all users allowed to edit a specific GameElement
     * @throws Exception
     */
    public function getGameElementUsers(){
        API::requireAdminPermission();
        API::requireValues('courseId', 'moduleId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        $users = GameElement::getGameElementUsers($courseId, $moduleId);

        API::response($users);
    }

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
        API::requireValues('course', 'user', 'moduleId', 'previousPreference', 'newPreference', 'date');

        $courseId = API::getValue('course', "int");
        $course = API::verifyCourseExists($courseId);

        $userId = API::getValue('user', "int");
        $user = API::verifyUserExists($userId);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        // Get rest of the values
        $previousPreference = API::getValue('previousPreference');
        $newPreference = API::getValue('newPreference');
        $date = API::getValue('date') ?? date("Y-m-d h:i:sa");

        $newPreferenceArg = Role::getRoleId($newPreference, $courseId);
        GameElement::updateUserPreference($courseId, $userId, $moduleId, $previousPreference, $newPreferenceArg, $date);
    }
}