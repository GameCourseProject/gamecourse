<?php
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Course;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

class Notifications extends Module {

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/notifications.css');
    }

    public function init() {
        // TODO: iterate all courses the user is in..
        $course = $this->getParent();
        $user = $course->getLoggedUser();
        if ($user->hasRole('Student')) {
            $activity = $user->getPreviousActivity();
            $awards = $user->getData('awards');
            if (is_null($awards) || !is_array($awards))
                return;

            $notificationFor = array_filter($awards, function($award) use ($activity) {
                return $award['date'] >= $activity;
            });

            $notifications = array();
            foreach ($notificationFor as $award) {
                if ($award['type'] == 'badge') {
                    $badgeName = $award['name'];
                    $badgeLevel = $award['level'];
                    $reward = $award['reward'];
                    $badges = $course->getModuleData('badges')->get('badges');
                    $badge = $badges[$badgeName];

                    $notification = array(
                        'type' => 'badge',
                        'badgeName' => $badgeName,
                        'badgeLevel' => $badgeLevel,
                        'reward' => $reward
                    );

                    // need to find level the user is in, because this badge could not be the last one of this kind
                    $badges = $user->getData('badges');
                    $currentBadge = $badges['list'][$badgeName];
                    if ($badge['maxLevel'] == $currentBadge['level']) {
                        $notification['maxLevel'] = true;
                    } else {
                        $notification['badgeNameNext'] = $badgeName;
                        $notification['badgeLevelNext'] = $currentBadge['level'] + 1;
                        if (!array_key_exists($currentBadge['level'], $badge['count'])) {
                            $notification['badgeProgress'] = $badge['levelDesc'][$currentBadge['level']];
                        } else if ($currentBadge['progressCount'] != -1) {
                            $notification['badgeProgress'] = $currentBadge['progressCount'] . ' out of ' . $badge['count'][$currentBadge['level']] . ' points';
                        } else
                            $notification['badgeProgress'] = 'What kind of badge is this??' . $badgeName . '-' . $badgeLevel;
                    }

                    $notifications['badge-' . $badgeName . '-' . $badgeLevel] = $notification;
                } else if ($award['type'] == 'skill') {
                    $notification = array(
                        'type' => 'skill',
                        'skillName' => $award['name'],
                        'reward' => $award['reward']
                    );

                    $skillData = $course->getModuleData('skills');
                    $sbTiers = $skillData->get('skills');
                    foreach ($sbTiers as $tierNum => $tier) {
                        foreach($tier['skills'] as $skill) {
                            if ($skill['name'] == $award['name']) {
                                $notification['color'] = $skill['color'];
                                break;
                            }
                        }
                    }

                    $notifications['skill-' . $award['name']] = $notification;
                }
            }

            if (count($notifications) > 0) {
                $notificationList = new ValueWrapper($this->getData()->get('list'));
                $userNotifications = $notificationList->getWrapped($user->getId());
                foreach ($notifications as $id => $notification)
                    $userNotifications->set($id, $notification);
                $this->getData()->set('list', $notificationList->getValue());
            }
        }

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->registerFunction('checkNotifications', function($user) {
            $pendingNotifications = $this->getData()->getWrapped('list')->get($user, array());
            return new \Modules\Views\Expression\ValueNode(count($pendingNotifications) > 0);
        });

        $viewHandler->registerFunction('getNotifications', function($user) {
            $pendingNotifications = $this->getData()->getWrapped('list')->get($user, array());

            $notifications = array();
            foreach($pendingNotifications as $id => $notification) {
                $notifications[$id] = SmartBoards\DataRetrieverContinuation::buildForArray($notification);
            }

            return SmartBoards\DataRetrieverContinuation::buildForArray($notifications);
        });

        API::registerFunction('notifications', 'removeNotification', function() {
            $id = API::getValue('notification');
            $istID = Core::getLoggedUser()->getId();
            $moduleData = $this->getData();
            $notifications = $moduleData->getWrapped('list')->get($istID);
            if (array_key_exists($id, $notifications)) {
                unset($notifications[$id]);
                $moduleData->getWrapped('list')->set($istID, $notifications);
            }
        });
        
        if ($viewsModule->getTemplate('Notifications Profile - by notifications') == NULL)
            $viewsModule->setTemplate('Notifications Profile - by notifications', unserialize(file_get_contents(__DIR__ . '/notifications_profile.vt')));
    }
}

ModuleLoader::registerModule(array(
    'id' => 'notifications',
    'name' => 'Notifications',
    'version' => '0.1',
    'factory' => function() {
        return new Notifications();
    }
));
?>
