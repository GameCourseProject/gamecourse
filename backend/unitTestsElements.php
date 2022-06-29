<?php

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use Modules\Badges\Badges;
use Modules\Streaks\Streaks;

Core::init();

echo "<h2>Automated Test Script</h2>";

global $success;
global $fail;
$GLOBALS['success'] = 0;
$GLOBALS['fail'] = 0;
global $courseInfo;
$GLOBALS['courseInfo'] = null;

global $lg_1;
$GLOBALS['lg_1'] = [];

global $fi_1;
$GLOBALS['fi_1'] = [];
global $fi_2;
$GLOBALS['fi_2'] = [];
global $fi_3;
$GLOBALS['fi_3'] = [];
global $fi_4;
$GLOBALS['fi_4'] = [];

global $p_1;
$GLOBALS['p_1'] = [];
global $p_2;
$GLOBALS['p_2'] = [];
global $p_3;
$GLOBALS['p_3'] = [];

global $dl_1;
$GLOBALS['dl_1'] = [];
global $dl_2;
$GLOBALS['dl_2'] = [];
global $dl_3;
$GLOBALS['dl_3'] = [];

global $df_1;
$GLOBALS['df_1'] = [];
global $df_2;
$GLOBALS['df_2'] = [];
global $df_3;
$GLOBALS['df_3'] = [];

global $dv_1;
$GLOBALS['dv_1'] = [];
global $dv_2;
$GLOBALS['dv_2'] = [];
global $dv_3;
$GLOBALS['dv_3'] = [];

global $u_1;
$GLOBALS['u_1'] = [];
global $u_2;
$GLOBALS['u_2'] = [];
global $u_3;
$GLOBALS['u_3'] = [];

global $cou_1;
$GLOBALS['cou_1'] = [];
global $cou_2;
$GLOBALS['cou_2'] = [];
global $cou_3;
$GLOBALS['cou_3'] = [];

global $c_1;
$GLOBALS['c_1'] = [];
global $c_2;
$GLOBALS['c_2'] = [];
global $c_3;
$GLOBALS['c_3'] = [];

$GLOBALS['courseInfo'] = testCourse();

$GLOBALS['dictionaryInfo'] = testDictionary();

function createUser($course, $count){
    // OR RUN THIS SCRIPT AFTER THE UNITTESTS.PHP ONE
}

function testPeergradingStreak($course, $count){
    /* function to test peergrading streak - needs access to Moodle's database */

    //         Core::$systemDB->insert("user_page_history", ["user" => $viewer, "page" => $pageId]);
}

function testPeriodicStreak($course, $periodicity, $periodicityTime, $count){

}

function testCountStreak($course, $count){

}

function testAtMostStreak($course, $count){
    if (Core::$systemDB->select(Streaks::TABLE_CONFIG, ["course" => $course])) {

    }
    else{
        // echo "<h3 style='font-weight: normal'>
        // <strong style='color:#F7941D;'>Warning:</strong> Make sure you authenticate to access to Google Sheets for course " . $course . "."
        //     . "</h3>";
        $GLOBALS['p_3'] =  ["warning", "<strong style='color:#F7941D; '>Warning:</strong>Make sure you authenticate to access to Google Sheets for course " . $course . "."];
    }
}

/*************************** Auxiliar functions ***************************/







echo "<table style=' border: 1px solid black; border-collapse: collapse; table-layout:fixed'>";
//Nome das colunas
echo "<tr>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Group</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Test</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Score</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Coverage</strong></th>";
echo "</tr>";
// Login Picture
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'>Login Picture</td>";
echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS['lg_1'][1] . "</td>";
if ($GLOBALS['lg_1'][0] == "warning") {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5;'>0%</br>(0/1)</td>";
} else if ($GLOBALS['lg_1'][0] == "success") {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897'>100%</br>(1/1)</td>";
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897;'>100%</br>(1/1)</td>";
} else if ($GLOBALS['lg_1'][0] == "fail") {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5;'>0%</br>(0/1)</td>";
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color: #C7E897;'>100%</br>(1/1)</td>";
}
echo "</tr>";
// Fénix Import
echo "<tr>";
if ($GLOBALS['courseInfo'] == 1) {
    if ($GLOBALS["fenixInfo"] == 0) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>Fénix Import</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'><strong style='color:#F7941D;'>Warning:</strong> To test fenix, please enable the fenix module.</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/4)</td>";
        echo "</tr>";
    } else {
        $info = $GLOBALS['fi_1'][0] . $GLOBALS["fi_2"][0] . $GLOBALS["fi_3"][0] . $GLOBALS["fi_4"][0];
        $countedInfo = countInfos($info, 4);
        echo "<td rowspan='4' style='border: 1px solid black; padding: 5px;'>Fénix Import</td>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS['fi_1'][1] . "</td>";
        echo "<td rowspan='4' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/4)</td>";
        echo "<td rowspan='4' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (4 - $countedInfo[0]) . "/4)</td>";
        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["fi_2"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["fi_3"][1] . " </td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px'>" . $GLOBALS["fi_4"][1] . "</td>";
        echo "</tr>";
    }
} else {
    checkCourseTable("Fénix Import", 4);
}

//Import/Export Course Users
if ($GLOBALS['courseInfo'] == 1) {
    $info = $GLOBALS["cou_1"][0] . $GLOBALS["cou_2"][0] . $GLOBALS["cou_3"][0];
    $countedInfo = countInfos($info, 3);
    echo "<tr>";
    echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;'>Import/Export Course Users</td>";
    echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["cou_1"][1] . "</td>";

    echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/3)</td>";
    echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (3 - $countedInfo[0]) . "/3)</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["cou_2"][1] . "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["cou_3"][1] . "</td>";
    echo "</tr>";
} else {
    checkCourseTable("Import/Export Course Users", 3);
}
//Import/Export Courses
if ($GLOBALS['courseInfo'] == 1) {
    $info = $GLOBALS["c_1"][0] . $GLOBALS["c_2"][0] . $GLOBALS["c_3"][0];
    $countedInfo = countInfos($info, 3);
    echo "<tr>";
    if ($countedInfo[0] > 0) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>Import/Export Courses</td>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["c_1"][1] . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/3)</td>";
    } else {
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;'>Import/Export Courses</td>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["c_1"][1] . "</td>";
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/3)</td>";
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (3 - $countedInfo[0]) . "/3)</td>";

        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["c_3"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["c_3"][1] . "</td>";
        echo "</tr>";
    }
} else {
    checkCourseTable("Import/Export Courses", 3);
}
//total

$percentage = ($GLOBALS['success'] / ($GLOBALS['success'] + $GLOBALS['fail'])) * 100;
$percentageCoverage = (($GLOBALS['success'] + $GLOBALS['fail']) / 26) * 100;

echo "<tr>";
echo "<td colspan='2' style='border: 1px solid black; padding: 5px;'><strong>Total</strong></td>";
if ($percentage == 100) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
} else if ($percentage < 50) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
} else {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFF1AA'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
}

if ($percentageCoverage == 100) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897'><strong>" . round($percentageCoverage, 2) . "%</br>(" . ($GLOBALS['success'] + $GLOBALS['fail']) . "/26)</strong></td>";
} else if ($percentageCoverage < 50) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'><strong>" . round($percentageCoverage, 2) . "%</br>(" . ($GLOBALS['success'] + $GLOBALS['fail']) . "/26)</strong></td>";
} else {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFF1AA'><strong>" . round($percentageCoverage, 2) . "%</br>(" . ($GLOBALS['success'] + $GLOBALS['fail']) . "/26)</strong></td>";
}
echo "</tr>";
echo "</table>";


function countInfos($info, $nrTotal)
{
    $warningCount = substr_count($info, "warning");
    $successCount = substr_count($info, "success");
    $percentageScore = round(($successCount / $nrTotal) * 100, 2);
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

function checkCourseTable($name, $nrTests)
{

    $semCurso = "<strong style='color:#F7941D;'>Warning:</strong> If you desire to test the whole script, please specify a course id as an URL parameter: ?course=1 or &course=1.";
    if (array_key_exists("course", $_GET)) {
        $cursoNaoExiste = "<strong style='color:#F7941D;'>Warning:</strong> There is no course with id " . $_GET["course"];
    }
    if ($GLOBALS['courseInfo'] == 0) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>" . $name . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>" . $semCurso . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/" . $nrTests . ")</td>";
        echo "</tr>";
    } else if ($GLOBALS['courseInfo'] == -1) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>" . $name . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>" . $cursoNaoExiste . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/" . $nrTests . ")</td>";
        echo "</tr>";
    }
}
