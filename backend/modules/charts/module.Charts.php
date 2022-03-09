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
            $courseId = $params['course'];

            // Get from cache if exists
            $cacheId = 'xpEvolution-' . $params['course'] . '-' . $userID;
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);
            if ($hasCache) {
                $spark = (array_key_exists('spark', $chart['info']) ? true : false);
                $chart['info'] = $cacheValue;
                $chart['info']['spark'] = $spark;
                return;
            }

            date_default_timezone_set('Europe/Lisbon');
            $baseLine = (new DateTime('2022-03-07')); // FIXME: course start date should be configurable
            $daysPassed = $baseLine->diff(new DateTime())->days;

            // Calculate XP per day
            $awards = Core::$systemDB->selectMultiple(AwardList::TABLE, ["user" => $userID, "course" => $courseId], "*" , "date");
            $awards = array_filter($awards, function ($award) { return $award["type"] != "tokens"; });

            $xpTotal = 0;
            $xpByDay = [];

            $day = 0;
            while ($day <= $daysPassed) {
                $awardsByDay = array_filter($awards, function ($award) use ($baseLine, $day) {
                    return $baseLine->diff(new DateTime($award["date"]))->days == $day;
                });

                if (!empty($awardsByDay)) {
                    foreach ($awardsByDay as $award) {
                        $xpTotal += $award["reward"];
                    }
                }

                $xpByDay[] = array('x' => $day, 'y' => $xpTotal);
                $day++;
            }

            $chart['info'] = array(
                'values' => $xpByDay,
                'domainX' => array(0, $daysPassed),
                'domainY' => array(0, $xpTotal),
                'spark' => (array_key_exists('spark', $chart['info']) ? true : false),
                'labelX' => 'Time (Days)',
                'labelY' => 'XP'
            );
            CacheSystem::store($cacheId, $chart['info']);
        });

        $this->registerChart('leaderboardEvolution', function(&$chart, EvaluateVisitor $visitor) {
            $params = $visitor->getParams();
            $userID = $params['user'];
            $courseId = $params['course'];
            $course = Course::getCourse($courseId, false);

            $students = $course->getUsersWithRole('Student');

            date_default_timezone_set('Europe/Lisbon');
            $baseLine = (new DateTime('2022-03-07')); // FIXME: course start date should be configurable
            $daysPassed = $baseLine->diff(new DateTime())->days;

            // Get from cache if exists
            $cacheId = 'leaderboardEvolution-' . $courseId . '-' . $userID;
            list($hasCache, $cacheValue) = CacheSystem::get($cacheId);
            if ($hasCache) {
                $chart['info'] = $cacheValue;
                return;
            }

            // Calculate XP for each student per day
            $studentXPPerDay = [];
            foreach ($students as $student) {
                $studentID = $student['id'];
                list($hasCache, $cacheValue) = CacheSystem::get('xpEvolution-' . $courseId . '-' . $studentID);
                if ($hasCache) {
                    $studentXPPerDay[$studentID] = $cacheValue["values"];

                } else {
                    $awards = Core::$systemDB->selectMultiple(AwardList::TABLE, ["user" => $studentID, "course" => $courseId], "*" , "date");
                    $awards = array_filter($awards, function ($award) { return $award["type"] != "tokens"; });

                    $xpTotal = 0;
                    $xpByDay = [];

                    $day = 0;
                    while ($day <= $daysPassed) {
                        $awardsByDay = array_filter($awards, function ($award) use ($baseLine, $day) {
                            return $baseLine->diff(new DateTime($award["date"]))->days == $day;
                        });

                        if (!empty($awardsByDay)) {
                            foreach ($awardsByDay as $award) {
                                $xpTotal += $award["reward"];
                            }
                        }

                        $xpByDay[] = array('x' => $day, 'y' => $xpTotal);
                        $day++;
                    }
                    $studentXPPerDay[$studentID] = $xpByDay;
                }
            }

            // Calculate student position
            $positionPerDay = [];
            $day = 0;
            while ($day <= $daysPassed) {
                usort($students, function ($a, $b) use ($studentXPPerDay, $day) {
                    $xpA = $studentXPPerDay[$a['id']][$day]["y"];
                    $xpB = $studentXPPerDay[$b['id']][$day]["y"];
                    return $xpB - $xpA;
                });

                $position = 1;
                for ($i = 0; $i < count($students); $i++) {
                    if ($students[$i]['id'] == $userID) $position = $i + 1;
                }

                $positionPerDay[$day] = array('x' => $day, 'y' => $position);
                $day++;
            }

            $chart['info'] = array(
                'values' => $positionPerDay,
                'domainX' => array(0, $daysPassed),
                'domainY' => array(1, count($students)),
                'startAtOneY' => true,
                'invertY' => true,
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
                'domainY' => array(0, count($students)),
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
