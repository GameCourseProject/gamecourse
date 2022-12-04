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
$GLOBALS['userId2'] = 7;
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

global $periodic1;
$GLOBALS['periodic1'] = [];
global $periodic2;
$GLOBALS['periodic2'] = [];
global $periodic3;
$GLOBALS['periodic3'] = [];
global $periodic4;
$GLOBALS['periodic4'] = [];
global $periodic5;
$GLOBALS['periodic5'] = [];


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
        $rating1 = 125;
        $rating2 = 150;
    } else {
        $type = 'quiz grade';
        $description = "Quiz ";
        $rating1 = 1000;
        $rating2 = 1000;
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
                "rating" => $rating1,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $description . 2,
                "type" => $type,
                "rating" => $rating1,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $description . 3,
                "type" => $type,
                "rating" => $rating2,
                "evaluator" => $teacherId
            ]
        );
    }

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
    $pSubmission = Core::$systemDB->selectMultiple('participation', [
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
                "rating" => 4,
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
                "rating" => 4,
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
                "rating" => 5,
                "evaluator" => $teacherId
            ]
        );
    }

    // adds post participations if needed
    $typePost = 'peerforum add post';
    $descriptionPost = "Re: Skill ";
    $pPost = Core::$systemDB->selectMultiple('participation', [
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
                "evaluator" => $teacherId
            ]
        );
    }

}

/*
function setPeriodicWeeklyStreak($name){
    // weekly : do a skill every week
    $courseId = $GLOBALS['courseId'];
    $userId = $GLOBALS['userId'];
    $teacherId = $GLOBALS['teacherId'];

    // adds streak if needed

    $streakId = Core::$systemDB->select('streak', ["name" => $name, "course" => $courseId], "id");
    if (empty($streakId)){
        setUpStreak($name, "Do a skill every week.", null, 3,
            1, "weeks_", 100, 2, false, false, true,
            false);
    }
    // adds submission participations if needed
    $typeSubmission = 'graded post';
    $descriptionSubmission = "Skill Tree, Re: Skill ";

    $pSubmission = Core::$systemDB->selectMultiple('participation', [
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
                "date" => date('Y-m-d H:i:s'),
                "type" => $typeSubmission,
                "post" => "mod/peerforum/discuss.php?d=200#1",
                "rating" => 4,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionSubmission . 1,
                "date" => date('Y-m-d H:i:s'),
                "type" => $typeSubmission,
                "post" => "mod/peerforum/discuss.php?d=201#1",
                "rating" => 4,
                "evaluator" => $teacherId
            ]
        );

    }

    // adds post participations if needed
    $typePost = 'peerforum add post';
    $descriptionPost = "Re: Skill ";
    $pPost = Core::$systemDB->selectMultiple('participation', [
        'user' => $userId,
        'course' => $courseId,
        "type" => $typePost,
        "evaluator" => $teacherId
    ], 'id');
    if (count($pPost) == 0){
        $date1 = "2022-08-20 17:16:50";
        $postDate1 = date('Y-m-d H:i:s', strtotime($date1));

        $date2 = "2022-08-28 17:16:50";
        $postDate2 = date('Y-m-d H:i:s', strtotime($date2));
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionPost . 1,
                "date" => $postDate1,
                "type" => $typePost,
                "post" => "mod/peerforum/discuss.php?d=200&parent=1",
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionPost . 1,
                "date" => $postDate2,
                "type" => $typePost,
                "post" => "mod/peerforum/discuss.php?d=201&parent=1",
                "evaluator" => $teacherId
            ]
        );
    }


}

function setPeriodicHourlyStreak($name){
    // clockwise : do a skill every 2 hours
    $courseId = $GLOBALS['courseId'];
    $userId = $GLOBALS['userId'];
    $teacherId = $GLOBALS['teacherId'];
    // needs insert peerforum add post + graded post

    $streakId = Core::$systemDB->select('streak', ["name" => $name, "course" => $courseId], "id");
    if (empty($streakId)){
        setUpStreak($name, "Do a skill every week.", null, 3,
            2, "hours", 100, 2, false, false, true,
            false);
    }
    // adds submission participations if needed
    $typeSubmission = 'graded post';
    $descriptionSubmission = "Skill Tree, Re: Skill ";

    $pSubmission = Core::$systemDB->selectMultiple('participation', [
        'user' => $userId,
        'course' => $courseId,
        "description" => $descriptionSubmission . 1,
        "type" => $typeSubmission,
        "rating" => 4,
        "evaluator" => $teacherId
    ], 'id');
    if (count($pSubmission) != 0){
        foreach ($pSubmission as $p){
            Core::$systemDB->delete("participation", ["id" => $p]);
        }

    }
    Core::$systemDB->insert('participation',
        [
            'user' => $userId,
            'course' => $courseId,
            "description" => $descriptionSubmission . 1,
            "date" => date('Y-m-d H:i:s'),
            "type" => $typeSubmission,
            "post" => "mod/peerforum/discuss.php?d=200#1",
            "rating" => 4,
            "evaluator" => $teacherId
        ]
    );
    Core::$systemDB->insert('participation',
        [
            'user' => $userId,
            'course' => $courseId,
            "description" => $descriptionSubmission . 1,
            "date" => date('Y-m-d H:i:s'),
            "type" => $typeSubmission,
            "post" => "mod/peerforum/discuss.php?d=201#1",
            "rating" => 4,
            "evaluator" => $teacherId
        ]
    );

    // adds post participations if needed
    $typePost = 'peerforum add post';
    $descriptionPost = "Re: Skill ";
    $pPost = Core::$systemDB->selectMultiple('participation', [
        'user' => $userId,
        'course' => $courseId,
        "type" => $typePost,
        "evaluator" => $teacherId
    ], 'id');
    if (count($pPost) != 0){
        foreach ($pPost as $p){
            Core::$systemDB->delete("participation", ["id" => $p]);
        }
    }
    $date1 = "2022-08-20 17:16:50";
    $postDate1 = date('Y-m-d H:i:s', strtotime($date1));

    $date2 = "2022-08-20 19:30:22";
    $postDate2 = date('Y-m-d H:i:s', strtotime($date2));
    Core::$systemDB->insert('participation',
        [
            'user' => $userId,
            'course' => $courseId,
            "description" => $descriptionPost . 1,
            "date" => $postDate1,
            "type" => $typePost,
            "post" => "mod/peerforum/discuss.php?d=200&parent=1",
            "evaluator" => $teacherId
        ]
    );
    Core::$systemDB->insert('participation',
        [
            'user' => $userId,
            'course' => $courseId,
            "description" => $descriptionPost . 1,
            "date" => $postDate2,
            "type" => $typePost,
            "post" => "mod/peerforum/discuss.php?d=201&parent=1",
            "evaluator" => $teacherId
        ]
    );

}

function setPeriodicMinuteStreak($name){
    // Minute : peergrade every 30 minutes
    $courseId = $GLOBALS['courseId'];
    $userId = $GLOBALS['userId'];
    $userId2 = $GLOBALS['userId2'];
    $teacherId = $GLOBALS['teacherId'];


    $type = 'peergraded post';

    $streakId = Core::$systemDB->select('streak', ["name" => $name, "course" => $courseId], "id");
    if (empty($streakId)){
        setUpStreak($name, "Attend 3 consecutive x.", null, 3,
            30, "minutes", 100, null, false, false, true,
            false);
    }

    // adds participations if needed
    $p = Core::$systemDB->selectMultiple('participation', [
        'evaluator' => $userId,
        'course' => $courseId,
        "type" => $type,
    ], 'id');

    // adss participations
    if (count($p) != 0){
        foreach ($p as $p1){
            Core::$systemDB->delete("participation", ["id" => $p]);
        }
    }
    $date1 = "2022-08-20 17:16:50";
    $postDate1 = date('Y-m-d H:i:s', strtotime($date1));
    $date2 = "2022-08-20 17:47:22";
    $postDate2 = date('Y-m-d H:i:s', strtotime($date2));
    Core::$systemDB->insert('participation',
        [
            'user' => $userId2,
            'course' => $courseId,
            "date" => $postDate1,
            "type" => $type,
            "rating" => 4,
            "evaluator" => $userId
        ]
    );
    Core::$systemDB->insert('participation',
        [
            'user' => $userId2,
            'course' => $courseId,
            "date" => $postDate2,
            "type" => $type ,
            "rating" => 4,
            "evaluator" => $userId
        ]
    );

}

*/

function setPeriodicStreak($name, $periodicity, $periodicityTime, $description, $type, $isCount = false){
    $courseId = $GLOBALS['courseId'];
    $userId = $GLOBALS['userId'];
    $teacherId = $GLOBALS['teacherId'];

    $streakId = Core::$systemDB->select('streak', ["name" => $name, "course" => $courseId], "id");
    if (empty($streakId)){
        setUpStreak($name, $description, null, 3,
            $periodicity, $periodicityTime, 100, 2, false, $isCount, true,
            false);
    }

    $date1 = "2022-08-20 17:16:50";
    $postDate1 = date('Y-m-d H:i:s', strtotime($date1));

    if ($periodicityTime == "Days" || $periodicityTime == "Weeks"){
        if ($periodicityTime == "Weeks"){
            $periodicity = $periodicity * 7;
        }
        $toAdd = "+" . $periodicity . " days";
    } elseif ($periodicityTime == "Hours"){
        $toAdd = "+" . $periodicity . " hours";
    }  else if($periodicityTime == "Minutes"){
        $toAdd = "+" . $periodicity . " minutes";
    } else{
        return;
    }
    $postDate2 = date('Y-m-d', strtotime($postDate1. $toAdd));

    if ($type == 'graded post'){
        $descriptionSubmission = "Skill Tree, Re: Skill ";

        $pSubmission = Core::$systemDB->selectMultiple('participation', [
            'user' => $userId,
            'course' => $courseId,
            "type" => $type,
            "evaluator" => $teacherId
        ], 'id');
        if (count($pSubmission) != 0){
            foreach ($pSubmission as $p){
                Core::$systemDB->delete("participation", ["id" => $p]);
            }
        }
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionSubmission . 1,
                "date" => date('Y-m-d H:i:s'),
                "type" => $type,
                "post" => "mod/peerforum/discuss.php?d=200#1",
                "rating" => 4,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionSubmission . 2,
                "date" => date('Y-m-d H:i:s'),
                "type" => $type,
                "post" => "mod/peerforum/discuss.php?d=201#1",
                "rating" => 4,
                "evaluator" => $teacherId
            ]
        );

        // adds post participations if needed
        $typePost = 'peerforum add post';
        $descriptionPost = "Re: Skill ";
        $pPost = Core::$systemDB->selectMultiple('participation', [
            'user' => $userId,
            'course' => $courseId,
            "type" => $typePost,
            "evaluator" => $teacherId
        ], 'id');
        if (count($pPost) != 0){
            foreach ($pPost as $p){
                Core::$systemDB->delete("participation", ["id" => $p]);
            }
        }

        $date1 = "2022-08-20 17:16:50";
        $postDate1 = date('Y-m-d H:i:s', strtotime($date1));
        $date2 = "2022-08-28 17:16:50";
        $postDate2 = date('Y-m-d H:i:s', strtotime($date2));
        
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionPost . 1,
                "date" => $postDate1,
                "type" => $typePost,
                "post" => "mod/peerforum/discuss.php?d=200&parent=1",
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "description" => $descriptionPost . 2,
                "date" => $postDate2,
                "type" => $typePost,
                "post" => "mod/peerforum/discuss.php?d=201&parent=1",
                "evaluator" => $teacherId
            ]
        );
    }
    elseif ($type == 'peergraded post'){
        $userId2 = $GLOBALS['userId2'];
        $pSubmission = Core::$systemDB->selectMultiple('participation', [
            'user' => $userId2,
            'course' => $courseId,
            "type" => $type,
            "rating" => 4,
            "evaluator" => $userId
        ], 'id');
        if (count($pSubmission) != 0){
            foreach ($pSubmission as $p){
                Core::$systemDB->delete("participation", ["id" => $p]);
            }
        }
        Core::$systemDB->insert('participation',
            [
                'user' => $userId2,
                'course' => $courseId,
                "date" => $postDate1,
                "type" => $type,
                "rating" => 4,
                "evaluator" => $userId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId2,
                'course' => $courseId,
                "date" => $postDate2,
                "type" => $type ,
                "rating" => 4,
                "evaluator" => $userId
            ]
        );
    }
    else{
        $pSubmission = Core::$systemDB->selectMultiple('participation', [
            'user' => $userId,
            'course' => $courseId,
            "type" => $type,
            "evaluator" => $teacherId
        ], 'id');
        if (count($pSubmission) != 0){
            foreach ($pSubmission as $p){
                Core::$systemDB->delete("participation", ["id" => $p]);
            }
        }
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "date" => $postDate1,
                "type" => $type,
                "rating" => 4,
                "evaluator" => $teacherId
            ]
        );
        Core::$systemDB->insert('participation',
            [
                'user' => $userId,
                'course' => $courseId,
                "date" => $postDate2,
                "type" => $type ,
                "rating" => 4,
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
    setUpForConsecutiveMaxGradesStreak("Practitioner", true);
    setUpForSuperlativeArtistStreak("Superlative Artist");
    // weekly : do a skill every week
    //setPeriodicStreak("Weekly", 1, "Weeks", "do a skill every week", "graded post");
    // clockwise : do a skill every 2 hours
    //setPeriodicStreak("ClockWise", 2, "Hours", "do a skill every 2 hours", "graded post");
    // Minute : peergrade every 30 minutes
    //setPeriodicStreak("Minute", 30, "Minutes", "peergrade every 30 minutes", "peergraded post");
    // Peergrader : peergrade every 3 days
    //setPeriodicStreak("Peergrader", 3, "Days", "peergrade every 3 days", "peergraded post");
    // Monthly check : do 2 peergrades in 3 weeks time   (isPeriodic & isCount)
    //setPeriodicStreak("Monthly check", 3, "Weeks", "do 2 peergrades in 3 weeks time", "peergraded post", true);

    Course::newExternalData($courseId, True);
    
    $award =  getAward("Stalker");
    $award2 = getAward("Lab Stalker");
    $award3 = getAward("Sage");
    $award4 = getAward("Practitioner");
    $award5 = getAward("Superlative Artist");
    $award6 = getAward("Constant Gardener");

    //$award7 = getAward("Weekly");
    //$award8 = getAward("ClockWise");
    //$award9 = getAward("Minute");
    //$award10 = getAward("Peergrader");
    //$award11 = getAward("Monthly check");

    // look for award
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
        deleteAward("Lab Stalker");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['lab_stalker'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Lab Stalker streak not awarded."];
    }
    if (!empty($award3)){
        $GLOBALS['success']++;
        $GLOBALS['sage'] =  ["success", "<strong style='color:green; '>Success:</strong> Sage streak successfully awarded."];
        deleteAward("Sage");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['sage'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Sage streak not awarded."];
    }
    if (!empty($award4)){
        $GLOBALS['success']++;
        $GLOBALS['practitioner'] =  ["success", "<strong style='color:green; '>Success:</strong> Practitioner streak successfully awarded."];
        deleteAward("Practitioner");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['practitioner'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Practitioner streak not awarded.."];
    }
    if (!empty($award5)){
        $GLOBALS['success']++;
        $GLOBALS['superlative_artist'] =  ["success", "<strong style='color:green; '>Success:</strong> Superlative Artist streak successfully awarded."];
        deleteAward("Superlative Artist");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['superlative_artist'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Superlative Artist streak not awarded."];
    }
    if (!empty($award6)){
        $GLOBALS['success']++;
        $GLOBALS['constant_gardener'] =  ["success", "<strong style='color:green; '>Success:</strong> Constant Gardener streak successfully awarded."];
        deleteAward("Constant Gardener");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['constant_gardener'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Constant Gardener streak not awarded."];
    }
    /*
    if (!empty($award7)){
        $GLOBALS['success']++;
        $GLOBALS['periodic1'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak successfully awarded."];
        deleteAward("Weekly");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['periodic1'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Sstreak not awarded."];
    }
    if (!empty($award8)){
        $GLOBALS['success']++;
        $GLOBALS['periodic2'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak successfully awarded."];
        deleteAward("ClockWise");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['periodic2'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Streak not awarded."];
    }
    if (!empty($award9)){
        $GLOBALS['success']++;
        $GLOBALS['periodic3'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak successfully awarded."];
        deleteAward("Minute");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['periodic3'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Streak not awarded."];
    }
    if (!empty($award10)){
        $GLOBALS['success']++;
        $GLOBALS['periodic4'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak successfully awarded."];
        deleteAward("Peergrader");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['periodic4'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Streak not awarded."];
    }
    if (!empty($award11)){
        $GLOBALS['success']++;
        $GLOBALS['periodic5'] =  ["success", "<strong style='color:green; '>Success:</strong> Streak successfully awarded."];
        deleteAward("Monthly check");
    }else{
        $GLOBALS['fail']++;
        $GLOBALS['periodic5'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Streak not awarded."];
    }    */


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

$info = $GLOBALS["stalker"][0] . $GLOBALS["lab_stalker"][0] ;
$countedInfo = countInfos($info, 2);
echo "<tr>";
echo "<td rowspan='6' style='border: 1px solid black; padding: 5px;'>MCP 2021/2022 Streaks</td>";
echo "<td rowspan='2'style='border: 1px solid black; padding: 5px;'> Consecutive attendance streaks.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["stalker"][1] . "</td>";
echo "<td style='border: 1px solid black; padding: 5px;background-color:" . $countedInfo[4] . ";'> " . $countedInfo[1] . "/2</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["lab_stalker"][1] . "</td>";
echo "<td style='border: 1px solid black; padding: 5px;background-color:" . $countedInfo[4] . ";'> " . $countedInfo[1] . "/2</td>";
echo "</tr>";

$info = $GLOBALS["sage"][0] . $GLOBALS["practitioner"][0] ;
$countedInfo = countInfos($info, 2);
echo "<tr>";
echo "<td rowspan='2'style='border: 1px solid black; padding: 5px;'> Consecutive grades streaks.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["sage"][1] . "</td>";
echo "<td style='border: 1px solid black; padding: 5px;background-color:" . $countedInfo[4] . ";'> " . $countedInfo[1] . "/2</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["practitioner"][1] . "</td>";
echo "<td style='border: 1px solid black; padding: 5px;background-color:" . $countedInfo[4] . ";'> " . $countedInfo[1] . "/2</td>";
echo "</tr>";

$info = $GLOBALS["superlative_artist"][0] ;
$countedInfo = countInfos($info, 1);
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Consecutive rating streaks.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["superlative_artist"][1] . "</td>";
echo "<td style='border: 1px solid black; padding: 5px;background-color:" . $countedInfo[4] . ";'> " . $countedInfo[1] . "/1 </td>";
echo "</tr>";

$info = $GLOBALS["constant_gardener"][0] ;
$countedInfo = countInfos($info, 1);
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Periodic streaks.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["constant_gardener"][1] . "</td>";
echo "<td style='border: 1px solid black; padding: 5px;background-color:" . $countedInfo[4] . ";'> " . $countedInfo[1] . "/1</td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='2' rowspan='4' style='border: 1px solid black; padding: 5px;'>Periodic Streaks</td>";

// $date = date('Y-m-d H:i:s'); -> gets current date - apply this when inserting participations regarding periodic streaks
// isPeriodic

echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Do a skill every week.' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
//echo "<td style='border: 1px solid black; padding: 5px;background-color:" . $countedInfo[4] . ";'>" . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Peergrade your colleagues every 3 days' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Do a skill every 2 hours' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;background-color:" . $countedInfo[4] . ";'>" . $GLOBALS["success"] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Peergrade your colleagues every 30 minutes' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

// isPeriodic & isCount
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'> Award 'Do 2 peegrades in 3 weeks time' streak.</td>";
echo "<td style='border: 1px solid black; padding: 5px;'> " . $GLOBALS["success"] . "</td>";
echo "</tr>";

$percentage = ($GLOBALS['success'] / ($GLOBALS['success'] + $GLOBALS['fail'])) * 100;
echo "<tr>";
echo "<td colspan='3' style='border: 1px solid black; padding: 5px;'><strong>Total</strong></td>";
if ($percentage == 100) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
} else if ($percentage < 50) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
} else {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFF1AA'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
}
echo "</tr>";
echo "</table>";

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
}
