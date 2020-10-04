<?php

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;

class Notifications extends Module
{

    public $notificationList = []; //contains an array of notfics of current user

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/notifications.css');
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

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler(); //
        $viewHandler->registerFunction('notifications', 'checkNotifications', function ($userId) {
            $pendingNotifications = $this->notificationList;

            //$courseId = $this->getParent()->getId();
            //$pendingNotifications = Core::$systemDB->selectMultiple("notification",["course"=>$courseId,"student"=>$userId]);
            return new \Modules\Views\Expression\ValueNode(count($pendingNotifications) > 0);
        }, 'library');

        $viewHandler->registerFunction('notifications', 'getNotifications', 'library', function ($userId) {
            $pendingNotifications = $this->notificationList;
            //$courseId = $this->getParent()->getId();
            //$pendingNotifications = Core::$systemDB->selectMultiple("notification natural join award",
            //                                                    ["course"=>$courseId,"student"=>$userId]);

            /*$notifications = array();
            foreach($pendingNotifications as $id => $notification) {
                $notifications[$id] = GameCourse\DataRetrieverContinuation::buildForArray($notification);
            }

            return GameCourse\DataRetrieverContinuation::buildForArray($notifications);*/
            return new \Modules\Views\Expression\ValueNode($pendingNotifications);
        }, 'library');

        API::registerFunction('notifications', 'removeNotification', function () {
            /*$id = API::getValue('notification');
            $userId = Core::getLoggedUser()->getId();
            $moduleData = $this->getData();
            $notifications = $moduleData->getWrapped('list')->get($istID);
            if (array_key_exists($id, $notifications)) {
                unset($notifications[$id]);
                $moduleData->getWrapped('list')->set($istID, $notifications);
            }*/
        });

        if (!$viewsModule->templateExists('Notifications Profile - by notifications'))
            $viewsModule->setTemplate('Notifications Profile - by notifications', file_get_contents(__DIR__ . '/notifications.txt'));
    }
}

ModuleLoader::registerModule(array(
    'id' => 'notifications',
    'name' => 'Notifications',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Notifications();
    }
));
