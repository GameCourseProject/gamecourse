<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

function printTrace() {
    echo '<pre>';
    print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
    echo '</pre>';
}

define('XP_PER_LEVEL', 1000);
define('MAX_BONUS_BADGES', 1000);
define('MAX_TREE_XP', 5000);

include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;
use \SmartBoards\User;
use \SmartBoards\Course;

$isCLI = Core::isCLI();
Core::init();

if(!Core::requireSetup(false))
    die('Please perform setup first!');

if ($isCLI) {
    $courseId = (array_key_exists(1, $argv) ? $argv[1] : 0);
} else {
    $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 0);
}

$course = Course::getCourse($courseId);
$users = $course->getUsers();

echo '<pre>';

// Read Teachers
$keys = array('id', 'name', 'email');
$teachers = file_get_contents(LEGACY_DATA_FOLDER . '/teachers.txt');
$teachers = preg_split('/[\r]?\n/', $teachers, -1, PREG_SPLIT_NO_EMPTY);
foreach($teachers as &$teacher) {
    $teacher = array_combine($keys, preg_split('/;/', $teacher));
    $user = User::getUser($teacher['id']);
    if (!$user->exists()) {
        $user->initialize($teacher['name'], $teacher['email']);
        $user->setAdmin(true);
    } else {
        $user->setName($teacher['name']);
        $user->setEmail($teacher['email']);
        $user->setAdmin(true);
    }

    if ($users->hasKey($teacher['id'])) {
        $userWrapped = $users->getWrapped($teacher['id']);
        $roles = $users->getWrapped($teacher['id'])->get('roles', array());
        if (!in_array('Teacher', $roles)) {
            $roles[] = 'Teacher';
            $users->getWrapped($teacher['id'])->set('roles', $roles);
        }
        $userWrapped->set('name', $teacher['name']);
        $userWrapped->set('email', $teacher['email']);
    } else {
        $teacher['roles'] = array('Teacher');
        $users->set($teacher['id'], $teacher);
        echo 'New teacher ' . $teacher['id'] . "\n";
    }
}

// Read Students
$keys = array('id', 'name', 'email', 'campus');
$students = utf8_encode(file_get_contents(LEGACY_DATA_FOLDER . '/students.txt'));
$students = preg_split('/[\r]?\n/', $students, -1, PREG_SPLIT_NO_EMPTY);
foreach($students as &$student) {
    $student = array_combine($keys, preg_split('/;/', $student));
    $user = User::getUser($student['id']);
    if (!$user->exists()) {
        $user->initialize($student['name'], $student['email']);
    } else {
        $user->setName($student['name']);
        $user->setEmail($student['email']);
    }

    if ($users->hasKey($student['id'])) {
        $userWrapped = $users->getWrapped($student['id']);
        $roles = $users->getWrapped($student['id'])->get('roles', array());
        if (!in_array('Student', $roles)) {
            $roles[] = 'Student';
            $users->getWrapped($student['id'])->set('roles', $roles);
        }
        $userWrapped->set('name', $student['name']);
        $userWrapped->set('email', $student['email']);
    } else {
        $student['roles'] = array('Student');
        $users->set($student['id'], $student);
        echo 'New student ' . $student['id'] . "\n";
    }
}

if (file_exists(LEGACY_DATA_FOLDER . '/gave_up.txt')) {
    $keys = array('id', 'name', 'email', 'campus');
    $studentsGaveUp = utf8_encode(file_get_contents(LEGACY_DATA_FOLDER . '/gave_up.txt'));
    $studentsGaveUp = preg_split('/[\r]?\n/', $studentsGaveUp, -1, PREG_SPLIT_NO_EMPTY);
    foreach($studentsGaveUp as &$student) {
        $student = array_combine($keys, preg_split('/;/', $student));
        if ($users->hasKey($student['id'])) {
            echo 'Student ' . $student['id'] . " gave up\n";
            $users->delete($student['id']);
        }
    }
}

//$course->getUsers()->setValue($users);

// Read Indicators
$indicators = json_decode(file_get_contents(LEGACY_DATA_FOLDER . '/indicators.json'), true);
$indicatorsByNum = array();
foreach ($indicators as &$indicatorsUser) {
    $indicatorsByNum[$indicatorsUser['num']] = $indicatorsUser['indicators'];
}

// Read Tree
$keys = array(
    'tier', 'name', 'dependencies', 'color', 'xp'
);
$skillTree = file_get_contents(LEGACY_DATA_FOLDER . '/tree.txt');
$skillTree = preg_split('/[\r]?\n/', $skillTree, -1, PREG_SPLIT_NO_EMPTY);
$sbSkillTree = array(
    't0' => array(
        'reward' => 150,
        'skills' => array()
    ),
    't1' => array(
        'reward' => 400,
        'skills' => array()
    ),
    't2' => array(
        'reward' => 750,
        'skills' => array()
    ),
    't3' => array(
        'reward' => 1150,
        'skills' => array()
    )
);

foreach($skillTree as &$skill) {
    $skill = array_combine($keys, preg_split('/;/', $skill));
    if (strpos($skill['dependencies'], '|') !== FALSE) {
        $skill['dependencies'] = preg_split('/[|]/', $skill['dependencies']);
        foreach($skill['dependencies'] as &$dependency) {
            $dependency = preg_split('/[+]/', $dependency);
        }
    } else {
        if (strpos($skill['dependencies'], '+') !== FALSE)
            $skill['dependencies'] = array(preg_split('/[+]/', $skill['dependencies']));
        else
            $skill['dependencies'] = array();
    }

    $tier = 't' . ($skill['tier']-1);
    unset($skill['tier']);
    unset($skill['xp']);
    $descriptionPage = file_get_contents(LEGACY_DATA_FOLDER . '/tree/' . str_replace(' ', '', $skill['name']) . '.html');

    $start = strpos($descriptionPage, '<td>') + 4;
    $end = stripos($descriptionPage, '</td>');
    $descriptionPage = substr($descriptionPage, $start, $end - $start);

    $skill['page'] = htmlspecialchars(utf8_encode($descriptionPage));
    $sbSkillTree[$tier]['skills'][] = $skill;
}

$course->getModuleData('skills')->set('skills', $sbSkillTree);

// Read Levels
$keys = array(
    'title', 'minxp'
);
$levels = file_get_contents(LEGACY_DATA_FOLDER . '/levels.txt');
$levels = preg_split('/[\r]?\n/', $levels, -1, PREG_SPLIT_NO_EMPTY);

foreach($levels as &$level) {
    $level = array_combine($keys, preg_split('/;/', $level));
    $level['minxp'] = (int) $level['minxp'];
}

$course->getModuleData('xp')->set('levels', $levels);

// Read Achievements/Badges
$keys = array(
    'name', 'description', 'desc1', 'desc2', 'desc3', 'xp1', 'xp2', 'xp3', 'countBased', 'postBased', 'pointBased',
    'count1', 'count2', 'count3'
);
$achievements = file_get_contents(LEGACY_DATA_FOLDER . '/achievements.txt');
$achievements = preg_split('/[\r]?\n/', $achievements, -1, PREG_SPLIT_NO_EMPTY);
$sbBadges = array();
$totalLevels = 0;
foreach($achievements as &$achievement) {
    $achievement = array_combine($keys, preg_split('/;/', $achievement));

    $xp = array();
    $xp[] = abs($achievement['xp1']);
    if ($achievement['xp2'] != '') {
        $xp[] = abs($achievement['xp2']);
        if ($achievement['xp3'] != '')
            $xp[] = abs($achievement['xp3']);
    }

    $levelDesc = array();
    $levelDesc[] = $achievement['desc1'];
    if (!empty($achievement['desc2'])) {
        $levelDesc[] = $achievement['desc2'];
        if (!empty($achievement['desc3']))
            $levelDesc[] = $achievement['desc3'];
    }

    $count = array();
    if (!empty($achievement['count1'])) {
        $count[] = $achievement['count1'];
        if (!empty($achievement['count2'])) {
            $count[] = $achievement['count2'];
            if (!empty($achievement['count3']))
                $count[] = $achievement['count3'];
        }
    }

    $sbBadges[$achievement['name']] = array(
        'name' => $achievement['name'],
        'description' => $achievement['description'],
        'maxLevel' => empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3),
        'xp' => $xp,
        'levelDesc' => $levelDesc,
        'extraCredit' => ($achievement['xp1'] < 0),
        'braggingRights' => ($achievement['xp1'] == 0),
        'countBased' => ($achievement['countBased'] == 'True'),
        'postBased' => ($achievement['postBased'] == 'True'),
        'pointBased' => ($achievement['pointBased'] == 'True'),
        'count' => $count
    );
    $totalLevels += $sbBadges[$achievement['name']]['maxLevel'];
}

$course->getModuleData('badges')->set('badges', $sbBadges);
$course->getModuleData('badges')->set('totalLevels', $totalLevels);

$badgesNames = array_keys($sbBadges);

// Read Awards
$keys = array('time', 'userid', 'what', 'field1', 'field2');
$awards = file_get_contents(LEGACY_DATA_FOLDER . '/awards.txt');
$awards = preg_split('/[\r]?\n/', $awards, -1, PREG_SPLIT_NO_EMPTY);

$awardsPerUser = array();
foreach($users as $userid => $user) {
    $awardsPerUser[$userid] = array();
}

foreach($awards as &$award) {
    $award = array_combine($keys, preg_split('/;/', $award, 5));
    if (array_key_exists($award['userid'], $awardsPerUser)) {
        $sbAward = null;
        if ($award['what'] == 'Initial Bonus')
            $sbAward = array('type' => 'bonus', 'reward' => (int) $award['field1'], 'date' => (double) $award['time'], 'name' => $award['what']);
        else if ($award['what'] == 'Grade from Lab')
            $sbAward = array('type' => 'grade', 'reward' => (int) $award['field1'], 'date' => (double) $award['time'], 'name' => 'Lab ' . $award['field2'], 'subtype' => 'lab', 'num' => $award['field2']);
        else if ($award['what'] == 'Grade from Quiz')
            $sbAward = array('type' => 'grade', 'reward' => (int) $award['field1'], 'date' => (double) $award['time'], 'name' => 'Quiz ' . $award['field2'], 'subtype' => 'quiz', 'num' => $award['field2']);
        else if ($award['what'] == 'Grade from Presentation')
            $sbAward = array('type' => 'grade', 'reward' => (int) $award['field1'], 'date' => (double) $award['time'], 'name' => 'Presentation', 'subtype' => 'presentation');
        else if ($award['what'] == 'Skill Tree')
            $sbAward = array('type' => 'skill', 'reward' => (int) $award['field1'], 'date' => (double) $award['time'], 'name' => $award['field2']);
        else if (in_array($award['what'], $badgesNames))
            $sbAward = array('type' => 'badge', 'reward' => $sbBadges[$award['what']]['xp'][$award['field1']-1], 'date' => (double) $award['time'], 'name' => $award['what'], 'level' => (int) $award['field1']);


        if ($sbAward != null) {
            $awardsPerUser[$award['userid']][] = $sbAward;
        } else {
            echo '<pre>';
            print_r($award);
            echo '</pre>';
        }
    }
}

// Process users
foreach($users as $userid => $user) {
    $roles = $users->getWrapped($userid)->get('roles');
    if (is_null($roles) || !in_array('Student', $roles))
        continue;
    $userDataWrapped = (new \SmartBoards\CourseUser($userid, $users->getWrapped($userid), $course))->getData();
    $userData = (new ValueWrapper($userDataWrapped->getValue()));
    $userData->set('awards', $awardsPerUser[$userid]);
    $userBadges = $userData->getWrapped('badges');
	$userBadges->delete('list');
    $userBadgesList = $userBadges->getWrapped('list');
    $userSkills = $userData->getWrapped('skills');
    $userSkillsList = $userSkills->getWrapped('list');
    $userSkills->setValue(array());

    $userQuizes = $userData->getWrapped('quizes');
    $quizesList = $userQuizes->getWrapped('list');

    $userLabs = $userData->getWrapped('labs');
    $labsList = $userLabs->getWrapped('list');

    $userPresentation = $userData->getWrapped('presentation');

    $badgeUnlockedLevel = array();
    $badgeUnlockedLevelTime = array();
    foreach ($sbBadges as $badgeName => $badge) {
        $badgeUnlockedLevelTime[$badgeName] = array();
    }

    $userBadges->set('normalxp', 0);
    $userBadges->set('bonusxp', 0);
    $userSkills->set('totalxp', 0);
    $userQuizes->set('totalxp', 0);
    $userLabs->set('totalxp', 0);
    $userPresentation->set('xp', 0);
    $userData->set('bonusxp', 0);

    $skillUnlocked = array();
    $countSkills = 0;
    foreach($userData->get('awards', array()) as $award) {
        if ($award['type'] == 'badge') {
            $badgeUnlockedLevelTime[$award['name']][$award['level']-1] = $award['date'];
            if (!array_key_exists($award['name'], $badgeUnlockedLevel))
                $badgeUnlockedLevel[$award['name']] = $award['level'];
            else if ($badgeUnlockedLevel[$award['name']] < $award['level'])
                $badgeUnlockedLevel[$award['name']] = $award['level'];
            else {
                echo 'Should never be here!!! Badge awarded out of order for ', $userid, ', badge ', $award['name'], '<br>';
            }
            if ($sbBadges[$award['name']]['extraCredit'])
                $userBadges->set('bonusxp', $userBadges->get('bonusxp') + $award['reward']);
            else
                $userBadges->set('normalxp', $userBadges->get('normalxp') + $award['reward']);
        } else if ($award['type'] == 'skill') {
            $skillUnlocked[] = $award['name'];
            $userSkills->set('totalxp', $userSkills->get('totalxp') + $award['reward']);
            $countSkills++;
        } else if ($award['type'] == 'grade') {
            if ($award['subtype'] == 'quiz') {
                $quizesList->set($award['num'], $award['reward']);
                $userQuizes->set('totalxp', $userQuizes->get('totalxp') + $award['reward']);
            } else if ($award['subtype'] == 'lab') {
                $labsList->set($award['num'], $award['reward']);
                $userLabs->set('totalxp', $userLabs->get('totalxp') + $award['reward']);
            } else {
                $userPresentation->set('xp', $award['reward']);
            }
        } else if ($award['type'] == 'bonus') {
            $userData->set('bonusxp', $userData->get('bonusxp') + $award['reward']);
        }
    }
    $userSkills->set('count', $countSkills);

    $userBadges->set('totalxp', $userBadges->get('normalxp') + $userBadges->get('bonusxp'));
    $userBadges->set('countedxp', $userBadges->get('normalxp') + min($userBadges->get('bonusxp'), MAX_BONUS_BADGES));
    $userSkills->set('countedxp', min($userSkills->get('totalxp'), MAX_TREE_XP));
    $userData->set('xp', $userBadges->get('countedxp') + $userSkills->get('countedxp') + $userQuizes->get('totalxp') + $userLabs->get('totalxp') + $userPresentation->get('xp') + $userData->get('bonusxp'));
    $userData->set('level', floor($userData->get('xp') / XP_PER_LEVEL));

    if (!array_key_exists($userid, $indicatorsByNum)) {
        foreach ($sbBadges as $badgeName => $badge) {
            $badgeInfo = array(
                'level' => 0,
                'levelTime' => array(),
                'progressCount' => -1,
                'progress' => array()
            );
            $userBadgesList->set($badgeName, $badgeInfo);
        }
        $userSkillsList->setValue(array());
        continue;
    }

    $indicatorsForUser = $indicatorsByNum[$userid];
    $completedLevels = 0;
    foreach ($sbBadges as $badgeName => $badge) {
        $badgeIndicators = $indicatorsForUser[$badgeName];

        $progress = array();
        if ($badge['postBased']) {
            if ($badge['countBased'] || (is_array($badgeIndicators[1]) && count($badgeIndicators[1]) != 0 && $badgeIndicators[1][0]['action'] == 'graded post')) {
                $postCount = 1;
                foreach($badgeIndicators[1] as $indicator) {
                    $progress[] = array(
                        'quality' => (int) $indicator['xp'],
                        'post' => $indicator['info'],
                        'link' => $indicator['url'],
                        'text' => 'P' . $postCount++
                    );
                }
            } else {
                $postCount = 1;
                foreach($badgeIndicators[1] as $indicator) {
                    $progress[] = array(
                        'link' => $indicator['url'],
                        'text' => 'P' . $postCount++
                    );
                }
            }
        } else if ($badge['countBased']) {
            if (is_array($badgeIndicators[1])) {
                foreach($badgeIndicators[1] as $indicator) {
                    $progress[] = array('text' => $indicator['info']);
                }
            }
        }

        $badgeInfo = array(
            'level' => array_key_exists($badgeName, $badgeUnlockedLevel) ? (int) $badgeUnlockedLevel[$badgeName] : 0,
            'levelTime' => $badgeUnlockedLevelTime[$badgeName],
            'progressCount' => (int)($badgeIndicators[0] == 'False' ? -1 : $badgeIndicators[0]),
            'progress' => $progress
        );
        $userBadgesList->set($badgeName, $badgeInfo);
        $completedLevels += $badgeInfo['level'];
    }
    $userBadges->set('completedLevels', $completedLevels);

    foreach ($skillUnlocked as $skill) {
        if (!array_key_exists($skill, $indicatorsForUser)) {
            echo "Did not receive indicator for skill $skill, for user $userid\n";
            continue;
        }
        $skillIndicator = $indicatorsForUser[$skill];
        $userSkillsList->set($skill, array('post' => $skillIndicator[1][0]['url'], 'quality' => (int) $skillIndicator[1][0]['xp'], 'time' => (double) $skillIndicator[1][0]['timestamp']));
    }

    $userDataWrapped->setValue($userData->getValue());
}
$course->getWrapped('lastUpdate')->setValue(time());

echo "Finished!\n";
echo '</pre>';
?>
