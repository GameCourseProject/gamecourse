<?php

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;

Core::init();

echo "<h2>Automated Streaks Test Script</h2>";


/*** ----------------------------------------------- ***/
/*** -------------------- TODO: -------------------- ***/
/***    This script depends on the existence of the  ***/
/***  rule file.                                     ***/
/***    - check if streaks rule file exists. if not, ***/
/***    add it to the project.                       ***/
/*** ----------------------------------------------- ***/
/*** ----------------------------------------------- ***/


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
global $practitioner;
$GLOBALS['practitioner'] = [];
global $constant_gardener;
$GLOBALS['constant_gardener'] = [];
global $superlative_artist;
$GLOBALS['superlative_artist'] = [];

/*** ----------------------------------------------- ***/
/*** -------------------- Setup -------------------- ***/
/*** ----------------------------------------------- ***/

function setUpStreak(string $name, string $description, ?string $color, int $count,
                     ?int $periodicity, ?string $periodicityTime, int $reward, ?int $tokens,
                     bool $isRepeatable, bool $isCount, bool $isPeriodic, bool $isAtMost){
    $courseId = $GLOBALS['courseId'];

    $streakData = [
        "course" => $courseId,
        "name" => $name,
        "course" => $courseId,
        "description" => $description,
        "color" => $color,
        "periodicity" => $periodicity,
        "periodicityTime" => $periodicityTime,
        "count" => $count,
        "reward" => $reward,
        "tokens" => $tokens,
        "isRepeatable" => +$isRepeatable,
        "isCount" => +$isCount,
        "isPeriodic" => +$isPeriodic,
        "isAtMost" => +$isAtMost,
    ];

    Core::$systemDB->insert(self::TABLE, $streakData);

}

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
    echo " setup \n";
    $streakId = Core::$systemDB->select('streak', ["name" => $name, "course" => $courseId], "id");
    if (empty($streakId)){
        setUpStreak($name, "Attend 3 consecutive x.", null, 3,
            null, null, 100, null, false, true, false,
            false);
    }

    // adds participations if needed
    $p = Core::$systemDB->select('participation', [
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
    else{
        echo " participation exists.\n";
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
    $p = Core::$systemDB->select('participation', [
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
                "type" => $type . " (late)",
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
function setUpForConstantGardenerStreak(){

}
function setUpForSuperlativeArtistStreak(string $name){
    // MCP 2021/2022 -> SuperlativeArtist
    $courseId = $GLOBALS['courseId'];
    $userId = $GLOBALS['userId'];
    $teacherId = $GLOBALS['teacherId'];

    // adds streak if needed

    $streakId = Core::$systemDB->select('streak', ["name" => $name, "course" => $courseId], "id");
    if (empty($streakId)){
        setUpStreak($name, "Get three skill posts of at least four points in a row.", null, 3,
            null, null, 100, null, false, true, false,
            false);
    }
    // adds submission participations if needed
    $typeSubmission = 'graded post';
    $descriptionSubmission = "Skill Tree, Re: Skill ";
    $pSubmission = Core::$systemDB->select('participation', [
        'user' => $userId,
        'course' => $courseId,
        "type" => $typeSubmission,
        "evaluator" => $teacherId
    ], 'id');
    if (count($pSubmission) == 0){
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionSubmission . 1,
                "type" => $typeSubmission,
                "post" => "mod/peerforum/discuss.php?d=123#1",
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionSubmission . 2,
                "type" => $typeSubmission,
                "post" => "mod/peerforum/discuss.php?d=124#2",
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionSubmission . 3,
                "type" => $typeSubmission,
                "post" => "mod/peerforum/discuss.php?d=125#3",
                "evaluator" => $teacherId
            ]
        );
    }

    // adds post participations if needed
    $typePost = 'peerforum add post';
    $descriptionPost = "Re: Skill ";
    $pPost = Core::$systemDB->select('participation', [
        'user' => $userId,
        'course' => $courseId,
        "type" => $typePost,
        "evaluator" => $teacherId
    ], 'id');
    if (count($pPost) == 0){
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionPost . 1,
                "type" => $typePost,
                "post" => "mod/peerforum/discuss.php?d=123&parent=1",
                "rating" => 4,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionPost . 2,
                "type" => $typePost,
                "post" => "mod/peerforum/discuss.php?d=124&parent=2",
                "rating" => 4,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionPost . 3,
                "type" => $typePost,
                "post" => "mod/peerforum/discuss.php?d=125&parent=3",
                "rating" => 5,
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
function testAwardStalkerStreak(){

    // create course
    // create streak
    // create user && evaluator in course

    # FOR NOW ASSUME THE COURSE, USER, STREAK AND EVALUATOR EXIST
    $courseId = 1;
    $userId = 8; //User with username ist122229
    $teacherId = 6;

    
    /*
    $type = "attended lecture";

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
    );   */

    //setUpForConsecutiveAttendanceStreak(true);
    echo " Run autogame\n";
    // run autogame
    Course::newExternalData($courseId, True);

    // look for award   echo found or not foundd
    $award = getAward("Stalker");
    if (!empty($award)){
        $GLOBALS['success']++;
        echo " SUCCESS: Stalker Streak successfully awarded for user " . $userId .".\n";
        Core::$systemDB->delete("award", ["user" => $userId, "course" => $courseId, "type" => "streak", "description" => "Stalker (1)"]);

    }else{
        echo " FAILED: Stalker Streak not awarded for user " . $userId .".\n";
    }

}

function testAwardStreaks(){
    setUpForConsecutiveAttendanceStreak("Stalker"); #Stalker
    setUpForConsecutiveAttendanceStreak("Lab Stalker",true); #Lab Stalker
    setUpForConsecutiveMaxGradesStreak("Sage"); #Practitioner
    setUpForConsecutiveMaxGradesStreak("Practitioner",true); #Sage
    setUpForSuperlativeArtistStreak("Superlative Artist"); #Superlative

    // run autogame
    Course::newExternalData($GLOBALS['courseId'], True);

    $streakAward1 = getAward("Stalker");
    $streakAward2 = getAward("Lab Stalker");
    $streakAward3 = getAward("Sage");
    $streakAward4 = getAward("Practitioner");
    $streakAward5 = getAward("Superlative Artist");

    // Write results
    if (!empty($streakAward1)){
        $GLOBALS['success']++;
        $GLOBALS['stalker'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak of consecutive lecture attendances successfully awarded."];
        echo " SUCCESS: Stalker Streak successfully awarded for user " . $GLOBALS['userId'] .".\n";
        deleteAward("Stalker");
    }else{
        $GLOBALS['fail']++;
        echo " FAILED: Stalker Streak not awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['stalker'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Failed to award streak of consecutive lecture attendances."];

    }
    if (!empty($streakAward2)){
        $GLOBALS['success']++;
        $GLOBALS['lab_stalker'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak of consecutive lab attendances successfully awarded."];
        echo " SUCCESS: Lab Stalker Streak successfully awarded for user " . $GLOBALS['userId'] .".\n";
        deleteAward("Lab Stalker");
    }else{
        $GLOBALS['fail']++;
        echo " FAILED: Lab Stalker Streak not awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['lab_stalker'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Failed to award streak of consecutive lab attendances."];

    }
    if (!empty($streakAward3)){
        $GLOBALS['success']++;
        echo " SUCCESS: Sage Streak successfully awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['sage'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak of consecutive maximum quiz grades successfully awarded."];
        deleteAward("Sage");
    }else{
        $GLOBALS['fail']++;
        echo " FAILED: Sage Streak not awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['sage'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Failed to award streak of consecutive maximum quiz grades."];
    }
    if (!empty($streakAward4)){
        $GLOBALS['success']++;
        $GLOBALS['practitioner'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak of consecutive maximum lab grades successfully awarded."];
        echo " SUCCESS: Practitioner Streak successfully awarded for user " . $GLOBALS['userId'] .".\n";
        deleteAward("Practitioner");
    }else{
        $GLOBALS['fail']++;
        echo " FAILED: Practitioner Streak not awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['practitioner'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Failed to award streak of consecutive maximum lab grades."];

    }
    if (!empty($streakAward5)){
        $GLOBALS['success']++;
        echo " SUCCESS: Superlative Artist Streak successfully awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['superlative_artist'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak of doing consecutive skill posts successfully awarded."];
        deleteAward("Superlative Artist");
    }else{
        $GLOBALS['fail']++;
        echo " FAILED: Superlative Artist Streak not awarded for user " . $GLOBALS['userId'] .".\n";
        $GLOBALS['superlative_artist'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Failed to award streak of doing consecutive skill posts."];
    }
}

testAwardStalkerStreak();
//testAwardStreaks();

echo "<table style=' border: 1px solid black; border-collapse: collapse; table-layout:fixed'>";
//Nome das colunas
echo "<tr>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Group</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Test</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Score</strong></th>";
echo "</tr>";


//$info = $GLOBALS["stalker"][0] . $GLOBALS["lab_stalker"][0];
//$countedInfo = countInfos($info, 2);

echo "<tr>";
echo "<td rowspan='2' style='border: 1px solid black; padding: 5px;'>Consecutive Attendance Streaks</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Test award Stalker streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";

//echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/2)</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Test award Lab Stalker streak.</td>";
echo "</tr>";

/*
$info = $GLOBALS["sage"][0] . $GLOBALS["practitioner"][0];
$countedInfo = countInfos($info, 2);
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'>Consecutive Grades Streaks</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Test award Sage.</td>";
echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/2)</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'>Consecutive Grades Streaks</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Test award Practitioner streak.</td>";
echo "</tr>";

$info = $GLOBALS["superlative_artist"][0];
$countedInfo = countInfos($info, 1);
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'>Consecutive Rating Streaks</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Test award SuperlativeArtist.</td>";
echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/1)</td>";
echo "</tr>";

*/

// TOTAL
echo "<tr>";
echo "<td colspan='2' style='border: 1px solid black; padding: 5px;'><strong>Total</strong></td>";
echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'><strong> 100%</br>(" . ($GLOBALS['success'] + $GLOBALS['fail']) . "/5)</strong></td>";
echo "</tr>";
echo "</table>";
                               /*

function countInfos($info, $nrTotal)
{
    $warningCount = substr_count($info, "warning");
    $successCount = substr_count($info, "success");
    $percentageScore =  round(($successCount / $nrTotal) * 100, 2);
    $percentageCover = round((($nrTotal - $warningCount) / $nrTotal) * 100, 2);

    $colorScore = null;
    $colorCover = null;

    if ($percentageScore < 50) {
        $colorScore = "#FFA5A5";
    } else if ($percentageScore == 100) {
        $colorScore = "#C7E897";
    } else {
        $colorScore = "#FFF1AA";
    }
    if ($percentageCover < 50) {
        $colorCover = "#FFA5A5";
    } else if ($percentageScore == 100) {
        $colorCover = "#C7E897";
    } else {
        $colorCover = "#FFF1AA";
    }
    return [$warningCount, $successCount, $percentageScore, $percentageCover, $colorScore, $colorCover];
}               */