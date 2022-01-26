<?php
namespace GameCourse;

class Settings {

    // FIXME: prob can delete; after refactor not being used
    public static function addTab($item) {
        static::$tabs[] = $item;
    }

    public static function getTabs() {
        if (!static::$tabsInitialized) {
            $course = Course::getCourse(API::getValue('course'), false);
            $enabledModules = $course->getEnabledModules();
            $configTabs=[];
            //$configTabs[]= static::buildTabItem('Students','course.settings.students',true );
            //$configTabs[]= static::buildTabItem('Teachers','course.settings.teachers',true );
            // if (in_array("skills", $enabledModules))
            //     $configTabs[]= static::buildTabItem('Skill Tree','course.settings.skills',true );
            // if (in_array("badges", $enabledModules))
            //     $configTabs[]= static::buildTabItem('Badges','course.settings.badges',true );
            // if (in_array("xp", $enabledModules))
            //     $configTabs[]= static::buildTabItem('Levels','course.settings.levels',true );
            // if (in_array("plugin", $enabledModules))
            //     $configTabs[]= static::buildTabItem('Plugins','course.settings.plugins',true );
            // static::addTab(static::buildTabItem('Configurations', 'course.settings.config', false, $configTabs));
            
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
