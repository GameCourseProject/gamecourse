<?php
use SmartBoards\Core;
use SmartBoards\DataSchema;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

class Badges extends Module {
    const BADGES_TEMPLATE_NAME = 'Badges block - by badges';

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

            $cacheId = $course->getId() . '-' . $course->getWrapped('lastUpdate')->getValue();
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);
            if ($hasCache) {
                $badgeCache = $cacheValue;
                $badges = $course->getModuleData('badges')->getWrapped('badges');
                $badgesNames = $badges->getKeys();
                foreach ($badgesNames as $badgeName) {
                    $maxLevel = $badges->getWrapped($badgeName)->get('maxLevel');
                    for ($i = 0; $i < $maxLevel; ++$i) {
                        $final = array();
                        foreach ($badgeCache[$badgeName][$i] as $badgeAward) {
                            $final[] = \SmartBoards\DataRetrieverContinuation::buildForArray($badgeAward);
                        }
                        $badgeCache[$badgeName][$i] = $final;
                    }
                }
                return new Modules\Views\Expression\ValueNode('');
            }

            $students = $course->getUsersWithRole('Student');
            $studentsBadges = array();
            $studentsUsernames = array();
            foreach ($students as $id => $student) { // using $student->get is expensive.. because it is a collection
                $studentsBadges[$id] = $course->getUserData($id)->getWrapped('badges')->getWrapped('list');
                $studentsUsernames[$id] = \SmartBoards\User::getUser($id)->getUsername();
                $studentsNames[$id] = $students->getWrapped($id)->get('name');//\SmartBoards\User::getUser($id)->getUsername();
                $studentsCampus[$id] = $students->getWrapped($id)->get('campus');//\SmartBoards\User::getUser($id)->getUsername();
            }
            $badges = $course->getModuleData('badges')->get('badges');
            $badgeCache = array();
            $badgeCacheClean = array();

            foreach ($badges as $badgeName => $badge) {
                $badgeCache[$badgeName] = array();
                $badgeCacheClean[$badgeName] = array();
                $badgeProgressCount = array();
                $badgeLevel = array();
                for ($i = 0; $i < $badge['maxLevel']; ++$i) {
                    $badgeCache[$badgeName][$i] = array();
                    $badgeCacheClean[$badgeName][$i] = array();
                    foreach ($studentsBadges as $id => $studentBadges) {
                        $badgeWrapped = $studentBadges->getWrapped($badgeName);

                        if (!array_key_exists($id, $badgeLevel)) // cache
                            $badgeLevel[$id] = $badgeWrapped->get('level');
                        if (!array_key_exists($id, $badgeProgressCount)) // cache
                            $badgeProgressCount[$id] = $badgeWrapped->get('progressCount');

                        if ($badgeLevel[$id] > $i) {
                            $badgeCache[$badgeName][$i][] = array(
                                'id' => $id,
                                'name' => $studentsNames[$id],
                                'campus' => $studentsCampus[$id],
                                'username' => $studentsUsernames[$id],
                                'progress' => $badgeProgressCount[$id],
                                'timestamp' => $badgeWrapped->getWrapped('levelTime')->get($i),
                                'when' => date('d-M-Y', $badgeWrapped->getWrapped('levelTime')->get($i))
                            );
                        }
                    }

                    usort($badgeCache[$badgeName][$i], function ($v1, $v2) {
                        return $v1['timestamp'] - $v2['timestamp'];
                    });

                    $badgeCacheClean[$badgeName][$i] = $badgeCache[$badgeName][$i];
                    $final = array();
                    foreach ($badgeCache[$badgeName][$i] as $badgeAward) {
                        $final[] = \SmartBoards\DataRetrieverContinuation::buildForArray($badgeAward);
                    }
                    $badgeCache[$badgeName][$i] = $final;
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
            //$indicator = $indicator->getValue();
            return new Modules\Views\Expression\ValueNode($indicator['indicatorText'] . ((!array_key_exists('quality', $indicator) || $indicator['quality'] == 0)? ' ' : ' (' . $indicator['quality'] . ')'));
        });

        if ($viewsModule->getTemplate(self::BADGES_TEMPLATE_NAME) == NULL)
            $viewsModule->setTemplate(self::BADGES_TEMPLATE_NAME, unserialize(file_get_contents(__DIR__ . '/badges.vt')),$this->getId());
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
