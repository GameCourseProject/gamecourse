<?php
namespace GameCourse;

use \Utils as Utils;
use \GameCourse as GameCourse;

class Settings {

    public static function addTab($item) {
        static::$tabs[] = $item;
    }

    public static function getTabs() {
        if (!static::$tabsInitialized) {
            $course = Course::getCourse(API::getValue('course'));
            $enabledModules = $course->getEnabledModules();
            $configTabs=[];
            //$configTabs[]= static::buildTabItem('Students','course.settings.students',true );
            //$configTabs[]= static::buildTabItem('Teachers','course.settings.teachers',true );
            if (in_array("skills", $enabledModules))
                $configTabs[]= static::buildTabItem('Skill Tree','course.settings.skills',true );
            if (in_array("badges", $enabledModules))
                $configTabs[]= static::buildTabItem('Badges','course.settings.badges',true );
            if (in_array("xp", $enabledModules))
                $configTabs[]= static::buildTabItem('Levels','course.settings.levels',true );
            if (in_array("plugin", $enabledModules))
                $configTabs[]= static::buildTabItem('Plugins','course.settings.plugins',true );
            static::addTab(static::buildTabItem('Configurations', 'course.settings.config', false, $configTabs));
            
            // $childTabs = array();
            // Utils::goThroughRoles($course->getRolesHierarchy(), function($role, $hasChildren, $continue, &$parent) {
            //     $children = array();
            //     if ($hasChildren)
            //         $continue($children);
            //     $shortName=str_replace(' ', '', $role["name"]);
            //     $parent[] = Settings::buildTabItem($role["name"], 'course.settings.roles.role({role:\''.$shortName.'\',id:'.$role["id"].'})', true, $children);
            // }, $childTabs);

            // $defaultViewTab = Settings::buildTabItem('Default', 'course.settings.roles.role({role:\'Default\',id:0})', true, $childTabs);
            //static::addTab(static::buildTabItem('Roles', 'course.settings.roles', true)); //, array($defaultViewTab)

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
