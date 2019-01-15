<?php
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class Charts extends Module {
    private $registeredCharts = array();

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/charts.css');
    }

    public function registerChart($id, $processFunc) {
        if (array_key_exists($id, $this->registeredCharts))
            new \Exception('Chart' . $id . ' already exists');

        $this->registeredCharts[$id] = $processFunc;
    }

    public function init() {
        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();

        $viewHandler->registerPartType('chart', null, null,
            function(&$chart) use (&$viewHandler) {
                if ($chart['chartType'] == 'progress') {
                    $viewHandler->parseSelf($chart['info']['value']);
                    $viewHandler->parseSelf($chart['info']['max']);
                }
            },
            function(&$chart, $viewParams, $visitor) use (&$viewHandler) {
                //print_r($chart);
                //$s = \SmartBoards\Course::$coursesDb->numQueriesExecuted();
                if ($chart['chartType'] == 'progress') {
                    $chart['info']['value'] = $chart['info']['value']->accept($visitor)->getValue();
                    $chart['info']['max'] = $chart['info']['max']->accept($visitor)->getValue();
                } else {
                    $processFunc = $this->registeredCharts[$chart['info']['provider']];
                    $processFunc($chart, $viewParams, $visitor);
                }
                //echo \SmartBoards\Course::$coursesDb->numQueriesExecuted() - $s . 'aaa';
            }
        );
        
        $this->registerChart('starPlot', function(&$chart, $params, $visitor) {
            $userID = $params['user'];
            $course = \SmartBoards\Course::getCourse($params['course']);
            $dataWrapped = $course->getUser($userID)->getData();

            $students = $course->getUsersWithRole('Student');
            $numStudents = $students->size();
            $studentsData = $students->map(function($key, $valueWrapped) use ($course) {
                return (new \SmartBoards\CourseUser($key, $valueWrapped, $course))->getData();
            });

            $starParams = $chart['info']['params'];
            $starUser = array();
            $starAverage = array();

            foreach($starParams as &$param) {
                $val = $dataWrapped->getWrappedComplex($param['id'])->getValue();
                $others = $studentsData->map(function ($key, $valueWrapped) use ($param){
                    return $valueWrapped->getWrappedComplex($param['id']);
                })->getValue();

                $starUser[$param['id']] = $val;
                $average = array_sum($others) / $numStudents;
                $starAverage[$param['id']] =$average;
                $param['max'] = min($param['max'], max(ceil($val/500) * 500, ceil($average/500) * 500));
            }

            $chart['info'] = array(
                'params' => $starParams,
                'user' => $starUser,
                'average' => $starAverage
            );
        });

        $this->registerChart('xpEvolution', function(&$chart, $params, $visitor) {
            $userID = $params['user'];
            $course = \SmartBoards\Course::getCourse($params['course']);

            $cacheId = 'xpEvolution' . $params['course'] . '-' . $course->getUserData($userID)->get('xp');
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);
            if ($hasCache) {
                $spark = (array_key_exists('spark', $chart['info']) ? $chart['info']['spark'] : false);
                $chart['info'] = $cacheValue;
                $chart['info']['spark'] = $spark;
                return;
            }

            $awards = $course->getUser($userID)->getData('awards', true)->sort(function($v1, $v2) {
                return $v1->get('date') - $v2->get('date');
            });

            $currentDay = new DateTime(date('Y-m-d', $awards->getWrapped(0)->get('date')));
            $xpDay = 0;
            $xpTotal = 0;
            $xpValue = array();
            foreach($awards as $award) {
                $awardDay = new DateTime(date('Y-m-d', $award->get('date')));
                $diff = $currentDay->diff($awardDay);
                $diffDays = ($diff->days * ($diff->invert ? -1 : 1));
                if ($diffDays > 0)
                    $currentDay = $awardDay;
                while ($diffDays > 0) {
                    $xpValue[] = array('x' => $xpDay, 'y' => $xpTotal);
                    $xpDay++;
                    $diffDays--;
                }
                $xpTotal += $award->get('reward');
            }
            $xpValue[] = array('x' => $xpDay, 'y' => $xpTotal);

            $chart['info'] = array(
                'values' => $xpValue,
                'domainX' => array(0, ceil($xpDay/15) * 15),
                'domainY' => array(0, ceil($xpTotal/500) * 500),
                'spark' => (array_key_exists('spark', $chart['info']) ? $chart['info']['spark'] : false),
                'labelX' => 'Time (Days)',
                'labelY' => 'XP'
            );
            CacheSystem::store($cacheId, $chart['info']);
        });

        $this->registerChart('leaderboardEvolution', function(&$chart, $params, $visitor) {
            $userID = $params['user'];
            $course = \SmartBoards\Course::getCourse($params['course']);

            $students = $course->getUsersWithRole('Student');

            $maxDay = 0;
            $minDay = PHP_INT_MAX;;
            $baseLine = (new DateTime('2015-01-01'))->getTimestamp();
            $calcDay = function($timestamp) use ($baseLine) {
                return (int)floor(($timestamp - $baseLine) / (3600 * 24));
            };

            $cacheId = 'leaderboardEvolution' .  $calcDay(time()) . '-' . $params['course'] . '-' . $course->getUserData($userID)->get('xp');
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);
            if ($hasCache) {
                $chart['info'] = $cacheValue;
                return;
            }

            $xpValues = array();
            $lastXPValue = array();
            $firstDayStudent = array();
            // calc xp for each student, each day
            foreach ($students as $id => $student) {
                $awards = (new \SmartBoards\CourseUser($id, $student, $course))->getData('awards');
                if (count($awards) == 0) {
                    $firstDayStudent[$id] = PHP_INT_MAX;
                    continue;
                }

                $currentDay = $calcDay($awards[0]['date']);
                $minDay = min($currentDay, $minDay);
                $xpTotal = 0;
                $xpValue = array();
                $firstDay = true;
                foreach ($awards as $award) {
                    $awardDay = $calcDay($award['date']);
                    $diff = $awardDay - $currentDay;
                    if ($diff > 0) {
                        if ($firstDay) {
                            $firstDay = false;
                            $firstDayStudent[$id] = $currentDay;
                        }
                        $xpValue[$currentDay] = $xpTotal;
                        $currentDay = $awardDay;
                    }

                    $xpTotal += $award['reward'];
                    $maxDay = max($maxDay, $awardDay);
                }
                $xpValue[$maxDay] = $xpTotal;
                $xpValues[$id] = $xpValue;

                if (!array_key_exists($id, $firstDayStudent))
                    $firstDayStudent[$id] = PHP_INT_MAX;
            }

            if ($firstDayStudent[$userID] == PHP_INT_MAX) {
                $chart['info'] = array(
                    'values' => array(),
                    'domainX' => array(0, 15),
                    'domainY' => array(1, $students->size()),
                    'startAtOneY' => true,
                    'invertY' => true,
                    'spark' => (array_key_exists('spark', $chart['info']) ? $chart['info']['spark'] : false),
                    'labelX' => 'Time (Days)',
                    'labelY' => 'Position'
                );
                CacheSystem::store($cacheId, $chart['info']);
                return;
            }

            //$students = $students->getValue();
            $studentIds = $students->getKeys();
            $studentNames = array();
            foreach ($studentIds as $id) {
                $firstDay = $firstDayStudent[$id];
                $firstCountedDay = $firstDayStudent[$userID];
                $studentNames[$id] = $students->getWrapped($id)->get('name');

                while($firstDay <= $firstCountedDay) {
                    if (array_key_exists($firstCountedDay, $xpValues[$id])) {
                        $lastXPValue[$id] = $xpValues[$id][$firstCountedDay];
                        break;
                    }
                    $firstCountedDay--;
                }
            }

            $cmpUser = function($v1, $v2) use ($studentNames) {
                $c = $v2['xp'] - $v1['xp'];
                if ($c == 0)
                    $c = strcmp($studentNames[$v1['student']], $studentNames[$v2['student']]);
                return $c;
            };

            $positions = array();
            for ($d = $firstCountedDay, $actualDay = 0; $d <= $maxDay; $d++, $actualDay++) {
                $position = 1;
                // get xp from the student
                if ($firstDayStudent[$userID] > $d)
                    $studentXP = array('xp' => 0, 'student' => $userID);
                else if (array_key_exists($d, $xpValues[$userID])) {
                    $xp = $xpValues[$userID][$d];
                    $lastXPValue[$userID] = $xp;
                    $studentXP = array('xp' => $xp, 'student' => $userID);
                } else {
                    $studentXP = array('xp' => $lastXPValue[$userID], 'student' => $userID);
                }

                foreach ($studentIds as $id) {
                    if ($id == $userID)
                        continue;
                    if ($firstDayStudent[$id] > $d)
                        $xp = 0;
                    else if (array_key_exists($d, $xpValues[$id])) {
                        $xp = $xpValues[$id][$d];
                        $lastXPValue[$id] = $xp;
                    } else {
                        $xp = $lastXPValue[$id];
                    }

                    if ($cmpUser(array('xp' => $xp, 'student'=> $id), $studentXP) < 0)
                        $position++;
                }

                $positions[] = array('x' => $actualDay, 'y' => $position);
            }

            $chart['info'] = array(
                'values' => $positions,
                'domainX' => array(0, count($positions)),
                'domainY' => array(0, $students->size()),
                'startAtOneY' => true,
                'invertY' => true,
                'spark' => (array_key_exists('spark', $chart['info']) ? $chart['info']['spark'] : false),
                'labelX' => 'Time (Days)',
                'labelY' => 'Position'
            );

            CacheSystem::store($cacheId, $chart['info']);
        });

        $this->registerChart('xpWorld', function(&$chart, $params, $visitor) {
            $userID = $params['user'];
            $course = \SmartBoards\Course::getCourse($params['course']);

            $students = $course->getUsersWithRole('Student');


            $highlightValue = PHP_INT_MAX;
            $xpValues = array();
            foreach ($students as $id => $student) {
                $xp = (new \SmartBoards\CourseUser($id, $student, $course))->getData('xp');
                $xp = $xp - ($xp % 500);
                if (array_key_exists($xp, $xpValues))
                    $xpValues[$xp]++;
                else
                    $xpValues[$xp] = 1;

                if ($id == $userID)
                    $highlightValue = $xp;
            }

            $maxCount = 0;
            $maxXP = 0;
            $data = array();
            foreach($xpValues as $xp => $count) {
                $maxCount = max($maxCount, $count);
                $maxXP = max($maxXP, $xp);
                $data[] = array('x' => $xp, 'y' => $count);
            }

            $chart['info'] = array(
                'values' => $data,
                'domainX' => range(0, max(ceil($maxXP/1000) * 1000, 20000), 500),
                'domainY' => array(0, $maxCount),
                'highlightValue' => $highlightValue,
                'shiftBar' => true,
                'labelX' => 'XP',
                'labelY' => '# Players'
            );
        });

        $this->registerChart('badgeWorld', function(&$chart, $params, $visitor) {
            $userID = $params['user'];
            $course = \SmartBoards\Course::getCourse($params['course']);

            $students = $course->getUsersWithRole('Student');

            $highlightValue = PHP_INT_MAX;
            $badgeCounts = array();
            foreach ($students as $id => $student) {
                $badgesCount = (new \SmartBoards\CourseUser($id, $student, $course))->getData('badges', true)->get('completedLevels');

                if (array_key_exists($badgesCount, $badgeCounts))
                    $badgeCounts[$badgesCount]++;
                else
                    $badgeCounts[$badgesCount] = 1;

                if ($id == $userID)
                    $highlightValue = $badgesCount;
            }

            $maxCount = 0;
            $data = array();
            foreach($badgeCounts as $badgesCount => $studentCount) {
                $maxCount = max($maxCount, $studentCount);
                $data[] = array('x' => $badgesCount, 'y' => $studentCount);
            }

            $totalLevels = $course->getModuleData('badges')->get('totalLevels');

            $chart['info'] = array(
                'values' => $data,
                'domainX' => range(0, $totalLevels),
                'domainY' => array(0, $maxCount),
                'highlightValue' => $highlightValue,
                'labelX' => 'Badges',
                'labelY' => '# Players'
            );
        });
    }
}

ModuleLoader::registerModule(array(
    'id' => 'charts',
    'name' => 'Charts',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard'),
        array('id' => 'skills', 'mode' => 'optional'),
        array('id' => 'badges', 'mode' => 'optional'),
        array('id' => 'xp', 'mode' => 'optional')
    ),
    'factory' => function() {
        return new Charts();
    }
));
?>
