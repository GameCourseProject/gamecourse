<?php

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;

Core::init();

$count = 0;

function testAwardStalkerStreak(){

    // create course
    // create streak
    // create user && evaluator in course

    # FOR NOW ASSUME THE COURSE, USER, STREAK AND EVALUATOR EXIST
    $courseId = 1;
    $userId = 7; //User with username ist122229
    $teacherId = 6;
    // check if streak
    $streakId = Core::$systemDB->select('streak', ["name" => "Stalker", "course" => $courseId], "id");

    // add participations for user
    /*
    Core::$systemDB->insert('participation',
        [
            'user' => $userId,
            'course' => $courseId,
            "description" => 1,
            "type" => 'attended lecture',
            "rating" => 3,
            "evaluator" => $teacherId
        ]
    );
    // add participations necessary for streak
    Core::$systemDB->insert('participation',
        [
            'user' => $userId,
            'course' => $courseId,
            "description" => 1,
            "type" => 'attended lecture',
            "evaluator" => $teacherId
        ]
    );
    Core::$systemDB->insert('participation',
        [
            'user' => $userId,
            'course' => $courseId,
            "description" => 1,
            "type" => 'attended lecture (late)',
            "evaluator" => $teacherId
        ]
    );
    Core::$systemDB->insert('participation',
        [
            'user' => $userId,
            'course' => $courseId,
            "description" => 1,
            "type" => 'attended lecture (late)',
            "evaluator" => $teacherId
        ]
    );
    */
    // run autogame
    Course::newExternalData($courseId, True);

    // look for award   echo found or not foundd
    $award = Core::$systemDB->select('award', ["user" => $userId, "course" => $courseId, "type" => "streak", "description" => "Stalker (1)"], "id");


    if (!empty($award)){
        
    }else{
        echo " FAILED: Stalker Streak not awarded for user" . $userId .".\n";
    }
    // AFTER:
    // eliminate award


}
testAwardStalkerStreak();

echo "<table style=' border: 1px solid black; border-collapse: collapse; table-layout:fixed'>";
//Nome das colunas
echo "<tr>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Group</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Test</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Score</strong></th>";
echo "</tr>";
// Login Picture
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'>Login Picture</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> Test award stalker streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> $count /1</td>";

echo "</tr>";
echo "</table>";
