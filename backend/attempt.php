<?php

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;

Core::init();

echo "<h2>Automated Streaks Test Script</h2>";

$GLOBALS['courseId'] = 1;
$GLOBALS['userId'] = 8;
$GLOBALS['teacherId'] = 6;

$GLOBALS['success'] = 0;
$GLOBALS['fail'] = 0;

global $stalker;
$GLOBALS['stalker'] = [];
global $lab_stalker;
$GLOBALS['lab_stalker'] = [];
global $sage;
$GLOBALS['sage'] = [];

function testAwardStalkerStreak(){

    $courseId = 1;
    $userId = 8;
    $teacherId = 6;
    
    //Course::newExternalData($courseId, True);
    $award =  Core::$systemDB->select('award', ["user" => $GLOBALS['userId'], "course" => $GLOBALS['courseId'], "type" => "streak", "description" => "Stalker (1)"], "id");

    // look for award   echo found or not foundd
    if (!empty($award)){
        $GLOBALS['success']++;
        echo " SUCCESS: Stalker Streak successfully awarded for user " . $userId .".\n";
        Core::$systemDB->delete("award", ["user" => $userId, "course" => $courseId, "type" => "streak", "description" => "Stalker (1)"]);
    }else{
        echo " FAILED: Stalker Streak not awarded for user " . $userId .".\n";
    }

}
//testAwardStalkerStreak();

/*** ----------------------------------------------- ***/
/*** -------------------- Setup -------------------- ***/
/*** ----------------------------------------------- ***/

function setUpForConsecutiveAttendanceStreak(string $name, bool $lab = false){
    // MCP 2021/2022 -> Lab Stalker & Stalker

    $courseId = $GLOBALS['courseId'];
    $userId = $GLOBALS['userId'];
    $teacherId = $GLOBALS['teacherId'];

    // adds streak if needed
    if ($lab){
        $type = 'attended lab';
    } else {
        $type = 'attended lecture';
    }
    $streakId = Core::$systemDB->select('streak', ["name" => $name, "course" => $courseId], "id");
    if (empty($streakId)){
        setUpStreak($name, "Attend 3 consecutive x.", null, 3,
            null, null, 100, null, false, true, false,
            false);
    }

    // adds participations if needed
    $p = Core::$systemDB->selectMultiple('participation', [
        'user' => $userId,
        'course' => $courseId,
        "type" => $type,
        "evaluator" => $teacherId
    ], 'id');

    // adss participations
    if (count($p) == 0){

        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => 1,
                "type" => $type,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => 2,
                "type" => $type . " (late)",
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => 3,
                "type" => $type,
                "evaluator" => $teacherId
            ]
        );
    }
}
function setUpForConsecutiveMaxGradesStreak(string $name, bool $lab = false){
    // MCP 2021/2022 -> Sage & Practitioner
    $courseId = $GLOBALS['courseId'];
    $userId = $GLOBALS['userId'];
    $teacherId = $GLOBALS['teacherId'];

    // adds streak if needed
    if ($lab){
        $type = 'lab grade';
        $description = "";
    } else {
        $type = 'quiz grade';
        $description = "Quiz ";
    }

    $streakId = Core::$systemDB->select('streak', ["name" => $name, "course" => $courseId], "id");
    if (empty($streakId)){
        setUpStreak($name, "Get 3 consecutive maximum grades in x.", null, 3,
            null, null, 100, null, false, true, false,
            false);
    }

    // adds participations if needed
    $p = Core::$systemDB->selectMultiple('participation', [
        'user' => $userId,
        'course' => $courseId,
        "type" => $type,
        "evaluator" => $teacherId
    ], 'id');

    // adss participations
    if (count($p) == 0){
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $description . 1,
                "type" => $type,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $description . 2,
                "type" => $type,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $description . 3,
                "type" => $type,
                "evaluator" => $teacherId
            ]
        );
    }

}


/*** ----------------------------------------------- ***/
/*** ------------------- Helpers ------------------- ***/
/*** ----------------------------------------------- ***/

function getAward($name){
    return Core::$systemDB->select('award', ["user" => $GLOBALS['userId'], "course" => $GLOBALS['courseId'], "type" => "streak", "description" => $name . " (1)"], "id");
}
function deleteAward($name){
    return Core::$systemDB->delete("award", ["user" => $GLOBALS['userId'], "course" => $GLOBALS['courseId'], "type" => "streak", "description" => $name . " (1)"]);
}

/*** ----------------------------------------------- ***/
/*** -------------------- Award -------------------- ***/
/*** ----------------------------------------------- ***/
function testAwardStreaks(){

    $courseId = 1;
    $userId = 8;

    setUpForConsecutiveAttendanceStreak("Stalker");
    setUpForConsecutiveAttendanceStreak("Lab Stalker", true);

    setUpForConsecutiveMaxGradesStreak("Sage");

    Course::newExternalData($courseId, True);
    $award =  getAward("Stalker");
    $award2 = getAward("Lab Stalker");
    $award3 = getAward("Sage");

    // look for award   echo found or not foundd
    if (!empty($award)){
        $GLOBALS['success']++;
        $GLOBALS['stalker'] = ["success", "<strong style='color:green'>Success:</strong> Stalker Streak successfully awarded."];
        Core::$systemDB->delete("award", ["user" => $userId, "course" => $courseId, "type" => "streak", "description" => "Stalker (1)"]);

    }else{
        $GLOBALS['fail']++;
        $GLOBALS['stalker'] = ["fail", "<strong style='color:red'>Fail:</strong> Stalker Streak not awarded."];
    }
    if (!empty($award2)){
        $GLOBALS['success']++;
        $GLOBALS['lab_stalker'] =  ["success", "<strong style='color:green; '>Success:</strong> Lab Stalker Streak successfully awarded."];
        echo " SUCCESS: Lab Stalker Streak successfully awarded for user " . $GLOBALS['userId'] .".\n";
        deleteAward("Lab Stalker");
    }else{
        $GLOBALS['fail']++;
        echo " FAILED: Lab Stalker Streak not awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['lab_stalker'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Lab Stalker streak not awarded."];
    }
    if (!empty($award3)){
        $GLOBALS['success']++;
        echo " SUCCESS: Sage Streak successfully awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['sage'] =  ["success", "<strong style='color:green; '>Success:</strong> Sage streak successfully awarded."];
        deleteAward("Sage");
    }else{
        $GLOBALS['fail']++;
        echo " FAILED: Sage Streak not awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['sage'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Sage streak not awarded."];
    }



}
testAwardStreaks();

//testAwardStreaks();

echo "<table style=' border: 1px solid black; border-collapse: collapse; table-layout:fixed'>";
//Nome das colunas
echo "<tr>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Streaks</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Group</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Test</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Score</strong></th>";
echo "</tr>";
echo "<tr>";
echo "<td rowspan='6' style='border: 1px solid black; padding: 5px;'>MCP 2021/2022 Streaks</td>";
echo "<td rowspan='2'style='border: 1px solid black; padding: 5px;'> Consecutive attendance streaks.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award Stalker streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award Lab Stalker streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td rowspan='2'style='border: 1px solid black; padding: 5px;'> Consecutive grades streaks.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award Sage streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award Practitioner streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Consecutive rating streaks.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award Superlative Artist streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Periodic streaks.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award Constant Gardener streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='2' rowspan='4' style='border: 1px solid black; padding: 5px;'>Periodic Streaks</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Do 2 skills within 2 hours' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Do 2 skills within 2 days' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Do 2 skills within 1 week' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Do 2 skills with no more than 2 days between them' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";



echo "<tr>";
echo "<td colspan='3' style='border: 1px solid black; padding: 5px;'><strong>Total</strong></td>";
echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'><strong> 100%</br>(" . ($GLOBALS['success'] + $GLOBALS['fail']) . "/5)</strong></td>";
echo "</tr>";

echo "</table>";

