<?php
namespace Modules\Quest;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class Quest extends Module
{
    const ID = 'quest';

    const LEVEL_NO_EXIST = 'Hummm.. This level does not seem to exist.';
    const QUEST_ANNOUNCE_TEMPLATE = 'Quest Announce - by quest';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $user = $this->getParent()->getLoggedUser();
        /*$questData = $this->getData();
        if (!$questData->hasKey('activeQuest')) {
            $questData->set('activeQuest', -1);
            $questData->set('quests', array());
        }

        $activeQuest = $questData->get('activeQuest');

        $startTime = $questData->getWrapped('quests')->getWrapped($activeQuest)->get('startTime');
        $endTime = $questData->getWrapped('quests')->getWrapped($activeQuest)->get('endTime');

        $hasQuest = $activeQuest >= 0 && $startTime < time() && $endTime > time();
        $globalUser = Core::getLoggedUser();
        $isTeacher = $user->hasRole('Teacher') || ($globalUser != null && $globalUser->isAdmin());
        if ($activeQuest >= 0 && ($isTeacher || $hasQuest)) {
            $subtext = '';
            if ($isTeacher) {
                if ($hasQuest)
                    $subtext = '(Live: ' . floor($endTime - time()) . 's left)';
                else {
                    if ($endTime < $startTime)
                        $subtext = 'Not set for live!';
                    else
                        $subtext = '(Not Live: ' . floor($startTime - time()) . 's to start)';
                }
            }
            Core::addNavigation( 'Quest', 'course.quest', true);
        }*/

        $this->initTemplates();
    }

    public function initTemplates() // FIXME: refactor templates
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::QUEST_ANNOUNCE_TEMPLATE))
            Views::createTemplateFromFile(self::QUEST_ANNOUNCE_TEMPLATE, file_get_contents(__DIR__ . '/quest_announce.txt'), $courseId, self::ID);
    }

    public function initAPIEndpoints()
    {
        API::registerFunction(self::ID, 'level', function() {
            API::requireValues('level');

            $userId = Core::getLoggedUser()->getId();
            /*$quest = $this->getData()->getValue();

            $activeQuest = $quest['activeQuest'];
            $resourceFolder = MODULES_FOLDER . '/quest/resources/' . $activeQuest . '/';
            if ($activeQuest < 0)*/
            API::response(array('error' => 'No quest running!'));
            /*
                        $quest = $quest['quests'][$activeQuest];

                        $globalUser = Core::getLoggedUser();
                        $user = $this->getParent()->getLoggedUser();
                        $isTeacher = $user->hasRole('Teacher') || ($globalUser != null && $globalUser->isAdmin());

                        $isQuestLive = $activeQuest >= 0 && $quest['startTime'] < time() && $quest['endTime'] > time();
                        if (!$isQuestLive && !$isTeacher)
                            API::response(array('error' => 'No quest running!'));

                        $questWrapped = $this->getData()->getWrapped('quests')->getWrapped($activeQuest);
                        $timeout = $quest['timeout'];
                        $rateLimit = $quest['rateLimit'];

                        $levelKeyworld = API::getValue('level');
                        if (array_key_exists($levelKeyworld, $quest['levels'])) {
                            $level = $quest['levels'][$levelKeyworld];

                            if ($level <= $quest['currentLevel']) {
                                $currentUnlocked = $quest['levelsInfo'][$quest['currentLevel']]['keyword'];

                                $levelInfo = $quest['levelsInfo'][$level];

                                $this->saveVisit($questWrapped, $userId, time(), $levelKeyworld);
                                $page = str_replace('$$resource_dir$$', $resourceFolder, $levelInfo['page']) . ($quest['currentLevel'] == $level ? '' : '<div class="quest-next">Current unlocked level: <a ui-sref="course.questLevel({level: \'' . $currentUnlocked . '\'})" style="color: white;">' . $currentUnlocked .'</a></div>');
                                API::response(array('level' => ($level + 1), 'title' => $levelInfo['title'], 'page' => $page));
                            } else if ($quest['currentLevel'] + 1 == $level) {
                                if (!$this->canTry($quest, $userId, $rateLimit)) {
                                    $this->saveTry($questWrapped, $userId, time(), API::getValue('level'), 'breakButCorrect');
                                    API::response(array('control' => 'Hold on adventurer! You tried to unlock this level so many times in the last hour. You should take a break and try again later.'));
                                }

                                $levelInfo = $quest['levelsInfo'][$level];
                                if ($levelInfo['requiresValidation']) {
                                    $this->saveSolution($questWrapped, $userId, $level, time(), 'required', false);
                                    API::response(array('validation' => 'png'));
                                }

                                $lastSolution = $this->getLastSolution($quest);
                                if (time() < ($lastSolution + $timeout)) {
                                    $this->saveSolution($questWrapped, $userId, $level, time(), 'correctWaitRateLimit', false);
                                    API::response(array('control' => 'Hold on adventurer! The solution is correct, however you must wait ' . round(($lastSolution + $timeout) - time()) . ' seconds to explore what lies ahead.'));
                                }

                                $this->saveSolution($questWrapped, $userId, $level, time(), 'notRequired', true);

                                // unlock level
                                $questWrapped->set('currentLevel', $level);
                                API::response(array('level' => ($level + 1), 'title' => $levelInfo['title'], 'page' => str_replace('$$resource_dir$$', $resourceFolder, $levelInfo['page'])));
                            } else {
                                if (!$this->canTry($quest, $userId, $rateLimit)) {
                                    $this->saveTry($questWrapped, $userId, time(), API::getValue('level'), 'break');
                                    API::response(array('control' => 'Hold on adventurer! You tried to unlock this level so many times in the last hour. You should take a break and try again later.'));
                                }

                                $this->saveTry($questWrapped, $userId, time(), API::getValue('level'));
                                API::response(array('error' => self::LEVEL_NO_EXIST));
                            }
                        } else {
                            if (!$this->canTry($quest, $userId, $rateLimit)) {
                                $this->saveTry($questWrapped, $userId, time(), API::getValue('level'), 'break');
                                API::response(array('control' => 'Hold on adventurer! You tried to unlock this level so many times in the last hour. You should take a break and try again later.'));
                            }

                            $this->saveTry($questWrapped, $userId, time(), API::getValue('level'));
                            API::response(array('error' => self::LEVEL_NO_EXIST));
                        }*/
        });

        API::registerFunction(self::ID, 'questSolution', function() {
            API::requireValues('level');

            $userId = Core::getLoggedUser()->getId();
            /*$levelKeyword = API::getValue('level');

            $quest = $this->getData()->getValue();
            $activeQuest = $quest['activeQuest'];
            $resourceFolder = MODULES_FOLDER . '/quest/resources/' . $activeQuest . '/';

            if ($activeQuest < 0)*/
            API::response(array('error' => 'No quest running!'));
            /*
                        $quest = $quest['quests'][$activeQuest];
                        $questWrapped = $this->getData()->getWrapped('quests')->getWrapped($activeQuest);
                        $timeout = $quest['timeout'];
                        $rateLimit = $quest['rateLimit'];

                        if (!$this->canTry($quest, $userId, $rateLimit)) {
                            $this->saveTry($questWrapped, $userId, time(), API::getValue('level'), 'break');
                            API::response(array('control' => 'Hold on adventurer! You tried to unlock this level so many times in the last hour. You should take a break and try again later.'));
                        }

                        if (!array_key_exists($levelKeyword, $quest['levels'])) {
                            $this->saveTry($questWrapped, $userId, time(), API::getValue('level'));
                            API::response(array('error' => self::LEVEL_NO_EXIST));
                        }

                        $level = $quest['levels'][$levelKeyword];

                        if ($level <= $quest['currentLevel']) {
                            $this->saveVisit($questWrapped, $userId, time(), $levelKeyword);
                            $levelInfo = $quest['levelsInfo'][$level];
                            API::response(array('level' => ($level + 1), 'title' => $levelInfo['title'], 'page' => str_replace('$$resource_dir$$', $resourceFolder, $levelInfo['page'])));
                        } else if ($quest['currentLevel'] + 1 == $level) {
                            $levelInfo = $quest['levelsInfo'][$level];
                            if ($levelInfo['requiresValidation']) {
                                // validate level
                                $img = imagecreatefrompng($resourceFolder . $levelInfo['validation']['solution']);
                                $img2 = @imagecreatefromstring(API::getUploadedFile());
                                if ($img2 === FALSE) {
                                    $this->saveSolution($questWrapped, $userId, $level, time(), 'proofUnsupported', false);
                                    API::response(array('error' => 'Unsupported file type!'));
                                }

                                if (comparePNGImages($img, $img2)) {
                                    $lastSolution = $this->getLastSolution($quest);
                                    if (time() < ($lastSolution + $timeout)) {
                                        $this->saveSolution($questWrapped, $userId, $level, time(), 'correctWaitTimeout', false);
                                        API::response(array('control' => 'Hold on adventurer! The solution is correct, however you must wait ' . round(($lastSolution + $timeout) - time()) . ' seconds to explore what lies ahead.'));
                                    }

                                    $this->saveSolution($questWrapped, $userId, $level, time(), 'proofCorrect', true);
                                    // unlock level
                                    $this->getData()->getWrapped('quests')->getWrapped($activeQuest)->set('currentLevel', $level);
                                    API::response(array('level' => ($level + 1), 'title' => $levelInfo['title'], 'page' => str_replace('$$resource_dir$$', $resourceFolder, $levelInfo['page'])));
                                } else {
                                    $this->saveSolution($questWrapped, $userId, $level, time(), 'proofIncorrect', false);
                                    API::response(array('error' => 'The solution does not match!'));
                                }

                            } else {
                                $lastSolution = $this->getLastSolution($quest);
                                if (time() < ($lastSolution + $timeout)) {
                                    $this->saveSolution($questWrapped, $userId, $level, time(), 'correctWaitTimeout', false);
                                    API::response(array('control' => 'Hold on adventurer! The solution is correct, however you must wait ' . round(($lastSolution + $timeout) - time()) . ' seconds to explore what lies ahead.'));
                                }

                                $this->saveSolution($questWrapped, $userId, $level, time(), 'notRequired', true);
                                // unlock level through upload but no validation?  its a bit wierd but ok
                                $this->getData()->getWrapped('quests')->getWrapped($activeQuest)->set('currentLevel', $level);
                                API::response(array('level' => ($level + 1), 'title' => $levelInfo['title'], 'page' => str_replace('$$resource_dir$$', $resourceFolder, $levelInfo['page'])));
                            }
                        } else {
                            $this->saveTry($questWrapped, $userId, time(), API::getValue('level'));
                            API::response(array('error' => self::LEVEL_NO_EXIST));
                        }*/
        });

        API::registerFunction(self::ID, 'questInfo', function() {
            API::requireCourseAdminPermission();
            API::requireValues('quest');

            $selectedQuest = API::getValue('quest');
            /*
                        $questModuleData = $this->getData()->getValue();
                        if (!array_key_exists($selectedQuest, $questModuleData['quests']))
                         */   API::error('Unknown quest', 404);

            /*$quest = $questModuleData['quests'][$selectedQuest];

            $resources = array();
            $folder = MODULES_FOLDER . '/quest/resources/' . $selectedQuest . '/';
            if (file_exists($folder)) {
                $resourcesDir = dir($folder);
                while (($resourceName = $resourcesDir->read()) !== false) {
                    if ($resourceName == '.' || $resourceName == '..')
                        continue;
                    $resources[] = $resourceName;
                }
                $resourcesDir->close();
            }

            ksort($quest['levelsInfo']);
            $quest['levelsInfo'] = array_values($quest['levelsInfo']);
            foreach ($quest['levelsInfo'] as &$level)
                if ($level['requiresValidation'] == '1')
                    $level['requiresValidation'] = true;
                else
                    $level['requiresValidation'] = false;

            API::response(array(
                'currentLevel' => $quest['currentLevel'],
                'levels' => $quest['levelsInfo'],
                'rateLimit' => $quest['rateLimit'],
                'timeout' => $quest['timeout'],
                'startTime' => $quest['startTime'],
                'endTime' => $quest['endTime'],
                'resources' => $resources
            ));*/
        });

        API::registerFunction(self::ID, 'saveLevels', function() {
            API::requireCourseAdminPermission();
            API::requireValues('quest', 'levels');

            $selectedQuest = API::getValue('quest');
            $levelsInfo = API::getValue('levels');

            /*$questModuleData = $this->getData()->getValue();
            if (!array_key_exists($selectedQuest, $questModuleData['quests']))
            */    API::error('Unknown quest', 404);

            /*$quest = $questModuleData['quests'][$selectedQuest];
            $quest['levelsInfo'] = $levelsInfo;
            $levels = array();
            foreach ($levelsInfo as $num => $level) {
                $levels[$level['keyword']] = $num;
            }
            $quest['levels'] = $levels;
            $this->getData()->getWrapped('quests')->set($selectedQuest, $quest);*/
        });

        API::registerFunction(self::ID, 'setLevel', function() {
            API::requireCourseAdminPermission();
            API::requireValues('quest', 'level');

            $selectedQuest = API::getValue('quest');
            $level = API::getValue('level');

            /*$questModuleData = $this->getData()->getValue();
            if (!array_key_exists($selectedQuest, $questModuleData['quests']))
             */   API::error('Unknown quest', 404);

            /*$quest = $questModuleData['quests'][$selectedQuest];
            $quest['currentLevel'] = $level;
            $this->getData()->getWrapped('quests')->set($selectedQuest, $quest);*/
        });

        API::registerFunction(self::ID, 'setRateLimit', function() {
            API::requireCourseAdminPermission();
            API::requireValues('quest', 'rateLimit');

            $selectedQuest = API::getValue('quest');
            $rateLimit = API::getValue('rateLimit');

            /*$questModuleData = $this->getData()->getValue();
            if (!array_key_exists($selectedQuest, $questModuleData['quests']))
            */    API::error('Unknown quest', 404);

            /*$quest = $questModuleData['quests'][$selectedQuest];
            $quest['rateLimit'] = $rateLimit;
            $this->getData()->getWrapped('quests')->set($selectedQuest, $quest);*/
        });

        API::registerFunction(self::ID, 'setTimeout', function() {
            API::requireCourseAdminPermission();
            API::requireValues('quest', 'timeout');

            $selectedQuest = API::getValue('quest');
            $timeout = API::getValue('timeout');

            /*$questModuleData = $this->getData()->getValue();
            if (!array_key_exists($selectedQuest, $questModuleData['quests']))
             */   API::error('Unknown quest', 404);

            /*$quest = $questModuleData['quests'][$selectedQuest];
            $quest['timeout'] = $timeout;
            $this->getData()->getWrapped('quests')->set($selectedQuest, $quest);*/
        });

        API::registerFunction(self::ID, 'setStartTime', function() {
            API::requireCourseAdminPermission();
            API::requireValues('quest', 'startTime');

            $selectedQuest = API::getValue('quest');
            $startTime = API::getValue('startTime');

            /*$questModuleData = $this->getData()->getValue();
            if (!array_key_exists($selectedQuest, $questModuleData['quests']))
            */    API::error('Unknown quest', 404);

            /*$quest = $questModuleData['quests'][$selectedQuest];
            $quest['startTime'] = $startTime;
            $this->getData()->getWrapped('quests')->set($selectedQuest, $quest);*/
        });

        API::registerFunction(self::ID, 'setEndTime', function() {
            API::requireCourseAdminPermission();
            API::requireValues('quest', 'endTime');

            $selectedQuest = API::getValue('quest');
            $endTime = API::getValue('endTime');

            /*$questModuleData = $this->getData()->getValue();
            if (!array_key_exists($selectedQuest, $questModuleData['quests']))
            */    API::error('Unknown quest', 404);

            /*$quest = $questModuleData['quests'][$selectedQuest];
            $quest['endTime'] = $endTime;
            $this->getData()->getWrapped('quests')->set($selectedQuest, $quest);*/
        });

        API::registerFunction(self::ID, 'resetStats', function() {
            API::requireCourseAdminPermission();
            API::requireValues('quest');

            $selectedQuest = API::getValue('quest');

            /*$questModuleData = $this->getData()->getValue();
            if (!array_key_exists($selectedQuest, $questModuleData['quests']))
             */   API::error('Unknown quest', 404);

            //$this->getData()->getWrapped('quests')->getWrapped($selectedQuest)->set('info', array('lastSolution' => 0, 'users'=>array('tries' => array(), 'visits' => array(), 'solution' => array())));
        });

        API::registerFunction(self::ID, 'uploadResource', function() {
            API::requireCourseAdminPermission();
            API::requireValues('resource', 'quest');

            /*$fileName = str_replace('/', '_', API::getValue('resource'));
            if ($fileName == '.')
                API::error('Stop hacking please..!', 418);

            $quest = API::getValue('quest');
            if (!preg_match('/^[0-9]+$/', $quest)) {
                API::error('Invalid quest!', 400);
            }

            $quest = $quest;

            $folder = MODULES_FOLDER . '/quest/resources/' . $quest . '/';
            if (!file_exists($folder))
                mkdir($folder, 777, true);
            file_put_contents($folder . $fileName, API::getUploadedFile());*/
        });

        API::registerFunction(self::ID, 'deleteResource', function() {
            API::requireCourseAdminPermission();
            API::requireValues('resource', 'quest');

            /*$fileName = str_replace('/', '_', API::getValue('resource'));
            if ($fileName == '.')
                API::error('Stop hacking please..!', 418);

            $quest = API::getValue('quest');
            if (!preg_match('/^[0-9]+$/', $quest)) {
                API::error('Invalid quest!', 400);
            }

            $quest = $quest;
            $file = MODULES_FOLDER . '/quest/resources/' . $quest . '/' . $fileName;
            if (file_exists($file))
                unlink($file);*/
        });

        API::registerFunction(self::ID, 'settings', function() {
            API::requireCourseAdminPermission();

            /*if (API::hasKey('activeQuest')) {
                $this->getData()->set('activeQuest', API::getValue('activeQuest'));
            } else if (API::hasKey('deleteQuest')) {
                $quest = API::getValue('deleteQuest');
                $activeQuest = $this->getData()->get('activeQuest');
                if ($activeQuest == $quest)
                    $this->getData()->set('activeQuest', -1);
                $quests = $this->getData()->get('quests');
                unset($quests[$quest]);
                $this->getData()->set('quests', $quests);
            } else if (API::hasKey('createQuest')) {
                $quests = array_keys($this->getData()->get('quests', array()));
                $end = end($quests);
                $newQuest = 0;
                if ($end !== false)
                    $newQuest = $end + 1;
                $this->getData()->getWrapped('quests')->set($newQuest, array(
                    'currentLevel' => -1,
                    'levels' => array(),
                    'levelsInfo' => array(),
                    'rateLimit' => 0,
                    'timeout' => 0,
                    'startTime' => 0,
                    'endTime' => 0
                ));
                API::response($newQuest);
            } else {
                $activeQuest = $this->getData()->get('activeQuest');
                $quests = array_keys($this->getData()->get('quests', array()));
                API::response(array('activeQuest' => $activeQuest, 'quests' => $quests));
            }*/
        });
    }

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/quest.css');
        parent::addResources('css/jquery.datetimepicker.css');
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getLastSolution($quest) {
        return $quest['info']['lastSolution'];
    }

    public function canTry($quest, $user, $rateLimit) {
        /*$questInfo = $quest['info'];
        if (!array_key_exists('tries', $questInfo['users']))
            $questInfo['users']['tries'] = array();
        $tries = $questInfo['users']['tries'];
        if (!array_key_exists($user, $tries))
            return true;
        $count = 0;
        $time = time() - 3600;
        $tries = $tries[$user];
        $numTries = count($tries);
        for($i = $numTries - 1; $i >= 0; --$i) {
            if ($tries[$i][0] < $time)
                break;
            else if ($tries[$i][0] >= $time && strpos($tries[$i][2], 'break') === FALSE)
                $count++;
        }

        return $count < $rateLimit;*/
    }

    public function saveVisit($quest, $user, $time, $level) {
        /*$questInfo = $quest->get('info');
        $visits = &$questInfo['users']['visits'];
        if (!array_key_exists($user, $visits))
            $visits[$user] = array();
        $visits[$user][] = array($time, $level);
        $quest->set('info', $questInfo);*/
    }

    public function saveTry($quest, $user, $time, $try, $desc = 'notFound') {
        /*$questInfo = $quest->get('info');
        if (!array_key_exists('tries', $questInfo['users']))
            $questInfo['users']['tries'] = array();
        $tries = &$questInfo['users']['tries'];
        if (!array_key_exists($user, $tries))
            $visits[$user] = array();
        $tries[$user][] = array($time, $try, $desc);
        $quest->set('info', $questInfo);*/
    }

    public function saveSolution($quest, $user, $level, $time, $verification, $newSolution) {
        /*$questInfo = $quest->get('info');
        $solution = &$questInfo['users']['solution'];
        if (!array_key_exists($level, $solution))
            $solution[$level] = array();

        if ($newSolution)
            $questInfo['lastSolution'] = $time;

        $solution[$level][] = array($user, $time, $verification);
        $quest->set('info', $questInfo);*/
    }

    private function comparePNGImages($img, $img2) {
        $width = imagesx($img);
        $height = imagesy($img);
        $width2 = imagesx($img2);
        $height2 = imagesy($img2);

        if ($width != $width2 || $height != $height2) {
            return false;
        }

        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                if (imagecolorat($img, $x, $y) != imagecolorat($img2, $x, $y)) {
                    return false;
                }
            }
        }
        return true;
    }
}

ModuleLoader::registerModule(array(
    'id' => 'quest',
    'name' => 'Quest',
    'description' => 'Generates a sequence of pages that create a treasure hunt game.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new Quest();
    }
));
