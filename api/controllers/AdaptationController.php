<?php

namespace API;

use Exception;
use GameCourse\Adaptation\EditableGameElement;
use GameCourse\Role\Role;

class AdaptationController
{
    /*** --------------------------------------------- ***/
    /*** ----------- Editable Game Elements ---------- ***/
    /*** --------------------------------------------- ***/

    public function gameElementsUserAllowedToEdit(){
        API::requireValues('courseId', 'userId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $userId = API::getValue("userId", "int");

        API::verifyUserExists($userId);
        API::verifyCourseUserExists($course, $userId);

        $editableGameElements = EditableGameElement::gameElementsUserAllowedToEdit($courseId, $userId);

        foreach ($editableGameElements as &$gameElementInfo) {
            $gameElement = EditableGameElement::getEditableGameElementById($gameElementInfo["id"]);
        }

        API::response($editableGameElements);

    }

    /**
     * Updates information regarding the editableGameElement
     *
     * @return void
     * @throws Exception
     */
    public function updateEditableGameElement()
    {
        API::requireAdminPermission();
        API::requireValues('id', 'course', 'moduleId', 'isEditable', 'nDays', 'notify', 'usersMode', 'users');

        $courseId = API::getValue('course', "int");
        $course = API::verifyCourseExists($courseId);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        $gameElementId = API::getValue("id", "int");
        $editableGameElement = EditableGameElement::getEditableGameElementById($gameElementId);

        // Get rest of the values
        $isEditable = API::getValue("isEditable", "bool");
        $nDays = API::getValue("nDays", "int");
        $notify = API::getValue("notify", "bool");
        $usersMode = API::getValue("usersMode");
        $users = API::getValue("users", "array");

        foreach ($users as $userId){
            API::verifyUserExists($userId);
            API::verifyCourseUserExists($course, $userId);
        }

        // Update EditableGameElement
        $editableGameElement->updateEditableGameElement($isEditable, $nDays, $users, $usersMode, $notify);

        $gameElementInfo = $editableGameElement->getData();

        API::response($gameElementInfo);
    }

    /**
     * Gets all users allowed to edit a specific editableGameElement
     * @throws Exception
     */
    public function getEditableGameElementUsers(){
        API::requireAdminPermission();
        API::requireValues('courseId', 'moduleId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        $users = EditableGameElement::getEditableGameElementUsers($courseId, $moduleId);

        API::response($users);
    }

    /**
     * Makes editableGameElement editable true/false
     *
     * @return void
     * @throws Exception
     */
    public function setGameElementEditable()
    {
        API::requireAdminPermission();
        API::requireValues('courseId', 'moduleId', 'isEditable');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $moduleId = API::getValue('moduleId');
        $module = API::verifyModuleExists($moduleId, $course);

        $isEditable = API::getValue('isEditable', "bool");

        $editableGameElement = EditableGameElement::getEditableGameElementByModule($courseId, $moduleId);
        $editableGameElement->setEditable($isEditable);
    }

    /**
     * Gets all editableGameElement in the system
     * @throws Exception
     */
    public function getEditableGameElements()
    {
        API::requireValues('courseId');

        $courseId = API::getValue('courseId', "int");
        $course = API::verifyCourseExists($courseId);

        $isEditable = API::getValue("isEditable", "bool") ?? null;
        $onlyNames = API::getValue("onlyNames", "bool") ?? false;
        $editableGameElements = EditableGameElement::getEditableGameElements($courseId, $isEditable, $onlyNames);

        foreach ($editableGameElements as &$gameElementInfo) {
            $gameElement = EditableGameElement::getEditableGameElementById($gameElementInfo["id"]);
        }

        API::response($editableGameElements);

    }

    /**
     * Gets all children from a specific editableGameElement
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

        $gameElement = EditableGameElement::getEditableGameElementByModule($courseId, $moduleId);
        $children = $gameElement->getEditableGameElementChildren();
        API::response($children);
    }

    /**
     * Gets previous user preference of specific editableGameElement
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

        $previousPreference = EditableGameElement::getPreviousUserPreference($courseId, $userId, $module->getName());
        API::response($previousPreference);
    }

    /**
     * Updates user preference of specific editableGameElement
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

        if ($previousPreference != ""){
            $previousPreference = Role::getRoleId($previousPreference, $courseId);
        } else $previousPreference = 0; // there's no id = 0

        $newPreferenceArg = Role::getRoleId($newPreference, $courseId);
        EditableGameElement::updateUserPreference($courseId, $userId, $moduleId, $previousPreference, $newPreferenceArg, $date);
    }
}