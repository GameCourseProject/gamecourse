<?php
namespace Modules\Notifications;

use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Views;
use Modules\AwardList\AwardList;

class Notifications extends Module
{
    const ID = 'notifications';

    const NOTIFICATIONS_PROFILE_TEMPLATE = 'Notifications Profile - by notifications';

    public $notificationList = []; //contains an array of notfics of current user


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        // TODO: iterate all courses the user is in..
        $course = $this->getParent();
        $courseId = $course->getId();
        $user = $course->getLoggedUser();
        $userId = $user->getId();
        if ($user->hasRole('Student')) {
            $activity = $user->getData("prevActivity");

            $awards = Core::$systemDB->selectMultiple(AwardList::TABLE, ["course" => $courseId, "student" => $userId], "awardDate");
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

        $this->initTemplates();
        $this->initDictionary();
    }

    public function initTemplates() // FIXME: refactor templates
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::NOTIFICATIONS_PROFILE_TEMPLATE))
            Views::createTemplateFromFile(self::NOTIFICATIONS_PROFILE_TEMPLATE, file_get_contents(__DIR__ . '/notifications.txt'), $courseId, self::ID);
    }

    public function initDictionary()
    {
        /*** ------------ Libraries ------------ ***/

        Dictionary::registerLibrary(self::ID, self::ID, "This library provides information regarding notifications. It is provided by the notification module.");
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/notifications.css');
        parent::addResources('imgs/');
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => Notifications::ID,
    'name' => 'Notifications',
    'description' => 'Allows email notifications when a badge or points are atributed to a student.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new Notifications();
    }
));
