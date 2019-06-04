<?php
namespace SmartBoards;

use \Utils as Utils;
use \SmartBoards as SmartBoards;

class Settings {

    public static function addTab($item) {
        static::$tabs[] = $item;
    }

    public static function getTabs() {
        if (!static::$tabsInitialized) {
            $course = Course::getCourse(API::getValue('course'));
            $enabledModules = $course->getEnabledModules();
            $configTabs=[];
            $configTabs[]= static::buildTabItem('Students','course.settings.students',true );
            $configTabs[]= static::buildTabItem('Teachers','course.settings.teachers',true );
            if (in_array("skills", $enabledModules))
                $configTabs[]= static::buildTabItem('Skill Tree','course.settings.skills',true );
            if (in_array("badges", $enabledModules))
                $configTabs[]= static::buildTabItem('Badges','course.settings.badges',true );
            if (in_array("xp", $enabledModules))
                $configTabs[]= static::buildTabItem('Levels','course.settings.levels',true );
            static::addTab(static::buildTabItem('Configurations', 'course.settings.config', false, $configTabs));
            
            $childTabs = array();
            Utils::goThroughRoles($course->getRolesHierarchy(), function($roleName, $hasChildren, $continue, &$parent) {
                $children = array();
                if ($hasChildren)
                    $continue($children);
                $parent[] = Settings::buildTabItem($roleName, 'course.settings.roles.role({role:\'' . $roleName . '\'})', true, $children);
            }, $childTabs);

            $defaultViewTab = Settings::buildTabItem('Default', 'course.settings.roles.role({role:\'Default\'})', true, $childTabs);
            static::addTab(static::buildTabItem('Roles', 'course.settings.roles', true, array($defaultViewTab)));

            foreach($course->getModules() as $module) {
                $module->initSettingsTabs();
            }
            static::$tabsInitialized = true;
        }
        return static::$tabs;
    }

    public static function buildTabItem($text, $ref, $isSRef = false, $subItems = array(), $additional = array()) {
        $item = array('text' => $text, ($isSRef ? 'sref' : 'href') => $ref);
        if ($subItems != array())
            $item['subItems'] = $subItems;
        return array_merge($item, $additional);
    }

    private static $tabsInitialized = false;
    private static $tabs = array();
}
?>
