<?php

use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Views;

class Notifications extends Module
{

    public $notificationList = []; //contains an array of notfics of current user

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/notifications.css');
        parent::addResources('imgs/');
    }

    public function init()
    {
        // TODO: iterate all courses the user is in..
        $course = $this->getParent();
        $courseId = $course->getId();
        $user = $course->getLoggedUser();
        $userId = $user->getId();
        if ($user->hasRole('Student')) {
            $activity = $user->getData("prevActivity");

            $awards = Core::$systemDB->selectMultiple("award", ["course" => $courseId, "student" => $userId], "awardDate");
            if (is_null($awards) || !is_array($awards))
                return;

            $notificationFor = array_filter($awards, function ($award) use ($activity) {
                return $award['awardDate'] >= $activity;
            });

            $notifications = array();
            foreach ($notificationFor as $award) {
                if ($award['type'] == 'badge') {
                    $badgeLevel = $award['level'];

                    $notification = array(
                        'type' => 'badge',
                        //'badgeName' => $badgeName,
                        'level' => $badgeLevel,
                        //'reward' => $reward
                        "name" => $award['name'],
                        "awardDate" => $award["awardDate"]
                    );

                    //$notifications['badge-' . $badgeName . '-' . $badgeLevel] = $notification;
                    $notifications[] = $notification;
                    $this->notificationList[] = $notification;
                } else if ($award['type'] == 'skill') {
                    $notification = array(
                        'type' => 'skill',
                        //'skillName' => $award['name'],
                        //'reward' => $award['reward']
                        "name" => $award['name'],
                        "awardDate" => $award["awardDate"]
                    );

                    //$notifications['skill-' . $award['name']] = $notification;
                    //$notifications[]=$notification;
                    $this->notificationList[] = $notification;
                }
            }

            if (count($notifications) > 0) {
                /*
                //delete old notifications
                Core::$systemDB->delete("notification",["course"=>$courseId,"student"=>$userId]);
                //set new notifications
                foreach($notifications as $notif){
                    Core::$systemDB->insert("notification", array_merge($notif,["course"=>$courseId,"student"=>$userId,]));
                }*/
            }
        }

        Dictionary::registerLibrary("notifications", "notifications", "This library provides information regarding notifications. It is provided by the notification module.");

        if (!Views::templateExists('Notifications Profile - by notifications', $this->getCourseId()))
            Views::setTemplateFromFile('Notifications Profile - by notifications', file_get_contents(__DIR__ . '/notifications.txt'), $this->getCourseId());
    }

    public function is_configurable()
    {
        return false;
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'notifications',
    'name' => 'Notifications',
    'description' => 'Allows email notifications when a badge or points are atributed to a student.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new Notifications();
    }
));
