<?php
use SmartBoards\Core;
use SmartBoards\DataSchema;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

class Badges extends Module {
    const BADGES_TEMPLATE_NAME = '(old) Badges block - by badges';
    const NEW_BADGES_TEMPLATE_NAME = 'Badges block - by badges';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/badges.css');
    }

    public function init() {
        /*DataSchema::register(array(
            DataSchema::courseUserDataFields(array(
                DataSchema::makeObject('badges', null, array(
                    DataSchema::makeField('totalxp', 'Total XP', 1000),
                    DataSchema::makeField('normalxp', 'XP from normal badges', 750),
                    DataSchema::makeField('bonusxp', 'XP from extra credits badge', 250),
                    DataSchema::makeField('countedxp', 'XP that counts toward final grade', 1000),
                    DataSchema::makeField('completedLevels', 'Number of completed levels', 12),
                    DataSchema::makeMap('list', null, DataSchema::makeField('name', 'Badge name', 'Squire'),
                        DataSchema::makeObject('badge', 'Badge', array(
                            DataSchema::makeField('level', 'Badge level', 1),
                            DataSchema::makeArray('levelTime', 'Badge awards time', DataSchema::makeField('time', 'Badge level award time', 1234567890)),
                            DataSchema::makeField('progressCount', 'Badge progress count', 5),
                            DataSchema::makeArray('progress', 'Badge progress indicators',
                                DataSchema::makeObject('indicator', 'Indicator', array(
                                    DataSchema::makeField('text', 'Text', 'IL1'),
                                    DataSchema::makeField('quality', 'Post quality', 4),
                                    DataSchema::makeField('link', 'Link', 'http://moodle/post'),
                                    DataSchema::makeField('post', 'Post', 'Re: Squire'),
                                ))
                            )
                        )),
                        function() {
                            return array('abc');
                        }
                    )
                ))
            )),
            DataSchema::courseModuleDataFields($this, array(
                DataSchema::makeMap('badges', null, DataSchema::makeField('badgeName', 'Badge Name', 'Squire'),
                    DataSchema::makeObject('badge', 'Badge', array(
                        DataSchema::makeField('name', 'Name', 'Squire'),
                        DataSchema::makeField('description', 'Description', 'Help your colleagues by writing...'),
                        DataSchema::makeField('maxLevel', 'Max Level', 3),
                        DataSchema::makeArray('xp', 'XP',
                            DataSchema::makeField('xp', 'XP for level', 100)
                        ),
                        DataSchema::makeArray('levelDesc', 'Level Description',
                            DataSchema::makeField('levelDesc', 'Description for level', 'get four points')
                        ),
                        DataSchema::makeField('extraCredit', 'Is extra credit', 'true'),
                        DataSchema::makeField('braggingRights', 'Is bragging rights', 'false'),
                        DataSchema::makeField('countBased', 'Is count based', 'true'),
                        DataSchema::makeField('postBased', 'Is post based', 'false'),
                        DataSchema::makeField('pointBased', 'Is point based', 'true'),
                        DataSchema::makeArray('count', 'Counts needed to unlock',
                            DataSchema::makeField('count', 'Count needed to unlock level', 4)
                        )
                    )),
                    function() {
                        return array('abc');
                    }
                ),
                DataSchema::makeField('totalLevels', 'Total number of badge levels', 72)
            ))
        ));
*/
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();

        $badgeCache = array();
        $viewHandler->registerFunction('userBadgesCache', function() use (&$badgeCache) {
            $course = $this->getParent();
            $courseId=$course->getId();
            
            $updated = Core::$sistemDB->select("course","lastUpdate",["id"=>$courseId]);
            $cacheId = $courseId . '-' . $updated;
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);  
            if ($hasCache) {
                $badgeCache = $cacheValue;
                return new Modules\Views\Expression\ValueNode('');
            }

            $students = $course->getUsersWithRole('Student');
            $studentsBadges = array();
            $studentsUsernames = array();

            foreach ($students as $student) {
                $userData = \SmartBoards\User::getUser($student['id'])->getData();
                $studentsUsernames[$student['id']] = $userData['username'];
                $studentsNames[$student['id']] = $userData['name'];
                $studentsCampus[$student['id']] = Core::$sistemDB->select("course_user","campus",["id"=>$student['id']]);
            }
            
            $badges = Core::$sistemDB->selectMultiple("badge",'*',["course"=>$courseId]);
            $badgeCache = array();
            $badgeCacheClean = array();
            foreach ($badges as $badge) {
                $badgeCache[$badge['name']] = array();
                $badgeCacheClean[$badge['name']] = array();
                $badgeProgressCount = array();
                $badgeLevel = array();
                $badgeStudents = Core::$sistemDB->selectMultiple("user_badge","*",
                                                    ["course"=>$courseId,"name"=>$badge['name']]);
                for ($i = 0; $i < $badge['maxLvl']; ++$i) {
                    $badgeCache[$badge['name']][$i] = array();
                    $badgeCacheClean[$badge['name']][$i] = array();
                    foreach ($badgeStudents as $studentBadge) {
                        $id = $studentBadge['student'];
            
                        if (!array_key_exists($id, $badgeLevel)) // cache
                            $badgeLevel[$id] = $studentBadge['level'];
                        
                        if (!array_key_exists($id, $badgeProgressCount)) // cache
                            $badgeProgressCount[$id] = $studentBadge['progress'];

                        if ($badgeLevel[$id] > $i) {
                            $timestamp = strtotime(Core::$sistemDB->select("badge_level_time","badgeLvlTime",
                                    ["badgeName"=>$badge['name'], "student"=> $id, "course"=>$courseId, "badgeLevel"=>$i+1]));
                            $badgeCache[$badge['name']][$i][] = array(
                                'id' => $id,
                                'name' => $studentsNames[$id],
                                'campus' => $studentsCampus[$id],
                                'username' => $studentsUsernames[$id],
                                'progress' => $badgeProgressCount[$id],
                                'timestamp' => $timestamp,
                                'when' => date('d-M-Y', $timestamp)
                            );
                        }
                    }

                    usort($badgeCache[$badge['name']][$i], function ($v1, $v2) {
                        return $v1['timestamp'] - $v2['timestamp'];
                    });
                    
                    $badgeCacheClean[$badge['name']][$i] = $badgeCache[$badge['name']][$i];
                }
            }
            
            CacheSystem::store($cacheId, $badgeCacheClean);
            return new Modules\Views\Expression\ValueNode('');
        });

        $viewHandler->registerFunction('userBadgesCacheGet', function($badgeName, $badgeLevel) use (&$badgeCache) {
            return new \Modules\Views\Expression\ValueNode($badgeCache[$badgeName][$badgeLevel]);
        });

        $viewHandler->registerFunction('userBadgesCacheDoesntHave', function($badgeName, $badgeLevel) use (&$badgeCache) {
            return new \Modules\Views\Expression\ValueNode(count($badgeCache[$badgeName][$badgeLevel]) == 0);
        });

        $viewHandler->registerFunction('indicator', function($indicator) {
            return new Modules\Views\Expression\ValueNode($indicator['indicatorText'] . ((!array_key_exists('quality', $indicator) || $indicator['quality'] == 0)? ' ' : ' (' . $indicator['quality'] . ')'));
        });

        //if ($viewsModule->getTemplate(self::BADGES_TEMPLATE_NAME) == NULL)
        //    $viewsModule->setTemplate(self::BADGES_TEMPLATE_NAME, file_get_contents(__DIR__ . '/badges.vt'),$this->getId());
        if ($viewsModule->getTemplate(self::NEW_BADGES_TEMPLATE_NAME) == NULL)
            $viewsModule->setTemplate(self::NEW_BADGES_TEMPLATE_NAME, file_get_contents(__DIR__ . '/newbadges.txt'),$this->getId());   
    }
}

ModuleLoader::registerModule(array(
    'id' => 'badges',
    'name' => 'Badges',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Badges();
    }
));
?>
