<?php
namespace Modules\Charts;

use CacheSystem;
use DateTime;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Core;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Expression\EvaluateVisitor;
use GameCourse\Views\ViewHandler;
use Modules\AwardList\AwardList;
use Modules\Badges\Badges;
use Modules\Skills\Skills;
use Modules\XP\XPLevels;

class Charts extends Module
{
    const ID = 'charts';

    private $registeredCharts = array();


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->initDictionary();
    }

    public function initDictionary()
    {
        /*** ------------ View Types ----------- ***/

        Dictionary::registerViewType(
            'chart',
            'This type displays various types of charts.',
            function(&$view) { //parse function
                if ($view['chartType'] == 'progress') {
                    ViewHandler::parseSelf($view['info']['value']);
                    ViewHandler::parseSelf($view['info']['max']);
                }
            },
            function(&$view, $visitor) { //processing function
                if ($view['chartType'] == 'progress') {
                    ViewHandler::processSelf($view['info']['value'], $visitor);
                    ViewHandler::processSelf($view['info']['max'], $visitor);

                } else if (!empty($view['info']['provider'])) {
                    $processFunc = $this->registeredCharts[$view['info']['provider']];
                    $processFunc($view, $visitor);
                }
            },
            true
        );

        /*** --------- Chart Providers --------- ***/

        $this->registerChart('starPlot', function(&$chart, EvaluateVisitor $visitor) {
            $params = $visitor->getParams();
            $userID = $params['user'];

            $course = Course::getCourse($params['course'], false);
            $userXPData = (new CourseUser($userID, $course))->getXP();

            $students = $course->getUsersWithRole('Student');
            $studentsData = [];
            foreach($students as $s) {
                $studentsData[] = (new CourseUser($s["id"], $course))->getXP();
            }
            $numStudents = sizeof($students);

            $starParams = $chart['info']['params'];
            $starUser = array();
            $starAverage = array();

            foreach($starParams as &$param) {
                $val = $userXPData[$param['id']];

                $others = array_map(function($studentsData) use ($param) {return $studentsData[$param['id']];},$studentsData);

                $starUser[$param['id']] = $val;
                $average = $numStudents == 0 ? 0 : array_sum($others) / $numStudents;
                $starAverage[$param['id']] = $average;
            }

            $chart['info'] = array(
                'params' => $starParams,
                'user' => $starUser,
                'average' => $starAverage
            );
        });

        $this->registerChart('xpEvolution', function(&$chart, EvaluateVisitor $visitor) {
            $params = $visitor->getParams();
            $userID = $params['user'];
            $course = Course::getCourse($params['course'], false);
            $xpModule = $course->getModule("xp");
            $xp = $xpModule->getUserXP($userID,$params['course']);

            $cacheId = 'xpEvolution' . $params['course'] . '-' . $userID .'-'.$xp;
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);
            if ($hasCache) {
                $spark = (array_key_exists('spark', $chart['info']) ? true : false);
                $chart['info'] = $cacheValue;
                $chart['info']['spark'] = $spark;
                return;
            }

            $awards = Core::$systemDB->selectMultiple(AwardList::TABLE,["user"=>$userID,"course"=>$course->getId()],"*","date");

            if (array_key_exists(0, $awards)){
                $currentDay = new DateTime(date('Y-m-d', strtotime($awards[0]['date'])));
            }
            else {
                $currentDay = (new DateTime('2021-03-01'));
            }

            $xpDay = 0;
            $xpTotal = 0;
            $xpValue = array();
            foreach($awards as $award) {
                $awardDay = new DateTime(date('Y-m-d', strtotime($award['date'])));
                $diff = $currentDay->diff($awardDay);
                $diffDays = ($diff->days * ($diff->invert ? -1 : 1));
                if ($diffDays > 0)
                    $currentDay = $awardDay;
                while ($diffDays > 0) {
                    $xpValue[] = array('x' => $xpDay, 'y' => $xpTotal);
                    $xpDay++;
                    $diffDays--;
                }
                $xpTotal += $award['reward'];
            }
            $xpValue[] = array('x' => $xpDay, 'y' => $xpTotal);

            $chart['info'] = array(
                'values' => $xpValue,
                'domainX' => array(0, ceil($xpDay/15) * 15),
                'domainY' => array(0, ceil($xpTotal/500) * 500),
                'spark' => (array_key_exists('spark', $chart['info']) ? true : false),
                'labelX' => 'Time (Days)',
                'labelY' => 'XP'
            );
            CacheSystem::store($cacheId, $chart['info']);
        });

        $this->registerChart('leaderboardEvolution', function(&$chart, EvaluateVisitor $visitor) {
            $params = $visitor->getParams();
            $userID = $params['user'];
            $course = Course::getCourse($params['course'], false);

            $students = $course->getUsersWithRole('Student');

            $maxDay = 0;
            $minDay = PHP_INT_MAX;

            $baseLine = (new DateTime('2015-01-01'));
            $calcDay = function($date) use ($baseLine) {
                if (is_string($date))
                    $date = strtotime($date);
                $date = new DateTime(date('Y-m-d', $date));
                $diff = $baseLine->diff($date);

                $diffDays = ($diff->days * ($diff->invert ? -1 : 1));
                return $diffDays;
            };

            //keeps cache of leaderboard chart of user since the last update
            $updated = $calcDay(Core::$systemDB->select("autogame",["course"=>$params['course']],"finishedRunning"));
            $cacheId = 'leaderboardEvolution' . $params['course'] . '-' . $userID . '-' . $updated;
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);
            if ($hasCache) {
                $chart['info'] = $cacheValue;
                return;
            }

            $xpValues = array();
            $lastXPValue = array();
            $firstDayStudent = array();
            // calc xp for each student, each day
            foreach ($students as $student) {
                $awards = Core::$systemDB->selectMultiple(AwardList::TABLE,['user'=>$student['id'],'course'=>$params['course']],"*","date");

                if (count($awards) == 0) {
                    $firstDayStudent[$student['id']] = PHP_INT_MAX;
                    continue;
                }

                $currentDay = $calcDay($awards[0]['date']);
                $minDay = min($currentDay, $minDay);
                $xpTotal = 0;

                $xpValue = array();
                $firstDay = true;
                foreach ($awards as $award) {
                    if ($award['reward']>0){
                        $awardDay = $calcDay($award['date']);
                        $diff = $awardDay - $currentDay;
                        if ($diff > 0 ) {
                            if ($firstDay) {
                                $firstDay = false;
                                $firstDayStudent[$student['id']] = $currentDay;
                            }
                            $xpValue[$currentDay] = $xpTotal;
                            $currentDay = $awardDay;
                        }
                        $xpTotal += $award['reward'];
                        $maxDay = max($maxDay, $awardDay);
                    }
                }
                $xpValue[$maxDay] = $xpTotal;
                $xpValues[$student['id']] = $xpValue;

                if (!array_key_exists($student['id'], $firstDayStudent))
                    $firstDayStudent[$student['id']] = PHP_INT_MAX;
            }

            if ($firstDayStudent[$userID] == PHP_INT_MAX) {
                $chart['info'] = array(
                    'values' => array(),
                    'domainX' => array(0, 15),
                    'domainY' => array(1, sizeof($students)),
                    'startAtOneY' => true,
                    'invertY' => true,
                    'spark' => (array_key_exists('spark', $chart['info']) ? true : false),
                    'labelX' => 'Time (Days)',
                    'labelY' => 'Position'
                );
                CacheSystem::store($cacheId, $chart['info']);
                return;
            }

            $studentNames = array();
            foreach ($students as $student) {
                $firstDay = $firstDayStudent[$student['id']];
                $firstCountedDay = $firstDayStudent[$userID];

                while($firstDay <= $firstCountedDay) {
                    if (array_key_exists($firstCountedDay, $xpValues[$student['id']])) {
                        $lastXPValue[$student['id']] = $xpValues[$student['id']][$firstCountedDay];
                        break;
                    }
                    $firstCountedDay--;
                }
            }

            $cmpUser = function($v1, $v2) use ($studentNames) {
                $c = $v2['xp'] - $v1['xp'];
                if ($c == 0)//in cases of ties, they are ordered by id
                    $c = $v1['student'] - $v2['student'];
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

                foreach ($students as $student) {
                    if ($student['id'] == $userID)
                        continue;
                    if ($firstDayStudent[$student['id']] > $d)
                        $xp = 0;
                    else if (array_key_exists($d, $xpValues[$student['id']])) {
                        $xp = $xpValues[$student['id']][$d];
                        $lastXPValue[$student['id']] = $xp;
                    } else {
                        $xp = $lastXPValue[$student['id']];
                    }

                    if ($cmpUser(array('xp' => $xp, 'student'=> $student['id']), $studentXP) < 0)
                        $position++;
                }

                $positions[] = array('x' => $actualDay, 'y' => $position);
            }

            $chart['info'] = array(
                'values' => $positions,
                'domainX' => array(0, count($positions)),
                'domainY' => array(0, sizeof($students)),
                'startAtOneY' => true,
                'invertY' => true,
                'spark' => (array_key_exists('spark', $chart['info']) ? true : false),
                'labelX' => 'Time (Days)',
                'labelY' => 'Position'
            );
            CacheSystem::store($cacheId, $chart['info']);
        });

        $this->registerChart('xpWorld', function(&$chart, EvaluateVisitor $visitor) {
            $params = $visitor->getParams();
            $userID = $params['user'];
            $course = Course::getCourse($params['course'], false);

            $students = $course->getUsersWithRole('Student');

            $highlightValue = PHP_INT_MAX;
            $xpValues = array();
            foreach ($students as $student) {
                $xpModule = $course->getModule("xp");
                $xp = $xpModule->getUserXP($student["id"],$params['course']);
                $xp = $xp - ($xp % 500);
                if (array_key_exists($xp, $xpValues))
                    $xpValues[$xp]++;
                else
                    $xpValues[$xp] = 1;

                if ($student['id'] == $userID)
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

            $domainX = range(0, max(ceil($maxXP/1000) * 1000, 20000), 500);
            foreach ($domainX as $xpVal) {
                $values = array_column($data, "x");
                $found = array_search($xpVal, $values);
                if (!is_int($found) && $found == false) $data[] = array('x' => $xpVal, 'y' => 0);
            }
            usort($data, function ($a, $b) {
                if ($a["x"] > $b["x"]) return 1;
                else if ($a["x"] < $b["x"]) return -1;
                return 1;
            });

            $chart['info'] = array(
                'values' => $data,
                'domainX' => $domainX,
                'domainY' => array(0, $maxCount),
                'highlightValue' => $highlightValue,
                'shiftBar' => true,
                'labelX' => 'XP',
                'labelY' => '# Players'
            );
        });

        $this->registerChart('badgeWorld', function(&$chart, EvaluateVisitor $visitor) {
            $params = $visitor->getParams();
            $userID = $params['user'];
            $course = Course::getCourse($params['course'], false);

            $students = $course->getUsersWithRole('Student');
            $badgesModule = $course->getModule("badges");
            $highlightValue = PHP_INT_MAX;
            $badgeCounts = array();
            foreach ($students as $student) {
                $badgesCount=$badgesModule->getBadgeCount($student["id"]);
                //getBadgeCount
                if (array_key_exists($badgesCount, $badgeCounts))
                    $badgeCounts[$badgesCount]++;
                else
                    $badgeCounts[$badgesCount] = 1;

                if ($student['id'] == $userID)
                    $highlightValue = $badgesCount;
            }

            $maxCount = 0;
            $data = array();
            foreach($badgeCounts as $badgesCount => $studentCount) {
                $maxCount = max($maxCount, $studentCount);
                $data[] = array('x' => $badgesCount, 'y' => $studentCount);
            }

            $totalLevels =$badgesModule->getBadgeCount();

            $domainX = range(0, $totalLevels);
            foreach ($domainX as $badgesVal) {
                $values = array_column($data, "x");
                $found = array_search($badgesVal, $values);
                if (!is_int($found) && $found == false) $data[] = array('x' => $badgesVal, 'y' => 0);
            }
            usort($data, function ($a, $b) {
                if ($a["x"] > $b["x"]) return 1;
                else if ($a["x"] < $b["x"]) return -1;
                return 1;
            });

            $chart['info'] = array(
                'values' => $data,
                'domainX' => $domainX,
                'domainY' => array(0, $maxCount),
                'highlightValue' => $highlightValue,
                'labelX' => 'Badges',
                'labelY' => '# Players'
            );
        });
    }

    public function setupResources()
    {
        parent::addResources('css/charts.css');
        parent::addResources('imgs/');
    }

    public function update_module($module)
    {
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function registerChart(string $id, $processFunc) {
        $this->registeredCharts[$id] = $processFunc;
    }
}

ModuleLoader::registerModule(array(
    'id' => Charts::ID,
    'name' => 'Charts',
    'description' => 'Enables charts on views: star plot, xp evolution, xp world, leaderboard evolution and badge world.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => Skills::ID, 'mode' => 'optional'),
        array('id' => Badges::ID, 'mode' => 'optional'),
        array('id' => XPLevels::ID, 'mode' => 'optional')
    ),
    'factory' => function() {
        return new Charts();
    }
));
